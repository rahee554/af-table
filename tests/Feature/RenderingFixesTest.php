<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

class RenderingFixesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    #[Test]
    public function a_relation_columns_date_uses_the_columns_format(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],
                ['key' => 'company_founded', 'label' => 'Founded', 'relation' => 'company:founded_date', 'format' => 'd M Y'],
            ],
        ])->assertSee('01 Apr 2015')->assertDontSee('2015-04-01 00:00:00');
    }

    #[Test]
    public function a_column_declared_unsortable_gets_no_sort_control_and_no_tag(): void
    {
        $html = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],
                ['key' => 'position', 'label' => 'Position', 'sortable' => false],
            ],
        ])->html();

        $this->assertStringContainsString("toggleSort('first_name')", $html);
        $this->assertStringNotContainsString("toggleSort('position')", $html);
        $this->assertStringNotContainsString('(Not Sortable)', $html);
    }

    #[Test]
    public function a_nested_relation_still_says_why_it_cannot_be_sorted(): void
    {
        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],
                ['key' => 'company_name', 'label' => 'Company', 'relation' => 'department.company:name'],
            ],
        ])->assertSee('(Not Sortable)');
    }

    #[Test]
    public function the_per_page_parameter_sets_the_page_size(): void
    {
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'perPage' => 2,
        ]);

        $this->assertCount(2, $component->viewData('data')->items());
    }
}
