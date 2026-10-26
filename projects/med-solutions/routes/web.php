<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Patient\DashboardController as PatientDashboardController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes - Med Solutions
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/contact', [HomeController::class, 'storeContact'])
    ->middleware('throttle:10,1')
    ->name('contact.store');

// Service Request / Multi-Service Booking & Status Tracking
Route::get('/service-request', [ServiceRequestController::class, 'create'])->name('services.create');
Route::post('/service-request', [ServiceRequestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('services.store');
Route::get('/track', [ServiceRequestController::class, 'track'])->name('services.track');

// Telemedicine Consultation Booking (Pre-Travel & Post-Return)
Route::get('/telemedicine', [ConsultationController::class, 'create'])->name('consultations.create');
Route::post('/telemedicine', [ConsultationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('consultations.store');

/*
|--------------------------------------------------------------------------
| Authenticated Patient & Portal Routes (Laravel Breeze / Auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        if ($request->user()->canManagePortal()) {
            return redirect()->route($request->user()->panelRoute());
        }
        return app(PatientDashboardController::class)->index($request);
    })->name('dashboard');

    Route::get('/patient/dashboard', [PatientDashboardController::class, 'index'])
        ->name('patient.dashboard');

    // Authorized or Temporary-Signed Medical Report Download
    Route::get('/service-requests/{serviceRequest}/medical-report', [ServiceRequestController::class, 'downloadReport'])
        ->name('services.report.download');
});

/*
|--------------------------------------------------------------------------
| Admin & Staff Panel Routes (Role-Based Access Control)
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Admin Panel (admin only): dashboard, CMS content, user & role management
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::patch('/services/{service}', [AdminDashboardController::class, 'updateServiceContent'])
            ->name('services.update');

        Route::patch('/users/{user}/role', [AdminDashboardController::class, 'updateUserRole'])
            ->name('users.update-role');
    });

/*
|--------------------------------------------------------------------------
| Staff Panel (staff only): leads, visas, appointments, telemedicine links
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'staffIndex'])->name('dashboard');
    });

// Lead & telemedicine updates are shared by the admin and staff panels
Route::middleware(['auth', 'role:admin,staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::patch('/service-requests/{serviceRequest}', [AdminDashboardController::class, 'updateServiceRequest'])
            ->name('service-requests.update');

        Route::patch('/consultations/{consultation}', [AdminDashboardController::class, 'updateConsultation'])
            ->name('consultations.update');
    });

if (file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
}
