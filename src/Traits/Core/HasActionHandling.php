<?php

namespace ArtflowStudio\Table\Traits\Core;

/**
 * HasActionHandling - Unified action processing functionality
 * 
 * This trait consolidates action methods from:
 * - HasUserActions (user-initiated actions)
 * - HasBulkActions (bulk operations)
 * - HasRowActions (individual row actions)
 * 
 * Purpose: Eliminate action conflicts and centralize action processing
 */
trait HasActionHandling
{
    // *----------- Action Properties -----------*//
    
    protected $availableActions = [];
    protected $actionResults = [];
    protected $actionQueue = [];
    protected $processingAction = false;
    
    // *----------- Core Action Methods -----------*//
    
    /**
     * Execute action with validation and logging
     * Consolidated from HasUserActions and HasBulkActions
     */
    public function executeAction($action, $data = [], $targets = [])
    {
        // Prevent concurrent action execution
        if ($this->processingAction) {
            $this->logActionResult($action, false, 'Action already in progress');
            return false;
        }
        
        $this->processingAction = true;
        
        try {
            // Validate action
            if (!$this->validateAction($action, $data)) {
                $this->processingAction = false;
                return false;
            }
            
            // Pre-action hooks
            $this->beforeActionExecution($action, $data, $targets);
            
            // Execute the action
            $result = $this->performAction($action, $data, $targets);
            
            // Post-action hooks
            $this->afterActionExecution($action, $data, $targets, $result);
            
            // Log result
            $this->logActionResult($action, $result['success'] ?? false, $result['message'] ?? '');
            
            // Trigger events
            if ($result['success'] ?? false) {
                $this->triggerActionEvent($action, $data, $targets, $result);
            }
            
            $this->processingAction = false;
            return $result;
            
        } catch (\Exception $e) {
            $this->processingAction = false;
            $this->logActionResult($action, false, $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e
            ];
        }
    }
    
    /**
     * Perform the actual action
     * From HasUserActions
     */
    protected function performAction($action, $data, $targets)
    {
        $actionConfig = $this->getActionConfig($action);
        $actionMethod = $actionConfig['method'] ?? ('handle' . ucfirst($action));
        
        // Check if method exists on component
        if (method_exists($this, $actionMethod)) {
            return $this->$actionMethod($data, $targets);
        }
        
        // Check for standard action handlers
        switch ($action) {
            case 'delete':
                return $this->handleDelete($data, $targets);
                
            case 'bulkDelete':
                return $this->handleBulkDelete($data, $targets);
                
            case 'export':
                return $this->handleExport($data, $targets);
                
            case 'archive':
                return $this->handleArchive($data, $targets);
                
            case 'restore':
                return $this->handleRestore($data, $targets);
                
            case 'approve':
                return $this->handleApprove($data, $targets);
                
            case 'reject':
                return $this->handleReject($data, $targets);
                
            case 'duplicate':
                return $this->handleDuplicate($data, $targets);
                
            case 'refresh':
                return $this->handleRefresh($data, $targets);
                
            default:
                return [
                    'success' => false,
                    'message' => "Unknown action: {$action}"
                ];
        }
    }
    
    // *----------- Bulk Action Methods -----------*//
    
    /**
     * Execute action on multiple items
     * Consolidated from HasBulkActions
     */
    public function executeBulkAction($action, $data = [])
    {
        $selectedRows = $this->selectedRows ?? [];
        
        if (empty($selectedRows)) {
            return [
                'success' => false,
                'message' => 'No items selected for bulk action'
            ];
        }
        
        return $this->executeAction($action, $data, $selectedRows);
    }
    
