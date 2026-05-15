<?php

namespace App\Modules\Inventory\Livewire;

// Inventory is a read-only projection — there is no create/edit form.
// Stock changes happen through StockEntry::record() called by other modules.
// This file is kept as a namespace placeholder so Livewire auto-discovery
// doesn't break if cached manifests reference inventory.form.
