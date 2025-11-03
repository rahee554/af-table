<?php

namespace ArtflowStudio\Table\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AftableTestSeeder extends Seeder
{
    private $faker;
    private $companies = [];
    private $departments = [];
    private $employees = [];
    private $projects = [];
    private $clients = [];
    private $tasks = [];

    public function run(): void
    {
        $this->faker = Faker::create();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->truncateTables();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        echo "🚀 Starting AF Table Test Data Generation...\n\n";

        $this->seedCompanies(100); // 100 companies
        $this->seedDepartments(500); // 500 departments
        $this->seedEmployees(10000); // 10,000 employees
        $this->seedProjects(2000); // 2,000 projects
        $this->seedTasks(15000); // 15,000 tasks
        $this->seedEmployeeProject(25000); // 25,000 project assignments
        $this->seedClients(3000); // 3,000 clients
        $this->seedInvoices(8000); // 8,000 invoices
        $this->seedTimesheets(50000); // 50,000 timesheet entries
        $this->seedDocuments(5000); // 5,000 documents

        echo "\n✅ Test data generation complete!\n";
        echo "📊 Total records: " . (100 + 500 + 10000 + 2000 + 15000 + 25000 + 3000 + 8000 + 50000 + 5000) . "\n";
    }

    private function truncateTables(): void
    {
        DB::table('test_documents')->truncate();
        DB::table('test_timesheets')->truncate();
        DB::table('test_invoices')->truncate();
        DB::table('test_clients')->truncate();
        DB::table('test_employee_project')->truncate();
        DB::table('test_tasks')->truncate();
        DB::table('test_projects')->truncate();
        DB::table('test_employees')->truncate();
        DB::table('test_departments')->truncate();
        DB::table('test_companies')->truncate();
    }

