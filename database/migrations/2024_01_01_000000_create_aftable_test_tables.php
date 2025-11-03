<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for AF Table testing environment
     * Creates comprehensive test tables with complex relationships
     */
    public function up(): void
    {
        // 1. Test Companies Table (Parent)
        Schema::create('test_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->integer('employee_count')->default(0);
            $table->decimal('revenue', 15, 2)->nullable();
            $table->date('founded_date')->nullable();
            $table->json('metadata')->nullable(); // For JSON column testing
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
            $table->index('country');
            $table->index(['status', 'is_verified']);
        });

        // 2. Test Departments Table (Parent for Employees)
        Schema::create('test_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('test_companies')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->integer('budget')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable(); // Will add FK later
            $table->json('settings')->nullable(); // JSON column
            $table->timestamps();
            
            $table->index('company_id');
            $table->index('code');
            $table->index('manager_id');
        });

        // 3. Test Employees Table (Many-to-One with Company & Department)
        Schema::create('test_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('test_companies')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('test_departments')->onDelete('set null');
            $table->string('employee_code', 50)->nullable()->unique(); // Employee code (EMP00001, etc.)
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('hire_date')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->enum('employment_type', ['full-time', 'part-time', 'contract', 'intern'])->default('full-time');
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active'); // Employee status
            $table->json('skills')->nullable(); // Array of skills
            $table->json('preferences')->nullable(); // User preferences
            $table->string('position')->nullable();
            $table->integer('years_experience')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('company_id');
            $table->index('department_id');
            $table->index('email');
            $table->index('employee_code'); // Index for employee_code
            $table->index('status'); // Index for status
            $table->index(['company_id', 'department_id']);
            $table->index(['employment_type', 'is_active']);
        });

        // Add foreign key for department manager after employees table exists
        Schema::table('test_departments', function (Blueprint $table) {
            $table->foreign('manager_id')
                ->references('id')
                ->on('test_employees')
                ->onDelete('set null');
        });

        // 4. Test Projects Table (Many-to-Many with Employees)
        Schema::create('test_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('test_companies')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('spent', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['planning', 'in-progress', 'on-hold', 'completed', 'cancelled'])->default('planning');
            $table->integer('progress')->default(0); // 0-100
            $table->json('milestones')->nullable();
            $table->timestamps();
            
            $table->index('company_id');
            $table->index('status');
            $table->index('priority');
            $table->index(['status', 'priority']);
        });

        // 5. Test Tasks Table (Belongs to Project and Employee)
        Schema::create('test_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('test_projects')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('test_employees')->onDelete('set null');
            $table->foreignId('created_by')->constrained('test_employees')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['todo', 'in-progress', 'review', 'done', 'cancelled'])->default('todo');
            $table->integer('estimated_hours')->nullable();
            $table->integer('actual_hours')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            
            $table->index('project_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index(['project_id', 'status']);
        });

        // 6. Pivot Table: Employee-Project (Many-to-Many)
        Schema::create('test_employee_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('test_employees')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('test_projects')->onDelete('cascade');
            $table->string('role')->nullable(); // Project role
            $table->decimal('hours_allocated', 8, 2)->default(0);
            $table->date('joined_at')->nullable();
            $table->timestamps();
            
            $table->unique(['employee_id', 'project_id']);
            $table->index('employee_id');
            $table->index('project_id');
        });

        // 7. Test Clients Table (Belongs to Company)
        Schema::create('test_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('test_companies')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('industry')->nullable();
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->decimal('lifetime_value', 15, 2)->default(0);
            $table->date('first_contract_date')->nullable();
            $table->json('contacts')->nullable(); // Array of contact persons
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('company_id');
            $table->index('tier');
            $table->index('email');
        });

        // 8. Test Invoices Table (Belongs to Client and Project)
        Schema::create('test_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('test_clients')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('test_projects')->onDelete('set null');
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->json('line_items')->nullable(); // Invoice items as JSON
            $table->timestamps();
            
            $table->index('client_id');
            $table->index('project_id');
            $table->index('status');
            $table->index('invoice_date');
        });

        // 9. Test Timesheets Table (Tracks employee time on tasks)
        Schema::create('test_timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('test_employees')->onDelete('cascade');
            $table->foreignId('task_id')->nullable()->constrained('test_tasks')->onDelete('cascade');
            $table->date('work_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours', 5, 2);
            $table->text('description')->nullable();
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            
            $table->index('employee_id');
            $table->index('task_id');
            $table->index('work_date');
            $table->index(['employee_id', 'work_date']);
        });

        // 10. Test Documents Table (Polymorphic - belongs to various models)
        Schema::create('test_documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // documentable_id, documentable_type (creates index automatically)
            $table->string('title');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable(); // in bytes
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('uploaded_by')->constrained('test_employees')->onDelete('cascade');
            $table->timestamps();
            
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_documents');
        Schema::dropIfExists('test_timesheets');
        Schema::dropIfExists('test_invoices');
        Schema::dropIfExists('test_clients');
        Schema::dropIfExists('test_employee_project');
        Schema::dropIfExists('test_tasks');
        Schema::dropIfExists('test_projects');
        Schema::dropIfExists('test_employees');
        Schema::dropIfExists('test_departments');
        Schema::dropIfExists('test_companies');
    }
};
