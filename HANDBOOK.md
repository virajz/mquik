# Mquik Handbook

> **Purpose:** A single skim-able doc that covers everything a developer (or AI pair) needs to be productive on Mquik in one read. Cross-references the deeper docs — doesn't duplicate them.
>
> **Read order if you're new:** §1 Orientation → §3 Architecture → §4 Generator → §5-6 Conventions → §11 Troubleshooting. Skim the rest.

---

## §1 — Orientation

| Question | Answer |
|---|---|
| What is this? | Workshop ERP for an automotive service business — 127 planned modules across 21 layers |
| Why does it exist? | See `requirements.md` (client spec, ~600 KB) and `modules.md` (architecture map) |
| Who builds it? | Solo dev + Claude as pair programmer · 8-week timeline · see `timeline.md` |
| Strategic plan | See `exec-summary.md` (private — three bets: generator + engines + ship/function/stub honesty) |
| What's shipped? | Module generator + remover · 8 masters live (Insurance Companies, Spare Brands, Customers, Customer Vehicles, Vehicle Brands, Vehicle Models, Vehicle Variants, Vehicle Colors) · ImportExport engine with Reverb-driven progress · 121+ passing tests |

**Other docs you should know about:**
- `CLAUDE.md` — Laravel Boost guidelines (read by AI agents, governs MCP/skill behavior)
- `UI.md` — UI/UX conventions (deeper than §5 here)
- `modules.md` — full 127-module map with phasing
- `timeline.md` — 8-week day-by-day plan
- `requirements.md` — client requirements (read before specifying any module)
- `exec-summary.md` — private execution strategy (do not share)
- `~/.claude/projects/-Users-viraj-Code-mquik/memory/` — Claude's project-specific memory (auto-managed)

---

## §2 — Stack (locked, do not change without explicit approval)

| Layer | Tech | Version |
|---|---|---|
| Language | PHP | 8.4 |
| Framework | Laravel | 13 |
| UI | Livewire | 4 |
| Components | Flux Pro | 2 |
| Styling | Tailwind | 4 |
| Auth | Fortify | 1 |
| Tests | Pest | 4 |
| Realtime | Reverb + Echo | 1 / latest |
| Queue | Database | (Laravel built-in) |
| Database | Postgres | (dev + prod via Laravel Cloud) |
| Test DB | SQLite `:memory:` | (per `phpunit.xml`) |
| CSV | `league/csv` | 9.x |
| Dev tooling | Laravel Boost MCP, Pint, Pail | (latest) |

**Brand colors:** orange `#EE7C2B` (`mq-orange-500`) + grey `#8A8A8A` (`mq-grey-500`). Defined as Tailwind tokens in `resources/css/app.css`. Never use raw hex.

---

## §3 — Architecture

Modules live under `app/Modules/{StudlyName}/` and are auto-discovered at boot by `App\Providers\ModuleServiceProvider`. Each module is a self-contained slice:

```
app/Modules/{Name}/
├── module.php                    # Manifest: label, group, icon, permissions, exportable, importable
├── menu.php                      # Sidebar entry (one or more)
├── routes.php                    # Module-specific routes (loaded into 'web' middleware group)
├── Models/{Name}.php
├── Database/
│   ├── Migrations/               # Auto-loaded
│   ├── Factories/{Name}Factory.php
│   └── Seeders/{Name}Seeder.php
├── Livewire/
│   ├── Index.php                 # List + delete + sort + open Form
│   ├── Form.php                  # Create + Edit (modal-based)
│   └── views/
│       ├── index.blade.php
│       └── form.blade.php
├── Exporters/{Name}Exporter.php  # implements Exportable contract
└── Importers/{Name}Importer.php  # implements Importable contract
```

**Tests** live in `tests/Feature/Modules/{Name}Test.php` (separate from the module dir so Pest auto-discovers).

