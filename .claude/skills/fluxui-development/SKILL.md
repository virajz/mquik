---
name: fluxui-development
description: "Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling."
license: MIT
metadata:
  author: laravel
---

# Flux UI Development

## Documentation

Always `search-docs` before making changes. Use broad topic queries; the tool scopes results to installed versions automatically.

## Overview

This project uses **Flux UI Pro v2** — all free and Pro components are available. Flux is built on Tailwind CSS v4 and requires Livewire v3.7+.

Always use Flux components when one exists for the need. Fall back to plain Blade only when no Flux component fits.

---

## Buttons

```blade
{{-- Variants: outline (default), primary, filled, danger, ghost, subtle --}}
<flux:button variant="primary">Save</flux:button>
<flux:button variant="danger">Delete</flux:button>
<flux:button variant="ghost">Cancel</flux:button>

{{-- With icon (leading / trailing) --}}
<flux:button icon="arrow-down-tray">Export</flux:button>
<flux:button icon:trailing="chevron-down">Open</flux:button>

{{-- Icon-only (square) --}}
<flux:button icon="ellipsis-horizontal" square />

{{-- Sizes: base (default), sm, xs --}}
<flux:button size="sm">Small</flux:button>

{{-- Link button --}}
<flux:button href="/dashboard" variant="ghost">Dashboard</flux:button>

{{-- Submit / loading --}}
<flux:button type="submit" variant="primary" loading>Save changes</flux:button>

{{-- With tooltip --}}
<flux:button icon="trash" tooltip="Delete item" tooltip:position="top" />
```

**Key props:** `variant`, `size`, `icon`, `icon:trailing`, `icon:variant` (outline/solid/mini/micro), `square`, `href`, `as`, `loading`, `tooltip`, `tooltip:position`, `kbd`, `inset`, `align`.

---

## Icons

