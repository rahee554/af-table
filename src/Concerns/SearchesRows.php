<?php

namespace ArtflowStudio\Table\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * The global search box: which columns take part, how the term is
 * normalised, and how it reaches the query.
 *
 * Consolidated in 1.6.0 from: HasUnifiedSearch.
 */
trait SearchesRows
{
    /**
     * LIKE escape character.
     *
     * Not a backslash: `ESCAPE '\'` means different things to MySQL, SQLite
     * and SQL Server, and `\` is the one character whose literal-string
     * handling is not portable. `=` has no special meaning in a SQL string
     * literal on any of them.
     */
    private const LIKE_ESCAPE = '=';

    /**
     * Neutralise LIKE metacharacters in a user-supplied term.
     *
     * Binding the value makes it injection-safe, but a bound LIKE value is
     * still a *pattern*: `%%%` matched every row, `____` matched every row with
     * four or more characters, and `Ali%ce` matched `Alice`. A search box is
     * not a pattern box. See AUDIT.md BUG-48.
     */
    protected function escapeLikeTerm(string $term): string
    {
        $e = self::LIKE_ESCAPE;

        return str_replace([$e, '%', '_'], [$e.$e, $e.'%', $e.'_'], $term);
    }

    /**
     * OR a literal "contains" condition onto the query.
     *
     * Uses whereRaw because the builder never emits an ESCAPE clause. The
     * column goes through the grammar's own `wrap()`, which quotes identifiers
     * and — importantly — compiles `column->json->path` into the driver's JSON
     * extraction expression, so this works for JSON columns too.
     */
    protected function orWhereContains($query, string $column, string $term)
    {
        $grammar = ($query instanceof Builder ? $query->getQuery() : $query)
            ->getGrammar();

        return $query->orWhereRaw(
            $grammar->wrap($column)." LIKE ? ESCAPE '".self::LIKE_ESCAPE."'",
            ['%'.$this->escapeLikeTerm($term).'%']
        );
    }

    /**
     * AND a literal "contains" condition onto the query.
     *
     * The `like` filter operator is not offered in the toolbar, but
     * `filterOperator` is a query-string property, so its value is user input
     * like any other. Same escaping as the search box.
     */
    protected function whereContains($query, string $column, string $term)
    {
        $grammar = ($query instanceof Builder ? $query->getQuery() : $query)
            ->getGrammar();

        return $query->whereRaw(
            $grammar->wrap($column)." LIKE ? ESCAPE '".self::LIKE_ESCAPE."'",
            ['%'.$this->escapeLikeTerm($term).'%']
        );
    }

    /**
     * Apply search to regular columns
     * From HasQueryBuilding
     */
    public function applyColumnSearch($query, $searchTerm)
    {
        $searchableColumns = $this->getSearchableColumns();

        foreach ($searchableColumns as $column) {
            if (! $this->isColumnSearchable($column)) {
                continue;
            }

            // Qualified: relation sorting joins, and an unqualified `id` or
            // `name` in this OR-group is then ambiguous. See AUDIT.md BUG-39.
            $this->orWhereContains(
                $query,
                method_exists($this, 'qualifyColumn') ? $this->qualifyColumn($column) : $column,
                $searchTerm
            );
        }

        return $query;
    }

    /**
     * Apply search to JSON columns
     * From HasQueryBuilding and HasSearch
     */
    public function applyJsonSearch($query, $searchTerm)
    {
        $jsonColumns = $this->getSearchableJsonColumns();

        foreach ($jsonColumns as $column => $paths) {
            if (is_array($paths)) {
                foreach ($paths as $path) {
                    $this->orWhereContains($query, "{$column}->{$path}", $searchTerm);
                }
            } else {
                // Search in entire JSON column
                $this->orWhereContains($query, $column, $searchTerm);
            }
        }

        return $query;
    }

    /**
     * Apply optimized search to query
     * Consolidated from HasSearch and HasQueryBuilding
     */
    public function applyOptimizedSearch($query)
    {
        if (empty($this->search) || strlen(trim($this->search)) < 3) {
            return $query;
        }

        $searchTerm = $this->sanitizeSearch($this->search);
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function ($subQuery) use ($searchTerm) {
            $this->applyColumnSearch($subQuery, $searchTerm);
            $this->applyRelationSearch($subQuery, $searchTerm);
            $this->applyJsonSearch($subQuery, $searchTerm);
        });
    }

    /**
     * Apply search to relationship columns
     * From HasQueryBuilding and HasSearch
     */
    public function applyRelationSearch($query, $searchTerm)
    {
        $relationColumns = $this->getSearchableRelations();

        foreach ($relationColumns as $relation => $columns) {
            if (is_array($columns)) {
                $query->orWhereHas($relation, function ($relationQuery) use ($columns, $searchTerm) {
                    $relationQuery->where(function ($subQuery) use ($columns, $searchTerm) {
                        foreach ($columns as $column) {
                            $this->orWhereContains($subQuery, $column, $searchTerm);
                        }
                    });
                });
            }
        }

        return $query;
    }

    /**
     * Set search value programmatically
     * From HasSearch
     */
    public function setSearch($searchTerm)
    {
        $this->search = $this->sanitizeSearch($searchTerm);
        $this->resetPage();

        // Auto-save if enabled
        if ($this->enableSessionPersistence ?? true) {
            $this->saveSearchToSession($this->search);
        }
    }
}