**Shared engines** (built once, every module registers):
- `App\Modules\ImportExport\` — upload wizard + queued export/import + Reverb progress
- `App\Support\Menu` — permission-filtered grouped menu, builds sidebar + section navbar
- `App\Support\ModuleRegistry` — every module's manifest indexed by name
- (Future) `Authorization`, `AuditLog`, `Notification`, `Reporting`, `SearchEngine`, `FollowUpEngine`

---

## §4 — The module generator (the multiplier)

Most-important command in the project. Don't hand-scaffold modules.

```bash
php artisan make:module VendorMaster        # creates 10 files, scaffold-ready
php artisan module:remove VendorMaster      # rolls back migration + deletes files
```

What `make:module {Name}` produces:
- Migration with `$table->index('name')` by default
- Model with `HasFactory`, custom table name, casts
- Factory with realistic faker data
- Seeder
- Livewire `Index` (list, search via `whereLike(caseSensitive: false)`, sort whitelist, delete via Flux modal confirm)
- Livewire `Form` (create + edit, capital typing on save, Flux modal)
- Index view (Flux table, sortable columns, dropdown menu with Import/Export, no border wrapper)
- Form view (Flux modal, separator + subheading + check-icon submit)
- `module.php` + `menu.php` + `routes.php`
- Pest test file with full CRUD + auth coverage

After generating, you customize: migration columns, factory definition, Form properties + validation, Form view fields, Index view columns, module label/icon/group, menu label/icon/group/order, test for new fields.

**Stubs live in:** `app/Console/stubs/module/`

---

## §5 — UI conventions (skim, see `UI.md` for full)

### Layout (Flux canonical)
- Sidebar: sticky, fully collapsible (desktop + mobile), Mquik brand, search input filters items via Alpine `x-data` on `<body>` (`menuQ`)
- Secondary header (top): scrollable navbar with section tabs derived from menu groups + Dashboard tab. **Not breadcrumbs.**
- Main: padded `px-6 py-6 lg:px-8 lg:py-8`

### Page anatomy (every module index)
1. Heading + subtitle + `+ New {Thing}` button + `⋮` dropdown (Import/Export) — all on one row
2. Filter bar (search, status filter, etc.) — no card wrapper
3. `<flux:table>` directly in page flow — **no border/card wrapper**
4. `<flux:pagination :paginator="$rows" />` — never `{{ $rows->links() }}`
5. `<livewire:import-export.export-button>` + `<livewire:import-export.import-wizard>` (passive, listen for global events)

### Tables
- Sortable columns on every real DB column: `<flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">`
- Component holds `$sortBy`, `$sortDirection` (`#[Url]`-bound), `$sortable` whitelist (security — never trust URL)
- ID column: `font-mono text-xs text-zinc-500`, format `#{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}`
- Action column: `class="w-32" align="end"`
- Empty state: full-row colspan with `flux:icon` + heading + helper text — never blank
- Status: `flux:badge color="lime"|"zinc"|"amber"|"red"` `size="sm"`

### Forms (in modals)
- Modal width: `class="md:w-md"` simple, `md:w-2xl` wide
- Pattern: `flux:heading` + `flux:subheading` → `flux:separator variant="subtle"` → field stack → `flux:separator variant="subtle"` (before toggle) → action footer
- Submit: `<flux:button type="submit" variant="primary" icon="check">`
- Cancel: `<flux:button variant="ghost">` inside `<flux:modal.close>`
- Required fields: use `required` attribute, no asterisks in labels
- Labels via Flux's `label="..."` prop, not separate `<flux:label>`
- `autofocus` on first input

### Field polish (don't ship vanilla)
- Phone: wrap in `<flux:input.group>` with `+91` prefix, `mask="99999 99999"`, `inputmode="numeric"`
- Codes (GSTIN, PAN, IFSC): `class:input="font-mono uppercase tracking-wide"`, `maxlength`, `description` line for format help
- Percentages: put `%` in the label (cleanest), or use `<flux:input.group.suffix>`
- Contact name: `icon="user"`. Email: `icon="envelope"`.
- Address/Notes: `<flux:textarea rows="2">`
- Active toggle: `<flux:switch label="Active" description="...">`

