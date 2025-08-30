<?php

namespace ArtflowStudio\Table\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AFTestTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = [
            // Tasks for Customer Portal Redesign Project (ID: 1)
            [
                'title' => 'Create UI/UX Mockups for Customer Dashboard',
                'description' => 'Design comprehensive mockups for the new customer dashboard including wireframes and high-fidelity designs',
                'status' => 'completed',
                'priority' => 'high',
                'project_id' => 1,
                'assigned_to' => 2, // Alice Johnson
                'created_by' => 1, // John Smith
                'estimated_hours' => 40,
                'actual_hours' => 45,
                'progress_percentage' => 100,
                'due_date' => '2024-02-15',
                'started_at' => Carbon::create(2024, 1, 20, 9, 0, 0),
                'completed_at' => Carbon::create(2024, 2, 14, 17, 30, 0),
                'checklist' => json_encode([
                    ['item' => 'Research current user pain points', 'completed' => true],
                    ['item' => 'Create wireframes', 'completed' => true],
                    ['item' => 'Design high-fidelity mockups', 'completed' => true],
                    ['item' => 'Get stakeholder approval', 'completed' => true],
                    ['item' => 'Create design system documentation', 'completed' => true]
                ]),
                'attachments' => json_encode([
                    'mockups_v1.fig',
                    'wireframes.pdf',
                    'design_system.pdf',
                    'user_research_report.docx'
                ]),
                'comments' => json_encode([
                    ['user' => 'John Smith', 'date' => '2024-01-25', 'comment' => 'Great progress on wireframes. Make sure to include mobile responsive designs.'],
                    ['user' => 'Alice Johnson', 'date' => '2024-02-05', 'comment' => 'Added mobile mockups and accessibility considerations.'],
                    ['user' => 'John Smith', 'date' => '2024-02-14', 'comment' => 'Excellent work! Approved for development phase.']
                ]),
                'cost' => 3600.00,
                'is_billable' => true,
                'requires_approval' => true,
                'reference_code' => 'CPR-TASK-001',
                'completion_notes' => 'All mockups completed and approved. Design system documentation provided for development team.',
                'created_at' => Carbon::now()->subDays(85),
                'updated_at' => Carbon::now()->subDays(70),
            ],
            [
                'title' => 'Implement User Authentication System',
                'description' => 'Develop secure user authentication with multi-factor authentication support',
                'status' => 'in_progress',
                'priority' => 'critical',
                'project_id' => 1,
                'assigned_to' => 3, // Bob Wilson
                'created_by' => 1, // John Smith
                'estimated_hours' => 60,
                'actual_hours' => 35,
                'progress_percentage' => 60,
                'due_date' => '2024-04-15',
                'started_at' => Carbon::create(2024, 3, 1, 10, 0, 0),
                'completed_at' => null,
                'checklist' => json_encode([
                    ['item' => 'Set up authentication middleware', 'completed' => true],
                    ['item' => 'Implement login/logout functionality', 'completed' => true],
                    ['item' => 'Add password reset feature', 'completed' => true],
                    ['item' => 'Implement multi-factor authentication', 'completed' => false],
                    ['item' => 'Add session management', 'completed' => false],
                    ['item' => 'Security testing', 'completed' => false]
                ]),
                'attachments' => json_encode([
                    'auth_spec.pdf',
                    'security_requirements.docx'
                ]),
                'comments' => json_encode([
                    ['user' => 'Bob Wilson', 'date' => '2024-03-10', 'comment' => 'Basic auth implemented. Working on MFA integration.'],
                    ['user' => 'John Smith', 'date' => '2024-03-15', 'comment' => 'Good progress. Priority on MFA for security compliance.']
                ]),
                'cost' => 2800.00,
                'is_billable' => true,
                'requires_approval' => true,
                'reference_code' => 'CPR-TASK-002',
                'completion_notes' => null,
                'created_at' => Carbon::now()->subDays(75),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'title' => 'Develop Customer Analytics Dashboard',
                'description' => 'Create interactive dashboard showing customer metrics and analytics',
                'status' => 'pending',
                'priority' => 'medium',
                'project_id' => 1,
                'assigned_to' => 2, // Alice Johnson
                'created_by' => 1, // John Smith
                'estimated_hours' => 50,
                'actual_hours' => 0,
                'progress_percentage' => 0,
                'due_date' => '2024-05-20',
                'started_at' => null,
                'completed_at' => null,
                'checklist' => json_encode([
                    ['item' => 'Design dashboard layout', 'completed' => false],
                    ['item' => 'Implement data visualization components', 'completed' => false],
                    ['item' => 'Connect to analytics API', 'completed' => false],
                    ['item' => 'Add filtering and sorting options', 'completed' => false],
                    ['item' => 'Performance optimization', 'completed' => false],
                    ['item' => 'User acceptance testing', 'completed' => false]
                ]),
                'attachments' => json_encode([]),
                'comments' => json_encode([]),
                'cost' => 0.00,
                'is_billable' => true,
                'requires_approval' => false,
                'reference_code' => 'CPR-TASK-003',
                'completion_notes' => null,
                'created_at' => Carbon::now()->subDays(60),
                'updated_at' => Carbon::now()->subDays(60),
            ],

            // Tasks for API Gateway Implementation Project (ID: 2)
            [
                'title' => 'Design API Gateway Architecture',
                'description' => 'Create comprehensive architecture design for the API gateway implementation',
                'status' => 'in_progress',
                'priority' => 'high',
                'project_id' => 2,
                'assigned_to' => 3, // Bob Wilson
                'created_by' => 1, // John Smith
                'estimated_hours' => 30,
                'actual_hours' => 20,
                'progress_percentage' => 70,
                'due_date' => '2024-03-15',
                'started_at' => Carbon::create(2024, 2, 20, 9, 0, 0),
                'completed_at' => null,
                'checklist' => json_encode([
                    ['item' => 'Research API gateway solutions', 'completed' => true],
                    ['item' => 'Design system architecture', 'completed' => true],
                    ['item' => 'Define security requirements', 'completed' => true],
                    ['item' => 'Create deployment strategy', 'completed' => false],
                    ['item' => 'Document architecture decisions', 'completed' => false]
                ]),
                'attachments' => json_encode([
                    'architecture_diagram.pdf',
                    'technology_comparison.xlsx'
                ]),
                'comments' => json_encode([
                    ['user' => 'Bob Wilson', 'date' => '2024-03-01', 'comment' => 'Kong API Gateway selected. Working on detailed architecture.'],
                    ['user' => 'John Smith', 'date' => '2024-03-05', 'comment' => 'Good choice. Focus on scalability and monitoring requirements.']
                ]),
                'cost' => 1600.00,
                'is_billable' => true,
                'requires_approval' => true,
                'reference_code' => 'API-TASK-001',
                'completion_notes' => null,
                'created_at' => Carbon::now()->subDays(50),
                'updated_at' => Carbon::now()->subDays(3),
            ],

            // Tasks for Employee Onboarding System Project (ID: 4)
            [
                'title' => 'Design Digital Onboarding Forms',
                'description' => 'Create responsive digital forms to replace paper-based onboarding process',
                'status' => 'completed',
                'priority' => 'medium',
                'project_id' => 4,
                'assigned_to' => 5, // Mike Davis
                'created_by' => 4, // Sarah Johnson
                'estimated_hours' => 25,
                'actual_hours' => 28,
                'progress_percentage' => 100,
                'due_date' => '2024-02-01',
                'started_at' => Carbon::create(2024, 1, 10, 9, 0, 0),
                'completed_at' => Carbon::create(2024, 1, 30, 16, 45, 0),
                'checklist' => json_encode([
                    ['item' => 'Identify all required forms', 'completed' => true],
                    ['item' => 'Design form layouts', 'completed' => true],
                    ['item' => 'Implement validation rules', 'completed' => true],
                    ['item' => 'Add document upload functionality', 'completed' => true],
                    ['item' => 'Test on multiple devices', 'completed' => true]
                ]),
                'attachments' => json_encode([
                    'form_designs.pdf',
                    'validation_rules.docx'
                ]),
                'comments' => json_encode([
                    ['user' => 'Sarah Johnson', 'date' => '2024-01-20', 'comment' => 'Forms look great! Make sure file upload supports PDF and images.'],
                    ['user' => 'Mike Davis', 'date' => '2024-01-25', 'comment' => 'Added support for PDF, JPG, PNG files up to 5MB.']
                ]),
                'cost' => 2240.00,
                'is_billable' => false,
                'requires_approval' => false,
                'reference_code' => 'EOS-TASK-001',
                'completion_notes' => 'All onboarding forms digitized and tested. Ready for integration with workflow system.',
                'created_at' => Carbon::now()->subDays(110),
                'updated_at' => Carbon::now()->subDays(95),
            ],
            [
                'title' => 'Implement Workflow Automation',
                'description' => 'Build automated workflow system for onboarding process with notifications',
                'status' => 'in_progress',
                'priority' => 'high',
                'project_id' => 4,
                'assigned_to' => 5, // Mike Davis
                'created_by' => 4, // Sarah Johnson
                'estimated_hours' => 45,
                'actual_hours' => 25,
                'progress_percentage' => 55,
                'due_date' => '2024-04-15',
                'started_at' => Carbon::create(2024, 2, 5, 9, 0, 0),
                'completed_at' => null,
                'checklist' => json_encode([
                    ['item' => 'Design workflow states', 'completed' => true],
                    ['item' => 'Implement state transitions', 'completed' => true],
                    ['item' => 'Add email notifications', 'completed' => true],
                    ['item' => 'Create approval processes', 'completed' => false],
                    ['item' => 'Build admin dashboard', 'completed' => false],
                    ['item' => 'Integration testing', 'completed' => false]
                ]),
                'attachments' => json_encode([
                    'workflow_diagram.pdf',
                    'notification_templates.docx'
                ]),
                'comments' => json_encode([
                    ['user' => 'Mike Davis', 'date' => '2024-03-10', 'comment' => 'Basic workflow implemented. Working on approval chains.'],
                    ['user' => 'Sarah Johnson', 'date' => '2024-03-12', 'comment' => 'Great progress! Ensure managers get approval notifications.']
                ]),
                'cost' => 2000.00,
                'is_billable' => false,
                'requires_approval' => true,
                'reference_code' => 'EOS-TASK-002',
                'completion_notes' => null,
                'created_at' => Carbon::now()->subDays(80),
                'updated_at' => Carbon::now()->subDays(2),
            ],

            // Tasks for Budget Planning System Project (ID: 6)
            [
                'title' => 'Implement Multi-Level Approval System',
                'description' => 'Build hierarchical approval system for budget requests with role-based permissions',
                'status' => 'review',
                'priority' => 'critical',
                'project_id' => 6,
                'assigned_to' => 7, // Jennifer Lopez
                'created_by' => 6, // Michael Chen
                'estimated_hours' => 40,
                'actual_hours' => 42,
                'progress_percentage' => 95,
                'due_date' => '2024-03-30',
                'started_at' => Carbon::create(2024, 2, 15, 9, 0, 0),
                'completed_at' => null,
                'checklist' => json_encode([
                    ['item' => 'Define approval hierarchy', 'completed' => true],
                    ['item' => 'Implement role-based permissions', 'completed' => true],
                    ['item' => 'Create approval workflow', 'completed' => true],
                    ['item' => 'Add notification system', 'completed' => true],
                    ['item' => 'Build approval dashboard', 'completed' => true],
                    ['item' => 'Code review and testing', 'completed' => false]
                ]),
                'attachments' => json_encode([
                    'approval_hierarchy.pdf',
                    'permission_matrix.xlsx',
                    'test_cases.docx'
                ]),
                'comments' => json_encode([
                    ['user' => 'Jennifer Lopez', 'date' => '2024-03-20', 'comment' => 'Approval system completed. Ready for code review.'],
                    ['user' => 'Michael Chen', 'date' => '2024-03-22', 'comment' => 'Excellent work! Scheduling code review for tomorrow.']
                ]),
                'cost' => 3360.00,
                'is_billable' => false,
                'requires_approval' => true,
                'reference_code' => 'BPS-TASK-001',
                'completion_notes' => null,
                'created_at' => Carbon::now()->subDays(65),
                'updated_at' => Carbon::now()->subDays(1),
            ],

            // Tasks for Customer Analytics Platform Project (ID: 7)
            [
                'title' => 'Set Up Data Collection Pipeline',
                'description' => 'Implement data collection pipeline from various customer touchpoints',
                'status' => 'in_progress',
                'priority' => 'high',
                'project_id' => 7,
                'assigned_to' => 9, // David Brown (suspended user)
                'created_by' => 8, // Emma Davis
                'estimated_hours' => 35,
                'actual_hours' => 15,
                'progress_percentage' => 40,
                'due_date' => '2024-04-30',
                'started_at' => Carbon::create(2024, 3, 1, 9, 0, 0),
                'completed_at' => null,
                'checklist' => json_encode([
                    ['item' => 'Identify data sources', 'completed' => true],
                    ['item' => 'Design data schema', 'completed' => true],
                    ['item' => 'Implement ETL processes', 'completed' => false],
                    ['item' => 'Set up data validation', 'completed' => false],
                    ['item' => 'Create monitoring dashboard', 'completed' => false]
                ]),
                'attachments' => json_encode([
                    'data_sources.xlsx',
                    'schema_design.pdf'
                ]),
                'comments' => json_encode([
                    ['user' => 'David Brown', 'date' => '2024-03-15', 'comment' => 'Data sources identified. Starting ETL implementation.'],
                    ['user' => 'Emma Davis', 'date' => '2024-03-20', 'comment' => 'Note: David is currently unavailable. May need reassignment.']
                ]),
                'cost' => 1200.00,
                'is_billable' => true,
                'requires_approval' => false,
                'reference_code' => 'CAP-TASK-001',
                'completion_notes' => null,
                'created_at' => Carbon::now()->subDays(45),
                'updated_at' => Carbon::now()->subDays(10),
            ],

            // Additional high-priority tasks
            [
                'title' => 'Security Audit for Authentication System',
                'description' => 'Comprehensive security audit of the new authentication system before production deployment',
                'status' => 'pending',
                'priority' => 'urgent',
                'project_id' => 1,
                'assigned_to' => null, // Unassigned
                'created_by' => 1, // John Smith
                'estimated_hours' => 20,
                'actual_hours' => 0,
                'progress_percentage' => 0,
                'due_date' => '2024-04-20',
                'started_at' => null,
                'completed_at' => null,
                'checklist' => json_encode([
                    ['item' => 'Penetration testing', 'completed' => false],
                    ['item' => 'Code security review', 'completed' => false],
                    ['item' => 'Vulnerability assessment', 'completed' => false],
                    ['item' => 'Security documentation review', 'completed' => false],
                    ['item' => 'Compliance verification', 'completed' => false]
                ]),
                'attachments' => json_encode([]),
                'comments' => json_encode([
                    ['user' => 'John Smith', 'date' => '2024-03-25', 'comment' => 'Critical security audit needed before go-live. Need to assign security expert.']
                ]),
                'cost' => 0.00,
                'is_billable' => true,
                'requires_approval' => true,
                'reference_code' => 'CPR-TASK-004',
                'completion_notes' => null,
                'created_at' => Carbon::now()->subDays(30),
                'updated_at' => Carbon::now()->subDays(30),
            ],
            [
                'title' => 'Performance Optimization for Large Datasets',
                'description' => 'Optimize database queries and implement caching for handling large customer datasets',
                'status' => 'cancelled',
                'priority' => 'low',
                'project_id' => 7,
                'assigned_to' => 9, // David Brown
                'created_by' => 8, // Emma Davis
                'estimated_hours' => 30,
                'actual_hours' => 5,
                'progress_percentage' => 15,
                'due_date' => '2024-06-15',
                'started_at' => Carbon::create(2024, 3, 10, 10, 0, 0),
                'completed_at' => null,
                'checklist' => json_encode([
                    ['item' => 'Analyze current performance', 'completed' => true],
                    ['item' => 'Identify bottlenecks', 'completed' => false],
                    ['item' => 'Implement query optimization', 'completed' => false],
                    ['item' => 'Set up Redis caching', 'completed' => false],
                    ['item' => 'Performance testing', 'completed' => false]
                ]),
                'attachments' => json_encode([
                    'performance_analysis.pdf'
                ]),
                'comments' => json_encode([
                    ['user' => 'David Brown', 'date' => '2024-03-12', 'comment' => 'Initial analysis completed. Found several optimization opportunities.'],
                    ['user' => 'Emma Davis', 'date' => '2024-03-25', 'comment' => 'Cancelling due to resource constraints and shifted priorities.']
                ]),
                'cost' => 400.00,
                'is_billable' => true,
                'requires_approval' => false,
                'reference_code' => 'CAP-TASK-002',
                'completion_notes' => 'Task cancelled due to resource reallocation. May revisit in Q3.',
                'created_at' => Carbon::now()->subDays(35),
                'updated_at' => Carbon::now()->subDays(15),
            ],
        ];

        foreach ($tasks as $task) {
            DB::table('af_test_tasks')->insert($task);
        }
    }
}
