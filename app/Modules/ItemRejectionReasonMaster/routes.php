<?php

use App\Modules\ItemRejectionReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:item_rejection_reason_master.view'])->group(function () {
    Route::get('/item-rejection-reason-master', Index::class)->name('item-rejection-reason-master.index');
});
