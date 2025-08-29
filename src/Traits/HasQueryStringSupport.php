<?php

namespace ArtflowStudio\Table\Traits;

trait HasQueryStringSupport
{
    /**
     * Get query string parameters for current state
     */
    public function getQueryStringParams(): array
    {
        $params = [];

        // Add search
        if (!empty($this->search)) {
            $params['search'] = $this->search;
        }

        // Add sorting
        if (!empty($this->sortBy)) {
            $params['sort'] = $this->sortBy;
            $params['direction'] = $this->sortDirection;
        }

        // Add pagination
        if ($this->page && $this->page > 1) {
            $params['page'] = $this->page;
        }

        if ($this->perPage !== ($this->defaultPerPage ?? 10)) {
            $params['per_page'] = $this->perPage;
        }

        // Add filters
        if (!empty($this->filters)) {
            foreach ($this->filters as $columnKey => $filterValue) {
                if (!empty($filterValue)) {
                    $params['filter_' . $columnKey] = is_array($filterValue) ? implode(',', $filterValue) : $filterValue;
                }
            }
        }

        // Add visible columns if different from default
        $visibleColumns = array_keys(array_filter($this->visibleColumns));
        $defaultVisible = array_keys($this->columns);
        
        if ($visibleColumns !== $defaultVisible) {
            $params['columns'] = implode(',', $visibleColumns);
        }

        return $params;
    }

    /**
     * Load state from query string parameters
     */
    public function loadFromQueryString(array $params = null)
    {
        if ($params === null) {
            $params = request()->query();
        }

        // Load search
        if (isset($params['search'])) {
            $this->search = $params['search'];
        }

        // Load sorting
        if (isset($params['sort'])) {
            $this->sortBy = $params['sort'];
            $this->sortDirection = $params['direction'] ?? 'asc';
        }

        // Load pagination
        if (isset($params['page'])) {
            $this->page = (int) $params['page'];
        }

        if (isset($params['per_page'])) {
            $this->perPage = (int) $params['per_page'];
        }

        // Load filters
        foreach ($params as $key => $value) {
            if (str_starts_with($key, 'filter_')) {
                $columnKey = substr($key, 7); // Remove 'filter_' prefix
                
                if (isset($this->columns[$columnKey])) {
                    // Handle comma-separated values for multi-select filters
                    $this->filters[$columnKey] = str_contains($value, ',') ? explode(',', $value) : $value;
                }
            }
        }

        // Load visible columns
        if (isset($params['columns'])) {
            $columns = explode(',', $params['columns']);
            
            // Reset all to false first
            foreach ($this->visibleColumns as $key => $visible) {
                $this->visibleColumns[$key] = false;
            }
            
            // Set specified columns to true
            foreach ($columns as $columnKey) {
                if (isset($this->visibleColumns[$columnKey])) {
                    $this->visibleColumns[$columnKey] = true;
                }
            }
        }
    }

