<?php

namespace ArtflowStudio\Table\Traits;

trait HasActions
{
    /**
     * Available actions for records
     */
    protected $actions = [];

    /**
     * Bulk actions available
     */
    protected $bulkActions = [];

    /**
     * Selected record IDs for bulk actions
     */
    protected $selectedRecords = [];

    /**
     * Register a single record action
     */
    public function addAction(string $key, array $config): void
    {
        $this->actions[$key] = array_merge([
            'label' => ucfirst($key),
            'icon' => null,
            'class' => 'btn btn-secondary',
            'confirm' => false,
            'permission' => null,
            'condition' => null,
            'url' => null,
            'route' => null,
            'method' => 'GET',
            'target' => '_self'
        ], $config);
    }

    /**
     * Register a bulk action
     */
    public function addBulkAction(string $key, array $config): void
    {
        $this->bulkActions[$key] = array_merge([
            'label' => ucfirst($key),
            'icon' => null,
            'class' => 'btn btn-secondary',
            'confirm' => false,
            'permission' => null,
            'condition' => null,
            'method' => 'POST',
            'url' => null,
            'route' => null
        ], $config);
    }

    /**
     * Remove an action
     */
    public function removeAction(string $key): void
    {
        unset($this->actions[$key]);
    }

    /**
     * Remove a bulk action
     */
    public function removeBulkAction(string $key): void
    {
        unset($this->bulkActions[$key]);
    }

    /**
     * Get available actions for a record
     */
    public function getActionsForRecord($record): array
    {
        $availableActions = [];

        foreach ($this->actions as $key => $action) {
            if ($this->canPerformAction($action, $record)) {
                $availableActions[$key] = $this->processActionForRecord($action, $record);
            }
        }

        return $availableActions;
    }

    /**
     * Get available bulk actions
     */
    public function getAvailableBulkActions(): array
    {
        $availableActions = [];

        foreach ($this->bulkActions as $key => $action) {
            if ($this->canPerformBulkAction($action)) {
                $availableActions[$key] = $action;
            }
        }

        return $availableActions;
    }

    /**
     * Check if user can perform action on record
     */
    protected function canPerformAction(array $action, $record): bool
    {
        // Check permission
        if (isset($action['permission'])) {
            if (!$this->checkPermission($action['permission'], $record)) {
                return false;
            }
        }

        // Check condition
        if (isset($action['condition'])) {
            if (is_callable($action['condition'])) {
                return call_user_func($action['condition'], $record, $this);
            }

            // If it's a string, try to evaluate it as a method name
            if (is_string($action['condition']) && method_exists($this, $action['condition'])) {
                return $this->{$action['condition']}($record);
            }
        }

        return true;
    }

    /**
     * Check if user can perform bulk action
     */
    protected function canPerformBulkAction(array $action): bool
    {
        // Check permission
        if (isset($action['permission'])) {
            if (!$this->checkPermission($action['permission'])) {
                return false;
            }
        }

        // Check condition
        if (isset($action['condition'])) {
            if (is_callable($action['condition'])) {
                return call_user_func($action['condition'], $this->selectedRecords, $this);
            }

            // If it's a string, try to evaluate it as a method name
            if (is_string($action['condition']) && method_exists($this, $action['condition'])) {
                return $this->{$action['condition']}($this->selectedRecords);
            }
        }

        return true;
    }

    /**
     * Process action configuration for a specific record
     */
    protected function processActionForRecord(array $action, $record): array
    {
        $processedAction = $action;

        // Generate URL if route is provided
        if (isset($action['route'])) {
            $routeParams = $this->extractRouteParams($action['route'], $record);
            $processedAction['url'] = route($routeParams['name'], $routeParams['params']);
        }

        // Generate URL if URL template is provided
        if (isset($action['url']) && str_contains($action['url'], '{')) {
            $processedAction['url'] = $this->processUrlTemplate($action['url'], $record);
        }

        return $processedAction;
    }

    /**
     * Extract route parameters from route configuration
     */
    protected function extractRouteParams($routeConfig, $record): array
    {
        if (is_string($routeConfig)) {
            return [
                'name' => $routeConfig,
                'params' => [$record->getKey()]
            ];
        }

        if (is_array($routeConfig)) {
            $params = [];
            
            if (isset($routeConfig['params'])) {
                foreach ($routeConfig['params'] as $param) {
                    if (str_starts_with($param, '{') && str_ends_with($param, '}')) {
                        $field = trim($param, '{}');
                        $params[] = $record->$field ?? $param;
                    } else {
                        $params[] = $param;
                    }
                }
            } else {
                $params = [$record->getKey()];
            }

            return [
                'name' => $routeConfig['name'],
                'params' => $params
            ];
        }

        return ['name' => '', 'params' => []];
    }

