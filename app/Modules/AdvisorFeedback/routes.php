<?php

use App\Modules\AdvisorFeedback\Livewire\Edit;
use App\Modules\AdvisorFeedback\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/advisor-feedback', Index::class)
        ->middleware('can:advisor_feedback.view')->name('advisor-feedback.index');
    Route::get('/advisor-feedback/create', Edit::class)
        ->middleware('can:advisor_feedback.create')->name('advisor-feedback.create');
    Route::get('/advisor-feedback/{advisorFeedback}/edit', Edit::class)
        ->middleware('can:advisor_feedback.update')->name('advisor-feedback.edit');
});
