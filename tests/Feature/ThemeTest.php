<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Support\Theme;
use ArtflowStudio\Table\Tests\Support\ColumnSets;
use ArtflowStudio\Table\Tests\TestCase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

/**
 * Theming: slot resolution, presets, per-table overrides, and the host
 * application's own pagination markup.
 *
 * Before 1.6.0 the markup hard-coded Bootstrap classes with no way out short of
 * republishing the view.
 */
class ThemeTest extends TestCase
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

    // ---------------------------------------------------------- resolution

    #[Test]
    public function it_defaults_to_bootstrap(): void
    {
        $this->assertSame('table table-bordered p-2', Theme::resolve()['table']);
    }

    #[Test]
    public function a_named_preset_replaces_the_whole_map(): void
    {
        $this->assertStringContainsString('min-w-full', Theme::resolve('tailwind')['table']);
        $this->assertSame('aft-table', Theme::resolve('none')['table']);
    }

    #[Test]
    public function per_slot_overrides_win_over_the_preset(): void
    {
        $resolved = Theme::resolve('bootstrap', ['table' => 'my-own-table']);

        $this->assertSame('my-own-table', $resolved['table']);
        // Untouched slots keep the preset value.
        $this->assertSame('form-control form-control-sm', $resolved['input']);
    }

    #[Test]
    public function app_wide_overrides_apply_to_every_table(): void
    {
        config()->set('aftable.theme.overrides', ['th' => 'app-wide-header']);

        $this->assertSame('app-wide-header', Theme::resolve()['th']);
    }

    #[Test]
    public function a_per_table_override_beats_an_app_wide_one(): void
    {
        config()->set('aftable.theme.overrides', ['th' => 'app-wide-header']);

        $this->assertSame('this-table-only', Theme::resolve(null, ['th' => 'this-table-only'])['th']);
    }

    #[Test]
    public function the_app_wide_preset_comes_from_config(): void
    {
        config()->set('aftable.theme.preset', 'none');

        $this->assertSame('aft-table', Theme::resolve()['table']);
    }

    #[Test]
    public function a_custom_preset_can_be_registered(): void
    {
        Theme::register('acme', ['table' => 'acme-table'], extends: 'none');

        $resolved = Theme::resolve('acme');

        $this->assertSame('acme-table', $resolved['table']);
        $this->assertSame('aft-th', $resolved['th'], 'extends: should inherit the rest.');
        $this->assertContains('acme', Theme::available());
    }

    #[Test]
    public function an_unknown_preset_falls_back_to_bootstrap_rather_than_breaking(): void
    {
        $this->assertSame('table table-bordered p-2', Theme::resolve('does-not-exist')['table']);
    }

    #[Test]
    public function the_ui_flow_preset_is_built_in(): void
    {
        $resolved = Theme::resolve('ui-flow');

        $this->assertSame('table table-hover table-borderless align-middle mb-0', $resolved['table']);
        $this->assertSame('btn btn-sm btn-soft-primary', $resolved['button.primary']);
        // Slots ui-flow doesn't restyle fall back to the same value as bootstrap.
        $this->assertSame('form-control form-control-sm', $resolved['input']);
        $this->assertContains('ui-flow', Theme::available());
    }

    // ------------------------------------------------------------ rendering

    #[Test]
    public function the_rendered_table_uses_the_overridden_classes(): void
    {
        $this->table(['classes' => ['table' => 'my-grid', 'th' => 'my-head']])
            ->assertSeeHtml('class="my-grid"')
            // Not an exact `class="my-head"` match: sortable headers append
            // `aftable-th--sortable` alongside whatever the 'th' slot resolves
            // to, the same way a column's own th_class already does.
            ->assertSeeHtml('my-head');
    }

    #[Test]
    public function the_none_preset_emits_no_framework_classes(): void
    {
        $html = $this->table(['theme' => 'none', 'assets' => false])->html();

        $this->assertStringContainsString('aft-table', $html);

        foreach (['table-bordered', 'form-control', 'btn btn-sm', 'fw-bold bg-body-secondary'] as $bootstrapism) {
            $this->assertStringNotContainsString(
                $bootstrapism,
                $html,
                "theme => 'none' must not emit [{$bootstrapism}]."
            );
        }
    }

    #[Test]
    public function per_column_classes_are_appended_to_the_slot(): void
    {
        $html = $this->table([
            'classes' => ['td' => 'slot-cell'],
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'salary', 'label' => 'Salary', 'td_class' => 'text-end'],
            ],
        ])->html();

        $this->assertStringContainsString('slot-cell text-end', $html);
    }

    // ----------------------------------------------------------- pagination

    #[Test]
    public function the_pagination_view_is_a_slot(): void
    {
        $this->assertSame(
            'artflow-table::livewire.pagination',
            Theme::resolve('bootstrap')['pagination']
        );
        $this->assertSame('pagination::tailwind', Theme::resolve('tailwind')['pagination']);
        $this->assertSame('artflow-table::livewire.pagination-none', Theme::resolve('none')['pagination']);
    }

    #[Test]
    public function a_host_application_can_supply_its_own_paginator(): void
    {
        // Laravel's own Bootstrap-5 paginator, rather than the package's.
        $html = $this->table([
            'classes' => ['pagination' => 'pagination::bootstrap-5'],
            'records' => 2,
        ])->html();

        // Laravel's paginator renders `Showing … results`; ours does too, so
        // assert on something only Laravel's emits.
        $this->assertStringContainsString('pagination', $html);
        $this->assertStringNotContainsString('wire:click.prevent.preserve-scroll', $html);
    }

    #[Test]
    public function the_tailwind_preset_renders_laravels_tailwind_paginator(): void
    {
        $this->table(['theme' => 'tailwind', 'records' => 2])->assertOk();
    }
}
