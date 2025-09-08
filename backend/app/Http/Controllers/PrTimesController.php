<?php

namespace App\Http\Controllers;

use App\Services\PrTimesApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrTimesController extends Controller
{
    protected PrTimesApiService $prTimesApiService;

    public function __construct(PrTimesApiService $prTimesApiService)
    {
        $this->prTimesApiService = $prTimesApiService;
    }

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

    public function getCompany(int $companyId): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getCompany($companyId);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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

    public function getRelease(int $companyId, int $releaseId): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getRelease($companyId, $releaseId);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getReleaseStatistics(int $companyId, int $releaseId): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getReleaseStatistics($companyId, $releaseId);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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

    public function getCategory(int $categoryId): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getCategory($categoryId);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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

    public function getPrefectures(): JsonResponse
    {
        try {
            $data = $this->prTimesApiService->getPrefectures();
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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