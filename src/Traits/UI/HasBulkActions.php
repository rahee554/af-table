<?php

namespace ArtflowStudio\Table\Traits\UI;

trait HasBulkActions
{
    /**
     * Selected rows for bulk actions
     */
    public $selectedRows = [];
    
    /**
     * Select all rows flag
     */
    public $selectAll = false;
    
    /**
     * Available bulk actions
     */
    public $bulkActions = [];
    
    /**
     * Current bulk action being performed
     */
    public $currentBulkAction = null;

    /**
     * Initialize bulk actions
     */
    public function initializeBulkActions()
    {
        $this->selectedRows = [];
        $this->selectAll = false;
        $this->currentBulkAction = null;
    }

    /**
     * Set available bulk actions
     */
    public function setBulkActions(array $actions)
    {
        $this->bulkActions = $actions;
        return $this;
    }

    /**
     * Toggle row selection
     */
    public function toggleRowSelection($id)
    {
        if (in_array($id, $this->selectedRows)) {
            $this->selectedRows = array_filter($this->selectedRows, function($rowId) use ($id) {
                return $rowId != $id;
            });
        } else {
            $this->selectedRows[] = $id;
        }
        
        $this->selectAll = false;
        $this->dispatch('rowSelectionChanged', $this->selectedRows);
    }

    /**
     * Toggle select all rows
     */
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedRows = [];
            $this->selectAll = false;
        } else {
            $this->selectedRows = $this->getAllRowIds();
            $this->selectAll = true;
        }
        
        $this->dispatch('selectAllChanged', $this->selectAll, $this->selectedRows);
    }

    /**
     * Get all row IDs from current data
     */
    protected function getAllRowIds()
    {
        if ($this->isForeachMode()) {
            $data = $this->getPaginatedForeachData();
            return $data->data->pluck('id')->toArray();
        }
        
        $data = $this->getData();
        if (is_object($data) && method_exists($data, 'pluck')) {
            return $data->pluck('id')->toArray();
        }
        
        return [];
    }

    /**
     * Clear all selections
     */
    public function clearSelection()
    {
        $this->selectedRows = [];
        $this->selectAll = false;
        $this->dispatch('selectionCleared');
    }

    /**
     * Get selected rows count
     */
    public function getSelectedCount(): int
    {
        return count($this->selectedRows);
    }

    /**
     * Check if row is selected
     */
    public function isRowSelected($id): bool
    {
        return in_array($id, $this->selectedRows);
    }

    /**
     * Perform bulk action
     */
    public function performBulkAction($action)
    {
        if (empty($this->selectedRows)) {
            $this->dispatch('bulkActionError', 'No rows selected');
            return;
        }

        $this->currentBulkAction = $action;
        
        switch ($action) {
            case 'delete':
                $this->bulkDelete();
                break;
            case 'export':
                $this->bulkExport();
                break;
            case 'archive':
                $this->bulkArchive();
                break;
            case 'restore':
                $this->bulkRestore();
                break;
            default:
                $this->customBulkAction($action);
                break;
        }
        
        $this->dispatch('bulkActionPerformed', $action, $this->selectedRows);
    }

    /**
     * Bulk delete selected rows
     */
    protected function bulkDelete()
    {
        try {
            if ($this->model) {
                $this->model::whereIn('id', $this->selectedRows)->delete();
                $this->dispatch('bulkActionSuccess', 'Selected items deleted successfully');
            }
        } catch (\Exception $e) {
            $this->dispatch('bulkActionError', 'Error deleting items: ' . $e->getMessage());
        }
        
        $this->clearSelection();
        $this->resetData();
    }

    /**
     * Bulk export selected rows
     */
    protected function bulkExport()
    {
        try {
            $data = [];
            
            if ($this->isForeachMode()) {
                $allData = $this->getForeachData();
                $data = $allData->whereIn('id', $this->selectedRows);
            } else {
                $data = $this->model::whereIn('id', $this->selectedRows)->get();
            }
            
            $this->dispatch('bulkExportReady', $data->toArray());
        } catch (\Exception $e) {
            $this->dispatch('bulkActionError', 'Error exporting items: ' . $e->getMessage());
        }
    }

    /**
     * Bulk archive selected rows
     */
    protected function bulkArchive()
    {
        try {
            if ($this->model) {
                $this->model::whereIn('id', $this->selectedRows)
                           ->update(['archived_at' => now()]);
                $this->dispatch('bulkActionSuccess', 'Selected items archived successfully');
            }
        } catch (\Exception $e) {
            $this->dispatch('bulkActionError', 'Error archiving items: ' . $e->getMessage());
        }
        
        $this->clearSelection();
        $this->resetData();
    }

    /**
     * Bulk restore selected rows
     */
    protected function bulkRestore()
    {
        try {
            if ($this->model) {
                $this->model::whereIn('id', $this->selectedRows)
                           ->update(['archived_at' => null]);
                $this->dispatch('bulkActionSuccess', 'Selected items restored successfully');
            }
        } catch (\Exception $e) {
            $this->dispatch('bulkActionError', 'Error restoring items: ' . $e->getMessage());
        }
        
        $this->clearSelection();
        $this->resetData();
    }

    /**
     * Custom bulk action handler
     */
    protected function customBulkAction($action)
    {
        // Override this method in your component to handle custom bulk actions
        $this->dispatch('customBulkAction', $action, $this->selectedRows);
    }

    /**
     * Get bulk action button classes
     */
    public function getBulkActionButtonClass($action): string
    {
        $baseClass = 'px-4 py-2 text-sm font-medium rounded-md ';
        
        switch ($action) {
            case 'delete':
                return $baseClass . 'text-white bg-red-600 hover:bg-red-700';
            case 'export':
                return $baseClass . 'text-white bg-green-600 hover:bg-green-700';
            case 'archive':
                return $baseClass . 'text-white bg-yellow-600 hover:bg-yellow-700';
            case 'restore':
                return $baseClass . 'text-white bg-blue-600 hover:bg-blue-700';
            default:
                return $baseClass . 'text-white bg-gray-600 hover:bg-gray-700';
        }
    }

    /**
     * Get selection summary
     */
    public function getSelectionSummary(): array
    {
        return [
            'selected_count' => $this->getSelectedCount(),
            'select_all' => $this->selectAll,
            'selected_ids' => $this->selectedRows,
            'has_selection' => !empty($this->selectedRows),
            'available_actions' => $this->bulkActions,
        ];
    }
}
