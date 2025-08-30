<?php

namespace ArtflowStudio\Table\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AFTestDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Responsible for all technology infrastructure and software development',
                'status' => 'active',
                'metadata' => json_encode([
                    'floor' => 3,
                    'extension' => '3001',
                    'head' => 'John Smith',
                    'technologies' => ['PHP', 'Laravel', 'React', 'MySQL']
                ]),
                'budget' => 150000.00,
                'employee_count' => 25,
                'established_date' => '2020-01-15',
                'created_at' => Carbon::now()->subDays(365),
                'updated_at' => Carbon::now()->subDays(30),
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Manages employee relations, recruitment, and company policies',
                'status' => 'active',
                'metadata' => json_encode([
                    'floor' => 2,
                    'extension' => '2001',
                    'head' => 'Sarah Johnson',
                    'specializations' => ['Recruitment', 'Training', 'Benefits']
                ]),
                'budget' => 80000.00,
                'employee_count' => 8,
                'established_date' => '2019-06-01',
                'created_at' => Carbon::now()->subDays(320),
                'updated_at' => Carbon::now()->subDays(15),
            ],
            [
                'name' => 'Finance & Accounting',
                'code' => 'FIN',
                'description' => 'Handles financial planning, accounting, and budget management',
                'status' => 'active',
                'metadata' => json_encode([
                    'floor' => 1,
                    'extension' => '1001',
                    'head' => 'Michael Chen',
                    'certifications' => ['CPA', 'CMA']
                ]),
                'budget' => 120000.00,
                'employee_count' => 12,
                'established_date' => '2018-03-01',
                'created_at' => Carbon::now()->subDays(280),
                'updated_at' => Carbon::now()->subDays(7),
            ],
            [
                'name' => 'Marketing & Sales',
                'code' => 'MKT',
                'description' => 'Drives customer acquisition and brand management',
                'status' => 'active',
                'metadata' => json_encode([
                    'floor' => 2,
                    'extension' => '2002',
                    'head' => 'Emma Davis',
                    'channels' => ['Digital', 'Print', 'Events', 'Social Media']
                ]),
                'budget' => 200000.00,
                'employee_count' => 18,
                'established_date' => '2019-09-01',
                'created_at' => Carbon::now()->subDays(240),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'name' => 'Research & Development',
                'code' => 'RND',
                'description' => 'Focuses on innovation and new product development',
                'status' => 'active',
                'metadata' => json_encode([
                    'floor' => 4,
                    'extension' => '4001',
                    'head' => 'Dr. Robert Wilson',
                    'focus_areas' => ['AI/ML', 'IoT', 'Blockchain', 'Mobile Apps']
                ]),
                'budget' => 300000.00,
                'employee_count' => 15,
                'established_date' => '2021-01-01',
                'created_at' => Carbon::now()->subDays(200),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'name' => 'Customer Support',
                'code' => 'CS',
                'description' => 'Provides technical support and customer service',
                'status' => 'active',
                'metadata' => json_encode([
                    'floor' => 1,
                    'extension' => '1002',
                    'head' => 'Lisa Garcia',
                    'channels' => ['Phone', 'Email', 'Chat', 'Tickets']
                ]),
                'budget' => 90000.00,
                'employee_count' => 20,
                'established_date' => '2020-08-01',
                'created_at' => Carbon::now()->subDays(150),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'name' => 'Quality Assurance',
                'code' => 'QA',
                'description' => 'Ensures product quality and testing standards',
                'status' => 'active',
                'metadata' => json_encode([
                    'floor' => 3,
                    'extension' => '3002',
                    'head' => 'David Brown',
                    'testing_types' => ['Manual', 'Automated', 'Performance', 'Security']
                ]),
                'budget' => 70000.00,
                'employee_count' => 10,
                'established_date' => '2020-11-01',
                'created_at' => Carbon::now()->subDays(100),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Manages daily operations and logistics',
                'status' => 'inactive',
                'metadata' => json_encode([
                    'floor' => 1,
                    'extension' => '1003',
                    'head' => 'Jennifer Taylor',
                    'areas' => ['Logistics', 'Procurement', 'Facilities']
                ]),
                'budget' => 60000.00,
                'employee_count' => 5,
                'established_date' => '2019-01-01',
                'created_at' => Carbon::now()->subDays(80),
                'updated_at' => Carbon::now()->subDays(10),
            ],
        ];

        foreach ($departments as $department) {
            DB::table('af_test_departments')->insert($department);
        }
    }
}
