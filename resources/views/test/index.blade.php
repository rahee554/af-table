@extends('artflow-table::test.layout')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="test-header">
        <h1><i class="fas fa-vial me-2"></i>AFTable Performance Test Suite</h1>
        <p>Comprehensive testing environment with 118,100+ records across 10 tables</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <h4>118,100+</h4>
                <p><i class="fas fa-database me-1"></i>Total Test Records</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h4>10</h4>
                <p><i class="fas fa-table me-1"></i>Interconnected Tables</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h4>15+</h4>
                <p><i class="fas fa-link me-1"></i>Complex Relationships</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h4>4</h4>
                <p><i class="fas fa-bolt me-1"></i>Performance Tests</p>
            </div>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Test Environment:</strong> This page demonstrates AFTable with realistic data including employees, projects, tasks, departments, and complex relationships. 
        Use the tabs below to switch between different test scenarios.
    </div>

    <!-- Test Tabs -->
    <div class="test-card">
        <ul class="nav nav-pills test-tabs mb-4" id="testTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="employees-tab" data-bs-toggle="pill" data-bs-target="#employees" 
                        type="button" role="tab" wire:click="$set('currentTest', 'employees')">
                    <i class="fas fa-users me-2"></i>Employees (10,000)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="projects-tab" data-bs-toggle="pill" data-bs-target="#projects" 
                        type="button" role="tab" wire:click="$set('currentTest', 'projects')">
                    <i class="fas fa-project-diagram me-2"></i>Projects (2,000)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tasks-tab" data-bs-toggle="pill" data-bs-target="#tasks" 
                        type="button" role="tab" wire:click="$set('currentTest', 'tasks')">
                    <i class="fas fa-tasks me-2"></i>Tasks (15,000)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="timesheets-tab" data-bs-toggle="pill" data-bs-target="#timesheets" 
                        type="button" role="tab" wire:click="$set('currentTest', 'timesheets')">
                    <i class="fas fa-clock me-2"></i>Timesheets (50,000)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="foreach-tab" data-bs-toggle="pill" data-bs-target="#foreach_demo"
                        type="button" role="tab">
                    <i class="fas fa-list me-2"></i>Foreach / Static Data
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="custom-template-tab" data-bs-toggle="pill" data-bs-target="#custom_template"
                        type="button" role="tab">
                    <i class="fas fa-code me-2"></i>Custom Template
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="print-demo-tab" data-bs-toggle="pill" data-bs-target="#print_demo"
                        type="button" role="tab">
                    <i class="fas fa-print me-2"></i>Print Demo
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="performance-tab" data-bs-toggle="pill" data-bs-target="#performance" 
                        type="button" role="tab" wire:click="$set('currentTest', 'performance')">
                    <i class="fas fa-tachometer-alt me-2"></i>Performance Tests
                </button>
            </li>
        </ul>

        <div class="tab-content" id="testTabsContent">
            <!-- Employees Tab -->
            <div class="tab-pane fade show active" id="employees" role="tabpanel">
                <h3 class="mb-3"><i class="fas fa-users me-2"></i>Employee Management Test</h3>
                <p class="text-muted mb-4">Testing with 10,000 employees including relationships, JSON columns, and complex filters</p>
                
                @livewire('test-table-component', ['testType' => 'employees'])
            </div>

            <!-- Projects Tab -->
            <div class="tab-pane fade" id="projects" role="tabpanel">
                <h3 class="mb-3"><i class="fas fa-project-diagram me-2"></i>Project Management Test</h3>
                <p class="text-muted mb-4">Testing with 2,000 projects including many-to-many relationships and JSON milestones</p>
                
                @livewire('test-table-component', ['testType' => 'projects'])
            </div>

            <!-- Tasks Tab -->
            <div class="tab-pane fade" id="tasks" role="tabpanel">
                <h3 class="mb-3"><i class="fas fa-tasks me-2"></i>Task Management Test</h3>
                <p class="text-muted mb-4">Testing with 15,000 tasks including nested relationships and date ranges</p>
                
                @livewire('test-table-component', ['testType' => 'tasks'])
            </div>

            <!-- Timesheets Tab -->
            <div class="tab-pane fade" id="timesheets" role="tabpanel">
                <h3 class="mb-3"><i class="fas fa-clock me-2"></i>Timesheet Tracking Test</h3>
                <p class="text-muted mb-4">Testing with 50,000 timesheet records - performance stress test</p>
                
                @livewire('test-table-component', ['testType' => 'timesheets'])
            </div>

            <!-- Foreach / Static Data Demo Tab -->
            <div class="tab-pane fade" id="foreach_demo" role="tabpanel">
                <h3 class="mb-3"><i class="fas fa-list me-2"></i>Foreach / Static Data Demo</h3>
                <p class="text-muted mb-4">Demonstrates passing a plain PHP array/Collection to AFTable — no Eloquent model required. Includes live PHP calculations in <code>raw</code> columns and <code>vars</code> accessible inside templates.</p>

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>How it works:</strong> Pass <code>data: $collection</code> instead of <code>model:</code>. Search, sort, filter, pagination, and column visibility all work on in-memory data. Use <code>raw</code> columns for PHP expressions like <code>@{{ $row->price * $row->quantity }}</code>. Extra variables are passed via <code>vars:</code> and accessible as <code>$vars['currency']</code> inside <code>raw</code> templates.
                </div>

                @livewire('test-table-component', ['testType' => 'foreach_demo'])
            </div>

            <!-- Custom Template Tab -->
            <div class="tab-pane fade" id="custom_template" role="tabpanel">
                <h3 class="mb-3"><i class="fas fa-code me-2"></i>Custom Template Test</h3>
                <p class="text-muted mb-4">Demonstrates the <code>&#64;aftable / &#64;endaftable</code> paired directives that let you pass a fully custom <code>&lt;table&gt;</code> element with your own CSS classes.</p>

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>How it works:</strong> Wrap the table config inside <code>&#64;aftable([...])</code> and <code>&#64;endaftable</code>. The HTML between the directives becomes the outer <code>&lt;table&gt;</code> markup — you control classes, ID, and inline styles. All sort/search/pagination still render inside it.
                    <br><br>
                    <strong>Example code:</strong>
                    <pre class="mt-2 mb-0 p-2 bg-light rounded small"><code>&#64;aftable(['model' => App\Models\User::class, 'columns' => [...]])
    &lt;table class="table table-hover table-sm border my-custom-theme"&gt;
        &lt;thead class="table-dark"&gt;&lt;tr&gt;&lt;/tr&gt;&lt;/thead&gt;
        &lt;tbody&gt;&lt;/tbody&gt;
    &lt;/table&gt;
