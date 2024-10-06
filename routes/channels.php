<?php

use Illuminate\Support\Facades\Broadcast;
// use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Broadcast::channel('delivery.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
//     // return true;
// });


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
