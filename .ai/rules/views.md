---
paths:
  - 'app/Modules/**/views/**'
---

# Views

## Dates display DD/MM/YYYY (Indian standard), no exceptions
Human-facing dates use Carbon format d/m/Y (with h:i A for times) — never "d M Y" or US order. Every <flux:date-picker> must carry locale="en-IN" (Flux reads navigator.language otherwise, which shows MM/DD for most browsers; the html lang attr is NOT consulted for the input text). Machine formats (Y-m-d wire state, CSV import/export, DB) stay ISO. APP_LOCALE is en_IN.

## Dependent Flux pickers need a wire:key tied to their option set
Any searchable/combobox <flux:select> whose options come from a computed that starts empty (cascading pickers: state→city→area, department→service type, department→advisor, category→sub-category) MUST carry a wire:key derived from the current option ids, e.g. wire:key="city-{{ $cities->pluck('id')->implode('-') }}".

Why: Flux renders its "No results found" row as <ui-option-empty ... wire:ignore> and hides it purely by JS setting data-hidden. When the picker first renders with no options that row is visible; Livewire then morphs the real options in around it without the custom element re-initialising, so "No results found" sits stranded above a full list, and the button text/disabled state stay stale ("Pick a department first" while options exist). A changing wire:key makes Livewire replace the element instead of morphing it, so it re-initialises correctly.

Do NOT fix this by overriding Flux's own blade stubs.
