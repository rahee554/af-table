<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers the `query` mount parameter (base constraints applied to every page)
 * and `countAggregations` (withCount without loading relations).
 */
class QueryConstraintTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    private function names($component): array
    {
        return collect($component->viewData('data')->items())
            ->pluck('first_name')->sort()->values()->all();
    }

    #[Test]
    public function an_associative_query_array_adds_a_where_clause(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'query' => ['position' => 'Engineer'],
        ]);

        $this->assertSame(['Alice', 'Chloé'], $this->names($component));
    }

    #[Test]
    public function a_two_element_constraint_array_adds_an_equals_clause(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'query' => [['position', 'Designer']],
        ]);

        $this->assertSame(['Bob'], $this->names($component));
    }

    #[Test]
    public function a_three_element_constraint_array_adds_an_operator_clause(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'query' => [['salary', '>', 86000]],
        ]);

        $this->assertSame(['Alice', 'Émile'], $this->names($component));
    }

    #[Test]
    public function base_constraints_survive_searching_and_sorting(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'query' => ['position' => 'Engineer'],
        ])->set('search', 'Ali')->call('toggleSort', 'salary');

        $this->assertSame(['Alice'], $this->names($component));
    }

    #[Test]
    public function base_constraints_also_bound_the_export(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'query' => ['position' => 'Engineer'],
            'exportable' => true,
        ])->call('handleExport', 'csv');

        $csv = base64_decode(data_get($component->effects, 'download.content'));

        $this->assertStringNotContainsString('Designer', $csv);
        $this->assertStringContainsString('Alice', $csv);
    }

    // NOTE: `countAggregations` is NOT covered here — withCount() is wiped by a
    // later select('*'). See AUDIT.md BUG-03 and tests/Regression/QueryBuildingRegressionTest.php.
}
