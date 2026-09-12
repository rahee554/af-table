<?php

namespace ArtflowStudio\Table\Console\Commands;

use ArtflowStudio\Table\Support\SchemaIntrospector;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;

/**
 * Print a ready-to-paste table for a model, read from the real schema.
 *
 * The web generator at /aftable/generator does the same job with a live
 * preview; this is the one-line version for when you already know the model.
 */
class MakeTableCommand extends Command
{
    protected $signature = 'af-table:make
                            {model? : Model class, e.g. "App\\Models\\User" or just "User"}
                            {--relations : Include one column per belongsTo relation}
                            {--o|output= : Write the snippet to this file instead of printing it}';

    protected $description = 'Generate an AFTable Blade snippet from a model\'s schema';

    public function handle(): int
    {
        if (! SchemaIntrospector::enabled()) {
            $this->components->error('Schema introspection is off.');
            $this->line('  Set <options=bold>AFTABLE_GENERATOR_ENABLED=true</> (local only — it reads your schema).');

            return self::FAILURE;
        }

        $models = SchemaIntrospector::models();

        if ($models === []) {
            $this->components->error('No Eloquent models found.');
            $this->line('  Check <options=bold>aftable.generator.model_paths</>.');

            return self::FAILURE;
        }

        $model = $this->resolveModel($models);

        if ($model === null) {
            return self::FAILURE;
        }

        $schema = SchemaIntrospector::describe($model);

        if ($schema['columns'] === []) {
            $this->components->error("Table [{$schema['table']}] does not exist. Run your migrations first.");

            return self::FAILURE;
        }

        $snippet = $this->render($schema);

        if ($path = $this->option('output')) {
            file_put_contents($path, $snippet.PHP_EOL);
            $this->components->info("Written to {$path}");

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line($snippet);
        $this->newLine();
        $this->components->info("{$schema['short']} · table {$schema['table']} · ".count($schema['columns']).' columns read');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, class-string>  $models
     */
    private function resolveModel(array $models): ?string
    {
        $argument = $this->argument('model');

        if ($argument === null) {
            if (! $this->input->isInteractive()) {
                $this->components->error('No model given. Pass one, or run interactively.');

                return null;
            }

            return select('Which model?', array_values($models));
        }

        $argument = str_replace('/', '\\', trim((string) $argument));

        // Full class name.
        if (in_array($argument, $models, true)) {
            return $argument;
        }

        // Short name — "User" for App\Models\User.
        $short = class_basename($argument);

        if (isset($models[$short])) {
            return $models[$short];
        }

        $this->components->error("[{$argument}] is not a model the generator can see.");
        $this->line('  Available: '.implode(', ', array_keys($models)));

        return null;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function render(array $schema): string
    {
        $lines = [];
        $jsonNotes = [];

        foreach ($schema['columns'] as $column) {
            $suggested = $column['suggested'];

            if ($suggested === null) {
                continue;
            }

            $definition = ["'key' => '{$suggested['key']}'", "'label' => '".addslashes($suggested['label'])."'"];

            if (($suggested['kind'] ?? null) === 'json') {
                // A JSON column is nothing without a path, and only the author
                // knows the shape. Emit a TODO rather than a silently wrong key.
                $definition[] = "'json' => 'TODO.path'";
                $jsonNotes[] = $suggested['key'];
            } elseif (($suggested['kind'] ?? null) === 'date') {
                $definition[] = "'format' => 'date'";
            } elseif (($suggested['kind'] ?? null) === 'boolean') {
                $definition[] = "'format' => 'boolean'";
            } elseif (($suggested['kind'] ?? null) === 'number') {
                $definition[] = "'format' => 'number'";
            }

            if (($suggested['sortable'] ?? true) === false) {
                $definition[] = "'sortable' => false";
            }

            if (! ($suggested['searchable'] ?? false) && ($suggested['kind'] ?? null) !== 'json') {
                $definition[] = "'searchable' => false";
            }

            $lines[] = '        ['.implode(', ', $definition).'],';
        }

        if ($this->option('relations')) {
            foreach ($schema['relations'] as $relation) {
                if ($relation['countable'] || $relation['columns'] === []) {
                    continue;
                }

                $target = $this->bestColumn($relation['columns']);
                $key = $relation['name'].'_'.$target;

                $lines[] = sprintf(
                    "        ['key' => '%s', 'label' => '%s', 'relation' => '%s:%s'],",
                    $key,
                    addslashes(Str::headline($relation['name'])),
                    $relation['name'],
                    $target
                );
            }
        }

        $snippet = "@livewire('aftable', [\n"
            ."    'model' => \\{$schema['model']}::class,\n"
            ."    'columns' => [\n"
            .implode("\n", $lines)."\n"
            ."    ],\n"
            ."    'searchable' => true,\n"
            ."    'exportable' => true,\n"
            .'])';

        if ($jsonNotes !== []) {
            $snippet .= "\n\n{{-- TODO: set a path on the JSON column(s): ".implode(', ', $jsonNotes).' --}}';
        }

        return $snippet;
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function bestColumn(array $columns): string
    {
        foreach (['name', 'title', 'label', 'first_name', 'email', 'slug'] as $preferred) {
            if (in_array($preferred, $columns, true)) {
                return $preferred;
            }
        }

        return $columns[0] ?? 'id';
    }
}
