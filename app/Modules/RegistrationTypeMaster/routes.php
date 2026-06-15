<?php

use App\Modules\RegistrationTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:registration_type_master.view'])->group(function () {
    Route::get('/registration-type-master', Index::class)->name('registration-type-master.index');
});
