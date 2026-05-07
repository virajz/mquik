<?php

use App\Modules\PageListMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:page_list_master.view'])->group(function () {
    Route::get('/page-list-master', Index::class)->name('page-list-master.index');
});
