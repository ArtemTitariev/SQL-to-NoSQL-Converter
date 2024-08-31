<?php

use Illuminate\Support\Facades\Broadcast;
// use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('delivery.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
    // return true;
});


Broadcast::channel(
    'users.{userId}.converts.{convertId}.ReadSchema',
    function ($user, $userId, $convertId) {
        $convert = \App\Models\Convert::find($convertId);

        if (!$convert) {
            return false;
        }

        return (int) $user->id === (int) $userId &&
            (int) $convert->id === (int) $convertId &&
            (int) $convert->user->id === (int) $userId;
    }
);
