<?php

namespace ArtflowStudio\Table\Traits;

trait HasSearch
{
    /**
     * Sanitize search input
     */
    protected function sanitizeSearch($search)
    {
        if (!is_string($search)) {
            return '';
        }
        
        $search = trim($search);
        // Limit length to prevent abuse
        return mb_substr($search, 0, 100);
    }

    /**
     * Update search and reset pagination
     */
    public function updatedSearch()
    {
        $this->search = $this->sanitizeSearch($this->search);
        $this->resetPage();
    }

    /**
     * Apply optimized search to query
     */
    protected function applyOptimizedSearch($query): void
    {
        $search = $this->sanitizeSearch($this->search);

        $query->where(function ($query) use ($search) {
            foreach ($this->columns as $columnKey => $column) {
                // Skip non-visible columns for performance
                if (!($this->visibleColumns[$columnKey] ?? true)) {
                    continue;
                }

                // Skip function columns
                if (isset($column['function'])) {
                    continue;
                }

                // Skip non-searchable columns
                if (!$this->isColumnSearchable($column)) {
                    continue;
                }

                if (isset($column['relation'])) {
                    $this->applyRelationSearch($query, $column, $search);
                } elseif (isset($column['json'])) {
                    $this->applyJsonSearch($query, $column, $search);
                } elseif (isset($column['key']) && $this->isValidColumn($column['key'])) {
                    $query->orWhere($column['key'], 'LIKE', "%{$search}%");
                }
            }
        });
    }

    /**
     * Apply search to relation columns
     */
    protected function applyRelationSearch($query, $column, $search)
    {
        if (!$this->validateRelationString($column['relation'])) {
            return;
        }

        [$relationName, $relatedColumn] = explode(':', $column['relation']);
        
        $query->orWhereHas($relationName, function ($relationQuery) use ($relatedColumn, $search) {
            $relationQuery->where($relatedColumn, 'LIKE', "%{$search}%");
        });
    }

    /**
     * Apply search to JSON columns
     */
    protected function applyJsonSearch($query, $column, $search)
    {
        if (!isset($column['key']) || !isset($column['json'])) {
            return;
        }

        if (!$this->validateJsonPath($column['json'])) {
            return;
        }

        $jsonColumn = $column['key'];
        $jsonPath = $column['json'];

        // Use JSON_EXTRACT for MySQL or similar for other databases
        $query->orWhereRaw("JSON_EXTRACT({$jsonColumn}, '$.{$jsonPath}') LIKE ?", ["%{$search}%"]);
    }

    /**
     * Clear search and refresh
     */
    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Refresh table and clear search
     */
    public function refreshTable()
    {
        $this->resetPage();
        $this->search = '';
    }

    /**
     * Set search value programmatically
     */
    public function setSearch($searchValue)
    {
        $this->search = $this->sanitizeSearch($searchValue);
        $this->resetPage();
    }

    /**
     * Get search suggestions (for autocomplete)
     */
    public function getSearchSuggestions($limit = 10): array
    {
        if (strlen($this->search) < 2) {
            return [];
        }

        $suggestions = [];
        $search = $this->sanitizeSearch($this->search);

        foreach ($this->columns as $columnKey => $column) {
            // Skip non-searchable columns
            if (!$this->isColumnSearchable($column)) {
                continue;
            }

            // Skip function and relation columns for suggestions
            if (isset($column['function']) || isset($column['relation'])) {
                continue;
            }

            if (isset($column['key']) && $this->isValidColumn($column['key'])) {
                $columnSuggestions = $this->model::where($column['key'], 'LIKE', "%{$search}%")
                    ->distinct()
                    ->limit($limit)
                    ->pluck($column['key'])
                    ->filter()
                    ->values()
                    ->toArray();

                $suggestions = array_merge($suggestions, $columnSuggestions);
            }
        }

        return array_unique(array_slice($suggestions, 0, $limit));
    }

    /**
     * Advanced search with multiple terms
     */
    public function advancedSearch($searchTerms)
    {
        if (!is_array($searchTerms)) {
            $searchTerms = explode(' ', $this->sanitizeSearch($searchTerms));
        }

        $searchTerms = array_filter($searchTerms, function($term) {
            return strlen(trim($term)) >= 2;
        });

        if (empty($searchTerms)) {
            return;
        }

        $this->search = implode(' ', $searchTerms);
        $this->resetPage();
    }

    /**
     * Search in specific column
     */
    public function searchInColumn($columnKey, $searchValue)
    {
        if (!$this->isAllowedColumn($columnKey)) {
            return;
        }

        $this->filterColumn = $columnKey;
        $this->search = $this->sanitizeSearch($searchValue);
        $this->resetPage();
    }

    /**
     * Get search statistics
     */
    public function getSearchStats(): array
    {
        if (empty($this->search)) {
            return [
                'total_records' => $this->getTotalRecords(),
                'filtered_records' => $this->getTotalRecords(),
                'search_term' => '',
                'columns_searched' => 0
            ];
        }

        $searchableColumns = 0;
        foreach ($this->columns as $column) {
            if ($this->isColumnSearchable($column)) {
                $searchableColumns++;
            }
        }

        return [
            'total_records' => $this->getTotalRecords(),
            'filtered_records' => $this->getFilteredRecords(),
            'search_term' => $this->search,
            'columns_searched' => $searchableColumns
        ];
    }

    /**
     * Get total records count
     */
    protected function getTotalRecords(): int
    {
        return $this->model::count();
    }

    /**
     * Get filtered records count
     */
    protected function getFilteredRecords(): int
    {
        $query = $this->model::query();
        
        if ($this->searchable && $this->search) {
            $this->applyOptimizedSearch($query);
        }

        if ($this->filterColumn && $this->filterValue) {
            $this->applyFilters($query);
        }

        return $query->count();
    }
}
