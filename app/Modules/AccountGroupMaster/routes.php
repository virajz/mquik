<?php

use App\Modules\AccountGroupMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:account_group_master.view'])->group(function () {
    Route::get('/account-group-master', Index::class)->name('account-group-master.index');
});
