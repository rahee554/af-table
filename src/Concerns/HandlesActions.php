<?php

namespace ArtflowStudio\Table\Concerns;

/**
 * Row and bulk actions.
 *
 * Consolidated in 1.6.0 from: HasActionHandling.
 *
 * The generic CRUD-action framework that used to live here (executeAction,
 * performAction, processBulkAction, the handle/change* stub handlers, the
 * before/after hooks and the action log) was removed — its "implementations"
 * were fake stubs (e.g. deleteSingleItem() returned a success string without
 * touching a database) and its only entry point, AftableComponent's own
 * handleBulkAction(), was not bound to anything in the shipped UI and had no
 * test coverage. See audit finding 3; that entry point was removed alongside
 * this trait's dead code. The real row/bulk actions the shipped Blade
 * template renders are plain route() links/forms.
 */
trait HandlesActions
{
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
}
