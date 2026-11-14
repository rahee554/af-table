<?php

namespace ArtflowStudio\Table\Tests\Feature\Guards;

use ArtflowStudio\Table\Http\Livewire\AftableComponent;
use ArtflowStudio\Table\Tests\Models\TestCompany;
use ArtflowStudio\Table\Tests\Models\TestEmployee;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * User-visible behavioural defects in the component and its template.
 *
 * Findings still OPEN carry #[Group('known-bugs')] on the method and are listed
 * by `composer test:bugs`. Findings already fixed stay here untagged, so they
 * run in the default suite as regression guards.
 */
class ComponentBehaviourGuardTest extends TestCase
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

    // BUG-02 (JSON-path columns) is FIXED — coverage moved to tests/Feature/JsonColumnTest.php.

    #[Test]
    #[TestDox('BUG-04: searching while on page 3 does not reset to page 1, so the table looks empty')]
    public function searching_resets_to_the_first_page(): void
    {
        $companyId = TestCompany::query()->value('id');
        for ($i = 0; $i < 25; $i++) {
            TestEmployee::create([
                'company_id' => $companyId,
                'first_name' => 'Bulk'.$i,
                'last_name' => 'Filler',
                'email' => "bulk{$i}@artflow.test",
                'salary' => 50000,
                'position' => 'Engineer',
            ]);
        }

        $component = $this->table()->call('gotoPage', 3);
        $component->set('search', 'Anderson');

        $this->assertSame(
            1,
            $component->viewData('data')->currentPage(),
            'updatedSearch() guards with `if ($value === $this->search) return;`. Livewire assigns '.
            'the property BEFORE calling the updated hook, so the guard is always true and the '.
            'whole body — resetPage(), cache invalidation, events, session save — never runs.'
        );
    }

    #[Test]
    #[TestDox('BUG-16: toggleSort() does not sync $sortBy, so the sort arrow never moves')]
    public function toggle_sort_keeps_the_sort_indicator_in_sync(): void
    {
        $component = $this->table()->call('toggleSort', 'salary');

        $this->assertSame(
            $component->get('sortColumn'),
            $component->get('sortBy'),
            'The template renders the ↑/↓ indicator with `$sortBy == $column[\'key\']`, but '.
            'toggleSort() only writes $sortColumn. Rows re-order while the arrow stays on the '.
            'previously sorted column.'
        );
    }

    #[Test]
    #[TestDox('BUG-16b: the rendered header shows the arrow on the column that is actually sorted')]
    public function the_rendered_sort_arrow_follows_the_sorted_column(): void
    {
        $html = $this->table()->call('toggleSort', 'salary')->html();

        $salaryHeader = null;
        if (preg_match("/toggleSort\('salary'\).*?<\/th>/s", $html, $m)) {
            $salaryHeader = $m[0];
        }

        $this->assertNotNull($salaryHeader, 'Could not locate the salary <th> in the rendered output.');
        $this->assertMatchesRegularExpression(
            '/aria-sort="(ascending|descending)"/',
            $salaryHeader,
            'The salary header should carry an aria-sort of ascending/descending after toggleSort(\'salary\').'
        );
    }

    #[Test]
    #[TestDox('BUG-13: static (foreach) mode always renders page 1')]
    public function static_mode_pagination_advances(): void
    {
        $component = Livewire::test('aftable', [
            'data' => ColumnSets::staticRows(),
            'columns' => ColumnSets::staticColumns(),
            'records' => 2,
            'tableId' => 'static-paging',
        ]);

        $page1 = collect($component->viewData('data')->items())->pluck('name')->all();
        $component->call('gotoPage', 2);
        $page2 = collect($component->viewData('data')->items())->pluck('name')->all();

        $this->assertNotSame(
            $page1,
            $page2,
            'getStaticPaginatedData() reads `$this->page`, which is not a property on a Livewire 4 '.
            'component (WithPagination stores it in $paginators). It resolves to 1 on every request, '.
            'so pages 2..n show the first page again.'
        );
    }

    #[Test]
    #[TestDox('BUG-08: Excel export throws — exportToExcel() is called with two arguments but declares one')]
    public function excel_export_produces_a_file(): void
    {
        $component = $this->table(['exportable' => true])->call('handleExport', 'xlsx');

        $component->assertFileDownloaded();
    }

    #[Test]
    #[TestDox('BUG-08b (fixed): handleExport() dispatches only to methods that accept its arguments')]
    public function handle_export_dispatches_to_two_argument_methods(): void
    {
        $component = AftableComponent::class;

        // handleExport() passes ($data, $filename). Every branch it can reach
        // must therefore declare at least two parameters. It previously routed
        // 'xlsx' to HasExportFeatures::exportToExcel($filename) - one parameter -
        // so the Collection landed in $filename and the export died with
        // "Array to string conversion".
        foreach (['exportToCsv', 'exportRowsToExcel', 'exportRowsToPdf'] as $method) {
            $reflection = new \ReflectionMethod($component, $method);

            $this->assertGreaterThanOrEqual(
                2,
                $reflection->getNumberOfParameters(),
                "{$method}() is reachable from handleExport(), which passes two arguments."
            );
        }
    }

    #[Test]
    #[TestDox('BUG-10 (fixed): PDF export either produces a PDF or says why it cannot')]
    public function pdf_export_never_silently_substitutes_a_csv(): void
    {
        $component = $this->table(['exportable' => true]);
        $formats = $component->instance()->availableExportFormats();

        $component->call('handleExport', 'pdf');
        $name = (string) data_get($component->effects, 'download.name');

        if ($formats['pdf']) {
            $this->assertStringEndsWith('.pdf', $name);

            return;
        }

        // No PDF package installed: fall back to CSV, but TELL the user.
        // The old code handed over a .csv with no explanation at all.
        $this->assertStringEndsWith('.csv', $name);
        $component->assertDispatched('showAlert');
    }

    #[Test]
    #[TestDox('BUG-10b (fixed): the toolbar only advertises formats this install can produce')]
    public function the_export_menu_hides_unavailable_formats(): void
    {
        $component = $this->table(['exportable' => true]);
        $formats = $component->instance()->availableExportFormats();
        $html = $component->html();

        $this->assertStringContainsString("handleExport('csv')", $html);

        foreach (['xlsx', 'pdf'] as $format) {
            if ($formats[$format]) {
                $this->assertStringContainsString("handleExport('{$format}')", $html);
            } else {
                $this->assertStringNotContainsString("handleExport('{$format}')", $html);
            }
        }
    }

    #[Test]
    #[TestDox('BUG-07 (documented limitation): closure raw columns are rejected, and the docblock no longer promises them')]
    public function closure_raw_columns_are_not_advertised(): void
    {
        // $columns is a public Livewire property, so its contents must survive
        // JSON serialisation into the snapshot. A Closure cannot. This is a
        // framework constraint rather than something the package can fix, so
        // the resolution is to stop documenting callables as supported.
        $source = file_get_contents(
            (new \ReflectionClass(AftableComponent::class))->getFileName()
        );

        $this->assertStringNotContainsString(
            'Callable / Closure (zero Blade overhead',
            $source,
            'The renderRawHtml() docblock still advertises closure raw columns as a '.
            'recommended option, but Livewire cannot serialise a Closure onto a public property.'
        );
    }

    #[Test]
    #[TestDox('BUG-14: the per-page dropdown is hidden whenever the table has 10 or fewer rows')]
    public function the_page_size_dropdown_is_always_available(): void
    {
        $this->table()->assertSeeHtml('wire:model.live="records"');
    }

    #[Test]
    #[TestDox('BUG-15: the @script block listens for `livewire:update`, an event Livewire never fires')]
    public function the_inline_script_listens_for_a_real_livewire_event(): void
    {
        $html = $this->table()->html();

        $this->assertStringNotContainsString(
            'livewire:update',
            $html,
            'Livewire 4 dispatches livewire:init / livewire:navigated and exposes commit hooks. '.
            'There is no `livewire:update` browser event, so the table fade animation and the '.
            '"keep the column dropdown open" behaviour never run.'
        );
    }
}
