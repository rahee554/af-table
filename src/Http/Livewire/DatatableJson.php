<?php

namespace ArtflowStudio\Table\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Session;

class DatatableJson extends Component
{
    use WithPagination;

    //*----------- Properties -----------*//
    public $jsonUrl, $columns, $visibleColumns = [],
        $checkbox = false, $search = '', $sortColumn = null, $sortDirection = 'asc', $selectedRows = [],
        $selectAll = false, $filters = [], $filterColumn = null, $filterOperator = '=', $filterValue = null, $dateColumn = null,
        $startDate = null, $endDate = null, $selectedColumn = null, $numberOperator = '=', $distinctValues = [], $columnType = null,
        $actions = [];
    public $index = true; // Show index column by default
    public $tableId = null; // Add unique table identifier
    public $query = null; // Custom query constraints
    public $headers = []; // HTTP headers for API requests
    public $queryParams = []; // Additional query parameters for API

    //*----------- Optional Configuration -----------*//
    public $searchable = true, $exportable = false, $printable = false, $colSort = true,
        $sort = 'desc', // For Initial sorting
        $refreshBtn = false; //refresh Button - false by default, can be enabled by passing true

    //*----------- Query String Parameters -----------*//
    public $queryString = [
        'records' => ['except' => 10], // Default to 10 if not set
        'search' => ['except' => ''],
        'sortColumn' => ['except' => null],
        'sortDirection' => ['except' => 'asc'],
        'filterColumn' => ['except' => null],
        'filterValue' => ['except' => null],
        'filterOperator' => ['except' => '='],
        'startDate' => ['except' => null],
        'endDate' => ['except' => null],
    ];

    //*----------- Event Listeners -----------*//
    protected $listeners = [
        'dateRangeSelected' => 'applyDateRange',
        'refreshTable' => '$refresh',
    ];

    //*----------- Component Initialization -----------*//
    public function mount($jsonUrl, $columns, $filters = [], $actions = [], $index = true, $tableId = null, $query = null, $headers = [], $queryParams = [])
    {
        $this->jsonUrl = $jsonUrl;
        $this->tableId = $tableId ?? 'datatable_json_' . uniqid();
        $this->query = $query; // Store custom query constraints
        $this->headers = $headers; // HTTP headers for API requests
        $this->queryParams = $queryParams; // Additional query parameters

        // Re-key columns by 'key'
        $this->columns = collect($columns)->mapWithKeys(function ($column) {
            return [$column['key'] => $column];
        })->toArray();

        // Session key for column visibility (unique per table and tableId)
        $sessionKey = $this->getColumnVisibilitySessionKey();

        // Try to load column visibility from session, else default
        $sessionVisibility = Session::get($sessionKey);
        if (is_array($sessionVisibility)) {
            $this->visibleColumns = $this->getValidatedVisibleColumns($sessionVisibility);
            Session::put($sessionKey, $this->visibleColumns);
        } else {
            $this->visibleColumns = $this->getDefaultVisibleColumns();
            Session::put($sessionKey, $this->visibleColumns);
        }

        $this->filters = $filters;
        $this->actions = $actions;
        $this->index = $index;

        if (empty($this->sortColumn)) {
            $first = collect($columns)->first();
            $this->sortColumn = $first['key'];
            $this->sortDirection = $this->sort;
        }
    }

    //*----------- Column Visibility Management -----------*//
    protected function getDefaultVisibleColumns()
    {
        return collect($this->columns)->mapWithKeys(function ($column) {
            return [$column['key'] => empty($column['hide'])];
        })->toArray();
    }

    protected function getValidatedVisibleColumns($sessionVisibility)
    {
        $validSessionVisibility = [];
        foreach ($this->columns as $columnKey => $columnConfig) {
            if (array_key_exists($columnKey, $sessionVisibility)) {
                $validSessionVisibility[$columnKey] = $sessionVisibility[$columnKey];
            } else {
                $validSessionVisibility[$columnKey] = empty($columnConfig['hide']);
            }
        }
        return $validSessionVisibility;
    }

