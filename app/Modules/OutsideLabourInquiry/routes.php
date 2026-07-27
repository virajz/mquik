<?php

use App\Modules\OutsideLabourInquiry\Livewire\Edit;
use App\Modules\OutsideLabourInquiry\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/outside-labour-inquiry', Index::class)
        ->middleware('can:outside_labour_inquiry.view')->name('outside-labour-inquiry.index');
    Route::get('/outside-labour-inquiry/create', Edit::class)
        ->middleware('can:outside_labour_inquiry.create')->name('outside-labour-inquiry.create');
    Route::get('/outside-labour-inquiry/{outsideLabourInquiry}/edit', Edit::class)
        ->middleware('can:outside_labour_inquiry.update')->name('outside-labour-inquiry.edit');
});
