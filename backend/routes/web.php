<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('review');
});

// 強み分析ツール
Route::get('/strength-analyzer', function () {
    return view('strength-analyzer');
})->name('strength-analyzer');
