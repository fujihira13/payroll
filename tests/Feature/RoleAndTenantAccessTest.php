<?php

namespace Tests\Feature;

use App\Enums\UserRole;
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
        $system = User::factory()->create(['role' => UserRole::SystemAdmin]);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyAdmin]);
        $employee = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Employee]);

        $this->actingAs($system)->get('/system/companies')->assertOk();
        $this->actingAs($system)->get('/company/employees')->assertForbidden();
        $this->actingAs($admin)->get('/company/employees')->assertOk();
        $this->actingAs($admin)->get('/system/companies')->assertForbidden();
        $this->actingAs($employee)->get('/employee/payslips')->assertOk();
        $this->actingAs($employee)->get('/company/employees')->assertForbidden();
    }

    public function test_company_admin_cannot_edit_an_employee_from_another_company(): void
    {
        $companyA = Company::create(['code' => 'A', 'name' => 'A社', 'is_active' => true]);
        $companyB = Company::create(['code' => 'B', 'name' => 'B社', 'is_active' => true]);
        $adminA = User::factory()->create(['company_id' => $companyA->id, 'role' => UserRole::CompanyAdmin]);
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
