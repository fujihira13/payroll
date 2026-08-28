<?php

namespace Tests\Feature;

use App\Enums\PayrollBatchStatus;
use App\Enums\PayslipItemCategory;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Mail\PayslipPublishedMail;
use App\Models\Company;
use App\Models\CompanyPayslipSetting;
use App\Models\PayrollBatch;
use App\Models\Payslip;
use App\Models\PayslipTemplate;
use App\Models\User;
use App\Services\PayrollCsvImporter;
use App\Services\PublishScheduledPayroll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayslipWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_csv_is_imported_using_company_item_codes(): void
    {
        [$company, $admin, $employee, $setting] = $this->context();
        $setting->items()->createMany([
            ['code' => 'basic_salary', 'label' => '基本給', 'category' => PayslipItemCategory::Earning, 'data_type' => 'amount', 'sort_order' => 1, 'is_required' => true, 'is_active' => true],
            ['code' => 'income_tax', 'label' => '所得税', 'category' => PayslipItemCategory::Deduction, 'data_type' => 'amount', 'sort_order' => 2, 'is_required' => true, 'is_active' => true],
        ]);
        $batch = $this->batch($company, $admin, $setting);
        $file = UploadedFile::fake()->createWithContent('payroll.csv', "employee_number,basic_salary,income_tax\nE001,300000,15000\n");

        $count = app(PayrollCsvImporter::class)->import($batch, $file);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('payslips', [
            'employee_id' => $employee->id,
            'gross_amount' => 300000,
            'deduction_amount' => 15000,
            'net_amount' => 285000,
        ]);
    }

    public function test_scheduled_batch_is_published_and_employee_is_notified(): void
    {
        Mail::fake();
        [$company, $admin, $employee, $setting] = $this->context();
        $batch = $this->batch($company, $admin, $setting, PayrollBatchStatus::Scheduled);
        $batch->update(['scheduled_for' => now()->subMinute(), 'approved_at' => now()->subMinute(), 'approved_by' => $admin->id]);
        Payslip::create(['payroll_batch_id' => $batch->id, 'employee_id' => $employee->id, 'details' => [], 'net_amount' => 200000]);

        $this->assertSame(1, app(PublishScheduledPayroll::class)->handle());

        $this->assertSame(PayrollBatchStatus::Published, $batch->fresh()->status);
        $this->assertNotNull($batch->payslips()->first()->notified_at);
        Mail::assertSent(PayslipPublishedMail::class, fn ($mail) => $mail->hasTo($employee->email)
            && str_contains($mail->messageBody, route('login')));
    }

    public function test_employee_can_only_view_published_own_payslip_and_view_is_recorded(): void
    {
        [$company, $admin, $employee, $setting] = $this->context();
        $batch = $this->batch($company, $admin, $setting, PayrollBatchStatus::Published);
        $batch->update(['published_at' => now()]);
        $payslip = Payslip::create([
            'payroll_batch_id' => $batch->id,
            'employee_id' => $employee->id,
            'details' => [['code' => 'basic', 'label' => '基本給', 'category' => 'earning', 'data_type' => 'amount', 'value' => 250000]],
            'gross_amount' => 250000,
            'net_amount' => 250000,
        ]);

        $this->actingAs($employee)->get(route('employee.payslips.show', $payslip))->assertOk()->assertSee('基本給');

        $payslip->refresh();
        $this->assertNotNull($payslip->first_viewed_at);
        $this->assertSame(1, $payslip->view_count);
    }

    public function test_employee_can_download_own_payslip_as_pdf(): void
    {
        [$company, $admin, $employee, $setting] = $this->context();
        $batch = $this->batch($company, $admin, $setting, PayrollBatchStatus::Published);
        $batch->update(['published_at' => now()]);
        $payslip = Payslip::create([
            'payroll_batch_id' => $batch->id,
            'employee_id' => $employee->id,
            'details' => [['code' => 'basic', 'label' => '基本給', 'category' => 'earning', 'data_type' => 'amount', 'value' => 250000]],
            'gross_amount' => 250000,
            'net_amount' => 250000,
        ]);

        $response = $this->actingAs($employee)->get(route('employee.payslips.pdf', $payslip));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    private function context(): array
    {
        $company = Company::create(['code' => 'ACME', 'name' => '株式会社Acme', 'is_active' => true]);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Employee, 'permission' => UserPermission::CompanyManager]);
        $employee = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Employee, 'employee_number' => 'E001']);
        $template = PayslipTemplate::create(['name' => '標準', 'is_active' => true]);
        $setting = CompanyPayslipSetting::create([
            'company_id' => $company->id,
            'payslip_template_id' => $template->id,
            'configured_by' => $admin->id,
            'name' => '自社標準',
            'is_active' => true,
        ]);

        return [$company, $admin, $employee, $setting];
    }

    private function batch(Company $company, User $admin, CompanyPayslipSetting $setting, PayrollBatchStatus $status = PayrollBatchStatus::Draft): PayrollBatch
    {
        return PayrollBatch::create([
            'company_id' => $company->id,
            'company_payslip_setting_id' => $setting->id,
            'created_by' => $admin->id,
            'name' => '2026年8月給与',
            'target_month' => '2026-08-01',
            'status' => $status,
        ]);
    }
}
