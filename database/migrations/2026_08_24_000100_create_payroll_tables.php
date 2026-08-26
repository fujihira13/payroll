<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payslip_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_template_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('label');
            $table->string('category', 20);
            $table->string('data_type', 20)->default('amount');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
            $table->unique(['payslip_template_id', 'code']);
        });

        Schema::create('company_payslip_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payslip_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_payslip_setting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_template_item_id')->nullable()->constrained('payslip_template_items')->nullOnDelete();
            $table->string('code', 60);
            $table->string('label');
            $table->string('category', 20);
            $table->string('data_type', 20)->default('amount');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_payslip_setting_id', 'code']);
        });

        Schema::create('payroll_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_payslip_setting_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->date('target_month');
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->restrictOnDelete();
            $table->json('details');
            $table->decimal('gross_amount', 14, 2)->default(0);
            $table->decimal('deduction_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['payroll_batch_id', 'employee_id']);
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->default('payslip_published');
            $table->string('subject');
            $table->text('body');
            $table->timestamps();
            $table->unique(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_batches');
        Schema::dropIfExists('company_payslip_items');
        Schema::dropIfExists('company_payslip_settings');
        Schema::dropIfExists('payslip_template_items');
        Schema::dropIfExists('payslip_templates');
    }
};
