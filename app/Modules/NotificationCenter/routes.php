<?php

use App\Modules\NotificationCenter\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications', Index::class)
        ->middleware('can:notification_center.view')
        ->name('notification-center.index');
});
