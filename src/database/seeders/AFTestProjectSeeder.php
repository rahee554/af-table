<?php

namespace ArtflowStudio\Table\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AFTestProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            // IT Department Projects
            [
                'name' => 'Customer Portal Redesign',
                'description' => 'Complete redesign of the customer portal with modern UI/UX and improved functionality',
                'code' => 'CPR-2024-001',
                'status' => 'active',
                'priority' => 'high',
                'department_id' => 1,
                'manager_id' => 1, // John Smith
                'budget' => 75000.00,
                'spent_amount' => 35000.00,
                'progress_percentage' => 45,
                'start_date' => '2024-01-15',
                'end_date' => '2024-06-30',
                'deadline' => '2024-07-15',
                'requirements' => json_encode([
                    'responsive_design',
                    'user_authentication',
                    'dashboard_analytics',
                    'mobile_compatibility',
                    'accessibility_compliance'
                ]),
                'technologies' => json_encode(['Laravel', 'Vue.js', 'MySQL', 'Redis', 'AWS']),
                'deliverables' => json_encode([
                    'ui_mockups' => ['status' => 'completed', 'due_date' => '2024-02-15'],
                    'backend_api' => ['status' => 'in_progress', 'due_date' => '2024-04-30'],
                    'frontend_app' => ['status' => 'planning', 'due_date' => '2024-05-31'],
                    'testing_deployment' => ['status' => 'pending', 'due_date' => '2024-06-15']
                ]),
                'client_name' => 'Internal - Customer Success Team',
                'is_confidential' => false,
                'notes' => 'High priority project with significant impact on customer satisfaction metrics.',
                'created_at' => Carbon::now()->subDays(90),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'name' => 'API Gateway Implementation',
                'description' => 'Implement centralized API gateway for microservices architecture',
                'code' => 'API-2024-002',
                'status' => 'planning',
                'priority' => 'medium',
                'department_id' => 1,
                'manager_id' => 1, // John Smith
                'budget' => 45000.00,
                'spent_amount' => 5000.00,
                'progress_percentage' => 10,
                'start_date' => '2024-03-01',
                'end_date' => '2024-08-31',
                'deadline' => '2024-09-15',
                'requirements' => json_encode([
                    'rate_limiting',
                    'authentication_integration',
                    'monitoring_logging',
                    'load_balancing',
                    'documentation'
                ]),
                'technologies' => json_encode(['Kong', 'Docker', 'Kubernetes', 'Prometheus', 'Grafana']),
                'deliverables' => json_encode([
                    'architecture_design' => ['status' => 'in_progress', 'due_date' => '2024-03-15'],
                    'gateway_setup' => ['status' => 'pending', 'due_date' => '2024-05-01'],
                    'integration_testing' => ['status' => 'pending', 'due_date' => '2024-07-01'],
                    'documentation' => ['status' => 'pending', 'due_date' => '2024-08-15']
                ]),
                'client_name' => 'Internal - Development Teams',
                'is_confidential' => false,
                'notes' => 'Will improve system scalability and API management across all services.',
                'created_at' => Carbon::now()->subDays(60),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'name' => 'Legacy System Migration',
                'description' => 'Migrate legacy PHP applications to Laravel framework',
                'code' => 'LSM-2024-003',
                'status' => 'on_hold',
                'priority' => 'low',
                'department_id' => 1,
                'manager_id' => 1, // John Smith
                'budget' => 120000.00,
                'spent_amount' => 15000.00,
                'progress_percentage' => 15,
                'start_date' => '2024-02-01',
                'end_date' => '2024-12-31',
                'deadline' => '2025-01-31',
                'requirements' => json_encode([
                    'data_migration',
                    'feature_parity',
                    'performance_improvement',
                    'security_updates',
                    'user_training'
                ]),
                'technologies' => json_encode(['Laravel', 'MySQL', 'PHP 8.2', 'Git', 'Docker']),
                'deliverables' => json_encode([
                    'assessment_report' => ['status' => 'completed', 'due_date' => '2024-02-15'],
                    'migration_plan' => ['status' => 'completed', 'due_date' => '2024-02-28'],
                    'prototype_development' => ['status' => 'on_hold', 'due_date' => '2024-06-01'],
                    'full_migration' => ['status' => 'pending', 'due_date' => '2024-11-30']
                ]),
                'client_name' => 'Internal - All Departments',
                'is_confidential' => false,
                'notes' => 'Project on hold due to resource allocation to higher priority projects.',
                'created_at' => Carbon::now()->subDays(45),
                'updated_at' => Carbon::now()->subDays(15),
            ],

            // HR Department Projects
            [
                'name' => 'Employee Onboarding System',
                'description' => 'Digital transformation of employee onboarding process',
                'code' => 'EOS-2024-004',
                'status' => 'active',
                'priority' => 'medium',
                'department_id' => 2,
                'manager_id' => 4, // Sarah Johnson
                'budget' => 35000.00,
                'spent_amount' => 20000.00,
                'progress_percentage' => 60,
                'start_date' => '2024-01-01',
                'end_date' => '2024-05-31',
                'deadline' => '2024-06-15',
                'requirements' => json_encode([
                    'digital_forms',
                    'document_upload',
                    'workflow_automation',
                    'notifications',
                    'reporting_dashboard'
                ]),
                'technologies' => json_encode(['Laravel', 'Livewire', 'MySQL', 'File Storage']),
                'deliverables' => json_encode([
                    'requirements_analysis' => ['status' => 'completed', 'due_date' => '2024-01-15'],
                    'system_design' => ['status' => 'completed', 'due_date' => '2024-02-01'],
                    'development' => ['status' => 'in_progress', 'due_date' => '2024-04-30'],
                    'testing_deployment' => ['status' => 'pending', 'due_date' => '2024-05-15']
                ]),
                'client_name' => 'Internal - HR Department',
                'is_confidential' => false,
                'notes' => 'Will significantly reduce onboarding time from 2 weeks to 3 days.',
                'created_at' => Carbon::now()->subDays(120),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'name' => 'Performance Review Automation',
                'description' => 'Automate annual performance review process with self-assessment and peer feedback',
                'code' => 'PRA-2024-005',
                'status' => 'completed',
                'priority' => 'high',
                'department_id' => 2,
                'manager_id' => 4, // Sarah Johnson
                'budget' => 25000.00,
                'spent_amount' => 23500.00,
                'progress_percentage' => 100,
                'start_date' => '2023-10-01',
                'end_date' => '2023-12-31',
                'deadline' => '2024-01-15',
                'requirements' => json_encode([
                    'self_assessment_forms',
                    'peer_feedback',
                    'manager_evaluation',
                    'goal_setting',
                    'report_generation'
                ]),
                'technologies' => json_encode(['Laravel', 'Vue.js', 'MySQL', 'PDF Generation']),
                'deliverables' => json_encode([
                    'system_design' => ['status' => 'completed', 'due_date' => '2023-10-15'],
                    'development' => ['status' => 'completed', 'due_date' => '2023-11-30'],
                    'testing' => ['status' => 'completed', 'due_date' => '2023-12-15'],
                    'deployment_training' => ['status' => 'completed', 'due_date' => '2023-12-31']
                ]),
                'client_name' => 'Internal - All Departments',
                'is_confidential' => false,
                'notes' => 'Successfully launched and used for 2024 annual reviews. Excellent feedback from users.',
                'created_at' => Carbon::now()->subDays(180),
                'updated_at' => Carbon::now()->subDays(30),
            ],

            // Finance Department Projects
            [
                'name' => 'Budget Planning System',
                'description' => 'Comprehensive budget planning and tracking system with approval workflows',
                'code' => 'BPS-2024-006',
                'status' => 'active',
                'priority' => 'high',
                'department_id' => 3,
                'manager_id' => 6, // Michael Chen
                'budget' => 60000.00,
                'spent_amount' => 40000.00,
                'progress_percentage' => 70,
                'start_date' => '2023-11-01',
                'end_date' => '2024-04-30',
                'deadline' => '2024-05-15',
                'requirements' => json_encode([
                    'multi_level_approval',
                    'budget_tracking',
                    'variance_analysis',
                    'reporting_dashboard',
                    'integration_with_accounting'
                ]),
                'technologies' => json_encode(['Laravel', 'Chart.js', 'MySQL', 'Excel Integration']),
                'deliverables' => json_encode([
                    'requirements_gathering' => ['status' => 'completed', 'due_date' => '2023-11-15'],
                    'system_development' => ['status' => 'in_progress', 'due_date' => '2024-03-31'],
                    'integration_testing' => ['status' => 'in_progress', 'due_date' => '2024-04-15'],
                    'user_training' => ['status' => 'pending', 'due_date' => '2024-04-30']
                ]),
                'client_name' => 'Internal - Finance & Department Heads',
                'is_confidential' => true,
                'notes' => 'Critical for 2025 budget planning cycle. Integration with existing accounting system is complex.',
                'created_at' => Carbon::now()->subDays(150),
                'updated_at' => Carbon::now()->subDays(1),
            ],

            // Marketing Department Projects
            [
                'name' => 'Customer Analytics Platform',
                'description' => 'Build comprehensive customer analytics and segmentation platform',
                'code' => 'CAP-2024-007',
                'status' => 'active',
                'priority' => 'medium',
                'department_id' => 4,
                'manager_id' => 8, // Emma Davis
                'budget' => 80000.00,
                'spent_amount' => 25000.00,
                'progress_percentage' => 30,
                'start_date' => '2024-02-01',
                'end_date' => '2024-08-31',
                'deadline' => '2024-09-30',
                'requirements' => json_encode([
                    'data_collection',
                    'customer_segmentation',
                    'behavioral_analysis',
                    'predictive_modeling',
                    'visualization_dashboard'
                ]),
                'technologies' => json_encode(['Laravel', 'Python', 'TensorFlow', 'D3.js', 'BigQuery']),
                'deliverables' => json_encode([
                    'data_architecture' => ['status' => 'completed', 'due_date' => '2024-02-15'],
                    'data_pipeline' => ['status' => 'in_progress', 'due_date' => '2024-05-01'],
                    'analytics_engine' => ['status' => 'planning', 'due_date' => '2024-07-01'],
                    'dashboard_development' => ['status' => 'pending', 'due_date' => '2024-08-15']
                ]),
                'client_name' => 'Internal - Marketing & Sales Teams',
                'is_confidential' => false,
                'notes' => 'Will enable data-driven marketing decisions and improve customer targeting.',
                'created_at' => Carbon::now()->subDays(75),
                'updated_at' => Carbon::now()->subDays(4),
            ],
            [
                'name' => 'Brand Website Redesign',
                'description' => 'Complete redesign of company website with modern design and improved SEO',
                'code' => 'BWR-2024-008',
                'status' => 'cancelled',
                'priority' => 'low',
                'department_id' => 4,
                'manager_id' => 8, // Emma Davis
                'budget' => 50000.00,
                'spent_amount' => 8000.00,
                'progress_percentage' => 15,
                'start_date' => '2024-01-15',
                'end_date' => '2024-05-31',
                'deadline' => '2024-06-30',
                'requirements' => json_encode([
                    'responsive_design',
                    'seo_optimization',
                    'content_management',
                    'performance_optimization',
                    'analytics_integration'
                ]),
                'technologies' => json_encode(['WordPress', 'PHP', 'JavaScript', 'CSS3', 'Google Analytics']),
                'deliverables' => json_encode([
                    'design_mockups' => ['status' => 'completed', 'due_date' => '2024-02-01'],
                    'content_strategy' => ['status' => 'cancelled', 'due_date' => '2024-02-15'],
                    'development' => ['status' => 'cancelled', 'due_date' => '2024-04-30'],
                    'seo_optimization' => ['status' => 'cancelled', 'due_date' => '2024-05-15']
                ]),
                'client_name' => 'Internal - Marketing Department',
                'is_confidential' => false,
                'notes' => 'Project cancelled due to budget reallocation to higher priority customer analytics platform.',
                'created_at' => Carbon::now()->subDays(100),
                'updated_at' => Carbon::now()->subDays(20),
            ],

            // R&D Department Projects
            [
                'name' => 'AI-Powered Data Analysis Tool',
                'description' => 'Develop machine learning tool for automated data analysis and insights',
                'code' => 'ADA-2024-009',
                'status' => 'active',
                'priority' => 'critical',
                'department_id' => 5,
                'manager_id' => null, // No specific manager assigned yet
                'budget' => 150000.00,
                'spent_amount' => 60000.00,
                'progress_percentage' => 40,
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'deadline' => '2025-01-31',
                'requirements' => json_encode([
                    'machine_learning_models',
                    'data_preprocessing',
                    'automated_insights',
                    'api_integration',
                    'scalable_architecture'
                ]),
                'technologies' => json_encode(['Python', 'TensorFlow', 'Pandas', 'FastAPI', 'Docker']),
                'deliverables' => json_encode([
                    'research_phase' => ['status' => 'completed', 'due_date' => '2024-02-29'],
                    'prototype_development' => ['status' => 'in_progress', 'due_date' => '2024-06-30'],
                    'model_training' => ['status' => 'in_progress', 'due_date' => '2024-09-30'],
                    'production_deployment' => ['status' => 'pending', 'due_date' => '2024-12-15']
                ]),
                'client_name' => 'Internal - All Departments',
                'is_confidential' => true,
                'notes' => 'High-impact R&D project with potential for commercialization. Regular progress reviews scheduled.',
                'created_at' => Carbon::now()->subDays(130),
                'updated_at' => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($projects as $project) {
            DB::table('af_test_projects')->insert($project);
        }
    }
}