    /**
     * Process URL template with record data
     */
    protected function processUrlTemplate(string $urlTemplate, $record): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($record) {
            $field = $matches[1];
            return $record->$field ?? $matches[0];
        }, $urlTemplate);
    }

    /**
     * Check user permission
     */
    protected function checkPermission(string $permission, $record = null): bool
    {
        // This is a basic implementation
        // In a real application, you would integrate with your authorization system
        
        if (function_exists('auth') && auth()->check()) {
            $user = auth()->user();
            
            if (method_exists($user, 'can')) {
                return $user->can($permission, $record);
            }
        }

        return true; // Default to allow if no auth system
    }

    /**
     * Select records for bulk actions
     */
    public function selectRecords(array $recordIds): void
    {
        $this->selectedRecords = $recordIds;
    }

    /**
     * Select all records on current page
     */
    public function selectAllOnPage(): void
    {
        $records = $this->getData();
        $this->selectedRecords = $records->pluck($this->getKeyName())->toArray();
    }

    /**
     * Clear selected records
     */
    public function clearSelection(): void
    {
        $this->selectedRecords = [];
    }

    /**
     * Get selected record count
     */
    public function getSelectedCount(): int
    {
        return count($this->selectedRecords);
    }

    /**
     * Check if record is selected
     */
    public function isRecordSelected($recordId): bool
    {
        return in_array($recordId, $this->selectedRecords);
    }

    /**
     * Execute bulk action
     */
    public function executeBulkAction(string $actionKey): array
    {
        if (!isset($this->bulkActions[$actionKey])) {
            return [
                'success' => false,
                'message' => 'Bulk action not found'
            ];
        }

        $action = $this->bulkActions[$actionKey];

        if (!$this->canPerformBulkAction($action)) {
            return [
                'success' => false,
                'message' => 'Permission denied for this action'
            ];
        }

        if (empty($this->selectedRecords)) {
            return [
                'success' => false,
                'message' => 'No records selected'
            ];
        }

        try {
            // If action has a handler, execute it
            if (isset($action['handler']) && is_callable($action['handler'])) {
                $result = call_user_func($action['handler'], $this->selectedRecords, $this);
                
                return [
                    'success' => true,
                    'message' => 'Bulk action executed successfully',
                    'result' => $result
                ];
            }

            // If action has a method, execute it
            if (isset($action['method']) && method_exists($this, $action['method'])) {
                $result = $this->{$action['method']}($this->selectedRecords);
                
                return [
                    'success' => true,
                    'message' => 'Bulk action executed successfully',
                    'result' => $result
                ];
            }

            return [
                'success' => false,
                'message' => 'No handler defined for this action'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error executing bulk action: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get primary key name
     */
    protected function getKeyName(): string
    {
        return (new ($this->model))->getKeyName();
    }

    /**
     * Setup default actions
     */
    protected function setupDefaultActions(): void
    {
        // View action
        $this->addAction('view', [
            'label' => 'View',
            'icon' => 'eye',
            'class' => 'btn btn-sm btn-info',
            'route' => [
                'name' => $this->getDefaultRoute('show'),
                'params' => ['{id}']
            ]
        ]);

        // Edit action
        $this->addAction('edit', [
            'label' => 'Edit',
            'icon' => 'edit',
            'class' => 'btn btn-sm btn-primary',
            'route' => [
                'name' => $this->getDefaultRoute('edit'),
                'params' => ['{id}']
            ]
        ]);

        // Delete action
        $this->addAction('delete', [
            'label' => 'Delete',
            'icon' => 'trash',
            'class' => 'btn btn-sm btn-danger',
            'confirm' => 'Are you sure you want to delete this item?',
            'method' => 'DELETE',
            'route' => [
                'name' => $this->getDefaultRoute('destroy'),
                'params' => ['{id}']
            ]
        ]);

        // Bulk delete action
        $this->addBulkAction('delete', [
            'label' => 'Delete Selected',
            'icon' => 'trash',
            'class' => 'btn btn-danger',
            'confirm' => 'Are you sure you want to delete the selected items?',
            'method' => 'DELETE'
        ]);
    }

    /**
     * Get default route name based on model
     */
    protected function getDefaultRoute(string $action): string
    {
        $modelName = strtolower(class_basename($this->model));
        return $modelName . '.' . $action;
    }

    /**
     * Get action statistics
     */
    public function getActionStats(): array
    {
        return [
            'total_actions' => count($this->actions),
            'total_bulk_actions' => count($this->bulkActions),
            'selected_records' => count($this->selectedRecords),
            'actions' => array_keys($this->actions),
            'bulk_actions' => array_keys($this->bulkActions),
            'has_permissions' => $this->hasActionsWithPermissions(),
            'has_conditions' => $this->hasActionsWithConditions()
        ];
    }

    /**
     * Check if any actions have permissions
     */
    protected function hasActionsWithPermissions(): bool
    {
        foreach ($this->actions as $action) {
            if (isset($action['permission'])) {
                return true;
            }
        }

        foreach ($this->bulkActions as $action) {
            if (isset($action['permission'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if any actions have conditions
     */
    protected function hasActionsWithConditions(): bool
    {
        foreach ($this->actions as $action) {
            if (isset($action['condition'])) {
                return true;
            }
        }

        foreach ($this->bulkActions as $action) {
            if (isset($action['condition'])) {
                return true;
            }
        }

        return false;
    }
}
