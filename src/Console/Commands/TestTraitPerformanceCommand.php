<?php

namespace ArtflowStudio\Table\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestTraitPerformanceCommand extends Command
{
    protected $signature = 'af-table:test-trait-performance 
                           {--use-existing : Use existing real tables instead of creating dummy ones}
                           {--detailed : Show detailed output for each test}
                           {--benchmark : Run comprehensive benchmarking}
                           {--records=1000 : Number of records to test with}';

    protected $description = 'Test AF Table trait performance with real-world scenarios using existing tables';

    protected $tables = [
        'af_test_departments',
        'af_test_users', 
        'af_test_projects',
        'af_test_tasks',
        'test_categories',
        'test_users',
        'test_posts',
        'test_comments'
    ];

    public function handle()
    {
        $this->info('🚀 Starting AF Table Trait Performance Testing');
        $this->newLine();

        if ($this->option('use-existing')) {
            $this->info('Using existing real tables for testing...');
            $this->testExistingTables();
        } else {
            $this->info('Creating test data for performance testing...');
            $this->createTestData();
        }

        $this->newLine();
        $this->info('✅ AF Table trait performance testing completed!');
    }

    protected function testExistingTables()
    {
        $this->info('📊 Testing Real AF Table Performance');
        $this->line('=====================================');

        // Phase 1: Basic Table Operations
        $this->runTestPhase('Basic Table Operations', function() {
            $this->testBasicOperations();
        });

        // Phase 2: Search Capabilities
        $this->runTestPhase('Search Capabilities', function() {
            $this->testSearchCapabilities();
        });

        // Phase 3: Advanced Filtering
        $this->runTestPhase('Advanced Filtering', function() {
            $this->testAdvancedFiltering();
        });

        // Phase 4: Sorting Operations
        $this->runTestPhase('Sorting Operations', function() {
            $this->testSortingOperations();
        });

        // Phase 5: Relationship Testing
        $this->runTestPhase('Relationship Testing', function() {
            $this->testRelationships();
        });

        // Phase 6: Nested Relationships
        $this->runTestPhase('Nested Relationships', function() {
            $this->testNestedRelationships();
        });

        // Phase 7: JSON Operations
        $this->runTestPhase('JSON Operations', function() {
            $this->testJsonOperations();
        });

        // Phase 8: Bulk Operations
        $this->runTestPhase('Bulk Operations', function() {
            $this->testBulkOperations();
        });

        // Phase 9: Memory & Performance
        $this->runTestPhase('Memory & Performance', function() {
            $this->testMemoryPerformance();
        });
    }