### Spacing rhythm (don't invent custom)
- Sections: `mb-6`
- Form fields: `space-y-4`
- Form sections: `space-y-5`
- Grid gaps: `gap-3` (rows), `gap-4` (large)
- Heading→subtitle: `mt-1`

### Dropdowns next to "New" button (Import/Export)
```blade
<flux:dropdown align="end">
    <flux:button variant="ghost" icon="ellipsis-vertical" />
    <flux:menu>
        <flux:menu.item icon="arrow-up-tray"
            wire:click="$dispatch('start-import', { module: '{{ studlyName }}' })">
            Import…
        </flux:menu.item>
        <flux:menu.item icon="arrow-down-tray"
            wire:click="$dispatch('start-export', { module: '{{ studlyName }}' })">
            Export
        </flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

### File size cap
Livewire components and Blade views: **max 200 lines**. Split via traits, services, or partials when growing past 150.

### Always-Flux table (never the native HTML version)

| For | Use | Never |
|---|---|---|
| Dropdowns | `<flux:select variant="listbox">` | native `<select>` or default `flux:select` |
| Dates | `<flux:date-picker with-today selectable-header fixed-weeks type="input">` | `flux:input type="date"` or native date inputs |
| Colors / hex | `<flux:color-picker type="input">` | text input + custom swatch div |
| File uploads | `<flux:file-upload>` + `<flux:file-upload.dropzone>` (+ `<flux:file-item>` for selected file) | `flux:input type="file"` |
| Pagination | `<flux:pagination :paginator="$rows">` | `{{ $rows->links() }}` |

The native HTML versions look out of place beside Flux components and lose the polish (calendar UI with 6-row fixed weeks, drag-drop zone, palette picker, mobile-friendly inputs).

### Foreign-key pickers (the convention)

When a form needs a FK select (e.g. Brand on a Model form, Customer on a CustomerVehicle form):

```php
// In the Livewire component
use Livewire\Attributes\Computed;

#[Computed]
public function brands()
{
    return VehicleBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
}
```

```blade
<flux:select wire:model="brand_id" label="Brand" variant="listbox" placeholder="Select brand" searchable required>
    @foreach ($this->brands as $b)
        <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
    @endforeach
</flux:select>
```

Add `searchable` when the list could exceed ~10 items. Add a `placeholder="Pick a brand first"` and `:disabled="! $brand_id"` on dependent dropdowns (Variant depends on Model).

Reset dependent FKs when parent changes:
```php
public function updatedModelId(): void
{
    $this->variant_id = null;
}
```

For visual hints in option (color swatch, transmission icon), pass HTML inside `<flux:select.option>`:
```blade
<flux:select.option :value="$c->id">
    <div class="flex items-center gap-2">
        <span class="size-3 shrink-0 rounded-full border border-zinc-300" style="background-color: {{ $c->hex_code }}"></span>
        <span>{{ $c->name }}</span>
    </div>
</flux:select.option>
```

### Cross-FK uniqueness (composite uniques)

When a model is unique within a parent (e.g. a Vehicle Model name is unique per brand — Maruti Swift vs Nissan Swift):

```php
// Migration
$table->unique(['brand_id', 'name']);

// Form Rule
'name' => [
    'required', 'string', 'max:255',
    Rule::unique('vehicle_models', 'name')
        ->where(fn ($q) => $q->where('brand_id', $this->brand_id))
        ->ignore($this->editingId),
],
```

---

## §6 — Data conventions

### Capital typing (workshop rule)
All string fields persist UPPERCASE. Enforced in `Form::save()` after validation:

```php
$skipUppercase = ['email', 'numeric_field', 'is_active'];
foreach ($data as $key => $value) {
    if (is_string($value) && ! in_array($key, $skipUppercase, true)) {
        $data[$key] = strtoupper($value);
    }
}
```

### Auto-generated IDs
`#00042` format on every list row + every success toast. Searchable by ID in filter bars.

