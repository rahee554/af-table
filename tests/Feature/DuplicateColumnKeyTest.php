<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Two columns reading the same database column.
 *
 * This is the ordinary way to render two JSON paths out of one document:
 *
 *     ['key' => 'preferences', 'json' => 'theme',    'label' => 'Theme'],
 *     ['key' => 'preferences', 'json' => 'language', 'label' => 'Locale'],
 *
 * The component disambiguates the second into the identifier
 * `preferences_language` so both can render (BUG-34). But the identifier is
 * not a database column, and the search path used it as one — so searching any
 * table configured this way died with
 * `Unknown column 'test_employees.preferences_language'`. The shipped test
 * bench was configured this way. See AUDIT.md BUG-52.
 */
class DuplicateColumnKeyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    private function twoJsonPaths(array $overrides = [])
    {
        return Livewire::test('aftable', array_merge([
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'first_name', 'label' => 'First Name'],
                ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
                ['key' => 'preferences', 'json' => 'language', 'label' => 'Locale'],
            ],
        ], $overrides));
    }

    #[Test]
    public function both_columns_survive_disambiguation(): void
    {
        $columns = $this->twoJsonPaths()->instance()->columns;

        $this->assertCount(4, $columns);
        $this->assertArrayHasKey('preferences', $columns);
        $this->assertArrayHasKey('preferences_language', $columns);
    }

    #[Test]
    public function searching_a_table_with_two_paths_on_one_column_does_not_error(): void
    {
        $this->twoJsonPaths()
            ->set('search', 'Alice')
            ->assertOk();
    }

    #[Test]
    public function searching_still_finds_the_expected_row(): void
    {
        $rows = $this->twoJsonPaths()->set('search', 'Alice')->instance()->getData()->items();

        $this->assertNotEmpty($rows, 'The search must still match on the ordinary columns.');
        $this->assertSame('Alice', $rows[0]->first_name);
    }

    #[Test]
    public function the_search_never_references_the_synthetic_identifier(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->twoJsonPaths()->set('search', 'Alice')->instance()->getData();

        $sql = implode(' ', array_column(DB::getQueryLog(), 'query'));

        $this->assertStringNotContainsString(
            'preferences_language',
            $sql,
            'preferences_language is a rendering identifier, never a column.'
        );
    }

    #[Test]
    public function both_json_paths_are_still_searched(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->twoJsonPaths()->set('search', 'dark')->instance()->getData();

        $sql = strtolower(implode(' ', array_column(DB::getQueryLog(), 'query')));

        // Dropping the column from the plain-search list must not drop it from
        // the JSON search as well.
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($sql, 'json_extract'),
            'Both preferences->theme and preferences->language should be searched.'
        );
    }

    #[Test]
    public function sorting_on_the_synthetic_identifier_is_refused(): void
    {
        $c = $this->twoJsonPaths()->call('toggleSort', 'preferences_language');

        $c->assertOk();
        $this->assertNotEmpty($c->instance()->getData()->items());
    }

    #[Test]
    public function two_plain_columns_on_one_source_column_search_it_once(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'first_name', 'label' => 'Given'],
                ['key' => 'first_name', 'label' => 'Also Given'],
            ],
        ])->set('search', 'Alice')->instance()->getData();

        $select = collect(DB::getQueryLog())
            ->first(fn ($q) => str_contains(strtolower($q['query']), 'first_name')
                && str_contains(strtolower($q['query']), 'like'));

        $this->assertNotNull($select);

        // Quote style differs per driver, so match the identifier + LIKE pair.
        preg_match_all('/first_name["`\]]?\s+like/i', $select['query'], $hits);

        $this->assertCount(1, $hits[0], 'The same column should not be LIKEd twice.');
    }

    #[Test]
    public function exporting_a_table_with_duplicate_keys_works(): void
    {
        $c = $this->twoJsonPaths(['exportable' => true])->call('handleExport', 'csv');

        $csv = base64_decode((string) data_get($c->effects, 'download.content'));

        $this->assertStringContainsString('Theme', $csv);
        $this->assertStringContainsString('Locale', $csv);
    }
}