    /**
     * Process bulk action on multiple records
     * From HasBulkActions
     */
    protected function processBulkAction($action, $records, $data = [])
    {
        $results = [
            'success' => true,
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
            'message' => ''
        ];
        
        foreach ($records as $record) {
            try {
                $singleResult = $this->processSingleRecord($action, $record, $data);
                
                if ($singleResult['success'] ?? false) {
                    $results['processed']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'record' => $record,
                        'error' => $singleResult['message'] ?? 'Unknown error'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'record' => $record,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Determine overall success
        $results['success'] = $results['failed'] === 0;
        $results['message'] = $this->formatBulkActionMessage($action, $results);
        
        return $results;
    }
    
    /**
     * Process action on single record
     * From HasBulkActions
     */
    protected function processSingleRecord($action, $record, $data = [])
    {
        // Override in specific implementations
        return [
            'success' => true,
            'message' => "Action {$action} processed for record {$record}"
        ];
    }
    
    /**
     * Format bulk action result message
     * From HasBulkActions
     */
    protected function formatBulkActionMessage($action, $results): string
    {
        $total = $results['processed'] + $results['failed'];
        
        if ($results['success']) {
            return "Successfully {$action} {$results['processed']} item(s)";
        } else {
            return "Processed {$results['processed']} item(s), {$results['failed']} failed out of {$total} total";
        }
    }
    
    // *----------- Standard Action Handlers -----------*//
    
    /**
     * Handle delete action
     * From HasUserActions and HasRowActions
     */
    protected function handleDelete($data, $targets)
    {
        if (empty($targets)) {
            return ['success' => false, 'message' => 'No items to delete'];
        }
        
        if (is_array($targets)) {
            return $this->processBulkAction('delete', $targets, $data);
        }
        
        // Single item delete
        return $this->deleteSingleItem($targets, $data);
    }
    
    /**
     * Handle bulk delete action
     */
    protected function handleBulkDelete($data, $targets)
    {
        return $this->handleDelete($data, $targets);
    }
    
    /**
     * Handle export action
     * From HasUserActions
     */
    protected function handleExport($data, $targets)
    {
        $format = $data['format'] ?? 'csv';
        $filename = $data['filename'] ?? 'export_' . date('Y-m-d_H-i-s');
        
        try {
            $exportData = $this->prepareExportData($targets);
            $exportPath = $this->generateExport($exportData, $format, $filename);
            
            return [
                'success' => true,
                'message' => 'Export completed successfully',
                'export_path' => $exportPath,
                'download_url' => $this->getExportDownloadUrl($exportPath)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle archive action
     * From HasUserActions
     */
    protected function handleArchive($data, $targets)
    {
        return $this->changeStatus($targets, 'archived', 'Archive');
    }
    
    /**
     * Handle restore action
     * From HasUserActions
     */
    protected function handleRestore($data, $targets)
    {
        return $this->changeStatus($targets, 'active', 'Restore');
    }
    
    /**
     * Handle approve action
     * From HasUserActions
     */
    protected function handleApprove($data, $targets)
    {
        return $this->changeStatus($targets, 'approved', 'Approve');
    }
    
    /**
     * Handle reject action
     * From HasUserActions
     */
    protected function handleReject($data, $targets)
    {
        $reason = $data['reason'] ?? 'No reason provided';
        return $this->changeStatus($targets, 'rejected', 'Reject', ['reason' => $reason]);
    }
    
    /**
     * Handle duplicate action
     * From HasRowActions
     */
    protected function handleDuplicate($data, $targets)
    {
        if (is_array($targets)) {
            return $this->processBulkAction('duplicate', $targets, $data);
        }
        
        return $this->duplicateSingleItem($targets, $data);
    }
    
    /**
     * Handle refresh action
     * From HasUserActions
     */
    protected function handleRefresh($data, $targets)
    {
        $this->refreshComponent();
        
        return [
            'success' => true,
            'message' => 'Table refreshed successfully'
        ];
    }
    
    // *----------- Row Action Methods -----------*//
    
    /**
     * Execute action on specific row
     * From HasRowActions
     */
    public function executeRowAction($action, $rowId, $data = [])
    {
        return $this->executeAction($action, $data, [$rowId]);
    }
    
    /**
     * Get available actions for row
     * From HasRowActions
     */
    public function getRowActions($record): array
    {
        $actions = [];
        
        foreach ($this->availableActions as $action => $config) {
            if ($this->isActionAvailableForRecord($action, $record)) {
                $actions[$action] = $config;
            }
        }
        
        return $actions;
    }
    
    /**
     * Check if action is available for specific record
     * From HasRowActions
     */
    protected function isActionAvailableForRecord($action, $record): bool
    {
        $actionConfig = $this->getActionConfig($action);
        
        // Check record-specific conditions
        if (isset($actionConfig['conditions'])) {
            foreach ($actionConfig['conditions'] as $condition) {
                if (!$this->evaluateActionCondition($condition, $record)) {
                    return false;
                }
            }
        }
        
        // Check permissions
        if (!$this->userCanPerformAction($action)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Evaluate action condition for record
     * From HasRowActions
     */
    protected function evaluateActionCondition($condition, $record): bool
    {
        if (is_callable($condition)) {
            return $condition($record, $this);
        }
        
        if (is_array($condition)) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;
            
            if ($field && isset($record[$field])) {
                return $this->compareValues($record[$field], $operator, $value);
            }
        }
        
        return true;
    }
    
    /**
     * Compare values with operator
     * From HasRowActions
     */
    protected function compareValues($actualValue, $operator, $expectedValue): bool
    {
        switch ($operator) {
            case '=':
            case '==':
                return $actualValue == $expectedValue;
            case '!=':
            case '<>':
                return $actualValue != $expectedValue;
            case '>':
                return $actualValue > $expectedValue;
            case '>=':
                return $actualValue >= $expectedValue;
            case '<':
                return $actualValue < $expectedValue;
            case '<=':
                return $actualValue <= $expectedValue;
            case 'in':
                return in_array($actualValue, (array) $expectedValue);
            case 'not_in':
                return !in_array($actualValue, (array) $expectedValue);
            case 'contains':
                return strpos($actualValue, $expectedValue) !== false;
            case 'starts_with':
                return strpos($actualValue, $expectedValue) === 0;
            case 'ends_with':
                return substr($actualValue, -strlen($expectedValue)) === $expectedValue;
            default:
                return false;
        }
    }
    
    // *----------- Helper Methods -----------*//
    
    /**
     * Change status for multiple items
     */
    protected function changeStatus($targets, $status, $actionName, $additionalData = [])
    {
        if (is_array($targets)) {
            return $this->processBulkAction('changeStatus', $targets, array_merge([
                'status' => $status,
                'action_name' => $actionName
            ], $additionalData));
        }
        
        return $this->changeSingleItemStatus($targets, $status, $actionName, $additionalData);
    }
    
    /**
     * Delete single item (override in implementation)
     */
    protected function deleteSingleItem($itemId, $data = [])
    {
        return [
            'success' => true,
            'message' => "Item {$itemId} deleted successfully"
        ];
    }
    
    /**
     * Change single item status (override in implementation)
     */
    protected function changeSingleItemStatus($itemId, $status, $actionName, $additionalData = [])
    {
        return [
            'success' => true,
            'message' => "{$actionName} completed for item {$itemId}"
        ];
    }
    
    /**
     * Duplicate single item (override in implementation)
     */
    protected function duplicateSingleItem($itemId, $data = [])
    {
        return [
            'success' => true,
            'message' => "Item {$itemId} duplicated successfully"
        ];
    }
    
    /**
     * Prepare data for export (override in implementation)
     */
    protected function prepareExportData($targets)
    {
        return [];
    }
    
    /**
     * Generate export file (override in implementation)
     */
    protected function generateExport($data, $format, $filename)
    {
        return "exports/{$filename}.{$format}";
    }
    
    /**
     * Get download URL for export (override in implementation)
     */
    protected function getExportDownloadUrl($exportPath)
    {
        return "/download/{$exportPath}";
    }
    
    // *----------- Action Lifecycle Methods -----------*//
    
    /**
     * Before action execution hook
     */
    protected function beforeActionExecution($action, $data, $targets)
    {
        // Override in specific implementations for custom pre-action logic
    }
    
    /**
     * After action execution hook
     */
    protected function afterActionExecution($action, $data, $targets, $result)
    {
        // Clear selections after successful bulk actions
        if (is_array($targets) && ($result['success'] ?? false)) {
            $this->clearSelectedRows();
        }
        
        // Refresh component if needed
        if ($result['success'] ?? false) {
            $this->refreshComponent();
        }
    }
    
    /**
     * Log action result
     */
    protected function logActionResult($action, $success, $message)
    {
        $this->actionResults[] = [
            'action' => $action,
            'success' => $success,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];
        
        // Keep only last 100 results
        if (count($this->actionResults) > 100) {
            $this->actionResults = array_slice($this->actionResults, -100);
        }
    }
    
    /**
     * Trigger action event (override for custom event handling)
     */
    protected function triggerActionEvent($action, $data, $targets, $result)
    {
        // Override in specific implementations for custom event handling
    }
    
    /**
     * Clear selected rows
     */
    public function clearSelectedRows()
    {
        $this->selectedRows = [];
    }
    
    /**
     * Refresh component
     */
    public function refreshComponent()
    {
        // Trigger Livewire refresh
        $this->dispatch('$refresh');
    }
    
    /**
     * Get action results
     */
    public function getActionResults(): array
    {
        return $this->actionResults;
    }
    
    /**
     * Get last action result
     */
    public function getLastActionResult(): ?array
    {
        return end($this->actionResults) ?: null;
    }
    
    /**
     * Clear action results
     */
    public function clearActionResults()
    {
        $this->actionResults = [];
    }
}
