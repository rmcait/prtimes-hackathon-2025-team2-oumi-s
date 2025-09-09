<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrTimesApiService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.prtimes.base_url');
        $this->token = config('services.prtimes.token');
    }

    private function makeRequest(string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . '/api' . $endpoint;
        
        // Log::info('PRTIMES API Request', [
        //     'url' => $url,
        //     'params' => $params
        // ]);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
        ])->get($url, $params);

        if ($response->failed()) {
            // Log::error('PRTIMES API Error', [
            //     'status' => $response->status(),
            //     'response' => $response->body()
            // ]);
            
            throw new \Exception("PRTIMES API request failed. URL: {$url}, Status: {$response->status()}, Response: " . $response->body());
        }

        return $response->json();
    }

    public function getCompanies(int $perPage = 100, int $page = 0): array
    {
        return $this->makeRequest('/companies', [
            'per_page' => min($perPage, 999),
            'page' => min($page, 99)
        ]);
    }

    public function getCompany(int $companyId): array
    {
        return $this->makeRequest("/companies/{$companyId}");
    }

    public function getCompaniesByIndustry(int $industryId, int $perPage = 100, int $page = 0): array
    {
        return $this->makeRequest("/industries/{$industryId}/companies", [
            'per_page' => min($perPage, 999),
            'page' => min($page, 99)
        ]);
    }

    public function getReleases(int $perPage = 100, int $page = 0, ?string $fromDate = null, ?string $toDate = null): array
    {
        $params = [
            'per_page' => min($perPage, 999),
            'page' => min($page, 99)
        ];

        if ($fromDate) {
            $params['from_date'] = $fromDate;
        }
        if ($toDate) {
            $params['to_date'] = $toDate;
        }

        return $this->makeRequest('/releases', $params);
    }

    public function getCompanyReleases(int $companyId, int $perPage = 100, int $page = 0, ?string $fromDate = null, ?string $toDate = null): array
    {
        $params = [
            'per_page' => min($perPage, 999),
            'page' => min($page, 99)
        ];

        if ($fromDate) {
            $params['from_date'] = $fromDate;
        }
        if ($toDate) {
            $params['to_date'] = $toDate;
        }

        return $this->makeRequest("/companies/{$companyId}/releases", $params);
    }

    public function getRelease(int $companyId, int $releaseId): array
    {
        return $this->makeRequest("/companies/{$companyId}/releases/{$releaseId}");
    }

    public function getReleaseStatistics(int $companyId, int $releaseId): array
    {
        return $this->makeRequest("/companies/{$companyId}/releases/{$releaseId}/statistics");
    }

    public function getReleaseKeywords(int $companyId, int $releaseId): array
    {
        return $this->makeRequest("/companies/{$companyId}/releases/{$releaseId}/keywords");
    }

    public function getReleaseLocations(int $companyId, int $releaseId): array
    {
        return $this->makeRequest("/companies/{$companyId}/releases/{$releaseId}/locations");
    }

    public function getCategories(int $perPage = 100, int $page = 0): array
    {
        return $this->makeRequest('/categories', [
            'per_page' => min($perPage, 999),
            'page' => min($page, 99)
        ]);
    }

    public function getCategory(int $categoryId): array
    {
        return $this->makeRequest("/categories/{$categoryId}");
    }

    public function getCategoryReleases(int $categoryId, int $perPage = 100, int $page = 0, ?string $fromDate = null, ?string $toDate = null): array
    {
        $params = [
            'per_page' => min($perPage, 999),
            'page' => min($page, 99)
        ];

        if ($fromDate) {
            $params['from_date'] = $fromDate;
        }
        if ($toDate) {
            $params['to_date'] = $toDate;
        }

        return $this->makeRequest("/categories/{$categoryId}/releases", $params);
    }

    public function getCategoryMovieReleases(int $categoryId, int $perPage = 100, int $page = 0, ?string $fromDate = null, ?string $toDate = null): array
    {
        $params = [
            'per_page' => min($perPage, 999),
            'page' => min($page, 99)
        ];

        if ($fromDate) {
            $params['from_date'] = $fromDate;
        }
        if ($toDate) {
            $params['to_date'] = $toDate;
        }

        return $this->makeRequest("/categories/{$categoryId}/releases/movie", $params);
    }

    public function getMovieReleases(int $perPage = 100, int $page = 0, ?string $fromDate = null, ?string $toDate = null): array
    {
        $params = [
            'per_page' => min($perPage, 999),
            'page' => min($page, 99)
        ];

        if ($fromDate) {
            $params['from_date'] = $fromDate;
        }
        if ($toDate) {
            $params['to_date'] = $toDate;
        }

        return $this->makeRequest('/releases/movie', $params);
    }

    public function getPrefectures(): array
    {
        return $this->makeRequest('/prefectures');
    }

    public function getPrefectureReleases(int $prefectureId, int $perPage = 100, int $page = 0, ?string $fromDate = null, ?string $toDate = null): array
    {
        $params = [
            'per_page' => min($perPage, 999),
            'page' => min($page, 99)
        ];

        if ($fromDate) {
            $params['from_date'] = $fromDate;
        }
        if ($toDate) {
            $params['to_date'] = $toDate;
        }

        return $this->makeRequest("/prefectures/{$prefectureId}/releases", $params);
    }

    public function getReleaseTypes(): array
    {
        return $this->makeRequest('/release_types');
    }
}