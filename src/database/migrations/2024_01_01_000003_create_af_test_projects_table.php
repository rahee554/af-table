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
        Schema::create('af_test_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('code', 30)->unique();
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->foreignId('department_id')->constrained('af_test_departments')->onDelete('cascade');
            $table->foreignId('manager_id')->nullable()->constrained('af_test_users')->onDelete('set null');
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->integer('progress_percentage')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('deadline')->nullable();
            $table->json('requirements')->nullable(); // Array of requirements
            $table->json('technologies')->nullable(); // Array of technologies used
            $table->json('deliverables')->nullable(); // Object with deliverable details
            $table->string('client_name', 100)->nullable();
            $table->boolean('is_confidential')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance testing
            $table->index(['department_id', 'status']);
            $table->index(['manager_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('deadline');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('af_test_projects');
    }
};