<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            'Satpam' => view('dashboard-satpam', $this->dashboard->dataForSatpamDashboard($user)),
            'Kalab' => view('dashboard-kalab', $this->dashboard->dataForKalabDashboard($user)),
            default => view('dashboard', $this->dashboard->dataForExecutiveDashboard()),
        };
    }
}
