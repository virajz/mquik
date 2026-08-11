<?php

use App\Modules\VendorPurchaseInquiry\Livewire\Edit;
use App\Modules\VendorPurchaseInquiry\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vendor-purchase-inquiry', Index::class)
        ->middleware('can:vendor_purchase_inquiry.view')->name('vendor-purchase-inquiry.index');
    Route::get('/vendor-purchase-inquiry/create', Edit::class)
        ->middleware('can:vendor_purchase_inquiry.create')->name('vendor-purchase-inquiry.create');
    // Opening a record needs only view — an advisor reads the quotes without
    // being able to change the RFQ. save() still authorises update.
    Route::get('/vendor-purchase-inquiry/{vendorPurchaseInquiry}/edit', Edit::class)
        ->middleware('can:vendor_purchase_inquiry.view')->name('vendor-purchase-inquiry.edit');
});
