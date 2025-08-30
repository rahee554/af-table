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
        Schema::create('af_test_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->json('metadata')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->integer('employee_count')->default(0);
            $table->date('established_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance testing
            $table->index(['status', 'created_at']);
            $table->index('name');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('af_test_departments');
    }
};