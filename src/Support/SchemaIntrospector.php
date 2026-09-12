<?php

namespace ArtflowStudio\Table\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Reads the host application's own schema so the code generator can offer real
 * models and real columns instead of asking you to type them.
 *
 * This is deliberately locked down. A schema endpoint on a reachable host hands
 * an attacker your table names, your column names and your relation graph, so:
 *
 *   - it is off unless `aftable.generator.enabled` is explicitly true;
 *   - it is never available in production (the route group is gated twice);
 *   - `describe()` only accepts a class that already appeared in `models()`,
 *     so it can never be used to instantiate an arbitrary class; and
 *   - anything the model marks `$hidden` is dropped before it is returned.
 */
class SchemaIntrospector
{
    /** Cast names that mean "this column holds a JSON document". */
    private const JSON_CASTS = ['array', 'json', 'object', 'collection', 'encrypted:array', 'encrypted:json'];

    /** Column names that are never useful in a table and often sensitive. */
    private const NEVER_SUGGEST = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        'api_token', 'secret', 'private_key',
    ];

    /** @var array<string, class-string<Model>>|null */
    private static ?array $models = null;

    public static function enabled(): bool
    {
        return (bool) config('aftable.generator.enabled', false);
    }

    /**
     * Every Eloquent model the generator is allowed to describe.
     *
     * @return array<string, class-string<Model>> short name => FQCN
     */
    public static function models(): array
    {
        if (self::$models !== null) {
            return self::$models;
        }

        $found = [];

        foreach ((array) config('aftable.generator.model_paths', []) as $namespace => $path) {
            if (is_int($namespace)) {
                $namespace = (string) config('aftable.generator.model_namespace', 'App\\Models\\');
            }

            foreach (self::phpFilesIn((string) $path) as $file) {
                $class = self::classFor($file, (string) $path, rtrim($namespace, '\\').'\\');

                if ($class !== null && self::isConcreteModel($class)) {
                    $found[class_basename($class)] = $class;
                }
            }
        }

        ksort($found);

        return self::$models = $found;
    }

    /** Test seam: drop the memoised model list. */
    public static function forget(): void
    {
        self::$models = null;
    }

    /**
     * Describe one model: its table, key, columns and relations, each with a
     * suggested AFTable column definition.
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException when the class is not an allowed model
     */
    public static function describe(string $model): array
    {
        // Allow-list, not a class_exists() check: `describe()` instantiates the
        // class, and an attacker choosing which class gets constructed is a
        // gadget hunt waiting to happen.
        if (! in_array($model, self::models(), true)) {
            throw new \InvalidArgumentException("[{$model}] is not a model the generator may describe.");
        }

        /** @var Model $instance */
        $instance = new $model;

        $table = $instance->getTable();
        $hidden = array_map('strval', $instance->getHidden());
        $casts = $instance->getCasts();

        return [
            'model' => $model,
            'short' => class_basename($model),
            'table' => $table,
            'key' => $instance->getKeyName(),
            'columns' => self::columns($table, $hidden, $casts, $instance->getKeyName(), self::softDeleteColumn($instance)),
            'relations' => self::relations($instance),
        ];
    }

    // ----------------------------------------------------------------- columns

    /**
     * @param  array<int, string>  $hidden
     * @param  array<string, string>  $casts
     * @param  string|null  $softDeleteColumn reported, but never suggested
     * @return array<int, array<string, mixed>>
     */
    private static function columns(string $table, array $hidden, array $casts, string $key, ?string $softDeleteColumn): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $out = [];

        foreach (Schema::getColumns($table) as $column) {
            $name = (string) $column['name'];

            // Never leak a hidden attribute's existence through the generator.
            if (in_array($name, $hidden, true)) {
                continue;
            }

            $cast = $casts[$name] ?? null;
            $type = strtolower((string) ($column['type_name'] ?? $column['type'] ?? 'string'));

            $out[] = [
                'name' => $name,
                'type' => $type,
                'cast' => $cast,
                'nullable' => (bool) ($column['nullable'] ?? true),
                'primary' => $name === $key,
                'suggested' => $name === $softDeleteColumn
                    ? null   // always NULL for a row you can see; never a column
                    : self::suggest($name, $type, $cast, $name === $key),
            ];
        }

        return $out;
    }

    /**
     * Turn a database column into the AFTable definition a person would most
     * likely have written for it.
     *
     * @return array<string, mixed>|null null = do not offer this column
     */
    private static function suggest(string $name, string $type, ?string $cast, bool $isKey): ?array
    {
        if (in_array($name, self::NEVER_SUGGEST, true)) {
            return null;
        }

        $definition = [
            'key' => $name,
            'label' => self::label($name),
        ];

        if ($isKey) {
            return $definition + ['sortable' => true, 'searchable' => false];
        }

        if ($cast !== null && in_array(strtolower($cast), self::JSON_CASTS, true)) {
            // A JSON column needs a path; the generator asks for one.
            return $definition + ['kind' => 'json', 'sortable' => false, 'searchable' => false];
        }

        if (self::looksLikeDate($type, $cast)) {
            return $definition + ['kind' => 'date', 'format' => 'd M Y', 'sortable' => true, 'searchable' => false];
        }

        if (self::looksLikeBool($type, $cast)) {
            return $definition + ['kind' => 'boolean', 'sortable' => true, 'searchable' => false];
        }

        if (self::looksLikeNumber($type, $cast)) {
            return $definition + ['kind' => 'number', 'sortable' => true, 'searchable' => false];
        }

        // Long free text sorts badly and blows the row height out.
        $long = in_array($type, ['text', 'mediumtext', 'longtext', 'blob', 'json', 'jsonb'], true);

        return $definition + [
            'kind' => 'text',
            'sortable' => ! $long,
            'searchable' => true,
        ];
    }

    /** The model's soft-delete column, if it soft-deletes at all. */
    private static function softDeleteColumn(Model $instance): ?string
    {
        return method_exists($instance, 'getDeletedAtColumn')
            ? $instance->getDeletedAtColumn()
            : null;
    }

    private static function looksLikeDate(string $type, ?string $cast): bool
    {
        $cast = strtolower((string) $cast);

        return str_contains($type, 'date')
            || str_contains($type, 'time')
            || $cast === 'date'
            || $cast === 'datetime'
            || $cast === 'immutable_date'
            || $cast === 'immutable_datetime'
            || str_starts_with($cast, 'date:')
            || str_starts_with($cast, 'datetime:');
    }

    private static function looksLikeBool(string $type, ?string $cast): bool
    {
        return in_array(strtolower((string) $cast), ['bool', 'boolean'], true)
            || in_array($type, ['bool', 'boolean', 'tinyint(1)'], true);
    }

    private static function looksLikeNumber(string $type, ?string $cast): bool
    {
        if (in_array(strtolower((string) $cast), ['int', 'integer', 'float', 'double', 'real', 'decimal'], true)) {
            return true;
        }

        foreach (['int', 'decimal', 'numeric', 'float', 'double', 'real'] as $needle) {
            if (str_contains($type, $needle)) {
                return true;
            }
        }

        return false;
    }

    // --------------------------------------------------------------- relations

    /**
     * Relations declared on the model, with the columns of the related table
     * so the generator can offer `relation => 'company:name'` directly.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function relations(Model $instance): array
    {
        $out = [];

        foreach ((new ReflectionClass($instance))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (! self::couldBeRelation($method, $instance)) {
                continue;
            }

            try {
                $relation = $instance->{$method->getName()}();
            } catch (\Throwable) {
                // A relation that needs a persisted parent, or a method that
                // simply is not one. Either way, not describable.
                continue;
            }

            if (! $relation instanceof Relation) {
                continue;
            }

            $related = $relation->getRelated();
            $returns = $relation::class;

            $out[] = [
                'name' => $method->getName(),
                'type' => class_basename($returns),
                'related' => $related::class,
                'table' => $related->getTable(),
                'columns' => self::relatedColumns($related),
                'countable' => self::isCountable($returns),
            ];
        }

        usort($out, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $out;
    }

    /**
     * Could this method be a relation?
     *
     * Relation methods are almost never annotated in the wild, so a declared
     * return type cannot be required — but calling every public method to find
     * out would run arbitrary model code. This narrows it to methods the model
     * itself declares, with no arguments, that are not accessors, mutators or
     * query scopes; and if a return type IS declared, it has to be a Relation.
     */
    private static function couldBeRelation(ReflectionMethod $method, Model $instance): bool
    {
        if ($method->isStatic() || $method->getNumberOfParameters() > 0) {
            return false;
        }

        if ($method->getDeclaringClass()->getName() !== $instance::class) {
            return false;
        }

        $name = $method->getName();

        if (str_starts_with($name, 'scope') || str_starts_with($name, '__')) {
            return false;
        }

        // getFooAttribute() / setFooAttribute()
        if (preg_match('/^(get|set)[A-Za-z0-9_]*Attribute$/', $name) === 1) {
            return false;
        }

        $type = $method->getReturnType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            return is_subclass_of($type->getName(), Relation::class)
                || $type->getName() === Relation::class;
        }

        // No usable return type: fall through and let the instanceof check decide.
        return $type === null;
    }

    /** @return array<int, string> */
    private static function relatedColumns(Model $related): array
    {
        $table = $related->getTable();

        if (! Schema::hasTable($table)) {
            return [];
        }

        $hidden = array_map('strval', $related->getHidden());

        return array_values(array_filter(
            SchemaCache::columnsFor($related),
            fn (string $c) => ! in_array($c, $hidden, true) && ! in_array($c, self::NEVER_SUGGEST, true)
        ));
    }

    /** `withCount()` only means something for a to-many relation. */
    private static function isCountable(string $relationClass): bool
    {
        foreach (['HasMany', 'BelongsToMany', 'HasManyThrough', 'MorphMany', 'MorphToMany'] as $many) {
            if (str_ends_with($relationClass, $many)) {
                return true;
            }
        }

        return false;
    }

    // ------------------------------------------------------------- discovery

    /** @return array<int, string> */
    private static function phpFilesIn(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private static function classFor(string $file, string $root, string $namespace): ?string
    {
        $relative = str_replace([realpath($root) ?: $root, $root], '', $file);
        $relative = trim(str_replace(['\\', '/'], '\\', $relative), '\\');

        if ($relative === '') {
            return null;
        }

        $class = $namespace.Str::beforeLast($relative, '.php');

        return class_exists($class) ? $class : null;
    }

    private static function isConcreteModel(string $class): bool
    {
        if (! is_subclass_of($class, Model::class)) {
            return false;
        }

        return ! (new ReflectionClass($class))->isAbstract();
    }

    /** `first_name` → `First Name`, `company_id` → `Company`, `id` → `ID`. */
    private static function label(string $column): string
    {
        if ($column === 'id') {
            return 'ID';
        }

        return Str::headline(Str::endsWith($column, '_id') ? Str::beforeLast($column, '_id') : $column);
    }
}
