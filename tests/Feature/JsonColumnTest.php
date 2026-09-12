<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Models\TestCompany;
use ArtflowStudio\Table\Tests\Models\TestEmployee;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * JSON-path columns: `['key' => 'preferences', 'json' => 'theme']`.
 *
 * This whole feature used to fatal the entire table render — HasJsonSupport and
 * HasTemplateRendering both declared getNestedValue() with their arguments in
 * opposite orders, and the trait conflict resolution picked the wrong one
 * (AUDIT.md BUG-02). Fixed by renaming the template-rendering one.
 */
class JsonColumnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    private function table(array $columns)
    {
        return Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => $columns,
        ]);
    }

    #[Test]
    public function a_json_path_column_renders_without_error(): void
    {
        $this->table(ColumnSets::withJson())->assertOk();
    }

    #[Test]
    public function it_extracts_a_top_level_key_from_an_array_cast_column(): void
    {
        // Employees alternate theme dark/light in DatasetSeeder.
        $this->table([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
        ])
            ->assertSee('dark')
            ->assertSee('light');
    }

    #[Test]
    public function it_extracts_from_a_raw_json_string_column(): void
    {
        // Write JSON as a plain string, bypassing the array cast.
        TestEmployee::query()->whereKey(1)->toBase()
            ->update(['preferences' => json_encode(['theme' => 'solarized', 'lang' => 'ur'])]);

        $this->table([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
        ])->assertSee('solarized');
    }

    #[Test]
    public function it_supports_dot_notation_for_nested_paths(): void
    {
        TestEmployee::query()->whereKey(1)->toBase()->update([
            'preferences' => json_encode(['ui' => ['sidebar' => ['pinned' => 'always']]]),
        ]);

        $this->table([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'ui.sidebar.pinned', 'label' => 'Sidebar'],
        ])->assertSee('always');
    }

    #[Test]
    public function a_missing_path_renders_an_empty_cell_rather_than_throwing(): void
    {
        $this->table([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'does.not.exist', 'label' => 'Nope'],
        ])->assertOk();
    }

    #[Test]
    public function a_null_json_column_renders_an_empty_cell(): void
    {
        TestEmployee::query()->toBase()->update(['preferences' => null]);

        $this->table([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
        ])->assertOk();
    }

    #[Test]
    public function boolean_json_values_render_as_yes_or_no(): void
    {
        TestEmployee::query()->whereKey(1)->toBase()
            ->update(['preferences' => json_encode(['beta' => true])]);

        $this->table([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'beta', 'label' => 'Beta'],
        ])->assertSee('Yes');
    }

    #[Test]
    public function array_json_values_render_as_json(): void
    {
        TestEmployee::query()->whereKey(1)->toBase()
            ->update(['preferences' => json_encode(['tags' => ['a', 'b']])]);

        $this->table([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'tags', 'label' => 'Tags'],
            // The cell prints json_encode($value) through {{ }}, so the quotes
            // arrive HTML-escaped — assertSee() escapes the needle to match.
        ])->assertSee('["a","b"]');
    }

    #[Test]
    public function json_columns_are_not_sortable(): void
    {
        $this->table([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
        ])
            ->call('toggleSort', 'preferences')
            ->assertSet('sortColumn', 'id');
    }

    #[Test]
    public function the_header_marks_json_columns_as_not_sortable(): void
    {
        $this->table([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
        ])->assertSee('(Not Sortable)');
    }

    #[Test]
    public function a_json_column_renders_alongside_relation_and_raw_columns(): void
    {
        $this->table([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
            ['key' => 'department_name', 'relation' => 'department:name', 'label' => 'Department'],
            ['key' => 'salary', 'label' => 'Salary', 'raw' => '<b>{{ $row->salary }}</b>'],
        ])
            ->assertOk()
            ->assertSee('dark')
            ->assertSee('Engineering')
            ->assertSeeHtml('<b>90000.00</b>');
    }

    #[Test]
    public function json_extraction_works_on_a_different_model(): void
    {
        $this->assertSame('gold', TestCompany::first()->metadata['tier']);

        Livewire::test('aftable', [
            'model' => TestCompany::class,
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'metadata', 'json' => 'tier', 'label' => 'Tier'],
            ],
        ])->assertSee('gold');
    }
}
