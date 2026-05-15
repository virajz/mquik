<?php

use App\Modules\Barcode\Livewire\Index;
use App\Modules\Barcode\Models\BarcodeLabel;
use App\Modules\Barcode\Services\BarcodePdfService;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/barcode', Index::class)
        ->middleware('can:barcode.view')
        ->name('barcode.index');

    // Streams a label PDF — generated on first request, cached for 24h on local disk.
    Route::get('/barcode/{label}/pdf', function (BarcodeLabel $label) {
        abort_unless(auth()->user()->can('barcode.view'), 403);

        return BarcodePdfService::response($label);
    })->name('barcode.pdf');
});
