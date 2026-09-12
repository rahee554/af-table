<?php

namespace ArtflowStudio\Table\Tests\Feature\Guards;

use ArtflowStudio\Table\Http\Livewire\AftableComponent;
use ArtflowStudio\Table\TableServiceProvider;
use ArtflowStudio\Table\Tests\Support\SourceScanner;
use ArtflowStudio\Table\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Structural guards. Each of these was a real finding in the 1.5.6 audit; they
 * stay green now and exist to keep it that way.
 *
 * Every finding here is FIXED. The tests stay as permanent guards.
 */
class CodeHealthGuardTest extends TestCase
{
    private function packageRoot(): string
    {
        return realpath(__DIR__.'/../../..');
    }

    #[Test]
    #[TestDox('STRUCT-01: dead backup/old source files are shipped inside src/')]
    public function no_backup_or_old_files_are_shipped(): void
    {
        $dead = [];
        foreach (['*.old', '*.backup', '*.bak', '*.orig'] as $pattern) {
            foreach ($this->globRecursive($this->packageRoot().'/src', $pattern) as $file) {
                $dead[] = str_replace($this->packageRoot().DIRECTORY_SEPARATOR, '', $file);
            }
        }

        $this->assertSame([], $dead, "Dead files under src/:\n  - ".implode("\n  - ", $dead));
    }

    #[Test]
    #[TestDox('STRUCT-02: src/resources duplicates the real resources/ directory')]
    public function views_and_assets_live_in_exactly_one_place(): void
    {
        $this->assertDirectoryDoesNotExist(
            $this->packageRoot().'/src/resources',
            'src/resources/ is a stale copy of resources/. Only resources/ is registered with '.
            'loadViewsFrom(), yet the service provider publishes src/resources/views — so '.
            '`vendor:publish --tag=views` hands the user the WRONG (unmaintained) templates.'
        );
    }

    #[Test]
    #[TestDox('STRUCT-10: unreachable Blade templates are shipped and published')]
    public function every_shipped_view_is_reachable(): void
    {
        // `publishes()` copies the whole resources/views tree into the host
        // application, so an unreachable template is not merely dead weight —
        // it lands in someone else's repository. The two legacy component
        // forks were deleted in 1.6.0 but their 1,267-line templates stayed,
        // alongside a stray PDF layout still carrying an absolute path from a
        // completely different project. See AUDIT.md BUG-50.
        $root = $this->packageRoot();
        $viewRoot = $root.'/resources/views';

        // Anything referenced by view()/@extends/@include/<x-...> or rendered
        // as a Livewire component template.
        $haystack = '';
        foreach (['/src', '/routes', '/resources/views', '/config'] as $dir) {
            foreach ($this->globRecursive($root.$dir, '*.php') as $file) {
                $haystack .= file_get_contents($file);
            }
        }

        $orphans = [];

        foreach ($this->globRecursive($viewRoot, '*.blade.php') as $file) {
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file, strlen($viewRoot) + 1));
            $name = str_replace('/', '.', substr($relative, 0, -strlen('.blade.php')));

            // components/foo -> <x-artflow-table::foo>
            $component = str_starts_with($name, 'components.')
                ? substr($name, strlen('components.'))
                : null;

            $referenced = str_contains($haystack, $name)
                || str_contains($haystack, str_replace('.', '/', $name))
                || ($component !== null && str_contains($haystack, 'artflow-table::'.$component));

