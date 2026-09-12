<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Support\SchemaCache;
use ArtflowStudio\Table\Tests\Models\TestCompany;
use ArtflowStudio\Table\Tests\Models\TestDepartment;
use ArtflowStudio\Table\Tests\Models\TestEmployee;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * N+1 guards.
 *
 * The load-bearing assertion in this file is not "few queries" — it is
 * **"the same number of queries regardless of how many rows are on the page"**.
 * A count budget silently rots as the package grows; a growth assertion cannot.
 *
 * Laravel's own `Model::preventLazyLoading()` is also switched on for the
 * relation tests, so an accidental lazy load throws instead of quietly costing
 * a query.
 */
class NPlusOneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);
        parent::tearDown();
    }

    /** Add N more employees spread across the existing departments. */
    private function addEmployees(int $count): void
    {
        $companyId = TestCompany::query()->value('id');
        $departmentIds = TestDepartment::query()->pluck('id')->all();

        for ($i = 0; $i < $count; $i++) {
            TestEmployee::create([
                'company_id' => $companyId,
                'department_id' => $departmentIds[$i % count($departmentIds)],
                'first_name' => 'Bulk'.$i,
                'last_name' => 'Filler',
                'email' => "nplus{$i}@artflow.test",
                'salary' => 50000 + $i,
                'position' => $i % 2 ? 'Engineer' : 'Designer',
                'preferences' => ['theme' => 'dark'],
            ]);
        }
    }

    /** Count the SELECTs issued while rendering. */
    private function queriesToRender(array $params): int
    {
        // Warm the schema cache first. Its 2 cold-start lookups are a constant,
        // not a per-row cost, and counting them would mask the thing this
        // method exists to measure.
        SchemaCache::columnsFor($params['model'] ?? ColumnSets::MODEL);

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test('aftable', $params)->html();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    /**
     * Render the same table at two page sizes and require an identical query
     * count. This is the assertion that actually catches an N+1.
     */
    private function assertQueryCountDoesNotGrowWithRows(array $params, string $because): void
    {
        $this->addEmployees(40);

        $small = $this->queriesToRender(array_merge($params, ['records' => 5, 'tableId' => 'nplus-small']));
        $large = $this->queriesToRender(array_merge($params, ['records' => 40, 'tableId' => 'nplus-large']));

        $this->assertSame(
            $small,
            $large,
            $because."\n".
            "  5 rows  -> {$small} queries\n".
            "  40 rows -> {$large} queries\n".
            'A per-row query is being issued.'
        );
    }

    #[Test]
    public function a_single_level_relation_column_does_not_scale_with_rows(): void
    {
        $this->assertQueryCountDoesNotGrowWithRows([
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],
                ['key' => 'department_name', 'relation' => 'department:name', 'label' => 'Department'],
            ],
        ], 'A single-level relation column must be eager loaded.');
    }

    #[Test]
    public function a_nested_relation_column_does_not_scale_with_rows(): void
    {
        $this->assertQueryCountDoesNotGrowWithRows([
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],
                ['key' => 'company_name', 'relation' => 'department.company:name', 'label' => 'Company'],
            ],
        ], 'A nested relation column must eager load every intermediate level.');
    }

    #[Test]
    public function several_relation_columns_do_not_scale_with_rows(): void
    {
        $this->assertQueryCountDoesNotGrowWithRows([
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withRelations(),
        ], 'Multiple relation columns must still be one eager load per relation.');
    }

    #[Test]
    public function count_aggregations_do_not_scale_with_rows(): void
    {
        $this->addEmployees(40);

        $params = [
            'model' => TestDepartment::class,
            'columns' => [
                ['key' => 'name', 'label' => 'Department'],
                ['key' => 'employees_count', 'label' => 'Employees', 'sortable' => false],
            ],
            'countAggregations' => ['employees' => null],
        ];

        $small = $this->queriesToRender($params + ['records' => 1, 'tableId' => 'agg-small']);
        $large = $this->queriesToRender($params + ['records' => 50, 'tableId' => 'agg-large']);

        $this->assertSame($small, $large, 'withCount() must be a subselect, not a query per row.');
    }

    #[Test]
    public function json_and_raw_columns_do_not_scale_with_rows(): void
    {
        $this->assertQueryCountDoesNotGrowWithRows([
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'first_name', 'label' => 'Name'],
                ['key' => 'preferences', 'json' => 'theme', 'label' => 'Theme'],
                ['key' => 'salary', 'label' => 'Salary', 'raw' => '<b>{{ $row->salary }}</b>'],
            ],
        ], 'JSON and raw columns are rendered from loaded data, never re-queried.');
    }

    #[Test]
    public function searching_does_not_introduce_a_query_per_row(): void
    {
        $this->addEmployees(40);

        SchemaCache::columnsFor(ColumnSets::MODEL);

        $render = function (int $records) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            Livewire::test('aftable', [
                'model' => ColumnSets::MODEL,
                'columns' => ColumnSets::withRelations(),
                'records' => $records,
                'tableId' => 'nplus-search-'.$records,
            ])->set('search', 'Bulk')->html();

            return count(DB::getQueryLog());
        };

        $this->assertSame($render(5), $render(40), 'Search must not add per-row queries.');
    }

    #[Test]
    public function sorting_by_a_relation_does_not_introduce_a_query_per_row(): void
    {
        $this->addEmployees(40);

        $render = function (int $records) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            Livewire::test('aftable', [
                'model' => ColumnSets::MODEL,
                'columns' => ColumnSets::withRelations(),
                'records' => $records,
                'tableId' => 'nplus-sort-'.$records,
            ])->call('toggleSort', 'department_name')->html();

            return count(DB::getQueryLog());
        };

        $this->assertSame($render(5), $render(40), 'Relation sorting joins; it must not lazy-load.');
    }

    #[Test]
    public function strict_lazy_loading_prevention_is_never_triggered(): void
    {
        // The definitive check: Laravel throws LazyLoadingViolationException the
        // instant an unloaded relation is touched.
        Model::preventLazyLoading();

        $this->addEmployees(20);

        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withRelations(),
            'records' => 20,
            'tableId' => 'nplus-strict',
        ])->assertOk();

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function strict_lazy_loading_prevention_holds_through_search_sort_and_paging(): void
    {
        Model::preventLazyLoading();

        $this->addEmployees(40);

        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withRelations(),
            'filters' => ['department_name' => ['type' => 'text']],
            'records' => 10,
            'tableId' => 'nplus-strict-flow',
        ]);

        $component->set('search', 'Bulk')->assertOk();
        $component->call('toggleSort', 'department_name')->assertOk();
        $component->call('nextPage')->assertOk();
        $component->call('toggleSort', 'first_name')->assertOk();

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function exporting_does_not_lazy_load_relations(): void
    {
        Model::preventLazyLoading();

        $this->addEmployees(30);

        Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::withRelations(),
            'exportable' => true,
            'tableId' => 'nplus-export',
        ])->call('handleExport', 'csv')->assertFileDownloaded();
    }

    #[Test]
    public function a_plain_table_issues_exactly_two_queries(): void
    {
        // One COUNT for the paginator, one SELECT for the page. Anything more on
        // a table with no relations means something is querying that should not.
        $this->assertSame(
            2,
            $this->queriesToRender([
                'model' => ColumnSets::MODEL,
                'columns' => ColumnSets::basic(),
                'tableId' => 'nplus-plain',
            ]),
            'A plain table should be COUNT + SELECT and nothing else.'
        );
    }

    #[Test]
    public function repeated_interactions_do_not_re_query_the_schema(): void
    {
        // Schema introspection used to run once per column per request - 20
        // queries per interaction on a five-column table. See AUDIT.md BUG-37.
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
            'tableId' => 'nplus-schema',
        ])->set('search', 'Alice');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $component->set('search', 'Bob')->call('toggleSort', 'salary')->call('nextPage');

        $schemaQueries = collect(DB::getQueryLog())
            ->filter(function ($query) {
                $sql = strtolower($query['query']);

                return str_contains($sql, 'pragma')
                    || str_contains($sql, 'information_schema')
                    || str_contains($sql, 'sqlite_master');
            })
            ->count();

        $this->assertSame(0, $schemaQueries, 'Schema lookups must be cached, not repeated per interaction.');
    }
}
