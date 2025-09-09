<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('review');
});

// 強み分析ツール
Route::get('/strength-analyzer', function () {
    return view('strength-analyzer');
})->name('strength-analyzer');

// なぜなぜ分析チャットbot
Route::get('/why-analyzer', function () {
    return view('why-analyzer');
})->name('why-analyzer');
