<?php

use App\Modules\ChecklistTemplateMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:checklist_template_master.view'])->group(function () {
    Route::get('/checklist-template-master', Index::class)->name('checklist-template-master.index');
});
