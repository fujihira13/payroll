<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('login_id', 255)->unique();
            $table->string('name');
            $table->string('password');
            $table->boolean('force_password_change')->default(true);
            $table->boolean('lock_status')->default(false);
            $table->unsignedInteger('try_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('login_slug', 60)->nullable()->unique()->after('code');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->string('name_kana')->nullable()->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('login_id', 255)->nullable()->after('employee_number');
            $table->unsignedTinyInteger('permission')->default(1)->after('role');
            $table->boolean('force_password_change')->default(false)->after('password');
            $table->boolean('lock_status')->default(false)->after('force_password_change');
            $table->unique(['company_id', 'login_id']);
        });

        Schema::table('payslip_templates', function (Blueprint $table) {
            $table->foreignId('created_by_admin_id')->nullable()->after('created_by')->constrained('admins')->nullOnDelete();
            $table->string('layout_type', 30)->default('standard')->after('description');
        });

        Schema::table('payslip_template_items', function (Blueprint $table) {
            $table->string('slot_code', 30)->nullable()->after('sort_order');
        });

        Schema::table('company_payslip_settings', function (Blueprint $table) {
            $table->string('layout_type', 30)->default('standard')->after('name');
        });

        Schema::table('company_payslip_items', function (Blueprint $table) {
            $table->string('slot_code', 30)->nullable()->after('sort_order');
        });

        Schema::table('email_templates', function (Blueprint $table) {
            // MariaDB may use the old composite unique index to support the company foreign key.
            // Give the foreign key its own index before replacing that unique constraint.
            $table->index('company_id', 'email_templates_company_id_index');
            $table->dropUnique(['company_id', 'type']);
            $table->string('name')->default('給与明細公開通知')->after('type');
            $table->string('sender_name')->nullable()->after('name');
            $table->string('sender_address')->nullable()->after('sender_name');
            $table->boolean('is_active')->default(true)->after('body');
            $table->unique(['company_id', 'name']);
        });

        DB::table('companies')->orderBy('id')->each(function ($company) {
            DB::table('companies')->where('id', $company->id)->update(['login_slug' => $company->code]);
        });

        DB::table('users')->where('role', 'company_admin')->update([
            'role' => 'employee',
            'permission' => 9,
        ]);

        DB::table('users')->whereNotNull('company_id')->orderBy('id')->each(function ($user) {
            $base = $user->employee_number ?: $user->email;
            $loginId = $base;
            $suffix = 1;
            while (DB::table('users')->where('company_id', $user->company_id)->where('login_id', $loginId)->exists()) {
                $loginId = $base.'_'.$suffix++;
            }
            DB::table('users')->where('id', $user->id)->update(['login_id' => $loginId]);
        });

        DB::table('users')->where('role', 'system_admin')->orderBy('id')->each(function ($user) {
            DB::table('admins')->insert([
                'login_id' => $user->email,
                'name' => $user->name,
                'password' => $user->password,
                'force_password_change' => false,
                'lock_status' => false,
                'try_count' => $user->login_failure_count,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        });

    }

    public function down(): void
    {
        DB::table('users')->where('permission', 9)->update(['role' => 'company_admin']);

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'name']);
            $table->dropColumn(['name', 'sender_name', 'sender_address', 'is_active']);
            $table->unique(['company_id', 'type']);
            $table->dropIndex('email_templates_company_id_index');
        });
        Schema::table('company_payslip_items', fn (Blueprint $table) => $table->dropColumn('slot_code'));
        Schema::table('company_payslip_settings', fn (Blueprint $table) => $table->dropColumn('layout_type'));
        Schema::table('payslip_template_items', fn (Blueprint $table) => $table->dropColumn('slot_code'));
        Schema::table('payslip_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_admin_id');
            $table->dropColumn('layout_type');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'login_id']);
            $table->dropColumn(['login_id', 'permission', 'force_password_change', 'lock_status']);
        });
        Schema::table('departments', fn (Blueprint $table) => $table->dropColumn('name_kana'));
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['login_slug']);
            $table->dropColumn('login_slug');
        });
        Schema::dropIfExists('admins');
    }
};
