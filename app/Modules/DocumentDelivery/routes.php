<?php

use App\Modules\DocumentDelivery\Livewire\Edit;
use App\Modules\DocumentDelivery\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/document-delivery', Index::class)
        ->middleware('can:document_delivery.view')->name('document-delivery.index');
    Route::get('/document-delivery/create', Edit::class)
        ->middleware('can:document_delivery.create')->name('document-delivery.create');
    Route::get('/document-delivery/{documentDelivery}/edit', Edit::class)
        ->middleware('can:document_delivery.update')->name('document-delivery.edit');
});