### Postgres-safe search
**Never** `where(col, 'like', ...)` — Postgres `LIKE` is case-sensitive. Use:

```php
$query->whereLike('name', '%'.$term.'%', caseSensitive: false)
    ->orWhereLike('code', '%'.$term.'%', caseSensitive: false);
```

Compiles to `ILIKE` (Postgres), `LIKE` with collation (MySQL), `LIKE` (SQLite).

### Indexes from day one
Every searchable, filterable, sortable column gets an index in the migration. Examples:
- `$table->index('name')` — every master
- `$table->index(['is_active', 'name'])` — composite for "active records sorted by name"
- `$table->unique('name')` — unique masters
- Foreign keys auto-index via `foreignId(...)->constrained()`

Cheaper to ship with indexes than add later.

### NEVER wipe dev DB
Forbidden against the Postgres dev DB without explicit user permission:
- `php artisan migrate:fresh`
- `php artisan migrate:rollback`
- `php artisan db:wipe`
- Same via `Artisan::call(...)` inside tinker

When debugging: wrap in `DB::beginTransaction()` / `DB::rollBack()`, or delete only specific records you created.

`php artisan migrate` (no flags) is additive and safe.

`php artisan test` uses SQLite `:memory:` per `phpunit.xml` — never touches Postgres.

---

## §7 — Real-time (Reverb + Echo)

### When to use it
Anything where the user is waiting for a queued job: imports, exports, long PDF generations, batch operations.

### Three processes must run for live updates in the browser
```bash
npm run dev                       # vite — bundles echo.js into the browser
php artisan queue:work --tries=1  # picks up queued jobs
php artisan reverb:start          # WebSocket server (port 8080 default)
```

`composer run dev` runs the first two but **not Reverb** — start it separately.

### Event class pattern (do this exactly)
```php
class XyzProgressUpdated implements ShouldBroadcastNow   // ← Now, not ShouldBroadcast
{
    public function __construct(
        public int $entityId,
        public int $userId,
        public string $status,
        public int $processedRows,
        public int $totalRows,
        // ... primitives only, NEVER pass models
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('exports.'.$this->userId);
    }

    public function broadcastAs(): string
    {
        return 'progress';   // ← custom name
    }

    public function broadcastWith(): array { /* compact payload */ }
}
```

**Why primitives + `ShouldBroadcastNow`:** `ShouldBroadcast` queues the broadcast; `SerializesModels` re-fetches by ID at processing time, so by then the model is at its final state — every "10% done" broadcast ships "100%" payload. The primitive snapshot + immediate dispatch fixes both.

### Listener pattern in Livewire (do this exactly)
```php
public int $userId = 0;

public function mount(): void
{
    $this->userId = (int) auth()->id();   // public property so #[On] can interpolate
}

#[On('echo-private:exports.{userId},.progress')]   // ← LEADING DOT before event name
public function onProgress(array $payload): void { /* ... */ }
```

**The leading `.` is mandatory** when using `broadcastAs()`. Without it Echo treats the name as an FQCN and silently never fires.

### Channel auth
In `routes/channels.php`:
```php
Broadcast::channel('exports.{userId}', fn ($user, $userId) => (int) $user->id === (int) $userId);
```

### Don't poll
We removed polling fallbacks. Reverb is the source of truth. If progress isn't updating, fix the wiring — the failure modes are: missing leading dot, queued (not Now) broadcast, model-vs-primitive in event constructor, or Reverb not running.

---

## §8 — Import / Export (every module gets this)

Engine lives in `app/Modules/ImportExport/`. Two thin contract classes per module:

