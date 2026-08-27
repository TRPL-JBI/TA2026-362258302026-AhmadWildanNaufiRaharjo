<?php

namespace App\Services\Apar;

use App\Models\Apar;
use App\Models\Notifikasi;
use App\Models\User;
use App\Notifications\WebPushAlertNotification;
use App\Support\NotifikasiLink;
use App\Support\WebPushConfig;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class AparExpiryNotificationService
{
    /**
     * @return array{warning: int, expired: int}
     */
    public function checkAndNotify(?Carbon $today = null): array
    {
        $today ??= Carbon::today();

        $petugasUsers = $this->petugasK3lhUsers();
        if ($petugasUsers->isEmpty()) {
            return ['warning' => 0, 'expired' => 0];
        }

        $petugasIds = $petugasUsers->pluck('id');
        $warningCount = 0;
        $expiredCount = 0;

        $warningApar = Apar::query()
            ->with('lokasi')
            ->where('is_notified', false)
            ->whereDate('tanggal_expired', '>=', $today)
            ->whereDate('tanggal_expired', '<=', $today->copy()->addDays(30))
            ->get();

        foreach ($warningApar as $apar) {
            $this->notifyAllPetugas($petugasUsers, $petugasIds, $apar, 'warning', $today);
            $apar->update(['is_notified' => true]);
            $warningCount++;
        }

        $expiredApar = Apar::query()
            ->with('lokasi')
            ->whereDate('tanggal_expired', '<', $today)
            ->get();

        foreach ($expiredApar as $apar) {
            if ($this->hasExpiredNotification($apar)) {
                continue;
            }

            $this->notifyAllPetugas($petugasUsers, $petugasIds, $apar, 'expired', $today);
            $expiredCount++;
        }

        return ['warning' => $warningCount, 'expired' => $expiredCount];
    }

    /**
     * @return Collection<int, User>
     */
    private function petugasK3lhUsers(): Collection
    {
        return User::query()
            ->where('role', 'Petugas K3LH')
            ->where('is_active', true)
            ->with('pushSubscriptions')
            ->get();
    }

    private function hasExpiredNotification(Apar $apar): bool
    {
        return Notifikasi::query()
            ->where('jenis_notifikasi', 'Early Warning APAR')
            ->where('reference_id', $apar->id)
            ->where('judul', 'like', 'APAR sudah expired%')
            ->exists();
    }

    /**
     * @param  Collection<int, User>  $petugasUsers
     * @param  Collection<int, int>  $petugasIds
     */
    private function notifyAllPetugas(Collection $petugasUsers, Collection $petugasIds, Apar $apar, string $type, Carbon $today): void
    {
        [$judul, $pesan] = $this->buildMessage($apar, $type, $today);
        $url = NotifikasiLink::forApar($apar, $type);

        foreach ($petugasIds as $userId) {
            Notifikasi::query()->create([
                'user_id' => $userId,
                'jenis_notifikasi' => 'Early Warning APAR',
                'judul' => $judul,
                'pesan' => $pesan,
                'reference_id' => $apar->id,
                'is_read' => false,
            ]);
        }

        if (! WebPushConfig::isConfigured()) {
            return;
        }

        foreach ($petugasUsers as $user) {
            $this->sendWebPush($user, $judul, $pesan, $url);
        }
    }

    private function sendWebPush(User $user, string $judul, string $pesan, string $url): void
    {
        if ($user->pushSubscriptions->isEmpty()) {
            return;
        }

        try {
            $user->notify(new WebPushAlertNotification($judul, $pesan, $url));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function buildMessage(Apar $apar, string $type, Carbon $today): array
    {
        $lokasi = $apar->lokasi?->nama_lokasi ?? 'Lokasi tidak diketahui';
        $expiredFormatted = $apar->tanggal_expired->translatedFormat('d M Y');
        $label = $apar->jenisKapasitasLabel();

        if ($type === 'expired') {
            return [
                "APAR sudah expired: {$apar->kode_apar}",
                sprintf(
                    'APAR %s (%s) di %s sudah expired sejak %s. Segera ganti atau perbarui tanggal expired di Inventaris APAR.',
                    $apar->kode_apar,
                    $label,
                    $lokasi,
                    $expiredFormatted,
                ),
            ];
        }

        $daysLeft = (int) $today->diffInDays($apar->tanggal_expired, false);

        return [
            "APAR akan expired: {$apar->kode_apar}",
            sprintf(
                'APAR %s (%s) di %s akan expired pada %s (%d hari lagi). Segera periksa di Inventaris APAR.',
                $apar->kode_apar,
                $label,
                $lokasi,
                $expiredFormatted,
                $daysLeft,
            ),
        ];
    }
}
