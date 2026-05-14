<?php

use App\Modules\InternalPartsInquiry\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/internal-parts-inquiries', Index::class)
        ->middleware('can:internal_parts_inquiry.view')
        ->name('internal-parts-inquiry.index');

    // Edit/create routes intentionally omitted — module is mid-build (Week 3, Day 2).
    // Add them back when App\Modules\InternalPartsInquiry\Livewire\Edit lands.
});
