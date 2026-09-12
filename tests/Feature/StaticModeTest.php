<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Models\TestEmployee;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers "foreach / static" mode: the component fed an array or Collection
 * through the `data` mount parameter instead of an Eloquent model.
 */
class StaticModeTest extends TestCase
{
    private function table(array $overrides = [])
    {
        return Livewire::test('aftable', array_merge([
            'data' => ColumnSets::staticRows(),
            'columns' => ColumnSets::staticColumns(),
            'tableId' => 'static-table',
        ], $overrides));
    }

    private function names($component): array
    {
        return collect($component->viewData('data')->items())->pluck('name')->all();
    }

    #[Test]
    public function passing_data_switches_the_component_into_static_mode(): void
    {
        $this->table()
            ->assertSet('isStaticMode', true)
            ->assertSet('model', '__static__');
    }

    #[Test]
    public function it_renders_every_static_row(): void
    {
        $component = $this->table();

        $this->assertCount(5, $component->viewData('data')->items());
        $component->assertSee('Doohickey');
    }

    #[Test]
    public function static_rows_are_exposed_as_objects_so_row_arrow_property_works(): void
    {
        $items = $this->table()->viewData('data')->items();

        $this->assertIsObject($items[0]);
        $this->assertSame('Widget', $items[0]->name);
    }

    #[Test]
    public function it_accepts_a_collection_of_arrays(): void
    {
        $component = $this->table(['data' => collect(ColumnSets::staticRows())]);

        $this->assertCount(5, $component->viewData('data')->items());
    }

    #[Test]
    public function it_accepts_a_collection_of_eloquent_models(): void
    {
        $this->seedSmallDataset();

        $component = Livewire::test('aftable', [
            'data' => TestEmployee::all(),
            'columns' => ColumnSets::basic(),
            'tableId' => 'static-eloquent',
        ]);

        $component->assertSet('isStaticMode', true);
        $this->assertCount(5, $component->viewData('data')->items());
    }

    #[Test]
    public function in_memory_search_filters_static_rows(): void
    {
        $component = $this->table()->set('search', 'Gadget');

        $this->assertSame(['Gadget'], $this->names($component));
    }

    #[Test]
    public function in_memory_search_still_honours_the_three_character_floor(): void
    {
        $component = $this->table()->set('search', 'Ga');

        $this->assertCount(5, $component->viewData('data')->items());
    }

    #[Test]
    public function in_memory_sorting_orders_static_rows(): void
    {
        $component = $this->table()->call('setSort', 'price', 'asc');

        $this->assertSame(
            ['Whatsit', 'Doohickey', 'Widget', 'Gadget', 'Thingamajig'],
            $this->names($component)
        );

        $component->call('setSort', 'price', 'desc');
        $this->assertSame('Thingamajig', $this->names($component)[0]);
    }

    #[Test]
    public function in_memory_column_filtering_narrows_static_rows(): void
    {
        $component = $this->table(['filters' => ['category' => ['type' => 'distinct']]])
            ->set('filterColumn', 'category')
            ->set('filterValue', 'Parts');

        $this->assertSame(['Doohickey', 'Thingamajig'], $this->names($component));
    }

    #[Test]
    public function distinct_filter_values_are_derived_from_the_static_rows_without_a_query(): void
    {
        $instance = $this->table(['filters' => ['category' => ['type' => 'distinct']]])->instance();

        $this->assertSame(['Misc', 'Parts', 'Tools'], array_values($instance->getDistinctValues('category')));
    }

    #[Test]
    public function raw_columns_can_compute_from_static_row_values(): void
    {
        // Widget: 10.00 * 3 = 30.00
        $this->table()->assertSeeHtml('30.00');
    }

    #[Test]
    public function static_mode_never_touches_the_database(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->table()->set('search', 'Widget')->call('setSort', 'price', 'desc');

        $this->assertSame([], DB::getQueryLog());
    }

    #[Test]
    public function extra_vars_are_available_inside_raw_templates(): void
    {
        Livewire::test('aftable', [
            'data' => [['id' => 1, 'price' => 10]],
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'price', 'label' => 'Price', 'raw' => '{{ $currency }} {{ $row->price }}'],
            ],
            'vars' => ['currency' => 'PKR'],
            'tableId' => 'static-vars',
        ])->assertSeeHtml('PKR 10');
    }

    // NOTE: closure/callable `raw` columns are documented but cannot work —
    // see AUDIT.md BUG-07 and tests/Regression/RawTemplateRegressionTest.php.
}
