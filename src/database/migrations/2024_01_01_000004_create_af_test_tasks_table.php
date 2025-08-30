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
        Schema::create('af_test_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'review', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('project_id')->constrained('af_test_projects')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('af_test_users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('af_test_users')->onDelete('cascade');
            $table->integer('estimated_hours')->nullable();
            $table->integer('actual_hours')->default(0);
            $table->integer('progress_percentage')->default(0);
            $table->date('due_date')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->json('checklist')->nullable(); // Array of checklist items
            $table->json('attachments')->nullable(); // Array of file paths/URLs
            $table->json('comments')->nullable(); // Array of comment objects
            $table->decimal('cost', 10, 2)->default(0);
            $table->boolean('is_billable')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->string('reference_code', 50)->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance testing
            $table->index(['project_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['created_by', 'created_at']);
            $table->index(['status', 'priority']);
            $table->index('due_date');
            $table->index('reference_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('af_test_tasks');
    }
};