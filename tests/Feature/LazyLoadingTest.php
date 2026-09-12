<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Support\SchemaCache;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * `deferData => true` defers the first data query until the rows scroll in.
 *
 * The property was accepted (and documented) from the start of the 1.6.0 work
 * but was never wired to the island, so it did nothing. The skeleton component
 * it needed had also been shipped unused since 1.5.x.
 */
class LazyLoadingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();

        // Constant cold-start cost; not what these tests are measuring.
        SchemaCache::columnsFor(ColumnSets::MODEL);
    }

    private function table(array $overrides = [])
    {
        return Livewire::test('aftable', array_merge([
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ], $overrides));
    }

    private function queriesWhileRendering(array $params): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test('aftable', $params)->html();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    #[Test]
    public function it_is_off_by_default(): void
    {
        $this->table()->assertSet('deferData', false);
    }

    #[Test]
    public function a_lazy_table_issues_no_data_query_on_first_render(): void
    {
        $eager = $this->queriesWhileRendering([
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'tableId' => 'lazy-off',
        ]);

        $lazy = $this->queriesWhileRendering([
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'deferData' => true,
            'tableId' => 'lazy-on',
        ]);

        $this->assertSame(2, $eager, 'Sanity: an eager table is COUNT + SELECT.');
        $this->assertSame(
            0,
            $lazy,
            'A lazy table must not touch the database until the island is asked for. '.
            "Got {$lazy} queries."
        );
    }

    #[Test]
    public function a_lazy_table_renders_the_skeleton_instead_of_rows(): void
    {
        $html = $this->table(['deferData' => true, 'tableId' => 'lazy-skeleton'])->html();

        $this->assertStringContainsString('aftable-skeleton', $html);
        $this->assertStringNotContainsString('Anderson', $html, 'No real data should be rendered yet.');
    }

    #[Test]
    public function a_lazy_table_emits_the_trigger_livewire_needs(): void
    {
        $html = $this->table(['deferData' => true, 'tableId' => 'lazy-trigger'])->html();

        // Livewire wires a lazy island with wire:intersect.once.
        $this->assertStringContainsString('wire:intersect', $html);
    }

    #[Test]
    public function an_eager_table_renders_rows_and_no_skeleton(): void
    {
        $html = $this->table(['tableId' => 'eager'])->html();

        $this->assertStringContainsString('Anderson', $html);
        $this->assertStringNotContainsString('aftable-skeleton', $html);
    }

    #[Test]
    public function the_toolbar_still_renders_while_the_data_is_deferred(): void
    {
        // The point of lazy is a fast shell: search, filters and the column menu
        // must be usable before the rows arrive.
        $this->table([
            'deferData' => true,
            'exportable' => true,
            'filters' => ColumnSets::filters(),
            'tableId' => 'lazy-shell',
        ])
            ->assertSeeHtml('wire:model.live.debounce.500ms="search"')
            ->assertSeeHtml('id="columnVisibilityDropdown"')
            ->assertSeeHtml('Filter Column');
    }

    // NOTE: there is no unit-level test for "the island resolves once
    // requested". Livewire drives __lazyLoadIsland from request metadata rather
    // than as a plain action, so it cannot be invoked from Livewire::test().
    // The behaviour that matters here — no query, skeleton shown, trigger
    // emitted — is covered above.

    #[Test]
    public function lazy_composes_with_every_column_type(): void
    {
        foreach (['basic', 'withRelations', 'withComputed', 'withJson'] as $set) {
            Livewire::test('aftable', [
                'model' => ColumnSets::MODEL,
                'columns' => ColumnSets::{$set}(),
                'deferData' => true,
                'tableId' => 'lazy-'.$set,
            ])->assertOk();
        }
    }

    #[Test]
    public function lazy_works_in_static_mode_too(): void
    {
        Livewire::test('aftable', [
            'data' => ColumnSets::staticRows(),
            'columns' => ColumnSets::staticColumns(),
            'deferData' => true,
            'tableId' => 'lazy-static',
        ])->assertOk()->assertSeeHtml('aftable-skeleton');
    }
}
