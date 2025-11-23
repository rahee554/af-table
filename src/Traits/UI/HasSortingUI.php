<?php

namespace ArtflowStudio\Table\Traits\UI;

trait HasSortingUI
{
    /**
     * Check if a column is currently sorted
     * Alias for isColumnSorted from HasSorting trait
     * Useful for displaying sort indicators in templates
     */
    public function isSorted(string $column): bool
    {
        // Delegate to HasSorting::isColumnSorted if available
        if (method_exists($this, 'isColumnSorted')) {
            return $this->isColumnSorted($column);
        }
        
        return $this->sortColumn === $column ?? false;
    }

    /**
     * Get sort direction icon class for a column
     * Returns FontAwesome icon classes based on current sort state
     * 
     * @param string $column Column key
     * @return string Icon class (e.g., 'fas fa-sort-up', 'fas fa-sort-down', 'fas fa-sort')
     */
    public function getSortIcon(string $column): string
    {
        if ($this->sortColumn !== $column) {
            return 'fas fa-sort'; // Neutral sort icon
        }

        return $this->sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
    }

    /**
     * Get CSS class for sort indicator
     * Useful for styling sorted columns differently
     * 
     * @param string $column Column key
     * @return string CSS class
     */
    public function getSortClass(string $column): string
    {
        if ($this->sortColumn !== $column) {
            return 'text-muted'; // Muted when not sorted
        }

        return $this->sortDirection === 'asc' ? 'text-primary sort-asc' : 'text-primary sort-desc';
    }

    /**
     * Get the opposite sort direction for toggle
     * Useful for creating toggle links
     * 
     * @param string $column Column key
     * @return string Next sort direction ('asc' or 'desc')
     */
    public function getNextSortDirection(string $column): string
    {
        if ($this->sortColumn !== $column) {
            return 'asc'; // Default to asc for new sort column
        }

        return $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    /**
     * Get current sort state as array
     * Useful for passing to templates or external systems
     * 
     * @return array ['column' => string, 'direction' => string]
     */
    public function getSortState(): array
    {
        return [
            'column' => $this->sortColumn,
            'direction' => $this->sortDirection,
        ];
    }

    /**
     * Check if column can be sorted
     * Used to disable sort on certain column types
     * 
     * @param string $column Column key
     * @return bool True if sortable
     */
    public function canSortColumn(string $column): bool
    {
        // This should delegate to HasSorting::isColumnSortable if available
        if (method_exists($this, 'isColumnSortable')) {
            return $this->isColumnSortable($column);
        }

        // Fallback check
        if (!isset($this->columns[$column])) {
            return false;
        }

        $config = $this->columns[$column];
        
        // Check if explicitly marked as unsortable
        if (isset($config['sortable']) && $config['sortable'] === false) {
            return false;
        }

        // Cannot sort JSON or function columns
        if (isset($config['json']) || isset($config['function'])) {
            return false;
        }

        return true;
    }

    /**
     * Get label for a column
     * Useful for sort header templates
     * 
     * @param string $column Column key
     * @return string|null Column label
     */
    public function getColumnLabel(string $column): ?string
    {
        if (!isset($this->columns[$column])) {
            return null;
        }

        $config = $this->columns[$column];
        
        if (isset($config['label'])) {
            return $config['label'];
        }

        return ucfirst(str_replace('_', ' ', $column));
    }

    /**
     * Get sortable columns with their metadata
     * Useful for rendering sort controls
     * 
     * @return array Sortable columns with metadata
     */
    public function getSortableColumns(): array
    {
        $sortable = [];

        if (!isset($this->columns)) {
            return $sortable;
        }

        foreach ($this->columns as $key => $config) {
            if (!is_array($config)) {
                continue;
            }

            if (!$this->canSortColumn($key)) {
                continue;
            }

            $sortable[$key] = [
                'key' => $key,
                'label' => $this->getColumnLabel($key),
                'is_sorted' => $this->isColumnSorted($key),
                'direction' => $this->sortDirection,
                'next_direction' => $this->getNextSortDirection($key),
                'icon' => $this->getSortIcon($key),
                'class' => $this->getSortClass($key),
            ];
        }

        return $sortable;
    }

    /**
     * Get HTML for sort indicator badge
     * Can be used directly in templates for quick implementation
     * 
     * @param string $column Column key
     * @param bool $showLabel Show text label with icon
     * @return string HTML for sort indicator
     */
    public function getSortIndicatorHtml(string $column, bool $showLabel = false): string
    {
        if (!$this->isColumnSorted($column)) {
            return '';
        }

        $icon = $this->getSortIcon($column);
        $direction = $this->sortDirection;

        if ($showLabel) {
            $label = ucfirst($direction);
            return "<span class='sort-indicator'><i class='{$icon}'></i> {$label}</span>";
        }

        return "<i class='{$icon}'></i>";
    }
}
