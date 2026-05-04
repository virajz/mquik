# Mquik UI Conventions

> Read this before designing any new screen. The product should feel cohesive whether someone is using `Appointment` or `Vendor Master` — same spacing, same buttons, same patterns.

---

## Brand

| Token           | Value     | Use                                  |
| --------------- | --------- | ------------------------------------ |
| `mq-orange-500` | `#EE7C2B` | Primary accent (logo, current state) |
| `mq-orange-600` | `#D96A1A` | Buttons, focus rings, links          |
| `mq-orange-700` | `#B55315` | Hover states, dark mode accent       |
| `mq-grey-500`   | `#8A8A8A` | Secondary text                       |
| `mq-grey-900`   | `#2B2B2B` | Body text                            |

CSS vars defined in [resources/css/app.css](resources/css/app.css). Tailwind utilities: `bg-mq-orange-500`, `text-mq-grey-900`, etc. The Flux `--color-accent` is mapped to `mq-orange-600` so all Flux components pick up the brand automatically.

**Don't:** use raw hex codes anywhere. Always reference the brand tokens.

---

## Layout

Every authenticated page uses `layouts.app` (defined in [resources/views/layouts/app/sidebar.blade.php](resources/views/layouts/app/sidebar.blade.php)).

```
┌──────────────────────────────────────────────────────────┐
│ [Logo]  Search menu...  │  Breadcrumbs        [Actions]  │  ← secondary header
├─────────────────────────┼────────────────────────────────┤
│ Dashboard              │                                 │
│                        │                                 │
│ ▼ General              │                                 │
│   • Vendor             │           Page content          │
│   • Customer           │                                 │
│                        │                                 │
│ ▼ HR                   │                                 │
│   • Attendance         │                                 │
│                        │                                 │
│ Settings               │                                 │
│ [User]                 │                                 │
└─────────────────────────┴────────────────────────────────┘
```

- **Sidebar:** sticky, fully collapsible (desktop + mobile), branded with the Mquik logo, has a search input for filtering menu items. Menu groups are auto-built from each module's `menu.php`.
- **Secondary header:** breadcrumbs left, page actions right (e.g. "+ New Vendor"). Hidden on mobile (mobile uses a top bar with sidebar toggle + profile).
- **Main content:** padded `px-6 py-6 lg:px-8 lg:py-8`. Max width is whatever Flux's main allows — don't add custom max-widths unless the page genuinely needs them.

---

## Page Structure (every module page)

```blade
<div>
    {{-- 1. Secondary header slots --}}
    <x-slot:breadcrumbs>
        <x-breadcrumbs :items="[['label' => 'Vendor Master']]" />
    </x-slot:breadcrumbs>

    <x-slot:actions>
        <flux:button variant="primary" icon="plus" wire:click="openCreate">
            New Vendor
        </flux:button>
    </x-slot:actions>

    {{-- 2. Page heading + subtitle --}}
    <div class="mb-6">
        <flux:heading size="xl" level="1">Vendor Master</flux:heading>
        <flux:text class="mt-1">Manage your vendors and suppliers.</flux:text>
    </div>

    {{-- 3. Filter bar (search, dropdowns, date range) --}}
    {{-- 4. Data table or content --}}
    {{-- 5. Pagination via flux:pagination --}}
    {{-- 6. Modals (forms, confirms) --}}
</div>
```

Order matters. Heading + subtitle always above the filter bar.

---

## Tables

- Use `flux:table` with explicit column widths for ID, dates, action columns
- ID column: `font-mono text-xs text-zinc-500`, format as `#00042` (5-digit zero-padded)
- Action column: `class="w-32 text-end"`
- Empty state: full-row colspan with `flux:icon` + heading + helper text — never blank
- Rows must have `:key="$row->id"` for Livewire reactivity

**No borders or card wrappers around the table.** Tables sit directly in the page flow — no `<div class="rounded-xl border ...">` shell. Flux's table already has its own subtle row dividers. Wrapping it in a card looks heavy and competes with the page heading. Same applies to filter bars — let them sit naturally above the table without a card.

**Sortable columns by default.** Every column that maps to a real DB column should be `sortable`. The Livewire component holds `$sortBy` + `$sortDirection` (URL-bound via `#[Url]`), a `$sortable` whitelist (security: never trust the URL), and a `sort($column)` action that toggles direction. Header markup:

```blade
<flux:table.column sortable
    :sorted="$sortBy === 'name'"
    :direction="$sortDirection"
    wire:click="sort('name')"
>
    Name
</flux:table.column>
```

The stub generator already wires sort into every new module — for masters, default sort is the most-useful column (alphabetical name for lookups, latest id for transactional).

**Pagination:** always `<flux:pagination :paginator="$rows" />`. Never `{{ $rows->links() }}`.

---

## Forms (in modals)

- Modal width: `class="md:w-[32rem]"` for normal forms, `md:w-[48rem]` for wide forms with side-by-side fields
- Always include heading + subtitle inside the modal
- Group fields with `space-y-4`
- Submit button is `variant="primary"`, cancel is `variant="ghost"` inside `<flux:modal.close>`
- Form labels via Flux's `label="..."` prop, not separate `<flux:label>` elements
- `autofocus` on the first input

