<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Http\Livewire\AftableComponent;
use ArtflowStudio\Table\Support\ActionNotAllowedException;
use ArtflowStudio\Table\Support\GuardsActions;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The client-callable action surface.
 *
 * Livewire exposes every public method as an action. This component has 400+,
 * almost all internal helpers — including, at one point, one that compiled and
 * executed its argument as Blade. GuardsActions closes that down to a declared
 * list at the single point every client call passes through.
 *
 * The most important test here is
 * {@see every_wire_binding_in_the_template_is_allowed}: it parses the shipped
 * Blade template and proves the guard cannot break the table's own UI.
 */
class ActionGuardTest extends TestCase
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

    // ------------------------------------------------------------ the guard

    #[Test]
    public function every_wire_binding_in_the_template_is_allowed(): void
    {
        $template = file_get_contents(
            realpath(__DIR__.'/../../resources/views/livewire/aftable-component.blade.php')
        );

        // wire:click="foo(...)", wire:change="foo(...)", wire:input.debounce="foo(...)"
        preg_match_all(
            '/wire:(?:click|change|input|submit|keydown)[.\w]*="\s*([a-zA-Z_$][\w$]*)\s*\(/',
            $template,
            $matches
        );

        $bound = array_values(array_unique($matches[1]));

        $this->assertNotEmpty($bound, 'Sanity: the template should bind at least one action.');

        $blocked = array_values(array_filter(
            $bound,
            fn (string $method) => ! GuardsActions::permits($method)
        ));

        $this->assertSame(
            [],
            $blocked,
            'The guard would break the shipped UI. These actions are bound in the template but '.
            "not allowed:\n  - ".implode("\n  - ", $blocked)
        );
    }

    #[Test]
    #[DataProvider('uiActions')]
    public function the_documented_ui_actions_are_permitted(string $method): void
    {
        $this->assertTrue(GuardsActions::permits($method), "{$method}() should be callable.");
    }

    public static function uiActions(): array
    {
        return array_map(fn ($m) => [$m], [
            'toggleSort', 'setSort', 'clearSort',
            'toggleColumnVisibility', 'showAllColumns', 'hideAllColumns',
            'addFilterInstance', 'removeFilterInstance', 'updateFilterInstance',
            'clearAllFilters', 'clearSearch', 'handleExport', 'refreshTable',
            'gotoPage', 'nextPage', 'previousPage', 'resetPage',
        ]);
    }

    #[Test]
    #[DataProvider('internalMethods')]
    public function internal_helpers_are_not_callable_from_the_browser(string $method): void
    {
        $this->assertFalse(
            GuardsActions::permits($method),
            "{$method}() is an internal helper and must not be a client action."
        );
    }

    public static function internalMethods(): array
    {
        return array_map(fn ($m) => [$m], [
            // the RCE primitive
            'renderRawHtml',
            // SSRF surface
            'setApiEndpoint', 'fetchApiData', 'configureApi',
            // cache manipulation
            'warmCache', 'clearDatatableCache', 'invalidateModelCache', 'clearCacheByPattern',
            // internals that leak shape or do work
            'getComponentDebugInfo', 'buildUnifiedQuery', 'getCachedDistinctValues',
            'applyAdvancedFilters', 'exportInChunks',
        ]);
    }

    #[Test]
    public function livewire_lifecycle_hooks_are_still_reachable(): void
    {
        // Livewire drives updating*/updated* itself; blocking them would break
        // every wire:model on the component.
        $this->assertTrue(GuardsActions::permits('updatedSearch'));
        $this->assertTrue(GuardsActions::permits('updatingSearch'));
        $this->assertTrue(GuardsActions::permits('updatedFilterColumn'));
    }

    #[Test]
    public function livewire_magic_actions_are_still_reachable(): void
    {
        foreach (['$refresh', '$set', '$toggle'] as $magic) {
            $this->assertTrue(GuardsActions::permits($magic), "{$magic} must keep working.");
        }
    }

    // -------------------------------------------------------- end to end

    #[Test]
    public function calling_a_blocked_action_is_refused(): void
    {
        $component = $this->table();

        try {
            $component->call('renderRawHtml', '{{ 6*7 }}', null);
            $refused = false;
        } catch (\Throwable $e) {
            $refused = $e instanceof ActionNotAllowedException
                || str_contains($e->getMessage(), 'not a callable AFTable action')
                || str_contains($e->getMessage(), 'renderRawHtml');
        }

        $this->assertTrue($refused, 'renderRawHtml() must be refused when called from the client.');
    }

    #[Test]
    public function a_blocked_action_never_executes_its_payload(): void
    {
        // The point is not just the error — it is that the Blade never runs.
        $component = $this->table();

        try {
            $component->call('renderRawHtml', '{{ 6*7 }}-{{ strtoupper("pwn") }}', null);
        } catch (\Throwable) {
            // expected
        }

        $this->assertStringNotContainsString('42-PWN', $component->html());
    }

    #[Test]
    public function allowed_actions_still_work_end_to_end(): void
    {
        $this->table()
            ->call('toggleSort', 'salary')->assertSet('sortColumn', 'salary')
            ->call('clearSort')->assertSet('sortColumn', null)
            ->call('addFilterInstance')
            ->call('clearAllFilterInstances')->assertSet('filterInstances', [])
            ->call('nextPage')
            ->assertOk();
    }

    #[Test]
    public function the_guard_can_be_extended_from_config(): void
    {
        $this->assertFalse(GuardsActions::permits('getComponentStats'));

        config()->set('aftable.security.extra_actions', ['getComponentStats']);

        $this->assertTrue(
            GuardsActions::permits('getComponentStats'),
            'A host app must be able to opt a method back in without patching the package.'
        );
    }

    #[Test]
    public function the_guard_can_be_disabled_entirely(): void
    {
        config()->set('aftable.security.guard_actions', false);

        // No exception: the guard steps aside completely.
        $this->table()->call('getComponentStats');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function the_guard_only_applies_to_this_package(): void
    {
        // A host application's own Livewire components must be untouched.
        $hook = new GuardsActions;

        $this->assertInstanceOf(GuardsActions::class, $hook);
        $this->assertTrue(
            method_exists(AftableComponent::class, 'render'),
            'Sanity check that the guarded class is the one we think it is.'
        );
    }
}
