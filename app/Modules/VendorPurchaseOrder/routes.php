<?php

use App\Modules\VendorPurchaseOrder\Livewire\Edit;
use App\Modules\VendorPurchaseOrder\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vendor-purchase-order', Index::class)
        ->middleware('can:vendor_purchase_order.view')->name('vendor-purchase-order.index');
    Route::get('/vendor-purchase-order/create', Edit::class)
        ->middleware('can:vendor_purchase_order.create')->name('vendor-purchase-order.create');
    Route::get('/vendor-purchase-order/{vendorPurchaseOrder}/edit', Edit::class)
        ->middleware('can:vendor_purchase_order.update')->name('vendor-purchase-order.edit');
});