### Make a module exportable
```php
// app/Modules/{Name}/Exporters/{Name}Exporter.php
class XyzExporter implements Exportable
{
    public function label(): string { return 'Xyz'; }
    public function headers(): array { return ['ID', 'Name', /* ... */]; }
    public function query(): Builder  { return Xyz::query()->orderBy('name'); }
    public function row(object $m): array { return [$m->id, $m->name, /* ... */]; }
    public function fileName(): string { return 'xyz'; }
}
```

In `module.php`:
```php
'exportable' => XyzExporter::class,
```

### Make a module importable
```php
class XyzImporter implements Importable
{
    public function label(): string { return 'Xyz'; }

    public function columns(): array {
        return [
            'name' => ['label' => 'Name', 'required' => true, 'type' => 'string'],
            'code' => ['label' => 'Code', 'required' => false, 'type' => 'string'],
        ];
    }

    public function uniqueBy(): array { return ['name']; }
    public function validateRow(array $data): array { /* return errors[] */ }
    public function createRecord(array $data): void { Xyz::create($this->normalize($data)); }
    public function updateRecord(object $existing, array $data): void { $existing->update($this->normalize($data)); }
}
```

In `module.php`:
```php
'importable' => XyzImporter::class,
```

The dropdown menu, wizard, progress modal, queued jobs, Reverb broadcasts, error CSV download, all come from the engine. No per-module wiring needed beyond the two classes + manifest entries — the stub generator already includes the `<livewire:import-export.export-button>` and `<livewire:import-export.import-wizard>` in every new index view.

### Wizard flow
Upload → Map columns (auto-suggested) → Behavior (upsert/create-only/skip/error) → Generate preview (dry-run) → Confirm & import (real run with progress + error CSV link)

### Notify the parent table after import
The wizard dispatches `{module-kebab}:saved` after a real import. Each module's Index already listens for that event (also fired by `Form::save()`), so the table refreshes automatically.

---

## §9 — Testing

### Run
```bash
php artisan test --compact                    # all tests
php artisan test --filter=XyzMaster --compact # one module
php artisan test --filter='it does X'         # one test
```

