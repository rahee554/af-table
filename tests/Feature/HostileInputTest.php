<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Hostile and nonsensical input, on every entry point a browser can reach.
 *
 * `records`, `search`, `sortColumn`, `sortDirection`, `filterColumn`,
 * `filterOperator`, `filterValue`, `startDate` and `endDate` are all bound to
 * the query string, so every one of them is attacker-controlled text. These
 * tests assert only that the table stays up; the assertions about it staying
 * *correct* live in SearchCorrectnessTest.
 */
class HostileInputTest extends TestCase
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

    // ---- pagination bounds

    #[Test]
    public function paging_past_the_last_page(): void
    {
        $this->t()->call('gotoPage', 9999)->assertOk();
    }

    #[Test]
    public function negative_page(): void
    {
        $this->t()->call('gotoPage', -5)->assertOk();
    }

    #[Test]
    public function per_page_zero(): void
    {
        $this->t()->set('records', 0)->assertOk();
    }

    #[Test]
    public function per_page_absurd(): void
    {
        $this->t()->set('records', 999999)->assertOk();
    }

    // ---- sorting oddities

    #[Test]
    public function sort_on_a_column_that_is_not_declared(): void
    {
        $this->t()->call('toggleSort', 'not_a_column')->assertOk();
    }

    #[Test]
    public function sort_on_a_raw_column(): void
    {
        $this->t(['columns' => ColumnSets::withComputed()])
            ->call('toggleSort', 'salary')->assertOk();
    }

    #[Test]
    public function sort_on_a_json_column(): void
    {
        $this->t(['columns' => ColumnSets::withJson()])
            ->call('toggleSort', 'preferences')->assertOk();
    }

    #[Test]
    public function sort_direction_garbage(): void
    {
        $this->t()->set('sortDirection', 'sideways')->assertOk();
    }

    // ---- search oddities

    #[Test]
    #[DataProvider('searchTerms')]
    public function search_terms(string $term): void
    {
        $this->t()->set('search', $term)->assertOk();
    }

    public static function searchTerms(): array
    {
        return array_map(fn ($s) => [$s], [
            "'; DROP TABLE test_employees; --",
            '%%%',
            '____',
            '\\',
            '<script>alert(1)</script>',
            str_repeat('a', 5000),
            '   ',
            'Ali%ce',
            '日本語',
        ]);
    }

    // ---- filters

    #[Test]
    #[DataProvider('filterOps')]
    public function number_filter_operators(string $op): void
    {
        $this->t(['filters' => ColumnSets::filters()])
            ->set('filterColumn', 'salary')
            ->set('filterOperator', $op)
            ->set('filterValue', '50000')
            ->assertOk();
    }

    public static function filterOps(): array
    {
        return array_map(fn ($s) => [$s], ['=', '!=', '>', '<', '>=', '<=', 'like', 'between', 'garbage']);
    }

    #[Test]
    public function filter_on_an_undeclared_column_is_ignored(): void
    {
        $c = $this->t(['filters' => ColumnSets::filters()])
            ->set('filterColumn', 'email')
            ->set('filterValue', 'x');

        $c->assertOk();
    }

    // ---- exports

    #[Test]
    #[DataProvider('formats')]
    public function every_export_format(string $fmt): void
    {
        $this->t(['exportable' => true])->call('handleExport', $fmt)->assertOk();
    }

    public static function formats(): array
    {
        return array_map(fn ($s) => [$s], ['csv', 'excel', 'xlsx', 'pdf', 'json', 'nonsense']);
    }

    #[Test]
    public function export_with_relation_and_json_columns(): void
    {
        $this->t([
            'columns' => array_merge(ColumnSets::withRelations(), ColumnSets::withJson()),
            'exportable' => true,
        ])->call('handleExport', 'csv')->assertOk();
    }

    // ---- columns

    #[Test]
    public function no_columns_at_all(): void
    {
        $this->t(['columns' => []])->assertOk();
    }

    #[Test]
    public function duplicate_keys(): void
    {
        $this->t(['columns' => [
            ['key' => 'first_name', 'label' => 'A'],
            ['key' => 'first_name', 'label' => 'B'],
        ]])->assertOk();
    }

    #[Test]
    public function hide_every_column(): void
    {
        $this->t()->call('hideAllColumns')->assertOk();
    }

    #[Test]
    public function toggle_an_unknown_column(): void
    {
        $this->t()->call('toggleColumnVisibility', 'nope')->assertOk();
    }

    // ---- themes

    #[Test]
    #[DataProvider('themes')]
    public function every_theme(string $theme): void
    {
        $this->t(['theme' => $theme])->assertOk();
    }

    public static function themes(): array
    {
        return array_map(fn ($s) => [$s], ['bootstrap', 'tailwind', 'none', 'not-a-theme']);
    }

    // ---- defer

    #[Test]
    public function defer_plus_search_and_sort(): void
    {
        $this->t(['deferData' => true])
            ->set('search', 'Ali')
            ->call('toggleSort', 'salary')
            ->assertOk();
    }

    // ---- static mode

    #[Test]
    public function static_mode_full_workout(): void
    {
        Livewire::test('aftable', [
            'data' => ColumnSets::staticRows(),
            'columns' => ColumnSets::staticColumns(),
            'exportable' => true,
        ])
            ->set('search', 'Widget')
            ->call('toggleSort', 'price')
            ->call('handleExport', 'csv')
            ->assertOk();
    }

    #[Test]
    public function static_mode_with_empty_data(): void
    {
        Livewire::test('aftable', ['data' => [], 'columns' => ColumnSets::staticColumns()])->assertOk();
    }

    #[Test]
    public function static_mode_with_ragged_rows(): void
    {
        Livewire::test('aftable', [
            'data' => [['id' => 1], ['id' => 2, 'name' => 'x'], ['name' => 'y']],
            'columns' => ColumnSets::staticColumns(),
        ])->assertOk();
    }

    // ---- misc

    #[Test]
    public function two_tables_keep_separate_state(): void
    {
        $a = $this->t(['tableId' => 'a'])->set('search', 'Alice');
        $b = $this->t(['tableId' => 'b']);

        $this->assertSame('', (string) $b->get('search'));
        $this->assertSame('Alice', $a->get('search'));
    }

    #[Test]
    public function query_constraint_plus_search_plus_sort_plus_export(): void
    {
        $this->t([
            'query' => ['position' => 'Engineer'],
            'exportable' => true,
        ])
            ->set('search', 'a')
            ->call('toggleSort', 'salary')
            ->call('handleExport', 'csv')
            ->assertOk();
    }

    #[Test]
    public function actions_render(): void
    {
        $this->t(['actions' => [
            ['label' => 'Edit', 'url' => '/e/{{ $row->id }}', 'class' => 'btn'],
            ['raw' => '<a href="/x/{{ $row->id }}">X</a>'],
        ]])->assertOk();
    }

    #[Test]
    public function refresh_and_clear_everything(): void
    {
        $this->t(['filters' => ColumnSets::filters()])
            ->set('search', 'Ali')
            ->call('addFilterInstance')
            ->call('clearAllFilters')
            ->call('clearSearch')
            ->call('refreshTable')
            ->assertOk();
    }
}
