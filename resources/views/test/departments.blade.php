<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AF Table - Departments Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    @livewireStyles
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('af-table.test.dashboard') }}">
                <i class="fas fa-table me-2"></i>AF Table Testing
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('af-table.test.departments') }}" aria-current="page">Departments</a>
                <a class="nav-link" href="{{ route('af-table.test.users') }}">Users</a>
                <a class="nav-link" href="{{ route('af-table.test.projects') }}">Projects</a>
                <a class="nav-link" href="{{ route('af-table.test.tasks') }}">Tasks</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-building me-2"></i>Departments Test</h1>
                    <a href="{{ route('af-table.test.dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Departments Datatable - Trait Version</h5>
                        <small class="text-muted">Testing all features with TestDepartment model</small>
                    </div>
                    <div class="card-body">
                        @livewire('test-datatable-departments', [
                            'model' => \ArtflowStudio\Table\Testing\Models\TestDepartment::class,
                            'columns' => [
                                'id' => 'ID',
                                'name' => 'Department Name',
                                'code' => 'Code',
                                'head_of_department' => 'Head',
                                'budget' => 'Budget',
                                'employee_count' => 'Employees',
                                'location' => 'Location',
                                'phone' => 'Phone',
                                'status' => 'Status',
                                'created_at' => 'Created'
                            ],
                            'searchable' => ['name', 'code', 'head_of_department', 'location'],
                            'sortable' => ['id', 'name', 'code', 'budget', 'employee_count', 'status', 'created_at'],
                            'filterable' => [
                                'status' => ['active', 'inactive', 'restructuring'],
                                'location' => function() {
                                    return \ArtflowStudio\Table\Testing\Models\TestDepartment::distinct('location')
                                        ->pluck('location')
                                        ->filter()
                                        ->sort()
                                        ->values()
                                        ->toArray();
                                }
                            ],
                            'exportable' => true,
                            'selectable' => true,
                            'actions' => [
                                'view' => ['icon' => 'fas fa-eye', 'class' => 'btn-primary btn-sm'],
                                'edit' => ['icon' => 'fas fa-edit', 'class' => 'btn-warning btn-sm'],
                                'delete' => ['icon' => 'fas fa-trash', 'class' => 'btn-danger btn-sm']
                            ],
                            'perPageOptions' => [10, 25, 50, 100],
                            'defaultPerPage' => 25,
                            'defaultSort' => 'name',
                            'defaultSortDirection' => 'asc'
                        ])
                    </div>
                </div>

                <!-- Feature Test Buttons -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Feature Tests</h6>
                            </div>
                            <div class="card-body">
                                <div class="btn-group flex-wrap" role="group">
                                    <button type="button" class="btn btn-outline-primary" onclick="testSorting()">
                                        <i class="fas fa-sort me-1"></i>Test Sorting
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="testFiltering()">
                                        <i class="fas fa-filter me-1"></i>Test Filtering
                                    </button>
                                    <button type="button" class="btn btn-outline-info" onclick="testSearch()">
                                        <i class="fas fa-search me-1"></i>Test Search
                                    </button>
                                    <button type="button" class="btn btn-outline-warning" onclick="testExport()">
                                        <i class="fas fa-download me-1"></i>Test Export
                                    </button>
                                    <button type="button" class="btn btn-outline-dark" onclick="testBulkActions()">
                                        <i class="fas fa-check-square me-1"></i>Test Bulk Actions
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts

    <script>
        function testSorting() {
            alert('Click on column headers to test sorting functionality');
        }

        function testFiltering() {
            alert('Use the filter dropdowns to test filtering functionality');
        }

        function testSearch() {
            alert('Use the search box to test search functionality');
        }

        function testExport() {
            alert('Click the export button to test export functionality');
        }

        function testBulkActions() {
            alert('Select rows using checkboxes and test bulk actions');
        }
    </script>
</body>
</html>
