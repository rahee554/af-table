<?php

namespace ArtflowStudio\Table\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ArtflowStudio\Table\Testing\Models\TestUser;
use ArtflowStudio\Table\Testing\Models\TestPost;
use ArtflowStudio\Table\Testing\Models\TestCategory;
use ArtflowStudio\Table\Testing\Models\TestComment;
use ArtflowStudio\Table\Testing\Models\TestTag;

class TestController extends Controller
{
    /**
     * Main test page for AF Table package
     */
    public function testPage()
    {
        return view('artflow-table::test-pages.main-test', [
            'title' => 'AF Table - Main Test Page',
            'description' => 'Comprehensive testing page for AF Table package functionality'
        ]);
    }

    /**
     * Test page specifically for DatatableTrait component
     */
    public function testTraitPage()
    {
        return view('artflow-table::test-pages.trait-test', [
            'title' => 'AF Table - Trait Test Page',
            'description' => 'Testing page for DatatableTrait component and all its traits'
        ]);
    }

    /**
     * Test departments datatable
     */
    public function testDepartments()
    {
        $columns = [
            [
                'key' => 'id',
                'label' => 'ID',
                'sortable' => true,
            ],
            [
                'key' => 'name',
                'label' => 'Department Name',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'code',
                'label' => 'Code',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'sortable' => true,
            ],
            [
                'key' => 'employee_count',
                'label' => 'Employees',
                'sortable' => true,
            ],
            [
                'key' => 'budget',
                'label' => 'Budget',
                'sortable' => true,
                'raw' => '<span class="badge bg-success">${{ number_format($row->budget, 2) }}</span>',
            ],
            [
                'key' => 'metadata',
                'label' => 'Floor',
                'json' => 'floor',
                'sortable' => false,
            ],
            [
                'key' => 'created_at',
                'label' => 'Created',
                'sortable' => true,
                'raw' => '{{ $row->created_at->format("M d, Y") }}',
            ],
        ];

        $filters = [
            'status' => ['type' => 'select'],
            'employee_count' => ['type' => 'number'],
            'budget' => ['type' => 'number'],
        ];

        $actions = [
            '<a href="#" class="btn btn-sm btn-primary me-1">Edit</a>',
            '<a href="#" class="btn btn-sm btn-danger">Delete</a>',
        ];

        return view('artflow-table::test-pages.model-test', [
            'title' => 'AF Table - Departments Test',
            'model' => 'ArtflowStudio\Table\Testing\Models\TestDepartment',
            'columns' => $columns,
            'filters' => $filters,
            'actions' => $actions,
            'tableId' => 'departments-test',
        ]);
    }

    /**
     * Test users datatable with complex relationships and JSON
     */
    public function testUsers()
    {
        $columns = [
            [
                'key' => 'id',
                'label' => 'ID',
                'sortable' => true,
            ],
            [
                'key' => 'name',
                'label' => 'Full Name',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'role',
                'label' => 'Role',
                'sortable' => true,
                'raw' => '<span class="badge bg-{{ $row->role === "admin" ? "danger" : ($row->role === "manager" ? "warning" : "info") }}">{{ ucfirst($row->role) }}</span>',
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'sortable' => true,
                'raw' => '<span class="badge bg-{{ $row->status === "active" ? "success" : ($row->status === "suspended" ? "danger" : "secondary") }}">{{ ucfirst($row->status) }}</span>',
            ],
            [
                'relation' => 'department:name',
                'label' => 'Department',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'profile',
                'label' => 'City',
                'json' => 'address.city',
                'sortable' => false,
                'searchable' => true,
            ],
            [
                'key' => 'salary',
                'label' => 'Salary',
                'sortable' => true,
                'raw' => '<span class="text-success fw-bold">${{ number_format($row->salary, 0) }}</span>',
            ],
            [
                'key' => 'hire_date',
                'label' => 'Hire Date',
                'sortable' => true,
                'raw' => '{{ $row->hire_date ? date("M d, Y", strtotime($row->hire_date)) : "N/A" }}',
            ],
            [
                'key' => 'is_remote',
                'label' => 'Remote',
                'sortable' => true,
                'raw' => '<i class="fas fa-{{ $row->is_remote ? "check text-success" : "times text-danger" }}"></i>',
            ],
        ];

        $filters = [
            'role' => ['type' => 'select'],
            'status' => ['type' => 'select'],
            'department_id' => ['type' => 'select', 'relation' => 'department:name'],
            'salary' => ['type' => 'number'],
            'is_remote' => ['type' => 'boolean'],
        ];

        $actions = [
            '<a href="#" class="btn btn-sm btn-outline-primary me-1" title="View Profile"><i class="fas fa-eye"></i></a>',
            '<a href="#" class="btn btn-sm btn-outline-success me-1" title="Edit User"><i class="fas fa-edit"></i></a>',
            '<a href="#" class="btn btn-sm btn-outline-danger" title="Delete User"><i class="fas fa-trash"></i></a>',
        ];

        return view('artflow-table::test-pages.model-test', [
            'title' => 'AF Table - Users Test',
            'model' => 'ArtflowStudio\Table\Testing\Models\TestUser',
            'columns' => $columns,
            'filters' => $filters,
            'actions' => $actions,
            'tableId' => 'users-test',
        ]);
    }