Flux uses [Heroicons](https://heroicons.com/) by default. Always look up exact names — never guess.

```blade
{{-- Four variants for every icon --}}
<flux:icon.bolt />                  {{-- 24px outline (default) --}}
<flux:icon.bolt variant="solid" />  {{-- 24px filled --}}
<flux:icon.bolt variant="mini" />   {{-- 20px filled --}}
<flux:icon.bolt variant="micro" />  {{-- 16px filled --}}

{{-- Inline in components --}}
<flux:button icon="arrow-down-tray">Export</flux:button>
<flux:badge icon="check-circle">Active</flux:badge>
```

For icons not in Heroicons, import from [Lucide](https://lucide.dev/):

```bash
php artisan flux:icon crown grip-vertical github
```

---

## Forms & Fields

```blade
{{-- Full field with label + error --}}
<flux:field>
    <flux:label>Email address</flux:label>
    <flux:input type="email" wire:model="email" placeholder="you@example.com" />
    <flux:error name="email" />
</flux:field>

{{-- Shorthand via label prop (wraps in flux:field automatically) --}}
<flux:input label="Email" type="email" wire:model="email" />
<flux:input label="Password" type="password" viewable wire:model="password" />

{{-- Description / help text --}}
<flux:input label="Username" wire:model="username" description="Lowercase letters only." />
<flux:input label="API Key" wire:model="apiKey" description:trailing="Keep this private." />

{{-- Input extras --}}
<flux:input icon="magnifying-glass" placeholder="Search..." clearable />
<flux:input icon:trailing="key" copyable wire:model="token" />
<flux:input mask="99/99/9999" placeholder="MM/DD/YYYY" />

{{-- Textarea --}}
<flux:textarea label="Bio" wire:model="bio" rows="4" />

{{-- Checkbox --}}
<flux:checkbox wire:model="agree" label="I agree to the terms" />

{{-- Checkbox group (cards variant — Pro) --}}
<flux:checkbox.group wire:model="features" label="Features" variant="cards" class="max-sm:flex-col">
    <flux:checkbox value="notifications" icon="bell" label="Notifications" description="Get notified of updates." />
    <flux:checkbox value="analytics" icon="chart-bar" label="Analytics" description="See usage insights." />
</flux:checkbox.group>

{{-- Checkbox group (buttons variant — Pro) --}}
<flux:checkbox.group wire:model="features" label="Features" variant="buttons">
    <flux:checkbox value="notifications" icon="bell" label="Notifications" />
    <flux:checkbox value="analytics" icon="chart-bar" label="Analytics" />
</flux:checkbox.group>

{{-- Radio group --}}
<flux:radio.group wire:model="plan" label="Plan">
    <flux:radio value="free" label="Free" />
    <flux:radio value="pro" label="Pro" />
</flux:radio.group>

{{-- Radio cards (Pro) --}}
<flux:radio.group wire:model="shipping" label="Shipping" variant="cards" class="max-sm:flex-col">
    <flux:radio value="standard" checked>
        <flux:radio.indicator />
        <div class="flex-1">
            <flux:heading class="leading-4">Standard</flux:heading>
            <flux:text size="sm" class="mt-2">4–10 business days</flux:text>
        </div>
    </flux:radio>
</flux:radio.group>

{{-- Switch --}}
<flux:switch wire:model="notifications" label="Email notifications" />

{{-- Dark mode toggle (Alpine magic) --}}
<flux:switch x-data x-model="$flux.dark" label="Dark mode" />
```

### Select

**Always use `variant="listbox"` for selects.** Never use the native default select.

```blade
{{-- Standard listbox (always use this) --}}
<flux:select variant="listbox" wire:model="industry" placeholder="Choose industry..." clearable>
    <flux:select.option value="photography">Photography</flux:select.option>
    <flux:select.option value="design">Design services</flux:select.option>
</flux:select>

{{-- Multi-select (Pro) --}}
<flux:select variant="listbox" wire:model="industries" multiple placeholder="Choose industries..." selected-suffix="industries selected">
    <flux:select.option>Photography</flux:select.option>
</flux:select>

{{-- Searchable listbox (Pro) --}}
<flux:select variant="listbox" wire:model="industry" searchable placeholder="Choose...">
    <flux:select.option>Photography</flux:select.option>
</flux:select>

{{-- Combobox with backend search (Pro) --}}
<flux:select wire:model="userId" variant="combobox" :filter="false">
    <x-slot name="input">
        <flux:select.input wire:model.live="search" placeholder="Search users..." />
    </x-slot>
    @foreach ($this->users as $user)
        <flux:select.option :value="$user->id" :wire:key="$user->id">{{ $user->name }}</flux:select.option>
    @endforeach
</flux:select>

{{-- Create option (Pro) --}}
<flux:select wire:model="projectId" variant="combobox">
    <x-slot name="input">
        <flux:select.input wire:model="search" placeholder="Start typing..." />
    </x-slot>
    @foreach ($this->projects as $project)
        <flux:select.option :value="$project->id">{{ $project->name }}</flux:select.option>
    @endforeach
    <flux:select.option.create wire:click="createProject" min-length="2">
        Create "<span wire:text="search"></span>"
    </flux:select.option.create>
</flux:select>

{{-- Options with icons --}}
<flux:select variant="listbox" placeholder="Select role...">
    <flux:select.option>
        <div class="flex items-center gap-2">
            <flux:icon.shield-check variant="mini" class="text-zinc-400" /> Owner
        </div>
    </flux:select.option>
</flux:select>
```

---

## Modal

```blade
{{-- Trigger + modal (name links them) --}}
<flux:modal.trigger name="edit-profile">
    <flux:button>Edit profile</flux:button>
</flux:modal.trigger>

<flux:modal name="edit-profile" class="md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Update profile</flux:heading>
            <flux:text class="mt-2">Make changes to your personal details.</flux:text>
        </div>
        <flux:input label="Name" placeholder="Your name" wire:model="name" />
        <div class="flex">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Save changes</flux:button>
        </div>
    </div>
</flux:modal>

{{-- Flyout / slide-over --}}
<flux:modal name="notifications" variant="flyout" position="right">
    ...
</flux:modal>

{{-- Control from Livewire PHP --}}
{{-- Flux::modal('edit-profile')->close(); --}}

{{-- Control from Alpine --}}
{{-- $flux.modal('confirm').show() / .close() --}}
{{-- $flux.modals().close()  — closes all --}}
```

**Key props:** `name`, `variant` (default/floating/bare), `flyout` (deprecated — use `variant="flyout"`), `position` (right/left/bottom), `dismissible`, `closable`, `scroll`, `wire:model`.

---

## Table

```blade
<flux:table>
    <flux:table.columns>
        <flux:table.column>Name</flux:table.column>
        <flux:table.column sortable sorted direction="desc" wire:click="sort('date')">Date</flux:table.column>
        <flux:table.column sortable wire:click="sort('amount')">Amount</flux:table.column>
        <flux:table.column align="end">Actions</flux:table.column>
    </flux:table.columns>

    <flux:table.rows>
        @foreach ($records as $record)
            <flux:table.row wire:key="{{ $record->id }}">
                <flux:table.cell>{{ $record->name }}</flux:table.cell>
                <flux:table.cell>{{ $record->date }}</flux:table.cell>
                <flux:table.cell>{{ $record->amount }}</flux:table.cell>
                <flux:table.cell align="end">
                    <flux:button size="sm" variant="ghost" icon="pencil" />
                </flux:table.cell>
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
```

**Column props:** `sortable`, `sorted`, `direction` (asc/desc), `align` (start/center/end), `sticky`.

---

## Dropdown & Context Menu

```blade
{{-- Action dropdown --}}
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Options</flux:button>
    <flux:menu>
        <flux:menu.item icon="pencil" kbd="⌘E">Edit</flux:menu.item>
        <flux:menu.item icon="document-duplicate">Duplicate</flux:menu.item>
        <flux:menu.separator />
        <flux:menu.submenu heading="Sort by">
            <flux:menu.radio checked>Name</flux:menu.radio>
            <flux:menu.radio>Date</flux:menu.radio>
        </flux:menu.submenu>
        <flux:menu.separator />
        <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
    </flux:menu>
</flux:dropdown>

{{-- Checkbox items --}}
<flux:dropdown>
    <flux:button icon:trailing="chevron-down">Permissions</flux:button>
    <flux:menu>
        <flux:menu.checkbox wire:model="read" checked>Read</flux:menu.checkbox>
        <flux:menu.checkbox wire:model="write">Write</flux:menu.checkbox>
    </flux:menu>
</flux:dropdown>

{{-- Navigation dropdown (links only) --}}
<flux:dropdown position="bottom" align="end">
    <flux:profile avatar="/img/user.png" name="Olivia Martin" />
    <flux:navmenu>
        <flux:navmenu.item href="/profile" icon="user">Profile</flux:navmenu.item>
        <flux:navmenu.item href="/billing" icon="credit-card">Billing</flux:navmenu.item>
        <flux:navmenu.item href="/logout" icon="arrow-right-start-on-rectangle" variant="danger">Logout</flux:navmenu.item>
    </flux:navmenu>
</flux:dropdown>

{{-- Context menu (right-click) --}}
<flux:context>
    <div>Right-click me</div>
    <flux:menu>
        <flux:menu.item>Open</flux:menu.item>
        <flux:menu.item variant="danger">Delete</flux:menu.item>
    </flux:menu>
</flux:context>
```

**`flux:menu.item` props:** `icon`, `icon:trailing`, `icon:variant`, `kbd`, `suffix`, `variant` (default/danger), `disabled`, `keep-open`.

---

## Toast Notifications

```blade
{{-- Required once in your layout --}}
<flux:toast />
{{-- or with custom position --}}
<flux:toast position="top end" />
```

```php
// From Livewire PHP:
Flux::toast('Your changes have been saved.');
Flux::toast(heading: 'Success!', text: 'Changes saved.', variant: 'success');
Flux::toast(text: 'Something went wrong.', variant: 'danger', duration: 0); // permanent
```

```js
// From Alpine:
$flux.toast('Your changes have been saved.')
$flux.toast({ heading: 'Success!', text: 'Done.', variant: 'success', duration: 3000 })
```

**Variants:** `success`, `warning`, `danger`. **Positions:** `bottom end` (default), `bottom center`, `bottom start`, `top end`, `top center`, `top start`.

---

## Navigation Layouts

### Sidebar layout

```blade
<flux:sidebar sticky collapsible class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
    <flux:sidebar.header>
        <flux:sidebar.brand href="/" logo="/img/logo.png" logo:dark="/img/logo-dark.png" name="Acme Inc." />
        <flux:sidebar.collapse class="lg:hidden" />
    </flux:sidebar.header>
    <flux:sidebar.search placeholder="Search..." />
    <flux:sidebar.nav>
        <flux:sidebar.item icon="home" href="/" current>Home</flux:sidebar.item>
        <flux:sidebar.item icon="inbox" badge="12" href="/inbox">Inbox</flux:sidebar.item>
        <flux:sidebar.group expandable heading="Favorites" class="grid">
            <flux:sidebar.item href="/marketing">Marketing site</flux:sidebar.item>
        </flux:sidebar.group>
    </flux:sidebar.nav>
    <flux:sidebar.spacer />
    <flux:sidebar.nav>
        <flux:sidebar.item icon="cog-6-tooth" href="/settings">Settings</flux:sidebar.item>
    </flux:sidebar.nav>
    <flux:dropdown position="top" align="start" class="max-lg:hidden">
        <flux:sidebar.profile avatar="/img/user.png" name="Olivia Martin" />
        <flux:menu>
            <flux:menu.item icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</flux:sidebar>

{{-- Mobile toggle in header --}}
<flux:header class="lg:hidden">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
    <flux:spacer />
    <flux:profile avatar="/img/user.png" />
</flux:header>

<flux:main>
    {{ $slot }}
</flux:main>
```

### Header layout

```blade
<flux:header container class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
    <flux:brand href="/" logo="/img/logo.png" name="Acme Inc." />
    <flux:navbar class="-mb-px">
        <flux:navbar.item href="/" current>Home</flux:navbar.item>
        <flux:navbar.item href="/docs">Docs</flux:navbar.item>
        <flux:dropdown>
            <flux:navbar.item icon:trailing="chevron-down">Account</flux:navbar.item>
            <flux:navmenu>
                <flux:navmenu.item href="/profile">Profile</flux:navmenu.item>
            </flux:navmenu>
        </flux:dropdown>
    </flux:navbar>
    <flux:spacer />
    <flux:dropdown position="top" align="end">
        <flux:profile avatar="/img/user.png" />
        <flux:menu>
            <flux:menu.item icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</flux:header>
<flux:main container>{{ $slot }}</flux:main>
```

### Navlist (secondary sidebar)

```blade
<flux:navlist class="w-64">
    <flux:navlist.item href="/" icon="home" current>Home</flux:navlist.item>
    <flux:navlist.group heading="Account" class="mt-4">
        <flux:navlist.item href="/profile">Profile</flux:navlist.item>
        <flux:navlist.item href="/billing" badge="32">Billing</flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
```

---

## Pagination

**Always use `flux:pagination` — never Laravel's default paginator views.**

Two patterns depending on whether you want the pagination embedded in the table or standalone:

```blade
{{-- Embedded in table (paginate prop handles it all) --}}
{{-- $orders = Order::paginate(10) --}}
<flux:table :paginate="$orders">
    ...
</flux:table>

{{-- Standalone (outside a table) --}}
<flux:pagination :paginator="$orders" />

{{-- With scroll-to-top on page change --}}
<flux:pagination :paginator="$orders" scroll-to />
<flux:pagination :paginator="$orders" scroll-to="#orders-table" />

{{-- Simple paginator (no total count — faster for large datasets) --}}
{{-- $orders = Order::simplePaginate(10) --}}
<flux:pagination :paginator="$orders" />

{{-- Table with scroll-to --}}
<flux:table :paginate="$orders" pagination:scroll-to="#orders-table">
    ...
</flux:table>
```

In Livewire, use the `WithPagination` trait and call `->paginate()` or `->simplePaginate()` on the query.

---

## Badges, Callouts & Cards

```blade
{{-- Badge --}}
<flux:badge color="green">Active</flux:badge>
<flux:badge color="red" rounded icon="x-mark">Error</flux:badge>
<flux:badge size="sm" variant="solid">Pro</flux:badge>

{{-- Callout --}}
<flux:callout variant="warning" icon="exclamation-triangle" heading="Action required" text="Please verify your email." />
<flux:callout variant="danger" icon="x-circle">
    <flux:callout.heading>Access denied</flux:callout.heading>
    <flux:callout.text>You do not have permission.</flux:callout.text>
    <x-slot name="actions">
        <flux:callout.button>Request access</flux:callout.button>
    </x-slot>
</flux:callout>

{{-- Card --}}
<flux:card class="space-y-6">
    <flux:heading size="lg">Log in</flux:heading>
    <flux:input label="Email" type="email" wire:model="email" />
    <flux:button variant="primary" class="w-full" type="submit">Continue</flux:button>
</flux:card>

{{-- Small card --}}
<flux:card size="sm" class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
    <flux:heading>Latest post</flux:heading>
    <flux:text class="mt-2">Stay up to date.</flux:text>
</flux:card>
```

---

## Avatar & Profile

```blade
{{-- Avatar --}}
<flux:avatar src="/img/user.png" name="Olivia Martin" size="lg" />
<flux:avatar name="Olivia Martin" color="auto" color:seed="{{ $user->id }}" />
<flux:avatar icon="user" circle />

{{-- Profile button (triggers dropdown) --}}
<flux:profile avatar="/img/user.png" name="Olivia Martin" icon:trailing="chevron-up-down" />
```

---

## Theming & Dark Mode

### Setup (app.css)

```css
@import "tailwindcss";
@import '../../vendor/livewire/flux/dist/flux.css';
@custom-variant dark (&:where(.dark, .dark *));
```

### Layout head

```blade
<head>
    ...
    @fluxAppearance
</head>
<body>
    ...
    @fluxScripts
</body>
```

### Dark mode controls

```blade
{{-- Simple toggle --}}
<flux:button x-data x-on:click="$flux.dark = ! $flux.dark" icon="moon" variant="subtle" />

{{-- Switch --}}
<flux:switch x-data x-model="$flux.dark" label="Dark mode" />

{{-- Dropdown (light/dark/system) --}}
<flux:dropdown x-data align="end">
    <flux:button variant="subtle" square>
        <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" />
        <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" />
    </flux:button>
    <flux:menu>
        <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Light</flux:menu.item>
        <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Dark</flux:menu.item>
        <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">System</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

### Customization

```blade
{{-- Tailwind override (use ! modifier to win conflicts) --}}
<flux:button class="bg-zinc-800! hover:bg-zinc-700!">Custom</flux:button>

{{-- Global override via data attribute --}}
<style>
    [data-flux-button] {
        @apply bg-zinc-800 dark:bg-zinc-400;
    }
</style>

{{-- Publish a component to fully customize --}}
{{-- php artisan flux:publish --}}
```

---

## Key Composition Patterns

```blade
{{-- flux:spacer pushes siblings to opposite ends --}}
<div class="flex">
    <flux:spacer />
    <flux:button variant="primary">Save</flux:button>
</div>

{{-- flux:separator --}}
<flux:separator />
<flux:separator variant="subtle" />
<flux:separator vertical variant="subtle" class="my-2" />

{{-- flux:heading + flux:text --}}
<flux:heading size="xl" level="1">Dashboard</flux:heading>
<flux:text class="mt-2 text-base">Here's what's new today.</flux:text>
<flux:heading size="lg">Section</flux:heading>  {{-- sizes: sm, base, lg, xl --}}

{{-- flux:link --}}
<flux:link href="/forgot" variant="subtle" class="text-sm">Forgot password?</flux:link>
```

---

## Common Pitfalls

- **Always `search-docs` first** — prop names change between versions.
- **Don't recreate what Flux provides.** Check the component list before writing custom HTML.
- **Table structure:** use `flux:table.columns` + `flux:table.rows`, not `flux:table.column` at the top level.
- **Select variants:** native `default`, custom `listbox`, typeahead `combobox` — listbox/combobox are Pro-only.
- **Modal control:** use `name` prop to link trigger and modal; use `Flux::modal('name')->close()` from PHP after actions.
- **Tailwind conflicts:** use the `!` modifier (`class="bg-red-500!"`) when your class loses to Flux internals.
- **Icons:** look up exact Heroicons names at heroicons.com. For others, `php artisan flux:icon <name>`.
- **Pro vs free:** combobox/listbox select, multi-select, checkbox/radio cards, and kanban are Pro-only.
