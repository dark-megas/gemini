<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotManController;



Route::get('/', function () {
    return view('welcome');
});



Route::get('/botman/chat', function () {
    return view('botman'); // renderiza resources/views/botman.blade.php
});
