<?php

use App\Modules\ServicePackageMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/service-package-master', Index::class)
        ->middleware('can:service_package_master.view')
        ->name('service-package-master.index');
});
