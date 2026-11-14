<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * The filter bar: search box, one labelled dropdown per quick filter, and a
 * clear button, in one row.
 */
class QuickFiltersTest extends TestCase
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
            'quickFilters' => [
                'position' => ['label' => 'Position', 'options' => ['Engineer' => 'Engineer', 'Designer' => 'Designer']],
                'status' => ['label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                'seniority' => [
                    'label' => 'Seniority',
                    'options' => ['junior' => 'Junior', 'senior' => 'Senior'],
                    'exists' => ['junior' => '!projects', 'senior' => 'projects'],
                ],
            ],
            'searchLabel' => 'Search staff',
        ], $overrides));
    }

    private function names($component): array
    {
        return collect($component->viewData('data')->items())->pluck('first_name')->sort()->values()->all();
    }

    #[Test]
    public function the_bar_renders_the_search_box_a_labelled_dropdown_per_filter_and_a_clear_button(): void
    {
        $this->table()
            ->assertSee('Search staff')
            ->assertSee('Position')
            ->assertSee('Seniority')
            ->assertSeeHtml('wire:model.live="quick.position"')
            ->assertSeeHtml('wire:click="clearQuickFilters"');
    }

    #[Test]
    public function the_toolbar_does_not_draw_a_second_search_box(): void
    {
        $this->assertSame(1, substr_count($this->table()->html(), 'wire:model.live.debounce.300ms="search"'));
        $this->assertSame(0, substr_count($this->table()->html(), 'wire:model.live.debounce.500ms="search"'));
    }

    #[Test]
    public function a_dropdown_narrows_the_rows(): void
    {
        $this->assertSame(['Alice', 'Chloé'], $this->names($this->table()->set('quick.position', 'Engineer')));
    }

    #[Test]
    public function dropdowns_combine_and_search_still_applies(): void
    {
        $component = $this->table()->set('quick.position', 'Engineer')->set('quick.status', 'active');

        $this->assertSame(['Alice'], $this->names($component));
        $this->assertSame(['Alice'], $this->names($component->set('search', 'Ali')));
    }

    #[Test]
    public function an_exists_option_filters_on_a_relation(): void
    {
        // Nobody has projects in the micro dataset, so "senior" is empty and "junior" is everyone.
        $this->assertSame([], $this->names($this->table()->set('quick.seniority', 'senior')));
        $this->assertCount(5, $this->table()->set('quick.seniority', 'junior')->viewData('data')->items());
    }

    #[Test]
    public function a_value_that_is_not_a_declared_option_is_ignored(): void
    {
        $this->assertCount(5, $this->table()->set('quick.position', 'Engineer\' OR 1=1 --')->viewData('data')->items());
        $this->assertCount(5, $this->table()->set('quick.nonsense', 'x')->viewData('data')->items());
    }

    #[Test]
    public function clearing_resets_the_dropdowns_and_the_search(): void
    {
        $component = $this->table()->set('quick.position', 'Designer')->set('search', 'Bob')->call('clearQuickFilters');

        $component->assertSet('quick', [])->assertSet('search', '');
        $this->assertCount(5, $component->viewData('data')->items());
    }

    #[Test]
    public function the_filter_definitions_cannot_be_rewritten_from_the_browser(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->table()->set('quickFilters', ['position' => ['label' => 'x', 'options' => [], 'column' => 'salary', 'placeholder' => '', 'exists' => []]]);
    }

    #[Test]
    public function a_table_without_quick_filters_keeps_its_own_toolbar_search(): void
    {
        $html = Livewire::test('aftable', ['model' => ColumnSets::MODEL, 'columns' => ColumnSets::basic()])->html();

        $this->assertStringContainsString('wire:model.live.debounce.500ms="search"', $html);
        $this->assertStringNotContainsString('clearQuickFilters', $html);
    }
}
