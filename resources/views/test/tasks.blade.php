<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AF Table - Tasks Test</title>
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
                <a class="nav-link" href="{{ route('af-table.test.projects') }}">Projects</a>
                <a class="nav-link" href="{{ route('af-table.test.tasks') }}" aria-current="page">Tasks</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-tasks me-2"></i>Tasks Test</h1>
                    <a href="{{ route('af-table.test.dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tasks Datatable - Trait Version</h5>
                        <small class="text-muted">Testing high-volume data and performance optimizations</small>
                    </div>
                    <div class="card-body">
                        @livewire('test-datatable-tasks', [
                            'model' => \ArtflowStudio\Table\Testing\Models\TestTask::class,
                            'columns' => [
                                'id' => 'ID',
                                'title' => 'Task Title',
                                'status' => 'Status',
                                'priority' => 'Priority',
                                'project.name' => 'Project',
                                'assignedUser.full_name' => 'Assigned To',
                                'estimated_hours' => 'Est. Hours',
                                'actual_hours' => 'Actual Hours',
                                'completion_percentage' => 'Progress',
                                'due_date' => 'Due Date',
                                'is_billable' => 'Billable'
                            ],
                            'with' => ['project', 'assignedUser'],
                            'searchable' => ['title', 'description', 'category'],
                            'sortable' => ['id', 'title', 'status', 'priority', 'estimated_hours', 'actual_hours', 'completion_percentage', 'due_date'],
                            'filterable' => [
                                'status' => ['pending', 'in_progress', 'review', 'completed', 'cancelled'],
                                'priority' => ['low', 'medium', 'high', 'critical'],
                                'project_id' => function() {
                                    return \ArtflowStudio\Table\Testing\Models\TestProject::pluck('name', 'id')->take(20)->toArray();
                                },
                                'assigned_to' => function() {
                                    return \ArtflowStudio\Table\Testing\Models\TestUser::selectRaw("id, CONCAT(first_name, ' ', last_name) as full_name")
                                        ->pluck('full_name', 'id')->take(20)->toArray();
                                },
                                'is_billable' => ['Yes' => 1, 'No' => 0],
                                'difficulty_level' => ['easy', 'medium', 'hard', 'expert']
                            ],
                            'exportable' => true,
                            'selectable' => true,
                            'actions' => [
                                'view' => ['icon' => 'fas fa-eye', 'class' => 'btn-primary btn-sm'],
                                'edit' => ['icon' => 'fas fa-edit', 'class' => 'btn-warning btn-sm'],
                                'complete' => ['icon' => 'fas fa-check', 'class' => 'btn-success btn-sm'],
                                'delete' => ['icon' => 'fas fa-trash', 'class' => 'btn-danger btn-sm']
                            ],
                            'query' => [
                                function($query) {
                                    return $query->with(['project.department', 'assignedUser.department']);
                                }
                            ],
                            'perPageOptions' => [25, 50, 100, 200],
                            'defaultPerPage' => 50,
                            'defaultSort' => 'due_date',
                            'defaultSortDirection' => 'asc'
                        ])
                    </div>
                </div>

                <!-- Performance and High-Volume Tests -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Performance & High-Volume Data Tests</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <h6>Performance Features:</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">✓ Memory management</li>
                                            <li class="list-group-item">✓ Query optimization</li>
                                            <li class="list-group-item">✓ Caching layer</li>
                                            <li class="list-group-item">✓ Chunked processing</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-3">
                                        <h6>Complex Queries:</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">✓ Nested relationships</li>
                                            <li class="list-group-item">✓ Deep eager loading</li>
                                            <li class="list-group-item">✓ Conditional loading</li>
                                            <li class="list-group-item">✓ Aggregated data</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-3">
                                        <h6>Data Volume:</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">✓ Large datasets</li>
                                            <li class="list-group-item">✓ Pagination efficiency</li>
                                            <li class="list-group-item">✓ Search performance</li>
                                            <li class="list-group-item">✓ Export handling</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-3">
                                        <h6>JSON Processing:</h6>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">✓ Dependencies array</li>
                                            <li class="list-group-item">✓ Comments array</li>
                                            <li class="list-group-item">✓ Tags filtering</li>
                                            <li class="list-group-item">✓ Attachments handling</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-info" onclick="showMemoryStats()">
                                            <i class="fas fa-memory me-1"></i>Memory Stats
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="showCacheStats()">
                                            <i class="fas fa-tachometer-alt me-1"></i>Cache Stats
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" onclick="showQueryStats()">
                                            <i class="fas fa-database me-1"></i>Query Stats
                                        </button>
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

    <script>
        function showMemoryStats() {
            // This would integrate with the memory management trait
            alert('Memory usage stats would be displayed here');
        }

        function showCacheStats() {
            // This would integrate with the caching trait
            alert('Cache hit/miss stats would be displayed here');
        }

        function showQueryStats() {
            // This would integrate with the query builder trait
            alert('Query performance stats would be displayed here');
        }
    </script>
</body>
</html>
