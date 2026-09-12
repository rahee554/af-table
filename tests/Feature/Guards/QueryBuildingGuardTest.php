<?php

namespace ArtflowStudio\Table\Tests\Feature\Guards;

use ArtflowStudio\Table\Tests\Models\TestDepartment;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Query-layer defects: features that build a query correctly and then have it
 * silently overwritten a few lines later.
 *
 * Findings still OPEN carry #[Group('known-bugs')] on the method and are listed
 * by `composer test:bugs`. Findings already fixed stay here untagged, so they
 * run in the default suite as regression guards.
 */
class QueryBuildingGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    #[Test]
    #[TestDox('BUG-03: countAggregations is wiped by the select() that runs right after withCount()')]
    public function count_aggregations_survive_column_optimisation(): void
    {
        $rows = Livewire::test('aftable', [
            'model' => TestDepartment::class,
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Name'],
            ],
            'countAggregations' => ['employees' => null],
        ])->viewData('data')->items();

        $this->assertNotNull(
            $rows[0]->employees_count,
            'buildUnifiedQuery() calls applyCountAggregations() (which adds withCount subselects) '.
            "and then applyColumnOptimization() runs \$query->select(['*']), discarding them."
        );
    }

    #[Test]
    #[TestDox('BUG-03b: countAggregations is a protected property, so it is lost after the first request')]
    public function count_aggregations_survive_a_livewire_round_trip(): void
    {
        $component = Livewire::test('aftable', [
            'model' => TestDepartment::class,
            'columns' => [['key' => 'id', 'label' => 'ID'], ['key' => 'name', 'label' => 'Name']],
            'countAggregations' => ['employees' => null],
        ]);

        // Any second request re-hydrates the component; protected properties are not persisted.
        $component->call('toggleSort', 'name');

        $this->assertNotEmpty(
            $component->instance()->getCountAggregations(),
            'HasCountAggregations::$countAggregations must be a public Livewire property '.
            '(or be recomputed in hydrate()) to survive between requests.'
        );
    }

    #[Test]
    #[TestDox('BUG-18: search silently returns every row when the term is non-Latin')]
    public function searching_a_non_latin_term_filters_rather_than_matching_everything(): void
    {
        DB::table('test_employees')->where('first_name', 'Alice')->update(['position' => 'مهندس']);

        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ])->set('search', 'مهندس');

        $this->assertCount(
            1,
            $component->viewData('data')->items(),
            'sanitizeSearch() runs preg_replace(\'/[^\w\s\-_@.]/i\') with no /u modifier, so every '.
            'non-ASCII byte is stripped. The term becomes empty and applyOptimizedSearch() '.
            'returns early — the user sees the unfiltered table and no error.'
        );
    }

    #[Test]
    #[TestDox('BUG-24: getSortableColumns() always returns an empty array')]
    public function get_sortable_columns_lists_the_sortable_columns(): void
    {
        $instance = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ])->instance();

        $this->assertNotEmpty(
            $instance->getSortableColumns(),
            'getSortableColumns() passes the column CONFIG ARRAY to isColumnSortable(), which '.
            'expects the column KEY, so every comparison fails and the result is always [].'
        );
    }

    #[Test]
    #[TestDox('BUG-06: a relation column declared without an explicit `key` gets a numeric array key')]
    public function relation_columns_without_a_key_are_keyed_by_their_relation(): void
    {
        $columns = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::relationsWithoutKey(),
        ])->get('columns');

        $this->assertSame(
            ['id', 'department_name'],
            array_keys($columns),
            'initializeColumns() falls back to the numeric array index when a direct-array '.
            "column has no 'key'. The column ends up as columns[1], which breaks sorting, "
            .'the visibility toggle id and session persistence.'
        );
    }

    #[Test]
    #[TestDox('BUG-23: removeFilterInstance() leaves a non-sequential array')]
    public function removing_a_filter_instance_keeps_the_array_list_shaped(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'filters' => ColumnSets::filters(),
        ])->call('addFilterInstance')->call('addFilterInstance');

        $ids = collect($component->get('filterInstances'))->pluck('id')->all();
        $component->call('removeFilterInstance', $ids[0]);

        $this->assertSame(
            [0],
            array_keys($component->get('filterInstances')),
            'array_filter() preserves keys. Without array_values() the property serialises as '.
            'a JSON object instead of an array and $filterInstances[] appends at the wrong index.'
        );
    }
}
