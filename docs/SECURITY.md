# Security

A Livewire table takes user input from several directions at once — the search
box, filter dropdowns, the query string, and the action payload itself. This
page records what the package does about each, and what it expects from you.

---

## The action surface

**Livewire treats every public method on a component as an action any visitor
can invoke with arbitrary arguments.** `AftableComponent` composes 27 traits and
exposes 400+ public methods, almost all internal helpers that are public only so
a sibling trait can reach them.

The package therefore ships an **action allow-list**. Every client-initiated
call passes through `Support\GuardsActions`, which permits the ~49 deliberate UI
actions and refuses everything else with a 403.

```php
// config/aftable.php
'security' => [
    'guard_actions' => true,   // strongly recommended
    'extra_actions' => [],     // your own methods, if you call any from the browser
],
```

If your application legitimately calls another method from the browser, name it
in `extra_actions` rather than disabling the guard.

`tests/Feature/ActionGuardTest.php` parses the shipped Blade template and
asserts every `wire:click` / `wire:change` target is allowed, so the guard
cannot silently break the table's own UI.

### Why this exists

`renderRawHtml()` compiles its first argument as a Blade template and evaluates
it. While it was public, this worked from the browser:

```
call renderRawHtml with '{{ 6*7 }}-{{ strtoupper("pwn") }}'  →  "42-PWN"
```

That is arbitrary PHP execution with the application's full privileges. The
method is now `protected` **and** refused by the guard — two independent
barriers, because one of them being reverted should not be enough.

---

## Input handling

| Input | Treatment |
|---|---|
| `search` | trimmed, `strip_tags`, capped at 255 chars, LIKE metacharacters escaped, bound as a query parameter |
| `filterColumn` | must appear in the declared `filters` or `columns` |
| `filterInstances[*].column` | same allow-list |
| `dateColumn` | same allow-list, values parsed through Carbon |
| `sortColumn` | must be a declared, sortable column |
| `relation` strings | parsed by `Support\RelationPath`, rejected unless a plain identifier chain |
| every column in a WHERE / ORDER BY | table-qualified before use |
| `records` / `perPage` | clamped to the largest `per_page_options` value |

### Search characters are escaped, not stripped

Laravel binds `LIKE` values as query parameters, so `where($col, 'LIKE', "%$term%")`
is already injection-safe. The package used to additionally run
`preg_replace('/[^\w\s\-_@.]/i', '', $term)`, which bought nothing and broke real
searches: apostrophes (`O'Brien`), `50%`, `C++`, `#tag` — and, because `\w`
without `/u` is ASCII-only, **every non-Latin script**. An Arabic or Urdu term
was stripped to the empty string and the table silently returned every row.

Stripping is gone. But a bound LIKE value is still a **pattern**, which is a
separate problem from injection and was live until 1.6.0:

| Term | Used to match | Now matches |
|---|---|---|
| `%%%` | every row | rows containing `%%%` |
| `____` | every row of four or more characters | rows containing `____` |
| `Ali%ce` | `Alice` | rows containing the literal `Ali%ce` |

