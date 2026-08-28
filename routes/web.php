<?php

use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\PasswordController as AdminPasswordController;
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

Route::middleware('guest:admin')->group(function () {
    Route::get('/manage', [AdminLoginController::class, 'create'])->name('manage.login');
    Route::post('/manage', [AdminLoginController::class, 'store'])->middleware('throttle:6,1')->name('manage.login.store');
});

Route::middleware('auth:admin')->prefix('manage')->name('manage.')->group(function () {
    Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
    Route::get('password', [AdminPasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [AdminPasswordController::class, 'update'])->name('password.update');

    Route::middleware('password.changed:admin')->group(function () {
        Route::resource('companies', CompanyController::class)->except('show');
        Route::post('admins/{administrator}/reset-password', [AdministratorController::class, 'resetPassword'])->name('admins.reset-password');
        Route::post('admins/{administrator}/unlock', [AdministratorController::class, 'unlock'])->name('admins.unlock');
        Route::resource('admins', AdministratorController::class)->parameters(['admins' => 'administrator'])->except('show');
        Route::resource('templates', PayslipTemplateController::class)->except('show');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'))->middleware('password.changed');
    Route::get('/dashboard', DashboardController::class)->middleware('password.changed')->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/account/password', [PasswordController::class, 'edit'])->name('account.password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('account.password.update');

    Route::prefix('company')->name('company.')->middleware(['password.changed', 'company_manager'])->group(function () {
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::get('employees/csv-template', [EmployeeController::class, 'csvTemplate'])->name('employees.csv-template');
        Route::post('employees/import', [EmployeeController::class, 'import'])->name('employees.import');
        Route::post('employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])->name('employees.reset-password');
        Route::post('employees/{employee}/unlock', [EmployeeController::class, 'unlock'])->name('employees.unlock');
        Route::post('employees/{employee}/send-email', [EmployeeController::class, 'sendEmail'])->name('employees.send-email');
        Route::resource('employees', EmployeeController::class)->except('show');
        Route::post('settings/prepare', [PayslipSettingController::class, 'prepare'])->name('settings.prepare');
        Route::post('settings/confirm', [PayslipSettingController::class, 'confirm'])->name('settings.confirm');
        Route::resource('settings', PayslipSettingController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('email-templates', EmailTemplateController::class)->except('show');
        Route::get('payroll/{batch}/csv-template', [PayrollBatchController::class, 'csvTemplate'])->name('payroll.csv-template');
        Route::post('payroll/{batch}/import', [PayrollBatchController::class, 'import'])->name('payroll.import');
        Route::post('payroll/{batch}/approve', [PayrollBatchController::class, 'approve'])->name('payroll.approve');
        Route::get('payroll/{batch}/payslips/{payslip}', [PayrollBatchController::class, 'showPayslip'])->name('payroll.payslips.show');
        Route::resource('payroll', PayrollBatchController::class)->parameters(['payroll' => 'batch'])->only(['index', 'create', 'store', 'show']);
    });

    Route::prefix('employee')->name('employee.')->middleware('password.changed')->group(function () {
        Route::get('payslips', [PayslipController::class, 'index'])->name('payslips.index');
        Route::get('payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
        Route::get('payslips/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('payslips.pdf');
    });
});
