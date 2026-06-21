<?php

use App\Modules\FollowUpModeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:follow_up_mode_master.view'])->group(function () {
    Route::get('/follow-up-mode-master', Index::class)->name('follow-up-mode-master.index');
});
