<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReviewController;

Route::get('/', [ReviewController::class, 'index']);
Route::get('/review', [ReviewController::class, 'index']);
Route::post('/proofread', [ReviewController::class, 'proofread'])->name('proofread');

// 強み分析ツール
Route::get('/strength-analyzer', function () {
    return view('strength-analyzer');
})->name('strength-analyzer');
