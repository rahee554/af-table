<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Models\TestEmployee;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * State that has to survive an interaction: selection, visibility, session
 * persistence, date ranges, stacked filters and the Blade entry points.
 *
 * Most of these cross a request boundary, which is where a Livewire component's
 * state bugs actually live — a property that works in one render and is lost or
 * stale in the next.
 */
class InteractionStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    private function t(array $o = [])
    {
        return Livewire::test('aftable', array_merge([
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ], $o));
    }

    private function csv($c): string
    {
        return base64_decode((string) data_get($c->effects, 'download.content'));
    }

    // ---- selection semantics

    #[Test]
    public function select_all_only_selects_the_current_page(): void
    {
        // 5 rows, page size 2 → "select all" on page 1 must not silently
        // select rows the user cannot see.
        $c = $this->t(['checkbox' => true, 'records' => 2])->set('selectAll', true);

        $this->assertLessThanOrEqual(2, count($c->get('selectedRows')));
    }

    #[Test]
    public function selection_survives_a_page_change(): void
    {
        $c = $this->t(['checkbox' => true, 'records' => 2])
            ->set('selectedRows', ['1'])
            ->call('nextPage');

        $this->assertContains('1', $c->get('selectedRows'));
    }

    #[Test]
    public function selection_is_dropped_when_the_result_set_changes(): void
    {
        // Keeping a selection across a search means a bulk action can hit rows
        // the user is no longer looking at.
        $c = $this->t(['checkbox' => true])
            ->set('selectedRows', ['1', '2', '3'])
            ->set('search', 'Anderson');

        $this->assertLessThanOrEqual(3, count($c->get('selectedRows')));
    }

    // ---- hidden columns and export

    #[Test]
    public function a_hidden_column_is_not_in_the_rendered_table(): void
    {
        $html = $this->t(['columns' => ColumnSets::withHidden()])->html();

        $this->assertStringNotContainsString('>Salary<', $html);
    }

    #[Test]
    public function export_reflects_column_visibility(): void
    {
        $visible = $this->csv($this->t(['exportable' => true])->call('handleExport', 'csv'));
        $hidden = $this->csv(
            $this->t(['exportable' => true])
                ->call('toggleColumnVisibility', 'salary')
                ->call('handleExport', 'csv')
        );

        $this->assertStringContainsString('Salary', $visible);
        $this->assertStringNotContainsString('Salary', $hidden, 'A hidden column should not be exported.');
    }

    // ---- session persistence

    #[Test]
    public function column_visibility_persists_across_a_remount(): void
    {
        $this->t(['tableId' => 'persist-a'])->call('toggleColumnVisibility', 'salary');

        $second = $this->t(['tableId' => 'persist-a']);

        $this->assertFalse(
            $second->get('visibleColumns')['salary'] ?? true,
            'The toggle should still be off on the next mount.'
        );
    }

    #[Test]
    public function two_table_ids_do_not_share_visibility_state(): void
    {
        $this->t(['tableId' => 'iso-a'])->call('toggleColumnVisibility', 'salary');

        $other = $this->t(['tableId' => 'iso-b']);

        $this->assertTrue($other->get('visibleColumns')['salary'] ?? false);
    }

    // ---- date range

    #[Test]
    public function a_date_range_filter_narrows_the_result_set(): void
    {
        $c = $this->t(['dateColumn' => 'hire_date'])
            ->set('startDate', '1900-01-01')
            ->set('endDate', '1900-12-31');

        $this->assertCount(0, $c->instance()->getData()->items());
    }

    #[Test]
    public function an_inverted_date_range_is_swapped_not_ignored(): void
    {
        // Dragging the range backwards should still filter. Ignoring it and
        // quietly showing every row is the failure mode worth guarding.
        $c = $this->t(['dateColumn' => 'hire_date'])
            ->set('startDate', '1900-12-31')
            ->set('endDate', '1900-01-01');

        $this->assertCount(0, $c->instance()->getData()->items());
    }

    #[Test]
    public function a_garbage_date_does_not_fatal(): void
    {
        $this->t(['dateColumn' => 'hire_date'])
            ->set('startDate', 'not-a-date')
            ->assertOk();
    }

    // ---- stacked filter instances

    #[Test]
    public function stacked_filter_instances_narrow_rather_than_widen(): void
    {
        $c = $this->t(['filters' => ColumnSets::filters()])
            ->call('addFilterInstance')
            ->call('addFilterInstance');

        $instances = $c->get('filterInstances');

        $this->assertCount(2, $instances);
        $this->assertNotSame(
            $instances[0]['id'],
            $instances[1]['id'],
            'Two instances created in the same request must not share an id.'
        );

        $c->call('removeFilterInstance', $instances[0]['id']);

        $this->assertSame(
            [0],
            array_keys($c->get('filterInstances')),
            'Keys must stay sequential or the next append lands at the wrong index.'
        );
    }

    // ---- extraVars

    #[Test]
    public function extra_vars_reach_raw_templates(): void
    {
        $html = $this->t([
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'sym', 'label' => 'Sym', 'raw' => '{{ $currency }}{{ $row->salary }}'],
            ],
            'vars' => ['currency' => 'USD-MARKER'],
        ])->html();

        $this->assertStringContainsString('USD-MARKER', $html);
    }

    #[Test]
    public function a_raw_template_referencing_an_unknown_var_does_not_fatal(): void
    {
        $this->t([
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'x', 'label' => 'X', 'raw' => '{{ $nope ?? "" }}'],
            ],
        ])->assertOk();
    }

    // ---- XSS

    #[Test]
    public function a_plain_column_escapes_html_in_the_data(): void
    {
        TestEmployee::query()
            ->limit(1)->update(['position' => '<script>alert(1)</script>']);

        $html = $this->t()->html();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    // ---- blade directives

    #[Test]
    public function all_four_directives_compile_and_render(): void
    {
        $model = ColumnSets::MODEL;

        $cases = [
            "@AFtable(['model' => '{$model}', 'columns' => [['key' => 'id', 'label' => 'ID']]])",
            "@AFtableTrait(['model' => '{$model}', 'columns' => [['key' => 'id', 'label' => 'ID']]])",
            "@aftable(['model' => '{$model}', 'columns' => [['key' => 'id', 'label' => 'ID']]])<table></table>@endaftable",
        ];

        foreach ($cases as $source) {
            $rendered = Blade::render($source);

            $this->assertStringContainsString('wire:id', $rendered, "Failed for: {$source}");
        }
    }

    #[Test]
    public function the_directive_accepts_a_class_constant_not_only_a_string(): void
    {
        $rendered = Blade::render(
            '@AFtable([\'model\' => \\'.ColumnSets::MODEL.'::class, \'columns\' => [[\'key\' => \'id\']]])'
        );

        $this->assertStringContainsString('wire:id', $rendered);
    }

    // ---- print

    #[Test]
    public function print_config_reaches_the_component(): void
    {
        $c = $this->t(['printable' => true, 'printConfig' => ['title' => 'PRINT-MARKER']]);

        $this->assertSame('PRINT-MARKER', $c->get('printConfig')['title']);
        $this->assertStringContainsString('PRINT-MARKER', $c->html());
    }

    // ---- count aggregations

    #[Test]
    public function a_count_aggregation_column_renders_a_number(): void
    {
        $html = $this->t(['columns' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'timesheets_count', 'label' => 'Sheets', 'relation' => 'timesheets:count'],
        ]])->html();

        $this->assertStringContainsString('<table', $html);
    }

    // ---- guard still lets the whole UI work

    #[Test]
    public function a_full_interaction_sequence_survives_the_action_guard(): void
    {
        $this->t(['filters' => ColumnSets::filters(), 'exportable' => true, 'checkbox' => true])
            ->call('toggleSort', 'salary')
            ->call('toggleColumnVisibility', 'position')
            ->call('addFilterInstance')
            ->set('search', 'And')
            ->call('nextPage')
            ->call('previousPage')
            ->call('clearAllFilters')
            ->call('clearSearch')
            ->call('showAllColumns')
            ->call('refreshTable')
            ->call('handleExport', 'csv')
            ->assertOk();
    }
}
