<?php

use App\Modules\ServiceSpecialistMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:service_specialist_master.view'])->group(function () {
    Route::get('/service-specialist-master', Index::class)->name('service-specialist-master.index');
});
