<?php

namespace ArtflowStudio\Table\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Column configuration and visibility, including the per-user
 * session persistence behind the visibility menu.
 *
 * Consolidated in 1.6.0 from: HasColumnManagement, HasColumnVisibility.
 */
trait ManagesColumns
{
    /**
     * Clear column visibility session
     */
    public function clearColumnVisibilitySession()
    {
        $sessionKey = $this->getColumnVisibilitySessionKey();
        Session::forget($sessionKey);

        // Reset to defaults
        $this->visibleColumns = $this->getDefaultVisibleColumns();
        Session::put($sessionKey, $this->visibleColumns);
    }

    /**
     * Get session key for column visibility
     */
    protected function getColumnVisibilitySessionKey()
    {
        // Use model class name and tableId for uniqueness
        $modelName = is_string($this->model) ? $this->model : (is_object($this->model) ? get_class($this->model) : 'datatable');

        // Include user ID for session isolation - prevents data leakage between users
        $userId = $this->getUserIdentifierForSession();

        return 'datatable_visible_columns_'.md5($modelName.'_'.static::class.'_'.$this->tableId.'_'.$userId);
    }

    /**
     * Get default visible columns
     */
    protected function getDefaultVisibleColumns()
    {
        $defaultColumns = [];
        foreach ($this->columns as $identifier => $column) {
            // Default to visible unless explicitly hidden
            $isVisible = ! isset($column['hide']) || ! $column['hide'];
            $defaultColumns[$identifier] = $isVisible;
        }

        return $defaultColumns;
    }

    /**
     * Get hidden columns count
     */
    public function getHiddenColumnsCount(): int
    {
        return count($this->visibleColumns) - $this->getVisibleColumnsCount();
    }

    /**
     * Get user identifier for session isolation
     */
    protected function getUserIdentifierForSession()
    {
        // Try different auth methods in order of preference
        if (Auth::check()) {
            return 'user_'.Auth::id();
        }

        if (app('request')->ip()) {
            // Fallback to session ID + IP for guest users
            return 'guest_'.md5(session()->getId().'_'.app('request')->ip());
        }

        // Final fallback to session ID only
        return 'session_'.session()->getId();
    }

    /**
     * Get validated visible columns from session
     */
    protected function getValidatedVisibleColumns($sessionVisibility)
    {
        $validSessionVisibility = [];
        foreach ($this->columns as $columnKey => $columnConfig) {
            if (array_key_exists($columnKey, $sessionVisibility)) {
                $validSessionVisibility[$columnKey] = $sessionVisibility[$columnKey];
            } else {
                // Use default visibility for missing columns
                $validSessionVisibility[$columnKey] = ! ($columnConfig['hide'] ?? false);
            }
        }

        return $validSessionVisibility;
    }

    /**
     * Get visible columns count
     */
    public function getVisibleColumnsCount(): int
    {
        return count(array_filter($this->visibleColumns));
    }

    /**
     * Hide all columns (except required ones)
     */
    public function hideAllColumns()
    {
        foreach ($this->columns as $columnKey => $column) {
            // Keep required columns visible
            if (isset($column['required']) && $column['required'] === true) {
                $this->visibleColumns[$columnKey] = true;
            } else {
                $this->visibleColumns[$columnKey] = false;
            }
        }

        Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);
        $this->dispatch('$refresh');
    }

    /**
     * Initialize column visibility from session or defaults
     */
    protected function initializeColumnVisibility()
    {
        $sessionKey = $this->getColumnVisibilitySessionKey();
        $sessionVisibility = Session::get($sessionKey, []);

        if (! empty($sessionVisibility)) {
            $this->visibleColumns = $this->getValidatedVisibleColumns($sessionVisibility);
        } else {
            $this->visibleColumns = $this->getDefaultVisibleColumns();
        }

        // Ensure all columns have a visibility state
        foreach ($this->columns as $columnKey => $column) {
            if (! array_key_exists($columnKey, $this->visibleColumns)) {
                $this->visibleColumns[$columnKey] = ! ($column['hide'] ?? false);
            }
        }
    }

    /**
     * Check if column is allowed for operations
     * Moved from DatatableTrait.php line 1875
     */
    protected function isAllowedColumn($column)
    {
        // Allow 'updated_at' for index column sorting
        if ($column === 'updated_at') {
            return true;
        }

        return array_key_exists($column, $this->columns);
    }

    /**
     * Reset to default column visibility
     */
    public function resetColumnVisibility()
    {
        $this->visibleColumns = $this->getDefaultVisibleColumns();
        Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);
        $this->dispatch('$refresh');
    }

    /**
     * Set column visibility
     */
    public function setColumnVisibility($columnKey, $visible)
    {
        if (array_key_exists($columnKey, $this->columns)) {
            $this->visibleColumns[$columnKey] = (bool) $visible;
            Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);
            $this->dispatch('$refresh');
        }
    }

    /**
     * Show all columns
     */
    public function showAllColumns()
    {
        foreach ($this->columns as $columnKey => $column) {
            $this->visibleColumns[$columnKey] = true;
        }

        Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);
        $this->dispatch('$refresh');
    }

    /**
     * Toggle column visibility
     */
    public function toggleColumnVisibility($columnKey)
    {
        // Ensure the column exists in the visibility array with proper default
        if (! array_key_exists($columnKey, $this->visibleColumns)) {
            $this->visibleColumns[$columnKey] = ! ($this->columns[$columnKey]['hide'] ?? false);
        }

        // Toggle the visibility
        $this->visibleColumns[$columnKey] = ! $this->visibleColumns[$columnKey];

        // Save to session
        Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);

        // Force component re-render
        $this->dispatch('$refresh');
    }

    /**
     * Update column visibility (called by wire:model)
     */
    public function updateColumnVisibility($columnKey)
    {
        // The visibleColumns array is automatically updated by Livewire
        // We just need to save it to the session
        Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);

        // Force component re-render to ensure table updates
        $this->dispatch('$refresh');
    }
}