    /**
     * Test projects datatable with complex relationships
     */
    public function testProjects()
    {
        $columns = [
            [
                'key' => 'id',
                'label' => 'ID',
                'sortable' => true,
            ],
            [
                'key' => 'name',
                'label' => 'Project Name',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'code',
                'label' => 'Code',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'sortable' => true,
                'raw' => '<span class="badge bg-{{ $row->status === "active" ? "success" : ($row->status === "completed" ? "primary" : ($row->status === "cancelled" ? "danger" : "warning")) }}">{{ ucwords(str_replace("_", " ", $row->status)) }}</span>',
            ],
            [
                'key' => 'priority',
                'label' => 'Priority',
                'sortable' => true,
                'raw' => '<span class="badge bg-{{ $row->priority === "critical" ? "danger" : ($row->priority === "high" ? "warning" : ($row->priority === "medium" ? "info" : "secondary")) }}">{{ ucfirst($row->priority) }}</span>',
            ],
            [
                'relation' => 'department:name',
                'label' => 'Department',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'relation' => 'manager:name',
                'label' => 'Manager',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'progress_percentage',
                'label' => 'Progress',
                'sortable' => true,
                'raw' => '<div class="progress" style="width: 80px;"><div class="progress-bar bg-{{ $row->progress_percentage >= 75 ? "success" : ($row->progress_percentage >= 50 ? "info" : ($row->progress_percentage >= 25 ? "warning" : "danger")) }}" style="width: {{ $row->progress_percentage }}%">{{ $row->progress_percentage }}%</div></div>',
            ],
            [
                'key' => 'budget',
                'label' => 'Budget',
                'sortable' => true,
                'raw' => '<span class="text-primary fw-bold">${{ number_format($row->budget, 0) }}</span>',
            ],
            [
                'key' => 'deadline',
                'label' => 'Deadline',
                'sortable' => true,
                'raw' => '{{ $row->deadline ? date("M d, Y", strtotime($row->deadline)) : "N/A" }}',
            ],
        ];

        $filters = [
            'status' => ['type' => 'select'],
            'priority' => ['type' => 'select'],
            'department_id' => ['type' => 'select', 'relation' => 'department:name'],
            'manager_id' => ['type' => 'select', 'relation' => 'manager:name'],
            'budget' => ['type' => 'number'],
            'progress_percentage' => ['type' => 'number'],
        ];

        $actions = [
            '<a href="#" class="btn btn-sm btn-outline-info me-1" title="View Details"><i class="fas fa-info-circle"></i></a>',
            '<a href="#" class="btn btn-sm btn-outline-success me-1" title="Edit Project"><i class="fas fa-edit"></i></a>',
            '<a href="#" class="btn btn-sm btn-outline-warning me-1" title="View Tasks"><i class="fas fa-tasks"></i></a>',
            '<a href="#" class="btn btn-sm btn-outline-danger" title="Archive"><i class="fas fa-archive"></i></a>',
        ];

        return view('artflow-table::test-pages.model-test', [
            'title' => 'AF Table - Projects Test',
            'model' => 'ArtflowStudio\Table\Testing\Models\TestProject',
            'columns' => $columns,
            'filters' => $filters,
            'actions' => $actions,
            'tableId' => 'projects-test',
        ]);
    }

