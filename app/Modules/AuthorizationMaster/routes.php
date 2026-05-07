<?php

use App\Modules\AuthorizationMaster\Livewire\Index;
use App\Modules\AuthorizationMaster\Livewire\Users;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:authorization_master.view'])->group(function () {
    Route::get('/authorization-master', Index::class)->name('authorization-master.index');
    Route::get('/authorization-master/users', Users::class)->name('authorization-master.users');
});
