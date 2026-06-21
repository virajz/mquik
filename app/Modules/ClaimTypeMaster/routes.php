<?php

use App\Modules\ClaimTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:claim_type_master.view'])->group(function () {
    Route::get('/claim-type-master', Index::class)->name('claim-type-master.index');
});