    /**
     * Test tasks datatable with complex relationships and JSON data
     */
    public function testTasks()
    {
        $columns = [
            [
                'key' => 'id',
                'label' => 'ID',
                'sortable' => true,
            ],
            [
                'key' => 'title',
                'label' => 'Task Title',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'sortable' => true,
                'raw' => '<span class="badge bg-{{ $row->status === "completed" ? "success" : ($row->status === "in_progress" ? "info" : ($row->status === "review" ? "warning" : ($row->status === "cancelled" ? "danger" : "secondary"))) }}">{{ ucwords(str_replace("_", " ", $row->status)) }}</span>',
            ],
            [
                'key' => 'priority',
                'label' => 'Priority',
                'sortable' => true,
                'raw' => '<span class="badge bg-{{ $row->priority === "urgent" ? "danger" : ($row->priority === "high" ? "warning" : ($row->priority === "medium" ? "info" : "secondary")) }}">{{ ucfirst($row->priority) }}</span>',
            ],
            [
                'relation' => 'project:name',
                'label' => 'Project',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'relation' => 'assignedTo:name',
                'label' => 'Assigned To',
                'sortable' => true,
                'searchable' => true,
            ],
            [
                'key' => 'progress_percentage',
                'label' => 'Progress',
                'sortable' => true,
                'raw' => '<div class="progress" style="width: 60px;"><div class="progress-bar" style="width: {{ $row->progress_percentage }}%">{{ $row->progress_percentage }}%</div></div>',
            ],
            [
                'key' => 'estimated_hours',
                'label' => 'Est. Hours',
                'sortable' => true,
            ],
            [
                'key' => 'actual_hours',
                'label' => 'Actual Hours',
                'sortable' => true,
                'raw' => '<span class="{{ $row->actual_hours > $row->estimated_hours ? "text-danger" : "text-success" }}">{{ $row->actual_hours }}</span>',
            ],
            [
                'key' => 'due_date',
                'label' => 'Due Date',
                'sortable' => true,
                'raw' => '{{ $row->due_date ? date("M d, Y", strtotime($row->due_date)) : "N/A" }}',
            ],
            [
                'key' => 'cost',
                'label' => 'Cost',
                'sortable' => true,
                'raw' => '<span class="text-primary">${{ number_format($row->cost, 2) }}</span>',
            ],
        ];

        $filters = [
            'status' => ['type' => 'select'],
            'priority' => ['type' => 'select'],
            'project_id' => ['type' => 'select', 'relation' => 'project:name'],
            'assigned_to' => ['type' => 'select', 'relation' => 'assignedTo:name'],
            'progress_percentage' => ['type' => 'number'],
            'is_billable' => ['type' => 'boolean'],
        ];

        $actions = [
            '<a href="#" class="btn btn-sm btn-outline-primary me-1" title="View Task"><i class="fas fa-eye"></i></a>',
            '<a href="#" class="btn btn-sm btn-outline-success me-1" title="Edit Task"><i class="fas fa-edit"></i></a>',
            '<a href="#" class="btn btn-sm btn-outline-info me-1" title="Comments"><i class="fas fa-comments"></i></a>',
            '<a href="#" class="btn btn-sm btn-outline-warning me-1" title="Time Log"><i class="fas fa-clock"></i></a>',
            '<a href="#" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></a>',
        ];

        return view('artflow-table::test-pages.model-test', [
            'title' => 'AF Table - Tasks Test',
            'model' => 'ArtflowStudio\Table\Testing\Models\TestTask',
            'columns' => $columns,
            'filters' => $filters,
            'actions' => $actions,
            'tableId' => 'tasks-test',
        ]);
    }

    /**
     * Comprehensive test page with multiple datatables
     */
    public function testComprehensive()
    {
        return view('artflow-table::test-pages.comprehensive-test', [
            'title' => 'AF Table - Comprehensive Test Suite',
            'description' => 'Testing multiple datatables, features, and edge cases'
        ]);
    }

    /**
     * Performance testing page
     */
    public function testPerformance()
    {
        return view('artflow-table::test-pages.performance-test', [
            'title' => 'AF Table - Performance Testing',
            'description' => 'Testing datatable performance with various dataset sizes'
        ]);
    }

    /**
     * Large dataset testing
     */
    public function testLargeDataset()
    {
        return view('artflow-table::test-pages.large-dataset-test', [
            'title' => 'AF Table - Large Dataset Test',
            'description' => 'Testing datatable with large datasets and optimization features'
        ]);
    }

    /**
     * JSON columns testing
     */
    public function testJsonColumns()
    {
        return view('artflow-table::test-pages.json-test', [
            'title' => 'AF Table - JSON Columns Test',
            'description' => 'Testing JSON column extraction and display functionality'
        ]);
    }

    /**
     * Relationships testing
     */
    public function testRelations()
    {
        return view('artflow-table::test-pages.relations-test', [
            'title' => 'AF Table - Relationships Test',
            'description' => 'Testing relationship columns, eager loading, and nested relations'
        ]);
    }

    /**
     * Export functionality testing
     */
    public function testExport()
    {
        return view('artflow-table::test-pages.export-test', [
            'title' => 'AF Table - Export Test',
            'description' => 'Testing export functionality (CSV, Excel, PDF)'
        ]);
    }

    /**
     * Filtering functionality testing
     */
    public function testFiltering()
    {
        return view('artflow-table::test-pages.filtering-test', [
            'title' => 'AF Table - Filtering Test',
            'description' => 'Testing various filter types and combinations'
        ]);
    }