`%` and `_` are now escaped and the query is emitted with an explicit
`ESCAPE` clause, so a search box searches for what was typed. The escape
character is `=`, not a backslash: `ESCAPE '\'` means different things to
MySQL, SQLite and SQL Server, and `\` is the one character whose
literal-string handling is not portable between them.

This is not only a correctness fix. A leading-wildcard `LIKE '%…%'` is a full
scan on every searchable column, and `%` alone made every search the worst
possible one.

### The page size is clamped

`records` and `perPage` are query-string properties, so they are user input.
Until 1.6.0 they were used verbatim: `?records=999999` made the server select,
hydrate and serialise the whole table into the Livewire snapshot on every
request. They are now clamped to the largest value in
`aftable.query.per_page_options` (default `500`). Raise that config value if
your application genuinely wants bigger pages.

### Column names are validated, not escaped

`filterColumn` and friends arrive from `wire:model` and the query string, so
they are attacker-controlled. They are checked against the declared
configuration before reaching the query builder. Without that check a crafted
request can filter on any column of the underlying table — a blind oracle over
columns you never displayed.

### Relation strings must be identifier chains

`Support\RelationPath` rejects anything that is not `word(.word)*:word`. These
values reach `whereHas()`, `with()` and historically `orderByRaw()`.

---

## Output handling

- Cell values go through Blade's `{{ }}`, so they are escaped.
- `raw` columns are **not** escaped — that is their purpose. Treat a `raw`
  template as code you wrote, never as data:

  ```php
  // fine — the row value is escaped by {{ }} inside the template
  ['raw' => '<span class="badge">@{{ $row->status }}</span>']

  // NOT fine — never build a raw template out of user input
  ['raw' => $request->input('template')]
  ```

- Non-scalar values (arrays, enums, dates) are normalised before echoing, so a
  `json`-cast column cannot break out of the cell or crash the render.

---

## Compiled raw templates

`raw` columns are compiled Blade, cached to disk by content hash under:

```
storage/framework/cache/aftable/
```

Not `sys_get_temp_dir()`. That directory is shared and world-writable on most
hosts, and the filenames are a predictable `md5()` of the template text — a
local user could pre-create the exact `.php` file the component was about to
`include`. Writes are atomic (temp name + `rename`), so a concurrent request can
never include a half-written file.

Clear them with:

```bash
php artisan af-table:clear-phantom-columns
```

---

## Demo routes

`/aftable/test`, `/aftable/docs` and `/aftable/generator` render demo data and
expose a code generator. They are:

- behind `config('aftable.routes.enabled')`, and
- **never registered when `APP_ENV=production`**, regardless of that flag.

## Schema introspection

The generator can read your application's own schema so it offers real models
and real columns. That is a third guard on top of the two above, because it is
categorically more sensitive than the demo pages: those read the package's own
`test_*` tables, this one reads *yours*.

`GET /aftable/generator/schema` and `GET /aftable/generator/schema/{model}` are
registered only when **all three** hold:

1. `aftable.routes.enabled` is true;
2. `APP_ENV` is not `production`; and
3. `aftable.generator.enabled` is explicitly true — **default `false`**.

`af-table:make` checks the same flag and refuses without it.

With introspection on, the endpoints still never return:

- anything the model lists in `$hidden` — dropped before serialisation, so the
  column's existence is not disclosed either;
- a suggestion for a column named `password`, `remember_token`, `api_token`,
  `secret`, `private_key` or `two_factor_*`. These are reported as existing
  (you may legitimately need to know) but never offered as a table column;
- anything at all for a class that is not an Eloquent model discovered under
  `aftable.generator.model_paths`.

That last one is the security boundary, not a convenience. `describe()`
constructs the class it is handed, so it accepts only classes that already
appeared in the discovered model list — never a `class_exists()` check, which
would let a caller pick any constructible class in the application.

Relation discovery calls the model's own no-arg public methods to see which
return a `Relation` — the same technique as `php artisan model:show`. It skips
accessors, mutators, scopes, magic methods, anything inherited from `Model`,
and anything with a declared return type that is not a `Relation`. It still
means your model code runs, so do not enable introspection on a host where that
matters.

Turn it on locally, generate your Blade, turn it off.

---

## Your responsibilities

The package controls the table, not your data.

**Authorisation is yours.** The component renders whatever the model query
returns. Scope it:

```blade
@livewire('aftable', [
    'model' => App\Models\Invoice::class,
    'query' => fn ($q) => $q->where('tenant_id', auth()->user()->tenant_id),
    …
])
```

A global scope on the model is stronger still, because it cannot be forgotten at
one call site.

**Row actions need their own gate.** An action's `route` is rendered as a link;
the package does not check whether the current user may follow it. Authorise in
the controller or policy, as usual.

**Do not expose columns you would not print.** Column visibility is a UI
convenience, not a security boundary — a hidden column is still fetched, and
still present in the Livewire snapshot the browser receives. If a value must not
reach the client, do not select it.

---

## Reporting

Security issues: <support@artflowstudio.com>. Please do not open a public issue
first.
