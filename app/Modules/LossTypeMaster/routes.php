<?php

use App\Modules\LossTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:loss_type_master.view'])->group(function () {
    Route::get('/loss-type-master', Index::class)->name('loss-type-master.index');
});
