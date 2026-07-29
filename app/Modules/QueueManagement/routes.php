<?php

use App\Modules\QueueManagement\Livewire\Edit;
use App\Modules\QueueManagement\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/queue-management', Index::class)
        ->middleware('can:queue_management.view')->name('queue-management.index');
    Route::get('/queue-management/create', Edit::class)
        ->middleware('can:queue_management.create')->name('queue-management.create');
    Route::get('/queue-management/{serviceQueue}/edit', Edit::class)
        ->middleware('can:queue_management.update')->name('queue-management.edit');
});
