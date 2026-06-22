<?php

use App\Modules\EstimateTemplateMaster\Livewire\Edit;
use App\Modules\EstimateTemplateMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/estimate-templates', Index::class)
        ->middleware('can:estimate_template_master.view')
        ->name('estimate-template-master.index');

    Route::get('/estimate-templates/create', Edit::class)
        ->middleware('can:estimate_template_master.create')
        ->name('estimate-template-master.create');

    Route::get('/estimate-templates/{estimateTemplate}/edit', Edit::class)
        ->middleware('can:estimate_template_master.update')
        ->name('estimate-template-master.edit');
});
