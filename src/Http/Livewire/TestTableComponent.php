<?php

namespace ArtflowStudio\Table\Http\Livewire;

use Livewire\Component;

class TestTableComponent extends Component
{
    public $testType = 'employees';

    public function mount($testType = 'employees')
    {
        $this->testType = $testType;
    }

    public function render()
    {
        $config = $this->getTestConfig();

        return view('artflow-table::test.table-component', [
            'model' => $config['model'] ?? null,
            'data' => $config['data'] ?? null,
            'vars' => $config['vars'] ?? [],
            'columns' => $config['columns'],
            'filters' => $config['filters'],
            'actions' => $config['actions'],
            'tableId' => $config['tableId'],
            'printable' => $config['printable'] ?? true,
            'printConfig' => $config['printConfig'] ?? [],
            'checkbox' => $config['checkbox'] ?? true,
        ]);
    }

    protected function getTestConfig(): array
    {
        switch ($this->testType) {
            case 'employees':
                return $this->getEmployeeConfig();

            case 'projects':
                return $this->getProjectConfig();

            case 'tasks':
                return $this->getTaskConfig();

            case 'timesheets':
                return $this->getTimesheetConfig();

            case 'foreach_demo':
                return $this->getForeachDemoConfig();

            case 'custom_template':
                return $this->getCustomTemplateConfig();

            case 'print_demo':
                return $this->getPrintDemoConfig();

            default:
                return $this->getEmployeeConfig();
        }
    }

