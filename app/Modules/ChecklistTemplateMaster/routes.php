<?php

use App\Modules\ChecklistTemplateMaster\Livewire\Edit;
use App\Modules\ChecklistTemplateMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/checklist-template-master', Index::class)
        ->middleware('can:checklist_template_master.view')
        ->name('checklist-template-master.index');
    Route::get('/checklist-template-master/create', Edit::class)
        ->middleware('can:checklist_template_master.create')
        ->name('checklist-template-master.create');
    Route::get('/checklist-template-master/{checklistTemplateMaster}/edit', Edit::class)
        ->middleware('can:checklist_template_master.update')
        ->name('checklist-template-master.edit');
});
