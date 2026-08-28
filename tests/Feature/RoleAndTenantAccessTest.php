<?php

namespace Tests\Feature;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndTenantAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_can_only_open_its_own_management_area(): void
    {
        $company = Company::create(['code' => 'ACME', 'name' => 'Acme', 'is_active' => true]);
        $system = Admin::create(['login_id' => 'system01', 'name' => '管理者', 'password' => 'password', 'force_password_change' => false]);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Employee, 'permission' => UserPermission::CompanyManager]);
        $employee = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Employee]);

        $this->actingAs($system, 'admin')->get('/manage/companies')->assertOk();
        $this->actingAs($admin)->get('/company/employees')->assertOk();
        $this->actingAs($employee)->get('/employee/payslips')->assertOk();
        $this->actingAs($employee)->get('/company/employees')->assertForbidden();
    }

    public function test_company_manager_cannot_edit_an_employee_from_another_company(): void
    {
        $companyA = Company::create(['code' => 'A', 'name' => 'A社', 'is_active' => true]);
        $companyB = Company::create(['code' => 'B', 'name' => 'B社', 'is_active' => true]);
        $adminA = User::factory()->create(['company_id' => $companyA->id, 'role' => UserRole::Employee, 'permission' => UserPermission::CompanyManager]);
        $employeeB = User::factory()->create([
            'company_id' => $companyB->id,
            'role' => UserRole::Employee,
            'employee_number' => 'B001',
        ]);

        $this->actingAs($adminA)
            ->get(route('company.employees.edit', $employeeB))
            ->assertNotFound();
    }
}
