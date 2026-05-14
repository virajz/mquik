<?php

use App\Modules\Appointment\Livewire\Edit;
use App\Modules\Appointment\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/appointments', Index::class)
        ->middleware('can:appointment.view')
        ->name('appointment.index');

    Route::get('/appointments/create', Edit::class)
        ->middleware('can:appointment.create')
        ->name('appointment.create');

    Route::get('/appointments/{appointment}/edit', Edit::class)
        ->middleware('can:appointment.update')
        ->name('appointment.edit');
});
