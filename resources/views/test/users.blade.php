<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AF Table - Users Test</title>
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
                <a class="nav-link" href="{{ route('af-table.test.departments') }}">Departments</a>
                <a class="nav-link" href="{{ route('af-table.test.users') }}" aria-current="page">Users</a>
                <a class="nav-link" href="{{ route('af-table.test.projects') }}">Projects</a>
                <a class="nav-link" href="{{ route('af-table.test.tasks') }}">Tasks</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-users me-2"></i>Users Test</h1>
                    <a href="{{ route('af-table.test.dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Users Datatable - Trait Version</h5>
                        <small class="text-muted">Testing relationships and complex data with TestUser model</small>
                    </div>
                    <div class="card-body">
                        @livewire('test-datatable-users', [
                            'model' => \ArtflowStudio\Table\Testing\Models\TestUser::class,
                            'columns' => [
                                'id' => 'ID',
                                'first_name' => 'First Name',
                                'last_name' => 'Last Name',
                                'email' => 'Email',
                                'department.name' => 'Department',
                                'position' => 'Position',
                                'salary' => 'Salary',
                                'hire_date' => 'Hire Date',
                                'status' => 'Status',
                                'is_manager' => 'Manager'
                            ],
                            'with' => ['department'],
                            'searchable' => ['first_name', 'last_name', 'email', 'position'],
                            'sortable' => ['id', 'first_name', 'last_name', 'email', 'salary', 'hire_date', 'status'],
                            'filterable' => [
                                'status' => ['active', 'inactive', 'on_leave', 'terminated'],
                                'department_id' => function() {
                                    return \ArtflowStudio\Table\Testing\Models\TestDepartment::pluck('name', 'id')->toArray();
                                },
                                'is_manager' => ['Yes' => 1, 'No' => 0]
                            ],
                            'exportable' => true,
                            'selectable' => true,
                            'actions' => [
                                'profile' => ['icon' => 'fas fa-user', 'class' => 'btn-info btn-sm'],
                                'edit' => ['icon' => 'fas fa-edit', 'class' => 'btn-warning btn-sm'],
                                'deactivate' => ['icon' => 'fas fa-ban', 'class' => 'btn-danger btn-sm']
                            ],
                            'query' => [
                                function($query) {
                                    return $query->with(['department']);
                                }
                            ],
                            'perPageOptions' => [15, 30, 50, 100],
                            'defaultPerPage' => 30,
                            'defaultSort' => 'last_name',
                            'defaultSortDirection' => 'asc'
                        ])
                    </div>
                </div>

                <!-- Relationship Tests -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Relationship & Advanced Feature Tests</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Relationship Features:</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">✓ Department relationship loading</li>
                                            <li class="list-group-item">✓ Eager loading with 'with' parameter</li>
                                            <li class="list-group-item">✓ Nested column sorting (department.name)</li>
                                            <li class="list-group-item">✓ Relationship-based filtering</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Advanced Features:</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">✓ JSON attributes handling</li>
                                            <li class="list-group-item">✓ Date formatting</li>
                                            <li class="list-group-item">✓ Boolean filtering (is_manager)</li>
                                            <li class="list-group-item">✓ Salary range filtering</li>
                                        </ul>
                                    </div>
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
</body>
</html>