    private function seedCompanies(int $count): void
    {
        echo "📦 Seeding {$count} companies...\n";
        
        $statuses = ['active', 'inactive', 'pending'];
        $countries = ['USA', 'UK', 'Canada', 'Germany', 'France', 'Japan', 'Australia', 'India'];
        
        $data = [];
        for ($i = 1; $i <= $count; $i++) {
            $data[] = [
                'name' => $this->faker->company(),
                'email' => $this->faker->unique()->companyEmail(),
                'phone' => $this->faker->phoneNumber(),
                'address' => $this->faker->address(),
                'country' => $this->faker->randomElement($countries),
                'city' => $this->faker->city(),
                'employee_count' => $this->faker->numberBetween(10, 1000),
                'revenue' => $this->faker->randomFloat(2, 100000, 10000000),
                'founded_date' => $this->faker->date('Y-m-d', '-10 years'),
                'metadata' => json_encode([
                    'industry' => $this->faker->randomElement(['Technology', 'Finance', 'Healthcare', 'Manufacturing', 'Retail']),
                    'website' => $this->faker->url(),
                    'employees_range' => $this->faker->randomElement(['1-10', '11-50', '51-200', '201-500', '500+']),
                    'year_founded' => $this->faker->year('-30 years'),
                ]),
                'status' => $this->faker->randomElement($statuses),
                'is_verified' => $this->faker->boolean(80),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('test_companies')->insert($data);
        $this->companies = DB::table('test_companies')->pluck('id')->toArray();
        echo "   ✓ Created {$count} companies\n";
    }

    private function seedDepartments(int $count): void
    {
        echo "📦 Seeding {$count} departments...\n";
        
        $deptNames = ['Engineering', 'Sales', 'Marketing', 'HR', 'Finance', 'Operations', 'Support', 'Product', 'Design', 'Legal'];
        
        $data = [];
        for ($i = 1; $i <= $count; $i++) {
            $data[] = [
                'company_id' => $this->faker->randomElement($this->companies),
                'name' => $this->faker->randomElement($deptNames) . ' - ' . $this->faker->word(),
                'code' => strtoupper($this->faker->unique()->lexify('DEPT???')),
                'description' => $this->faker->sentence(),
                'budget' => $this->faker->numberBetween(50000, 500000),
                'manager_id' => null, // Will update after employees
                'settings' => json_encode([
                    'office_location' => $this->faker->city(),
                    'work_hours' => $this->faker->randomElement(['9-5', '8-4', '10-6', 'flexible']),
                    'remote_allowed' => $this->faker->boolean(70),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('test_departments')->insert($data);
        $this->departments = DB::table('test_departments')->pluck('id')->toArray();
        echo "   ✓ Created {$count} departments\n";
    }

    private function seedEmployees(int $count): void
    {
        echo "📦 Seeding {$count} employees (this may take a moment)...\n";
        
        $positions = ['Software Engineer', 'Senior Developer', 'Product Manager', 'Designer', 'Analyst', 'Manager', 'Director', 'VP', 'Consultant', 'Specialist'];
        $types = ['full-time', 'part-time', 'contract', 'intern'];
        $statuses = ['active', 'inactive', 'on_leave'];
        
        $chunkSize = 1000;
        $globalIndex = 1; // Track employee number across chunks
        for ($chunk = 0; $chunk < ceil($count / $chunkSize); $chunk++) {
            $data = [];
            $currentChunkSize = min($chunkSize, $count - ($chunk * $chunkSize));
            
            for ($i = 1; $i <= $currentChunkSize; $i++) {
                $hireDate = $this->faker->dateTimeBetween('-5 years', 'now');
                $data[] = [
                    'company_id' => $this->faker->randomElement($this->companies),
                    'department_id' => $this->faker->randomElement($this->departments),
                    'employee_code' => 'EMP' . str_pad($globalIndex, 5, '0', STR_PAD_LEFT), // EMP00001, EMP00002, etc.
                    'first_name' => $this->faker->firstName(),
                    'last_name' => $this->faker->lastName(),
                    'email' => $this->faker->unique()->email(),
                    'phone' => $this->faker->phoneNumber(),
                    'birth_date' => $this->faker->date('Y-m-d', '-50 years'),
                    'hire_date' => $hireDate->format('Y-m-d'),
                    'salary' => $this->faker->randomFloat(2, 30000, 200000),
                    'employment_type' => $this->faker->randomElement($types),
                    'status' => $this->faker->randomElement($statuses), // Add status column
                    'skills' => json_encode($this->faker->randomElements(['PHP', 'JavaScript', 'Python', 'Java', 'SQL', 'React', 'Vue', 'Laravel', 'Node.js', 'AWS'], $this->faker->numberBetween(2, 6))),
                    'preferences' => json_encode([
                        'theme' => $this->faker->randomElement(['light', 'dark', 'auto']),
                        'language' => $this->faker->randomElement(['en', 'es', 'fr', 'de']),
                        'notifications' => $this->faker->boolean(80),
                        'timezone' => $this->faker->timezone(),
                    ]),
                    'position' => $this->faker->randomElement($positions),
                    'years_experience' => $this->faker->numberBetween(0, 20),
                    'is_active' => $this->faker->boolean(90),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $globalIndex++; // Increment for next employee
            }
            
            DB::table('test_employees')->insert($data);
            echo "   ✓ Progress: " . (($chunk + 1) * $chunkSize) . " / {$count}\n";
        }
        
        $this->employees = DB::table('test_employees')->pluck('id')->toArray();
        
        // Update some departments with managers
        foreach ($this->departments as $deptId) {
            if ($this->faker->boolean(70)) {
                DB::table('test_departments')
                    ->where('id', $deptId)
                    ->update(['manager_id' => $this->faker->randomElement($this->employees)]);
            }
        }
        
        echo "   ✓ Created {$count} employees\n";
    }

    private function seedProjects(int $count): void
    {
        echo "📦 Seeding {$count} projects...\n";
        
        $priorities = ['low', 'medium', 'high', 'critical'];
        $statuses = ['planning', 'in-progress', 'on-hold', 'completed', 'cancelled'];
        
        $chunkSize = 500;
        for ($chunk = 0; $chunk < ceil($count / $chunkSize); $chunk++) {
            $data = [];
            $currentChunkSize = min($chunkSize, $count - ($chunk * $chunkSize));
            
            for ($i = 1; $i <= $currentChunkSize; $i++) {
                $startDate = $this->faker->dateTimeBetween('-2 years', '+3 months');
                $endDate = $this->faker->dateTimeBetween($startDate, '+2 years');
                
                $data[] = [
                    'company_id' => $this->faker->randomElement($this->companies),
                    'name' => $this->faker->catchPhrase(),
                    'code' => strtoupper($this->faker->unique()->bothify('PRJ-####')),
                    'description' => $this->faker->paragraph(),
                    'budget' => $this->faker->randomFloat(2, 10000, 1000000),
                    'spent' => $this->faker->randomFloat(2, 5000, 800000),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'priority' => $this->faker->randomElement($priorities),
                    'status' => $this->faker->randomElement($statuses),
                    'progress' => $this->faker->numberBetween(0, 100),
                    'milestones' => json_encode([
                        ['name' => 'Kickoff', 'date' => $this->faker->date(), 'completed' => true],
                        ['name' => 'Phase 1', 'date' => $this->faker->date(), 'completed' => $this->faker->boolean()],
                        ['name' => 'Phase 2', 'date' => $this->faker->date(), 'completed' => $this->faker->boolean()],
                        ['name' => 'Launch', 'date' => $this->faker->date(), 'completed' => false],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            DB::table('test_projects')->insert($data);
        }
        
        $this->projects = DB::table('test_projects')->pluck('id')->toArray();
        echo "   ✓ Created {$count} projects\n";
    }

    private function seedTasks(int $count): void
    {
        echo "📦 Seeding {$count} tasks...\n";
        
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $statuses = ['todo', 'in-progress', 'review', 'done', 'cancelled'];
        
        $chunkSize = 1000;
        for ($chunk = 0; $chunk < ceil($count / $chunkSize); $chunk++) {
            $data = [];
            $currentChunkSize = min($chunkSize, $count - ($chunk * $chunkSize));
            
            for ($i = 1; $i <= $currentChunkSize; $i++) {
                $data[] = [
                    'project_id' => $this->faker->randomElement($this->projects),
                    'assigned_to' => $this->faker->randomElement($this->employees),
                    'created_by' => $this->faker->randomElement($this->employees),
                    'title' => $this->faker->sentence(),
                    'description' => $this->faker->paragraph(),
                    'priority' => $this->faker->randomElement($priorities),
                    'status' => $this->faker->randomElement($statuses),
                    'estimated_hours' => $this->faker->numberBetween(1, 80),
                    'actual_hours' => $this->faker->numberBetween(1, 100),
                    'due_date' => $this->faker->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
                    'completed_at' => $this->faker->boolean(40) ? $this->faker->date() : null,
                    'tags' => json_encode($this->faker->randomElements(['bug', 'feature', 'enhancement', 'documentation', 'testing', 'refactor'], $this->faker->numberBetween(1, 3))),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            DB::table('test_tasks')->insert($data);
        }
        
        $this->tasks = DB::table('test_tasks')->pluck('id')->toArray();
        echo "   ✓ Created {$count} tasks\n";
    }

    private function seedEmployeeProject(int $count): void
    {
        echo "📦 Seeding {$count} employee-project assignments...\n";
        
        $roles = ['Developer', 'Lead', 'Manager', 'QA', 'Designer', 'Analyst', 'Consultant'];
        
        // Generate unique combinations
        $combinations = [];
        $attempts = 0;
        $maxAttempts = $count * 3; // Allow some buffer for finding unique combinations
        
        while (count($combinations) < $count && $attempts < $maxAttempts) {
            $empId = $this->faker->randomElement($this->employees);
            $projId = $this->faker->randomElement($this->projects);
            $key = "{$empId}-{$projId}";
            
            if (!isset($combinations[$key])) {
                $combinations[$key] = [
                    'employee_id' => $empId,
                    'project_id' => $projId,
                    'role' => $this->faker->randomElement($roles),
                    'hours_allocated' => $this->faker->randomFloat(2, 10, 200),
                    'joined_at' => $this->faker->date(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            $attempts++;
        }
        
        // Insert in chunks
        $chunkSize = 1000;
        $data = array_values($combinations);
        
        foreach (array_chunk($data, $chunkSize) as $chunk) {
            DB::table('test_employee_project')->insert($chunk);
        }
        
        echo "   ✓ Created " . count($combinations) . " employee-project assignments\n";
    }

    private function seedClients(int $count): void
    {
        echo "📦 Seeding {$count} clients...\n";
        
        $industries = ['Technology', 'Finance', 'Healthcare', 'Retail', 'Manufacturing', 'Education', 'Government'];
        $tiers = ['bronze', 'silver', 'gold', 'platinum'];
        
        $chunkSize = 500;
        for ($chunk = 0; $chunk < ceil($count / $chunkSize); $chunk++) {
            $data = [];
            $currentChunkSize = min($chunkSize, $count - ($chunk * $chunkSize));
            
            for ($i = 1; $i <= $currentChunkSize; $i++) {
                $data[] = [
                    'company_id' => $this->faker->randomElement($this->companies),
                    'name' => $this->faker->company(),
                    'email' => $this->faker->unique()->companyEmail(),
                    'phone' => $this->faker->phoneNumber(),
                    'address' => $this->faker->address(),
                    'industry' => $this->faker->randomElement($industries),
                    'tier' => $this->faker->randomElement($tiers),
                    'lifetime_value' => $this->faker->randomFloat(2, 1000, 500000),
                    'first_contract_date' => $this->faker->date(),
                    'contacts' => json_encode([
                        ['name' => $this->faker->name(), 'email' => $this->faker->email(), 'phone' => $this->faker->phoneNumber()],
                        ['name' => $this->faker->name(), 'email' => $this->faker->email(), 'phone' => $this->faker->phoneNumber()],
                    ]),
                    'is_active' => $this->faker->boolean(85),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            DB::table('test_clients')->insert($data);
        }
        
        $this->clients = DB::table('test_clients')->pluck('id')->toArray();
        echo "   ✓ Created {$count} clients\n";
    }

    private function seedInvoices(int $count): void
    {
        echo "📦 Seeding {$count} invoices...\n";
        
        $statuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
        
        $chunkSize = 1000;
        for ($chunk = 0; $chunk < ceil($count / $chunkSize); $chunk++) {
            $data = [];
            $currentChunkSize = min($chunkSize, $count - ($chunk * $chunkSize));
            
            for ($i = 1; $i <= $currentChunkSize; $i++) {
                $amount = $this->faker->randomFloat(2, 1000, 50000);
                $tax = $amount * 0.1;
                $total = $amount + $tax;
                
                $invoiceDate = $this->faker->dateTimeBetween('-1 year', 'now');
                $dueDate = (clone $invoiceDate)->modify('+30 days');
                
                $data[] = [
                    'client_id' => $this->faker->randomElement($this->clients),
                    'project_id' => $this->faker->randomElement($this->projects),
                    'invoice_number' => 'INV-' . str_pad($chunk * $chunkSize + $i, 6, '0', STR_PAD_LEFT),
                    'invoice_date' => $invoiceDate->format('Y-m-d'),
                    'due_date' => $dueDate->format('Y-m-d'),
                    'amount' => $amount,
                    'tax' => $tax,
                    'total' => $total,
                    'status' => $this->faker->randomElement($statuses),
                    'notes' => $this->faker->sentence(),
                    'line_items' => json_encode([
                        ['description' => $this->faker->words(3, true), 'quantity' => $this->faker->numberBetween(1, 10), 'rate' => $this->faker->randomFloat(2, 50, 500)],
                        ['description' => $this->faker->words(3, true), 'quantity' => $this->faker->numberBetween(1, 10), 'rate' => $this->faker->randomFloat(2, 50, 500)],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            DB::table('test_invoices')->insert($data);
        }
        
        echo "   ✓ Created {$count} invoices\n";
    }

    private function seedTimesheets(int $count): void
    {
        echo "📦 Seeding {$count} timesheets (this may take a moment)...\n";
        
        $chunkSize = 2000;
        for ($chunk = 0; $chunk < ceil($count / $chunkSize); $chunk++) {
            $data = [];
            $currentChunkSize = min($chunkSize, $count - ($chunk * $chunkSize));
            
            for ($i = 1; $i <= $currentChunkSize; $i++) {
                $data[] = [
                    'employee_id' => $this->faker->randomElement($this->employees),
                    'task_id' => $this->faker->randomElement($this->tasks),
                    'work_date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                    'start_time' => $this->faker->time('H:i:s'),
                    'end_time' => $this->faker->time('H:i:s'),
                    'hours' => $this->faker->randomFloat(2, 0.5, 12),
                    'description' => $this->faker->sentence(),
                    'is_billable' => $this->faker->boolean(80),
                    'is_approved' => $this->faker->boolean(60),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            DB::table('test_timesheets')->insert($data);
            echo "   ✓ Progress: " . (($chunk + 1) * $chunkSize) . " / {$count}\n";
        }
        
        echo "   ✓ Created {$count} timesheets\n";
    }

    private function seedDocuments(int $count): void
    {
        echo "📦 Seeding {$count} documents...\n";
        
        $documentableTypes = [
            'ArtflowStudio\Table\Tests\Models\TestCompany',
            'ArtflowStudio\Table\Tests\Models\TestProject',
            'ArtflowStudio\Table\Tests\Models\TestEmployee',
        ];
        
        $fileTypes = ['pdf', 'docx', 'xlsx', 'png', 'jpg', 'zip'];
        
        $chunkSize = 1000;
        for ($chunk = 0; $chunk < ceil($count / $chunkSize); $chunk++) {
            $data = [];
            $currentChunkSize = min($chunkSize, $count - ($chunk * $chunkSize));
            
            for ($i = 1; $i <= $currentChunkSize; $i++) {
                $fileType = $this->faker->randomElement($fileTypes);
                $documentableType = $this->faker->randomElement($documentableTypes);
                
                // Get a random ID based on type
                $documentableId = match($documentableType) {
                    'ArtflowStudio\Table\Tests\Models\TestCompany' => $this->faker->randomElement($this->companies),
                    'ArtflowStudio\Table\Tests\Models\TestProject' => $this->faker->randomElement($this->projects),
                    'ArtflowStudio\Table\Tests\Models\TestEmployee' => $this->faker->randomElement($this->employees),
                    default => 1
                };
                
                $data[] = [
                    'documentable_type' => $documentableType,
                    'documentable_id' => $documentableId,
                    'title' => $this->faker->sentence(3),
                    'file_name' => $this->faker->slug() . '.' . $fileType,
                    'file_path' => 'documents/' . $this->faker->slug() . '.' . $fileType,
                    'file_type' => $fileType,
                    'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
                    'description' => $this->faker->paragraph(),
                    'metadata' => json_encode([
                        'uploaded_from' => $this->faker->randomElement(['web', 'mobile', 'api']),
                        'version' => $this->faker->randomFloat(1, 1, 5),
                        'category' => $this->faker->randomElement(['contract', 'report', 'presentation', 'image', 'other']),
                    ]),
                    'uploaded_by' => $this->faker->randomElement($this->employees),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            DB::table('test_documents')->insert($data);
        }
        
        echo "   ✓ Created {$count} documents\n";
    }
}
