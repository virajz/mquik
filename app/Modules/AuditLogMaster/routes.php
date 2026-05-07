<?php

use App\Modules\AuditLogMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:audit_log_master.view'])->group(function () {
    Route::get('/audit-log-master', Index::class)->name('audit-log-master.index');
});
