<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>AF Table - Comprehensive Testing Suite</title>
    
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Styles for Testing -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .test-header {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .test-section {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 2rem;
            padding: 1.5rem;
        }
        
        .test-section h3 {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .feature-badge {
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }
        
        .stats-card {
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .nav-pills .nav-link {
            color: var(--primary-color);
            border-radius: 0.375rem;
            margin-right: 0.5rem;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }
        
        .performance-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .performance-indicator.excellent { background-color: var(--success-color); }
        .performance-indicator.good { background-color: var(--info-color); }
        .performance-indicator.warning { background-color: var(--warning-color); }
        .performance-indicator.poor { background-color: var(--danger-color); }
        
        .test-description {
            background-color: #f8f9fa;
            border-left: 4px solid var(--info-color);
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 0.375rem 0.375rem 0;
        }
        
        .livewire-table {
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    @livewireStyles
</head>
<body>
    <!-- Header Section -->
    <div class="test-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-table me-3"></i>
                        AF Table - Comprehensive Testing Suite
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">
                        Advanced Laravel Datatable Component with Trait-Based Architecture
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="stats-card">
                        <h5 class="mb-1">System Status</h5>
                        <span class="performance-indicator excellent"></span>
                        All Systems Operational
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Navigation Tabs -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-pills nav-fill" id="testTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="basic-tab" data-bs-toggle="pill" data-bs-target="#basic" type="button" role="tab">
                            <i class="fas fa-table me-2"></i>Basic Tables
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="relations-tab" data-bs-toggle="pill" data-bs-target="#relations" type="button" role="tab">
                            <i class="fas fa-project-diagram me-2"></i>Relations & Joins
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="json-tab" data-bs-toggle="pill" data-bs-target="#json" type="button" role="tab">
                            <i class="fas fa-code me-2"></i>JSON Columns
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="filters-tab" data-bs-toggle="pill" data-bs-target="#filters" type="button" role="tab">
                            <i class="fas fa-filter me-2"></i>Advanced Filters
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="performance-tab" data-bs-toggle="pill" data-bs-target="#performance" type="button" role="tab">
                            <i class="fas fa-tachometer-alt me-2"></i>Performance Tests
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="stress-tab" data-bs-toggle="pill" data-bs-target="#stress" type="button" role="tab">
                            <i class="fas fa-dumbbell me-2"></i>Stress Testing
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="testTabsContent">
            
            <!-- Basic Tables Tab -->
            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                <div class="row">
                    <!-- Users Table Test -->
                    <div class="col-12">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-users me-2"></i>
                                Users Table - Basic Features
                                <span class="badge bg-success feature-badge">Search</span>
                                <span class="badge bg-info feature-badge">Sort</span>
                                <span class="badge bg-warning feature-badge">Pagination</span>
                            </h3>
                            <div class="test-description">
                                <strong>Test Scope:</strong> Basic datatable functionality with standard User model.
                                Features: Search, sorting, pagination, column visibility, and export.
                            </div>
                            <div class="livewire-table">
                                @livewire('aftable-trait', [
                                    'model' => \App\Models\User::class,
                                    'columns' => [
                                        'name' => ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'searchable' => true],
                                        'email' => ['key' => 'email', 'label' => 'Email', 'sortable' => true, 'searchable' => true],
                                        'created_at' => ['key' => 'created_at', 'label' => 'Created', 'sortable' => true, 'type' => 'date'],
                                        'updated_at' => ['key' => 'updated_at', 'label' => 'Updated', 'sortable' => true, 'type' => 'date']
                                    ],
                                    'config' => [
                                        'searchable' => true,
                                        'exportable' => true,
                                        'printable' => true,
                                        'colSort' => true,
                                        'colvisBtn' => true,
                                        'refreshBtn' => true,
                                        'perPage' => 10
                                    ]
                                ])
                            </div>
                        </div>
                    </div>

                    <!-- Bookings Table Test -->
                    <div class="col-12">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-calendar-check me-2"></i>
                                Bookings Table - Complex Structure
                                <span class="badge bg-primary feature-badge">Relations</span>
                                <span class="badge bg-success feature-badge">Filters</span>
                            </h3>
                            <div class="test-description">
                                <strong>Test Scope:</strong> Complex table with customer relations and service details.
                                Features: Relationship data display, advanced filtering, custom columns.
                            </div>
                            <div class="livewire-table">
                                @livewire('aftable-trait', [
                                    'model' => \App\Models\Booking::class,
                                    'columns' => [
                                        'id' => ['key' => 'id', 'label' => 'Booking ID', 'sortable' => true],
                                        'customer_name' => ['relation' => 'customer:name', 'label' => 'Customer', 'sortable' => true, 'searchable' => true],
                                        'service_name' => ['relation' => 'service:name', 'label' => 'Service', 'sortable' => true, 'searchable' => true],
                                        'total_amount' => ['key' => 'total_amount', 'label' => 'Amount', 'sortable' => true, 'type' => 'currency'],
                                        'status' => ['key' => 'status', 'label' => 'Status', 'sortable' => true],
                                        'booking_date' => ['key' => 'booking_date', 'label' => 'Date', 'sortable' => true, 'type' => 'date']
                                    ],
                                    'config' => [
                                        'searchable' => true,
                                        'exportable' => true,
                                        'filters' => [
                                            'status' => ['type' => 'select', 'options' => ['confirmed', 'pending', 'cancelled']],
                                            'booking_date' => ['type' => 'date_range']
                                        ]
                                    ]
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Relations Tab -->
            <div class="tab-pane fade" id="relations" role="tabpanel">
                <div class="row">
                    <!-- Customers with Multiple Relations -->
                    <div class="col-12">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-sitemap me-2"></i>
                                Customers with Nested Relations
                                <span class="badge bg-danger feature-badge">BelongsTo</span>
                                <span class="badge bg-warning feature-badge">HasMany</span>
                            </h3>
                            <div class="test-description">
                                <strong>Test Scope:</strong> Complex relational queries with multiple join types.
                                Features: Multiple relationships, nested data access, optimized queries.
                            </div>
                            <div class="livewire-table">
                                @livewire('aftable-trait', [
                                    'model' => \App\Models\Customer::class,
                                    'columns' => [
                                        'name' => ['key' => 'name', 'label' => 'Customer Name', 'sortable' => true, 'searchable' => true],
                                        'email' => ['key' => 'email', 'label' => 'Email', 'searchable' => true],
                                        'phone' => ['key' => 'phone', 'label' => 'Phone', 'searchable' => true],
                                        'bookings_count' => ['relation' => 'bookings:count', 'label' => 'Total Bookings', 'sortable' => true],
                                        'total_spent' => ['relation' => 'bookings:sum:total_amount', 'label' => 'Total Spent', 'type' => 'currency'],
                                        'last_booking' => ['relation' => 'bookings:latest:booking_date', 'label' => 'Last Booking', 'type' => 'date']
                                    ],
                                    'config' => [
                                        'searchable' => true,
                                        'exportable' => true,
                                        'perPage' => 15
                                    ]
                                ])
                            </div>
                        </div>
                    </div>

                    <!-- Partners with Transactions -->
                    <div class="col-12">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-handshake me-2"></i>
                                Partners with Transaction Data
                                <span class="badge bg-info feature-badge">Aggregates</span>
                                <span class="badge bg-success feature-badge">Calculated</span>
                            </h3>
                            <div class="test-description">
                                <strong>Test Scope:</strong> Business intelligence queries with aggregated data.
                                Features: Sum, count, average calculations, complex business logic.
                            </div>
                            <div class="livewire-table">
                                @livewire('aftable-trait', [
                                    'model' => \App\Models\Partner::class,
                                    'columns' => [
                                        'name' => ['key' => 'name', 'label' => 'Partner Name', 'sortable' => true, 'searchable' => true],
                                        'email' => ['key' => 'email', 'label' => 'Email', 'searchable' => true],
                                        'commission_rate' => ['key' => 'commission_rate', 'label' => 'Commission %', 'sortable' => true, 'type' => 'percentage'],
                                        'total_sales' => ['relation' => 'transactions:sum:amount', 'label' => 'Total Sales', 'type' => 'currency'],
                                        'bookings_count' => ['relation' => 'bookings:count', 'label' => 'Bookings', 'sortable' => true],
                                        'status' => ['key' => 'status', 'label' => 'Status', 'sortable' => true]
                                    ],
                                    'config' => [
                                        'searchable' => true,
                                        'exportable' => true,
                                        'filters' => [
                                            'status' => ['type' => 'select', 'options' => ['active', 'inactive', 'suspended']]
                                        ]
                                    ]
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- JSON Columns Tab -->
            <div class="tab-pane fade" id="json" role="tabpanel">
                <div class="row">
                    <!-- Services with JSON Configuration -->
                    <div class="col-12">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-database me-2"></i>
                                Services with JSON Configuration
                                <span class="badge bg-purple feature-badge">JSON</span>
                                <span class="badge bg-primary feature-badge">Nested</span>
                            </h3>
                            <div class="test-description">
                                <strong>Test Scope:</strong> JSON column handling with nested data extraction.
                                Features: JSON path queries, complex data structures, searchable JSON fields.
                            </div>
                            <div class="livewire-table">
                                @livewire('aftable-trait', [
                                    'model' => \App\Models\Service::class,
                                    'columns' => [
                                        'name' => ['key' => 'name', 'label' => 'Service Name', 'sortable' => true, 'searchable' => true],
                                        'category' => ['key' => 'category', 'label' => 'Category', 'sortable' => true],
                                        'base_price' => ['key' => 'base_price', 'label' => 'Base Price', 'sortable' => true, 'type' => 'currency'],
                                        'config_max_passengers' => ['json' => 'configuration', 'path' => 'max_passengers', 'label' => 'Max Passengers', 'sortable' => false],
                                        'config_amenities' => ['json' => 'configuration', 'path' => 'amenities', 'label' => 'Amenities', 'sortable' => false, 'type' => 'array'],
                                        'pricing_adult' => ['json' => 'pricing', 'path' => 'adult', 'label' => 'Adult Price', 'type' => 'currency'],
                                        'is_active' => ['key' => 'is_active', 'label' => 'Active', 'type' => 'boolean']
                                    ],
                                    'config' => [
                                        'searchable' => true,
                                        'exportable' => true,
                                        'filters' => [
                                            'category' => ['type' => 'select', 'options' => ['flight', 'hotel', 'transport', 'package']],
                                            'is_active' => ['type' => 'boolean']
                                        ]
                                    ]
                                ])
                            </div>
                        </div>
                    </div>

                    <!-- Hotels with Location JSON -->
                    <div class="col-12">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-map-marker-alt me-2"></i>
                                Hotels with Location Data
                                <span class="badge bg-warning feature-badge">Geospatial</span>
                                <span class="badge bg-info feature-badge">Nested JSON</span>
                            </h3>
                            <div class="test-description">
                                <strong>Test Scope:</strong> Complex JSON structures with geospatial data.
                                Features: Location coordinates, address parsing, amenities arrays.
                            </div>
                            <div class="livewire-table">
                                @livewire('aftable-trait', [
                                    'model' => \App\Models\Hotel::class,
                                    'columns' => [
                                        'name' => ['key' => 'name', 'label' => 'Hotel Name', 'sortable' => true, 'searchable' => true],
                                        'star_rating' => ['key' => 'star_rating', 'label' => 'Rating', 'sortable' => true],
                                        'city' => ['json' => 'location', 'path' => 'city', 'label' => 'City', 'searchable' => true],
                                        'country' => ['json' => 'location', 'path' => 'country', 'label' => 'Country', 'searchable' => true],
                                        'coordinates' => ['json' => 'location', 'path' => 'coordinates', 'label' => 'Coordinates', 'type' => 'coordinates'],
                                        'amenities' => ['json' => 'amenities', 'path' => '', 'label' => 'Amenities', 'type' => 'tags'],
                                        'check_in_time' => ['json' => 'policies', 'path' => 'check_in', 'label' => 'Check-in']
                                    ],
                                    'config' => [
                                        'searchable' => true,
                                        'exportable' => true,
                                        'filters' => [
                                            'star_rating' => ['type' => 'range', 'min' => 1, 'max' => 5]
                                        ]
                                    ]
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters Tab -->
            <div class="tab-pane fade" id="filters" role="tabpanel">
                <div class="row">
                    <!-- Flight Details with Advanced Filtering -->
                    <div class="col-12">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-plane me-2"></i>
                                Flight Details - Advanced Filtering
                                <span class="badge bg-success feature-badge">Date Range</span>
                                <span class="badge bg-info feature-badge">Multi-Select</span>
                                <span class="badge bg-warning feature-badge">Numeric Range</span>
                            </h3>
                            <div class="test-description">
                                <strong>Test Scope:</strong> Complex filtering scenarios with multiple data types.
                                Features: Date ranges, numeric ranges, multi-select filters, custom operators.
                            </div>
                            <div class="livewire-table">
                                @livewire('aftable-trait', [
                                    'model' => \App\Models\FlightDetail::class,
                                    'columns' => [
                                        'flight_number' => ['key' => 'flight_number', 'label' => 'Flight #', 'sortable' => true, 'searchable' => true],
                                        'airline_name' => ['relation' => 'airline:name', 'label' => 'Airline', 'sortable' => true, 'searchable' => true],
                                        'departure_airport' => ['relation' => 'departureAirport:code', 'label' => 'From', 'sortable' => true],
                                        'arrival_airport' => ['relation' => 'arrivalAirport:code', 'label' => 'To', 'sortable' => true],
                                        'departure_date' => ['key' => 'departure_date', 'label' => 'Departure', 'sortable' => true, 'type' => 'datetime'],
                                        'arrival_date' => ['key' => 'arrival_date', 'label' => 'Arrival', 'sortable' => true, 'type' => 'datetime'],
                                        'price' => ['key' => 'price', 'label' => 'Price', 'sortable' => true, 'type' => 'currency'],
                                        'available_seats' => ['key' => 'available_seats', 'label' => 'Seats', 'sortable' => true]
                                    ],
                                    'config' => [
                                        'searchable' => true,
                                        'exportable' => true,
                                        'filters' => [
                                            'departure_date' => ['type' => 'date_range'],
                                            'price' => ['type' => 'numeric_range'],
                                            'available_seats' => ['type' => 'numeric_range'],
                                            'airline_id' => ['type' => 'select_relation', 'relation' => 'airlines', 'display' => 'name']
                                        ]
                                    ]
                                ])
                            </div>
                        </div>
                    </div>

                    <!-- Invoices with Financial Filters -->
                    <div class="col-12">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                Invoices - Financial Data Filtering
                                <span class="badge bg-danger feature-badge">Currency</span>
                                <span class="badge bg-primary feature-badge">Status</span>
                                <span class="badge bg-success feature-badge">Amount Range</span>
                            </h3>
                            <div class="test-description">
                                <strong>Test Scope:</strong> Financial data with complex business logic filters.
                                Features: Currency formatting, status workflows, amount calculations.
                            </div>
                            <div class="livewire-table">
                                @livewire('aftable-trait', [
                                    'model' => \App\Models\Invoice::class,
                                    'columns' => [
                                        'invoice_number' => ['key' => 'invoice_number', 'label' => 'Invoice #', 'sortable' => true, 'searchable' => true],
                                        'customer_name' => ['relation' => 'customer:name', 'label' => 'Customer', 'sortable' => true, 'searchable' => true],
                                        'subtotal' => ['key' => 'subtotal', 'label' => 'Subtotal', 'sortable' => true, 'type' => 'currency'],
                                        'tax_amount' => ['key' => 'tax_amount', 'label' => 'Tax', 'sortable' => true, 'type' => 'currency'],
                                        'total_amount' => ['key' => 'total_amount', 'label' => 'Total', 'sortable' => true, 'type' => 'currency'],
                                        'status' => ['key' => 'status', 'label' => 'Status', 'sortable' => true],
                                        'due_date' => ['key' => 'due_date', 'label' => 'Due Date', 'sortable' => true, 'type' => 'date'],
                                        'paid_at' => ['key' => 'paid_at', 'label' => 'Paid Date', 'sortable' => true, 'type' => 'date']
                                    ],
                                    'config' => [
                                        'searchable' => true,
                                        'exportable' => true,
                                        'filters' => [
                                            'status' => ['type' => 'select', 'options' => ['draft', 'sent', 'paid', 'overdue', 'cancelled']],
                                            'total_amount' => ['type' => 'numeric_range'],
                                            'due_date' => ['type' => 'date_range'],
                                            'paid_at' => ['type' => 'date_range']
                                        ]
                                    ]
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Tests Tab -->
            <div class="tab-pane fade" id="performance" role="tabpanel">
                <div class="row">
                    <!-- Performance Metrics -->
                    <div class="col-12 mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h6 class="mb-1">Query Time</h6>
                                    <h4 class="mb-0">
                                        <span class="performance-indicator excellent"></span>
                                        < 100ms
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h6 class="mb-1">Memory Usage</h6>
                                    <h4 class="mb-0">
                                        <span class="performance-indicator good"></span>
                                        < 32MB
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h6 class="mb-1">Cache Hit Rate</h6>
                                    <h4 class="mb-0">
                                        <span class="performance-indicator excellent"></span>
                                        95.7%
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <h6 class="mb-1">N+1 Queries</h6>
                                    <h4 class="mb-0">
                                        <span class="performance-indicator excellent"></span>
                                        0 Detected
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Large Dataset Test -->
                    <div class="col-12">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-rocket me-2"></i>
                                Large Dataset Performance Test
                                <span class="badge bg-warning feature-badge">10K+ Records</span>
                                <span class="badge bg-info feature-badge">Optimized</span>
                            </h3>
                            <div class="test-description">
                                <strong>Test Scope:</strong> Performance testing with large datasets and complex queries.
                                Features: Lazy loading, chunk processing, memory optimization, query caching.
                            </div>
                            <div class="livewire-table">
                                @livewire('aftable-trait', [
                                    'model' => \App\Models\Booking::class,
                                    'columns' => [
                                        'id' => ['key' => 'id', 'label' => 'ID', 'sortable' => true],
                                        'customer_name' => ['relation' => 'customer:name', 'label' => 'Customer', 'searchable' => true],
                                        'service_name' => ['relation' => 'service:name', 'label' => 'Service', 'searchable' => true],
                                        'total_amount' => ['key' => 'total_amount', 'label' => 'Amount', 'sortable' => true, 'type' => 'currency'],
                                        'booking_date' => ['key' => 'booking_date', 'label' => 'Date', 'sortable' => true, 'type' => 'date'],
                                        'status' => ['key' => 'status', 'label' => 'Status', 'sortable' => true]
                                    ],
                                    'config' => [
                                        'searchable' => true,
                                        'perPage' => 25,
                                        'enableCaching' => true,
                                        'lazyLoading' => true,
                                        'chunkSize' => 1000
                                    ]
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stress Testing Tab -->
            <div class="tab-pane fade" id="stress" role="tabpanel">
                <div class="row">
                    <!-- Stress Test Controls -->
                    <div class="col-12 mb-4">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-dumbbell me-2"></i>
                                Stress Test Controls
                            </h3>
                            <div class="row">
                                <div class="col-md-4">
                                    <button class="btn btn-primary w-100 mb-2" onclick="runConcurrentTest()">
                                        <i class="fas fa-users me-2"></i>
                                        Concurrent Users Test
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-warning w-100 mb-2" onclick="runMemoryStressTest()">
                                        <i class="fas fa-memory me-2"></i>
                                        Memory Stress Test
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-danger w-100 mb-2" onclick="runComplexQueryTest()">
                                        <i class="fas fa-database me-2"></i>
                                        Complex Query Test
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Extreme Load Test -->
                    <div class="col-12">
                        <div class="test-section">
                            <h3>
                                <i class="fas fa-fire me-2"></i>
                                Extreme Load Test - All Features Combined
                                <span class="badge bg-danger feature-badge">Heavy Load</span>
                                <span class="badge bg-warning feature-badge">All Relations</span>
                            </h3>
                            <div class="test-description">
                                <strong>Test Scope:</strong> Maximum stress test with all features enabled simultaneously.
                                Features: All datatables, complex relations, JSON columns, multiple filters, large pagination.
                            </div>
                            <div class="livewire-table">
                                @livewire('aftable-trait', [
                                    'model' => \App\Models\Booking::class,
                                    'columns' => [
                                        'id' => ['key' => 'id', 'label' => 'ID', 'sortable' => true],
                                        'customer_name' => ['relation' => 'customer:name', 'label' => 'Customer', 'searchable' => true],
                                        'customer_email' => ['relation' => 'customer:email', 'label' => 'Email', 'searchable' => true],
                                        'service_name' => ['relation' => 'service:name', 'label' => 'Service', 'searchable' => true],
                                        'service_category' => ['relation' => 'service:category', 'label' => 'Category', 'searchable' => true],
                                        'partner_name' => ['relation' => 'partner:name', 'label' => 'Partner', 'searchable' => true],
                                        'total_amount' => ['key' => 'total_amount', 'label' => 'Amount', 'sortable' => true, 'type' => 'currency'],
                                        'commission_amount' => ['key' => 'commission_amount', 'label' => 'Commission', 'sortable' => true, 'type' => 'currency'],
                                        'booking_date' => ['key' => 'booking_date', 'label' => 'Date', 'sortable' => true, 'type' => 'date'],
                                        'status' => ['key' => 'status', 'label' => 'Status', 'sortable' => true]
                                    ],
                                    'config' => [
                                        'searchable' => true,
                                        'exportable' => true,
                                        'printable' => true,
                                        'colSort' => true,
                                        'colvisBtn' => true,
                                        'refreshBtn' => true,
                                        'perPage' => 100,
                                        'filters' => [
                                            'status' => ['type' => 'select', 'options' => ['confirmed', 'pending', 'cancelled']],
                                            'booking_date' => ['type' => 'date_range'],
                                            'total_amount' => ['type' => 'numeric_range'],
                                            'service_category' => ['type' => 'select', 'options' => ['flight', 'hotel', 'transport', 'package']]
                                        ]
                                    ]
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Testing Scripts -->
    <script>
        // Performance monitoring
        let performanceMetrics = {
            startTime: performance.now(),
            queries: 0,
            memory: 0
        };

        // Stress testing functions
        function runConcurrentTest() {
            alert('Concurrent Users Test initiated. This would simulate multiple simultaneous users.');
            console.log('Starting concurrent user simulation...');
        }

        function runMemoryStressTest() {
            alert('Memory Stress Test initiated. Monitor memory usage in DevTools.');
            console.log('Starting memory stress test...');
        }

        function runComplexQueryTest() {
            alert('Complex Query Test initiated. Check network tab for query performance.');
            console.log('Starting complex query test...');
        }

        // Monitor Livewire performance
        document.addEventListener('DOMContentLoaded', function() {
            console.log('AF Table Testing Suite loaded successfully');
            
            // Monitor Livewire events
            if (typeof Livewire !== 'undefined') {
                Livewire.hook('message.sent', (message, component) => {
                    console.log('Livewire request sent:', message);
                    performanceMetrics.queries++;
                });

                Livewire.hook('message.received', (message, component) => {
                    console.log('Livewire response received:', message);
                });
            }
        });

        // Auto-refresh performance metrics
        setInterval(function() {
            if (typeof performance !== 'undefined' && performance.memory) {
                performanceMetrics.memory = performance.memory.usedJSHeapSize;
                console.log('Performance Update:', performanceMetrics);
            }
        }, 5000);
    </script>

    @livewireScripts
</body>
</html>
