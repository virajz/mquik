<?php

use App\Modules\VisitorManagement\Livewire\Edit;
use App\Modules\VisitorManagement\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/visitor-management', Index::class)
        ->middleware('can:visitor_management.view')->name('visitor-management.index');
    Route::get('/visitor-management/create', Edit::class)
        ->middleware('can:visitor_management.create')->name('visitor-management.create');
    Route::get('/visitor-management/{visitorVisit}/edit', Edit::class)
        ->middleware('can:visitor_management.update')->name('visitor-management.edit');
});
