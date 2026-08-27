<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): RedirectResponse|View
    {
        if (Auth::check()) {
            return $this->redirectWithoutCache(redirect()->route('dashboard'));
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return $this->redirectWithoutCache(
            redirect()->intended(route('dashboard', absolute: false)),
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->redirectWithoutCache(
            redirect()
                ->route('login')
                ->with('status', 'Anda berhasil logout.'),
        );
    }

    private function redirectWithoutCache(RedirectResponse $redirect): RedirectResponse
    {
        return $redirect->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
