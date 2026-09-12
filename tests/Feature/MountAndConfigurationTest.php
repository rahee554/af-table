<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers mount(): column normalisation across the three supported config
 * formats, table identity, sort auto-detection and visibility bootstrapping.
 */
class MountAndConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    #[Test]
    public function it_normalises_direct_array_columns_keyed_by_their_key(): void
    {
        $columns = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ])->get('columns');

        $this->assertSame(
            ['id', 'first_name', 'last_name', 'salary', 'position'],
            array_keys($columns)
        );
        $this->assertSame('First Name', $columns['first_name']['label']);
        $this->assertSame('first_name', $columns['first_name']['key']);
    }

    #[Test]
    public function it_normalises_legacy_named_array_columns(): void
    {
        $columns = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::namedArray(),
        ])->get('columns');

        $this->assertSame(['id', 'first_name', 'salary'], array_keys($columns));
        // Missing labels are humanised from the key.
        $this->assertSame('Salary', $columns['salary']['label']);
        $this->assertSame('salary', $columns['salary']['key']);
    }

    #[Test]
    public function it_normalises_plain_string_columns(): void
    {
        $columns = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::strings(),
        ])->get('columns');

        $this->assertSame(['id', 'first_name', 'salary'], array_keys($columns));
        $this->assertSame('First name', $columns['first_name']['label']);
    }

    #[Test]
    public function it_derives_the_table_id_from_the_model_when_none_is_given(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ]);

        $component->assertSet('tableId', ColumnSets::MODEL);
    }

    #[Test]
    public function it_honours_an_explicit_table_id(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'tableId' => 'employees-table',
        ])->assertSet('tableId', 'employees-table');
    }

    #[Test]
    public function it_auto_selects_the_first_sortable_column_when_no_sort_is_given(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ])->assertSet('sortColumn', 'id');
    }

    #[Test]
    public function an_explicit_sort_by_wins_over_auto_detection(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'sortBy' => 'salary',
            'sortDirection' => 'desc',
        ])
            ->assertSet('sortColumn', 'salary')
            ->assertSet('sortBy', 'salary')
            ->assertSet('sortDirection', 'desc');
    }

    #[Test]
    public function an_invalid_sort_direction_falls_back_to_ascending(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'sortBy' => 'salary',
            'sortDirection' => 'sideways',
        ])->assertSet('sortDirection', 'asc');
    }

    #[Test]
    public function the_legacy_sort_parameter_still_sets_the_direction(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'sort' => 'desc',
        ])->assertSet('sortDirection', 'desc');
    }

    #[Test]
    public function it_marks_every_column_visible_unless_explicitly_hidden(): void
    {
        $visible = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withHidden(),
        ])->get('visibleColumns');

        $this->assertTrue($visible['id']);
        $this->assertTrue($visible['first_name']);
        $this->assertFalse($visible['salary'], 'A column with hide=true must start hidden.');
    }

    #[Test]
    public function it_collects_every_nested_relation_path_for_eager_loading(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withRelations(),
        ]);

        $relations = (fn () => $this->cachedRelations)->call($component->instance());

        $this->assertContains('department', $relations);
        $this->assertContains('department.company', $relations);
    }

    #[Test]
    public function it_renders_without_error_for_every_supported_column_shape(): void
    {
        foreach (['basic', 'namedArray', 'strings', 'withRelations', 'withComputed', 'withHidden'] as $set) {
            Livewire::test('aftable', [
                'model' => ColumnSets::MODEL,
                'columns' => ColumnSets::{$set}(),
            ])->assertOk();
        }
    }
}
