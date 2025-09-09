<?php

namespace App\Http\Controllers;

use App\Services\PrTimesApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags PRTIMES API
 */
class PrTimesController extends Controller
{
    protected PrTimesApiService $prTimesApiService;

    public function __construct(PrTimesApiService $prTimesApiService)
    {
        $this->prTimesApiService = $prTimesApiService;
    }

    /**
     * 企業一覧取得
     *
     * PRTIMES APIから企業一覧をページング付きで取得します。
     *
     * @operationId getCompanies
     * @response array
     */
    public function getCompanies(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 100);
            $page = $request->get('page', 0);
            
            $data = $this->prTimesApiService->getCompanies($perPage, $page);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 企業詳細取得
     *
     * 特定の企業の詳細情報を取得します。
     *
     * @operationId getCompany
     * @response object
     */
    public function getCompany(int $companyId): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getCompany($companyId);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * プレスリリース一覧取得
     *
     * プレスリリース一覧をページング付きで取得します。日付範囲でのフィルタリングも可能です。
     *
     * @operationId getReleases
     * @response array
     */
    public function getReleases(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 100);
            $page = $request->get('page', 0);
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            
            $data = $this->prTimesApiService->getReleases($perPage, $page, $fromDate, $toDate);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 企業別プレスリリース一覧取得
     *
     * 特定の企業のプレスリリース一覧を取得します。
     *
     * @operationId prTimes.getCompanyReleases
     * @response array
     */
    public function getCompanyReleases(Request $request, int $companyId): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 100);
            $page = $request->get('page', 0);
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            
            $data = $this->prTimesApiService->getCompanyReleases($companyId, $perPage, $page, $fromDate, $toDate);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * プレスリリース詳細取得
     *
     * 特定のプレスリリースの詳細情報を取得します。
     *
     * @operationId prTimes.getRelease
     * @response array
     */
    public function getRelease(int $companyId, int $releaseId): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getRelease($companyId, $releaseId);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * プレスリリース統計取得
     *
     * 特定のプレスリリースの閲覧統計などの情報を取得します。
     *
     * @operationId prTimes.getReleaseStatistics
     * @response array
     */
    public function getReleaseStatistics(int $companyId, int $releaseId): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getReleaseStatistics($companyId, $releaseId);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * カテゴリ一覧取得
     *
     * プレスリリースカテゴリ一覧をページング付きで取得します。
     *
     * @operationId getCategories
     * @response array
     */
    public function getCategories(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 100);
            $page = $request->get('page', 0);
            
            $data = $this->prTimesApiService->getCategories($perPage, $page);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * カテゴリ詳細取得
     *
     * 特定のカテゴリの詳細情報を取得します。
     *
     * @operationId prTimes.getCategory
     * @response array
     */
    public function getCategory(int $categoryId): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getCategory($categoryId);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * カテゴリ別プレスリリース一覧取得
     *
     * 特定のカテゴリのプレスリリース一覧を取得します。
     *
     * @operationId prTimes.getCategoryReleases
     * @response array
     */
    public function getCategoryReleases(Request $request, int $categoryId): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 100);
            $page = $request->get('page', 0);
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            
            $data = $this->prTimesApiService->getCategoryReleases($categoryId, $perPage, $page, $fromDate, $toDate);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 動画付きプレスリリース一覧取得
     *
     * 動画が付いているプレスリリース一覧を取得します。
     *
     * @operationId prTimes.getMovieReleases
     * @response array
     */
    public function getMovieReleases(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 100);
            $page = $request->get('page', 0);
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            
            $data = $this->prTimesApiService->getMovieReleases($perPage, $page, $fromDate, $toDate);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 都道府県一覧取得
     *
     * 都道府県一覧を取得します。
     *
     * @operationId prTimes.getPrefectures
     * @response array
     */
    public function getPrefectures(): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getPrefectures();
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * リリースタイプ一覧取得
     *
     * プレスリリースタイプ一覧を取得します。
     *
     * @operationId prTimes.getReleaseTypes
     * @response array
     */
    public function getReleaseTypes(): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getReleaseTypes();
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}