&#64;endaftable</code></pre>
                </div>

                @livewire('test-table-component', ['testType' => 'custom_template'])
            </div>

            <!-- Print Demo Tab -->
            <div class="tab-pane fade" id="print_demo" role="tabpanel">
                <h3 class="mb-3"><i class="fas fa-print me-2"></i>Print Demo</h3>
                <p class="text-muted mb-4">Demonstrates the isolated print window system. Click the <strong>Print</strong> button in the toolbar to open a clean pop-up window containing only the table — no surrounding layout.</p>

                <div class="alert alert-success mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>printConfig options:</strong>
                    <code>title</code>, <code>header</code> (custom HTML), <code>footer</code>, <code>fontSize</code> (pt), <code>orientation</code> (portrait/landscape), <code>paperSize</code> (A4/Letter/…), <code>showDate</code>, <code>showPageNum</code>, <code>tableClass</code>.
                    <br><br>
                    This demo uses <strong>landscape A4</strong>, <strong>11pt</strong> font, a company header note, and shows the date + page number.
                </div>

                <div class="alert alert-warning mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Make sure <strong>pop-ups are allowed</strong> for this page in your browser — the isolated print window opens in a new pop-up.
                </div>

                @livewire('test-table-component', ['testType' => 'print_demo'])
            </div>

            <!-- Performance Tab -->
            <div class="tab-pane fade" id="performance" role="tabpanel">
                <h3 class="mb-3"><i class="fas fa-tachometer-alt me-2"></i>Performance Analysis</h3>
                <p class="text-muted mb-4">Run comprehensive performance tests to validate optimizations</p>
                
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Performance Tests:</strong> Click the button below to run all performance tests via command line.
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-bolt me-2"></i>Query Caching Test
                            </div>
                            <div class="card-body">
                                <p>Tests query result caching with hash-based invalidation</p>
                                <ul class="small">
                                    <li>Initial render query count</li>
                                    <li>Cache hit verification (0 queries)</li>
                                    <li>Cache invalidation on search</li>
                                    <li>Manual cache clearing</li>
                                </ul>
                                <span class="badge bg-success">Expected: 70-80% reduction</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-database me-2"></i>Distinct Values Test
                            </div>
                            <div class="card-body">
                                <p>Tests distinct values preloading and caching</p>
                                <ul class="small">
                                    <li>Preload on mount verification</li>
                                    <li>Single query per filter</li>
                                    <li>Multiple calls return cache</li>
                                    <li>Persistence check</li>
                                </ul>
                                <span class="badge bg-success">Expected: 90% reduction</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <i class="fas fa-link me-2"></i>N+1 Prevention Test
                            </div>
                            <div class="card-body">
                                <p>Tests eager loading and N+1 query detection</p>
                                <ul class="small">
                                    <li>Relation detection accuracy</li>
                                    <li>Eager loading verification</li>
                                    <li>Iteration query count</li>
                                    <li>N+1 risk score calculation</li>
                                </ul>
                                <span class="badge bg-success">Expected: 95% reduction</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-filter me-2"></i>Filter Consolidation Test
                            </div>
                            <div class="card-body">
                                <p>Tests duplicate filter detection and consolidation</p>
                                <ul class="small">
                                    <li>Filter configuration validation</li>
                                    <li>Query structure analysis</li>
                                    <li>Duplicate WHERE detection</li>
                                    <li>Efficiency percentage</li>
                                </ul>
                                <span class="badge bg-success">Expected: 50% reduction</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button class="btn btn-lg btn-primary" onclick="runPerformanceTests()">
                        <i class="fas fa-play me-2"></i>Run All Performance Tests
                    </button>
                </div>

                <div id="testResults" class="mt-4" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-terminal me-2"></i>Test Results
                        </div>
                        <div class="card-body">
                            <pre id="testOutput" class="mb-0" style="max-height: 400px; overflow-y: auto;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Documentation Link -->
    <div class="text-center mt-4">
        <a href="{{ asset('vendor/artflow-studio/table/TESTING_ENVIRONMENT.md') }}" class="btn btn-outline-primary" target="_blank">
            <i class="fas fa-book me-2"></i>View Full Documentation
        </a>
    </div>
</div>

@push('scripts')
<script>
function runPerformanceTests() {
    const resultsDiv = document.getElementById('testResults');
    const outputPre = document.getElementById('testOutput');
    
    resultsDiv.style.display = 'block';
    outputPre.textContent = 'Running performance tests...\n\n';
    
    // Simulate running command (in real scenario, this would trigger an API call)
    fetch('/aftable/test/run-performance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        outputPre.textContent = data.output || 'Tests completed. Check terminal for detailed results.';
    })
    .catch(error => {
        outputPre.textContent = 'Error running tests. Please run manually:\nphp artisan af-table:test-trait --suite=performance';
    });
}
</script>
@endpush
@endsection
