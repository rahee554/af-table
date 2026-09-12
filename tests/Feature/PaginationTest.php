<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\Models\TestCompany;
use ArtflowStudio\Table\Tests\Models\TestEmployee;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

class PaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
        $this->seedMoreEmployees(25);
    }

    private function seedMoreEmployees(int $count): void
    {
        $companyId = TestCompany::query()->value('id');

        for ($i = 0; $i < $count; $i++) {
            TestEmployee::create([
                'company_id' => $companyId,
                'first_name' => 'Bulk'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'last_name' => 'Filler',
                'email' => "bulk{$i}@artflow.test",
                'salary' => 50000 + $i,
                'position' => 'Engineer',
            ]);
        }
    }

    private function table(array $overrides = [])
    {
        return Livewire::test('aftable', array_merge([
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ], $overrides));
    }

    #[Test]
    public function it_paginates_ten_rows_per_page_by_default(): void
    {
        $data = $this->table()->viewData('data');

        $this->assertSame(10, $data->perPage());
        $this->assertCount(10, $data->items());
        $this->assertSame(30, $data->total());
        $this->assertSame(3, $data->lastPage());
    }

    #[Test]
    public function the_records_dropdown_changes_the_page_size(): void
    {
        $data = $this->table()->set('records', 50)->viewData('data');

        $this->assertSame(50, $data->perPage());
        $this->assertCount(30, $data->items());
    }

    #[Test]
    public function the_records_and_per_page_properties_stay_in_sync(): void
    {
        $this->table()
            ->set('records', 100)
            ->assertSet('records', 100)
            ->assertSet('perPage', 100);
    }

    #[Test]
    public function navigating_to_the_next_page_returns_different_rows(): void
    {
        $component = $this->table(['sortBy' => 'id', 'sortDirection' => 'asc']);

        $firstPage = collect($component->viewData('data')->items())->pluck('id')->all();

        $component->call('nextPage');
        $secondPage = collect($component->viewData('data')->items())->pluck('id')->all();

        $this->assertCount(10, $secondPage);
        $this->assertEmpty(array_intersect($firstPage, $secondPage));
    }

    // NOTE: "searching resets to page 1" is NOT covered here — it is broken.
    // See AUDIT.md BUG-04 and tests/Regression/UpdatedHookRegressionTest.php.

    #[Test]
    public function sorting_resets_back_to_the_first_page(): void
    {
        $component = $this->table()->call('gotoPage', 3);
        $component->call('toggleSort', 'salary');

        $this->assertSame(1, $component->viewData('data')->currentPage());
    }

    #[Test]
    public function filtering_resets_back_to_the_first_page(): void
    {
        $component = $this->table(['filters' => ColumnSets::filters()])->call('gotoPage', 3);

        $component->set('filterColumn', 'position')->set('filterValue', 'Engineer');

        $this->assertSame(1, $component->viewData('data')->currentPage());
    }

    #[Test]
    public function the_pagination_view_renders(): void
    {
        $this->table()->assertOk()->assertSeeHtml('pagination');
    }
}
