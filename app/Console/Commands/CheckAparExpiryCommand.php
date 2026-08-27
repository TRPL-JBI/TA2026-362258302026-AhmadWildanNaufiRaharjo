<?php

namespace App\Console\Commands;

use App\Services\Apar\AparExpiryNotificationService;
use Illuminate\Console\Command;

class CheckAparExpiryCommand extends Command
{
    protected $signature = 'apar:check-expiry';

    protected $description = 'Periksa APAR mendekati expired (≤30 hari) dan sudah expired, lalu kirim notifikasi in-app & Web Push ke Petugas K3LH';

    public function handle(AparExpiryNotificationService $service): int
    {
        $result = $service->checkAndNotify();

        $this->info(sprintf(
            'Selesai: %d mendekati expired (≤30 hari), %d sudah expired.',
            $result['warning'],
            $result['expired'],
        ));

        return self::SUCCESS;
    }
}
