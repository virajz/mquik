<?php

use App\Modules\RefundTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:refund_type_master.view'])->group(function () {
    Route::get('/refund-type-master', Index::class)->name('refund-type-master.index');
});
