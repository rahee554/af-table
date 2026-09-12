# Contributing

Thanks for considering it. This guide is short on ceremony and specific about
the few things that actually matter here.

---

## Getting set up

```bash
git clone https://github.com/rahee554/af-table.git
cd af-table
composer install
composer test
```

You need **PHP 8.2+**. Everything else comes from `composer install` — the suite
runs on Orchestra Testbench against SQLite in memory, so there is no database to
configure and no application to boot.

`composer test` should be green before you change anything. If it is not, say so
in your issue; that is a bug on our side.

### Seeing it in a real application

The package ships a test bench. From an application that requires the package:

```bash
php artisan af-table:migrate --seed --scale=0.05
```

Then open `/aftable/test`. Eleven sections, every feature rendered against real
data, with a diagnostics strip showing query count, render time and the resolved
cache backend. If you are changing behaviour, this is where you look at it.

`--scale=1` seeds the full ~118,600-row performance fixture. `0.05` is enough
for everything except the scale section.

---

## What we're targeting

**Livewire 4.0+ and Laravel 12.** Not a floor we are reluctant about — the
package uses islands, `#[Computed]`, `@assets`/`@script`, the `commit` hook and
component hooks. Please do not add Livewire 3 compatibility shims; if you need
3.x, 1.5.x is there.

PHP 8.2 is the floor. Use what the language gives you: constructor promotion,
readonly, enums, `match`, first-class callables, named arguments.

---

## The rules that are actually enforced

These are tests, not preferences. They fail the build.

### One method name, one home

No two concerns may declare the same method. No `insteadof`. No `as` aliasing.

This is not stylistic. Until 1.6.0 the component composed 27 traits with 40+
conflict-resolution clauses and 14 duplicated method names — and PHP cannot warn
you when two implementations of the same name disagree. One pair genuinely did:
two traits declared `getNestedValue()` with the arguments in opposite orders,
the `insteadof` picked the wrong one, and **every JSON column fataled**.

If two concerns want the same name, one of them is named wrong.

> `CodeHealthGuardTest::no_method_name_is_defined_by_more_than_one_trait`

### Every `$this->method()` must resolve

A static check walks every call site in the component and its concerns and
resolves it against the methods that exist. Twelve methods were once called and
defined nowhere; eight of them were guaranteed 500s on user-reachable paths.

> `UndefinedMethodGuardTest::every_this_call_resolves_to_a_real_method`

### Query count must not grow with row count

Not "few queries" — *the same number*. A budget rots as the package grows; a
growth assertion cannot. Tests render the same table at 5 rows and at 40 and
require an identical count, with `Model::preventLazyLoading()` on.

> `NPlusOneTest`

If you add a feature that touches the query, add a case there.

### The action guard must not break the UI

Livewire exposes every public method as an action a visitor can invoke with
arbitrary arguments. `Support\GuardsActions` restricts that to a declared list.

A test parses the shipped Blade template and asserts every `wire:click` /
`wire:change` target is on the list. **If you add a new UI action, add it to
`GuardsActions::ALLOWED`** or that test tells you.

> `ActionGuardTest::every_wire_binding_in_the_template_is_allowed`

### No hard-coded framework classes in the markup

Every styleable element goes through a theme slot:

```blade
{{-- yes --}}
<td class="{{ $cls('td', $column['td_class'] ?? '') }}">

{{-- no --}}
<td class="px-3 py-2 text-sm">
```

Adding an element means adding a slot to all three presets in `Support\Theme`
(`BOOTSTRAP`, `TAILWIND`, `NONE`). See `docs/THEMING.md`.

> `ThemeTest::the_none_preset_emits_no_framework_classes`

### PHP 8.4 clean

No implicit-nullable parameters (`string $x = null` → `?string $x = null`). The
package loads clean under `error_reporting(E_ALL)`.

> `CodeHealthGuardTest::the_source_is_free_of_php_84_deprecations`

---

## Layout

```
src/
├── Concerns/          the 11 traits the component composes
├── Exports/           export writers
├── Http/Livewire/     AftableComponent — the only component
├── Support/           standalone, unit-testable helpers
└── TableServiceProvider.php
```

