<?php

use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\MapBoundaryController;
use App\Livewire\Admin\AuditLogViewer;
use App\Livewire\Admin\CandidateManagement;
use App\Livewire\Admin\LgaManagement;
use App\Livewire\Admin\PartyManagement;
use App\Livewire\Admin\PollingStationManagement;
use App\Livewire\Admin\PollingUnitAgentForm;
use App\Livewire\Admin\PollingUnitAgentIndex;
use App\Livewire\Admin\SenatorialDistrictManagement;
use App\Livewire\Admin\SmsManagement;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\WardManagement;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\MfaVerifyPage;
use App\Livewire\Admin\RolePermissionManager;
use App\Livewire\Agent\AgentHome;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Reports\WardReportForm;
use App\Livewire\Supervisor\ReportInbox;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest-only routes
Route::middleware('guest')->group(function () {
    Route::get('/login', LoginPage::class)->name('login');
});

// MFA verify — accessible to unauthenticated users completing MFA challenge
// Self-protecting: MfaVerifyPage::mount() redirects to login if no pending session
Route::get('/mfa/verify', MfaVerifyPage::class)->name('mfa.verify');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

// Landing page
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        return redirect()->route($user->isFieldAgent() ? 'agent.home' : 'dashboard');
    }

    return view('landing');
})->name('home');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Overview::class)->name('dashboard');
    Route::get('/agent/home', AgentHome::class)->name('agent.home');

    // Map boundaries API (for web dashboard - mirrors /api/v1/dashboard/map/boundaries)
    Route::get('/api/v1/dashboard/map/boundaries', MapBoundaryController::class)
        ->name('dashboard.map.boundaries');

    // Ward agents
    Route::get('/reports/create', WardReportForm::class)->name('reports.create');

    // Supervisors + Super Admin
    Route::middleware('App\Http\Middleware\CheckRole:super_admin,supervisor')->group(function () {
        Route::get('/supervisor/inbox', ReportInbox::class)->name('supervisor.inbox');
        Route::get('/admin/pu-agents', PollingUnitAgentIndex::class)->name('admin.pu-agents');
        Route::get('/admin/pu-agents/create', PollingUnitAgentForm::class)->name('admin.pu-agents.create');
        Route::get('/admin/pu-agents/{agentId}/edit', PollingUnitAgentForm::class)->name('admin.pu-agents.edit');
    });

    // Super Admin only
    Route::middleware('App\Http\Middleware\CheckRole:super_admin')->group(function () {
        Route::get('/admin/users', UserManagement::class)->name('admin.users');
        Route::get('/admin/roles', RolePermissionManager::class)->name('admin.roles');
        Route::get('/admin/audit', AuditLogViewer::class)->name('admin.audit');
        Route::get('/admin/audit/export', [AuditController::class, 'export'])->name('audit.export');
        Route::get('/admin/sms', SmsManagement::class)->name('admin.sms');

        // Configuration Management
        Route::get('/admin/lgas', LgaManagement::class)->name('admin.lgas');
        Route::get('/admin/senatorial-districts', SenatorialDistrictManagement::class)->name('admin.senatorial-districts');
        Route::get('/admin/wards', WardManagement::class)->name('admin.wards');
        Route::get('/admin/polling-stations', PollingStationManagement::class)->name('admin.polling-stations');
        Route::get('/admin/parties', PartyManagement::class)->name('admin.parties');
        Route::get('/admin/candidates', CandidateManagement::class)->name('admin.candidates');
    });
});
