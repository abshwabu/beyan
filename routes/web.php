<?php

use App\Domain\Academics\Http\Controllers\AcademicYearController;
use App\Domain\Institution\Http\Controllers\DashboardController;
use App\Domain\Institution\Http\Controllers\InstitutionRegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('institution.register');
});

Route::get('/register-institution', [InstitutionRegistrationController::class, 'create'])
    ->name('institution.register');

Route::post('/register-institution', [InstitutionRegistrationController::class, 'store'])
    ->name('institution.register.store');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('academic-years')->name('academic-years.')->group(function () {
        Route::get('/', [AcademicYearController::class, 'index'])->name('index');
        Route::post('/', [AcademicYearController::class, 'store'])->name('store');
        Route::get('/{id}', [AcademicYearController::class, 'show'])->name('show');
        Route::put('/{id}', [AcademicYearController::class, 'update'])->name('update');
        Route::delete('/{id}', [AcademicYearController::class, 'destroy'])->name('destroy');
    });

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('institution.register');
    })->name('logout');
});
