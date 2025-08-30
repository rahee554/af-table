<?php

namespace ArtflowStudio\Table\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TestTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing new test data
        DB::table('af_test_tasks')->delete();
        DB::table('af_test_projects')->delete();
        DB::table('af_test_users')->delete();
        DB::table('af_test_departments')->delete();
        
        // Clear existing legacy test data
        DB::table('test_post_tags')->delete();
        DB::table('test_profiles')->delete();
        DB::table('test_comments')->delete();
        DB::table('test_posts')->delete();
        DB::table('test_tags')->delete();
        DB::table('test_users')->delete();
        DB::table('test_categories')->delete();

        // Seed af_test_departments
        $departments = [];
        $deptData = [
            ['name' => 'Information Technology', 'code' => 'IT', 'head' => 'John Smith', 'budget' => 150000, 'employees' => 25, 'location' => 'Building A, Floor 3'],
            ['name' => 'Human Resources', 'code' => 'HR', 'head' => 'Sarah Johnson', 'budget' => 80000, 'employees' => 12, 'location' => 'Building B, Floor 1'],
            ['name' => 'Marketing', 'code' => 'MKT', 'head' => 'Mike Wilson', 'budget' => 120000, 'employees' => 18, 'location' => 'Building A, Floor 2'],
            ['name' => 'Finance', 'code' => 'FIN', 'head' => 'Lisa Chen', 'budget' => 100000, 'employees' => 15, 'location' => 'Building B, Floor 2'],
            ['name' => 'Operations', 'code' => 'OPS', 'head' => 'David Brown', 'budget' => 200000, 'employees' => 30, 'location' => 'Building C, Floor 1'],
            ['name' => 'Research & Development', 'code' => 'RND', 'head' => 'Dr. Emily Davis', 'budget' => 180000, 'employees' => 22, 'location' => 'Building D, Floor 3'],
            ['name' => 'Customer Service', 'code' => 'CS', 'head' => 'Robert Taylor', 'budget' => 90000, 'employees' => 20, 'location' => 'Building A, Floor 1'],
            ['name' => 'Quality Assurance', 'code' => 'QA', 'head' => 'Jennifer Martinez', 'budget' => 70000, 'employees' => 10, 'location' => 'Building D, Floor 2']
        ];

        foreach ($deptData as $index => $dept) {
            $departments[] = [
                'name' => $dept['name'],
                'code' => $dept['code'],
                'description' => "Responsible for {$dept['name']} operations and strategic initiatives",
                'head_of_department' => $dept['head'],
                'budget' => $dept['budget'],
                'employee_count' => $dept['employees'],
                'location' => $dept['location'],
                'phone' => '+1-555-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                'email' => strtolower($dept['code']) . '@company.com',
                'status' => ['active', 'active', 'active', 'active', 'active', 'inactive', 'restructuring', 'active'][array_rand(['active', 'active', 'active', 'active', 'active', 'inactive', 'restructuring', 'active'])],
                'goals' => json_encode([
                    'q1' => 'Achieve ' . rand(80, 100) . '% efficiency target',
                    'q2' => 'Implement new ' . strtolower($dept['name']) . ' system',
                    'q3' => 'Reduce costs by ' . rand(5, 15) . '%',
                    'q4' => 'Increase team satisfaction to ' . rand(85, 95) . '%'
                ]),
                'resources' => json_encode([
                    'software' => ['Office 365', 'Slack', 'Zoom'],
                    'hardware' => ['Laptops', 'Monitors', 'Printers'],
                    'budget_allocation' => [
                        'salaries' => rand(60, 70),
                        'equipment' => rand(15, 25),
                        'training' => rand(5, 10),
                        'misc' => rand(5, 10)
                    ]
                ]),
                'policies' => json_encode([
                    'remote_work' => rand(0, 1) ? 'allowed' : 'restricted',
                    'overtime' => 'approved_required',
                    'training_budget' => rand(1000, 5000),
                    'dress_code' => ['casual', 'business_casual', 'formal'][array_rand(['casual', 'business_casual', 'formal'])]
                ]),
                'established_date' => Carbon::now()->subYears(rand(2, 10))->format('Y-m-d'),
                'notes' => "Department established with focus on {$dept['name']} excellence and innovation",
                'created_at' => Carbon::now()->subDays(rand(30, 365)),
                'updated_at' => Carbon::now()->subDays(rand(1, 30))
            ];
        }
        DB::table('af_test_departments')->insert($departments);
        $departmentIds = DB::table('af_test_departments')->pluck('id')->toArray();

        // Seed af_test_users
        $users = [];
        $positions = ['Developer', 'Manager', 'Analyst', 'Specialist', 'Coordinator', 'Lead', 'Senior Developer', 'Junior Developer', 'Administrator', 'Director'];
        $statuses = ['active', 'inactive', 'on_leave', 'terminated'];
        
        for ($i = 1; $i <= 100; $i++) {
            $hireDate = Carbon::now()->subDays(rand(30, 1800));
            $users[] = [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => "user{$i}@company.com",
                'username' => "user{$i}",
                'phone' => fake()->phoneNumber(),
                'status' => $statuses[array_rand($statuses)],
                'department_id' => $departmentIds[array_rand($departmentIds)],
                'position' => $positions[array_rand($positions)],
                'salary' => rand(35000, 120000),
                'hire_date' => $hireDate->format('Y-m-d'),
                'birth_date' => Carbon::now()->subYears(rand(22, 60))->format('Y-m-d'),
                'address' => fake()->address(),
                'emergency_contact' => fake()->name(),
                'emergency_phone' => fake()->phoneNumber(),
                'is_manager' => rand(0, 10) === 0, // 10% chance of being manager
                'skills' => json_encode(fake()->randomElements(['PHP', 'Laravel', 'Vue.js', 'MySQL', 'Git', 'Docker', 'AWS', 'JavaScript', 'Python', 'React'], rand(2, 5))),
                'certifications' => json_encode(fake()->randomElements(['AWS Certified', 'Laravel Certified', 'PMP', 'Scrum Master', 'Google Analytics'], rand(0, 3))),
                'preferences' => json_encode([
                    'communication' => ['email', 'slack', 'phone'][array_rand(['email', 'slack', 'phone'])],
                    'work_hours' => ['9-5', '8-4', '10-6'][array_rand(['9-5', '8-4', '10-6'])],
                    'remote_days' => rand(0, 3)
                ]),
                'notes' => fake()->sentence(10),
                'created_at' => $hireDate,
                'updated_at' => Carbon::now()->subDays(rand(1, 30))
            ];
        }
        DB::table('af_test_users')->insert($users);
        $userIds = DB::table('af_test_users')->pluck('id')->toArray();
        $managerIds = DB::table('af_test_users')->where('is_manager', true)->pluck('id')->toArray();

        // Seed af_test_projects
        $projects = [];
        $projectStatuses = ['planning', 'active', 'on_hold', 'completed', 'cancelled'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        
        for ($i = 1; $i <= 50; $i++) {
            $startDate = Carbon::now()->subDays(rand(30, 365));
            $endDate = $startDate->copy()->addDays(rand(30, 180));
            $deadline = $startDate->copy()->addDays(rand(45, 200));
            
            $projects[] = [
                'name' => fake()->catchPhrase() . " Project {$i}",
                'description' => fake()->paragraphs(2, true),
                'code' => 'PRJ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'status' => $projectStatuses[array_rand($projectStatuses)],
                'priority' => $priorities[array_rand($priorities)],
                'department_id' => $departmentIds[array_rand($departmentIds)],
                'manager_id' => !empty($managerIds) ? $managerIds[array_rand($managerIds)] : $userIds[array_rand($userIds)],
                'budget' => rand(10000, 500000),
                'spent_amount' => rand(1000, 50000),
                'progress_percentage' => rand(0, 100),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'deadline' => $deadline->format('Y-m-d'),
                'requirements' => json_encode([
                    'functional' => fake()->sentences(3),
                    'technical' => fake()->sentences(2),
                    'performance' => fake()->sentences(1)
                ]),
                'technologies' => json_encode(fake()->randomElements(['Laravel', 'Vue.js', 'MySQL', 'Redis', 'Docker', 'AWS', 'JavaScript', 'PHP', 'Python'], rand(3, 6))),
                'deliverables' => json_encode([
                    'documentation' => ['status' => 'in_progress', 'due' => $deadline->format('Y-m-d')],
                    'testing' => ['status' => 'pending', 'due' => $deadline->subDays(7)->format('Y-m-d')],
                    'deployment' => ['status' => 'not_started', 'due' => $deadline->format('Y-m-d')]
                ]),
                'client_name' => fake()->company(),
                'is_confidential' => rand(0, 4) === 0, // 20% chance
                'notes' => fake()->paragraphs(1, true),
                'created_at' => $startDate,
                'updated_at' => Carbon::now()->subDays(rand(1, 15))
            ];
        }
        DB::table('af_test_projects')->insert($projects);
        $projectIds = DB::table('af_test_projects')->pluck('id')->toArray();

        // Seed af_test_tasks
        $tasks = [];
        $taskStatuses = ['pending', 'in_progress', 'review', 'completed', 'cancelled'];
        $taskPriorities = ['low', 'medium', 'high', 'critical'];
        $difficulties = ['easy', 'medium', 'hard', 'expert'];
        $categories = ['Frontend', 'Backend', 'Database', 'Testing', 'Documentation', 'Deployment', 'Analysis', 'Design'];
        
        for ($i = 1; $i <= 300; $i++) {
            $dueDate = Carbon::now()->addDays(rand(-30, 60));
            $startedAt = rand(0, 1) ? Carbon::now()->subDays(rand(1, 30)) : null;
            $completedAt = $startedAt && rand(0, 1) ? $startedAt->copy()->addDays(rand(1, 10)) : null;
            
            $tasks[] = [
                'title' => fake()->sentence(rand(4, 8)),
                'description' => fake()->paragraphs(rand(1, 3), true),
                'status' => $taskStatuses[array_rand($taskStatuses)],
                'priority' => $taskPriorities[array_rand($taskPriorities)],
                'project_id' => $projectIds[array_rand($projectIds)],
                'assigned_to' => $userIds[array_rand($userIds)],
                'estimated_hours' => rand(1, 40),
                'actual_hours' => rand(0, 50),
                'completion_percentage' => rand(0, 100),
                'due_date' => $dueDate->format('Y-m-d'),
                'started_at' => $startedAt?->format('Y-m-d H:i:s'),
                'completed_at' => $completedAt?->format('Y-m-d H:i:s'),
                'dependencies' => json_encode(fake()->randomElements(range(1, min($i, 10)), rand(0, 3))),
                'attachments' => json_encode([
                    ['name' => 'specification.pdf', 'size' => rand(100, 5000) . 'KB'],
                    ['name' => 'wireframe.png', 'size' => rand(500, 2000) . 'KB']
                ]),
                'comments' => json_encode([
                    ['user' => fake()->name(), 'comment' => fake()->sentence(), 'date' => Carbon::now()->subDays(rand(1, 10))->format('Y-m-d')],
                    ['user' => fake()->name(), 'comment' => fake()->sentence(), 'date' => Carbon::now()->subDays(rand(1, 5))->format('Y-m-d')]
                ]),
                'tags' => json_encode(fake()->randomElements(['urgent', 'bug', 'feature', 'enhancement', 'refactor', 'security'], rand(1, 3))),
                'difficulty_level' => $difficulties[array_rand($difficulties)],
                'category' => $categories[array_rand($categories)],
                'is_billable' => rand(0, 1) ? true : false,
                'created_at' => Carbon::now()->subDays(rand(1, 60)),
                'updated_at' => Carbon::now()->subDays(rand(1, 10))
            ];
        }
        DB::table('af_test_tasks')->insert($tasks);

        // Legacy test data (for backward compatibility)
        $this->seedLegacyTables();

        echo "All test tables seeded successfully!\n";
        echo "Departments: " . count($departments) . "\n";
        echo "Users: " . count($users) . "\n";
        echo "Projects: " . count($projects) . "\n";
        echo "Tasks: " . count($tasks) . "\n";
    }

    private function seedLegacyTables()
    {
        // Seed test_categories
        $categories = [];
        $categoryNames = ['Technology', 'Travel', 'Food', 'Health', 'Education', 'Entertainment', 'Sports', 'Business'];
        foreach ($categoryNames as $name) {
            $categories[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "Description for {$name} category",
                'is_active' => rand(0, 1) ? true : false,
                'metadata' => json_encode([
                    'featured' => rand(0, 1) ? true : false,
                    'order' => rand(1, 10),
                    'icon' => 'icon-' . strtolower($name)
                ]),
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now()->subDays(rand(0, 5))
            ];
        }
        DB::table('test_categories')->insert($categories);
        $categoryIds = DB::table('test_categories')->pluck('id')->toArray();

        // Seed test_users
        $users = [];
        $statuses = ['active', 'inactive', 'pending'];
        for ($i = 1; $i <= 50; $i++) {
            $users[] = [
                'name' => "Test User {$i}",
                'email' => "testuser{$i}@example.com",
                'username' => "testuser{$i}",
                'status' => $statuses[array_rand($statuses)],
                'birth_date' => Carbon::now()->subYears(rand(18, 65))->format('Y-m-d'),
                'profile' => json_encode([
                    'first_name' => "Test",
                    'last_name' => "User {$i}",
                    'phone' => "+1234567890" . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'address' => [
                        'street' => "{$i} Test Street",
                        'city' => 'Test City',
                        'country' => 'Test Country'
                    ]
                ]),
                'preferences' => json_encode([
                    'theme' => rand(0, 1) ? 'dark' : 'light',
                    'notifications' => rand(0, 1) ? true : false,
                    'language' => ['en', 'fr', 'es'][array_rand(['en', 'fr', 'es'])],
                    'timezone' => 'UTC'
                ]),
                'created_at' => Carbon::now()->subDays(rand(1, 100)),
                'updated_at' => Carbon::now()->subDays(rand(0, 10))
            ];
        }
        DB::table('test_users')->insert($users);
        $userIds = DB::table('test_users')->pluck('id')->toArray();

        // Add more legacy data as needed...
        echo "Legacy tables seeded for backward compatibility.\n";
    }
}