**`Support/` is where new logic should go.** A class there has no Livewire
dependency, is unit-testable in isolation, and can be reused. `Concerns/` is for
behaviour that genuinely needs the component's state.

Good examples to copy: `Support\RelationPath` (one parser replacing 40+
`explode(':')` sites), `Support\CacheStore` (Redis detection with graceful
fallback), `Support\Theme`.

---

## Writing tests

Every change needs one. The suite is the reason this package can be refactored
at all.

- **`tests/Feature/`** — behaviour, driven through `Livewire::test()`.
- **`tests/Feature/Guards/`** — invariants that must never regress. Each was a
  real defect once; the test is there so it stays dead.
- **`tests/Unit/`** — `Support/` classes in isolation.
- **`tests/Support/`** — fixtures. `DatasetSeeder` is deliberately tiny (1
  company, 2 departments, 5 employees) so assertions can name exact rows.

Two habits that make a real difference here:

**Assert the mechanism, not the symptom.** A failure message should say *why*:

```php
$this->assertSame(1, $component->viewData('data')->currentPage(),
    'updatedSearch() guards with `if ($value === $this->search) return;`. Livewire '.
    'assigns the property BEFORE the updated hook, so the guard is always true and '.
    'the whole body never runs.');
```

**Prefer a property over a number.** `assertSame($small, $large)` on query
counts survives refactoring; `assertLessThan(10, $queries)` does not.

---

## Reporting a bug

Include the versions (PHP, Laravel, Livewire), the column/filter config that
triggers it, and what you expected. A failing test is worth ten paragraphs — and
`tests/Support/ColumnSets.php` probably already has a fixture close to your case.

**Security issues: email <support@artflowstudio.com> first.** Please do not open
a public issue for anything that looks exploitable. See `docs/SECURITY.md`.

---

## Pull requests

- Branch from `main`.
- `composer test` green.
- A test for the change.
- Changelog entry under `## [Unreleased]` in `CHANGELOG.md`.
- Behaviour change → update the relevant file in `docs/`. Five files, all short;
  keep them that way. The package previously had 14 documentation files
  totalling 6,823 lines, several of which documented features that did not work.
- Breaking change → say so explicitly, and add a note to `UPGRADING.md`.

Small, focused PRs get reviewed faster. If you are planning something large —
particularly anything touching the query builder or the concern layout — open an
issue first so we can agree the shape.

### Commit messages

Plain and specific. `Fix ambiguous column error when sorting by a relation`
beats `fix bug`. Reference an issue where there is one.

---

## Things we will probably say no to

- Livewire 3 compatibility.
- A new trait that duplicates a method name that already exists.
- Hard-coded CSS classes in the component markup.
- A feature with no test.
- Re-adding a datasource abstraction (HTTP, JSON file). Both existed, neither
  was ever reachable, and one was pure SSRF surface. If you need a non-Eloquent
  source today, pass a Collection via `data`.

---

## Roadmap

Where help is most useful, roughly in order:

1. **Livewire 4 single-file components** — co-locating class and template is
   exactly what would have prevented the sort-arrow bug, where the template read
   `$sortBy` and the code wrote `$sortColumn`. The 800-line template wants
   splitting into toolbar / filters / header / body / footer sub-components with
   islands. This is the largest open item and the one that most needs a design
   discussion before code.
2. **Generator coverage** — `Support\SchemaIntrospector` now reads models,
   columns, casts and relations, but the suggestion rules are deliberately
   conservative. Better type inference (enums, `decimal:2` precision, morph
   relations) is a self-contained place to start. Everything it returns is
   security-sensitive, so read `docs/SECURITY.md` § Schema introspection first.
3. **Theme presets** — `bootstrap`, `tailwind` and `none` ship today. A preset
   is a class map plus a pagination view; see `Support\Theme::register()`.

The generator's schema introspection, lazy islands and CI were the previous
three items here and all landed in 1.6.0. See `AUDIT.md` §12.

---

## License

MIT. By contributing you agree your work is released under it.