    public function toggleColumnVisibility($columnKey)
    {
        $this->visibleColumns[$columnKey] = !$this->visibleColumns[$columnKey];
        Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);
    }

    protected function getColumnVisibilitySessionKey()
    {
        // Use model class name and tableId for uniqueness
        $modelName = is_string($this->model) ? $this->model : (is_object($this->model) ? get_class($this->model) : 'datatable');
        
        // Include user ID for session isolation - prevents data leakage between users
        $userId = $this->getUserIdentifierForSession();
        
        return 'datatable_visible_columns_' . md5($modelName . '_' . static::class . '_' . $this->tableId . '_' . $userId);
    }

    /**
     * Get user identifier for session isolation
     */
    protected function getUserIdentifierForSession()
    {
        // Try different auth methods in order of preference
        if (function_exists('auth') && auth()->check()) {
            return 'user_' . auth()->id();
        }
        
        if (function_exists('request') && request()->ip()) {
            // Fallback to session ID + IP for guest users
            return 'guest_' . md5(session()->getId() . '_' . request()->ip());
        }
        
        // Final fallback to session ID only
        return 'session_' . session()->getId();
    }

    public function clearColumnVisibilitySession()
    {
        $sessionKey = $this->getColumnVisibilitySessionKey();
        Session::forget($sessionKey);

        // Reset to defaults
        $this->visibleColumns = $this->getDefaultVisibleColumns();
        Session::put($sessionKey, $this->visibleColumns);
    }

    //*----------- JSON Data Fetching -----------*//
    protected function fetchJsonData()
    {
        try {
            // Build query parameters for the API request
            $params = array_merge($this->queryParams, [
                'page' => $this->getPage(),
                'per_page' => $this->perPage ?? 10,
                'search' => $this->search,
                'sort_column' => $this->sortColumn,
                'sort_direction' => $this->sortDirection,
            ]);

            // Apply custom query constraints
            if ($this->query && is_array($this->query)) {
                foreach ($this->query as $index => $constraint) {
                    if (is_array($constraint)) {
                        if (count($constraint) === 3) {
                            [$column, $operator, $value] = $constraint;
                            $params["query[{$index}][column]"] = $column;
                            $params["query[{$index}][operator]"] = $operator;
                            $params["query[{$index}][value]"] = $value;
                        } elseif (count($constraint) === 2) {
                            [$column, $value] = $constraint;
                            $params["query[{$index}][column]"] = $column;
                            $params["query[{$index}][operator]"] = '=';
                            $params["query[{$index}][value]"] = $value;
                        }
                    }
                }
            }

            // Apply filters
            if ($this->filterColumn && $this->filterValue) {
                $params['filter_column'] = $this->filterColumn;
                $params['filter_operator'] = $this->filterOperator;
                $params['filter_value'] = $this->filterValue;
            }

            // Apply date range filter
            if ($this->dateColumn && $this->startDate && $this->endDate) {
                $params['date_column'] = $this->dateColumn;
                $params['start_date'] = $this->startDate;
                $params['end_date'] = $this->endDate;
            }

            // Make HTTP request
            $response = Http::withHeaders($this->headers)->get($this->jsonUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                return $this->transformJsonResponse($data);
            } else {
                logger()->error('AFTable JSON API error: ' . $response->body());
                return $this->getEmptyPaginatedData();
            }
        } catch (\Exception $e) {
            logger()->error('AFTable JSON fetch error: ' . $e->getMessage());
            return $this->getEmptyPaginatedData();
        }
    }

    protected function transformJsonResponse($data)
    {
        // Handle different JSON response formats
        if (isset($data['data']) && isset($data['pagination'])) {
            // Format: { data: [...], pagination: { current_page, last_page, total, etc } }
            return (object) [
                'data' => collect($data['data'])->map(function ($item) {
                    return (object) $item;
                }),
                'current_page' => $data['pagination']['current_page'] ?? 1,
                'last_page' => $data['pagination']['last_page'] ?? 1,
                'per_page' => $data['pagination']['per_page'] ?? $this->records,
                'total' => $data['pagination']['total'] ?? count($data['data']),
                'from' => $data['pagination']['from'] ?? 1,
                'to' => $data['pagination']['to'] ?? count($data['data']),
            ];
        } elseif (isset($data['current_page'])) {
            // Laravel pagination format
            return (object) [
                'data' => collect($data['data'] ?? [])->map(function ($item) {
                    return (object) $item;
                }),
                'current_page' => $data['current_page'],
                'last_page' => $data['last_page'],
                'per_page' => $data['per_page'],
                'total' => $data['total'],
                'from' => $data['from'],
                'to' => $data['to'],
            ];
        } else {
            // Simple array format - create pagination manually
            $items = collect($data)->map(function ($item) {
                return (object) $item;
            });
            
            $page = $this->getPage();
            $perPage = $this->records;
            $total = $items->count();
            $offset = ($page - 1) * $perPage;
            $paginatedItems = $items->slice($offset, $perPage);

            return (object) [
                'data' => $paginatedItems,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ];
        }
    }

    protected function getEmptyPaginatedData()
    {
        return (object) [
            'data' => collect([]),
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => $this->records,
            'total' => 0,
            'from' => 0,
            'to' => 0,
        ];
    }

    //*----------- Search Functionality -----------*//
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function refreshTable()
    {
        $this->resetPage();
        $this->search = '';
    }

    public function updatedrecords()
    {
        $this->resetPage();
    }

    //*----------- Sorting Functionality -----------*//
    public function toggleSort($column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    //*----------- Filter Management -----------*//
    public function updatedFilterValue()
    {
        $this->resetPage();
    }

    public function updatedFilterColumn()
    {
        $this->filterValue = null;
        $this->resetPage();
    }

    public function updatedSelectedColumn($column)
    {
        $filterDetails = $this->filters[$column] ?? null;

        if ($filterDetails) {
            $this->selectedColumn = $column;
            $this->columnType = $filterDetails['type'];

            // For JSON data, fetch distinct values via API
            $this->distinctValues = $this->getDistinctValuesFromApi($column);
        }
    }

    protected function getDistinctValuesFromApi($column)
    {
        try {
            $params = array_merge($this->queryParams, [
                'distinct_column' => $column,
                'action' => 'distinct_values'
            ]);

            $response = Http::withHeaders($this->headers)->get($this->jsonUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                return $data['values'] ?? [];
            }
        } catch (\Exception $e) {
            logger()->error('AFTable distinct values API error: ' . $e->getMessage());
        }

        return [];
    }

    public function applyDateRange($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->resetPage();
    }

    protected function getDefaultOperator($filterType)
    {
        switch ($filterType) {
            case 'select':
                return '=';
            case 'integer':
            case 'number':
                return '=';
            case 'date':
                return '=';
            case 'text':
            default:
                return 'LIKE';
        }
    }

    //*----------- Export Functionality -----------*//
    public function export($format)
    {
        try {
            $params = array_merge($this->queryParams, [
                'export' => true,
                'format' => $format,
                'search' => $this->search,
                'sort_column' => $this->sortColumn,
                'sort_direction' => $this->sortDirection,
            ]);

            // Apply filters to export
            if ($this->filterColumn && $this->filterValue) {
                $params['filter_column'] = $this->filterColumn;
                $params['filter_operator'] = $this->filterOperator;
                $params['filter_value'] = $this->filterValue;
            }

            // Apply custom query constraints
            if ($this->query && is_array($this->query)) {
                foreach ($this->query as $index => $constraint) {
                    if (is_array($constraint)) {
                        if (count($constraint) === 3) {
                            [$column, $operator, $value] = $constraint;
                            $params["query[{$index}][column]"] = $column;
                            $params["query[{$index}][operator]"] = $operator;
                            $params["query[{$index}][value]"] = $value;
                        } elseif (count($constraint) === 2) {
                            [$column, $value] = $constraint;
                            $params["query[{$index}][column]"] = $column;
                            $params["query[{$index}][operator]"] = '=';
                            $params["query[{$index}][value]"] = $value;
                        }
                    }
                }
            }

            $response = Http::withHeaders($this->headers)->get($this->jsonUrl, $params);

            if ($response->successful()) {
                $filename = "export_{$format}_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
                
                return response()->streamDownload(function () use ($response) {
                    echo $response->body();
                }, $filename);
            }
        } catch (\Exception $e) {
            logger()->error('AFTable export error: ' . $e->getMessage());
            session()->flash('error', 'Export failed. Please try again.');
        }
    }

    //*----------- Utility Methods -----------*//
    public function renderRawHtml($rawTemplate, $row)
    {
        return $this->renderRawHtml($rawTemplate, $row); // Use secure method
    }

    public function getDynamicClass($column, $row)
    {
        $classes = [];
        if (isset($column['classCondition']) && is_array($column['classCondition'])) {
            foreach ($column['classCondition'] as $class => $condition) {
                if (is_callable($condition) && $condition($row)) {
                    $classes[] = $class;
                }
            }
        }
        return implode(' ', $classes);
    }

    //*----------- Component Render -----------*//
    public function render()
    {
        $data = $this->fetchJsonData();

        return view('artflow-studio.table::datatable', [
            'data' => $data,
            'filters' => $this->filters,
            'columns' => $this->columns,
            'visibleColumns' => $this->visibleColumns,
            'checkbox' => $this->checkbox,
            'actions' => $this->actions,
            'searchable' => $this->searchable,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
            'records' => $this->records,
            'index' => $this->index,
        ]);
    }
}
