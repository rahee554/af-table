<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Search and filtering must never silently widen.
 *
 * A crash is obvious and gets reported. A search box that quietly returns rows
 * it should have excluded is not: the user reads the result as the answer. Each
 * of these pins a case where the table used to return more than it should —
 * LIKE wildcards leaking out of the search term (BUG-48), an unvalidated page
 * size (BUG-49), an undeclared filter column, a garbage operator.
 */
class SearchCorrectnessTest extends TestCase
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

    private function rows($c): array
    {
        return $c->instance()->getData()->items();
    }

    // ---- static export content

    #[Test]
    public function static_export_has_the_right_headers_and_rows(): void
    {
        $c = Livewire::test('aftable', [
            'data' => ColumnSets::staticRows(),
            'columns' => ColumnSets::staticColumns(),
            'exportable' => true,
        ])->call('handleExport', 'csv');

        $csv = $this->csv($c);

        $this->assertStringContainsString('Widget', $csv);
        $this->assertStringContainsString('Doohickey', $csv);

        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertCount(6, $lines, '1 header + 5 static rows');
    }

    #[Test]
    public function static_export_respects_the_active_search(): void
    {
        $c = Livewire::test('aftable', [
            'data' => ColumnSets::staticRows(),
            'columns' => ColumnSets::staticColumns(),
            'exportable' => true,
        ])->set('search', 'Widget')->call('handleExport', 'csv');

        $csv = $this->csv($c);

        $this->assertStringContainsString('Widget', $csv);
        $this->assertStringNotContainsString('Doohickey', $csv);
    }

    #[Test]
    public function static_export_filename_is_not_the_sentinel(): void
    {
        $c = Livewire::test('aftable', [
            'data' => ColumnSets::staticRows(),
            'columns' => ColumnSets::staticColumns(),
            'exportable' => true,
        ])->call('handleExport', 'csv');

        $name = (string) data_get($c->effects, 'download.name');

        $this->assertStringNotContainsString('__static__', $name);
    }

    // ---- search must never silently widen

    #[Test]
    public function a_search_that_matches_nothing_returns_nothing(): void
    {
        $this->assertCount(0, $this->rows($this->t()->set('search', 'zzzznotfound')));
    }

    #[Test]
    public function a_very_long_search_term_does_not_return_everything(): void
    {
        $this->assertCount(0, $this->rows($this->t()->set('search', str_repeat('z', 5000))));
    }

    #[Test]
    public function a_wildcard_only_search_is_not_treated_as_match_all(): void
    {
        // '%%%' is >= 3 chars, so it passes the floor. If % survives into the
        // LIKE it matches every row.
        $this->assertCount(0, $this->rows($this->t()->set('search', '%%%')));
    }

    #[Test]
    public function an_underscore_search_is_not_a_single_char_wildcard(): void
    {
        $this->assertCount(0, $this->rows($this->t()->set('search', '____')));
    }

    #[Test]
    public function a_percent_inside_a_real_term_does_not_widen_the_match(): void
    {
        $this->assertCount(0, $this->rows($this->t()->set('search', 'Ali%ce')));
    }

    // ---- filters must never silently widen

    #[Test]
    public function an_undeclared_filter_column_does_not_filter(): void
    {
        $all = count($this->rows($this->t(['filters' => ColumnSets::filters()])));

        $filtered = $this->rows(
            $this->t(['filters' => ColumnSets::filters()])
                ->set('filterColumn', 'email')
                ->set('filterValue', 'nobody@example.com')
        );

        $this->assertCount($all, $filtered, 'An undeclared filter is ignored, not applied.');
    }

    #[Test]
    public function a_garbage_operator_does_not_silently_match_everything(): void
    {
        $rows = $this->rows(
            $this->t(['filters' => ColumnSets::filters()])
                ->set('filterColumn', 'salary')
                ->set('filterOperator', 'garbage')
                ->set('filterValue', '1')
        );

        $this->assertLessThanOrEqual(5, count($rows));
    }

    #[Test]
    public function greater_than_actually_filters(): void
    {
        $rows = $this->rows(
            $this->t(['filters' => ColumnSets::filters()])
                ->set('filterColumn', 'salary')
                ->set('filterOperator', '>')
                ->set('filterValue', '999999')
        );

        $this->assertCount(0, $rows, 'No employee earns a million.');
    }

    // ---- pagination

    #[Test]
    public function per_page_zero_does_not_return_zero_rows_forever(): void
    {
        $rows = $this->rows($this->t()->set('records', 0));

        $this->assertNotCount(0, $rows, 'A 0 page size must fall back, not blank the table.');
    }

    #[Test]
    public function page_size_is_clamped_to_the_configured_options(): void
    {
        $c = $this->t()->set('records', 999999);

        $this->assertLessThanOrEqual(500, $c->instance()->getData()->perPage());
    }

    // ---- columns

    #[Test]
    public function two_columns_on_the_same_source_column_both_survive(): void
    {
        $c = $this->t(['columns' => [
            ['key' => 'first_name', 'label' => 'Given'],
            ['key' => 'first_name', 'label' => 'Also Given'],
        ]]);

        $this->assertCount(2, $c->instance()->columns, 'BUG-34: they used to overwrite each other.');
    }

    #[Test]
    public function hiding_every_column_still_renders_a_table(): void
    {
        $html = $this->t()->call('hideAllColumns')->html();

        $this->assertStringContainsString('<table', $html);
    }

    // ---- sorting

    #[Test]
    public function sorting_actually_reorders(): void
    {
        $asc = $this->rows($this->t()->call('toggleSort', 'salary'));
        $desc = $this->rows($this->t()->call('toggleSort', 'salary')->call('toggleSort', 'salary'));

        $this->assertNotSame(
            array_map(fn ($r) => $r->id, $asc),
            array_map(fn ($r) => $r->id, $desc)
        );
    }

    #[Test]
    public function an_undeclared_sort_column_is_refused_not_applied(): void
    {
        $c = $this->t()->call('toggleSort', 'salary; DROP TABLE test_employees');

        $this->assertNotSame('salary; DROP TABLE test_employees', $c->get('sortColumn'));
        $this->assertNotEmpty($this->rows($c));
    }

    // ---- relation columns

    #[Test]
    public function a_relation_column_renders_its_value(): void
    {
        $html = $this->t(['columns' => ColumnSets::withRelations()])->html();

        $this->assertStringContainsString('Engineering', $html);
    }

    #[Test]
    public function a_nested_relation_column_renders_its_value(): void
    {
        $html = $this->t(['columns' => ColumnSets::withRelations()])->html();

        $this->assertStringContainsString('Artflow Studio', $html);
    }

    // ---- json

    #[Test]
    public function a_json_path_on_a_column_that_does_not_exist_is_blank_not_fatal(): void
    {
        $html = $this->t(['columns' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'no_such_column', 'json' => 'a.b', 'label' => 'Nope'],
        ]])->html();

        $this->assertStringContainsString('<table', $html);
    }

    // ---- theme fallback

    #[Test]
    public function an_unknown_theme_falls_back_rather_than_rendering_unstyled(): void
    {
        $ui = $this->t(['theme' => 'not-a-theme'])->instance()->themeClasses();

        $this->assertNotEmpty($ui['table'] ?? '', 'An unknown preset should fall back to the default.');
    }
}
