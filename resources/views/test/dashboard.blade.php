<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AF Table - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    @livewireStyles
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('af-table.test.dashboard') }}">
                <i class="fas fa-table me-2"></i>AF Table Testing Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('af-table.test.departments') }}">Departments</a>
                <a class="nav-link" href="{{ route('af-table.test.users') }}">Users</a>
                <a class="nav-link" href="{{ route('af-table.test.projects') }}">Projects</a>
                <a class="nav-link" href="{{ route('af-table.test.tasks') }}">Tasks</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">AF Table Testing Dashboard</h1>
                
                <div class="row">
                    <!-- Departments Card -->
                    <div class="col-md-3 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title">Departments</h4>
                                        <p class="card-text">{{ $stats['departments'] ?? 0 }}</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-building fa-2x"></i>
                                    </div>
                                </div>
                                <a href="{{ route('af-table.test.departments') }}" class="btn btn-light btn-sm mt-2">
                                    View All <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Users Card -->
                    <div class="col-md-3 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title">Users</h4>
                                        <p class="card-text">{{ $stats['users'] ?? 0 }}</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                                <a href="{{ route('af-table.test.users') }}" class="btn btn-light btn-sm mt-2">
                                    View All <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Projects Card -->
                    <div class="col-md-3 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title">Projects</h4>
                                        <p class="card-text">{{ $stats['projects'] ?? 0 }}</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-project-diagram fa-2x"></i>
                                    </div>
                                </div>
                                <a href="{{ route('af-table.test.projects') }}" class="btn btn-light btn-sm mt-2">
                                    View All <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks Card -->
                    <div class="col-md-3 mb-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="card-title">Tasks</h4>
                                        <p class="card-text">{{ $stats['tasks'] ?? 0 }}</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-tasks fa-2x"></i>
                                    </div>
                                </div>
                                <a href="{{ route('af-table.test.tasks') }}" class="btn btn-light btn-sm mt-2">
                                    View All <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Features Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Test Features</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="list-group">
                                            <div class="list-group-item list-group-item-action active">
                                                <strong>Core Features</strong>
                                            </div>
                                            <a href="{{ route('af-table.test.sorting') }}" class="list-group-item list-group-item-action">
                                                <i class="fas fa-sort me-2"></i>Sorting Test
                                            </a>
                                            <a href="{{ route('af-table.test.filtering') }}" class="list-group-item list-group-item-action">
                                                <i class="fas fa-filter me-2"></i>Filtering Test
                                            </a>
                                            <a href="{{ route('af-table.test.search') }}" class="list-group-item list-group-item-action">
                                                <i class="fas fa-search me-2"></i>Search Test
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="list-group">
                                            <div class="list-group-item list-group-item-action active">
                                                <strong>Advanced Features</strong>
                                            </div>
                                            <a href="{{ route('af-table.test.export') }}" class="list-group-item list-group-item-action">
                                                <i class="fas fa-download me-2"></i>Export Test
                                            </a>
                                            <a href="{{ route('af-table.test.bulk-actions') }}" class="list-group-item list-group-item-action">
                                                <i class="fas fa-check-square me-2"></i>Bulk Actions
                                            </a>
                                            <a href="{{ route('af-table.test.column-visibility') }}" class="list-group-item list-group-item-action">
                                                <i class="fas fa-eye me-2"></i>Column Visibility
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="list-group">
                                            <div class="list-group-item list-group-item-action active">
                                                <strong>Performance</strong>
                                            </div>
                                            <a href="{{ route('af-table.test.memory') }}" class="list-group-item list-group-item-action">
                                                <i class="fas fa-memory me-2"></i>Memory Test
                                            </a>
                                            <a href="{{ route('af-table.test.caching') }}" class="list-group-item list-group-item-action">
                                                <i class="fas fa-tachometer-alt me-2"></i>Caching Test
                                            </a>
                                            <a href="{{ route('af-table.test.performance') }}" class="list-group-item list-group-item-action">
                                                <i class="fas fa-chart-line me-2"></i>Performance Test
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Endpoints -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">API Endpoints</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Method</th>
                                                <th>Endpoint</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><span class="badge bg-primary">GET</span></td>
                                                <td>/api/af-table/departments</td>
                                                <td>Get departments data</td>
                                                <td><a href="/api/af-table/departments" target="_blank" class="btn btn-sm btn-outline-primary">Test</a></td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-primary">GET</span></td>
                                                <td>/api/af-table/users</td>
                                                <td>Get users data</td>
                                                <td><a href="/api/af-table/users" target="_blank" class="btn btn-sm btn-outline-primary">Test</a></td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-primary">GET</span></td>
                                                <td>/api/af-table/projects</td>
                                                <td>Get projects data</td>
                                                <td><a href="/api/af-table/projects" target="_blank" class="btn btn-sm btn-outline-primary">Test</a></td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-primary">GET</span></td>
                                                <td>/api/af-table/tasks</td>
                                                <td>Get tasks data</td>
                                                <td><a href="/api/af-table/tasks" target="_blank" class="btn btn-sm btn-outline-primary">Test</a></td>
                                            </tr>
                                        </tbody>
                                    </table>
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
