<?php

namespace ArtflowStudio\Table\Traits\UI;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

trait HasColumnVisibility
{
    /**
     * Get default visible columns
     */
    protected function getDefaultVisibleColumns()
    {
        $defaultColumns = [];
        foreach ($this->columns as $identifier => $column) {
            // Default to visible unless explicitly hidden
            $isVisible = !isset($column['hide']) || !$column['hide'];
            $defaultColumns[$identifier] = $isVisible;
        }
        return $defaultColumns;
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
                $validSessionVisibility[$columnKey] = !($columnConfig['hide'] ?? false);
            }
        }
        return $validSessionVisibility;
    }

    /**
     * Toggle column visibility
     */
    public function toggleColumnVisibility($columnKey)
    {
        // Ensure the column exists in the visibility array with proper default
        if (!array_key_exists($columnKey, $this->visibleColumns)) {
            $this->visibleColumns[$columnKey] = !($this->columns[$columnKey]['hide'] ?? false);
        }

        // Toggle the visibility
        $this->visibleColumns[$columnKey] = !$this->visibleColumns[$columnKey];
        
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

    /**
     * Get session key for column visibility
     */
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
        if (Auth::check()) {
            return 'user_' . Auth::id();
        }
        
        if (app('request')->ip()) {
            // Fallback to session ID + IP for guest users
            return 'guest_' . md5(session()->getId() . '_' . app('request')->ip());
        }
        
        // Final fallback to session ID only
        return 'session_' . session()->getId();
    }

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
     * Initialize column visibility from session or defaults
     */
    protected function initializeColumnVisibility()
    {
        $sessionKey = $this->getColumnVisibilitySessionKey();
        $sessionVisibility = Session::get($sessionKey, []);
        
        if (!empty($sessionVisibility)) {
            $this->visibleColumns = $this->getValidatedVisibleColumns($sessionVisibility);
        } else {
            $this->visibleColumns = $this->getDefaultVisibleColumns();
        }
        
        // Ensure all columns have a visibility state
        foreach ($this->columns as $columnKey => $column) {
            if (!array_key_exists($columnKey, $this->visibleColumns)) {
                $this->visibleColumns[$columnKey] = !($column['hide'] ?? false);
            }
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
     * Reset to default column visibility
     */
    public function resetColumnVisibility()
    {
        $this->visibleColumns = $this->getDefaultVisibleColumns();
        Session::put($this->getColumnVisibilitySessionKey(), $this->visibleColumns);
        $this->dispatch('$refresh');
    }

    /**
     * Get visible columns (filtered to show only visible ones)
     */
    public function getVisibleColumns(): array
    {
        $visibleColumns = [];
        foreach ($this->columns as $columnKey => $column) {
            if ($this->isColumnVisible($columnKey)) {
                $visibleColumns[$columnKey] = $column;
            }
        }
        return $visibleColumns;
    }

    /**
     * Get visible columns count
     */
    public function getVisibleColumnsCount(): int
    {
        return count(array_filter($this->visibleColumns));
    }

    /**
     * Get hidden columns count
     */
    public function getHiddenColumnsCount(): int
    {
        return count($this->visibleColumns) - $this->getVisibleColumnsCount();
    }

    /**
     * Check if column is visible
     */
    public function isColumnVisible($columnKey): bool
    {
        return $this->visibleColumns[$columnKey] ?? false;
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
}
