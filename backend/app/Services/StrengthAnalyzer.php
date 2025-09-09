<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class StrengthAnalyzer
{
    protected $apiKey;
    protected $prtimesApiKey;
    
    private $mediaHookElements = [
        'time_seasonality' => '時代性/季節性',
        'images_video' => '画像/映像',
        'contradiction_conflict' => '矛盾/対立',
        'regional_focus' => '地域性',
        'topicality' => '話題性',
        'social_public_interest' => '社会性/公共性',
        'novelty_uniqueness' => '新規性/独自性',
        'superlative_rarity' => '特級性/希少性',
        'unexpectedness' => '意外性'
    ];

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->prtimesApiKey = config('services.prtimes.api_key', env('PRTIMES_API_KEY'));
    }

    public function getReleaseTypes(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->prtimesApiKey,
                    'Accept' => 'application/json',
                ])
                ->get('https://hackathon.stg-prtimes.net/api/release_types');

            if ($response->failed()) {
                throw new \Exception('PRTIMES API request failed: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'リリースタイプの取得に失敗しました: ' . $e->getMessage(),
                'default_types' => [
                    ['id' => 1, 'name' => 'イベント'],
                    ['id' => 2, 'name' => '新商品'],
                    ['id' => 3, 'name' => 'サービス'],
                    ['id' => 4, 'name' => '企業']
                ]
            ];
        }
    }

    public function analyzeStrengths(string $markdownContent, ?string $persona = null, ?string $releaseType = null): array
    {
        if (empty($this->apiKey)) {
            // デモ用のモックレスポンス
            return $this->getMockStrengthAnalysis($markdownContent, $persona, $releaseType);
        }

    $prompt = $this->buildAnalysisPrompt($markdownContent, $persona, $releaseType);
        
        $response = Http::withToken($this->apiKey)
            ->timeout(60) // 分析は時間がかかる可能性があるため長めに設定
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3, // 安定した分析結果のため低めに設定
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI API request failed: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        
        if (empty($content)) {
            throw new \Exception('OpenAI API returned empty response. Response: ' . $response->body());
        }

        return $this->parseAnalysisResult($content, $markdownContent);
    }

    private function getSystemPrompt(): string
    {
        return "あなたは PR TIMES の記事分析エキスパートです。

与えられた記事はプレスリリース（企業の新商品・サービス発表、イベント告知、調査結果発表など）として配信される予定のコンテンツです。プレスリリースの効果的な情報発信の観点から分析を行ってください。
        
与えられた Markdown 形式の記事（タイトル・画像・本文を含む全文）から企業や組織の強みを抽出し、メディアフック9要素で分類してください。

記事構造の想定:
- タイトル（# 見出し）
- 画像（![alt](url) 形式）
- 本文（複数の見出しと段落）

メディアフック9要素:
1. 時代性/季節性 (Time/Seasonality): 時流や季節に合った内容
2. 画像/映像 (Images/Video): インパクトのある視覚的要素（画像の存在も評価対象）
3. 矛盾/対立 (Contradiction/Conflict): 常識に反する内容や対比
4. 地域性 (Regional): 特定地域に関する情報
5. 話題性 (Topicality): 現在のトレンドや話題
6. 社会性/公共性 (Social/Public Interest): 社会的意義や公共の利益
7. 新規性/独自性 (Novelty/Uniqueness): 初回や独自のアプローチ
8. 特級性/希少性 (Superlative/Rarity): 数値による特別感や希少性
9. 意外性 (Unexpectedness): 予想外の展開や驚きの要素

リリースタイプ別の分析ポイント:
- 商品サービス: 機能性、競合優位性、市場価値に注目
- イベント: 参加価値、体験の独自性、話題性に注目
- キャンペーン: 限定性、お得感、参加しやすさに注目
- 経営情報: 信頼性、透明性、将来性に注目
- 調査レポート: データの信頼性、社会的意義、新発見に注目
- 人物: 専門性、人間性、影響力に注目
- 上場企業決算発表: 成長性、安定性、投資価値に注目
- その他: コンテンツの独自性と価値に注目

画像について:
- 記事に![](画像URL)が含まれる場合、「画像/映像」要素として強みを評価してください
- 画像のalt属性やファイル名からも内容を推測し、分析に活用してください

分析結果は必ず JSON 形式で返してください。";
    }

    private function buildAnalysisPrompt(string $content, ?string $persona = null, ?string $releaseType = null): string
    {
        $personaText = $persona ? "【企業が伝えたい生活者の人物像】\n{$persona}\n" : "";
        $releaseTypeText = $releaseType ? "【リリースタイプ】\n{$releaseType}\n" : "";
        return "以下のプレスリリース用Markdown記事（タイトル・画像・本文を含む全文）を分析して、企業や組織の強みを抽出し、メディアフック9要素で分類してください。\n\n"
            . "【分析対象】\nプレスリリースとして配信予定のコンテンツです。メディアや読者に効果的に情報を伝えるための強みを重視して分析してください。\n\n"
            . $personaText
            . $releaseTypeText
            . "【記事全文】\n{$content}\n\n"
            . "【分析要求】\n"
            . "1. プレスリリースとしての訴求力を評価しながら、記事から強みを抽出してください（タイトル、画像、本文すべてから分析）\n"
            . "2. 各強みをメディアフック9要素で分類してください\n"
            . "3. 画像が含まれる場合は「画像/映像」要素として評価してください\n"
            . "4. プレスリリースとしてのインパクトスコア（低/中/高）を付けてください\n"
            . "5. プレスリリースとして不足している要素があれば指摘してください\n"
            . "6. プレスリリースとして特に優れた強みをハイライトしてください\n"
            . "7. 企業が伝えたい生活者の人物像（上記）になりきって、このプレスリリースを読んだ感想を1～2文で出力してください（率直な印象や共感ポイント、疑問点など）。\n"
            . ($releaseType ? "8. リリースタイプ「{$releaseType}」の観点から、このタイプに最も適した強みの抽出と評価を重視してください。該当するリリースタイプの特性に基づいて分析の重点を調整してください。\n" : "8. リリースタイプが指定されていないため、汎用的な分析を行ってください。\n")
            . "\n【出力形式】\n以下のJSON形式で出力してください：\n\n"
            . "```json\n{\n"
            . "  \"strengths\": [\n    {\n      \"content\": \"抽出された強み文\",\n      \"category\": \"メディアフック要素名\",\n      \"impact_score\": \"低/中/高\",\n      \"type\": \"定量/定性\",\n      \"position\": \"見出しや段落の位置情報\"\n    }\n  ],\n"
            . "  \"missing_elements\": [\n    {\n      \"element\": \"不足要素名\",\n      \"suggestion\": \"具体的な改善提案（リリースタイプに応じた提案を含める）\"\n    }\n  ],\n"
            . "  \"highlights\": [\n    {\n      \"content\": \"特に優れた強み\",\n      \"reason\": \"優れている理由（リリースタイプの観点も含める）\"\n    }\n  ],\n"
            . "  \"summary\": {\n    \"total_strengths\": 抽出した強みの総数,\n    \"high_impact_count\": 高インパクトの強みの数,\n    \"covered_elements\": [\"カバーしている要素のリスト\"],\n    \"release_type\": \"指定されたリリースタイプ（なければnull）\",\n    \"reference_url\": \"https://prtimes.jp/magazine/media-hook/\"\n  },\n"
            . "  \"persona_feedback\": \"全体的な印象（1文）、疑問点や気になる点があれば（1～2文、なければ空文字）、人物像のユーザーが感じた良い点や共感ポイント（1～2文）\"\n"
            . "}\n```
";
    }
    private function parseAnalysisResult(string $content, string $originalContent): array
    {
        // JSON部分を抽出
        $jsonMatch = [];
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $jsonMatch)) {
            $jsonString = $jsonMatch[1];
        } else {
            // JSONマーカーがない場合、全体をJSONとして扱う
            $jsonString = $content;
        }

        $result = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse analysis result as JSON: ' . json_last_error_msg());
        }

        // 分析メタデータを追加
        $result['analysis_metadata'] = [
            'analyzed_at' => now()->toISOString(),
            'article_length' => mb_strlen($originalContent),
            'processing_status' => 'completed',
            'reference_source' => 'https://prtimes.jp/magazine/media-hook/'
        ];

        return $result;
    }

    public function validateAnalysisResult(array $result): bool
    {
        $requiredKeys = ['strengths', 'missing_elements', 'highlights', 'summary'];
        
        foreach ($requiredKeys as $key) {
            if (!isset($result[$key])) {
                return false;
            }
        }

        // strengths配列の検証
        if (isset($result['strengths'])) {
            foreach ($result['strengths'] as $strength) {
                $requiredStrengthKeys = ['content', 'category', 'impact_score', 'type', 'position'];
                foreach ($requiredStrengthKeys as $strengthKey) {
                    if (!isset($strength[$strengthKey])) {
                        return false;
                    }
                }
            }
        }

        // persona_feedbackは任意のため、存在チェックのみ
        // (personaが設定されていない場合は空になる可能性がある)

        return true;
    }

    private function getMockStrengthAnalysis(string $content, ?string $persona = null, ?string $releaseType = null): array
    {
        // デモ用のモックデータ
        return [
            "strengths" => [
                [
                    "content" => "画期的な新商品のリリース",
                    "category" => "新規性/独自性",
                    "impact_score" => "高",
                    "type" => "定性",
                    "position" => "タイトル・本文"
                ],
                [
                    "content" => "従来比200%の向上という具体的数値",
                    "category" => "特級性/希少性",
                    "impact_score" => "高",
                    "type" => "定量",
                    "position" => "本文・商品特徴"
                ],
                [
                    "content" => "2年間の開発期間でチーム一丸の取り組み",
                    "category" => "社会性/公共性",
                    "impact_score" => "中",
                    "type" => "定性",
                    "position" => "本文・開発背景"
                ]
            ],
            "missing_elements" => [
                [
                    "element" => "時代性/季節性",
                    "suggestion" => "現在のトレンドや時流との関連性を追加すると、より読者の興味を引けるでしょう"
                ],
                [
                    "element" => "地域性",
                    "suggestion" => "対象地域や市場を明確にすることで、読者にとってより身近な情報になります"
                ]
            ],
            "highlights" => [
                [
                    "content" => "従来比200%向上という具体的な数値",
                    "reason" => "定量的なデータは読者の信頼性を高め、商品の優位性を明確に示しています"
                ]
            ],
            "summary" => [
                "total_strengths" => 3,
                "high_impact_count" => 2,
                "covered_elements" => ["新規性/独自性", "特級性/希少性", "社会性/公共性"],
                "release_type" => $releaseType,
                "reference_url" => "https://prtimes.jp/magazine/media-hook/"
            ],
            "persona_feedback" => $persona ? "指定されたターゲット読者の視点から見ると、革新的な商品への期待感が高まります。" : "",
            "analysis_metadata" => [
                "analyzed_at" => now()->toISOString(),
                "article_length" => mb_strlen($content),
                "processing_status" => "completed_demo_mode",
                "reference_source" => "https://prtimes.jp/magazine/media-hook/"
            ]
        ];
    }
}