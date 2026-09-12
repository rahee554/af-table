<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers the primary filter (column + operator + value), the additional
 * "filter instance" rows, and distinct-value population for select filters.
 */
class FilteringTest extends TestCase
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
            'filters' => ColumnSets::filters(),
        ], $overrides));
    }

    private function names($component): array
    {
        return collect($component->viewData('data')->items())
            ->pluck('first_name')->sort()->values()->all();
    }

    #[Test]
    public function an_equals_filter_narrows_the_result_set(): void
    {
        $component = $this->table()
            ->set('filterColumn', 'position')
            ->set('filterValue', 'Engineer');

        $this->assertSame(['Alice', 'Chloé'], $this->names($component));
    }

    #[Test]
    #[DataProvider('numericOperators')]
    public function numeric_operators_are_applied(string $operator, int $value, int $expectedCount): void
    {
        $component = $this->table()
            ->set('filterColumn', 'salary')
            ->set('filterOperator', $operator)
            ->set('filterValue', $value);

        $this->assertCount($expectedCount, $component->viewData('data')->items());
    }

    public static function numericOperators(): array
    {
        // salaries: 60000, 70000, 85000, 90000, 120000
        return [
            'equal' => ['=', 85000, 1],
            'not equal' => ['!=', 85000, 4],
            'greater than' => ['>', 85000, 2],
            'greater or equal' => ['>=', 85000, 3],
            'less than' => ['<', 85000, 2],
            'less or equal' => ['<=', 85000, 3],
        ];
    }

    #[Test]
    public function a_like_filter_matches_partial_values(): void
    {
        $component = $this->table()
            ->set('filterColumn', 'first_name')
            ->set('filterOperator', 'like')
            ->set('filterValue', 'li');

        $this->assertSame(['Alice'], $this->names($component));
    }

    #[Test]
    public function an_empty_filter_value_is_ignored(): void
    {
        $component = $this->table()
            ->set('filterColumn', 'position')
            ->set('filterValue', '');

        $this->assertCount(5, $component->viewData('data')->items());
    }

    #[Test]
    public function changing_the_filter_column_resets_value_and_operator(): void
    {
        $this->table()
            ->set('filterColumn', 'salary')
            ->set('filterOperator', '>')
            ->set('filterValue', 80000)
            ->set('filterColumn', 'position')
            ->assertSet('filterValue', null)
            ->assertSet('filterOperator', '=');
    }

    #[Test]
    public function distinct_values_are_preloaded_for_distinct_filters(): void
    {
        $values = $this->table()->instance()->getDistinctValues('position');

        sort($values);
        $this->assertSame(['Designer', 'Director', 'Engineer', 'Intern'], array_values($values));
    }

    #[Test]
    public function distinct_values_for_an_unfiltered_column_are_empty(): void
    {
        $this->assertSame([], $this->table()->instance()->getDistinctValues('last_name'));
    }

    #[Test]
    public function add_filter_instance_appends_an_empty_row(): void
    {
        $instances = $this->table()->call('addFilterInstance')->get('filterInstances');

        $this->assertCount(1, $instances);
        $this->assertNull($instances[0]['column']);
        $this->assertSame('=', $instances[0]['operator']);
        $this->assertArrayHasKey('id', $instances[0]);
    }

    #[Test]
    public function a_filter_instance_narrows_the_result_set(): void
    {
        $component = $this->table()->call('addFilterInstance');
        $id = $component->get('filterInstances')[0]['id'];

        $component->call('updateFilterInstance', $id, 'column', 'position')
            ->call('updateFilterInstance', $id, 'value', 'Engineer');

        $this->assertSame(['Alice', 'Chloé'], $this->names($component));
    }

    #[Test]
    public function multiple_filter_instances_are_combined_with_and(): void
    {
        $component = $this->table()->call('addFilterInstance')->call('addFilterInstance');
        $ids = collect($component->get('filterInstances'))->pluck('id')->all();

        $component->call('updateFilterInstance', $ids[0], 'column', 'position')
            ->call('updateFilterInstance', $ids[0], 'value', 'Engineer')
            ->call('updateFilterInstance', $ids[1], 'column', 'salary')
            ->call('updateFilterInstance', $ids[1], 'operator', '>')
            ->call('updateFilterInstance', $ids[1], 'value', 86000);

        $this->assertSame(['Alice'], $this->names($component));
    }

    #[Test]
    public function clear_all_filter_instances_empties_the_list(): void
    {
        $this->table()
            ->call('addFilterInstance')
            ->call('addFilterInstance')
            ->call('clearAllFilterInstances')
            ->assertSet('filterInstances', []);
    }

    #[Test]
    public function the_primary_filter_and_a_filter_instance_stack(): void
    {
        $component = $this->table()
            ->set('filterColumn', 'position')
            ->set('filterValue', 'Engineer')
            ->call('addFilterInstance');

        $id = $component->get('filterInstances')[0]['id'];
        $component->call('updateFilterInstance', $id, 'column', 'salary')
            ->call('updateFilterInstance', $id, 'operator', '<')
            ->call('updateFilterInstance', $id, 'value', 86000);

        $this->assertSame(['Chloé'], $this->names($component));
    }
}
