<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SmsController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\MapBoundaryController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:100,1')->group(function () {

    // Public auth routes
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('/auth/mfa/verify', [AuthController::class, 'mfaVerify'])
        ->middleware('throttle:10,1');
    Route::post('/sms/receive', [SmsController::class, 'receive']); // webhook, no auth

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Reports — device binding for ward agent submissions
        Route::get('/reports', [ReportController::class, 'index']);
        Route::post('/reports', [ReportController::class, 'store'])
            ->middleware([
                'App\Http\Middleware\DeviceBinding',
                'App\Http\Middleware\RateLimitReports',
            ]);
        Route::post('/reports/batch', [ReportController::class, 'batch'])
            ->middleware([
                'App\Http\Middleware\DeviceBinding',
                'App\Http\Middleware\RateLimitReports',
            ]);
        Route::get('/reports/{report}', [ReportController::class, 'show']);
        Route::put('/reports/{report}', [ReportController::class, 'update']);

        // Supervisor + admin actions
        Route::middleware('App\Http\Middleware\CheckRole:super_admin,supervisor')->group(function () {
            Route::post('/reports/{report}/escalate', [ReportController::class, 'escalate']);
            Route::post('/reports/{report}/verify', [ReportController::class, 'verify']);
            Route::post('/reports/{report}/flag', [ReportController::class, 'flag']);
            Route::post('/reports/{report}/clarification', [ReportController::class, 'requestClarification']);
            Route::post('/reports/{report}/note', [ReportController::class, 'addNote']);
        });

        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('/summary', [DashboardController::class, 'summary']);
            // Agent login status is management-only; field agents have no visibility into peer activity
            Route::get('/agents/login-status', [DashboardController::class, 'agentLoginStatus'])
                ->middleware('App\Http\Middleware\CheckRole:super_admin,state_admin,supervisor');
            Route::get('/submissions/by-ward', [DashboardController::class, 'byWard']);
            Route::get('/submissions/by-lga', [DashboardController::class, 'byLga']);
            Route::get('/submissions/by-party', [DashboardController::class, 'byParty']);
            Route::get('/submissions/by-senatorial-district', [DashboardController::class, 'bySenatorialDistrict']);
            Route::get('/candidates', [DashboardController::class, 'candidates']);
            Route::get('/security-incidents', [DashboardController::class, 'securityIncidents']);
            Route::get('/map/boundaries', MapBoundaryController::class);
        });

        // SMS (outbound) - admin/supervisor only
        Route::post('/sms/send', [SmsController::class, 'send'])
            ->middleware('App\Http\Middleware\CheckRole:super_admin,supervisor');

        // Audit logs — management roles only; field agents must not view audit trails
        Route::get('/audit', [AuditController::class, 'index'])
            ->middleware('App\Http\Middleware\CheckRole:super_admin,state_admin,supervisor');
        Route::get('/audit/export', [AuditController::class, 'export'])
            ->middleware('App\Http\Middleware\CheckRole:super_admin,state_admin');

        // Reports export — restricted to management; supervisor sees their LGA via forUser() scope
        Route::get('/export/{type}', [ExportController::class, 'export'])
            ->middleware('App\Http\Middleware\CheckRole:super_admin,state_admin,supervisor');

        // User management - super admin only
        Route::apiResource('/users', UserController::class)
            ->middleware('App\Http\Middleware\CheckRole:super_admin');
    });
});
