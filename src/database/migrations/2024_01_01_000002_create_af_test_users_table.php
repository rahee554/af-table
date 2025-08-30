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
        Schema::create('af_test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->enum('role', ['admin', 'manager', 'employee', 'intern'])->default('employee');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->foreignId('department_id')->nullable()->constrained('af_test_departments')->onDelete('set null');
            $table->json('profile')->nullable(); // For JSON testing: {name, address: {street, city}, preferences: []}
            $table->decimal('salary', 10, 2)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            $table->boolean('is_remote')->default(false);
            $table->json('skills')->nullable(); // Array of skills
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance testing
            $table->index(['department_id', 'status']);
            $table->index(['role', 'created_at']);
            $table->index('email');
            $table->index('hire_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('af_test_users');
    }
};