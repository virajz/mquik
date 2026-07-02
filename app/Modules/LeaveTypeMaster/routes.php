<?php

use App\Modules\LeaveTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:leave_type_master.view'])->group(function () {
    Route::get('/leave-type-master', Index::class)->name('leave-type-master.index');
});
