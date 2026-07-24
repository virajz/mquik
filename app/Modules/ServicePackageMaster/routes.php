<?php

use App\Modules\ServicePackageMaster\Livewire\Edit;
use App\Modules\ServicePackageMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/service-package-master', Index::class)
        ->middleware('can:service_package_master.view')
        ->name('service-package-master.index');
    Route::get('/service-package-master/create', Edit::class)
        ->middleware('can:service_package_master.create')
        ->name('service-package-master.create');
    Route::get('/service-package-master/{servicePackageMaster}/edit', Edit::class)
        ->middleware('can:service_package_master.update')
        ->name('service-package-master.edit');
});
