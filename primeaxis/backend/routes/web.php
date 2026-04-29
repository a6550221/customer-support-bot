<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'PrimeAxis CRM API is running.', 'version' => '1.0']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/index.php', function () {
    return response()->json(['status' => 'ok']);
});
