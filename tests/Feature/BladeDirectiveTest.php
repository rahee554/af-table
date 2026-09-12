<?php

namespace ArtflowStudio\Table\Tests\Feature;

use ArtflowStudio\Table\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * End-to-end coverage of the Blade directives, rendered through a real Blade
 * template rather than through Livewire::test().
 *
 * This is the path that BUG-32 broke: `Livewire::mount()` returns a string in
 * Livewire 4, but every directive called `->html()` on it, so any page using
 * `@AFtable` / `@aftable` fataled with "Call to a member function html() on
 * string". Only `@livewire('aftable', …)` worked, which is why host apps ended
 * up avoiding the directives entirely.
 */
class BladeDirectiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSmallDataset();
    }

    private const MODEL = '\ArtflowStudio\Table\Tests\Models\TestEmployee::class';

    private const COLUMNS = <<<'BLADE'
    [
        ['key' => 'id', 'label' => 'ID'],
        ['key' => 'position', 'label' => 'Position',
         'raw' => '<span class="badge bg-success">{{ $row->position }}</span>'],
    ]
    BLADE;

    /** Render a template and strip Livewire's snapshot/effects attributes. */
    private function render(string $template): string
    {
        return preg_replace('/wire:(snapshot|effects)="[^"]*"/s', '', Blade::render($template));
    }

    public static function directives(): array
    {
        $model = self::MODEL;
        $cols = self::COLUMNS;

        return [
            '@livewire' => ["@livewire('aftable', ['model' => {$model}, 'columns' => {$cols}])"],
            '@AFtable' => ["@AFtable(['model' => {$model}, 'columns' => {$cols}])"],
            '@AFtableTrait' => ["@AFtableTrait(['model' => {$model}, 'columns' => {$cols}])"],
            '@aftable/@endaftable' => ["@aftable(['model' => {$model}, 'columns' => {$cols}])\n@endaftable"],
        ];
    }

    #[Test]
    #[DataProvider('directives')]
    public function every_directive_renders_the_table(string $template): void
    {
        $html = $this->render($template);

        // The configured columns are `id` and `position`.
        $this->assertStringContainsString('Engineer', $html);
        $this->assertStringContainsString('wire:id', $html);
        $this->assertStringContainsString('<table', $html);
    }

    #[Test]
    #[DataProvider('directives')]
    public function every_directive_renders_raw_html_as_html(string $template): void
    {
        $html = $this->render($template);

        // Five employees → five badge spans, rendered as live markup.
        $this->assertSame(
            5,
            preg_match_all('#<span class="badge bg-success">#', $html),
            'Raw column HTML must reach the browser as markup, not as escaped text.'
        );
        $this->assertStringContainsString('<span class="badge bg-success">Engineer</span>', $html);
    }

    #[Test]
    #[DataProvider('directives')]
    public function no_directive_leaks_uncompiled_php_into_the_output(string $template): void
    {
        $html = $this->render($template);

        $this->assertStringNotContainsString('<?php', $html);
        $this->assertStringNotContainsString('{{ $row->', $html);
    }

    #[Test]
    #[DataProvider('directives')]
    public function no_directive_html_escapes_a_raw_cell(string $template): void
    {
        $html = $this->render($template);

        $this->assertDoesNotMatchRegularExpression(
            '#<td[^>]*>\s*&lt;span#',
            $html,
            'A raw cell was HTML-escaped instead of being emitted as markup.'
        );
    }

    #[Test]
    public function raw_columns_work_whether_or_not_the_parent_view_pre_compiles_the_braces(): void
    {
        $model = self::MODEL;

        // `{{ }}` — the parent Blade file pre-compiles this into an echo of
        // e($row->position) before the component ever sees the string.
        $precompiled = "@AFtable(['model' => {$model}, 'columns' => [
            ['key' => 'position', 'label' => 'Position',
             'raw' => '<b>{{ \$row->position }}</b>'],
        ]])";

        // `@{{ }}` — the parent leaves it alone and the component compiles it.
        $verbatim = "@AFtable(['model' => {$model}, 'columns' => [
            ['key' => 'position', 'label' => 'Position',
             'raw' => '<b>@{{ \$row->position }}</b>'],
        ]])";

        foreach ([$precompiled, $verbatim] as $template) {
            $this->assertStringContainsString('<b>Engineer</b>', $this->render($template));
        }
    }

    #[Test]
    public function the_paired_directive_uses_its_body_as_the_table_shell(): void
    {
        $model = self::MODEL;
        $cols = self::COLUMNS;

        $html = $this->render(
            "@aftable(['model' => {$model}, 'columns' => {$cols}, 'tableId' => 'themed'])\n".
            "<table class=\"table table-dark my-theme\"></table>\n".
            '@endaftable'
        );

        $this->assertStringContainsString('table table-dark my-theme', $html);
        $this->assertStringContainsString('id="themed"', $html);
        // The body must be consumed as the shell, not echoed as a stray empty table.
        $this->assertSame(1, substr_count($html, 'my-theme'));
    }

    #[Test]
    public function the_paired_directive_without_a_body_behaves_like_the_plain_one(): void
    {
        $model = self::MODEL;
        $cols = self::COLUMNS;

        $paired = $this->render("@aftable(['model' => {$model}, 'columns' => {$cols}])\n@endaftable");
        $plain = $this->render("@AFtable(['model' => {$model}, 'columns' => {$cols}])");

        $normalise = fn ($h) => preg_replace('/wire:id="[^"]*"|lw-\d+-\d+|[a-zA-Z0-9]{20}/', '', $h);

        $this->assertSame($normalise($plain), $normalise($paired));
    }

    #[Test]
    public function two_tables_on_one_page_do_not_collide(): void
    {
        $model = self::MODEL;
        $cols = self::COLUMNS;

        $html = $this->render(
            "@AFtable(['model' => {$model}, 'columns' => {$cols}, 'tableId' => 'first'])".
            "@AFtable(['model' => {$model}, 'columns' => {$cols}, 'tableId' => 'second'])"
        );

        $this->assertStringContainsString('id="first"', $html);
        $this->assertStringContainsString('id="second"', $html);
        $this->assertSame(10, preg_match_all('#<span class="badge bg-success">#', $html));
    }
}
