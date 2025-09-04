<?php

namespace ArtflowStudio\Table\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AFTestDataSeeder extends Seeder
{
    private $faker;
    private $userIds = [];
    private $companyIds = [];
    private $productIds = [];
    private $orderIds = [];
    private $departmentIds = [];
    private $projectIds = [];
    private $taskIds = [];
    private $tagIds = [];
    private $permissionIds = [];

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting AF Table Test Data Seeding...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        $this->clearExistingData();
        
        // Seed data in dependency order
        $this->seedUsers(500);           // Table 1
        $this->seedCompanies(50);        // Table 2  
        $this->seedProducts(1000);       // Table 3
        $this->seedOrders(2000);         // Table 4
        $this->seedOrderItems(5000);     // Table 5
        $this->seedDepartments(200);     // Table 6
        $this->seedProjects(300);        // Table 7
        $this->seedTasks(1500);          // Table 8
        $this->seedComments(3000);       // Table 9
        $this->seedFiles(800);           // Table 10
        $this->seedEvents(600);          // Table 11
        $this->seedNotifications(2500);  // Table 12
        $this->seedAnalytics(5000);      // Table 13
        $this->seedSettings(400);        // Table 14
        $this->seedLogs(10000);          // Table 15
        $this->seedTags(100);            // Table 16
        $this->seedTaggables(2000);      // Table 17
        $this->seedPermissions(50);      // Table 18
        $this->seedUserPermissions(1000); // Table 19
        $this->seedReports(100);         // Table 20
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('✅ AF Table Test Data Seeding Completed!');
    }

    private function clearExistingData(): void
    {
        $this->command->info('🧹 Clearing existing test data...');
        
        $tables = [
            'af_test_table20', 'af_test_table19', 'af_test_table18', 'af_test_table17',
            'af_test_table16', 'af_test_table15', 'af_test_table14', 'af_test_table13',
            'af_test_table12', 'af_test_table11', 'af_test_table10', 'af_test_table9',
            'af_test_table8', 'af_test_table7', 'af_test_table6', 'af_test_table5',
            'af_test_table4', 'af_test_table3', 'af_test_table2', 'af_test_table1'
        ];
        
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
    }

    private function seedUsers(int $count): void
    {
        $this->command->info("👥 Seeding {$count} users...");
        
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $user = [
                'name' => $this->faker->name(),
                'email' => $this->faker->unique()->safeEmail(),
                'username' => $this->faker->unique()->userName(),
                'status' => $this->faker->randomElement(['active', 'inactive', 'pending', 'suspended']),
                'role' => $this->faker->randomElement(['admin', 'manager', 'employee', 'client']),
                'profile' => json_encode([
                    'avatar' => $this->faker->imageUrl(200, 200, 'people'),
                    'bio' => $this->faker->paragraph(),
                    'social_links' => [
                        'twitter' => '@' . $this->faker->userName(),
                        'linkedin' => $this->faker->url(),
                        'github' => $this->faker->userName()
                    ]
                ]),
                'preferences' => json_encode([
                    'theme' => $this->faker->randomElement(['light', 'dark', 'auto']),
                    'language' => $this->faker->randomElement(['en', 'es', 'fr', 'de', 'it']),
                    'notifications' => [
                        'email' => $this->faker->boolean(),
                        'push' => $this->faker->boolean(),
                        'sms' => $this->faker->boolean()
                    ]
                ]),
                'score' => $this->faker->randomFloat(2, 0, 100),
                'birth_date' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
                'last_login_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'created_at' => $now = $this->faker->dateTimeBetween('-2 years', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now'),
                'deleted_at' => $this->faker->optional(0.05)->dateTimeBetween('-6 months', 'now')
            ];
            $users[] = $user;
        }
        
        DB::table('af_test_table1')->insert($users);
        $this->userIds = DB::table('af_test_table1')->pluck('id')->toArray();
    }

    private function seedCompanies(int $count): void
    {
        $this->command->info("🏢 Seeding {$count} companies...");
        
        $companies = [];
        for ($i = 0; $i < $count; $i++) {
            $company = [
                'name' => $this->faker->company(),
                'slug' => $this->faker->unique()->slug(),
                'type' => $this->faker->randomElement(['startup', 'corporation', 'non-profit', 'government']),
                'industry' => $this->faker->randomElement(['tech', 'finance', 'healthcare', 'education', 'retail']),
                'address' => json_encode([
                    'street' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'country' => $this->faker->country(),
                    'postal_code' => $this->faker->postcode()
                ]),
                'contact' => json_encode([
                    'phone' => $this->faker->phoneNumber(),
                    'email' => $this->faker->companyEmail(),
                    'website' => $this->faker->url(),
                    'social' => [
                        'twitter' => '@' . $this->faker->userName(),
                        'linkedin' => $this->faker->url(),
                        'facebook' => $this->faker->url()
                    ]
                ]),
                'metadata' => json_encode([
                    'founded_year' => $this->faker->year(),
                    'employee_count' => $this->faker->numberBetween(10, 10000),
                    'revenue' => $this->faker->randomFloat(2, 100000, 1000000000)
                ]),
                'employee_count' => $this->faker->numberBetween(10, 10000),
                'annual_revenue' => $this->faker->randomFloat(2, 100000, 1000000000),
                'is_public' => $this->faker->boolean(30),
                'created_at' => $now = $this->faker->dateTimeBetween('-5 years', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $companies[] = $company;
        }
        
        DB::table('af_test_table2')->insert($companies);
        $this->companyIds = DB::table('af_test_table2')->pluck('id')->toArray();
    }

    private function seedProducts(int $count): void
    {
        $this->command->info("📦 Seeding {$count} products...");
        
        $products = [];
        for ($i = 0; $i < $count; $i++) {
            $price = $this->faker->randomFloat(2, 10, 1000);
            $product = [
                'name' => $this->faker->words(3, true),
                'sku' => $this->faker->unique()->bothify('SKU-####-????'),
                'company_id' => $this->faker->randomElement($this->companyIds),
                'category' => $this->faker->randomElement(['electronics', 'clothing', 'books', 'home', 'sports']),
                'price' => $price,
                'cost' => $price * $this->faker->randomFloat(2, 0.3, 0.8),
                'stock_quantity' => $this->faker->numberBetween(0, 1000),
                'specifications' => json_encode([
                    'dimensions' => $this->faker->randomElement(['10x5x2 cm', '20x15x8 cm', '50x30x10 cm']),
                    'weight' => $this->faker->randomFloat(2, 0.1, 10) . ' kg',
                    'color' => $this->faker->colorName(),
                    'features' => $this->faker->words(5)
                ]),
                'pricing_history' => json_encode([
                    [
                        'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                        'price' => $price * 1.2,
                        'discount' => 0
                    ],
                    [
                        'date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                        'price' => $price,
                        'discount' => 20
                    ]
                ]),
                'tags' => json_encode($this->faker->words(3)),
                'rating' => $this->faker->randomFloat(2, 1, 5),
                'review_count' => $this->faker->numberBetween(0, 500),
                'is_featured' => $this->faker->boolean(20),
                'launched_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
                'created_at' => $now = $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $products[] = $product;
        }
        
        DB::table('af_test_table3')->insert($products);
        $this->productIds = DB::table('af_test_table3')->pluck('id')->toArray();
    }

    private function seedOrders(int $count): void
    {
        $this->command->info("🛒 Seeding {$count} orders...");
        
        $orders = [];
        for ($i = 0; $i < $count; $i++) {
            $subtotal = $this->faker->randomFloat(2, 20, 1000);
            $tax = $subtotal * 0.08;
            $shipping = $this->faker->randomFloat(2, 5, 25);
            $total = $subtotal + $tax + $shipping;
            
            $order = [
                'order_number' => 'ORD-' . $this->faker->unique()->numerify('######'),
                'user_id' => $this->faker->randomElement($this->userIds),
                'company_id' => $this->faker->randomElement($this->companyIds),
                'status' => $this->faker->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
                'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed', 'refunded']),
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_cost' => $shipping,
                'total_amount' => $total,
                'shipping_address' => json_encode([
                    'street' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'country' => $this->faker->country(),
                    'postal' => $this->faker->postcode()
                ]),
                'billing_address' => json_encode([
                    'street' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'country' => $this->faker->country(),
                    'postal' => $this->faker->postcode()
                ]),
                'payment_details' => json_encode([
                    'method' => $this->faker->randomElement(['credit_card', 'paypal', 'bank_transfer']),
                    'card_last4' => $this->faker->numerify('####'),
                    'transaction_id' => $this->faker->uuid()
                ]),
                'tracking_info' => json_encode([
                    'carrier' => $this->faker->randomElement(['UPS', 'FedEx', 'DHL', 'USPS']),
                    'tracking_number' => $this->faker->bothify('##??########'),
                    'events' => [
                        ['date' => $this->faker->dateTime()->format('Y-m-d H:i:s'), 'status' => 'Order placed'],
                        ['date' => $this->faker->dateTime()->format('Y-m-d H:i:s'), 'status' => 'In transit']
                    ]
                ]),
                'shipped_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 month', 'now'),
                'delivered_at' => $this->faker->optional(0.5)->dateTimeBetween('-1 month', 'now'),
                'created_at' => $now = $this->faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $orders[] = $order;
        }
        
        DB::table('af_test_table4')->insert($orders);
        $this->orderIds = DB::table('af_test_table4')->pluck('id')->toArray();
    }

    private function seedOrderItems(int $count): void
    {
        $this->command->info("📋 Seeding {$count} order items...");
        
        $orderItems = [];
        for ($i = 0; $i < $count; $i++) {
            $quantity = $this->faker->numberBetween(1, 5);
            $unitPrice = $this->faker->randomFloat(2, 10, 200);
            $totalPrice = $quantity * $unitPrice;
            
            $orderItem = [
                'order_id' => $this->faker->randomElement($this->orderIds),
                'product_id' => $this->faker->randomElement($this->productIds),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'product_snapshot' => json_encode([
                    'name' => $this->faker->words(3, true),
                    'sku' => $this->faker->bothify('SKU-####-????'),
                    'description' => $this->faker->sentence()
                ]),
                'customizations' => json_encode([
                    'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
                    'color' => $this->faker->colorName(),
                    'engravings' => $this->faker->optional()->words(2, true)
                ]),
                'created_at' => $now = $this->faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $orderItems[] = $orderItem;
        }
        
        DB::table('af_test_table5')->insert($orderItems);
    }

    private function seedDepartments(int $count): void
    {
        $this->command->info("🏢 Seeding {$count} departments...");
        
        $departments = [];
        for ($i = 0; $i < $count; $i++) {
            $department = [
                'name' => $this->faker->randomElement(['Engineering', 'Marketing', 'Sales', 'HR', 'Finance', 'Operations', 'Legal', 'IT']),
                'code' => $this->faker->unique()->bothify('DEPT-###'),
                'company_id' => $this->faker->randomElement($this->companyIds),
                'manager_id' => $this->faker->randomElement($this->userIds),
                'parent_id' => $this->faker->optional(0.3)->randomElement($this->departmentIds ?: [null]),
                'description' => $this->faker->paragraph(),
                'budget_info' => json_encode([
                    'annual_budget' => $this->faker->numberBetween(100000, 5000000),
                    'spent' => $this->faker->numberBetween(50000, 2000000),
                    'remaining' => $this->faker->numberBetween(50000, 3000000)
                ]),
                'metrics' => json_encode([
                    'headcount' => $this->faker->numberBetween(5, 50),
                    'performance_score' => $this->faker->randomFloat(2, 60, 100)
                ]),
                'employee_count' => $this->faker->numberBetween(5, 50),
                'is_active' => $this->faker->boolean(90),
                'created_at' => $now = $this->faker->dateTimeBetween('-2 years', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $departments[] = $department;
        }
        
        DB::table('af_test_table6')->insert($departments);
        $this->departmentIds = DB::table('af_test_table6')->pluck('id')->toArray();
    }

    private function seedProjects(int $count): void
    {
        $this->command->info("📊 Seeding {$count} projects...");
        
        $projects = [];
        for ($i = 0; $i < $count; $i++) {
            $startDate = $this->faker->dateTimeBetween('-1 year', '+3 months');
            $endDate = $this->faker->dateTimeBetween($startDate, '+1 year');
            
            $project = [
                'name' => $this->faker->catchPhrase(),
                'code' => $this->faker->unique()->bothify('PROJ-###'),
                'company_id' => $this->faker->randomElement($this->companyIds),
                'department_id' => $this->faker->randomElement($this->departmentIds),
                'manager_id' => $this->faker->randomElement($this->userIds),
                'status' => $this->faker->randomElement(['planning', 'active', 'on-hold', 'completed', 'cancelled']),
                'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
                'description' => $this->faker->paragraphs(3, true),
                'requirements' => json_encode([
                    'functional' => $this->faker->sentences(3),
                    'technical' => $this->faker->sentences(2),
                    'business' => $this->faker->sentences(2)
                ]),
                'timeline' => json_encode([
                    'phases' => [
                        ['name' => 'Planning', 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $this->faker->dateTimeBetween($startDate, $endDate)->format('Y-m-d')],
                        ['name' => 'Development', 'start_date' => $this->faker->dateTimeBetween($startDate, $endDate)->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]
                    ]
                ]),
                'budget' => json_encode([
                    'allocated' => $budget = $this->faker->numberBetween(50000, 500000),
                    'spent' => $spent = $this->faker->numberBetween(0, $budget),
                    'remaining' => $budget - $spent
                ]),
                'budget_amount' => $this->faker->randomFloat(2, 50000, 500000),
                'completion_percentage' => $this->faker->numberBetween(0, 100),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'created_at' => $now = $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $projects[] = $project;
        }
        
        DB::table('af_test_table7')->insert($projects);
        $this->projectIds = DB::table('af_test_table7')->pluck('id')->toArray();
    }

    private function seedTasks(int $count): void
    {
        $this->command->info("✅ Seeding {$count} tasks...");
        
        $tasks = [];
        for ($i = 0; $i < $count; $i++) {
            $estimatedHours = $this->faker->randomFloat(2, 1, 40);
            $loggedHours = $this->faker->randomFloat(2, 0, $estimatedHours * 1.2);
            
            $task = [
                'title' => $this->faker->sentence(6),
                'project_id' => $this->faker->randomElement($this->projectIds),
                'assignee_id' => $this->faker->randomElement($this->userIds),
                'reporter_id' => $this->faker->randomElement($this->userIds),
                'status' => $this->faker->randomElement(['todo', 'in-progress', 'review', 'testing', 'done', 'blocked']),
                'priority' => $this->faker->randomElement(['lowest', 'low', 'medium', 'high', 'highest']),
                'type' => $this->faker->randomElement(['feature', 'bug', 'improvement', 'task', 'epic']),
                'description' => $this->faker->paragraphs(2, true),
                'acceptance_criteria' => json_encode($this->faker->sentences(3)),
                'labels' => json_encode($this->faker->words(3)),
                'time_tracking' => json_encode([
                    'estimated_hours' => $estimatedHours,
                    'logged_hours' => $loggedHours,
                    'remaining_hours' => max(0, $estimatedHours - $loggedHours)
                ]),
                'story_points' => $this->faker->randomElement([1, 2, 3, 5, 8, 13]),
                'estimated_hours' => $estimatedHours,
                'logged_hours' => $loggedHours,
                'due_date' => $this->faker->optional(0.7)->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
                'started_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 month', 'now'),
                'completed_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 month', 'now'),
                'created_at' => $now = $this->faker->dateTimeBetween('-3 months', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $tasks[] = $task;
        }
        
        DB::table('af_test_table8')->insert($tasks);
        $this->taskIds = DB::table('af_test_table8')->pluck('id')->toArray();
    }

    private function seedComments(int $count): void
    {
        $this->command->info("💬 Seeding {$count} comments...");
        
        $morphTypes = [
            'ArtflowStudio\Table\Models\TestTable8', // Tasks
            'ArtflowStudio\Table\Models\TestTable7', // Projects
        ];
        
        $comments = [];
        for ($i = 0; $i < $count; $i++) {
            $commentableType = $this->faker->randomElement($morphTypes);
            $commentableIds = $commentableType === 'ArtflowStudio\Table\Models\TestTable8' ? $this->taskIds : $this->projectIds;
            
            $comment = [
                'commentable_type' => $commentableType,
                'commentable_id' => $this->faker->randomElement($commentableIds),
                'user_id' => $this->faker->randomElement($this->userIds),
                'parent_id' => $this->faker->optional(0.2)->randomElement($this->taskIds ?: [null]),
                'content' => $this->faker->paragraphs($this->faker->numberBetween(1, 3), true),
                'mentions' => json_encode($this->faker->optional(0.3)->randomElements($this->userIds, $this->faker->numberBetween(1, 3))),
                'attachments' => json_encode($this->faker->optional(0.2)->sentences(2)),
                'metadata' => json_encode([
                    'edited_at' => $this->faker->optional(0.1)->dateTime(),
                    'edit_count' => $this->faker->numberBetween(0, 3),
                    'reactions' => [
                        'like' => $this->faker->numberBetween(0, 10),
                        'love' => $this->faker->numberBetween(0, 5)
                    ]
                ]),
                'is_internal' => $this->faker->boolean(20),
                'is_edited' => $this->faker->boolean(10),
                'created_at' => $now = $this->faker->dateTimeBetween('-3 months', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $comments[] = $comment;
        }
        
        DB::table('af_test_table9')->insert($comments);
    }

    private function seedFiles(int $count): void
    {
        $this->command->info("📁 Seeding {$count} files...");
        
        $morphTypes = [
            'ArtflowStudio\Table\Models\TestTable8', // Tasks
            'ArtflowStudio\Table\Models\TestTable7', // Projects
            'ArtflowStudio\Table\Models\TestTable1', // Users
        ];
        
        $files = [];
        for ($i = 0; $i < $count; $i++) {
            $fileableType = $this->faker->randomElement($morphTypes);
            $fileableIds = match($fileableType) {
                'ArtflowStudio\Table\Models\TestTable8' => $this->taskIds,
                'ArtflowStudio\Table\Models\TestTable7' => $this->projectIds,
                default => $this->userIds
            };
            
            $originalName = $this->faker->word() . '.' . $this->faker->fileExtension();
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            
            $file = [
                'name' => $this->faker->uuid() . '.' . $extension,
                'original_name' => $originalName,
                'fileable_type' => $fileableType,
                'fileable_id' => $this->faker->randomElement($fileableIds),
                'uploaded_by' => $this->faker->randomElement($this->userIds),
                'mime_type' => $this->faker->mimeType(),
                'extension' => $extension,
                'size_bytes' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
                'path' => 'uploads/' . $this->faker->uuid() . '.' . $extension,
                'disk' => $this->faker->randomElement(['local', 's3', 'gcs']),
                'metadata' => json_encode([
                    'dimensions' => $this->faker->optional()->randomElement(['1920x1080', '800x600', '1024x768']),
                    'duration' => $this->faker->optional()->numberBetween(30, 3600), // seconds
                    'pages' => $this->faker->optional()->numberBetween(1, 100)
                ]),
                'versions' => json_encode([
                    ['version' => 1, 'path' => 'uploads/v1/' . $this->faker->uuid(), 'created_at' => $this->faker->dateTime()]
                ]),
                'hash' => $this->faker->sha256(),
                'is_public' => $this->faker->boolean(30),
                'download_count' => $this->faker->numberBetween(0, 100),
                'last_accessed_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 month', 'now'),
                'created_at' => $now = $this->faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now'),
                'deleted_at' => $this->faker->optional(0.02)->dateTimeBetween('-1 month', 'now')
            ];
            $files[] = $file;
        }
        
        DB::table('af_test_table10')->insert($files);
    }

    private function seedEvents(int $count): void
    {
        $this->command->info("📅 Seeding {$count} events...");
        
        $events = [];
        for ($i = 0; $i < $count; $i++) {
            $startDateTime = $this->faker->dateTimeBetween('-1 month', '+3 months');
            $endDateTime = (clone $startDateTime)->modify('+' . $this->faker->numberBetween(30, 480) . ' minutes');
            
            $event = [
                'title' => $this->faker->sentence(4),
                'description' => $this->faker->optional(0.8)->paragraph(),
                'organizer_id' => $this->faker->randomElement($this->userIds),
                'project_id' => $this->faker->optional(0.6)->randomElement($this->projectIds),
                'type' => $this->faker->randomElement(['meeting', 'deadline', 'milestone', 'reminder', 'holiday']),
                'status' => $this->faker->randomElement(['scheduled', 'in-progress', 'completed', 'cancelled']),
                'start_datetime' => $startDateTime,
                'end_datetime' => $endDateTime,
                'timezone' => $this->faker->timezone(),
                'attendees' => json_encode($this->faker->randomElements($this->userIds, $this->faker->numberBetween(2, 8))),
                'recurrence' => json_encode($this->faker->optional(0.2)->randomElement([
                    ['pattern' => 'daily', 'frequency' => 1, 'end_date' => $this->faker->dateTimeBetween('+1 month', '+6 months')->format('Y-m-d')],
                    ['pattern' => 'weekly', 'frequency' => 1, 'end_date' => $this->faker->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d')]
                ])),
                'location' => json_encode([
                    'type' => $this->faker->randomElement(['office', 'virtual', 'external']),
                    'address' => $this->faker->address(),
                    'virtual_link' => $this->faker->optional(0.4)->url()
                ]),
                'reminders' => json_encode([
                    ['time_before' => 15, 'method' => 'email'],
                    ['time_before' => 5, 'method' => 'push']
                ]),
                'is_all_day' => $this->faker->boolean(10),
                'is_recurring' => $this->faker->boolean(20),
                'created_at' => $now = $this->faker->dateTimeBetween('-2 months', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $events[] = $event;
        }
        
        DB::table('af_test_table11')->insert($events);
    }

    private function seedNotifications(int $count): void
    {
        $this->command->info("🔔 Seeding {$count} notifications...");
        
        $morphTypes = [
            'ArtflowStudio\Table\Models\TestTable8', // Tasks  
            'ArtflowStudio\Table\Models\TestTable7', // Projects
            'ArtflowStudio\Table\Models\TestTable4', // Orders
        ];
        
        $notifications = [];
        for ($i = 0; $i < $count; $i++) {
            $notifiableType = $this->faker->randomElement($morphTypes);
            $notifiableIds = match($notifiableType) {
                'ArtflowStudio\Table\Models\TestTable8' => $this->taskIds,
                'ArtflowStudio\Table\Models\TestTable7' => $this->projectIds,
                default => $this->orderIds
            };
            
            $notification = [
                'user_id' => $this->faker->randomElement($this->userIds),
                'notifiable_type' => $notifiableType,
                'notifiable_id' => $this->faker->randomElement($notifiableIds),
                'type' => $this->faker->randomElement(['task_assigned', 'project_updated', 'order_shipped', 'deadline_reminder']),
                'title' => $this->faker->sentence(4),
                'message' => $this->faker->sentence(8),
                'data' => json_encode([
                    'action_url' => $this->faker->url(),
                    'action_text' => $this->faker->randomElement(['View Task', 'View Project', 'Track Order'])
                ]),
                'channels' => json_encode($this->faker->randomElements(['email', 'sms', 'push', 'in-app'], $this->faker->numberBetween(1, 3))),
                'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
                'read_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 month', 'now'),
                'delivered_at' => $this->faker->optional(0.9)->dateTimeBetween('-1 month', 'now'),
                'scheduled_for' => $this->faker->optional(0.1)->dateTimeBetween('now', '+1 week'),
                'is_read' => $this->faker->boolean(60),
                'created_at' => $now = $this->faker->dateTimeBetween('-2 months', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $notifications[] = $notification;
        }
        
        DB::table('af_test_table12')->insert($notifications);
    }

    private function seedAnalytics(int $count): void
    {
        $this->command->info("📈 Seeding {$count} analytics records...");
        
        $morphTypes = [
            'ArtflowStudio\Table\Models\TestTable2', // Companies
            'ArtflowStudio\Table\Models\TestTable7', // Projects
            'ArtflowStudio\Table\Models\TestTable3', // Products
        ];
        
        $analytics = [];
        for ($i = 0; $i < $count; $i++) {
            $trackableType = $this->faker->randomElement($morphTypes);
            $trackableIds = match($trackableType) {
                'ArtflowStudio\Table\Models\TestTable2' => $this->companyIds,
                'ArtflowStudio\Table\Models\TestTable7' => $this->projectIds,
                default => $this->productIds
            };
            
            $analytic = [
                'metric_name' => $this->faker->randomElement(['revenue', 'users', 'conversions', 'page_views', 'bounce_rate', 'session_duration']),
                'trackable_type' => $trackableType,
                'trackable_id' => $this->faker->randomElement($trackableIds),
                'recorded_by' => $this->faker->optional(0.8)->randomElement($this->userIds),
                'metric_type' => $this->faker->randomElement(['counter', 'gauge', 'timer', 'histogram']),
                'period' => $this->faker->randomElement(['hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'yearly']),
                'value' => $this->faker->randomFloat(4, 0, 10000),
                'dimensions' => json_encode([
                    'category' => $this->faker->word(),
                    'subcategory' => $this->faker->word(),
                    'tags' => $this->faker->words(2)
                ]),
                'metadata' => json_encode([
                    'source' => $this->faker->randomElement(['google_analytics', 'internal', 'third_party']),
                    'confidence' => $this->faker->randomFloat(2, 0.8, 1.0)
                ]),
                'period_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'recorded_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'created_at' => $now = $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $analytics[] = $analytic;
        }
        
        DB::table('af_test_table13')->insert($analytics);
    }

    private function seedSettings(int $count): void
    {
        $this->command->info("⚙️ Seeding {$count} settings...");
        
        $morphTypes = [
            'ArtflowStudio\Table\Models\TestTable1', // Users
            'ArtflowStudio\Table\Models\TestTable2', // Companies
            'ArtflowStudio\Table\Models\TestTable7', // Projects
        ];
        
        $settings = [];
        for ($i = 0; $i < $count; $i++) {
            $configurableType = $this->faker->randomElement($morphTypes);
            $configurableIds = match($configurableType) {
                'ArtflowStudio\Table\Models\TestTable1' => $this->userIds,
                'ArtflowStudio\Table\Models\TestTable2' => $this->companyIds,
                default => $this->projectIds
            };
            
            $key = $this->faker->randomElement(['theme', 'language', 'timezone', 'email_notifications', 'api_key', 'max_users', 'storage_limit']);
            $type = $this->faker->randomElement(['string', 'integer', 'float', 'boolean', 'json', 'array']);
            
            $setting = [
                'key' => $key,
                'configurable_type' => $configurableType,
                'configurable_id' => $this->faker->randomElement($configurableIds),
                'type' => $type,
                'value' => $this->generateSettingValue($key, $type),
                'json_value' => $type === 'json' ? json_encode(['nested' => ['key' => $this->faker->word()]]) : null,
                'description' => $this->faker->sentence(),
                'validation_rules' => json_encode(['required', 'string', 'max:255']),
                'options' => $key === 'theme' ? json_encode(['light', 'dark', 'auto']) : null,
                'is_public' => $this->faker->boolean(30),
                'is_required' => $this->faker->boolean(20),
                'group' => $this->faker->randomElement(['general', 'security', 'appearance', 'notifications']),
                'sort_order' => $this->faker->numberBetween(1, 100),
                'updated_by' => $this->faker->randomElement($this->userIds),
                'created_at' => $now = $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $settings[] = $setting;
        }
        
        DB::table('af_test_table14')->insert($settings);
    }

    private function generateSettingValue(string $key, string $type): string
    {
        return match([$key, $type]) {
            ['theme', 'string'] => $this->faker->randomElement(['light', 'dark', 'auto']),
            ['language', 'string'] => $this->faker->randomElement(['en', 'es', 'fr', 'de']),
            ['timezone', 'string'] => $this->faker->timezone(),
            ['email_notifications', 'boolean'] => $this->faker->boolean() ? '1' : '0',
            ['max_users', 'integer'] => (string) $this->faker->numberBetween(10, 1000),
            ['storage_limit', 'float'] => (string) $this->faker->randomFloat(2, 1.0, 100.0),
            default => $this->faker->word()
        };
    }

    private function seedLogs(int $count): void
    {
        $this->command->info("📝 Seeding {$count} log entries...");
        
        $morphTypes = [
            'ArtflowStudio\Table\Models\TestTable1', // Users
            'ArtflowStudio\Table\Models\TestTable8', // Tasks
            'ArtflowStudio\Table\Models\TestTable4', // Orders
        ];
        
        $logs = [];
        for ($i = 0; $i < $count; $i++) {
            $loggableType = $this->faker->randomElement($morphTypes);
            $loggableIds = match($loggableType) {
                'ArtflowStudio\Table\Models\TestTable1' => $this->userIds,
                'ArtflowStudio\Table\Models\TestTable8' => $this->taskIds,
                default => $this->orderIds
            };
            
            $log = [
                'action' => $this->faker->randomElement(['create', 'update', 'delete', 'login', 'logout', 'view', 'export']),
                'loggable_type' => $loggableType,
                'loggable_id' => $this->faker->randomElement($loggableIds),
                'user_id' => $this->faker->optional(0.9)->randomElement($this->userIds),
                'level' => $this->faker->randomElement(['debug', 'info', 'warning', 'error', 'critical']),
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
                'properties' => json_encode([
                    'old' => ['status' => $this->faker->word()],
                    'new' => ['status' => $this->faker->word()]
                ]),
                'context' => json_encode([
                    'request_id' => $this->faker->uuid(),
                    'session_id' => $this->faker->uuid(),
                    'route' => $this->faker->slug()
                ]),
                'session_id' => $this->faker->uuid(),
                'request_id' => $this->faker->uuid(),
                'occurred_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
                'created_at' => $now = $this->faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $logs[] = $log;
        }
        
        // Insert in batches to handle large volume
        $chunks = array_chunk($logs, 1000);
        foreach ($chunks as $chunk) {
            DB::table('af_test_table15')->insert($chunk);
        }
    }

    private function seedTags(int $count): void
    {
        $this->command->info("🏷️ Seeding {$count} tags...");
        
        $tags = [];
        for ($i = 0; $i < $count; $i++) {
            $name = $this->faker->unique()->word();
            $tag = [
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'color' => $this->faker->hexColor(),
                'description' => $this->faker->optional(0.7)->sentence(),
                'metadata' => json_encode([
                    'icon' => $this->faker->randomElement(['tag', 'star', 'heart', 'flag']),
                    'category' => $this->faker->randomElement(['general', 'priority', 'status', 'type']),
                    'usage_count' => $this->faker->numberBetween(0, 100)
                ]),
                'usage_count' => $this->faker->numberBetween(0, 100),
                'is_system' => $this->faker->boolean(20),
                'created_by' => $this->faker->randomElement($this->userIds),
                'created_at' => $now = $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $tags[] = $tag;
        }
        
        DB::table('af_test_table16')->insert($tags);
        $this->tagIds = DB::table('af_test_table16')->pluck('id')->toArray();
    }

    private function seedTaggables(int $count): void
    {
        $this->command->info("🔗 Seeding {$count} tag relationships...");
        
        $morphTypes = [
            'ArtflowStudio\Table\Models\TestTable8', // Tasks
            'ArtflowStudio\Table\Models\TestTable7', // Projects  
            'ArtflowStudio\Table\Models\TestTable3', // Products
        ];
        
        $taggables = [];
        for ($i = 0; $i < $count; $i++) {
            $taggableType = $this->faker->randomElement($morphTypes);
            $taggableIds = match($taggableType) {
                'ArtflowStudio\Table\Models\TestTable8' => $this->taskIds,
                'ArtflowStudio\Table\Models\TestTable7' => $this->projectIds,
                default => $this->productIds
            };
            
            $taggable = [
                'tag_id' => $this->faker->randomElement($this->tagIds),
                'taggable_type' => $taggableType,
                'taggable_id' => $this->faker->randomElement($taggableIds),
                'tagged_by' => $this->faker->randomElement($this->userIds),
                'metadata' => json_encode([
                    'confidence' => $this->faker->randomFloat(2, 0.5, 1.0),
                    'auto_tagged' => $this->faker->boolean(30)
                ]),
                'created_at' => $now = $this->faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $taggables[] = $taggable;
        }
        
        DB::table('af_test_table17')->insert($taggables);
    }

    private function seedPermissions(int $count): void
    {
        $this->command->info("🔐 Seeding {$count} permissions...");
        
        $permissions = [];
        $resources = ['users', 'projects', 'tasks', 'orders', 'reports', 'settings'];
        $actions = ['create', 'read', 'update', 'delete', 'manage'];
        
        for ($i = 0; $i < $count; $i++) {
            $resource = $this->faker->randomElement($resources);
            $action = $this->faker->randomElement($actions);
            
            $permission = [
                'name' => "{$action}_{$resource}",
                'guard_name' => 'web',
                'resource' => $resource,
                'action' => $action,
                'description' => "Can {$action} {$resource}",
                'conditions' => json_encode([
                    'field_conditions' => [
                        'department_id' => 'same_department',
                        'company_id' => 'same_company'
                    ],
                    'role_conditions' => ['admin', 'manager']
                ]),
                'metadata' => json_encode([
                    'category' => $this->faker->randomElement(['basic', 'advanced', 'admin']),
                    'risk_level' => $this->faker->randomElement(['low', 'medium', 'high'])
                ]),
                'is_system' => $this->faker->boolean(30),
                'created_at' => $now = $this->faker->dateTimeBetween('-2 years', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $permissions[] = $permission;
        }
        
        DB::table('af_test_table18')->insert($permissions);
        $this->permissionIds = DB::table('af_test_table18')->pluck('id')->toArray();
    }

    private function seedUserPermissions(int $count): void
    {
        $this->command->info("👤 Seeding {$count} user permissions...");
        
        $morphTypes = [
            'ArtflowStudio\Table\Models\TestTable2', // Companies
            'ArtflowStudio\Table\Models\TestTable7', // Projects
            'ArtflowStudio\Table\Models\TestTable6', // Departments
        ];
        
        $userPermissions = [];
        for ($i = 0; $i < $count; $i++) {
            $permissionableType = $this->faker->randomElement($morphTypes);
            $permissionableIds = match($permissionableType) {
                'ArtflowStudio\Table\Models\TestTable2' => $this->companyIds,
                'ArtflowStudio\Table\Models\TestTable7' => $this->projectIds,
                default => $this->departmentIds
            };
            
            $grantedAt = $this->faker->dateTimeBetween('-1 year', 'now');
            
            $userPermission = [
                'user_id' => $this->faker->randomElement($this->userIds),
                'permission_id' => $this->faker->randomElement($this->permissionIds),
                'permissionable_type' => $permissionableType,
                'permissionable_id' => $this->faker->randomElement($permissionableIds),
                'conditions' => json_encode([
                    'time_based' => [
                        'start_time' => '09:00',
                        'end_time' => '17:00'
                    ],
                    'location_based' => ['office', 'remote']
                ]),
                'restrictions' => json_encode([
                    'max_records' => $this->faker->numberBetween(10, 1000),
                    'allowed_actions' => $this->faker->randomElements(['view', 'edit', 'delete'], $this->faker->numberBetween(1, 3))
                ]),
                'granted_at' => $grantedAt,
                'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('now', '+2 years'),
                'granted_by' => $this->faker->randomElement($this->userIds),
                'is_active' => $this->faker->boolean(90),
                'created_at' => $now = $grantedAt,
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $userPermissions[] = $userPermission;
        }
        
        DB::table('af_test_table19')->insert($userPermissions);
    }

    private function seedReports(int $count): void
    {
        $this->command->info("📊 Seeding {$count} reports...");
        
        $reports = [];
        for ($i = 0; $i < $count; $i++) {
            $name = $this->faker->catchPhrase() . ' Report';
            $executionCount = $this->faker->numberBetween(0, 100);
            
            $report = [
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'company_id' => $this->faker->randomElement($this->companyIds),
                'created_by' => $this->faker->randomElement($this->userIds),
                'type' => $this->faker->randomElement(['dashboard', 'chart', 'table', 'pivot', 'export']),
                'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
                'description' => $this->faker->paragraph(),
                'configuration' => json_encode([
                    'filters' => [
                        'date_range' => ['start' => '2024-01-01', 'end' => '2024-12-31'],
                        'status' => ['active', 'pending']
                    ],
                    'grouping' => ['department', 'date'],
                    'aggregations' => ['sum', 'count', 'avg']
                ]),
                'data_sources' => json_encode([
                    'tables' => ['af_test_table1', 'af_test_table7', 'af_test_table8'],
                    'joins' => [
                        ['table' => 'af_test_table7', 'on' => 'manager_id'],
                        ['table' => 'af_test_table8', 'on' => 'project_id']
                    ],
                    'filters' => ['status = active']
                ]),
                'visualizations' => json_encode([
                    'charts' => [
                        ['type' => 'bar', 'data' => 'completion_percentage'],
                        ['type' => 'pie', 'data' => 'status_distribution']
                    ],
                    'tables' => [
                        ['columns' => ['name', 'status', 'completion_percentage']]
                    ]
                ]),
                'parameters' => json_encode([
                    'user_selectable' => [
                        'date_range' => ['type' => 'daterange', 'required' => true],
                        'department' => ['type' => 'select', 'options' => 'departments']
                    ]
                ]),
                'schedule' => json_encode($this->faker->optional(0.3)->randomElement([
                    ['frequency' => 'daily', 'time' => '08:00'],
                    ['frequency' => 'weekly', 'day' => 'monday', 'time' => '09:00'],
                    ['frequency' => 'monthly', 'day' => 1, 'time' => '10:00']
                ])),
                'recipients' => json_encode($this->faker->optional(0.5)->randomElements($this->userIds, $this->faker->numberBetween(1, 5))),
                'execution_count' => $executionCount,
                'last_executed_at' => $executionCount > 0 ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
                'avg_execution_time' => $executionCount > 0 ? $this->faker->randomFloat(3, 0.1, 30.0) : 0.000,
                'is_public' => $this->faker->boolean(20),
                'created_at' => $now = $this->faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $this->faker->dateTimeBetween($now, 'now')
            ];
            $reports[] = $report;
        }
        
        DB::table('af_test_table20')->insert($reports);
    }
}
