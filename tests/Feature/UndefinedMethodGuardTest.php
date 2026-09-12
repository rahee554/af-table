<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Http\Livewire\AftableComponent;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * BUG-09 — FIXED. Kept in the default suite as a permanent guard.
 *
 * AftableComponent delegates to eight helper methods that are defined in no
 * trait and in no parent class. Every one of them is reachable from the browser
 * (all are public Livewire actions or fire from an `updated*` hook), so each is
 * a guaranteed 500 in production.
 *
 * The last test here is the important one: it statically resolves every
 * `$this->method()` call site in the component and its traits against the
 * methods that actually exist. Keep it green and this entire bug class — eight
 * user-reachable actions that threw BadMethodCallException — cannot come back.
 */
class UndefinedMethodGuardTest extends TestCase
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

    #[Test]
    #[TestDox('BUG-09 (fixed): sortBy() action fatals — saveSortingToSession() is undefined')]
    public function sort_by_action_does_not_fatal(): void
    {
        $this->table()->call('sortBy', 'salary')->assertSet('sortColumn', 'salary');
    }

    #[Test]
    #[TestDox('BUG-09 (fixed): updating a filters.* model fatals — saveFiltersToSession() is undefined')]
    public function updating_a_filter_model_does_not_fatal(): void
    {
        $this->table(['filters' => ColumnSets::filters()])
            ->set('filters.position', 'Engineer')
            ->assertOk();
    }

    #[Test]
    #[TestDox('BUG-09 (fixed): date-range filtering fatals — applyDateRangeFilter() is undefined')]
    public function date_range_filtering_does_not_fatal(): void
    {
        $component = $this->table()
            ->set('dateColumn', 'hire_date')
            ->set('startDate', '2020-01-01')
            ->set('endDate', '2020-03-01');

        $this->assertLessThan(5, count($component->viewData('data')->items()));
    }

    #[Test]
    #[TestDox('BUG-09 (fixed): clearAllFilters() fatals — clearFilters() is undefined')]
    public function clear_all_filters_does_not_fatal(): void
    {
        $this->table(['filters' => ColumnSets::filters()])
            ->set('filterColumn', 'position')
            ->set('filterValue', 'Engineer')
            ->call('clearAllFilters')
            ->assertSet('filterColumn', null);
    }

    #[Test]
    #[TestDox('BUG-09 (fixed): clearSearch() fatals — clearSearchInput() is undefined')]
    public function clear_search_does_not_fatal(): void
    {
        $this->table()->set('search', 'Alice')->call('clearSearch')->assertSet('search', '');
    }

    #[Test]
    #[TestDox('BUG-09 (fixed): getDebugInfo() fatals — getComponentDebugInfo() is undefined')]
    public function get_debug_info_does_not_fatal(): void
    {
        $this->assertIsArray($this->table()->instance()->getDebugInfo());
    }

    #[Test]
    #[TestDox('BUG-09 (fixed): getQueryString() fatals — getQueryStringConfiguration() is undefined')]
    public function get_query_string_does_not_fatal(): void
    {
        $this->assertIsArray($this->table()->instance()->getQueryString());
    }

    #[Test]
    #[TestDox('BUG-09 (fixed): updatedPerPage() fatals — $this->page is not a Livewire property')]
    public function updating_per_page_does_not_fatal(): void
    {
        $this->table()->set('perPage', 25)->assertSet('perPage', 25);
    }

    #[Test]
    #[TestDox('BUG-09 (fixed): no method is called on the component that is not defined on it')]
    public function every_this_call_resolves_to_a_real_method(): void
    {
        $reflection = new \ReflectionClass(AftableComponent::class);

        $defined = [];
        foreach ($reflection->getMethods() as $method) {
            $defined[strtolower($method->getName())] = true;
        }

        $files = [$reflection->getFileName()];
        foreach ($reflection->getTraits() as $trait) {
            $files[] = $trait->getFileName();
            foreach ($trait->getTraits() as $nested) {
                $files[] = $nested->getFileName();
            }
        }

        $missing = [];
        foreach (array_unique(array_filter($files)) as $file) {
            preg_match_all('/\$this->([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', file_get_contents($file), $matches);
            foreach ($matches[1] as $name) {
                if (! isset($defined[strtolower($name)])) {
                    $missing[$name] = basename($file);
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Methods called on \$this but never defined:\n".
            implode("\n", array_map(fn ($f, $m) => "  - {$m}() (called from {$f})", $missing, array_keys($missing)))
        );
    }
}
