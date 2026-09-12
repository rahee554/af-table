<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * How `initializeColumns()` derives the array key for each column.
 *
 * Two defects lived here, both silent:
 *
 *  - BUG-06: a direct-array column with no `key` got the numeric array index,
 *    so it became `columns[1]` — breaking sorting, the visibility checkbox id
 *    and session persistence, and renumbering whenever columns were reordered.
 *  - BUG-34: columns were keyed purely on `key`, so two columns reading the
 *    same underlying database column (two JSON paths out of one document, two
 *    attributes off one relation) silently overwrote each other and the table
 *    quietly lost a column.
 */
class ColumnKeyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    private function columnsFor(array $columns): array
    {
        return Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => $columns,
        ])->get('columns');
    }

    #[Test]
    public function two_json_paths_on_the_same_column_both_survive(): void
    {
        $columns = $this->columnsFor([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
            ['key' => 'preferences', 'json' => 'lang', 'label' => 'Locale'],
        ]);

        $this->assertSame(['id', 'preferences', 'preferences_lang'], array_keys($columns));
        $this->assertSame('Theme', $columns['preferences']['label']);
        $this->assertSame('Locale', $columns['preferences_lang']['label']);

        // Both must still point at the same physical column.
        $this->assertSame('preferences', $columns['preferences']['key']);
        $this->assertSame('preferences', $columns['preferences_lang']['key']);
    }

    #[Test]
    public function both_json_columns_actually_render(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
                ['key' => 'preferences', 'json' => 'lang', 'label' => 'Locale'],
            ],
        ])
            ->assertSee('Theme')
            ->assertSee('Locale')
            ->assertSee('dark')   // preferences.theme
            ->assertSee('en');    // preferences.lang
    }

    #[Test]
    public function two_attributes_off_the_same_relation_both_survive(): void
    {
        $columns = $this->columnsFor([
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'dept', 'relation' => 'department:name', 'label' => 'Department'],
            ['key' => 'dept', 'relation' => 'department:code', 'label' => 'Code'],
        ]);

        $this->assertSame(['id', 'dept', 'dept_code'], array_keys($columns));
    }

    #[Test]
    public function an_exact_duplicate_falls_back_to_a_numeric_suffix(): void
    {
        $columns = $this->columnsFor([
            ['key' => 'salary', 'label' => 'Salary'],
            ['key' => 'salary', 'label' => 'Salary again'],
            ['key' => 'salary', 'label' => 'And again'],
        ]);

        $this->assertSame(['salary', 'salary_2', 'salary_3'], array_keys($columns));
    }

    #[Test]
    public function a_relation_column_without_a_key_is_named_after_its_relation(): void
    {
        $columns = $this->columnsFor([
            ['key' => 'id', 'label' => 'ID'],
            ['relation' => 'department:name', 'label' => 'Department'],
            ['relation' => 'department.company:name', 'label' => 'Company'],
        ]);

        $this->assertSame(
            ['id', 'department_name', 'department_company_name'],
            array_keys($columns),
            'Keys must describe the column, not its position in the array.'
        );
    }

    #[Test]
    public function a_json_column_without_a_key_is_named_after_its_path(): void
    {
        $columns = $this->columnsFor([
            ['key' => 'preferences', 'label' => 'Prefs'],
            ['json' => 'theme', 'label' => 'Theme'],
        ]);

        $this->assertSame(['preferences', 'theme'], array_keys($columns));
    }

    #[Test]
    public function reordering_columns_does_not_change_their_keys(): void
    {
        $a = $this->columnsFor([
            ['key' => 'id', 'label' => 'ID'],
            ['relation' => 'department:name', 'label' => 'Department'],
        ]);

        $b = $this->columnsFor([
            ['relation' => 'department:name', 'label' => 'Department'],
            ['key' => 'id', 'label' => 'ID'],
        ]);

        $a2 = array_keys($a);
        $b2 = array_keys($b);
        sort($a2);
        sort($b2);

        $this->assertSame($a2, $b2, 'A column key must not depend on array position.');
    }

    #[Test]
    public function the_three_documented_config_shapes_still_key_as_before(): void
    {
        $this->assertSame(
            ['id', 'first_name', 'last_name', 'salary', 'position'],
            array_keys($this->columnsFor(ColumnSets::basic())),
            'direct array with keys'
        );

        $this->assertSame(
            ['id', 'first_name', 'salary'],
            array_keys($this->columnsFor(ColumnSets::namedArray())),
            'legacy named array'
        );

        $this->assertSame(
            ['id', 'first_name', 'salary'],
            array_keys($this->columnsFor(ColumnSets::strings())),
            'plain strings'
        );
    }
}