    protected function getEmployeeConfig(): array
    {
        return [
            'model' => '\\ArtflowStudio\\Table\\Tests\\Models\\TestEmployee',
            'tableId' => 'test_employees_table',
            'columns' => [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'sortable' => true,
                    'searchable' => false,
                ],
                [
                    'key' => 'employee_code',
                    'label' => 'Code',
                    'sortable' => true,
                    'searchable' => true,
                ],
                [
                    'key' => 'first_name',
                    'label' => 'First Name',
                    'sortable' => true,
                    'searchable' => true,
                ],
                [
                    'key' => 'last_name',
                    'label' => 'Last Name',
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
                    'key' => 'department_name',
                    'label' => 'Department',
                    'sortable' => true,
                    'searchable' => true,
                    'relation' => 'department:name',
                ],
                [
                    'key' => 'position',
                    'label' => 'Position',
                    'sortable' => true,
                    'searchable' => true,
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'sortable' => true,
                    'searchable' => false,
                    'raw' => '<span class="badge bg-{{ $row->status === "active" ? "success" : ($row->status === "inactive" ? "danger" : "warning") }}">{{ ucfirst($row->status) }}</span>',
                ],
                [
                    'key' => 'hire_date',
                    'label' => 'Hire Date',
                    'sortable' => true,
                    'searchable' => false,
                ],
                [
                    'key' => 'salary',
                    'label' => 'Salary',
                    'sortable' => true,
                    'searchable' => false,
                ],
            ],
            'filters' => [
                'status' => [
                    'type' => 'distinct',
                    'label' => 'Status',
                ],
                'department_id' => [
                    'type' => 'select',
                    'label' => 'Department',
                    'relation' => 'department:name',
                ],
                'position' => [
                    'type' => 'text',
                    'label' => 'Position',
                ],
                'salary' => [
                    'type' => 'number',
                    'label' => 'Salary',
                ],
                'hire_date' => [
                    'type' => 'date',
                    'label' => 'Hire Date',
                ],
            ],
            'actions' => [],
        ];
    }

    protected function getProjectConfig(): array
    {
        return [
            'model' => '\\ArtflowStudio\\Table\\Tests\\Models\\TestProject',
            'tableId' => 'test_projects_table',
            'columns' => [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'sortable' => true,
                ],
                [
                    'key' => 'project_code',
                    'label' => 'Code',
                    'sortable' => true,
                    'searchable' => true,
                ],
                [
                    'key' => 'name',
                    'label' => 'Project Name',
                    'sortable' => true,
                    'searchable' => true,
                ],
                [
                    'key' => 'company_name',
                    'label' => 'Company',
                    'sortable' => true,
                    'searchable' => true,
                    'relation' => 'company:name',
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'sortable' => true,
                    'raw' => '<span class="badge bg-{{ $row->status === "completed" ? "success" : ($row->status === "active" ? "primary" : "secondary") }}">{{ ucfirst($row->status) }}</span>',
                ],
                [
                    'key' => 'start_date',
                    'label' => 'Start Date',
                    'sortable' => true,
                ],
                [
                    'key' => 'end_date',
                    'label' => 'End Date',
                    'sortable' => true,
                ],
                [
                    'key' => 'budget',
                    'label' => 'Budget',
                    'sortable' => true,
                ],
            ],
            'filters' => [
                'status' => [
                    'type' => 'distinct',
                    'label' => 'Status',
                ],
                'company_id' => [
                    'type' => 'select',
                    'label' => 'Company',
                    'relation' => 'company:name',
                ],
                'budget' => [
                    'type' => 'number',
                    'label' => 'Budget',
                ],
            ],
            'actions' => [],
        ];
    }

    protected function getTaskConfig(): array
    {
        return [
            'model' => '\\ArtflowStudio\\Table\\Tests\\Models\\TestTask',
            'tableId' => 'test_tasks_table',
            'columns' => [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'sortable' => true,
                ],
                [
                    'key' => 'title',
                    'label' => 'Task',
                    'sortable' => true,
                    'searchable' => true,
                ],
                [
                    'key' => 'project_name',
                    'label' => 'Project',
                    'sortable' => true,
                    'searchable' => true,
                    'relation' => 'project:name',
                ],
                [
                    'key' => 'assignee_name',
                    'label' => 'Assigned To',
                    'sortable' => false,
                    'searchable' => false,
                    'relation' => 'assignee:first_name',
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'sortable' => true,
                    'raw' => '<span class="badge bg-{{ $row->status === "completed" ? "success" : ($row->status === "in_progress" ? "warning" : "secondary") }}">{{ str_replace("_", " ", ucfirst($row->status)) }}</span>',
                ],
                [
                    'key' => 'priority',
                    'label' => 'Priority',
                    'sortable' => true,
                    'raw' => '<span class="badge bg-{{ $row->priority === "high" ? "danger" : ($row->priority === "medium" ? "warning" : "info") }}">{{ ucfirst($row->priority) }}</span>',
                ],
                [
                    'key' => 'due_date',
                    'label' => 'Due Date',
                    'sortable' => true,
                ],
            ],
            'filters' => [
                'status' => [
                    'type' => 'distinct',
                    'label' => 'Status',
                ],
                'priority' => [
                    'type' => 'distinct',
                    'label' => 'Priority',
                ],
                'project_id' => [
                    'type' => 'select',
                    'label' => 'Project',
                    'relation' => 'project:name',
                ],
            ],
            'actions' => [],
        ];
    }

    protected function getForeachDemoConfig(): array
    {
        $categories = ['Electronics', 'Clothing', 'Food', 'Books', 'Toys'];
        $statuses = ['active', 'inactive', 'pending'];

        $data = collect(range(1, 100))->map(fn ($i) => [
            'id' => $i,
            'product' => 'Product '.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'category' => $categories[$i % 5],
            'price' => round(mt_rand(100, 99900) / 100, 2),
            'quantity' => mt_rand(1, 100),
            'status' => $statuses[$i % 3],
        ]);

        return [
            'model' => null,
            'data' => $data,
            'vars' => ['currency' => 'USD'],
            'tableId' => 'foreach_demo_table',
            'columns' => [
                [
                    'key' => 'id',
                    'label' => '#',
                    'sortable' => true,
                ],
                [
                    'key' => 'product',
                    'label' => 'Product',
                    'sortable' => true,
                    'searchable' => true,
                ],
                [
                    'key' => 'category',
                    'label' => 'Category',
                    'sortable' => true,
                    'searchable' => false,
                ],
                [
                    'key' => 'price',
                    'label' => 'Unit Price',
                    'sortable' => true,
                    'raw' => '{{ $vars["currency"] }} {{ number_format($row->price, 2) }}',
                ],
                [
                    'key' => 'quantity',
                    'label' => 'Qty',
                    'sortable' => true,
                ],
                [
                    'key' => 'total',
                    'label' => 'Total (Calc)',
                    'sortable' => false,
                    'raw' => '<strong>{{ $vars["currency"] }} {{ number_format($row->price * $row->quantity, 2) }}</strong>',
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'sortable' => true,
                    'raw' => '<span class="badge bg-{{ $row->status === "active" ? "success" : ($row->status === "inactive" ? "danger" : "warning") }}">{{ ucfirst($row->status) }}</span>',
                ],
            ],
            'filters' => [
                'category' => [
                    'type' => 'distinct',
                    'label' => 'Category',
                ],
                'status' => [
                    'type' => 'distinct',
                    'label' => 'Status',
                ],
            ],
            'actions' => [],
        ];
    }

    protected function getTimesheetConfig(): array
    {
        return [
            'model' => '\\ArtflowStudio\\Table\\Tests\\Models\\TestTimesheet',
            'tableId' => 'test_timesheets_table',
            'columns' => [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'sortable' => true,
                ],
                [
                    'key' => 'employee_name',
                    'label' => 'Employee',
                    'sortable' => false,
                    'searchable' => false,
                    'relation' => 'employee:first_name',
                ],
                [
                    'key' => 'task_title',
                    'label' => 'Task',
                    'sortable' => false,
                    'searchable' => false,
                    'relation' => 'task:title',
                ],
                [
                    'key' => 'date',
                    'label' => 'Date',
                    'sortable' => true,
                ],
                [
                    'key' => 'hours',
                    'label' => 'Hours',
                    'sortable' => true,
                ],
                [
                    'key' => 'is_billable',
                    'label' => 'Billable',
                    'sortable' => true,
                    'raw' => '<span class="badge bg-{{ $row->is_billable ? "success" : "secondary" }}">{{ $row->is_billable ? "Yes" : "No" }}</span>',
                ],
            ],
            'filters' => [
                'date' => [
                    'type' => 'date',
                    'label' => 'Date',
                ],
                'is_billable' => [
                    'type' => 'select',
                    'label' => 'Billable',
                ],
                'hours' => [
                    'type' => 'number',
                    'label' => 'Hours',
                ],
            ],
            'actions' => [],
        ];
    }

    protected function getCustomTemplateConfig(): array
    {
        return [
            'model' => '\\ArtflowStudio\\Table\\Tests\\Models\\TestEmployee',
            'tableId' => 'custom_template_table',
            'printable' => false,
            'printConfig' => [],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'searchable' => false],
                ['key' => 'employee_code', 'label' => 'Code', 'sortable' => true, 'searchable' => true],
                ['key' => 'first_name', 'label' => 'First Name', 'sortable' => true, 'searchable' => true],
                ['key' => 'last_name', 'label' => 'Last Name', 'sortable' => true, 'searchable' => true],
                ['key' => 'department_name', 'label' => 'Department', 'sortable' => true, 'relation' => 'department:name'],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'sortable' => true,
                    'raw' => '<span class="badge rounded-pill bg-{{ $row->status === "active" ? "success" : ($row->status === "on_leave" ? "warning" : "danger") }}">{{ ucfirst(str_replace("_", " ", $row->status)) }}</span>',
                ],
            ],
            'filters' => [
                'department_name' => ['type' => 'distinct', 'label' => 'Department'],
                'status' => ['type' => 'distinct', 'label' => 'Status'],
            ],
            'actions' => [],
        ];
    }

    protected function getPrintDemoConfig(): array
    {
        return [
            'model' => '\\ArtflowStudio\\Table\\Tests\\Models\\TestEmployee',
            'tableId' => 'print_demo_table',
            'printable' => true,
            'printConfig' => [
                'title'       => 'Employee Directory',
                'header'      => '<p style="color:#555;">Confidential — Internal Use Only</p>',
                'footer'      => 'ArtflowStudio LMS · HR Department',
                'fontSize'    => 11,
                'orientation' => 'landscape',
                'paperSize'   => 'A4',
                'showDate'    => true,
                'showPageNum' => true,
                'tableClass'  => 'table-striped',
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'searchable' => false],
                ['key' => 'employee_code', 'label' => 'Code', 'sortable' => true, 'searchable' => true],
                ['key' => 'first_name', 'label' => 'First Name', 'sortable' => true, 'searchable' => true],
                ['key' => 'last_name', 'label' => 'Last Name', 'sortable' => true, 'searchable' => true],
                ['key' => 'email', 'label' => 'Email', 'sortable' => true, 'searchable' => true],
                ['key' => 'department_name', 'label' => 'Department', 'sortable' => true, 'relation' => 'department:name'],
                ['key' => 'position', 'label' => 'Position', 'sortable' => true, 'searchable' => true],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'sortable' => true,
                    'raw' => '<span class="badge bg-{{ $row->status === "active" ? "success" : ($row->status === "on_leave" ? "warning" : "danger") }}">{{ ucfirst(str_replace("_", " ", $row->status)) }}</span>',
                ],
            ],
            'filters' => [
                'department_name' => ['type' => 'distinct', 'label' => 'Department'],
                'status' => ['type' => 'distinct', 'label' => 'Status'],
            ],
            'actions' => [],
        ];
    }
}
