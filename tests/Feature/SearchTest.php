<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers the global search box: the 3-character floor, which columns take part,
 * relation search, and the input sanitiser.
 */
class SearchTest extends TestCase
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

    private function names($component): array
    {
        return collect($component->viewData('data')->items())
            ->pluck('first_name')
            ->sort()
            ->values()
            ->all();
    }

    #[Test]
    public function without_a_search_term_every_row_is_returned(): void
    {
        $this->assertCount(5, $this->table()->viewData('data')->items());
    }

    #[Test]
    public function a_search_term_shorter_than_three_characters_is_ignored(): void
    {
        $component = $this->table()->set('search', 'Al');

        $this->assertCount(5, $component->viewData('data')->items());
    }

    #[Test]
    public function a_three_character_term_filters_on_searchable_columns(): void
    {
        $component = $this->table()->set('search', 'Ali');

        $this->assertSame(['Alice'], $this->names($component));
    }

    #[Test]
    public function search_matches_anywhere_inside_the_value(): void
    {
        $component = $this->table()->set('search', 'nderso');

        $this->assertSame(['Alice'], $this->names($component));
    }

    #[Test]
    public function search_spans_every_searchable_column(): void
    {
        // "Designer" only exists in the `position` column.
        $component = $this->table()->set('search', 'Designer');

        $this->assertSame(['Bob'], $this->names($component));
    }

    #[Test]
    public function a_term_matching_nothing_returns_an_empty_page(): void
    {
        $component = $this->table()->set('search', 'zzzznotfound');

        $this->assertCount(0, $component->viewData('data')->items());
    }

    #[Test]
    public function columns_marked_not_searchable_are_excluded(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'first_name', 'label' => 'First Name'],
                ['key' => 'position', 'label' => 'Position', 'searchable' => false],
            ],
        ])->set('search', 'Designer');

        $this->assertCount(0, $component->viewData('data')->items());
    }

    #[Test]
    public function clearing_the_search_restores_the_full_result_set(): void
    {
        $component = $this->table()->set('search', 'Ali');
        $this->assertCount(1, $component->viewData('data')->items());

        $component->set('search', '');
        $this->assertCount(5, $component->viewData('data')->items());
    }

    #[Test]
    public function setting_the_search_to_an_empty_string_via_the_ui_clears_it(): void
    {
        // The rendered template clears via `$set('search', '')` rather than the
        // clearSearch() action, which is broken — see AUDIT.md BUG-05.
        $this->table()
            ->set('search', 'Alice')
            ->set('search', '')
            ->assertSet('search', '');
    }

    #[Test]
    public function the_sanitiser_strips_markup_and_caps_the_length_but_keeps_the_term_intact(): void
    {
        $component = $this->table()->instance();
        $sanitize = fn (string $v) => (fn () => $this->sanitizeSearch($v))->call($component);

        // Markup is dropped — a search term is never HTML.
        $this->assertSame('alert(1)', $sanitize('<script>alert(1)</script>'));

        // Everything else survives. Laravel binds LIKE values as parameters, so
        // stripping punctuation bought no safety and broke real searches.
        $this->assertSame("O'Brien", $sanitize("O'Brien"));
        $this->assertSame('50% off', $sanitize('50% off'));
        $this->assertSame('C++', $sanitize('C++'));
        $this->assertSame('user@example.com', $sanitize('user@example.com'));

        // Non-Latin scripts survive intact — see AUDIT.md BUG-18.
        $this->assertSame('مهندس', $sanitize('مهندس'));
        $this->assertSame('中文', $sanitize('中文'));

        // Length is capped so a huge term cannot be used to hammer LIKE scans.
        $this->assertSame(255, mb_strlen($sanitize(str_repeat('a', 500))));
    }

    #[Test]
    public function a_non_latin_search_term_actually_filters(): void
    {
        DB::table('test_employees')
            ->where('first_name', 'Alice')
            ->update(['position' => 'مهندس']);

        $component = $this->table()->set('search', 'مهندس');

        $this->assertSame(['Alice'], $this->names($component));
    }

    #[Test]
    public function searching_never_throws_for_any_supported_column_shape(): void
    {
        foreach (['basic', 'withRelations', 'withComputed', 'withHidden'] as $set) {
            Livewire::test('aftable', [
                'model' => ColumnSets::MODEL,
                'columns' => ColumnSets::{$set}(),
            ])->set('search', 'Ali')->assertOk();
        }
    }
}