    protected function runTestPhase($name, $callback)
    {
        $this->newLine();
        $this->info("📋 Phase: {$name}");
        $this->line(str_repeat('-', 50));
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        try {
            $callback();
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            $duration = round(($endTime - $startTime) * 1000, 2);
            $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2);
            
            $this->info("✅ {$name} completed in {$duration}ms (Memory: {$memoryUsed}MB)");
        } catch (\Exception $e) {
            $this->error("❌ {$name} failed: " . $e->getMessage());
            if ($this->option('detailed')) {
                $this->error($e->getTraceAsString());
            }
        }
    }

    protected function testBasicOperations()
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("⚠️  Table {$table} does not exist, skipping...");
                continue;
            }

            $count = DB::table($table)->count();
            $this->line("📊 {$table}: {$count} records");

            if ($this->option('detailed')) {
                $columns = Schema::getColumnListing($table);
                $this->line("   Columns: " . implode(', ', $columns));
            }
        }
    }

    protected function testSearchCapabilities()
    {
        // Test search on af_test_users
        if (Schema::hasTable('af_test_users')) {
            $this->line("🔍 Testing search on af_test_users");
            
            // Basic search
            $results = DB::table('af_test_users')
                ->where('first_name', 'like', '%John%')
                ->count();
            $this->line("   First name search (John): {$results} results");

            // Email search
            $results = DB::table('af_test_users')
                ->where('email', 'like', '%@example.com%')
                ->count();
            $this->line("   Email search (@example.com): {$results} results");

            // Multi-column search
            $results = DB::table('af_test_users')
                ->where(function($query) {
                    $query->where('first_name', 'like', '%test%')
                          ->orWhere('last_name', 'like', '%test%')
                          ->orWhere('email', 'like', '%test%');
                })
                ->count();
            $this->line("   Multi-column search (test): {$results} results");
        }

        // Test search on af_test_projects
        if (Schema::hasTable('af_test_projects')) {
            $this->line("🔍 Testing search on af_test_projects");
            
            $results = DB::table('af_test_projects')
                ->where('name', 'like', '%Project%')
                ->count();
            $this->line("   Project name search: {$results} results");

            $results = DB::table('af_test_projects')
                ->where('description', 'like', '%Laravel%')
                ->count();
            $this->line("   Description search (Laravel): {$results} results");
        }

        // Test search on test_posts
        if (Schema::hasTable('test_posts')) {
            $this->line("🔍 Testing search on test_posts");
            
            $results = DB::table('test_posts')
                ->where('title', 'like', '%Post%')
                ->count();
            $this->line("   Post title search: {$results} results");

            $results = DB::table('test_posts')
                ->where('content', 'like', '%content%')
                ->count();
            $this->line("   Content search: {$results} results");
        }
    }

    protected function testAdvancedFiltering()
    {
        // Status filtering on projects
        if (Schema::hasTable('af_test_projects')) {
            $this->line("🎯 Testing advanced filtering on af_test_projects");
            
            $active = DB::table('af_test_projects')
                ->where('status', 'active')
                ->count();
            $this->line("   Active projects: {$active}");

            $completed = DB::table('af_test_projects')
                ->where('status', 'completed')
                ->count();
            $this->line("   Completed projects: {$completed}");

            // Date range filtering
            $recent = DB::table('af_test_projects')
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            $this->line("   Recent projects (30 days): {$recent}");
        }

        // Status filtering on users  
        if (Schema::hasTable('af_test_users')) {
            $this->line("🎯 Testing status filtering on af_test_users");
            
            $active = DB::table('af_test_users')
                ->where('status', 'active')
                ->count();
            $this->line("   Active users: {$active}");

            $inactive = DB::table('af_test_users')
                ->where('status', 'inactive')
                ->count();
            $this->line("   Inactive users: {$inactive}");

            // Department filtering
            $withDept = DB::table('af_test_users')
                ->whereNotNull('department_id')
                ->count();
            $this->line("   Users with department: {$withDept}");
        }

        // Priority filtering on tasks
        if (Schema::hasTable('af_test_tasks')) {
            $this->line("🎯 Testing priority filtering on af_test_tasks");
            
            $high = DB::table('af_test_tasks')
                ->where('priority', 'high')
                ->count();
            $this->line("   High priority tasks: {$high}");

            $medium = DB::table('af_test_tasks')
                ->where('priority', 'medium')
                ->count();
            $this->line("   Medium priority tasks: {$medium}");

            $low = DB::table('af_test_tasks')
                ->where('priority', 'low')
                ->count();
            $this->line("   Low priority tasks: {$low}");
        }
    }

    protected function testSortingOperations()
    {
        // Test sorting on different columns
        if (Schema::hasTable('af_test_users')) {
            $this->line("📊 Testing sorting on af_test_users");
            
            // Name sorting
            $users = DB::table('af_test_users')
                ->orderBy('first_name', 'asc')
                ->limit(5)
                ->pluck('first_name');
            $this->line("   Top 5 users (first_name ASC): " . $users->implode(', '));

            // Date sorting
            $recent = DB::table('af_test_users')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->pluck('first_name');
            $this->line("   Most recent users: " . $recent->implode(', '));
        }

        if (Schema::hasTable('af_test_projects')) {
            $this->line("📊 Testing sorting on af_test_projects");
            
            // Priority + date sorting
            $projects = DB::table('af_test_projects')
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->pluck('name');
            $this->line("   Top projects (priority + date): " . $projects->implode(', '));
        }

        if (Schema::hasTable('test_posts')) {
            $this->line("📊 Testing sorting on test_posts");
            
            // Published date sorting
            $posts = DB::table('test_posts')
                ->orderBy('published_at', 'desc')
                ->limit(3)
                ->pluck('title');
            $this->line("   Latest posts: " . $posts->implode(', '));
        }
    }

    protected function testRelationships()
    {
        $this->line("🔗 Testing single-level relationships");

        // Department -> Users relationship
        if (Schema::hasTable('af_test_departments') && Schema::hasTable('af_test_users')) {
            $deptData = DB::table('af_test_departments')
                ->leftJoin('af_test_users', 'af_test_departments.id', '=', 'af_test_users.department_id')
                ->select('af_test_departments.name as dept_name', DB::raw('COUNT(af_test_users.id) as user_count'))
                ->groupBy('af_test_departments.id', 'af_test_departments.name')
                ->get();

            $this->line("   Department -> Users:");
            foreach ($deptData as $dept) {
                $this->line("     {$dept->dept_name}: {$dept->user_count} users");
            }
        }

        // User -> Projects relationship
        if (Schema::hasTable('af_test_users') && Schema::hasTable('af_test_projects')) {
            $userProjects = DB::table('af_test_users')
                ->leftJoin('af_test_projects', 'af_test_users.id', '=', 'af_test_projects.manager_id')
                ->select(
                    DB::raw('CONCAT(af_test_users.first_name, " ", af_test_users.last_name) as user_name'), 
                    DB::raw('COUNT(af_test_projects.id) as project_count')
                )
                ->groupBy('af_test_users.id', 'af_test_users.first_name', 'af_test_users.last_name')
                ->having('project_count', '>', 0)
                ->limit(5)
                ->get();

            $this->line("   User -> Projects:");
            foreach ($userProjects as $user) {
                $this->line("     {$user->user_name}: {$user->project_count} projects");
            }
        }

        // Project -> Tasks relationship  
        if (Schema::hasTable('af_test_projects') && Schema::hasTable('af_test_tasks')) {
            $projectTasks = DB::table('af_test_projects')
                ->leftJoin('af_test_tasks', 'af_test_projects.id', '=', 'af_test_tasks.project_id')
                ->select('af_test_projects.name as project_name', DB::raw('COUNT(af_test_tasks.id) as task_count'))
                ->groupBy('af_test_projects.id', 'af_test_projects.name')
                ->having('task_count', '>', 0)
                ->limit(5)
                ->get();

            $this->line("   Project -> Tasks:");
            foreach ($projectTasks as $project) {
                $this->line("     {$project->project_name}: {$project->task_count} tasks");
            }
        }

        // Category -> Posts relationship
        if (Schema::hasTable('test_categories') && Schema::hasTable('test_posts')) {
            $categoryPosts = DB::table('test_categories')
                ->leftJoin('test_posts', 'test_categories.id', '=', 'test_posts.category_id')
                ->select('test_categories.name as category_name', DB::raw('COUNT(test_posts.id) as post_count'))
                ->groupBy('test_categories.id', 'test_categories.name')
                ->get();

            $this->line("   Category -> Posts:");
            foreach ($categoryPosts as $category) {
                $this->line("     {$category->category_name}: {$category->post_count} posts");
            }
        }

        // Post -> Comments relationship
        if (Schema::hasTable('test_posts') && Schema::hasTable('test_comments')) {
            $postComments = DB::table('test_posts')
                ->leftJoin('test_comments', 'test_posts.id', '=', 'test_comments.post_id')
                ->select('test_posts.title as post_title', DB::raw('COUNT(test_comments.id) as comment_count'))
                ->groupBy('test_posts.id', 'test_posts.title')
                ->having('comment_count', '>', 0)
                ->limit(5)
                ->get();

            $this->line("   Post -> Comments:");
            foreach ($postComments as $post) {
                $this->line("     " . substr($post->post_title, 0, 30) . "...: {$post->comment_count} comments");
            }
        }
    }

    protected function testNestedRelationships()
    {
        $this->line("🔗🔗 Testing multi-level nested relationships");

        // Department -> User -> Projects -> Tasks (4-level)
        if (Schema::hasTable('af_test_departments') && 
            Schema::hasTable('af_test_users') && 
            Schema::hasTable('af_test_projects') && 
            Schema::hasTable('af_test_tasks')) {
            
            $nested = DB::table('af_test_departments')
                ->join('af_test_users', 'af_test_departments.id', '=', 'af_test_users.department_id')
                ->join('af_test_projects', 'af_test_users.id', '=', 'af_test_projects.manager_id')
                ->join('af_test_tasks', 'af_test_projects.id', '=', 'af_test_tasks.project_id')
                ->select(
                    'af_test_departments.name as dept_name',
                    DB::raw('CONCAT(af_test_users.first_name, " ", af_test_users.last_name) as user_name'),
                    'af_test_projects.name as project_name',
                    'af_test_tasks.title as task_title',
                    'af_test_tasks.priority'
                )
                ->limit(10)
                ->get();

            $this->line("   Department -> User -> Project -> Task (showing first 10):");
            foreach ($nested as $item) {
                $this->line("     {$item->dept_name} | {$item->user_name} | {$item->project_name} | {$item->task_title} ({$item->priority})");
            }
        }

        // Category -> Post -> Comments -> User (3-level)
        if (Schema::hasTable('test_categories') && 
            Schema::hasTable('test_posts') && 
            Schema::hasTable('test_comments') &&
            Schema::hasTable('test_users')) {
            
            $nested = DB::table('test_categories')
                ->join('test_posts', 'test_categories.id', '=', 'test_posts.category_id')
                ->join('test_comments', 'test_posts.id', '=', 'test_comments.post_id')
                ->join('test_users', 'test_comments.user_id', '=', 'test_users.id')
                ->select(
                    'test_categories.name as category_name',
                    'test_posts.title as post_title',
                    'test_comments.content as comment_content',
                    'test_users.name as commenter_name'
                )
                ->limit(5)
                ->get();

            $this->line("   Category -> Post -> Comment -> User (showing first 5):");
            foreach ($nested as $item) {
                $comment = substr($item->comment_content, 0, 30) . '...';
                $post = substr($item->post_title, 0, 20) . '...';
                $this->line("     {$item->category_name} | {$post} | {$comment} | {$item->commenter_name}");
            }
        }

        // Complex aggregation with nested relationships
        $this->line("   Complex nested aggregations:");
        
        if (Schema::hasTable('af_test_departments') && 
            Schema::hasTable('af_test_users') && 
            Schema::hasTable('af_test_projects')) {
            
            $stats = DB::table('af_test_departments')
                ->leftJoin('af_test_users', 'af_test_departments.id', '=', 'af_test_users.department_id')
                ->leftJoin('af_test_projects', 'af_test_users.id', '=', 'af_test_projects.manager_id')
                ->select(
                    'af_test_departments.name as dept_name',
                    DB::raw('COUNT(DISTINCT af_test_users.id) as user_count'),
                    DB::raw('COUNT(DISTINCT af_test_projects.id) as project_count'),
                    DB::raw('AVG(CASE WHEN af_test_projects.status = "completed" THEN 1 ELSE 0 END) * 100 as completion_rate')
                )
                ->groupBy('af_test_departments.id', 'af_test_departments.name')
                ->get();

            foreach ($stats as $stat) {
                $completion = round($stat->completion_rate, 1);
                $this->line("     {$stat->dept_name}: {$stat->user_count} users, {$stat->project_count} projects, {$completion}% completion");
            }
        }
    }

    protected function testJsonOperations()
    {
        $this->line("🗂️ Testing JSON column operations");

        // Test JSON operations on af_test_projects (metadata column)
        if (Schema::hasTable('af_test_projects') && Schema::hasColumn('af_test_projects', 'metadata')) {
            $this->line("   Testing af_test_projects.metadata JSON operations:");
            
            // Count records with specific JSON values
            $withBudget = DB::table('af_test_projects')
                ->whereNotNull('metadata->budget')
                ->count();
            $this->line("     Projects with budget metadata: {$withBudget}");

            $withTags = DB::table('af_test_projects')
                ->whereNotNull('metadata->tags')
                ->count();
            $this->line("     Projects with tags metadata: {$withTags}");

            // JSON search operations
            $urgentProjects = DB::table('af_test_projects')
                ->whereJsonContains('metadata->tags', 'urgent')
                ->count();
            $this->line("     Projects tagged as urgent: {$urgentProjects}");

            $highBudget = DB::table('af_test_projects')
                ->where('metadata->budget', '>', 10000)
                ->count();
            $this->line("     Projects with budget > 10000: {$highBudget}");

            if ($this->option('detailed')) {
                // Show sample JSON data
                $samples = DB::table('af_test_projects')
                    ->whereNotNull('metadata')
                    ->select('name', 'metadata')
                    ->limit(3)
                    ->get();
                
                $this->line("     Sample JSON metadata:");
                foreach ($samples as $sample) {
                    $metadata = is_string($sample->metadata) ? $sample->metadata : json_encode($sample->metadata);
                    $this->line("       {$sample->name}: " . substr($metadata, 0, 50) . "...");
                }
            }
        }

        // Test JSON operations on af_test_tasks (metadata column)
        if (Schema::hasTable('af_test_tasks') && Schema::hasColumn('af_test_tasks', 'metadata')) {
            $this->line("   Testing af_test_tasks.metadata JSON operations:");
            
            $withEstimate = DB::table('af_test_tasks')
                ->whereNotNull('metadata->estimated_hours')
                ->count();
            $this->line("     Tasks with time estimates: {$withEstimate}");

            $withLabels = DB::table('af_test_tasks')
                ->whereNotNull('metadata->labels')
                ->count();
            $this->line("     Tasks with labels: {$withLabels}");

            // Complex JSON queries
            $bugTasks = DB::table('af_test_tasks')
                ->whereJsonContains('metadata->labels', 'bug')
                ->count();
            $this->line("     Bug-labeled tasks: {$bugTasks}");

            $longTasks = DB::table('af_test_tasks')
                ->where('metadata->estimated_hours', '>', 8)
                ->count();
            $this->line("     Tasks estimated > 8 hours: {$longTasks}");
        }

        // Test JSON operations on other tables if they have JSON columns
        foreach (['test_posts', 'test_users', 'test_categories'] as $table) {
            if (Schema::hasTable($table)) {
                $columns = Schema::getColumnListing($table);
                $jsonColumns = [];
                
                foreach ($columns as $column) {
                    $columnType = Schema::getColumnType($table, $column);
                    if (in_array($columnType, ['json', 'text'])) {
                        // Check if it contains JSON data
                        $sample = DB::table($table)->whereNotNull($column)->first();
                        if ($sample && isset($sample->$column)) {
                            $value = $sample->$column;
                            if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
                                $jsonColumns[] = $column;
                            }
                        }
                    }
                }
                
                if (!empty($jsonColumns)) {
                    $this->line("   {$table} JSON columns: " . implode(', ', $jsonColumns));
                }
            }
        }
    }

    protected function testBulkOperations()
    {
        $this->line("⚡ Testing bulk operations and batch processing");

        // Test bulk inserts (we'll use a temporary table for this)
        $tempTable = 'af_test_bulk_temp';
        
        try {
            // Create temporary table
            if (!Schema::hasTable($tempTable)) {
                Schema::create($tempTable, function($table) {
                    $table->id();
                    $table->string('name');
                    $table->string('email');
                    $table->timestamps();
                });
            }

            // Test bulk insert performance
            $startTime = microtime(true);
            $batchSize = 100;
            $batches = 5;
            
            for ($batch = 0; $batch < $batches; $batch++) {
                $data = [];
                for ($i = 0; $i < $batchSize; $i++) {
                    $data[] = [
                        'name' => 'Bulk User ' . ($batch * $batchSize + $i),
                        'email' => 'bulk' . ($batch * $batchSize + $i) . '@example.com',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table($tempTable)->insert($data);
            }
            
            $insertTime = round((microtime(true) - $startTime) * 1000, 2);
            $totalRecords = $batches * $batchSize;
            $this->line("   Bulk insert: {$totalRecords} records in {$insertTime}ms");

            // Test bulk select performance
            $startTime = microtime(true);
            $results = DB::table($tempTable)
                ->where('name', 'like', '%User%')
                ->get();
            $selectTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->line("   Bulk select: {$results->count()} records in {$selectTime}ms");

            // Test bulk update performance
            $startTime = microtime(true);
            $updated = DB::table($tempTable)
                ->where('id', '<=', 50)
                ->update(['name' => DB::raw("CONCAT(name, ' - Updated')")]);
            $updateTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->line("   Bulk update: {$updated} records in {$updateTime}ms");

            // Test bulk delete performance
            $startTime = microtime(true);
            $deleted = DB::table($tempTable)
                ->where('id', '>', 400)
                ->delete();
            $deleteTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->line("   Bulk delete: {$deleted} records in {$deleteTime}ms");

            // Cleanup
            Schema::dropIfExists($tempTable);
            
        } catch (\Exception $e) {
            $this->error("   Bulk operations test failed: " . $e->getMessage());
            Schema::dropIfExists($tempTable);
        }

        // Test pagination performance on existing tables
        if (Schema::hasTable('af_test_users')) {
            $this->line("   Testing pagination performance on af_test_users:");
            
            $pageSize = 25;
            $pages = 3;
            
            for ($page = 1; $page <= $pages; $page++) {
                $startTime = microtime(true);
                $results = DB::table('af_test_users')
                    ->orderBy('id')
                    ->offset(($page - 1) * $pageSize)
                    ->limit($pageSize)
                    ->get();
                $pageTime = round((microtime(true) - $startTime) * 1000, 2);
                $this->line("     Page {$page}: {$results->count()} records in {$pageTime}ms");
            }
        }

        // Test complex aggregation performance
        if (Schema::hasTable('af_test_projects') && Schema::hasTable('af_test_tasks')) {
            $this->line("   Testing complex aggregation performance:");
            
            $startTime = microtime(true);
            $stats = DB::table('af_test_projects')
                ->leftJoin('af_test_tasks', 'af_test_projects.id', '=', 'af_test_tasks.project_id')
                ->select(
                    'af_test_projects.status',
                    DB::raw('COUNT(DISTINCT af_test_projects.id) as project_count'),
                    DB::raw('COUNT(af_test_tasks.id) as task_count'),
                    DB::raw('AVG(CASE WHEN af_test_tasks.priority = "high" THEN 1 ELSE 0 END) * 100 as high_priority_percent')
                )
                ->groupBy('af_test_projects.status')
                ->get();
            $aggTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->line("     Complex aggregation completed in {$aggTime}ms:");
            foreach ($stats as $stat) {
                $highPct = round($stat->high_priority_percent, 1);
                $this->line("       {$stat->status}: {$stat->project_count} projects, {$stat->task_count} tasks, {$highPct}% high priority");
            }
        }
    }

    protected function testMemoryPerformance()
    {
        $this->line("🧠 Testing memory usage and performance benchmarks");

        $initialMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();
        
        $this->line("   Initial memory usage: " . round($initialMemory / 1024 / 1024, 2) . " MB");
        $this->line("   Peak memory usage: " . round($peakMemory / 1024 / 1024, 2) . " MB");

        // Test memory usage with large result sets
        if (Schema::hasTable('af_test_users')) {
            $startMemory = memory_get_usage();
            
            $users = DB::table('af_test_users')->get();
            $afterSelectMemory = memory_get_usage();
            
            $memoryIncrease = round(($afterSelectMemory - $startMemory) / 1024 / 1024, 2);
            $this->line("   Loading {$users->count()} users: +{$memoryIncrease} MB");
            
            unset($users);
            $afterUnsetMemory = memory_get_usage();
            $memoryFreed = round(($afterSelectMemory - $afterUnsetMemory) / 1024 / 1024, 2);
            $this->line("   Memory freed after unset: {$memoryFreed} MB");
        }

        // Test chunked processing vs full load
        if (Schema::hasTable('af_test_tasks')) {
            $this->line("   Comparing chunked vs full load processing:");
            
            // Full load test
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            $allTasks = DB::table('af_test_tasks')->get();
            $processedCount = 0;
            foreach ($allTasks as $task) {
                $processedCount++;
                // Simulate processing
            }
            
            $fullLoadTime = round((microtime(true) - $startTime) * 1000, 2);
            $fullLoadMemory = round((memory_get_usage() - $startMemory) / 1024 / 1024, 2);
            $this->line("     Full load: {$processedCount} tasks in {$fullLoadTime}ms, +{$fullLoadMemory}MB");
            
            unset($allTasks);
            
            // Chunked processing test
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            $processedCount = 0;
            
            DB::table('af_test_tasks')->orderBy('id')->chunk(100, function($tasks) use (&$processedCount) {
                foreach ($tasks as $task) {
                    $processedCount++;
                    // Simulate processing
                }
            });
            
            $chunkTime = round((microtime(true) - $startTime) * 1000, 2);
            $chunkMemory = round((memory_get_usage() - $startMemory) / 1024 / 1024, 2);
            $this->line("     Chunked: {$processedCount} tasks in {$chunkTime}ms, +{$chunkMemory}MB");
        }

        // Database connection performance
        $this->line("   Database connection performance:");
        
        $connectionTests = ['simple_select', 'complex_join', 'aggregation', 'subquery'];
        
        foreach ($connectionTests as $test) {
            $startTime = microtime(true);
            
            switch ($test) {
                case 'simple_select':
                    if (Schema::hasTable('af_test_users')) {
                        DB::table('af_test_users')->limit(10)->get();
                    }
                    break;
                    
                case 'complex_join':
                    if (Schema::hasTable('af_test_users') && Schema::hasTable('af_test_projects')) {
                        DB::table('af_test_users')
                            ->join('af_test_projects', 'af_test_users.id', '=', 'af_test_projects.manager_id')
                            ->limit(10)
                            ->get();
                    }
                    break;
                    
                case 'aggregation':
                    if (Schema::hasTable('af_test_projects')) {
                        DB::table('af_test_projects')
                            ->select('status', DB::raw('COUNT(*) as count'))
                            ->groupBy('status')
                            ->get();
                    }
                    break;
                    
                case 'subquery':
                    if (Schema::hasTable('af_test_users') && Schema::hasTable('af_test_projects')) {
                        DB::table('af_test_users')
                            ->whereIn('id', function($query) {
                                $query->select('manager_id')
                                      ->from('af_test_projects')
                                      ->where('status', 'active');
                            })
                            ->limit(10)
                            ->get();
                    }
                    break;
            }
            
            $testTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->line("     {$test}: {$testTime}ms");
        }

        // Final memory report
        $finalMemory = memory_get_usage();
        $finalPeak = memory_get_peak_usage();
        
        $totalMemoryUsed = round(($finalMemory - $initialMemory) / 1024 / 1024, 2);
        $peakMemoryUsed = round($finalPeak / 1024 / 1024, 2);
        
        $this->line("   Final memory usage: " . round($finalMemory / 1024 / 1024, 2) . " MB");
        $this->line("   Total memory increase: {$totalMemoryUsed} MB");
        $this->line("   Peak memory during testing: {$peakMemoryUsed} MB");

        if ($this->option('benchmark')) {
            $this->runBenchmarkSuite();
        }
    }

    protected function runBenchmarkSuite()
    {
        $this->newLine();
        $this->info("🏁 Running comprehensive benchmark suite");
        $this->line(str_repeat('=', 60));

        $benchmarks = [
            'Simple SELECT queries' => function() {
                return $this->benchmarkSimpleSelects();
            },
            'Complex JOIN queries' => function() {
                return $this->benchmarkComplexJoins();
            },
            'Aggregation queries' => function() {
                return $this->benchmarkAggregations();
            },
            'Search operations' => function() {
                return $this->benchmarkSearchOperations();
            },
            'JSON operations' => function() {
                return $this->benchmarkJsonOperations();
            }
        ];

        $results = [];
        
        foreach ($benchmarks as $name => $benchmark) {
            $this->line("🔄 Running: {$name}");
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            $result = $benchmark();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            $duration = round(($endTime - $startTime) * 1000, 2);
            $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2);
            
            $results[$name] = [
                'duration' => $duration,
                'memory' => $memoryUsed,
                'operations' => $result['operations'] ?? 0,
                'records' => $result['records'] ?? 0
            ];
            
            $this->line("   ⏱️  {$duration}ms | 🧠 {$memoryUsed}MB | 📊 {$result['operations']} ops | 📝 {$result['records']} records");
        }

        $this->newLine();
        $this->info("📈 Benchmark Summary");
        $this->line(str_repeat('-', 60));
        
        foreach ($results as $name => $result) {
            $opsPerSec = $result['operations'] > 0 ? round($result['operations'] / ($result['duration'] / 1000), 2) : 0;
            $this->line("🎯 {$name}:");
            $this->line("   Duration: {$result['duration']}ms | Memory: {$result['memory']}MB");
            $this->line("   Operations/sec: {$opsPerSec} | Records processed: {$result['records']}");
            $this->newLine();
        }
    }

    protected function benchmarkSimpleSelects()
    {
        $operations = 0;
        $records = 0;
        
        $queries = [
            ['table' => 'af_test_users', 'limit' => 50],
            ['table' => 'af_test_projects', 'limit' => 30],
            ['table' => 'af_test_tasks', 'limit' => 100],
            ['table' => 'test_posts', 'limit' => 25],
            ['table' => 'test_comments', 'limit' => 75]
        ];
        
        foreach ($queries as $query) {
            if (Schema::hasTable($query['table'])) {
                $result = DB::table($query['table'])->limit($query['limit'])->get();
                $operations++;
                $records += $result->count();
            }
        }
        
        return ['operations' => $operations, 'records' => $records];
    }

    protected function benchmarkComplexJoins()
    {
        $operations = 0;
        $records = 0;
        
        // AF Table joins
        if (Schema::hasTable('af_test_departments') && Schema::hasTable('af_test_users')) {
            $result = DB::table('af_test_departments')
                ->join('af_test_users', 'af_test_departments.id', '=', 'af_test_users.department_id')
                ->limit(50)
                ->get();
            $operations++;
            $records += $result->count();
        }
        
        if (Schema::hasTable('af_test_users') && Schema::hasTable('af_test_projects')) {
            $result = DB::table('af_test_users')
                ->join('af_test_projects', 'af_test_users.id', '=', 'af_test_projects.manager_id')
                ->limit(50)
                ->get();
            $operations++;
            $records += $result->count();
        }
        
        if (Schema::hasTable('af_test_projects') && Schema::hasTable('af_test_tasks')) {
            $result = DB::table('af_test_projects')
                ->join('af_test_tasks', 'af_test_projects.id', '=', 'af_test_tasks.project_id')
                ->limit(100)
                ->get();
            $operations++;
            $records += $result->count();
        }
        
        // Test table joins
        if (Schema::hasTable('test_categories') && Schema::hasTable('test_posts')) {
            $result = DB::table('test_categories')
                ->join('test_posts', 'test_categories.id', '=', 'test_posts.category_id')
                ->limit(50)
                ->get();
            $operations++;
            $records += $result->count();
        }
        
        if (Schema::hasTable('test_posts') && Schema::hasTable('test_comments')) {
            $result = DB::table('test_posts')
                ->join('test_comments', 'test_posts.id', '=', 'test_comments.post_id')
                ->limit(75)
                ->get();
            $operations++;
            $records += $result->count();
        }
        
        return ['operations' => $operations, 'records' => $records];
    }

    protected function benchmarkAggregations()
    {
        $operations = 0;
        $records = 0;
        
        $aggregations = [
            ['table' => 'af_test_users', 'column' => 'department_id'],
            ['table' => 'af_test_projects', 'column' => 'status'],
            ['table' => 'af_test_tasks', 'column' => 'priority'],
            ['table' => 'test_posts', 'column' => 'category_id'],
            ['table' => 'test_comments', 'column' => 'post_id']
        ];
        
        foreach ($aggregations as $agg) {
            if (Schema::hasTable($agg['table']) && Schema::hasColumn($agg['table'], $agg['column'])) {
                $result = DB::table($agg['table'])
                    ->select($agg['column'], DB::raw('COUNT(*) as count'))
                    ->groupBy($agg['column'])
                    ->get();
                $operations++;
                $records += $result->count();
            }
        }
        
        return ['operations' => $operations, 'records' => $records];
    }

    protected function benchmarkSearchOperations()
    {
        $operations = 0;
        $records = 0;
        
        $searches = [
            ['table' => 'af_test_users', 'column' => 'first_name', 'term' => 'John'],
            ['table' => 'af_test_users', 'column' => 'email', 'term' => '@example.com'],
            ['table' => 'af_test_projects', 'column' => 'name', 'term' => 'Project'],
            ['table' => 'af_test_projects', 'column' => 'description', 'term' => 'Laravel'],
            ['table' => 'af_test_tasks', 'column' => 'title', 'term' => 'Task'],
            ['table' => 'test_posts', 'column' => 'title', 'term' => 'Post'],
            ['table' => 'test_posts', 'column' => 'content', 'term' => 'content'],
            ['table' => 'test_comments', 'column' => 'content', 'term' => 'comment']
        ];
        
        foreach ($searches as $search) {
            if (Schema::hasTable($search['table']) && Schema::hasColumn($search['table'], $search['column'])) {
                $result = DB::table($search['table'])
                    ->where($search['column'], 'like', '%' . $search['term'] . '%')
                    ->get();
                $operations++;
                $records += $result->count();
            }
        }
        
        return ['operations' => $operations, 'records' => $records];
    }

    protected function benchmarkJsonOperations()
    {
        $operations = 0;
        $records = 0;
        
        // Test JSON operations on af_test_projects
        if (Schema::hasTable('af_test_projects') && Schema::hasColumn('af_test_projects', 'metadata')) {
            $queries = [
                function() { return DB::table('af_test_projects')->whereNotNull('metadata->budget')->get(); },
                function() { return DB::table('af_test_projects')->whereNotNull('metadata->tags')->get(); },
                function() { return DB::table('af_test_projects')->whereJsonContains('metadata->tags', 'urgent')->get(); },
                function() { return DB::table('af_test_projects')->where('metadata->budget', '>', 5000)->get(); }
            ];
            
            foreach ($queries as $query) {
                try {
                    $result = $query();
                    $operations++;
                    $records += $result->count();
                } catch (\Exception $e) {
                    // Skip if JSON operation not supported
                }
            }
        }
        
        // Test JSON operations on af_test_tasks
        if (Schema::hasTable('af_test_tasks') && Schema::hasColumn('af_test_tasks', 'metadata')) {
            $queries = [
                function() { return DB::table('af_test_tasks')->whereNotNull('metadata->estimated_hours')->get(); },
                function() { return DB::table('af_test_tasks')->whereNotNull('metadata->labels')->get(); },
                function() { return DB::table('af_test_tasks')->where('metadata->estimated_hours', '>', 4)->get(); }
            ];
            
            foreach ($queries as $query) {
                try {
                    $result = $query();
                    $operations++;
                    $records += $result->count();
                } catch (\Exception $e) {
                    // Skip if JSON operation not supported
                }
            }
        }
        
        return ['operations' => $operations, 'records' => $records];
    }

    protected function createTestData()
    {
        $this->warn('Creating test data functionality not implemented in this version.');
        $this->info('Use --use-existing flag to test with real AF Table data.');
    }
}
