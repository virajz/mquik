<?php

use App\Modules\ChecklistGroupMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:checklist_group_master.view'])->group(function () {
    Route::get('/checklist-group-master', Index::class)->name('checklist-group-master.index');
});
