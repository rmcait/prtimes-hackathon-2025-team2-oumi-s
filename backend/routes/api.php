<?php

use App\Http\Controllers\PrTimesController;
use App\Http\Controllers\StrengthAnalysisController;
use App\Http\Controllers\WhyAnalysisController;
use App\Http\Controllers\ProofreadController;
use Illuminate\Support\Facades\Route;

Route::prefix('prtimes')->group(function () {
    // Companies
    Route::get('/companies', [PrTimesController::class, 'getCompanies']);
    Route::get('/companies/{companyId}', [PrTimesController::class, 'getCompany']);
    
    // Company Releases
    Route::get('/companies/{companyId}/releases', [PrTimesController::class, 'getCompanyReleases']);
    Route::get('/companies/{companyId}/releases/{releaseId}', [PrTimesController::class, 'getRelease']);
    Route::get('/companies/{companyId}/releases/{releaseId}/statistics', [PrTimesController::class, 'getReleaseStatistics']);
    
    // Releases
    Route::get('/releases', [PrTimesController::class, 'getReleases']);
    Route::get('/releases/movie', [PrTimesController::class, 'getMovieReleases']);
    
    // Categories
    Route::get('/categories', [PrTimesController::class, 'getCategories']);
    Route::get('/categories/{categoryId}', [PrTimesController::class, 'getCategory']);
    Route::get('/categories/{categoryId}/releases', [PrTimesController::class, 'getCategoryReleases']);
    
    // Reference Data
    Route::get('/prefectures', [PrTimesController::class, 'getPrefectures']);
    Route::get('/release-types', [PrTimesController::class, 'getReleaseTypes']);
});

// Strength Analysis API
Route::prefix('strength-analysis')->group(function () {
    // 記事強み分析
    Route::post('/analyze', [StrengthAnalysisController::class, 'analyzeStrengths']);
    
    // リリースタイプ取得
    Route::get('/release-types', [StrengthAnalysisController::class, 'getReleaseTypes']);
    
    // 情報取得
    Route::get('/info', [StrengthAnalysisController::class, 'getAnalysisInfo']);
    Route::get('/health', [StrengthAnalysisController::class, 'healthCheck']);
});

// Why Analysis API (なぜなぜ分析チャットボット)
Route::prefix('why-analysis')->group(function () {
    // なぜなぜ分析開始
    Route::post('/start', [WhyAnalysisController::class, 'startAnalysis']);
    
    // 会話継続
    Route::post('/continue', [WhyAnalysisController::class, 'continueAnalysis']);
    
    // 最終洞察生成
    Route::post('/insight', [WhyAnalysisController::class, 'generateInsight']);
    
    // 情報取得
    Route::get('/info', [WhyAnalysisController::class, 'getAnalysisInfo']);
    Route::get('/health', [WhyAnalysisController::class, 'healthCheck']);
});

// Proofreading API
Route::post('/proofread', [ProofreadController::class, 'proofread']);
