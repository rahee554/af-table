<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Models\TestCompany;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\View\ViewException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Livewire lets the browser overwrite any public property that is not locked.
 * The properties that decide WHAT a table reads must not be among them: a
 * signed-in visitor could otherwise point a table at another model, drop the
 * `query` scope the host applied, or add columns the host never exposed.
 */
class ConfigurationLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    private function table()
    {
        return Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'filters' => ColumnSets::filters(),
            'query' => ['position' => 'Engineer'],
        ]);
    }

    public static function lockedProperties(): array
    {
        return [
            'model' => ['model', TestCompany::class],
            'query' => ['query', []],
            'columns' => ['columns', ['secret' => ['label' => 'Secret']]],
            'filters' => ['filters', ['salary' => ['type' => 'number']]],
            'actions' => ['actions', []],
        ];
    }

    #[Test]
    #[DataProvider('lockedProperties')]
    public function the_browser_cannot_rewrite_what_the_table_reads(string $property, mixed $value): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->table()->set($property, $value);
    }

    #[Test]
    public function the_scope_the_host_applied_still_holds(): void
    {
        $names = collect($this->table()->viewData('data')->items())->pluck('first_name')->sort()->values()->all();

        $this->assertSame(['Alice', 'Chloé'], $names);
    }

    #[Test]
    public function a_named_model_scope_can_be_used_as_the_base_query(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'query' => ['scope:engineers' => []],
        ]);

        $this->assertSame(['Alice', 'Chloé'], collect($component->viewData('data')->items())->pluck('first_name')->sort()->values()->all());
    }

    #[Test]
    public function a_scope_that_the_model_does_not_define_is_refused(): void
    {
        // Blade wraps whatever a view throws; the message is the part that matters.
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('[delete] is not a scope');

        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'query' => ['scope:delete' => []],
        ])->viewData('data');
    }
}
