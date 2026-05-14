<?php

use App\Modules\InternalPartsInquiry\Livewire\Edit;
use App\Modules\InternalPartsInquiry\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/internal-parts-inquiries', Index::class)
        ->middleware('can:internal_parts_inquiry.view')
        ->name('internal-parts-inquiry.index');

    Route::get('/internal-parts-inquiries/create', Edit::class)
        ->middleware('can:internal_parts_inquiry.create')
        ->name('internal-parts-inquiry.create');

    Route::get('/internal-parts-inquiries/{internalPartsInquiry}/edit', Edit::class)
        ->middleware('can:internal_parts_inquiry.update')
        ->name('internal-parts-inquiry.edit');
});
