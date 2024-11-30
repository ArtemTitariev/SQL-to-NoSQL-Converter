<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel(
    'users.{userId}.converts.{convertId}.ReadSchema',
    function ($user, $userId, $convertId) {
        return \App\Models\Convert::canAccess($user, $userId, $convertId);
    }
);

Broadcast::channel(
    'users.{userId}.converts.{convertId}.ProcessRelationships',
    function ($user, $userId, $convertId) {
        return \App\Models\Convert::canAccess($user, $userId, $convertId);
    }
);
