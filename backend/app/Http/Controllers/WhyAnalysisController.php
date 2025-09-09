<?php

namespace App\Http\Controllers;

use App\Services\WhyAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhyAnalysisController extends Controller
{
    protected $whyAnalyzer;

    public function __construct(WhyAnalyzer $whyAnalyzer)
    {
        $this->whyAnalyzer = $whyAnalyzer;
    }

    /**
     * なぜなぜ分析を開始する
     */
    public function startAnalysis(Request $request): JsonResponse
    {
        try {
            // バリデーション
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:10|max:50000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました',
                    'errors' => $validator->errors()
                ], 400);
            }

            $content = $request->input('content');

            // 分析開始
            Log::info('Starting why analysis', [
                'content_length' => mb_strlen($content)
            ]);

            $result = $this->whyAnalyzer->analyzeWhy($content);

            Log::info('Why analysis started successfully');

            return response()->json([
                'success' => true,
                'message' => 'なぜなぜ分析を開始しました',
                'data' => $result,
                'session_id' => uniqid('why_', true) // セッション管理用
            ]);

        } catch (\Exception $e) {
            Log::error('Why analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'なぜなぜ分析でエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * なぜなぜ分析の会話を継続する
     */
    public function continueAnalysis(Request $request): JsonResponse
    {
        try {
            // バリデーション
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:10|max:50000',
                'chat_history' => 'required|array',
                'user_response' => 'required|string|min:1|max:1000',
                'session_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました',
                    'errors' => $validator->errors()
                ], 400);
            }

            $content = $request->input('content');
            $chatHistory = $request->input('chat_history');
            $userResponse = $request->input('user_response');
            $sessionId = $request->input('session_id');

            // 会話継続
            Log::info('Continuing why analysis', [
                'session_id' => $sessionId,
                'history_length' => count($chatHistory)
            ]);

            $result = $this->whyAnalyzer->continueConversation($content, $chatHistory, $userResponse);

            Log::info('Why analysis continued successfully', [
                'session_id' => $sessionId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'なぜなぜ分析を継続しました',
                'data' => $result,
                'session_id' => $sessionId
            ]);

        } catch (\Exception $e) {
            Log::error('Why analysis continuation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'なぜなぜ分析の継続でエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 最終的な洞察を生成する
     */
    public function generateInsight(Request $request): JsonResponse
    {
        try {
            // バリデーション
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:10|max:50000',
                'chat_history' => 'required|array',
                'session_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました',
                    'errors' => $validator->errors()
                ], 400);
            }

            $content = $request->input('content');
            $chatHistory = $request->input('chat_history');
            $sessionId = $request->input('session_id');

            // 最終洞察生成
            Log::info('Generating final insight', [
                'session_id' => $sessionId,
                'history_length' => count($chatHistory)
            ]);

            $result = $this->whyAnalyzer->generateFinalInsight($content, $chatHistory);

            Log::info('Final insight generated successfully', [
                'session_id' => $sessionId
            ]);

            return response()->json([
                'success' => true,
                'message' => '最終的な洞察を生成しました',
                'data' => $result,
                'session_id' => $sessionId
            ]);

        } catch (\Exception $e) {
            Log::error('Final insight generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '最終洞察の生成でエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * なぜなぜ分析機能の情報を取得
     */
    public function getAnalysisInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'description' => 'なぜなぜ分析チャットボット - 記事や企画の独自性を深掘り',
                'features' => [
                    '独自性のある要素の自動特定',
                    'インタラクティブな「なぜ？」質問',
                    '段階的な深掘り分析',
                    '最終的な洞察とストーリー生成',
                    'PR価値の発見と提案'
                ],
                'analysis_steps' => [
                    '1. 記事内容から独自要素を特定',
                    '2. 「なぜ？」を繰り返し質問',
                    '3. ユーザーとの対話で深掘り',
                    '4. 本質的価値やストーリーを発見',
                    '5. PR活用のための提案'
                ],
                'max_content_length' => 50000,
                'max_response_length' => 1000,
                'reference_url' => 'https://www.keyence.co.jp/ss/general/manufacture-tips/5whys.jsp'
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
                    'service' => 'WhyAnalyzer',
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
