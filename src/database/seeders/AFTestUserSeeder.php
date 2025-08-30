<?php

namespace ArtflowStudio\Table\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AFTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            // IT Department Users
            [
                'name' => 'John Smith',
                'email' => 'john.smith@aftable.test',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => '+1-555-0101',
                'role' => 'manager',
                'status' => 'active',
                'department_id' => 1,
                'profile' => json_encode([
                    'full_name' => 'John Alexander Smith',
                    'address' => [
                        'street' => '123 Tech Street',
                        'city' => 'San Francisco',
                        'state' => 'CA',
                        'zip' => '94105'
                    ],
                    'preferences' => ['email_notifications', 'dark_theme'],
                    'emergency_contact' => [
                        'name' => 'Jane Smith',
                        'phone' => '+1-555-0102'
                    ]
                ]),
                'salary' => 120000.00,
                'hire_date' => '2020-01-15',
                'birth_date' => '1985-05-12',
                'bio' => 'Experienced IT manager with 10+ years in software development and team leadership.',
                'is_remote' => false,
                'skills' => json_encode(['PHP', 'Laravel', 'Team Management', 'System Architecture']),
                'last_login_at' => Carbon::now()->subHours(2),
                'created_at' => Carbon::now()->subDays(365),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'name' => 'Alice Johnson',
                'email' => 'alice.johnson@aftable.test',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => '+1-555-0103',
                'role' => 'employee',
                'status' => 'active',
                'department_id' => 1,
                'profile' => json_encode([
                    'full_name' => 'Alice Marie Johnson',
                    'address' => [
                        'street' => '456 Dev Avenue',
                        'city' => 'Palo Alto',
                        'state' => 'CA',
                        'zip' => '94301'
                    ],
                    'preferences' => ['mobile_notifications'],
                    'emergency_contact' => [
                        'name' => 'Bob Johnson',
                        'phone' => '+1-555-0104'
                    ]
                ]),
                'salary' => 85000.00,
                'hire_date' => '2021-03-01',
                'birth_date' => '1990-08-25',
                'bio' => 'Full-stack developer specializing in Laravel and React applications.',
                'is_remote' => true,
                'skills' => json_encode(['Laravel', 'React', 'MySQL', 'Git', 'API Development']),
                'last_login_at' => Carbon::now()->subMinutes(30),
                'created_at' => Carbon::now()->subDays(300),
                'updated_at' => Carbon::now()->subHours(3),
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob.wilson@aftable.test',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => '+1-555-0105',
                'role' => 'employee',
                'status' => 'active',
                'department_id' => 1,
                'profile' => json_encode([
                    'full_name' => 'Robert James Wilson',
                    'address' => [
                        'street' => '789 Code Lane',
                        'city' => 'Mountain View',
                        'state' => 'CA',
                        'zip' => '94041'
                    ],
                    'preferences' => ['email_notifications', 'desktop_notifications'],
                    'emergency_contact' => [
                        'name' => 'Mary Wilson',
                        'phone' => '+1-555-0106'
                    ]
                ]),
                'salary' => 78000.00,
                'hire_date' => '2021-07-15',
                'birth_date' => '1988-11-30',
                'bio' => 'DevOps engineer focused on cloud infrastructure and automation.',
                'is_remote' => false,
                'skills' => json_encode(['AWS', 'Docker', 'Kubernetes', 'CI/CD', 'Linux']),
                'last_login_at' => Carbon::now()->subHours(1),
                'created_at' => Carbon::now()->subDays(250),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            
            // HR Department Users
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@aftable.test',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => '+1-555-0201',
                'role' => 'manager',
                'status' => 'active',
                'department_id' => 2,
                'profile' => json_encode([
                    'full_name' => 'Sarah Elizabeth Johnson',
                    'address' => [
                        'street' => '321 HR Boulevard',
                        'city' => 'San Jose',
                        'state' => 'CA',
                        'zip' => '95110'
                    ],
                    'preferences' => ['email_notifications'],
                    'emergency_contact' => [
                        'name' => 'Tom Johnson',
                        'phone' => '+1-555-0202'
                    ]
                ]),
                'salary' => 95000.00,
                'hire_date' => '2019-06-01',
                'birth_date' => '1982-02-14',
                'bio' => 'HR manager with expertise in talent acquisition and employee development.',
                'is_remote' => false,
                'skills' => json_encode(['Recruitment', 'Employee Relations', 'Training', 'Policy Development']),
                'last_login_at' => Carbon::now()->subHours(4),
                'created_at' => Carbon::now()->subDays(320),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'name' => 'Mike Davis',
                'email' => 'mike.davis@aftable.test',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => '+1-555-0203',
                'role' => 'employee',
                'status' => 'active',
                'department_id' => 2,
                'profile' => json_encode([
                    'full_name' => 'Michael Robert Davis',
                    'address' => [
                        'street' => '654 Recruiter Road',
                        'city' => 'Santa Clara',
                        'state' => 'CA',
                        'zip' => '95050'
                    ],
                    'preferences' => ['mobile_notifications', 'email_notifications'],
                    'emergency_contact' => [
                        'name' => 'Lisa Davis',
                        'phone' => '+1-555-0204'
                    ]
                ]),
                'salary' => 68000.00,
                'hire_date' => '2020-09-01',
                'birth_date' => '1987-07-08',
                'bio' => 'HR specialist focusing on recruitment and onboarding processes.',
                'is_remote' => true,
                'skills' => json_encode(['Recruitment', 'Interviewing', 'Onboarding', 'HRIS']),
                'last_login_at' => Carbon::now()->subHours(6),
                'created_at' => Carbon::now()->subDays(200),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            
            // Finance Department Users
            [
                'name' => 'Michael Chen',
                'email' => 'michael.chen@aftable.test',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => '+1-555-0301',
                'role' => 'manager',
                'status' => 'active',
                'department_id' => 3,
                'profile' => json_encode([
                    'full_name' => 'Michael Wei Chen',
                    'address' => [
                        'street' => '987 Finance Street',
                        'city' => 'Fremont',
                        'state' => 'CA',
                        'zip' => '94536'
                    ],
                    'preferences' => ['email_notifications', 'financial_alerts'],
                    'emergency_contact' => [
                        'name' => 'Angela Chen',
                        'phone' => '+1-555-0302'
                    ]
                ]),
                'salary' => 110000.00,
                'hire_date' => '2018-03-01',
                'birth_date' => '1980-09-22',
                'bio' => 'CPA with 15+ years experience in financial management and analysis.',
                'is_remote' => false,
                'skills' => json_encode(['Financial Analysis', 'Budget Management', 'CPA', 'Tax Planning']),
                'last_login_at' => Carbon::now()->subHours(3),
                'created_at' => Carbon::now()->subDays(280),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'name' => 'Jennifer Lopez',
                'email' => 'jennifer.lopez@aftable.test',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => '+1-555-0303',
                'role' => 'employee',
                'status' => 'active',
                'department_id' => 3,
                'profile' => json_encode([
                    'full_name' => 'Jennifer Maria Lopez',
                    'address' => [
                        'street' => '147 Accounting Avenue',
                        'city' => 'Hayward',
                        'state' => 'CA',
                        'zip' => '94541'
                    ],
                    'preferences' => ['email_notifications'],
                    'emergency_contact' => [
                        'name' => 'Carlos Lopez',
                        'phone' => '+1-555-0304'
                    ]
                ]),
                'salary' => 72000.00,
                'hire_date' => '2020-01-15',
                'birth_date' => '1989-12-03',
                'bio' => 'Accountant specializing in accounts payable and receivable.',
                'is_remote' => false,
                'skills' => json_encode(['Accounting', 'QuickBooks', 'Excel', 'Financial Reporting']),
                'last_login_at' => Carbon::now()->subHours(5),
                'created_at' => Carbon::now()->subDays(230),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            
            // Marketing Department Users
            [
                'name' => 'Emma Davis',
                'email' => 'emma.davis@aftable.test',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => '+1-555-0401',
                'role' => 'manager',
                'status' => 'active',
                'department_id' => 4,
                'profile' => json_encode([
                    'full_name' => 'Emma Charlotte Davis',
                    'address' => [
                        'street' => '258 Marketing Mall',
                        'city' => 'Oakland',
                        'state' => 'CA',
                        'zip' => '94612'
                    ],
                    'preferences' => ['email_notifications', 'campaign_alerts'],
                    'emergency_contact' => [
                        'name' => 'James Davis',
                        'phone' => '+1-555-0402'
                    ]
                ]),
                'salary' => 98000.00,
                'hire_date' => '2019-09-01',
                'birth_date' => '1986-04-17',
                'bio' => 'Digital marketing manager with expertise in online campaigns and brand management.',
                'is_remote' => true,
                'skills' => json_encode(['Digital Marketing', 'SEO/SEM', 'Social Media', 'Analytics']),
                'last_login_at' => Carbon::now()->subMinutes(15),
                'created_at' => Carbon::now()->subDays(240),
                'updated_at' => Carbon::now()->subHours(1),
            ],
            [
                'name' => 'David Brown',
                'email' => 'david.brown@aftable.test',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => '+1-555-0403',
                'role' => 'employee',
                'status' => 'suspended',
                'department_id' => 4,
                'profile' => json_encode([
                    'full_name' => 'David Michael Brown',
                    'address' => [
                        'street' => '369 Content Court',
                        'city' => 'Berkeley',
                        'state' => 'CA',
                        'zip' => '94705'
                    ],
                    'preferences' => ['mobile_notifications'],
                    'emergency_contact' => [
                        'name' => 'Susan Brown',
                        'phone' => '+1-555-0404'
                    ]
                ]),
                'salary' => 65000.00,
                'hire_date' => '2021-02-01',
                'birth_date' => '1991-06-28',
                'bio' => 'Content marketing specialist focusing on blog posts and social media content.',
                'is_remote' => true,
                'skills' => json_encode(['Content Writing', 'Social Media', 'WordPress', 'Photography']),
                'last_login_at' => Carbon::now()->subDays(30),
                'created_at' => Carbon::now()->subDays(180),
                'updated_at' => Carbon::now()->subDays(30),
            ],
            
            // Additional Test Users for various scenarios
            [
                'name' => 'Test Admin',
                'email' => 'admin@aftable.test',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('password'),
                'phone' => '+1-555-9999',
                'role' => 'admin',
                'status' => 'active',
                'department_id' => 1,
                'profile' => json_encode([
                    'full_name' => 'System Administrator',
                    'address' => [
                        'street' => '000 Admin Avenue',
                        'city' => 'System',
                        'state' => 'CA',
                        'zip' => '00000'
                    ],
                    'preferences' => ['all_notifications'],
                    'emergency_contact' => [
                        'name' => 'Emergency Contact',
                        'phone' => '+1-555-0000'
                    ]
                ]),
                'salary' => 150000.00,
                'hire_date' => '2018-01-01',
                'birth_date' => '1975-01-01',
                'bio' => 'System administrator with full access privileges.',
                'is_remote' => false,
                'skills' => json_encode(['System Administration', 'Security', 'Database Management']),
                'last_login_at' => Carbon::now(),
                'created_at' => Carbon::now()->subDays(400),
                'updated_at' => Carbon::now(),
            ],
        ];

        foreach ($users as $user) {
            DB::table('af_test_users')->insert($user);
        }
    }
}