    /**
     * Search functionality testing
     */
    public function testSearch()
    {
        return view('artflow-table::test-pages.search-test', [
            'title' => 'AF Table - Search Test',
            'description' => 'Testing search functionality across columns and relations'
        ]);
    }

    /**
     * Sorting functionality testing
     */
    public function testSorting()
    {
        return view('artflow-table::test-pages.sorting-test', [
            'title' => 'AF Table - Sorting Test',
            'description' => 'Testing sorting functionality on various column types'
        ]);
    }

    /**
     * Security testing
     */
    public function testSecurity()
    {
        return view('artflow-table::test-pages.security-test', [
            'title' => 'AF Table - Security Test',
            'description' => 'Testing security features and input validation'
        ]);
    }

    /**
     * Validation testing
     */
    public function testValidation()
    {
        return view('artflow-table::test-pages.validation-test', [
            'title' => 'AF Table - Validation Test',
            'description' => 'Testing input validation and error handling'
        ]);
    }

    /**
     * API endpoint to get test data
     */
    public function getTestData(Request $request, string $model)
    {
        $modelClass = $this->getModelClass($model);
        
        if (!$modelClass) {
            return response()->json(['error' => 'Invalid model'], 404);
        }

        $query = $modelClass::query();
        
        if ($request->has('limit')) {
            $query->limit($request->get('limit', 100));
        }

        $data = $query->get();

        return response()->json([
            'model' => $model,
            'count' => $data->count(),
            'data' => $data
        ]);
    }

    /**
     * Test export data endpoint
     */
    public function testExportData(Request $request, string $model)
    {
        $modelClass = $this->getModelClass($model);
        
        if (!$modelClass) {
            return response()->json(['error' => 'Invalid model'], 404);
        }

        // This would typically trigger an export job
        return response()->json([
            'message' => 'Export initiated',
            'model' => $model,
            'format' => $request->get('format', 'csv')
        ]);
    }

    /**
     * Health check endpoint
     */
    public function healthCheck()
    {
        $status = [
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'database' => $this->checkDatabase(),
            'tables' => $this->checkTables(),
            'models' => $this->checkModels(),
        ];

        return response()->json($status);
    }

    /**
     * Documentation page
     */
    public function documentation()
    {
        return view('artflow-table::docs.index', [
            'title' => 'AF Table - Documentation',
            'description' => 'Complete documentation for AF Table package'
        ]);
    }

    /**
     * Examples page
     */
    public function examples()
    {
        return view('artflow-table::docs.examples', [
            'title' => 'AF Table - Examples',
            'description' => 'Code examples and implementation guides'
        ]);
    }

    /**
     * Changelog page
     */
    public function changelog()
    {
        return view('artflow-table::docs.changelog', [
            'title' => 'AF Table - Changelog',
            'description' => 'Version history and updates'
        ]);
    }

    /**
     * Get model class from string
     */
    protected function getModelClass(string $model): ?string
    {
        $models = [
            'departments' => 'ArtflowStudio\Table\Testing\Models\TestDepartment',
            'users' => 'ArtflowStudio\Table\Testing\Models\TestUser',
            'projects' => 'ArtflowStudio\Table\Testing\Models\TestProject',
            'tasks' => 'ArtflowStudio\Table\Testing\Models\TestTask',
            'posts' => TestPost::class,
            'categories' => TestCategory::class,
            'comments' => TestComment::class,
            'tags' => TestTag::class,
        ];

        return $models[$model] ?? null;
    }

    /**
     * Check database connection
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'connected', 'driver' => config('database.default')];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Check if test tables exist
     */
    protected function checkTables(): array
    {
        $tables = ['af_test_departments', 'af_test_users', 'af_test_projects', 'af_test_tasks'];
        $status = [];

        foreach ($tables as $table) {
            try {
                $exists = Schema::hasTable($table);
                $count = $exists ? DB::table($table)->count() : 0;
                $status[$table] = ['exists' => $exists, 'count' => $count];
            } catch (\Exception $e) {
                $status[$table] = ['exists' => false, 'error' => $e->getMessage()];
            }
        }

        return $status;
    }

    /**
     * Check if test models are available
     */
    protected function checkModels(): array
    {
        $models = [
            'TestDepartment' => 'ArtflowStudio\Table\Testing\Models\TestDepartment',
            'TestUser' => 'ArtflowStudio\Table\Testing\Models\TestUser',
            'TestProject' => 'ArtflowStudio\Table\Testing\Models\TestProject',
            'TestTask' => 'ArtflowStudio\Table\Testing\Models\TestTask',
        ];

        $status = [];
        foreach ($models as $name => $class) {
            $status[$name] = ['exists' => class_exists($class)];
        }

        return $status;
    }
}
