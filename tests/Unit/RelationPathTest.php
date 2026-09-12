<?php

namespace ArtflowStudio\Table\Tests\Unit;

use ArtflowStudio\Table\Support\RelationPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The single parser for `'relation' => 'a.b:column'`.
 *
 * Replaces 40+ scattered `explode(':')` call sites, most of which were
 * unguarded list-destructuring that emitted "Undefined array key 1" on any
 * malformed value. See AUDIT.md §6.
 */
class RelationPathTest extends TestCase
{
    #[Test]
    public function it_parses_a_simple_relation(): void
    {
        $path = RelationPath::parse('department:name');

        $this->assertSame('department', $path->path);
        $this->assertSame('name', $path->attribute);
        $this->assertSame(['department'], $path->segments);
        $this->assertSame(['department'], $path->eagerLoads);
        $this->assertFalse($path->isNested());
    }

    #[Test]
    public function it_parses_a_nested_relation_and_builds_progressive_eager_loads(): void
    {
        $path = RelationPath::parse('department.company.owner:email');

        $this->assertSame('department.company.owner', $path->path);
        $this->assertSame('email', $path->attribute);
        $this->assertSame(['department', 'company', 'owner'], $path->segments);
        $this->assertSame(
            ['department', 'department.company', 'department.company.owner'],
            $path->eagerLoads,
            'Eager loading needs every intermediate path, not just the deepest one.'
        );
        $this->assertTrue($path->isNested());
        $this->assertSame('department', $path->firstSegment());
        $this->assertSame('owner', $path->lastSegment());
    }

    #[Test]
    public function it_tolerates_surrounding_whitespace(): void
    {
        $path = RelationPath::parse('  department . company : name  ');

        $this->assertSame('department.company', $path->path);
        $this->assertSame('name', $path->attribute);
    }

    #[Test]
    public function it_rejects_a_second_colon_rather_than_guessing(): void
    {
        // explode(':', $v, 2) splits on the first colon, but the resulting
        // attribute must still be a plain identifier — these values reach
        // whereHas()/orderBy(), so anything else is refused outright.
        $this->assertNull(RelationPath::tryParse('department:name:extra'));
    }

    #[Test]
    #[DataProvider('malformed')]
    public function it_returns_null_for_malformed_input(?string $input, string $why): void
    {
        $this->assertNull(RelationPath::tryParse($input), $why);
        $this->assertFalse(RelationPath::isValid($input), $why);
    }

    public static function malformed(): array
    {
        return [
            'null' => [null, 'null is not a relation'],
            'empty' => ['', 'empty string'],
            'no colon' => ['department', 'the colon is what makes it a relation spec'],
            'no path' => [':name', 'missing relation'],
            'no attribute' => ['department:', 'missing attribute'],
            'only a colon' => [':', 'nothing either side'],
            'sql in path' => ['dept; DROP TABLE users:name', 'must reject non-identifiers'],
            'sql in attribute' => ['department:name); DROP TABLE users; --', 'must reject non-identifiers'],
            'space in path' => ['depart ment:name', 'identifiers cannot contain spaces'],
            'leading digit' => ['1department:name', 'identifiers cannot start with a digit'],
            'backtick' => ['`department`:name', 'must reject quoting characters'],
        ];
    }

    #[Test]
    public function parse_throws_on_malformed_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid relation [nope]');

        RelationPath::parse('nope');
    }

    #[Test]
    public function it_round_trips_through_to_string(): void
    {
        $this->assertSame('a.b:c', (string) RelationPath::parse('a.b:c'));
    }

    #[Test]
    public function repeated_parses_of_the_same_string_return_the_same_instance(): void
    {
        // Relation strings are re-parsed for every row of every render, so the
        // memo matters for large tables.
        $this->assertSame(
            RelationPath::tryParse('department:name'),
            RelationPath::tryParse('department:name')
        );
    }
}
