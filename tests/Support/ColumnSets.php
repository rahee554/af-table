<?php

namespace ArtflowStudio\Table\Tests\Support;

use ArtflowStudio\Table\Tests\Models\TestEmployee;

/**
 * Reusable column / filter / action configurations so individual tests stay
 * focused on behaviour rather than on restating a 30-line config array.
 */
final class ColumnSets
{
    public const MODEL = TestEmployee::class;

    /** Plain database columns, direct-array format. */
    public static function basic(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'first_name', 'label' => 'First Name'],
            ['key' => 'last_name', 'label' => 'Last Name'],
            ['key' => 'salary', 'label' => 'Salary'],
            ['key' => 'position', 'label' => 'Position'],
        ];
    }

    /** Legacy named-array format: 'key' => [...config]. */
    public static function namedArray(): array
    {
        return [
            'id' => ['label' => 'ID'],
            'first_name' => ['label' => 'First Name'],
            'salary' => [],
        ];
    }

    /** Shorthand string format: ['id', 'first_name']. */
    public static function strings(): array
    {
        return ['id', 'first_name', 'salary'];
    }

    /** Includes a single-level relation column and a nested one. */
    public static function withRelations(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'first_name', 'label' => 'First Name'],
            ['key' => 'department_name', 'relation' => 'department:name', 'label' => 'Department'],
            ['key' => 'company_name', 'relation' => 'department.company:name', 'label' => 'Company'],
        ];
    }

    /**
     * Relation columns written WITHOUT an explicit `key`, which the docs never
     * show but which reads naturally. Included to pin the (broken) behaviour —
     * see AUDIT.md BUG-06.
     */
    public static function relationsWithoutKey(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID'],
            ['relation' => 'department:name', 'label' => 'Department'],
        ];
    }

    /** Function column + raw Blade column (no JSON — see withJson()). */
    public static function withComputed(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'full_name', 'function' => 'getFullNameAttribute', 'label' => 'Full Name'],
            ['key' => 'salary', 'label' => 'Salary', 'raw' => '{{ number_format($row->salary, 0) }}'],
        ];
    }

    /**
     * JSON-path column. Kept separate because rendering it currently fatals —
     * see AUDIT.md BUG-02 and tests/Regression/JsonColumnRegressionTest.php.
     */
    public static function withJson(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
            ['key' => 'metadata', 'json' => 'tier', 'label' => 'Tier'],
        ];
    }

    /** A hidden-by-default column, for visibility tests. */
    public static function withHidden(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'first_name', 'label' => 'First Name'],
            ['key' => 'salary', 'label' => 'Salary', 'hide' => true],
        ];
    }

    /** Filter definitions matching self::basic() + status. */
    public static function filters(): array
    {
        return [
            'position' => ['type' => 'distinct'],
            'salary' => ['type' => 'number'],
            'first_name' => ['type' => 'text'],
        ];
    }

    /** Static (foreach-mode) rows. */
    public static function staticRows(): array
    {
        return [
            ['id' => 1, 'name' => 'Widget', 'price' => 10.0, 'qty' => 3, 'category' => 'Tools'],
            ['id' => 2, 'name' => 'Gadget', 'price' => 25.5, 'qty' => 1, 'category' => 'Tools'],
            ['id' => 3, 'name' => 'Doohickey', 'price' => 5.25, 'qty' => 10, 'category' => 'Parts'],
            ['id' => 4, 'name' => 'Thingamajig', 'price' => 99.99, 'qty' => 2, 'category' => 'Parts'],
            ['id' => 5, 'name' => 'Whatsit', 'price' => 1.0, 'qty' => 100, 'category' => 'Misc'],
        ];
    }

    public static function staticColumns(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'price', 'label' => 'Price'],
            ['key' => 'qty', 'label' => 'Qty'],
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'total', 'label' => 'Total', 'sortable' => false,
                'raw' => '{{ number_format($row->price * $row->qty, 2) }}'],
        ];
    }
}
