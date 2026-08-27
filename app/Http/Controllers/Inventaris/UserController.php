<?php

namespace App\Http\Controllers\Inventaris;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventaris\StoreUserRequest;
use App\Http\Requests\Inventaris\UpdateUserRequest;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with('lokasi:id,nama_lokasi')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('username', 'like', "%{$search}%")
                        ->orWhere('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_lengkap')
            ->paginate(10)
            ->withQueryString();

        $laboratorium = Lokasi::query()
            ->where('jenis_lokasi', 'Laboratorium')
            ->orderBy('nama_lokasi')
            ->get(['id', 'nama_lokasi']);

        return view('inventaris.user', [
            'users' => $users,
            'search' => $search,
            'roles' => User::roles(),
            'laboratorium' => $laboratorium,
            'roleBadgeClass' => [
                'Petugas K3LH' => 'bg-blue-50 text-blue-800 border-blue-200',
                'Satpam' => 'bg-amber-50 text-amber-900 border-amber-200',
                'Kalab' => 'bg-violet-50 text-violet-800 border-violet-200',
                'Pimpinan' => 'bg-slate-50 text-slate-800 border-slate-200',
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::query()->create([
            'username' => $validated['username'],
            'password' => $validated['password'],
            'nama_lengkap' => $validated['nama_lengkap'],
            'role' => $validated['role'],
            'lokasi_id' => $validated['lokasi_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('inventaris.user')
            ->with('success', 'Akun user berhasil ditambahkan.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $authUser = $request->user();

        if ($authUser !== null && $user->is($authUser)) {
            if (array_key_exists('is_active', $validated) && ! $validated['is_active']) {
                return redirect()
                    ->route('inventaris.user')
                    ->with('error', 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.');
            }

            if ($validated['role'] !== $authUser->role) {
                return redirect()
                    ->route('inventaris.user')
                    ->with('error', 'Anda tidak dapat mengubah role akun sendiri.');
            }
        }

        $user->fill([
            'username' => $validated['username'],
            'nama_lengkap' => $validated['nama_lengkap'],
            'role' => $validated['role'],
            'lokasi_id' => $validated['lokasi_id'] ?? null,
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('inventaris.user')
            ->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return redirect()
                ->route('inventaris.user')
                ->with('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.');
        }

        try {
            $user->delete();
        } catch (QueryException) {
            return redirect()
                ->route('inventaris.user')
                ->with('error', 'User tidak dapat dihapus karena masih terhubung ke data patroli, laporan, atau aktivitas lain.');
        }

        return redirect()
            ->route('inventaris.user')
            ->with('success', 'User berhasil dihapus.');
    }
}