            if (! $referenced) {
                $orphans[] = 'resources/views/'.$relative;
            }
        }

        sort($orphans);

        $this->assertSame(
            [],
            $orphans,
            'These templates are shipped and published but nothing renders them:
  - '.
            implode('
  - ', $orphans)
        );
    }

    #[Test]
    #[TestDox('STRUCT-03: src/routes/web.php is never loaded by the service provider')]
    public function no_orphaned_route_file_is_shipped(): void
    {
        $this->assertFileDoesNotExist(
            $this->packageRoot().'/src/routes/web.php',
            'Only routes/test.php is registered. src/routes/web.php declares ~25 routes and a '.
            'TestController with matching methods, none of which is reachable.'
        );
    }

    #[Test]
    #[TestDox('STRUCT-04: loose procedural scripts are shipped under src/Testing/')]
    public function no_loose_scripts_are_shipped_in_src(): void
    {
        $loose = array_map(
            fn ($f) => basename($f),
            glob($this->packageRoot().'/src/Testing/test_*.php')
        );

        $this->assertSame(
            [],
            $loose,
            'Non-PSR-4 procedural scripts under src/Testing/: '.implode(', ', $loose).
            '. These are not autoloadable, are never executed and should live in tests/.'
        );
    }

    #[Test]
    #[TestDox('STRUCT-05: three near-duplicate datatable components are registered')]
    public function only_one_datatable_component_is_maintained(): void
    {
        $components = array_filter([
            'Datatable.php',
            'DatatableTrait.php',
            'AftableComponent.php',
        ], fn ($f) => file_exists($this->packageRoot().'/src/Http/Livewire/'.$f));

        $this->assertCount(
            1,
            $components,
            'Datatable (1,663 lines), DatatableTrait (1,861) and AftableComponent (2,003) are '.
            'three forks of the same feature set, all registered as Livewire components and all '.
            'carrying their own copy of the relation/JSON/sort logic. Fixes applied to one do '.
            'not reach the others.'
        );
    }

    #[Test]
    #[TestDox('STRUCT-11: a concern declares a method the component also declares, and loses silently')]
    public function no_concern_declares_a_method_the_component_also_declares(): void
    {
        // PHP resolves trait/class collisions in favour of the class, with no
        // warning and no signature check. `exportToCsv()` existed on both with
        // incompatible signatures, so the trait copy — and the whole export
        // branch that called it — was unreachable code that still looked live.
        // See AUDIT.md BUG-51.
        $reflection = new \ReflectionClass(AftableComponent::class);

        $componentMethods = [];
        foreach ($this->methodsDeclaredIn($reflection->getFileName()) as $name) {
            $componentMethods[$name] = true;
        }

        $shadowed = [];

        foreach ($reflection->getTraits() as $trait) {
            // Livewire's own traits are not ours to police.
            if (! str_starts_with($trait->getName(), 'ArtflowStudio\\')) {
                continue;
            }

            foreach ($this->methodsDeclaredIn($trait->getFileName()) as $name) {
                if (isset($componentMethods[$name])) {
                    $shadowed[] = $trait->getShortName().'::'.$name.'()';
                }
            }
        }

        sort($shadowed);

        $this->assertSame(
            [],
            $shadowed,
            "These concern methods are shadowed by the component and never run:\n  - ".
            implode("\n  - ", $shadowed)
        );
    }

    /**
     * Method names declared directly in one file, ignoring abstracts.
     *
     * Reflection cannot help here: PHP flattens traits, so every method
     * reports the using class as its declaring class.
     *
     * @return array<int, string>
     */
    private function methodsDeclaredIn(string $file): array
    {
        $source = (string) file_get_contents($file);

        preg_match_all(
            '/^\s*(?:(?:final|abstract)\s+)?(?:public|protected|private)?\s*(?:static\s+)?function\s+(\w+)\s*\(/m',
            $source,
            $matches
        );

        $abstract = [];
        preg_match_all('/^\s*abstract\s+[^;]*function\s+(\w+)\s*\(/m', $source, $abstractMatches);
        foreach ($abstractMatches[1] as $name) {
            $abstract[$name] = true;
        }

        return array_values(array_filter(
            array_unique($matches[1]),
            fn (string $name) => ! isset($abstract[$name])
        ));
    }

    #[Test]
    #[TestDox('STRUCT-06 (fixed): the component composes a reviewable number of concerns, with no conflict resolution')]
    public function the_component_composes_a_reviewable_number_of_traits(): void
    {
        $traits = (new \ReflectionClass(AftableComponent::class))->getTraitNames();

        // 11 concerns + Livewire's WithPagination. Was 27 traits.
        $this->assertLessThanOrEqual(
            14,
            count($traits),
            count($traits).' traits are composed into one class.'
        );

        // The real property: no `insteadof` and no `as` aliasing. Both exist only
        // to paper over two traits declaring the same method, and the compiler
        // cannot tell you when the two implementations disagree — which is
        // exactly how BUG-02 shipped.
        $source = SourceScanner::executableSourceOf(AftableComponent::class);

        $this->assertStringNotContainsString(
            'insteadof',
            $source,
            'A conflict-resolution clause means two concerns declare the same method. '.
            'Rename one instead.'
        );
    }

    #[Test]
    #[TestDox('STRUCT-07: methods are defined more than once across the composed traits')]
    public function no_method_name_is_defined_by_more_than_one_trait(): void
    {
        $reflection = new \ReflectionClass(AftableComponent::class);

        $byName = [];
        foreach ($reflection->getTraits() as $trait) {
            foreach ($trait->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() === $trait->getName()) {
                    $byName[$method->getName()][] = $trait->getShortName();
                }
            }
        }

        $duplicated = array_filter($byName, fn ($traits) => count($traits) > 1);
        ksort($duplicated);

        $lines = array_map(
            fn ($name, $traits) => "  - {$name}(): ".implode(', ', $traits),
            array_keys($duplicated),
            $duplicated
        );

        $this->assertSame([], $duplicated, "Duplicated trait methods:\n".implode("\n", $lines));
    }

    #[Test]
    #[TestDox('STRUCT-08: the package emits PHP 8.4 implicit-nullable deprecations')]
    public function the_source_is_free_of_php_84_deprecations(): void
    {
        $offenders = [];

        foreach ($this->globRecursive($this->packageRoot().'/src', '*.php') as $file) {
            $source = file_get_contents($file);
            // `Type $param = null` without a `?` prefix / union with null.
            if (preg_match_all(
                '/function\s+\w+\s*\([^)]*?(?<!\?)\b(?!null\b)(string|int|float|bool|array|callable|iterable|object|self|[A-Z]\w*)\s+\$\w+\s*=\s*null/i',
                $source,
                $m
            )) {
                $offenders[] = basename($file).' ('.count($m[0]).')';
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Implicitly-nullable parameters (deprecated in PHP 8.4):\n  - ".implode("\n  - ", $offenders)
        );
    }

    #[Test]
    #[TestDox('STRUCT-09: routes/test.php is registered unconditionally, including in production')]
    public function demo_routes_are_not_registered_in_production(): void
    {
        $source = file_get_contents(
            (new \ReflectionClass(TableServiceProvider::class))->getFileName()
        );

        $guarded = str_contains($source, 'environment(')
            || str_contains($source, 'isProduction')
            || str_contains($source, "config('aftable");

        $this->assertTrue(
            $guarded,
            'loadRoutesFrom(routes/test.php) runs on every boot, so /aftable/test, /aftable/docs '.
            'and /aftable/generator are publicly reachable on production deployments and the '.
            'test page queries test_* tables that do not exist there (500).'
        );
    }

    /** @return list<string> */
    private function globRecursive(string $dir, string $pattern): array
    {
        $found = glob($dir.DIRECTORY_SEPARATOR.$pattern) ?: [];

        foreach (glob($dir.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [] as $sub) {
            $found = array_merge($found, $this->globRecursive($sub, $pattern));
        }

        return $found;
    }
}
