<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

class ExportAndSelectionTest extends TestCase
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
            'exportable' => true,
            'checkbox' => true,
        ], $overrides));
    }

    /** Read the CSV body out of Livewire's `download` effect. */
    private function csvFrom($component): string
    {
        $content = data_get($component->effects, 'download.content');

        $this->assertNotNull($content, 'Expected the component to emit a file download.');

        return base64_decode($content);
    }

    #[Test]
    public function csv_export_returns_a_file_download(): void
    {
        $component = $this->table()->call('handleExport', 'csv');

        $component->assertFileDownloaded();
        $this->assertStringEndsWith('.csv', data_get($component->effects, 'download.name'));
        $this->assertStringContainsString('text/csv', (string) data_get($component->effects, 'download.contentType'));
    }

    #[Test]
    public function csv_export_writes_a_header_row_from_the_column_labels(): void
    {
        $csv = $this->csvFrom($this->table()->call('handleExport', 'csv'));

        // A UTF-8 BOM is written first so Excel opens accented / non-Latin
        // text correctly instead of showing mojibake.
        $bom = chr(0xEF).chr(0xBB).chr(0xBF);

        $this->assertStringStartsWith($bom, $csv);
        $this->assertStringStartsWith(
            'ID,"First Name","Last Name",Salary,Position',
            trim(substr($csv, strlen($bom)))
        );
    }

    #[Test]
    public function csv_export_contains_one_line_per_row(): void
    {
        $csv = $this->csvFrom($this->table()->call('handleExport', 'csv'));

        $lines = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertCount(6, $lines, 'Expected 1 header + 5 data rows.');
        $this->assertStringContainsString('Alice', $csv);
    }

    #[Test]
    public function csv_export_respects_the_active_filters(): void
    {
        $component = $this->table(['filters' => ColumnSets::filters()])
            ->set('filterColumn', 'position')
            ->set('filterValue', 'Engineer');

        $csv = $this->csvFrom($component->call('handleExport', 'csv'));
        $lines = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertCount(3, $lines, 'Expected 1 header + 2 Engineer rows.');
        $this->assertStringNotContainsString('Designer', $csv);
    }

    #[Test]
    public function csv_export_respects_the_active_search(): void
    {
        $component = $this->table()->set('search', 'Anderson');

        $csv = $this->csvFrom($component->call('handleExport', 'csv'));
        $lines = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertCount(2, $lines);
    }

    #[Test]
    public function exporting_an_empty_result_set_dispatches_a_warning_instead_of_a_file(): void
    {
        $this->table()
            ->set('search', 'zzzznotfound')
            ->call('handleExport', 'csv')
            ->assertDispatched('showAlert');
    }

    #[Test]
    public function selecting_all_on_the_page_collects_every_visible_row_id(): void
    {
        $expected = DB::table('test_employees')->orderBy('id')->pluck('id')
            ->map(fn ($id) => (string) $id)->all();

        $component = $this->table()->set('selectAll', true);

        $this->assertSame($expected, $component->get('selectedRows'));
        $this->assertSame(5, $component->instance()->getSelectedCount());
    }

    #[Test]
    public function unticking_select_all_clears_the_selection(): void
    {
        $this->table()
            ->set('selectAll', true)
            ->set('selectAll', false)
            ->assertSet('selectedRows', []);
    }

    #[Test]
    public function clear_bulk_selection_resets_both_flags(): void
    {
        $this->table()
            ->set('selectAll', true)
            ->call('clearBulkSelection')
            ->assertSet('selectedRows', [])
            ->assertSet('selectAll', false);
    }

    #[Test]
    public function clear_selection_dispatches_a_notification(): void
    {
        $this->table()
            ->set('selectAll', true)
            ->call('clearSelection')
            ->assertSet('selectedRows', [])
            ->assertDispatched('selectionCleared');
    }

    #[Test]
    public function rows_can_be_selected_individually(): void
    {
        $component = $this->table()->set('selectedRows', ['1', '3']);

        $this->assertSame(2, $component->instance()->getSelectedCount());
    }
}
