<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Support\SchemaIntrospector;
use ArtflowStudio\Table\Tests\Models\TestEmployee;
use ArtflowStudio\Table\Tests\Support\Models\HiddenColumnModel;
use ArtflowStudio\Table\Tests\Support\Models\SensitiveColumnModel;
use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

/**
 * Schema introspection behind the code generator.
 *
 * The generator used to make you type every model class, column key and
 * relation by hand. It can now read them — which means it can also hand your
 * table names, column names and relation graph to whoever loads the page, so
 * most of what is asserted here is that it stays shut unless you open it.
 */
class GeneratorSchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SchemaIntrospector::forget();

        config()->set('aftable.generator.enabled', true);
        config()->set('aftable.generator.model_paths', [
            'ArtflowStudio\\Table\\Tests\\Models' => realpath(__DIR__.'/../../src/Tests/Models'),
        ]);
    }

    protected function tearDown(): void
    {
        SchemaIntrospector::forget();
        parent::tearDown();
    }

    // -------------------------------------------------------------- the gate

    #[Test]
    public function introspection_is_off_by_default(): void
    {
        // The shipped config value, not the one this test sets in setUp().
        $shipped = require __DIR__.'/../../config/aftable.php';

        $this->assertFalse(
            $shipped['generator']['enabled'],
            'A schema endpoint must never ship switched on.'
        );
    }

    #[Test]
    public function the_model_list_endpoint_404s_when_introspection_is_off(): void
    {
        config()->set('aftable.generator.enabled', false);

        $this->get('/aftable/generator/schema')->assertNotFound();
    }

    #[Test]
    public function the_describe_endpoint_404s_when_introspection_is_off(): void
    {
        config()->set('aftable.generator.enabled', false);

        $this->get('/aftable/generator/schema/'.str_replace('\\', '/', TestEmployee::class))
            ->assertNotFound();
    }

    #[Test]
    public function describe_refuses_a_class_that_is_not_a_discovered_model(): void
    {
        // The allow-list is the point: describe() constructs the class, so an
        // attacker choosing which class gets constructed is a gadget hunt.
        $this->expectException(\InvalidArgumentException::class);

        SchemaIntrospector::describe(Application::class);
    }

    #[Test]
    public function the_describe_endpoint_404s_for_an_unknown_class(): void
    {
        $this->get('/aftable/generator/schema/App/Models/NotAThing')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function hidden_attributes_are_never_returned(): void
    {
        SchemaIntrospector::forget();
        config()->set('aftable.generator.model_paths', [
            'ArtflowStudio\Table\Tests\Support\Models' => realpath(__DIR__.'/../Support/Models'),
        ]);

        $described = SchemaIntrospector::describe(HiddenColumnModel::class);
        $names = array_column($described['columns'], 'name');

        $this->assertNotEmpty($names, 'Sanity: the model should still report its visible columns.');
        $this->assertContains('first_name', $names);

        foreach ((new HiddenColumnModel)->getHidden() as $hidden) {
            $this->assertNotContains($hidden, $names, "[{$hidden}] is hidden and must not be exposed.");
        }
    }

    #[Test]
    public function sensitive_column_names_are_never_suggested(): void
    {
        Schema::create('test_sensitive', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('password');
            $table->string('api_token')->nullable();
            $table->string('remember_token')->nullable();
        });

        SchemaIntrospector::forget();
        config()->set('aftable.generator.model_paths', [
            'ArtflowStudio\Table\Tests\Support\Models' => realpath(__DIR__.'/../Support/Models'),
        ]);

        $columns = collect(SchemaIntrospector::describe(SensitiveColumnModel::class)['columns'])
            ->keyBy('name');

        // Reported (you may need to know the column is there) but never offered.
        foreach (['password', 'api_token', 'remember_token'] as $sensitive) {
            $this->assertArrayHasKey($sensitive, $columns);
            $this->assertNull(
                $columns[$sensitive]['suggested'],
                "[{$sensitive}] must never be offered as a table column."
            );
        }

        $this->assertNotNull($columns['name']['suggested'], 'Ordinary columns are still offered.');
    }

    // -------------------------------------------------------------- discovery

    #[Test]
    public function it_discovers_the_models_under_the_configured_paths(): void
    {
        $models = SchemaIntrospector::models();

        $this->assertArrayHasKey('TestEmployee', $models);
        $this->assertSame(TestEmployee::class, $models['TestEmployee']);
    }

    #[Test]
    public function the_model_list_endpoint_returns_them(): void
    {
        $this->get('/aftable/generator/schema')
            ->assertOk()
            ->assertJsonFragment(['models' => array_values(SchemaIntrospector::models())]);
    }

    // --------------------------------------------------------------- describe

    #[Test]
    public function it_reports_the_table_and_key(): void
    {
        $described = SchemaIntrospector::describe(TestEmployee::class);

        $this->assertSame('test_employees', $described['table']);
        $this->assertSame('id', $described['key']);
        $this->assertSame('TestEmployee', $described['short']);
    }

    #[Test]
    public function it_reads_the_real_columns(): void
    {
        $names = array_column(SchemaIntrospector::describe(TestEmployee::class)['columns'], 'name');

        foreach (['id', 'first_name', 'last_name', 'salary', 'position'] as $expected) {
            $this->assertContains($expected, $names);
        }
    }

    #[Test]
    public function a_date_column_is_suggested_as_a_date(): void
    {
        $columns = collect(SchemaIntrospector::describe(TestEmployee::class)['columns'])
            ->keyBy('name');

        $this->assertSame('date', $columns['created_at']['suggested']['kind']);
        $this->assertSame('d M Y', $columns['created_at']['suggested']['format']);
    }

    #[Test]
    public function a_numeric_column_is_suggested_as_a_number(): void
    {
        $columns = collect(SchemaIntrospector::describe(TestEmployee::class)['columns'])
            ->keyBy('name');

        $this->assertSame('number', $columns['salary']['suggested']['kind']);
        $this->assertTrue($columns['salary']['suggested']['sortable']);
    }

    #[Test]
    public function a_json_cast_column_is_suggested_as_json(): void
    {
        $columns = collect(SchemaIntrospector::describe(TestEmployee::class)['columns'])
            ->keyBy('name');

        $this->assertArrayHasKey('skills', $columns, 'Sanity: the fixture has a JSON column.');
        $this->assertSame('json', $columns['skills']['suggested']['kind']);
    }

    #[Test]
    public function the_primary_key_is_flagged_and_not_searchable(): void
    {
        $columns = collect(SchemaIntrospector::describe(TestEmployee::class)['columns'])
            ->keyBy('name');

        $this->assertTrue($columns['id']['primary']);
        $this->assertFalse($columns['id']['suggested']['searchable']);
    }

    #[Test]
    public function labels_are_humanised_and_lose_their_id_suffix(): void
    {
        $columns = collect(SchemaIntrospector::describe(TestEmployee::class)['columns'])
            ->keyBy('name');

        $this->assertSame('First Name', $columns['first_name']['suggested']['label']);
        $this->assertSame('Department', $columns['department_id']['suggested']['label']);
    }

    #[Test]
    public function the_soft_delete_column_is_reported_but_never_suggested(): void
    {
        // `deleted_at` is NULL for every row you can see, so it is only ever
        // an empty column.
        $columns = collect(SchemaIntrospector::describe(TestEmployee::class)['columns'])
            ->keyBy('name');

        $this->assertArrayHasKey('deleted_at', $columns);
        $this->assertNull($columns['deleted_at']['suggested']);
    }

    // -------------------------------------------------------------- relations

    #[Test]
    public function it_finds_the_declared_relations_with_their_columns(): void
    {
        $relations = collect(SchemaIntrospector::describe(TestEmployee::class)['relations'])
            ->keyBy('name');

        $this->assertArrayHasKey('department', $relations);
        $this->assertSame('BelongsTo', $relations['department']['type']);
        $this->assertContains('name', $relations['department']['columns']);
        $this->assertFalse($relations['department']['countable']);
    }

    #[Test]
    public function a_to_many_relation_is_marked_countable(): void
    {
        $relations = collect(SchemaIntrospector::describe(TestEmployee::class)['relations'])
            ->keyBy('name');

        $this->assertArrayHasKey('timesheets', $relations);
        $this->assertTrue(
            $relations['timesheets']['countable'],
            'withCount() only means something for a to-many relation.'
        );
    }

    #[Test]
    public function describing_a_model_runs_no_row_queries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        SchemaIntrospector::describe(TestEmployee::class);

        $selects = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_starts_with(strtolower(trim($q['query'])), 'select * from'))
            ->count();

        $this->assertSame(0, $selects, 'Introspection must read metadata, never rows.');
    }
}
