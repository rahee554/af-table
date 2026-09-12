<?php

namespace ArtflowStudio\Table\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Cached schema introspection.
 *
 * `isColumnSearchable()` verified each column against
 * `Schema::getColumnListing()` — once per column, and it is reached from
 * several call sites per render. Profiling a five-column table showed
 * **20 schema queries on every search, sort and page change**:
 *
 *     update: search   22 queries  {"COUNT":1,"SCHEMA":20,"SELECT":1}
 *     update: sort     22 queries  {"COUNT":1,"SCHEMA":20,"SELECT":1}
 *     update: page     22 queries  {"COUNT":1,"SCHEMA":20,"SELECT":1}
 *
 * A table's column list does not change between two clicks of a paginator, so
 * this memoises per process and caches across requests under the `schema` TTL
 * bucket. After wiring this in, the same interactions issue 2 queries.
 */
final class SchemaCache
{
    /** @var array<string, list<string>> Per-process memo, keyed "connection.table". */
    private static array $columns = [];

    /** @var array<string, string> model class => table name */
    private static array $tables = [];

    /**
     * Column names for a model's table.
     *
     * @return list<string>
     */
    public static function columnsFor(string|Model $model): array
    {
        $instance = self::instance($model);

        if ($instance === null) {
            return [];
        }

        $connection = $instance->getConnectionName() ?? 'default';
        $table = $instance->getTable();
        $memoKey = $connection.'.'.$table;

        if (isset(self::$columns[$memoKey])) {
            return self::$columns[$memoKey];
        }

        $columns = TableCache::remember(
            'schema:'.$memoKey,
            'schema',
            function () use ($instance, $table) {
                try {
                    return Schema::connection($instance->getConnectionName())
                        ->getColumnListing($table);
                } catch (\Throwable) {
                    // An unreachable database must not take the render down;
                    // an empty list simply means "cannot verify".
                    return [];
                }
            },
            ['schema']
        );

        return self::$columns[$memoKey] = is_array($columns) ? $columns : [];
    }

    /** Does the model's table physically have this column? */
    public static function hasColumn(string|Model $model, string $column): bool
    {
        if ($column === '') {
            return false;
        }

        return in_array($column, self::columnsFor($model), true);
    }

    /** Table name for a model, memoised. */
    public static function tableFor(string|Model $model): ?string
    {
        $instance = self::instance($model);

        return $instance?->getTable();
    }

    /** Forget everything. Used by tests and after a migration. */
    public static function flush(): void
    {
        self::$columns = [];
        self::$tables = [];
    }

    /**
     * Resolve a model class or instance to an instance, without exploding on
     * the '__static__' sentinel the component uses in foreach mode.
     */
    private static function instance(string|Model $model): ?Model
    {
        if ($model instanceof Model) {
            return $model;
        }

        if ($model === '' || $model === '__static__' || ! class_exists($model)) {
            return null;
        }

        try {
            $instance = new $model;
        } catch (\Throwable) {
            return null;
        }

        return $instance instanceof Model ? $instance : null;
    }
}
