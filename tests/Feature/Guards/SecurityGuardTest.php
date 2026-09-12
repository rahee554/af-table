<?php

namespace ArtflowStudio\Table\Tests\Feature\Guards;

use ArtflowStudio\Table\Http\Livewire\AftableComponent;
use ArtflowStudio\Table\Support\ActionNotAllowedException;
use ArtflowStudio\Table\Support\GuardsActions;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use ArtflowStudio\Table\Traits\Advanced\HasApiEndpoint;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Security defects. See AUDIT.md §Security.
 *
 * Findings still OPEN carry #[Group('known-bugs')] on the method and are listed
 * by `composer test:bugs`. Findings already fixed stay here untagged, so they
 * run in the default suite as regression guards.
 */
class SecurityGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    private function table(array $overrides = [])
    {
        return Livewire::test('aftable', array_merge([
            'model' => ColumnSets::MODEL,
            'columns' => ColumnSets::basic(),
        ], $overrides));
    }

    #[Test]
    #[TestDox('SEC-01 (critical): renderRawHtml() is a public Livewire action, so any visitor can execute arbitrary Blade/PHP')]
    public function render_raw_html_is_not_callable_from_the_browser(): void
    {
        $reflection = new \ReflectionMethod(AftableComponent::class, 'renderRawHtml');

        $this->assertFalse(
            $reflection->isPublic(),
            'renderRawHtml() must not be public: Livewire exposes every public method as a '.
            'client-callable action, and this one compiles and evaluates its first argument '.
            'as a Blade template. Mark it protected and expose a narrow view helper instead.'
        );
    }

    #[Test]
    #[TestDox('SEC-01 (critical): proof — Livewire refuses renderRawHtml as a client action')]
    public function livewire_refuses_to_invoke_render_raw_html_from_the_client(): void
    {
        // Before the fix this returned '42-PWN' — i.e. the caller's Blade was
        // compiled and executed on the server. Two independent barriers now stop
        // it: the method is protected, and GuardsActions refuses the call.
        $this->expectException(ActionNotAllowedException::class);

        $this->table()->call('renderRawHtml', '{{ 6*7 }}-{{ strtoupper("pwn") }}', null);
    }

    #[Test]
    #[TestDox('SEC-02: compiled raw templates are written to the world-writable OS temp directory')]
    public function compiled_raw_templates_are_not_written_to_the_os_temp_directory(): void
    {
        foreach (glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'aftable_tpl_*.php') as $stale) {
            @unlink($stale);
        }

        $this->table(['columns' => ColumnSets::withComputed()])->assertOk();

        $this->assertSame(
            [],
            glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'aftable_tpl_*.php'),
            'Compiled templates belong under storage_path(), written atomically. '.
            'sys_get_temp_dir() is shared and world-writable on most hosts, so another '.
            'local user can pre-create the md5-named file that this component then includes.'
        );
    }

    // SEC-03 folded into SEC-02: the cache is a legitimate compiled-view cache;
    // the defect was its location, not its lifetime.

    #[Test]
    #[TestDox('SEC-04: filterColumn is not validated, so any table column can be probed')]
    public function a_completely_undeclared_filter_column_is_rejected(): void
    {
        // `email` is a real DB column that this table neither displays nor
        // declares as a filter. A crafted request (or a hand-edited query
        // string) must not be able to filter on it — that is a blind oracle
        // over columns the operator never exposed.
        $component = Livewire::test('aftable', [
            'model' => ColumnSets::MODEL,
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'first_name', 'label' => 'First Name'],
            ],
            'filters' => ['first_name' => ['type' => 'text']],
        ])
            ->set('filterColumn', 'email')
            ->set('filterValue', 'alice.anderson@artflow.test');

        $this->assertCount(
            5,
            $component->viewData('data')->items(),
            'Filtering on an undeclared column must be a no-op.'
        );
    }

    #[Test]
    #[TestDox('SEC-05 (mitigated): the CLIENT-CALLABLE surface is small, even though the class is not')]
    public function the_client_callable_surface_is_reviewable(): void
    {
        $public = count((new \ReflectionClass(AftableComponent::class))
            ->getMethods(\ReflectionMethod::IS_PUBLIC));

        $callable = count(GuardsActions::allowed());

        // The class still has 400+ public methods - that is STRUCT-06/07, and it
        // cannot be fixed safely until the 27 traits are collapsed. What matters
        // for security is how many of them a BROWSER can reach, and that is
        // gated by GuardsActions rather than by visibility.
        $this->assertLessThan(
            60,
            $callable,
            "{$callable} of {$public} public methods are client-callable."
        );

        $this->assertLessThan(
            $public / 4,
            $callable,
            'The guarded surface should be a small fraction of the class.'
        );
    }

    #[Test]
    #[TestDox('SEC-06 (fixed): the HTTP-client datasource was removed entirely')]
    public function the_http_client_datasource_is_gone(): void
    {
        // HasApiEndpoint carried 857 lines of HTTP-client code that nothing
        // could reach: no mount parameter, no view usage, no documentation. It
        // was pure SSRF surface, so 1.6.0 deletes it rather than guarding it.
        $this->assertFalse(
            trait_exists(HasApiEndpoint::class),
            'HasApiEndpoint should no longer exist.'
        );

        foreach (['setApiEndpoint', 'fetchApiData', 'configureApi'] as $method) {
            $this->assertFalse(
                method_exists(AftableComponent::class, $method),
                "{$method}() should no longer exist on the component."
            );
        }
    }
}
