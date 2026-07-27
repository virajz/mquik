<?php

use App\Modules\SurveyorInspection\Livewire\Edit;
use App\Modules\SurveyorInspection\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/surveyor-inspection', Index::class)
        ->middleware('can:surveyor_inspection.view')->name('surveyor-inspection.index');
    Route::get('/surveyor-inspection/create', Edit::class)
        ->middleware('can:surveyor_inspection.create')->name('surveyor-inspection.create');
    Route::get('/surveyor-inspection/{surveyorInspection}/edit', Edit::class)
        ->middleware('can:surveyor_inspection.update')->name('surveyor-inspection.edit');
});