### Where they live
- `tests/Feature/Modules/{Name}Test.php` — per-module CRUD + auth + behaviors
- `tests/Feature/Modules/ImportExport/*.php` — engine tests
- `tests/Feature/Auth/*.php` — Fortify (some are skipped via feature flags — that's normal)

### What to cover per module
- Index renders + auth-redirect
- Search filters correctly (use distinct strings — `"INACTIVE BRAND"` contains `"ACTIVE BRAND"` substring trap)
- Status filters
- Sort works (use `strpos` on `html()` to assert order)
- Form create with capital typing assertion
- Form update preserves self-uniqueness
- Form validation errors
- Delete from index
- Module-specific edge cases

### Test infrastructure
- `tests/Pest.php` has `RefreshDatabase` enabled globally on Feature tests
- Test DB is SQLite `:memory:` (per `phpunit.xml`)
- Livewire file uploads: `UploadedFile::fake()->createWithContent('test.csv', $content)` (NOT a manual `new UploadedFile`)
- Storage: `Storage::fake('local')` then read via `Storage::disk('local')->path($stored)` (the fake puts files in tempdir, not real `storage/app/private/`)

---

## §10 — Daily commands

```bash
# Dev shell
composer run dev                              # serve + queue + pail + vite (no reverb)
php artisan reverb:start                      # WebSocket server (separate terminal)

# Scaffolding
php artisan make:module {Name}
php artisan module:remove {Name} --force

# Tests + format
php artisan test --compact
vendor/bin/pint --dirty --format agent        # only changed files (preferred)

# Migrations
php artisan migrate                           # additive ONLY — safe
# DON'T: migrate:fresh, migrate:rollback, db:wipe (against Postgres dev DB)

# Boost MCP (use these instead of guessing)
# - search-docs (always before adding a package or trying syntax)
# - database-schema, database-query
# - read-log-entries, last-error
# - browser-logs
```

---

## §11 — Troubleshooting (known-good fixes)

| Symptom | Root cause | Fix |
|---|---|---|
| Reverb broadcasts in worker log but UI doesn't update | `#[On]` listener missing leading dot | Use `,.progress` not `,progress` (the dot signals custom `broadcastAs()`) |
| Progress events all arrive showing 100% | `ShouldBroadcast` (queued) + `SerializesModels` re-fetches model at final state | Switch to `ShouldBroadcastNow`, pass primitives in event constructor |
| Sidebar items not clickable | Wrapping divs around `<flux:sidebar.item>` breaks click area / wire:navigate | Drop wrappers, put `x-show` directly on the Flux component |
| Sidebar search input not interactive | `x-data` on `<flux:sidebar>` doesn't propagate to all children | Move `x-data="{ menuQ: '' }"` to `<body>`, replace `<flux:sidebar.search>` with regular `<flux:input icon="magnifying-glass" clearable>` |
| "New" button doesn't fire `wire:click` | Button placed in layout's secondary header, outside Livewire component scope | Move action buttons into the page content next to the heading |
| `assertDontSee('ACTIVE BRAND')` fails when "INACTIVE BRAND" is present | Substring contained match (`INACTIVE BRAND` contains `ACTIVE BRAND`) | Use distinct strings in tests (`BOSCH ENABLED` / `DENSO DISABLED`) |
| Search returns no results in Postgres dev | `LIKE` is case-sensitive in Postgres | Use `whereLike(col, term, caseSensitive: false)` |
| Test "syntax error, unexpected token }" in PHP string | `{$obj::class}` doesn't interpolate inside double-quoted strings | Extract `$cls = $obj::class;` first |
| Static analyzer warns "Undefined method 'actingAs'/'id'/'get'" | False positive — Pest TestCase trait + `auth()`/`Storage::disk()` facades resolve at runtime | Ignore — confirmed runtime behavior is correct |
| `composer dump-autoload` errors after deleting a module class but file references it | Old class still referenced in another file | Delete or update the referencing file before dumping |
| Flux Pro `composer install` 403 in CI/cloud container | Auth file in wrong location for cloud user split | Project-local `auth.json` (highest precedence), or `composer config http-basic.composer.fluxui.dev` in setup script |
| `migrate:fresh` accidentally wiped dev DB | Used `migrate:fresh` instead of additive `migrate` | NEVER again — see §6 NEVER wipe dev DB |
| Livewire file upload test errors `Undefined property: name` | Built `new UploadedFile(...)` manually instead of via `UploadedFile::fake()` | Use `UploadedFile::fake()->createWithContent(...)` for Livewire `WithFileUploads` |
| Form modal placeholder text appears in `assertDontSee` and breaks tests | The placeholder string (e.g. "GJ 05 AA 1234") is in the HTML always — substring check fails | Change the placeholder to something abstract ("STATE RTO ALPHA NUMBER") OR make test data use distinct strings that don't collide with placeholders |
| FK uniqueness within parent fails or false-allows duplicates | `Rule::unique` without scope — checks the whole table | Use `Rule::unique(...)->where(fn ($q) => $q->where('parent_id', $this->parent_id))->ignore($editingId)` |
| Two icons collide in sidebar (same Heroicon for two modules) | Lazy icon picking | Audit `menu.php` icons across modules; use distinct shapes (truck, cube, swatch, building-storefront, shield-check, tag, user-circle) |
| Sidebar order is chaotic | Each module set its own `order` independently | Cluster groups in 10-spaced order: people 10-20, vehicle reference 30-60, other reference 70+ |
| Color picker inside a form shows hex but not as a swatch | Used `flux:input` with custom span | Use `<flux:color-picker type="input">` — built-in palette + live swatch |

---

## §12 — File map (where to find what)

| Want to find… | Look at… |
|---|---|
| Module conventions / generator | `app/Console/Commands/MakeModuleCommand.php`, `app/Console/stubs/module/*.stub` |
| Module discovery / loading | `app/Providers/ModuleServiceProvider.php` |
| Menu rendering | `app/Support/Menu.php`, `resources/views/layouts/app/sidebar.blade.php` |
| Module registry | `app/Support/ModuleRegistry.php` |
| Brand tokens | `resources/css/app.css` (top of file, `--color-mq-*`) |
| App layout shell | `resources/views/layouts/app/sidebar.blade.php` |
| Breadcrumbs partial | `resources/views/components/breadcrumbs.blade.php` (rarely used now — section navbar replaces) |
| Import/Export engine | `app/Modules/ImportExport/` |
| Real-time channels | `routes/channels.php` |
| Echo client setup | `resources/js/echo.js` + `resources/js/app.js` |
| Pest config | `tests/Pest.php`, `phpunit.xml` |
| Pint config | `pint.json` |
| Reverb config | `config/reverb.php`, `.env` (`REVERB_*` + `BROADCAST_CONNECTION=reverb`) |
| Queue config | `config/queue.php`, `.env` (`QUEUE_CONNECTION=database`) |

---

## §13 — Glossary of patterns we use

| Pattern | Where | Notes |
|---|---|---|
| **Module manifest** | `module.php` per module | Returns array with `label`, `group`, `icon`, `permissions`, `exportable?`, `importable?` |
| **Resource component** | Livewire `Index` per module | List + search + filter + sort + delete + open Form |
| **Form component** | Livewire `Form` per module | Create + edit, modal-based, validation, capital typing on save, dispatches `{kebab}:saved` |
| **Workflow event** | `{kebab}:saved`, `{kebab}:edit` | Form ↔ Index ↔ Wizard communication |
| **Engine module** | `Modules/ImportExport/` | Shared functionality, no menu entry, modules opt in via manifest keys |
| **Contract pair** | `Exportable` + `Importable` | Per-module integration with the ImportExport engine |
| **Read-model** | (future) `JobHistory`, `OutstandingTracker` | Owns no writes; rebuilt from event stream — Day 5+ pattern |
| **Approval engine** | (Day 2 work) `ApprovalEngine` | Will replace per-approval modules with config registrations |
| **Section navbar** | Layout secondary header | Auto-derived from `Menu::sectionTabs()` (one tab per group) |
| **Dropdown actions** | Index page `⋮` next to "New" | Currently Import/Export, leaves room for Print/Bulk Delete |

---

## §14 — Working with Claude (AI pair-programmer)

When asking Claude for help on Mquik, be precise about:
- **Module name + scope** (1 sentence)
- **Where it fits** (group, downstream consumers)
- **Whether to check requirements.md first** (if you don't know the full scope)

Claude will produce a structured spec like:
```
MODULE / GROUP / ICON / TABLE / COLUMNS (with type, validation, indexed) / INDEXES /
FORM SECTIONS / LIST COLUMNS / SEARCH / DEFAULT SORT / POLICIES / SEEDER /
VALIDATION HIGHLIGHTS / UI POLISH / INTEGRATION POINTS
```

You confirm or tweak in chat, then Claude scaffolds via `make:module`, customizes per spec, runs tests + Pint, and confirms.

**Claude's persistent memory:** `~/.claude/projects/-Users-viraj-Code-mquik/memory/` — small files that remember user preferences and project facts across sessions. The index is `MEMORY.md`.

### §14.1 — Quality bar (non-negotiable, written after the 2026-05-06 incident)

These rules exist because we shipped:
- A flaky factory (`VehicleColorMaster` `dechex` without zero-pad → ~7% test failure rate)
- Two modules with stale scaffolder defaults in `menu.php` (group=`General`, label=`InventoryGroupMaster`) because the agent's report was trusted instead of verified

**Definition of "done" for any change:**
1. **Verify, don't trust agent reports.** If an agent (or batch of edits) claims a file was modified, open it. `cat` it. Confirm. Reports lie when the agent edited the wrong file or skipped one silently.
2. **Run the full test suite — at least once, more if factories changed.** Any new factory or random-data generator runs **5 consecutive `php artisan test` passes** before the change is "done". Flaky tests die at birth, not in production.
3. **Smoke the UI.** For any change that touches blade templates, sidebar, menu, layout, or form rendering — open the dev server and look at the actual page once. Screenshot if the change is visual.
4. **`menu.php` and `module.php` consistency check.** Both files declare `group`. They must match. Drift means the sidebar renders one thing while the rest of the system thinks something else. Run the audit script:
   ```bash
   for d in app/Modules/*/; do
     m=$(basename "$d"); mg=$(grep "'group' =>" "$d/menu.php" 2>/dev/null | head -1 | sed -E "s/.*=> '([^']+)'.*/\1/")
     cg=$(grep "'group' =>" "$d/module.php" 2>/dev/null | head -1 | sed -E "s/.*=> '([^']+)'.*/\1/")
     [ -n "$mg" ] && [ "$mg" != "$cg" ] && echo "DRIFT: $m → menu=$mg, module=$cg"
   done
   ```
5. **Pint, then tests, then visual.** Two of three is no longer enough.

**When delegating to a sub-agent:**
- Give it an explicit **post-condition checklist** ("after writing, verify menu.php has these exact strings: ...").
- Demand a **diff or `cat` of every modified file** in its report, not just a "modified" filename list.
- After it returns, **re-read the files yourself** before declaring success. The session of 2026-05-06 cost us an hour of debugging because we trusted a report.

**When fixing a bug:**
- Always run the failing test multiple times to confirm it's deterministic. If it passes 4/5 runs, you have a flaky test, not a fixed bug. Look at all `random_int`, `dechex`, `bothify`, `numerify` and Faker calls in the touched factory.

**Sidebar changes specifically:**
- After adding any new module, audit groups via the script above.
- A module's `module.php` group is the source of truth for the badge in audit logs / permission UI; `menu.php` group is the sidebar bucket. Keep them aligned.
- The "General" group should only appear if you genuinely have an uncategorised utility. If a real master ends up in "General", it's almost certainly a missed `menu.php` edit.

---

## §15 — Phasing reminder (where in the plan are we)

Per `timeline.md`, we're tracking:

| Wave | Scope | Status |
|---|---|---|
| **Day 1** | Foundation: app shell, generator, masters skeleton | ✓ done |
| **Day 1.5** | InsuranceCompany + SpareBrand masters + ImportExport engine | ✓ done |
| **Day 1.6** | Customer, Customer Vehicle, Vehicle Brand/Model/Variant/Color | ✓ done (8 masters now live) |
| **Day 2** | Vehicle Inventory snapshot + Service Type + Service Package masters (still need before JobCard) | NEXT |
| **Day 2.5** | Authorization, AuditLog (foundational engines) | pending |
| **Day 3** | Front-desk workflow: Appointment → PickupDrop → GateInOut → JobCard → DigitalInspection | pending |
| **Day 4** | Parts procurement chain (IPI/IPR/IPO/VPI/VPR/VPO + GRN) | pending |
| **Day 5** | Sales: Estimate, Approval, Proforma, Invoices, Returns | pending |
| **Day 6** | Cash, Closure, Outside Work, Bodyshop, Insurance | pending |
| **Day 7** | Accounting, CRM follow-ups, Notifications, Mobile API | pending |
| **Day 8** | Reports, Dashboards, Smart Salary, integrations, UAT, go-live | pending |

**Right now we're between Day 1.6 and Day 2.** Master-data foundation is mostly done. Before JobCard, we still need: Vehicle Inventory (the standard checklist a Job Card snapshots), Service Type (job category), Service Package / AMC (pre-defined service bundles), and several smaller lookup masters identified in `requirements.md` row 88. Authorization lands in parallel — until then, the menu engine's permission stubs are no-ops and `module.php`'s `permissions` arrays are dead weight.

---

*Last updated: post-Day-D ImportExport engine ship. If you make changes that affect conventions, update this file in the same PR.*
