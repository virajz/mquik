<?php

namespace App\Modules\Inventory\Models;

// Inventory has no own Eloquent model — stock is projected from StockEntry rows.
// This file is kept as a namespace anchor so the module autoloads cleanly.
// Use StockLedger::snapshot() / currentQty() / fifoLayers() for data access.
