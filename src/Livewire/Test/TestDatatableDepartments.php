<?php

namespace ArtflowStudio\Table\Livewire\Test;

use ArtflowStudio\Table\DatatableTrait;
use ArtflowStudio\Table\Testing\Models\TestDepartment;
use Livewire\Component;

class TestDatatableDepartments extends Component
{
    use DatatableTrait;

    public function mount()
    {
        $this->model = TestDepartment::class;
        $this->columns = [
            'id' => 'ID',
            'name' => 'Department Name',
            'code' => 'Code',
            'head_of_department' => 'Head',
            'budget' => 'Budget',
            'employee_count' => 'Employees',
            'location' => 'Location',
            'phone' => 'Phone',
            'status' => 'Status',
            'created_at' => 'Created'
        ];
        $this->searchable = ['name', 'code', 'head_of_department', 'location'];
        $this->sortable = ['id', 'name', 'code', 'budget', 'employee_count', 'status', 'created_at'];
        $this->filterable = [
            'status' => ['active', 'inactive', 'restructuring'],
            'location' => function() {
                return TestDepartment::distinct('location')
                    ->pluck('location')
                    ->filter()
                    ->sort()
                    ->values()
                    ->toArray();
            }
        ];
        $this->exportable = true;
        $this->selectable = true;
        $this->actions = [
            'view' => ['icon' => 'fas fa-eye', 'class' => 'btn-primary btn-sm'],
            'edit' => ['icon' => 'fas fa-edit', 'class' => 'btn-warning btn-sm'],
            'delete' => ['icon' => 'fas fa-trash', 'class' => 'btn-danger btn-sm']
        ];
        $this->perPageOptions = [10, 25, 50, 100];
        $this->perPage = 25;
        $this->sortColumn = 'name';
        $this->sortDirection = 'asc';
    }

    public function render()
    {
        $data = $this->buildQuery()->paginate($this->perPage);
        
        return view('artflow-table::datatable-trait', [
            'data' => $data,
            'columns' => $this->columns,
            'actions' => $this->actions,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
            'searchable' => $this->searchable,
            'filterable' => $this->filterable,
            'exportable' => $this->exportable,
            'selectable' => $this->selectable
        ]);
    }
}
