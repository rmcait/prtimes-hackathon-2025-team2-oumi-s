<?php

namespace App\Http\Controllers;

use App\Services\StrengthAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StrengthAnalysisController extends Controller
{
    protected $strengthAnalyzer;

    public function __construct(StrengthAnalyzer $strengthAnalyzer)
    {
        $this->strengthAnalyzer = $strengthAnalyzer;
    }

    /**
     * Markdown記事の強みを分析する
     */
    public function analyzeStrengths(Request $request): JsonResponse
    {
        try {
            // バリデーション
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:10|max:50000',
                'persona' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました',
                    'errors' => $validator->errors()
                ], 400);
            }

            $content = $request->input('content');
            $persona = $request->input('persona');

            // 分析実行
            Log::info('Starting strength analysis', [
                'content_length' => mb_strlen($content),
                'persona' => $persona
            ]);

            $result = $this->strengthAnalyzer->analyzeStrengths($content, $persona);

            // 結果の検証
            if (!$this->strengthAnalyzer->validateAnalysisResult($result)) {
                Log::error('Analysis result validation failed', $result);
                return response()->json([
                    'success' => false,
                    'message' => '分析結果の形式が正しくありません'
                ], 500);
            }

            // ファイル情報を追加
            $result['file_info'] = [
                'uploaded_at' => now()->toISOString(),
                'content_length' => mb_strlen($content)
            ];

            Log::info('Strength analysis completed successfully', [
                'strengths_count' => count($result['strengths'] ?? [])
            ]);

            return response()->json([
                'success' => true,
                'message' => '記事の強み分析が完了しました',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Strength analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '分析処理でエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * 分析機能の情報を取得
     */
    public function getAnalysisInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'description' => 'Markdown形式の記事コンテンツから企業・組織の強みを分析',
                'input_format' => 'Markdown形式のテキスト',
                'max_content_length' => 50000,
                'media_hook_elements' => [
                    'time_seasonality' => '時代性/季節性',
                    'images_video' => '画像/映像',
                    'contradiction_conflict' => '矛盾/対立',
                    'regional_focus' => '地域性',
                    'topicality' => '話題性',
                    'social_public_interest' => '社会性/公共性',
                    'novelty_uniqueness' => '新規性/独自性',
                    'superlative_rarity' => '特級性/希少性',
                    'unexpectedness' => '意外性'
                ],
                'output_features' => [
                    '強み抽出',
                    'メディアフック9要素による分類',
                    'インパクトスコア評価（低/中/高）',
                    '不足要素の指摘',
                    '改善提案',
                    '特に優れた強みのハイライト'
                ],
                'reference_url' => 'https://prtimes.jp/magazine/media-hook/'
            ]
        ]);
    }

    /**
     * ヘルスチェック
     */
    public function healthCheck(): JsonResponse
    {
        try {
            // OpenAI APIキーの確認
            $apiKey = config('services.openai.api_key');
            $hasApiKey = !empty($apiKey);

            return response()->json([
                'success' => true,
                'status' => 'healthy',
                'data' => [
                    'service' => 'StrengthAnalyzer',
                    'version' => '1.0.0',
                    'openai_configured' => $hasApiKey,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}