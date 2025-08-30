<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AF Table - Projects Test</title>
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
                <a class="nav-link" href="{{ route('af-table.test.users') }}">Users</a>
                <a class="nav-link" href="{{ route('af-table.test.projects') }}" aria-current="page">Projects</a>
                <a class="nav-link" href="{{ route('af-table.test.tasks') }}">Tasks</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-project-diagram me-2"></i>Projects Test</h1>
                    <a href="{{ route('af-table.test.dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Projects Datatable - Trait Version</h5>
                        <small class="text-muted">Testing complex data, JSON fields, and multiple relationships</small>
                    </div>
                    <div class="card-body">
                        @livewire('test-datatable-projects', [
                            'model' => \ArtflowStudio\Table\Testing\Models\TestProject::class,
                            'columns' => [
                                'id' => 'ID',
                                'name' => 'Project Name',
                                'code' => 'Code',
                                'status' => 'Status',
                                'priority' => 'Priority',
                                'department.name' => 'Department',
                                'manager.full_name' => 'Manager',
                                'budget' => 'Budget',
                                'progress_percentage' => 'Progress',
                                'start_date' => 'Start Date',
                                'deadline' => 'Deadline',
                                'is_confidential' => 'Confidential'
                            ],
                            'with' => ['department', 'manager'],
                            'searchable' => ['name', 'code', 'description', 'client_name'],
                            'sortable' => ['id', 'name', 'code', 'status', 'priority', 'budget', 'progress_percentage', 'start_date', 'deadline'],
                            'filterable' => [
                                'status' => ['planning', 'active', 'on_hold', 'completed', 'cancelled'],
                                'priority' => ['low', 'medium', 'high', 'critical'],
                                'department_id' => function() {
                                    return \ArtflowStudio\Table\Testing\Models\TestDepartment::pluck('name', 'id')->toArray();
                                },
                                'is_confidential' => ['Yes' => 1, 'No' => 0]
                            ],
                            'exportable' => true,
                            'selectable' => true,
                            'actions' => [
                                'view' => ['icon' => 'fas fa-eye', 'class' => 'btn-primary btn-sm'],
                                'tasks' => ['icon' => 'fas fa-tasks', 'class' => 'btn-info btn-sm'],
                                'edit' => ['icon' => 'fas fa-edit', 'class' => 'btn-warning btn-sm'],
                                'archive' => ['icon' => 'fas fa-archive', 'class' => 'btn-secondary btn-sm']
                            ],
                            'query' => [
                                function($query) {
                                    return $query->with(['department', 'manager', 'tasks']);
                                }
                            ],
                            'perPageOptions' => [10, 25, 50, 100],
                            'defaultPerPage' => 25,
                            'defaultSort' => 'deadline',
                            'defaultSortDirection' => 'asc'
                        ])
                    </div>
                </div>

                <!-- JSON and Complex Data Tests -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Complex Data & JSON Field Tests</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6>JSON Fields:</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">✓ Technologies array</li>
                                            <li class="list-group-item">✓ Requirements array</li>
                                            <li class="list-group-item">✓ Deliverables object</li>
                                            <li class="list-group-item">✓ JSON search capability</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6>Calculations:</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">✓ Budget utilization</li>
                                            <li class="list-group-item">✓ Remaining budget</li>
                                            <li class="list-group-item">✓ Progress percentage</li>
                                            <li class="list-group-item">✓ Overdue detection</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6>Relationships:</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">✓ Department (belongsTo)</li>
                                            <li class="list-group-item">✓ Manager (belongsTo)</li>
                                            <li class="list-group-item">✓ Tasks (hasMany)</li>
                                            <li class="list-group-item">✓ Eager loading</li>
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
