<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

class ColumnVisibilityTest extends TestCase
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
            'tableId' => 'visibility-test',
        ], $overrides));
    }

    #[Test]
    public function toggling_a_column_flips_its_visibility(): void
    {
        $component = $this->table();
        $this->assertTrue($component->get('visibleColumns')['salary']);

        $component->call('toggleColumnVisibility', 'salary');
        $this->assertFalse($component->get('visibleColumns')['salary']);

        $component->call('toggleColumnVisibility', 'salary');
        $this->assertTrue($component->get('visibleColumns')['salary']);
    }

    #[Test]
    public function the_toggle_column_alias_delegates_to_the_same_logic(): void
    {
        $component = $this->table()->call('toggleColumn', 'salary');

        $this->assertFalse($component->get('visibleColumns')['salary']);
    }

    #[Test]
    public function a_hidden_column_is_absent_from_the_rendered_table(): void
    {
        // The sortable <th> is the only place this attribute appears; the
        // column-visibility dropdown entry uses toggleColumnVisibility instead.
        $this->table()
            ->assertSeeHtml("toggleSort('salary')")
            ->call('toggleColumnVisibility', 'salary')
            ->assertDontSeeHtml("toggleSort('salary')");
    }

    #[Test]
    public function visibility_is_written_to_the_session(): void
    {
        $component = $this->table()->call('toggleColumnVisibility', 'salary');

        $key = (fn () => $this->getColumnVisibilitySessionKey())->call($component->instance());

        $this->assertFalse(Session::get($key)['salary']);
    }

    #[Test]
    public function visibility_is_restored_from_the_session_on_the_next_mount(): void
    {
        $first = $this->table()->call('toggleColumnVisibility', 'salary');
        $this->assertFalse($first->get('visibleColumns')['salary']);

        $second = $this->table();
        $this->assertFalse(
            $second->get('visibleColumns')['salary'],
            'Column visibility should survive a re-mount via the session.'
        );
    }

    #[Test]
    public function the_session_key_is_scoped_per_table_id(): void
    {
        $this->table(['tableId' => 'table-a'])->call('toggleColumnVisibility', 'salary');

        $other = $this->table(['tableId' => 'table-b']);

        $this->assertTrue(
            $other->get('visibleColumns')['salary'],
            'Hiding a column on one table must not affect another table.'
        );
    }

    #[Test]
    public function show_all_columns_makes_every_column_visible(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withHidden(),
            'tableId' => 'show-all',
        ])->call('showAllColumns');

        $this->assertSame([true, true, true], array_values($component->get('visibleColumns')));
    }

    #[Test]
    public function hide_all_columns_keeps_required_columns_visible(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'required' => true],
                ['key' => 'first_name', 'label' => 'First Name'],
            ],
            'tableId' => 'hide-all',
        ])->call('hideAllColumns');

        $visible = $component->get('visibleColumns');
        $this->assertTrue($visible['id']);
        $this->assertFalse($visible['first_name']);
    }

    #[Test]
    public function reset_column_visibility_restores_the_configured_defaults(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withHidden(),
            'tableId' => 'reset-vis',
        ])
            ->call('showAllColumns')
            ->call('resetColumnVisibility');

        $this->assertFalse($component->get('visibleColumns')['salary']);
    }

    #[Test]
    public function visible_and_hidden_counts_are_reported(): void
    {
        $instance = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withHidden(),
            'tableId' => 'counts',
        ])->instance();

        $this->assertSame(2, $instance->getVisibleColumnsCount());
        $this->assertSame(1, $instance->getHiddenColumnsCount());
    }

    #[Test]
    public function clear_column_visibility_session_forgets_the_stored_preference(): void
    {
        $component = $this->table(['tableId' => 'clear-vis'])
            ->call('toggleColumnVisibility', 'salary')
            ->call('clearColumnVisibilitySession');

        $this->assertTrue($component->get('visibleColumns')['salary']);
    }
}