    /**
     * Generate URL with current state
     */
    public function generateUrl($baseUrl = null): string
    {
        if ($baseUrl === null) {
            $baseUrl = request()->url();
        }

        $params = $this->getQueryStringParams();
        
        if (empty($params)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Generate URL with modified parameters
     */
    public function generateUrlWith(array $modifications): string
    {
        $params = $this->getQueryStringParams();
        
        // Apply modifications
        foreach ($modifications as $key => $value) {
            if ($value === null) {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        $baseUrl = request()->url();
        
        if (empty($params)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Generate sort URL for a column
     */
    public function generateSortUrl($columnKey): string
    {
        $direction = 'asc';
        
        // If already sorting by this column, toggle direction
        if ($this->sortBy === $columnKey) {
            $direction = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        }

        return $this->generateUrlWith([
            'sort' => $columnKey,
            'direction' => $direction,
            'page' => null // Reset to first page when sorting
        ]);
    }

    /**
     * Generate filter URL
     */
    public function generateFilterUrl($columnKey, $value): string
    {
        $filterKey = 'filter_' . $columnKey;
        
        return $this->generateUrlWith([
            $filterKey => $value,
            'page' => null // Reset to first page when filtering
        ]);
    }

    /**
     * Generate clear filter URL
     */
    public function generateClearFilterUrl($columnKey = null): string
    {
        $modifications = ['page' => null];
        
        if ($columnKey) {
            // Clear specific filter
            $modifications['filter_' . $columnKey] = null;
        } else {
            // Clear all filters
            $params = $this->getQueryStringParams();
            foreach ($params as $key => $value) {
                if (str_starts_with($key, 'filter_')) {
                    $modifications[$key] = null;
                }
            }
        }

        return $this->generateUrlWith($modifications);
    }

    /**
     * Generate search URL
     */
    public function generateSearchUrl($searchTerm): string
    {
        return $this->generateUrlWith([
            'search' => $searchTerm,
            'page' => null // Reset to first page when searching
        ]);
    }

    /**
     * Generate clear search URL
     */
    public function generateClearSearchUrl(): string
    {
        return $this->generateUrlWith([
            'search' => null,
            'page' => null
        ]);
    }

    /**
     * Generate pagination URL
     */
    public function generatePaginationUrl($page): string
    {
        return $this->generateUrlWith([
            'page' => $page > 1 ? $page : null
        ]);
    }

    /**
     * Generate per-page URL
     */
    public function generatePerPageUrl($perPage): string
    {
        return $this->generateUrlWith([
            'per_page' => $perPage !== ($this->defaultPerPage ?? 10) ? $perPage : null,
            'page' => null // Reset to first page when changing per page
        ]);
    }

    /**
     * Generate column visibility URL
     */
    public function generateColumnVisibilityUrl(array $visibleColumns): string
    {
        $defaultVisible = array_keys($this->columns);
        
        // Only include in URL if different from default
        $columnsParam = $visibleColumns === $defaultVisible ? null : implode(',', $visibleColumns);

        return $this->generateUrlWith([
            'columns' => $columnsParam
        ]);
    }

    /**
     * Generate reset URL (clear all parameters)
     */
    public function generateResetUrl(): string
    {
        return request()->url();
    }

    /**
     * Check if query string has any datatable parameters
     */
    public function hasQueryStringParams(): bool
    {
        $params = request()->query();
        
        $datatableKeys = ['search', 'sort', 'direction', 'page', 'per_page', 'columns'];
        
        foreach ($datatableKeys as $key) {
            if (isset($params[$key])) {
                return true;
            }
        }
        
        // Check for filter parameters
        foreach ($params as $key => $value) {
            if (str_starts_with($key, 'filter_')) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get shareable URL for current state
     */
    public function getShareableUrl(): string
    {
        return $this->generateUrl();
    }

    /**
     * Parse query string parameters
     */
    public function parseQueryStringParams(string $queryString): array
    {
        parse_str($queryString, $params);
        return $params;
    }

    /**
     * Validate query string parameters
     */
    public function validateQueryStringParams(array $params): array
    {
        $valid = [];
        $invalid = [];

        // Validate search
        if (isset($params['search'])) {
            if (is_string($params['search'])) {
                $valid['search'] = $params['search'];
            } else {
                $invalid['search'] = 'Search must be a string';
            }
        }

        // Validate sort
        if (isset($params['sort'])) {
            if (isset($this->columns[$params['sort']])) {
                $valid['sort'] = $params['sort'];
                
                // Validate direction
                if (isset($params['direction'])) {
                    if (in_array($params['direction'], ['asc', 'desc'])) {
                        $valid['direction'] = $params['direction'];
                    } else {
                        $invalid['direction'] = 'Direction must be asc or desc';
                    }
                }
            } else {
                $invalid['sort'] = 'Invalid sort column';
            }
        }

        // Validate pagination
        if (isset($params['page'])) {
            if (is_numeric($params['page']) && $params['page'] > 0) {
                $valid['page'] = (int) $params['page'];
            } else {
                $invalid['page'] = 'Page must be a positive integer';
            }
        }

        if (isset($params['per_page'])) {
            if (is_numeric($params['per_page']) && $params['per_page'] > 0) {
                $valid['per_page'] = (int) $params['per_page'];
            } else {
                $invalid['per_page'] = 'Per page must be a positive integer';
            }
        }

        // Validate filters
        foreach ($params as $key => $value) {
            if (str_starts_with($key, 'filter_')) {
                $columnKey = substr($key, 7);
                
                if (isset($this->columns[$columnKey])) {
                    $valid[$key] = $value;
                } else {
                    $invalid[$key] = 'Invalid filter column';
                }
            }
        }

        // Validate columns
        if (isset($params['columns'])) {
            $columns = explode(',', $params['columns']);
            $validColumns = [];
            
            foreach ($columns as $column) {
                if (isset($this->columns[$column])) {
                    $validColumns[] = $column;
                }
            }
            
            if (!empty($validColumns)) {
                $valid['columns'] = implode(',', $validColumns);
            }
            
            if (count($validColumns) !== count($columns)) {
                $invalid['columns'] = 'Some columns are invalid';
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid
        ];
    }

    /**
     * Get query string statistics
     */
    public function getQueryStringStats(): array
    {
        $params = $this->getQueryStringParams();
        $currentUrl = $this->generateUrl();
        
        return [
            'has_params' => !empty($params),
            'param_count' => count($params),
            'url_length' => strlen($currentUrl),
            'params' => $params,
            'current_url' => $currentUrl,
            'has_search' => isset($params['search']),
            'has_sort' => isset($params['sort']),
            'has_filters' => !empty(array_filter(array_keys($params), fn($key) => str_starts_with($key, 'filter_'))),
            'has_pagination' => isset($params['page']) || isset($params['per_page']),
            'has_column_config' => isset($params['columns'])
        ];
    }
}
