<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Per-user import/export progress — each user only sees their own jobs.
Broadcast::channel('exports.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('imports.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
