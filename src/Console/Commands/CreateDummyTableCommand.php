<?php

namespace ArtflowStudio\Table\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CreateDummyTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'aftable:create-dummy-table 
                            {name=af_test_table : The name of the table to create}
                            {--records=10000 : Number of records to generate}
                            {--force : Force creation even if table exists}';

    /**
     * The console command description.
     */
    protected $description = 'Create a dummy test table with various column types and populate it with test data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tableName = $this->argument('name');
        $recordCount = (int) $this->option('records');
        $force = $this->option('force');

        $this->info("Creating dummy table: {$tableName}");

        // Check if table exists
        if (Schema::hasTable($tableName)) {
            if (!$force) {
                if (!$this->confirm("Table {$tableName} already exists. Do you want to drop and recreate it?")) {
                    $this->info('Operation cancelled.');
                    return 0;
                }
            }
            
            $this->info("Dropping existing table: {$tableName}");
            Schema::dropIfExists($tableName);
        }

        // Create the table
        $this->createTable($tableName);
        
        // Create the model
        $this->createModel($tableName);

        // Populate with data
        $this->populateTable($tableName, $recordCount);

        $this->info("✅ Successfully created table {$tableName} with {$recordCount} records");
        
        // Show usage example
        $this->showUsageExample($tableName);

        return 0;
    }

    /**
     * Create the test table with various column types
     */
    protected function createTable(string $tableName)
    {
        $this->info("Creating table structure...");

        Schema::create($tableName, function (Blueprint $table) {
            // Primary key
            $table->id();

            // String columns
            $table->string('name')->index();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('company')->nullable()->index();
            
            // Text columns
            $table->text('description')->nullable();
            $table->longText('notes')->nullable();

            // Integer columns
            $table->integer('age')->nullable();
            $table->integer('score')->default(0);
            $table->bigInteger('revenue')->default(0);
            $table->unsignedInteger('views')->default(0);

            // Decimal columns
            $table->decimal('salary', 10, 2)->nullable();
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->float('percentage')->default(0.0);

            // Boolean columns
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_premium')->default(false);

            // Date columns
            $table->date('birth_date')->nullable();
            $table->date('hire_date')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            // JSON columns
            $table->json('preferences')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();

            // Enum-like columns
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->default('active');
            $table->enum('role', ['admin', 'user', 'manager', 'guest'])->default('user');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            // Foreign key relationships
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });

        // Create related tables for testing relationships
        $this->createRelatedTables();
    }

    /**
     * Create related tables for testing relationships
     */
    protected function createRelatedTables()
    {
        // Categories table
        if (!Schema::hasTable('af_test_categories')) {
            Schema::create('af_test_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->string('color')->default('#000000');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Departments table
        if (!Schema::hasTable('af_test_departments')) {
            Schema::create('af_test_departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 10);
                $table->text('description')->nullable();
                $table->decimal('budget', 12, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Create Eloquent model for the test table
     */
    protected function createModel(string $tableName)
    {
        $modelName = Str::studly(Str::singular($tableName));
        $modelPath = app_path("Models/{$modelName}.php");

        if (file_exists($modelPath)) {
            $this->info("Model {$modelName} already exists, skipping...");
            return;
        }

        $this->info("Creating model: {$modelName}");

        $modelContent = $this->generateModelContent($modelName, $tableName);
        
        if (!is_dir(dirname($modelPath))) {
            mkdir(dirname($modelPath), 0755, true);
        }
        
        file_put_contents($modelPath, $modelContent);
    }

    /**
     * Generate model content
     */
    protected function generateModelContent(string $modelName, string $tableName): string
    {
        return "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$modelName} extends Model
{
    use HasFactory, SoftDeletes;

    protected \$table = '{$tableName}';

    protected \$fillable = [
        'name', 'email', 'phone', 'website', 'company', 'description', 'notes',
        'age', 'score', 'revenue', 'views', 'salary', 'rating', 'percentage',
        'is_active', 'is_verified', 'is_premium', 'birth_date', 'hire_date',
        'last_login_at', 'verified_at', 'preferences', 'metadata', 'tags',
        'status', 'role', 'priority', 'category_id', 'department_id', 'manager_id'
    ];

    protected \$casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'is_premium' => 'boolean',
        'birth_date' => 'date',
        'hire_date' => 'date',
        'last_login_at' => 'datetime',
        'verified_at' => 'datetime',
        'preferences' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'salary' => 'decimal:2',
        'rating' => 'decimal:2',
        'revenue' => 'integer',
        'views' => 'integer',
    ];

    // Relationships
    public function category()
    {
        return \$this->belongsTo(AfTestCategory::class, 'category_id');
    }

    public function department()
    {
        return \$this->belongsTo(AfTestDepartment::class, 'department_id');
    }

    public function manager()
    {
        return \$this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates()
    {
        return \$this->hasMany(self::class, 'manager_id');
    }

    // Scopes
    public function scopeActive(\$query)
    {
        return \$query->where('is_active', true);
    }

    public function scopeVerified(\$query)
    {
        return \$query->where('is_verified', true);
    }

    public function scopeByStatus(\$query, \$status)
    {
        return \$query->where('status', \$status);
    }

    public function scopeByRole(\$query, \$role)
    {
        return \$query->where('role', \$role);
    }
}
";
    }

    /**
     * Populate the table with test data
     */
    protected function populateTable(string $tableName, int $recordCount)
    {
        $this->info("Populating table with {$recordCount} records...");

        // First, populate related tables
        $this->populateRelatedTables();

        // Get IDs from related tables for foreign keys
        $categoryIds = DB::table('af_test_categories')->pluck('id')->toArray();
        $departmentIds = DB::table('af_test_departments')->pluck('id')->toArray();

        $chunkSize = 1000;
        $chunks = ceil($recordCount / $chunkSize);

        $progressBar = $this->output->createProgressBar($chunks);

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $currentChunkSize = min($chunkSize, $recordCount - ($chunk * $chunkSize));
            $records = [];

            for ($i = 0; $i < $currentChunkSize; $i++) {
                $recordIndex = ($chunk * $chunkSize) + $i + 1;
                $records[] = $this->generateRecord($recordIndex, $categoryIds, $departmentIds, $recordCount);
            }

            DB::table($tableName)->insert($records);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Populate related tables
     */
    protected function populateRelatedTables()
    {
        // Categories
        if (DB::table('af_test_categories')->count() === 0) {
            $categories = [
                ['name' => 'Technology', 'description' => 'Technology related items', 'color' => '#3498db'],
                ['name' => 'Marketing', 'description' => 'Marketing and advertising', 'color' => '#e74c3c'],
                ['name' => 'Sales', 'description' => 'Sales and revenue', 'color' => '#2ecc71'],
                ['name' => 'Support', 'description' => 'Customer support', 'color' => '#f39c12'],
                ['name' => 'Finance', 'description' => 'Financial operations', 'color' => '#9b59b6'],
                ['name' => 'Operations', 'description' => 'Business operations', 'color' => '#1abc9c'],
                ['name' => 'Research', 'description' => 'Research and development', 'color' => '#34495e'],
                ['name' => 'Quality', 'description' => 'Quality assurance', 'color' => '#e67e22'],
            ];

            foreach ($categories as &$category) {
                $category['created_at'] = now();
                $category['updated_at'] = now();
            }

            DB::table('af_test_categories')->insert($categories);
        }

        // Departments
        if (DB::table('af_test_departments')->count() === 0) {
            $departments = [
                ['name' => 'Engineering', 'code' => 'ENG', 'description' => 'Software engineering team', 'budget' => 1500000.00],
                ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Marketing and brand management', 'budget' => 800000.00],
                ['name' => 'Sales', 'code' => 'SAL', 'description' => 'Sales and business development', 'budget' => 1200000.00],
                ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'Human resources management', 'budget' => 600000.00],
                ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Financial operations', 'budget' => 400000.00],
                ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Business operations', 'budget' => 900000.00],
                ['name' => 'Customer Support', 'code' => 'SUP', 'description' => 'Customer service and support', 'budget' => 500000.00],
            ];

            foreach ($departments as &$department) {
                $department['created_at'] = now();
                $department['updated_at'] = now();
            }

            DB::table('af_test_departments')->insert($departments);
        }
    }

    /**
     * Generate a single test record
     */
    protected function generateRecord(int $index, array $categoryIds, array $departmentIds, int $totalRecords): array
    {
        $faker = \Faker\Factory::create();
        
        return [
            'name' => $faker->name(),
            'email' => $faker->unique()->email(),
            'phone' => $faker->phoneNumber(),
            'website' => $faker->url(),
            'company' => $faker->company(),
            'description' => $faker->paragraph(),
            'notes' => $faker->text(500),
            'age' => $faker->numberBetween(18, 65),
            'score' => $faker->numberBetween(0, 100),
            'revenue' => $faker->numberBetween(10000, 1000000),
            'views' => $faker->numberBetween(0, 100000),
            'salary' => $faker->randomFloat(2, 30000, 150000),
            'rating' => $faker->randomFloat(2, 1, 5),
            'percentage' => $faker->randomFloat(2, 0, 100),
            'is_active' => $faker->boolean(80), // 80% active
            'is_verified' => $faker->boolean(60), // 60% verified
            'is_premium' => $faker->boolean(20), // 20% premium
            'birth_date' => $faker->date('Y-m-d', '-18 years'),
            'hire_date' => $faker->date('Y-m-d', '-5 years'),
            'last_login_at' => $faker->dateTimeBetween('-30 days', 'now'),
            'verified_at' => $faker->boolean(60) ? $faker->dateTimeBetween('-1 year', 'now') : null,
            'preferences' => json_encode([
                'theme' => $faker->randomElement(['light', 'dark', 'auto']),
                'notifications' => $faker->boolean(),
                'language' => $faker->randomElement(['en', 'es', 'fr', 'de', 'it']),
                'timezone' => $faker->timezone(),
            ]),
            'metadata' => json_encode([
                'source' => $faker->randomElement(['web', 'mobile', 'api', 'import']),
                'campaign' => $faker->randomElement(['summer2023', 'holiday2023', 'spring2024', null]),
                'referrer' => $faker->randomElement(['google', 'facebook', 'twitter', 'direct', null]),
                'ip_address' => $faker->ipv4(),
            ]),
            'tags' => json_encode($faker->randomElements(['important', 'vip', 'new', 'returning', 'high-value', 'lead', 'customer'], $faker->numberBetween(0, 3))),
            'status' => $faker->randomElement(['active', 'inactive', 'pending', 'suspended']),
            'role' => $faker->randomElement(['admin', 'user', 'manager', 'guest']),
            'priority' => $faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'category_id' => $faker->randomElement($categoryIds),
            'department_id' => $faker->randomElement($departmentIds),
            'manager_id' => $index > 100 ? $faker->optional(0.3)->numberBetween(1, min(100, $index - 1)) : null, // 30% have managers
            'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => $faker->dateTimeBetween('-6 months', 'now'),
            'deleted_at' => $faker->optional(0.05)->dateTimeBetween('-3 months', 'now'), // 5% soft deleted
        ];
    }

    /**
     * Show usage example
     */
    protected function showUsageExample(string $tableName)
    {
        $modelName = Str::studly(Str::singular($tableName));
        
        $this->newLine();
        $this->info('🎉 Dummy table created successfully!');
        $this->newLine();
        $this->info('📋 Table Details:');
        $this->line("   Table: {$tableName}");
        $this->line("   Model: App\\Models\\{$modelName}");
        $this->line("   Records: " . DB::table($tableName)->count());
        $this->newLine();
        
        $this->info('🔧 Usage Example:');
        $this->line('');
        $this->line('// In your Livewire component:');
        $this->line('public function mount()');
        $this->line('{');
        $this->line("    \$this->model = \\App\\Models\\{$modelName}::class;");
        $this->line('    $this->columns = [');
        $this->line("        'id' => ['label' => 'ID', 'sortable' => true],");
        $this->line("        'name' => ['label' => 'Name', 'searchable' => true, 'sortable' => true],");
        $this->line("        'email' => ['label' => 'Email', 'searchable' => true],");
        $this->line("        'company' => ['label' => 'Company', 'searchable' => true],");
        $this->line("        'category_name' => ['label' => 'Category', 'relation' => 'category:name'],");
        $this->line("        'department_name' => ['label' => 'Department', 'relation' => 'department:name'],");
        $this->line("        'status' => ['label' => 'Status', 'sortable' => true],");
        $this->line("        'is_active' => ['label' => 'Active', 'sortable' => true],");
        $this->line("        'created_at' => ['label' => 'Created', 'sortable' => true],");
        $this->line('    ];');
        $this->line('}');
        $this->newLine();
        
        $this->info('🧪 Test Commands:');
        $this->line('php artisan aftable:test-traits');
        $this->line('php artisan aftable:benchmark-performance');
        $this->newLine();
        
        $this->info('🗑️  Cleanup:');
        $this->line("To remove the test data, run:");
        $this->line("php artisan aftable:cleanup-dummy-tables");
    }
}
