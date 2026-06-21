<?php

use App\Modules\DocumentCollection\Livewire\Edit;
use App\Modules\DocumentCollection\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/document-collections', Index::class)
        ->middleware('can:document_collection.view')
        ->name('document-collection.index');

    Route::get('/document-collections/create', Edit::class)
        ->middleware('can:document_collection.create')
        ->name('document-collection.create');

    Route::get('/document-collections/{documentCollection}/edit', Edit::class)
        ->middleware('can:document_collection.update')
        ->name('document-collection.edit');
});
