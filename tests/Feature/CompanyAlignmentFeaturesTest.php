<?php

namespace Tests\Feature;

use App\Enums\PayslipItemCategory;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Department;
use App\Models\PayslipTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyAlignmentFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_must_change_a_temporary_password_before_management(): void
    {
        $admin = Admin::create([
            'login_id' => 'admin001',
            'name' => '管理者',
            'password' => 'OldPassword1',
            'force_password_change' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')->get('/manage/companies')
            ->assertRedirect(route('manage.password.edit'));

        $this->actingAs($admin, 'admin')->put('/manage/password', [
            'current_password' => 'OldPassword1',
            'password' => 'NewPassword2',
            'password_confirmation' => 'NewPassword2',
        ])->assertRedirect(route('manage.companies.index'));

        $admin->refresh();
        $this->assertFalse($admin->force_password_change);
        $this->assertTrue(Hash::check('NewPassword2', $admin->password));
    }

    public function test_company_creation_also_creates_an_initial_company_manager(): void
    {
        $system = Admin::create([
            'login_id' => 'system001',
            'name' => 'システム管理者',
            'password' => 'Password1',
            'force_password_change' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($system, 'admin')->post('/manage/companies', [
            'code' => 'ACME',
            'name' => 'Acme株式会社',
            'is_active' => '1',
            'initial_admin_login_id' => 'company001',
            'initial_admin_name' => '企業管理者',
            'initial_admin_email' => 'company-admin@example.test',
        ]);

        $response->assertRedirect(route('manage.companies.index'))
            ->assertSessionHas('temporary_password', fn ($password) => is_string($password) && strlen($password) === 12);
        $company = Company::where('code', 'ACME')->firstOrFail();
        $this->actingAs($system, 'admin')->get('/manage/companies')
            ->assertOk()
            ->assertSee(route('login'))
            ->assertDontSee('/login/ACME');
        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'role' => UserRole::Employee->value,
            'login_id' => 'company001',
            'permission' => UserPermission::CompanyManager->value,
            'force_password_change' => true,
        ]);
    }

    public function test_employee_permission_only_accepts_one_or_nine(): void
    {
        $company = Company::create(['code' => 'PERM', 'name' => '権限会社', 'is_active' => true]);
        $companyAdmin = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Employee,
            'permission' => UserPermission::CompanyManager,
        ]);

        $this->actingAs($companyAdmin)->post('/company/employees', [
            'employee_number' => 'E005',
            'login_id' => 'employee005',
            'name' => '権限テスト',
            'email' => 'permission@example.test',
            'password' => 'Password1',
            'permission' => '5',
            'is_active' => '1',
        ])->assertSessionHasErrors('permission');

        $this->assertDatabaseMissing('users', ['email' => 'permission@example.test']);
    }

    public function test_employee_csv_imports_permission_nine_and_rejects_other_values(): void
    {
        $company = Company::create(['code' => 'CSV', 'name' => 'CSV会社', 'is_active' => true]);
        $companyManager = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Employee,
            'permission' => UserPermission::CompanyManager,
        ]);
        Department::create(['company_id' => $company->id, 'code' => 'SALES', 'name' => '営業部']);

        $validCsv = UploadedFile::fake()->createWithContent(
            'employees.csv',
            "employee_number,login_id,name,email,department_code,permission,password\nE009,manager009,社員管理者,manager009@example.test,SALES,9,Password1\n"
        );
        $this->actingAs($companyManager)->post('/company/employees/import', ['csv' => $validCsv])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'login_id' => 'manager009',
            'permission' => UserPermission::CompanyManager->value,
        ]);

        $invalidCsv = UploadedFile::fake()->createWithContent(
            'employees-invalid.csv',
            "employee_number,login_id,name,email,department_code,permission,password\nE005,invalid005,不正権限,invalid005@example.test,SALES,5,Password1\n"
        );
        $this->actingAs($companyManager)->post('/company/employees/import', ['csv' => $invalidCsv])
            ->assertSessionHasErrors('csv');
        $this->assertDatabaseMissing('users', ['email' => 'invalid005@example.test']);
    }

    public function test_company_report_wizard_preserves_the_selected_slot(): void
    {
        $company = Company::create(['code' => 'WIZARD', 'name' => '帳票会社', 'is_active' => true]);
        $companyAdmin = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Employee,
            'permission' => UserPermission::CompanyManager,
        ]);
        $template = PayslipTemplate::create([
            'name' => '標準帳票',
            'layout_type' => 'standard',
            'is_active' => true,
        ]);
        $templateItem = $template->items()->create([
            'code' => 'basic_salary',
            'label' => '基本給',
            'category' => PayslipItemCategory::Earning,
            'data_type' => 'amount',
            'sort_order' => 0,
            'is_required' => true,
            'slot_code' => 'left_01',
        ]);
        $mapping = [
            'payslip_template_id' => $template->id,
            'name' => '自社標準帳票',
            'layout_type' => 'standard',
            'items' => [[
                'source_template_item_id' => $templateItem->id,
                'code' => 'basic_salary',
                'label' => '基本給',
                'category' => PayslipItemCategory::Earning->value,
                'data_type' => 'amount',
                'slot_code' => 'right_02',
                'is_required' => '1',
                'is_active' => '1',
            ]],
        ];

        $this->actingAs($companyAdmin)->post('/company/settings/prepare', [
            'payslip_template_id' => $template->id,
            'name' => '自社標準帳票',
        ])->assertOk()->assertSee('STEP 2 / 3');
        $this->actingAs($companyAdmin)->post('/company/settings/confirm', $mapping)
            ->assertOk()->assertSee('STEP 3 / 3');
        $this->actingAs($companyAdmin)->post('/company/settings', $mapping)
            ->assertRedirect();

        $this->assertDatabaseHas('company_payslip_items', [
            'code' => 'basic_salary',
            'slot_code' => 'right_02',
            'is_active' => true,
        ]);
    }
}
