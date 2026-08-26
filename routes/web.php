<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Company\DepartmentController;
use App\Http\Controllers\Company\EmailTemplateController;
use App\Http\Controllers\Company\EmployeeController;
use App\Http\Controllers\Company\PayrollBatchController;
use App\Http\Controllers\Company\PayslipSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Employee\PasswordController;
use App\Http\Controllers\Employee\PayslipController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\System\AdministratorController;
use App\Http\Controllers\System\CompanyController;
use App\Http\Controllers\System\PayslipTemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::prefix('system')->name('system.')->middleware('role:system_admin')->group(function () {
        Route::resource('companies', CompanyController::class)->except('show');
        Route::resource('administrators', AdministratorController::class)->except('show');
        Route::resource('templates', PayslipTemplateController::class)->except('show');
    });

    Route::prefix('company')->name('company.')->middleware('role:company_admin')->group(function () {
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::get('employees/csv-template', [EmployeeController::class, 'csvTemplate'])->name('employees.csv-template');
        Route::post('employees/import', [EmployeeController::class, 'import'])->name('employees.import');
        Route::resource('employees', EmployeeController::class)->except('show');
        Route::resource('settings', PayslipSettingController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::get('email-template', [EmailTemplateController::class, 'edit'])->name('email-template.edit');
        Route::put('email-template', [EmailTemplateController::class, 'update'])->name('email-template.update');
        Route::get('payroll/{batch}/csv-template', [PayrollBatchController::class, 'csvTemplate'])->name('payroll.csv-template');
        Route::post('payroll/{batch}/import', [PayrollBatchController::class, 'import'])->name('payroll.import');
        Route::post('payroll/{batch}/approve', [PayrollBatchController::class, 'approve'])->name('payroll.approve');
        Route::resource('payroll', PayrollBatchController::class)->parameters(['payroll' => 'batch'])->only(['index', 'create', 'store', 'show']);
    });

    Route::prefix('employee')->name('employee.')->middleware('role:employee')->group(function () {
        Route::get('payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::get('payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('payslips.pdf');
        Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    });
});
