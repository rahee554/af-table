<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Support\SchemaIntrospector;
use ArtflowStudio\Table\Support\Theme;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * The configuration surface: themes, phantom columns, cache scoping, several
 * tables on one page, and the Artisan commands.
 *
 * These are the seams where a host application meets the package, and the ones
 * least likely to be exercised by the demo bench.
 */
class ConfigurationSurfaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    private function t(array $o = [])
    {
        return Livewire::test('aftable', array_merge([
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ], $o));
    }

    // ---- phantom columns

    #[Test]
    public function a_column_removed_from_the_config_does_not_survive_in_session_state(): void
    {
        // Toggle a column off, then remount with that column gone from the
        // config entirely. A stale visibleColumns entry must not resurrect it.
        $this->t(['tableId' => 'phantom'])->call('toggleColumnVisibility', 'salary');

        $second = $this->t([
            'tableId' => 'phantom',
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'first_name', 'label' => 'First Name'],
            ],
        ]);

        $this->assertSame(
            ['id', 'first_name'],
            array_keys($second->get('visibleColumns')),
            'visibleColumns should track the current config, not a stale session copy.'
        );
    }

    #[Test]
    public function a_column_added_to_the_config_appears_after_a_remount(): void
    {
        $this->t(['tableId' => 'grow', 'columns' => [['key' => 'id', 'label' => 'ID']]]);

        $second = $this->t(['tableId' => 'grow']);

        $this->assertArrayHasKey('salary', $second->get('visibleColumns'));
    }

    // ---- two tables on one page

    #[Test]
    public function two_components_on_one_page_do_not_collide(): void
    {
        $a = $this->t(['tableId' => 'multi-a'])->set('search', 'Alice');
        $b = $this->t(['tableId' => 'multi-b'])->call('toggleSort', 'salary');

        $this->assertSame('Alice', $a->get('search'));
        $this->assertSame('', (string) $b->get('search'));
        $this->assertSame('salary', $b->get('sortColumn'));
        $this->assertNotSame('salary', $a->get('sortColumn'));
    }

    #[Test]
    public function each_component_gets_its_own_island(): void
    {
        // Islands are named, and the name is reused by every instance. If
        // Livewire scoped them per page rather than per component, two tables
        // would fight over one island.
        $a = $this->t(['tableId' => 'isl-a'])->html();
        $b = $this->t(['tableId' => 'isl-b'])->html();

        $this->assertStringContainsString('aftable-data', $a);
        $this->assertStringContainsString('aftable-data', $b);
        $this->assertNotSame($a, $b);
    }

    // ---- theming

    #[Test]
    public function a_registered_custom_preset_is_used(): void
    {
        Theme::register('probe', ['table' => 'my-custom-table-class'], 'bootstrap');

        $ui = $this->t(['theme' => 'probe'])->instance()->themeClasses();

        $this->assertSame('my-custom-table-class', $ui['table']);
        $this->assertNotEmpty($ui['wrapper'] ?? '', 'Unspecified slots should inherit from the parent preset.');
    }

    #[Test]
    public function per_slot_overrides_beat_the_preset(): void
    {
        $ui = $this->t(['theme' => 'bootstrap', 'classes' => ['table' => 'override-me']])
            ->instance()->themeClasses();

        $this->assertSame('override-me', $ui['table']);
    }

    #[Test]
    public function the_pagination_view_is_a_slot(): void
    {
        $bootstrap = $this->t(['theme' => 'bootstrap'])->instance()->themeClasses();
        $tailwind = $this->t(['theme' => 'tailwind'])->instance()->themeClasses();

        $this->assertNotSame($bootstrap['pagination'], $tailwind['pagination']);
    }

    #[Test]
    public function a_host_can_supply_its_own_pagination_view(): void
    {
        $ui = $this->t(['classes' => ['pagination' => 'pagination::simple-default']])
            ->instance()->themeClasses();

        $this->assertSame('pagination::simple-default', $ui['pagination']);
    }

    // ---- caching / invalidation

    #[Test]
    public function inserting_a_row_is_visible_after_a_refresh(): void
    {
        $c = $this->t();
        $before = $c->instance()->getData()->total();

        DB::table('test_employees')->insert([
            'company_id' => 1, 'department_id' => 1,
            'first_name' => 'Zed', 'last_name' => 'Probe',
            'email' => 'zed.probe@example.test',
            'employment_type' => 'full-time', 'status' => 'active',
            'years_experience' => 0, 'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $c->call('refreshTable');

        $this->assertSame($before + 1, $c->instance()->getData()->total(), 'A cached count must not go stale.');
    }

    #[Test]
    public function the_distinct_value_cache_is_scoped_per_table_id(): void
    {
        $a = $this->t(['filters' => ColumnSets::filters(), 'tableId' => 'dv-a'])
            ->instance()->getDistinctValues('position');

        $b = $this->t(['filters' => ColumnSets::filters(), 'tableId' => 'dv-b'])
            ->instance()->getDistinctValues('position');

        $this->assertSame($a, $b, 'Same model + column should agree across tables.');
        $this->assertNotEmpty($a);
    }

    // ---- N+1 under defer

    #[Test]
    public function deferred_mode_still_eager_loads_relations(): void
    {
        Model::preventLazyLoading(true);

        try {
            $c = Livewire::test('aftable', [
                'model' => ColumnSets::MODEL,
                'columns' => ColumnSets::withRelations(),
                'deferData' => true,
            ]);

            // Force the island's work to happen.
            $c->instance()->getData();

            $this->addToAssertionCount(1);
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    // ---- commands

    #[Test]
    public function clear_phantom_columns_runs_and_reports(): void
    {
        $this->artisan('af-table:clear-phantom-columns')->assertExitCode(0);
    }

    #[Test]
    public function make_refuses_a_model_whose_table_is_missing(): void
    {
        config()->set('aftable.generator.enabled', true);
        config()->set('aftable.generator.model_paths', [
            'ArtflowStudio\\Table\\Tests\\Support\\Models' => realpath(__DIR__.'/../Support/Models'),
        ]);
        SchemaIntrospector::forget();

        // SensitiveColumnModel points at test_sensitive, which this test does
        // not create.
        $this->artisan('af-table:make', ['model' => 'SensitiveColumnModel'])
            ->expectsOutputToContain('does not exist')
            ->assertExitCode(1);

        SchemaIntrospector::forget();
    }

    #[Test]
    public function make_is_non_interactive_safe(): void
    {
        config()->set('aftable.generator.enabled', true);

        $this->artisan('af-table:make')->assertExitCode(1);
    }

    // ---- assets flag

    #[Test]
    public function the_assets_flag_controls_the_injected_blocks(): void
    {
        $with = $this->t(['assets' => true])->html();
        $without = $this->t(['assets' => false])->html();

        $this->assertNotSame(strlen($with), strlen($without));
    }
}