**Required vs optional:** mark required with the `required` attribute. Don't use asterisks in labels — let Flux handle it.

---

## Buttons

| Variant   | When                                                                   |
| --------- | ---------------------------------------------------------------------- |
| `primary` | Main action of the screen (Save, Create, Submit) — orange              |
| `filled`  | Secondary key action (Approve, Send)                                   |
| `ghost`   | Tertiary actions, table row actions, Cancel                            |
| `danger`  | Delete, irreversible — only inside confirmation modals, never on a row |
| `subtle`  | Small inline actions in cards                                          |

**Row actions in a table:** `flux:button size="sm" variant="ghost"` with `icon` only or `icon + Label`.

**Destructive actions:** always behind a `flux:modal` confirmation. Never a one-click delete on a row.

---

## Toasts

Use Flux toasts for write feedback:

- Success: `Flux::toast(text: 'Vendor #00042 created.', variant: 'success')`
- Error: `Flux::toast(text: '...', variant: 'danger')`
- Always include the record ID in the toast — workshop convention

---

## Capital typing (workshop rule)

All string fields persist in **UPPERCASE**. Enforced in `Form::save()` after validation:

```php
foreach ($data as $key => $value) {
    if (is_string($value)) {
        $data[$key] = strtoupper($value);
    }
}
```

Display data as-stored (no extra transforms). Inputs accept any case (we capitalize on save).

---

## Auto-generated IDs

Show on every record in lists: `#00042` format (`str_pad(id, 5, '0', STR_PAD_LEFT)`).
Show in success toasts after create.
Searchable by ID in any module's filter bar.

---

## Spacing (Tailwind scale)

- Between sections: `mb-6` (1.5rem)
- Between form fields: `space-y-4` (1rem)
- Inside cards: `p-6`
- Between table action buttons: `gap-1`
- Heading-to-subtitle: `mt-1`

Don't invent custom spacing. Use the rhythm above.

---

## Icons

- Heroicons (Flux ships with them): `home`, `plus`, `pencil-square`, `trash`, `magnifying-glass`, `cog-6-tooth`, `arrow-right-start-on-rectangle`, `inbox`, `cube`, etc.
- Module sidebar icon: declared in `module.php` (`'icon' => 'truck'` for vendors, `'building-office-2'` for customers, etc.)
- Don't introduce icon libraries — Heroicons is enough

---

## Component file size limits

- Livewire PHP files: **max 200 lines**. Split if longer.
- Blade views: **max 200 lines**. Extract child components or partials.
- Ideal Livewire component does one thing: `Index` lists, `Form` creates/edits, `Detail` shows. Don't combine.

If you find a component growing past 150 lines, ask: is this really one responsibility?

---

## Never do

- Inline `style="..."` attributes
- Tailwind arbitrary values like `text-[14px]` unless absolutely necessary (use `text-sm`)
- Custom CSS files per module — everything goes through Tailwind utilities + Flux
- Deep grids (`grid-cols-7`) without thinking about mobile
- Tables that don't have an empty state
- Buttons without an `icon` if the action benefits from one (Create, Edit, Delete, Search always have icons)
- Modals without a clear heading saying what they do
- Toasts without an action subject ("Saved" → bad. "Vendor #00042 saved" → good.)
- `{{ $rows->links() }}` — always `flux:pagination`
- Plain `<select>` — always `flux:select` with `variant="listbox"`

---

## Performance & Database

The system runs on Postgres. Two non-negotiables from day one:

### 1. Index every column you filter, sort, or search by

- `id` (auto, included with `$table->id()`)
- `name` (every master gets indexed by default in the stub)
- Any `is_active`, `status`, `type` enum-style column
- Foreign keys (auto-indexed by `foreignId(...)->constrained()`)
- Date columns used for range filters (`created_at`, `due_date`, etc.)

It's vastly cheaper to ship with indexes than to add them later when the table is at 100k rows.

### 2. Case-insensitive search uses `whereLike(..., caseSensitive: false)`

Postgres `LIKE` is case-sensitive — `where('name', 'like', '%abc%')` won't match `'ABC'`. The portable replacement is Laravel 11+'s `whereLike(column, term, caseSensitive: false)`, which compiles to:

- `ILIKE` on Postgres
- `LIKE` with collation on MySQL
- `LIKE` on SQLite

The module generator stubs default to this. Never hand-write `'like'` in a new query.

### 3. Eager load relationships in index queries

Any `->with(...)` you add to an index must be matched by a column listed in `$fillable` or selected explicitly. Avoid N+1.

---

## Reference screen

Look at [DemoModule's index view](app/Modules/DemoModule/Livewire/views/index.blade.php) — that's the canonical pattern every module index follows. Look at [DemoModule's form view](app/Modules/DemoModule/Livewire/views/form.blade.php) — same for forms.

When in doubt, copy from these.
