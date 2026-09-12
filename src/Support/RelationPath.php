<?php

namespace ArtflowStudio\Table\Support;

/**
 * One parser for the `'relation' => 'a.b:column'` column syntax.
 *
 * The package had **40+ separate `explode(':')` call sites** across components,
 * traits and Blade views, most of them unguarded list-destructuring:
 *
 *     [$relationPath, $attribute] = explode(':', $relationString);
 *
 * which emits `Undefined array key 1` the moment a relation string is missing
 * its colon, and quietly produces a null attribute afterwards. This class is
 * the single place that knows the format, so a malformed value fails once,
 * predictably, and every caller gets the same answer.
 *
 * Instances are immutable and cheap; parsing is memoised per string because the
 * same relation is re-parsed for every row of every render.
 */
final class RelationPath
{
    /** @var array<string, self|null> */
    private static array $cache = [];

    /**
     * @param  string  $path       Relation chain, e.g. "department.company"
     * @param  string  $attribute  Column on the final relation, e.g. "name"
     * @param  list<string>  $segments  ["department", "company"]
     * @param  list<string>  $eagerLoads ["department", "department.company"]
     */
    private function __construct(
        public readonly string $path,
        public readonly string $attribute,
        public readonly array $segments,
        public readonly array $eagerLoads,
    ) {}

    /**
     * Parse "a.b:column". Returns null when the string is not a valid relation
     * spec — callers should treat null as "this is not a relation column".
     */
    public static function tryParse(?string $relation): ?self
    {
        if ($relation === null) {
            return null;
        }

        if (array_key_exists($relation, self::$cache)) {
            return self::$cache[$relation];
        }

        return self::$cache[$relation] = self::build($relation);
    }

    /**
     * Parse or throw. Use where a malformed value is a programming error.
     */
    public static function parse(string $relation): self
    {
        return self::tryParse($relation)
            ?? throw new \InvalidArgumentException(
                "Invalid relation [{$relation}]. Expected 'relation:column' or 'a.b:column'."
            );
    }

    /** Is this string shaped like a relation spec at all? */
    public static function isValid(?string $relation): bool
    {
        return self::tryParse($relation) !== null;
    }

    /** True for multi-level relations like "department.company:name". */
    public function isNested(): bool
    {
        return count($this->segments) > 1;
    }

    /** The first relation in the chain. */
    public function firstSegment(): string
    {
        return $this->segments[0];
    }

    /** The deepest relation in the chain — the one that owns the attribute. */
    public function lastSegment(): string
    {
        return $this->segments[count($this->segments) - 1];
    }

    public function __toString(): string
    {
        return $this->path.':'.$this->attribute;
    }

    private static function build(string $relation): ?self
    {
        $relation = trim($relation);

        if ($relation === '' || ! str_contains($relation, ':')) {
            return null;
        }

        // Limit 2: an attribute may legitimately never contain a colon, but
        // splitting on the FIRST one keeps behaviour predictable either way.
        [$path, $attribute] = explode(':', $relation, 2);

        $path = trim($path);
        $attribute = trim($attribute);

        if ($path === '' || $attribute === '') {
            return null;
        }

        $segments = array_values(array_filter(
            array_map('trim', explode('.', $path)),
            fn ($s) => $s !== ''
        ));

        if ($segments === []) {
            return null;
        }

        // Reject anything that is not a plain identifier chain. These values end
        // up in whereHas()/with() and, historically, in orderByRaw().
        foreach ($segments as $segment) {
            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment)) {
                return null;
            }
        }

        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $attribute)) {
            return null;
        }

        // Progressive paths for eager loading: department, department.company…
        $eagerLoads = [];
        $accumulated = '';

        foreach ($segments as $segment) {
            $accumulated = $accumulated === '' ? $segment : $accumulated.'.'.$segment;
            $eagerLoads[] = $accumulated;
        }

        return new self(implode('.', $segments), $attribute, $segments, $eagerLoads);
    }
}
