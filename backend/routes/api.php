<?php

use App\Http\Controllers\PrTimesController;
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