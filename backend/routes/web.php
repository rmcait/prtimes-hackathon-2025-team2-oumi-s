<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReviewController;

// Route::get('/', [ReviewController::class, 'index']);
Route::get('/', function () {
    return view('comment-review');
});
Route::get('/review', [ReviewController::class, 'index']);
Route::post('/proofread', [ReviewController::class, 'proofread'])->name('proofread');

// 強み分析ツール
Route::get('/strength-analyzer', function () {
    return view('strength-analyzer');
})->name('strength-analyzer');

// なぜなぜ分析チャットbot
Route::get('/why-analyzer', function () {
    return view('why-analyzer');
})->name('why-analyzer');

// 統合AI記事レビュー画面
Route::get('/integrated-review', function () {
    return view('integrated-review');
})->name('integrated-review');

// GoogleDocsライク コメントレビュー画面
Route::get('/comment-review', function () {
    return view('comment-review');
})->name('comment-review');
