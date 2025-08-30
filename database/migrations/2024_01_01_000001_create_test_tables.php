<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create af_test_departments table
        Schema::create('af_test_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->string('head_of_department', 100)->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->integer('employee_count')->default(0);
            $table->string('location', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->enum('status', ['active', 'inactive', 'restructuring'])->default('active');
            $table->json('goals')->nullable();
            $table->json('resources')->nullable();
            $table->json('policies')->nullable();
            $table->date('established_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'created_at']);
            $table->index(['location', 'status']);
            $table->index('code');
            $table->index('budget');
        });

        // Create af_test_users table
        Schema::create('af_test_users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('email', 100)->unique();
            $table->string('username', 50)->unique()->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave', 'terminated'])->default('active');
            $table->foreignId('department_id')->nullable()->constrained('af_test_departments')->onDelete('set null');
            $table->string('position', 100)->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact', 100)->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->boolean('is_manager')->default(false);
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();
            $table->json('preferences')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'department_id']);
            $table->index(['hire_date', 'status']);
            $table->index('email');
            $table->index('salary');
            $table->index('is_manager');
        });

        // Create af_test_projects table
        Schema::create('af_test_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('code', 20)->unique();
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->foreignId('department_id')->nullable()->constrained('af_test_departments')->onDelete('set null');
            $table->foreignId('manager_id')->nullable()->constrained('af_test_users')->onDelete('set null');
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->integer('progress_percentage')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('deadline')->nullable();
            $table->json('requirements')->nullable();
            $table->json('technologies')->nullable();
            $table->json('deliverables')->nullable();
            $table->string('client_name', 100)->nullable();
            $table->boolean('is_confidential')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'priority']);
            $table->index(['department_id', 'status']);
            $table->index(['manager_id', 'status']);
            $table->index(['deadline', 'status']);
            $table->index('code');
            $table->index('budget');
            $table->index('is_confidential');
        });

        // Create af_test_tasks table
        Schema::create('af_test_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'review', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->foreignId('project_id')->nullable()->constrained('af_test_projects')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('af_test_users')->onDelete('set null');
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->integer('completion_percentage')->default(0);
            $table->date('due_date')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('attachments')->nullable();
            $table->json('comments')->nullable();
            $table->json('tags')->nullable();
            $table->enum('difficulty_level', ['easy', 'medium', 'hard', 'expert'])->default('medium');
            $table->string('category', 50)->nullable();
            $table->boolean('is_billable')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'priority']);
            $table->index(['project_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['due_date', 'status']);
            $table->index(['category', 'status']);
            $table->index('is_billable');
            $table->index('difficulty_level');
        });

        // Legacy tables for backward compatibility
        // Create test_categories table
        Schema::create('test_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['is_active', 'created_at']);
        });

        // Create test_users table
        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->date('birth_date')->nullable();
            $table->json('profile')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('email');
        });

        // Create test_posts table
        Schema::create('test_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->text('excerpt')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->foreignId('test_user_id')->constrained('test_users')->onDelete('cascade');
            $table->foreignId('test_category_id')->constrained('test_categories')->onDelete('cascade');
            $table->integer('views_count')->default(0);
            $table->json('meta_data')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'published_at']);
            $table->index(['test_user_id', 'status']);
            $table->index(['test_category_id', 'status']);
        });

        // Create test_comments table
        Schema::create('test_comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('test_post_id')->constrained('test_posts')->onDelete('cascade');
            $table->foreignId('test_user_id')->constrained('test_users')->onDelete('cascade');
            $table->boolean('is_approved')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['test_post_id', 'is_approved']);
            $table->index('test_user_id');
        });

        // Create test_tags table
        Schema::create('test_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#000000');
            $table->timestamps();
        });

        // Create test_post_tags pivot table
        Schema::create('test_post_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_post_id')->constrained('test_posts')->onDelete('cascade');
            $table->foreignId('test_tag_id')->constrained('test_tags')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['test_post_id', 'test_tag_id']);
        });

        // Create test_profiles table (one-to-one relationship)
        Schema::create('test_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_user_id')->constrained('test_users')->onDelete('cascade');
            $table->string('bio')->nullable();
            $table->string('website')->nullable();
            $table->string('avatar')->nullable();
            $table->json('social_links')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->unique('test_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_post_tags');
        Schema::dropIfExists('test_profiles');
        Schema::dropIfExists('test_tags');
        Schema::dropIfExists('test_comments');
        Schema::dropIfExists('test_posts');
        Schema::dropIfExists('test_users');
        Schema::dropIfExists('test_categories');
        Schema::dropIfExists('af_test_tasks');
        Schema::dropIfExists('af_test_projects');
        Schema::dropIfExists('af_test_users');
        Schema::dropIfExists('af_test_departments');
    }
};
