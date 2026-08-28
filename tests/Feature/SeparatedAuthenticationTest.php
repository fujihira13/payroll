<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Admin;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeparatedAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_logs_in_from_manage_only(): void
    {
        $admin = Admin::create([
            'login_id' => 'admin001',
            'name' => '管理者',
            'password' => 'Password123',
            'force_password_change' => false,
            'is_active' => true,
        ]);

        $this->post('/manage', ['login_id' => 'admin001', 'password' => 'Password123'])
            ->assertRedirect(route('manage.companies.index'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_company_user_logs_in_with_company_code_and_login_id(): void
    {
        $company = Company::create(['code' => 'ACME', 'login_slug' => 'acme', 'name' => 'Acme', 'is_active' => true]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Employee,
            'login_id' => 'staff001',
            'password' => 'Password123',
        ]);

        $this->post('/login', ['company_code' => 'acme', 'login_id' => 'staff001', 'password' => 'Password123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_company_user_is_locked_after_five_failures(): void
    {
        $company = Company::create(['code' => 'LOCK', 'login_slug' => 'lock', 'name' => 'Lock', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id, 'login_id' => 'staff002', 'password' => 'Password123']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['company_code' => 'lock', 'login_id' => 'staff002', 'password' => 'wrong-password']);
        }

        $this->assertTrue($user->fresh()->lock_status);
        $this->assertSame(5, $user->fresh()->login_failure_count);
    }
}
