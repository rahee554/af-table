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
        // Table 1: Users - Primary user accounts
        Schema::create('af_test_table1', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->index();
            $table->string('email', 150)->unique();
            $table->string('username', 50)->unique();
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->default('active');
            $table->enum('role', ['admin', 'manager', 'employee', 'client'])->default('employee');
            $table->json('profile')->nullable(); // {avatar, bio, social_links: {}}
            $table->json('preferences')->nullable(); // {theme, language, notifications: {}}
            $table->decimal('score', 5, 2)->default(0.00);
            $table->date('birth_date')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'role']);
            $table->index('last_login_at');
        });

        // Table 2: Companies - Organization records
        Schema::create('af_test_table2', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index();
            $table->string('slug', 160)->unique();
            $table->enum('type', ['startup', 'corporation', 'non-profit', 'government'])->default('corporation');
            $table->string('industry', 100)->index();
            $table->json('address')->nullable(); // {street, city, state, country, postal_code}
            $table->json('contact')->nullable(); // {phone, email, website, social: {}}
            $table->json('metadata')->nullable(); // {founded_year, employee_count, revenue}
            $table->integer('employee_count')->default(0);
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->index(['type', 'industry']);
        });

        // Table 3: Products - Catalog items
        Schema::create('af_test_table3', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index();
            $table->string('sku', 50)->unique();
            $table->foreignId('company_id')->constrained('af_test_table2')->onDelete('cascade');
            $table->string('category', 100)->index();
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->json('specifications')->nullable(); // {dimensions, weight, color, features: []}
            $table->json('pricing_history')->nullable(); // [{date, price, discount}]
            $table->json('tags')->nullable(); // Array of product tags
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('review_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('launched_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'category']);
            $table->index(['price', 'is_featured']);
            $table->index('stock_quantity');
        });

        // Table 4: Orders - Purchase transactions
        Schema::create('af_test_table4', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->constrained('af_test_table1')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('af_test_table2')->onDelete('cascade');
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('shipping_cost', 8, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);
            $table->json('shipping_address'); // {street, city, state, country, postal}
            $table->json('billing_address'); // {street, city, state, country, postal}
            $table->json('payment_details')->nullable(); // {method, card_last4, transaction_id}
            $table->json('tracking_info')->nullable(); // {carrier, tracking_number, events: []}
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['company_id', 'payment_status']);
            $table->index('order_number');
        });

        // Table 5: Order Items - Individual order line items
        Schema::create('af_test_table5', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('af_test_table4')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('af_test_table3')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->json('product_snapshot')->nullable(); // {name, sku, description}
            $table->json('customizations')->nullable(); // {size, color, engravings}
            $table->timestamps();
            $table->index(['order_id', 'product_id']);
        });

        // Table 6: Departments - Organizational units
        Schema::create('af_test_table6', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->index();
            $table->string('code', 20)->unique();
            $table->foreignId('company_id')->constrained('af_test_table2')->onDelete('cascade');
            $table->foreignId('manager_id')->nullable()->constrained('af_test_table1')->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('af_test_table6')->onDelete('set null');
            $table->text('description')->nullable();
            $table->json('budget_info')->nullable(); // {annual_budget, spent, remaining}
            $table->json('metrics')->nullable(); // {headcount, performance_score}
            $table->integer('employee_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'is_active']);
            $table->index('manager_id');
        });

        // Table 7: Projects - Work initiatives
        Schema::create('af_test_table7', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index();
            $table->string('code', 30)->unique();
            $table->foreignId('company_id')->constrained('af_test_table2')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('af_test_table6')->onDelete('set null');
            $table->foreignId('manager_id')->constrained('af_test_table1')->onDelete('cascade');
            $table->enum('status', ['planning', 'active', 'on-hold', 'completed', 'cancelled'])->default('planning');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('description')->nullable();
            $table->json('requirements')->nullable(); // {functional: [], technical: [], business: []}
            $table->json('timeline')->nullable(); // {phases: [{name, start_date, end_date}]}
            $table->json('budget')->nullable(); // {allocated, spent, remaining}
            $table->decimal('budget_amount', 12, 2)->nullable();
            $table->integer('completion_percentage')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['manager_id', 'priority']);
            $table->index('completion_percentage');
        });

        // Table 8: Tasks - Individual work items
        Schema::create('af_test_table8', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200)->index();
            $table->foreignId('project_id')->constrained('af_test_table7')->onDelete('cascade');
            $table->foreignId('assignee_id')->nullable()->constrained('af_test_table1')->onDelete('set null');
            $table->foreignId('reporter_id')->constrained('af_test_table1')->onDelete('cascade');
            $table->enum('status', ['todo', 'in-progress', 'review', 'testing', 'done', 'blocked'])->default('todo');
            $table->enum('priority', ['lowest', 'low', 'medium', 'high', 'highest'])->default('medium');
            $table->enum('type', ['feature', 'bug', 'improvement', 'task', 'epic'])->default('task');
            $table->text('description')->nullable();
            $table->json('acceptance_criteria')->nullable(); // Array of criteria
            $table->json('labels')->nullable(); // Array of labels/tags
            $table->json('time_tracking')->nullable(); // {estimated_hours, logged_hours, remaining_hours}
            $table->integer('story_points')->nullable();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->decimal('logged_hours', 6, 2)->default(0.00);
            $table->date('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'status']);
            $table->index(['assignee_id', 'priority']);
            $table->index('due_date');
        });

        // Table 9: Comments - Communication records
        Schema::create('af_test_table9', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable'); // Polymorphic relationship
            $table->foreignId('user_id')->constrained('af_test_table1')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('af_test_table9')->onDelete('cascade');
            $table->text('content');
            $table->json('mentions')->nullable(); // Array of mentioned user IDs
            $table->json('attachments')->nullable(); // [{name, url, type, size}]
            $table->json('metadata')->nullable(); // {edited_at, edit_count, reactions: {}}
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
            $table->index('user_id');
            $table->index('parent_id');
        });

        // Table 10: Files - Document management
        Schema::create('af_test_table10', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255); // Storage filename
            $table->string('original_name', 255); // User-uploaded filename
            $table->morphs('fileable'); // Polymorphic relationship
            $table->foreignId('uploaded_by')->constrained('af_test_table1')->onDelete('cascade');
            $table->string('mime_type', 100);
            $table->string('extension', 10);
            $table->bigInteger('size_bytes');
            $table->string('path', 500);
            $table->string('disk', 50)->default('local');
            $table->json('metadata')->nullable(); // {dimensions, duration, pages, etc.}
            $table->json('versions')->nullable(); // Version history
            $table->string('hash', 64)->nullable(); // File integrity check
            $table->boolean('is_public')->default(false);
            $table->integer('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('uploaded_by');
            $table->index(['mime_type', 'extension']);
        });

        // Table 11: Events - Calendar and scheduling
        Schema::create('af_test_table11', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200)->index();
            $table->text('description')->nullable();
            $table->foreignId('organizer_id')->constrained('af_test_table1')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('af_test_table7')->onDelete('set null');
            $table->enum('type', ['meeting', 'deadline', 'milestone', 'reminder', 'holiday'])->default('meeting');
            $table->enum('status', ['scheduled', 'in-progress', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamp('start_datetime')->nullable();
            $table->timestamp('end_datetime')->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->json('attendees')->nullable(); // Array of user IDs
            $table->json('recurrence')->nullable(); // {pattern, frequency, end_date}
            $table->json('location')->nullable(); // {type, address, virtual_link}
            $table->json('reminders')->nullable(); // [{time_before, method}]
            $table->boolean('is_all_day')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();
            $table->index(['organizer_id', 'start_datetime']);
            $table->index(['type', 'status']);
        });

        // Table 12: Notifications - User alerts
        Schema::create('af_test_table12', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('af_test_table1')->onDelete('cascade');
            $table->morphs('notifiable'); // Polymorphic relationship
            $table->string('type', 100)->index();
            $table->string('title', 200);
            $table->text('message');
            $table->json('data')->nullable(); // {action_url, action_text}
            $table->json('channels')->nullable(); // ['email', 'sms', 'push', 'in-app']
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_read']);
            $table->index(['type', 'priority']);
        });

        // Table 13: Analytics - Metrics and tracking
        Schema::create('af_test_table13', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name', 100)->index();
            $table->morphs('trackable'); // Polymorphic relationship
            $table->foreignId('recorded_by')->nullable()->constrained('af_test_table1')->onDelete('set null');
            $table->enum('metric_type', ['counter', 'gauge', 'timer', 'histogram'])->default('counter');
            $table->enum('period', ['hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('daily');
            $table->decimal('value', 15, 4);
            $table->json('dimensions')->nullable(); // {category, subcategory, tags: []}
            $table->json('metadata')->nullable(); // {source, confidence}
            $table->date('period_date');
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            $table->index(['metric_name', 'period_date']);
            $table->index(['metric_type', 'period']);
        });

        // Table 14: Settings - Configuration storage
        Schema::create('af_test_table14', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->index();
            $table->morphs('configurable'); // Polymorphic relationship
            $table->enum('type', ['string', 'integer', 'float', 'boolean', 'json', 'array'])->default('string');
            $table->text('value');
            $table->json('json_value')->nullable(); // For complex data structures
            $table->text('description')->nullable();
            $table->json('validation_rules')->nullable(); // Laravel validation rules
            $table->json('options')->nullable(); // Available options for enum-like settings
            $table->boolean('is_public')->default(false);
            $table->boolean('is_required')->default(false);
            $table->string('group', 50)->default('general');
            $table->integer('sort_order')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('af_test_table1')->onDelete('set null');
            $table->timestamps();
            $table->index(['key', 'group']);
            $table->index('type');
        });

        // Table 15: Logs - Activity tracking
        Schema::create('af_test_table15', function (Blueprint $table) {
            $table->id();
            $table->string('action', 50)->index();
            $table->morphs('loggable'); // Polymorphic relationship
            $table->foreignId('user_id')->nullable()->constrained('af_test_table1')->onDelete('set null');
            $table->enum('level', ['debug', 'info', 'warning', 'error', 'critical'])->default('info');
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable(); // {old: {}, new: {}}
            $table->json('context')->nullable(); // {request_id, session_id, route}
            $table->string('session_id', 100)->nullable();
            $table->string('request_id', 100)->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            $table->index(['action', 'level']);
            $table->index(['user_id', 'occurred_at']);
        });

        // Table 16: Tags - Labeling system
        Schema::create('af_test_table16', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 110)->unique();
            $table->string('color', 7)->default('#007bff'); // Hex color
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // {icon, category, usage_count}
            $table->integer('usage_count')->default(0);
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->constrained('af_test_table1')->onDelete('cascade');
            $table->timestamps();
            $table->index(['name', 'is_system']);
        });

        // Table 17: Taggables - Many-to-many polymorphic pivot
        Schema::create('af_test_table17', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('af_test_table16')->onDelete('cascade');
            $table->morphs('taggable'); // Polymorphic relationship
            $table->foreignId('tagged_by')->constrained('af_test_table1')->onDelete('cascade');
            $table->json('metadata')->nullable(); // {confidence, auto_tagged}
            $table->timestamps();
            $table->index(['tag_id', 'tagged_by']);
        });

        // Table 18: Permissions - Access control
        Schema::create('af_test_table18', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('guard_name', 50)->default('web');
            $table->string('resource', 50)->index();
            $table->string('action', 50)->index();
            $table->text('description')->nullable();
            $table->json('conditions')->nullable(); // {field_conditions: {}, role_conditions: []}
            $table->json('metadata')->nullable(); // {category, risk_level}
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->index(['resource', 'action']);
        });

        // Table 19: User Permissions - Permission assignments
        Schema::create('af_test_table19', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('af_test_table1')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('af_test_table18')->onDelete('cascade');
            $table->morphs('permissionable'); // Polymorphic relationship
            $table->json('conditions')->nullable(); // {time_based: {}, location_based: []}
            $table->json('restrictions')->nullable(); // {max_records, allowed_actions: []}
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('granted_by')->constrained('af_test_table1')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'is_active']);
            $table->index(['permission_id', 'granted_at']);
        });

        // Table 20: Reports - Business intelligence
        Schema::create('af_test_table20', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->index();
            $table->string('slug', 160)->unique();
            $table->foreignId('company_id')->constrained('af_test_table2')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('af_test_table1')->onDelete('cascade');
            $table->enum('type', ['dashboard', 'chart', 'table', 'pivot', 'export'])->default('table');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->text('description')->nullable();
            $table->json('configuration'); // {filters: {}, grouping: [], aggregations: []}
            $table->json('data_sources'); // {tables: [], joins: [], filters: []}
            $table->json('visualizations')->nullable(); // {charts: [], tables: []}
            $table->json('parameters')->nullable(); // {user_selectable: {}}
            $table->json('schedule')->nullable(); // {frequency, time, recipients: []}
            $table->json('recipients')->nullable(); // Array of user IDs
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->decimal('avg_execution_time', 8, 3)->default(0.000); // Seconds
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['type', 'is_public']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('af_test_table20');
        Schema::dropIfExists('af_test_table19');
        Schema::dropIfExists('af_test_table18');
        Schema::dropIfExists('af_test_table17');
        Schema::dropIfExists('af_test_table16');
        Schema::dropIfExists('af_test_table15');
        Schema::dropIfExists('af_test_table14');
        Schema::dropIfExists('af_test_table13');
        Schema::dropIfExists('af_test_table12');
        Schema::dropIfExists('af_test_table11');
        Schema::dropIfExists('af_test_table10');
        Schema::dropIfExists('af_test_table9');
        Schema::dropIfExists('af_test_table8');
        Schema::dropIfExists('af_test_table7');
        Schema::dropIfExists('af_test_table6');
        Schema::dropIfExists('af_test_table5');
        Schema::dropIfExists('af_test_table4');
        Schema::dropIfExists('af_test_table3');
        Schema::dropIfExists('af_test_table2');
        Schema::dropIfExists('af_test_table1');
    }
};