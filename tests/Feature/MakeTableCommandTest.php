<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Support\SchemaIntrospector;
use ArtflowStudio\Table\Tests\Models\TestEmployee;
use ArtflowStudio\Table\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `af-table:make` — the command-line half of the code generator.
 *
 * Same introspection as /aftable/generator, same gate: it reads your schema,
 * so it refuses to run unless introspection is explicitly enabled.
 */
class MakeTableCommandTest extends TestCase
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

    #[Test]
    public function it_refuses_to_run_when_introspection_is_disabled(): void
    {
        config()->set('aftable.generator.enabled', false);

        $this->artisan('af-table:make', ['model' => TestEmployee::class])
            ->expectsOutputToContain('Schema introspection is off.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_rejects_a_class_it_cannot_see(): void
    {
        $this->artisan('af-table:make', ['model' => 'App\\Models\\Nope'])
            ->expectsOutputToContain('is not a model the generator can see')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_accepts_the_short_model_name(): void
    {
        $this->artisan('af-table:make', ['model' => 'TestEmployee'])
            ->expectsOutputToContain("@livewire('aftable'")
            ->assertExitCode(0);
    }

    #[Test]
    public function it_writes_a_mountable_snippet(): void
    {
        $path = $this->outputPath();

        $this->artisan('af-table:make', ['model' => TestEmployee::class, '--output' => $path])
            ->assertExitCode(0);

        $snippet = file_get_contents($path);

        $this->assertStringContainsString("@livewire('aftable', [", $snippet);
        $this->assertStringContainsString('\\'.TestEmployee::class.'::class', $snippet);
        $this->assertStringContainsString("'key' => 'first_name'", $snippet);
        $this->assertStringContainsString("'label' => 'First Name'", $snippet);
    }

    #[Test]
    public function date_and_number_columns_carry_a_format(): void
    {
        $snippet = $this->snippet();

        $this->assertStringContainsString("'key' => 'hire_date', 'label' => 'Hire Date', 'format' => 'date'", $snippet);
        $this->assertStringContainsString("'key' => 'salary', 'label' => 'Salary', 'format' => 'number'", $snippet);
    }

    #[Test]
    public function a_json_column_gets_a_todo_rather_than_a_wrong_path(): void
    {
        // Only the author knows the document's shape. Guessing produces a
        // column that silently renders nothing.
        $snippet = $this->snippet();

        $this->assertStringContainsString("'json' => 'TODO.path'", $snippet);
        $this->assertStringContainsString('TODO: set a path on the JSON column(s)', $snippet);
    }

    #[Test]
    public function relations_are_opt_in(): void
    {
        $this->assertStringNotContainsString("'relation' =>", $this->snippet());
        $this->assertStringContainsString("'relation' => 'department:name'", $this->snippet(['--relations' => true]));
    }

    #[Test]
    public function countable_relations_are_not_emitted_as_columns(): void
    {
        // `timesheets` is a hasMany; `timesheets:something` is not a column.
        $this->assertStringNotContainsString("'relation' => 'timesheets:", $this->snippet(['--relations' => true]));
    }

    #[Test]
    public function the_generated_snippet_is_valid_php_inside_the_directive(): void
    {
        $snippet = $this->snippet();

        // Strip the Blade wrapper and check the array parses.
        preg_match('/@livewire\(\'aftable\', (\[.*\])\)/s', $snippet, $m);

        $this->assertNotEmpty($m, 'The snippet should contain a parseable array.');
        $this->assertNotFalse(
            @eval('return '.str_replace('\\ArtflowStudio', 'ArtflowStudio', $m[1]).';'),
            'The generated array must be valid PHP.'
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function snippet(array $options = []): string
    {
        $path = $this->outputPath();

        $this->artisan('af-table:make', array_merge([
            'model' => TestEmployee::class,
            '--output' => $path,
        ], $options))->run();

        return (string) file_get_contents($path);
    }

    private function outputPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'aftable-make-').'.blade.php';

        $this->beforeApplicationDestroyed(function () use ($path) {
            @unlink($path);
        });

        return $path;
    }
}
