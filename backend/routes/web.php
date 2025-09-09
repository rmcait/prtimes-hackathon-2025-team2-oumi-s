<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReviewController;

Route::get('/', [ReviewController::class, 'index']);
Route::get('/review', [ReviewController::class, 'index']);
Route::post('/proofread', [ReviewController::class, 'proofread'])->name('proofread');
