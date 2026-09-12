<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers cell rendering for each column type, the toolbar toggles, actions,
 * the custom-template directive body, and eager loading.
 */
class RenderingTest extends TestCase
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

    #[Test]
    public function plain_key_columns_print_their_value(): void
    {
        $this->table()->assertSee('Anderson');
    }

    #[Test]
    public function relation_columns_print_the_related_attribute(): void
    {
        $this->table(['columns' => ColumnSets::withRelations()])
            ->assertSee('Engineering')
            ->assertSee('Artflow Studio');
    }

    #[Test]
    public function function_columns_call_the_method_on_the_row(): void
    {
        $this->table(['columns' => ColumnSets::withComputed()])
            ->assertSee('Alice Anderson');
    }

    #[Test]
    public function raw_blade_columns_are_compiled_and_evaluated(): void
    {
        $this->table(['columns' => ColumnSets::withComputed()])
            ->assertSeeHtml('120,000');
    }

    #[Test]
    public function raw_columns_can_emit_html(): void
    {
        $this->table(['columns' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'position', 'label' => 'Position',
                'raw' => '<span class="badge bg-info">{{ $row->position }}</span>'],
        ]])->assertSeeHtml('<span class="badge bg-info">Engineer</span>');
    }

    #[Test]
    public function raw_column_output_is_escaped_by_double_braces(): void
    {
        DB::table('test_employees')->where('first_name', 'Alice')
            ->update(['position' => '<script>alert(1)</script>']);

        $this->table(['columns' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'position', 'label' => 'Position', 'raw' => '{{ $row->position }}'],
        ]])->assertDontSeeHtml('<script>alert(1)</script>');
    }

    #[Test]
    public function an_empty_result_set_renders_the_empty_state_component(): void
    {
        DB::table('test_employees')->delete();

        $this->table()->assertSee('No data found');
    }

    #[Test]
    public function the_index_column_is_rendered_when_requested(): void
    {
        $this->table(['index' => true])->assertSeeHtml('toggleSort(\'updated_at\')');
    }

    #[Test]
    public function the_checkbox_column_is_rendered_when_enabled(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'checkbox' => true,
        ])->assertSeeHtml('wire:model.live="selectedRows"');
    }

    #[Test]
    public function the_search_box_can_be_switched_off(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'searchable' => false,
        ])->assertDontSeeHtml('wire:model.live.debounce.500ms="search"');
    }

    #[Test]
    public function the_column_visibility_button_can_be_switched_off(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'colvisBtn' => false,
        ])->assertDontSeeHtml('id="columnVisibilityDropdown"');
    }

    #[Test]
    public function the_export_dropdown_only_appears_when_exportable(): void
    {
        $this->table()->assertDontSeeHtml('id="exportDropdown"');

        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'exportable' => true,
        ])->assertSeeHtml('id="exportDropdown"');
    }

    #[Test]
    public function the_print_button_only_appears_when_printable(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'printable' => true,
        ])->assertSeeHtml('aftablePrint(');
    }

    #[Test]
    public function package_assets_can_be_suppressed(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'assets' => false,
        ])->assertDontSeeHtml('AF Table Package styles');
    }

    #[Test]
    public function a_get_action_renders_a_link(): void
    {
        app('router')->get('/employees/{id}', fn () => '')->name('employees.show');

        $this->table(['actions' => [
            'view' => [
                'label' => 'View',
                'route' => 'employees.show',
                'params' => ['id'],
                'class' => 'btn btn-sm btn-primary',
                'icon' => 'fas fa-eye',
            ],
        ]])
            ->assertSeeHtml('btn btn-sm btn-primary')
            ->assertSeeHtml('fas fa-eye')
            ->assertSeeHtml('/employees/1');
    }

    #[Test]
    public function a_delete_action_renders_a_csrf_protected_form(): void
    {
        app('router')->delete('/employees/{id}', fn () => '')->name('employees.destroy');

        $this->table(['actions' => [
            'delete' => [
                'label' => 'Delete',
                'route' => 'employees.destroy',
                'params' => ['id'],
                'method' => 'DELETE',
                'confirm' => 'Are you sure?',
            ],
        ]])
            ->assertSeeHtml('name="_token"')
            ->assertSeeHtml('value="DELETE"')
            ->assertSeeHtml('Are you sure?');
    }

    #[Test]
    public function a_raw_string_action_is_rendered_as_html(): void
    {
        $this->table(['actions' => [
            '<button class="btn-legacy">Go</button>',
        ]])->assertSeeHtml('<button class="btn-legacy">Go</button>');
    }

    #[Test]
    public function a_custom_template_replaces_the_default_table_tag(): void
    {
        $this->table(['customTemplate' => '<table class="table my-theme"></table>'])
            ->assertSeeHtml('class="table my-theme"');
    }

    #[Test]
    public function a_custom_template_still_receives_the_table_id(): void
    {
        $this->table([
            'tableId' => 'custom-id',
            'customTemplate' => '<table class="table my-theme"></table>',
        ])->assertSeeHtml('id="custom-id"');
    }

    #[Test]
    public function relation_columns_are_eager_loaded_rather_than_lazy_loaded(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->table(['columns' => ColumnSets::withRelations()])->assertOk();

        $selects = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn ($q) => str_starts_with(strtolower($q), 'select'))
            ->count();

        // 1 count + 1 page + 1 departments + 1 companies == 4.
        // Anything above ~6 means the relation is being lazy-loaded per row.
        $this->assertLessThanOrEqual(
            6,
            $selects,
            'Relation columns must be eager loaded; got '.$selects.' SELECTs for 5 rows.'
        );
    }
}
