<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers toggleSort(), setSort(), clearSort() and the resulting row order.
 */
class SortingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    private function table(array $overrides = [])
    {
        return Livewire::test('aftable', array_merge([
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ], $overrides));
    }

    private function salaries($component): array
    {
        return collect($component->viewData('data')->items())
            ->pluck('salary')
            ->map(fn ($s) => (int) $s)
            ->all();
    }

    #[Test]
    public function toggle_sort_selects_a_new_column_ascending(): void
    {
        $this->table()
            ->call('toggleSort', 'salary')
            ->assertSet('sortColumn', 'salary')
            ->assertSet('sortDirection', 'asc');
    }

    #[Test]
    public function toggling_the_same_column_flips_the_direction(): void
    {
        $this->table()
            ->call('toggleSort', 'salary')
            ->assertSet('sortDirection', 'asc')
            ->call('toggleSort', 'salary')
            ->assertSet('sortDirection', 'desc')
            ->call('toggleSort', 'salary')
            ->assertSet('sortDirection', 'asc');
    }

    #[Test]
    public function ascending_sort_actually_orders_the_rows(): void
    {
        $component = $this->table()->call('toggleSort', 'salary');

        $this->assertSame([60000, 70000, 85000, 90000, 120000], $this->salaries($component));
    }

    #[Test]
    public function descending_sort_actually_orders_the_rows(): void
    {
        $component = $this->table()
            ->call('toggleSort', 'salary')
            ->call('toggleSort', 'salary');

        $this->assertSame([120000, 90000, 85000, 70000, 60000], $this->salaries($component));
    }

    #[Test]
    public function set_sort_applies_a_column_and_direction_programmatically(): void
    {
        $component = $this->table()->call('setSort', 'salary', 'desc');

        $component->assertSet('sortColumn', 'salary')->assertSet('sortDirection', 'desc');
        $this->assertSame(120000, $this->salaries($component)[0]);
    }

    #[Test]
    public function set_sort_rejects_an_unknown_column(): void
    {
        $this->table()
            ->call('setSort', 'not_a_column', 'asc')
            ->assertSet('sortColumn', 'id');
    }

    #[Test]
    public function set_sort_normalises_a_bogus_direction(): void
    {
        $this->table()
            ->call('setSort', 'salary', 'diagonally')
            ->assertSet('sortDirection', 'asc');
    }

    #[Test]
    public function clear_sort_removes_the_active_column(): void
    {
        $this->table()
            ->call('toggleSort', 'salary')
            ->call('clearSort')
            ->assertSet('sortColumn', null)
            ->assertSet('sortDirection', 'asc');
    }

    #[Test]
    public function toggle_sort_ignores_a_column_that_is_not_configured(): void
    {
        $this->table()
            ->call('toggleSort', 'secret_column')
            ->assertSet('sortColumn', 'id');
    }

    #[Test]
    public function toggle_sort_ignores_function_columns(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withComputed(),
        ])
            ->call('toggleSort', 'full_name')
            ->assertSet('sortColumn', 'id');
    }

    #[Test]
    public function sorting_by_a_single_level_relation_orders_by_the_related_column(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withRelations(),
            'sortBy' => 'department_name',
            'sortDirection' => 'asc',
        ]);

        $component->assertOk();

        $departments = collect($component->viewData('data')->items())
            ->map(fn ($row) => $row->department?->name)
            ->filter()
            ->values()
            ->all();

        $this->assertSame($departments, collect($departments)->sort()->values()->all());
    }

    #[Test]
    public function the_sort_direction_validator_only_accepts_asc_and_desc(): void
    {
        $instance = $this->table()->instance();
        $validate = fn ($v) => (fn () => $this->validateSortDirection($v))->call($instance);

        $this->assertSame('asc', $validate('asc'));
        $this->assertSame('desc', $validate('desc'));
        $this->assertSame('asc', $validate('ASC; DROP TABLE users'));
        $this->assertSame('asc', $validate(null));
    }
}
