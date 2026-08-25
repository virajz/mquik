---
paths:
  - 'app/Modules/**/views/**'
---

# Views

## Dates display DD/MM/YYYY (Indian standard), no exceptions
Human-facing dates use Carbon format d/m/Y (with h:i A for times) — never "d M Y" or US order. Every <flux:date-picker> must carry locale="en-IN" (Flux reads navigator.language otherwise, which shows MM/DD for most browsers; the html lang attr is NOT consulted for the input text). Machine formats (Y-m-d wire state, CSV import/export, DB) stay ISO. APP_LOCALE is en_IN.
