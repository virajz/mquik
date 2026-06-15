<?php

use App\Modules\CustomerApprovalTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:customer_approval_type_master.view'])->group(function () {
    Route::get('/customer-approval-type-master', Index::class)->name('customer-approval-type-master.index');
});
