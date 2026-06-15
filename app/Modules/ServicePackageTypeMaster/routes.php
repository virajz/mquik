<?php

use App\Modules\ServicePackageTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:service_package_type_master.view'])->group(function () {
    Route::get('/service-package-type-master', Index::class)->name('service-package-type-master.index');
});
