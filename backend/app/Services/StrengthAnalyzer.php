<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class StrengthAnalyzer
{
    protected $apiKey;
    
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
    }

    public function analyzeStrengths(string $markdownContent): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key is not configured. Please set OPENAI_API_KEY in .env file.');
        }

        $prompt = $this->buildAnalysisPrompt($markdownContent);
        
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
        
与えられた Markdown 形式の記事から企業や組織の強みを抽出し、メディアフック9要素で分類してください。

メディアフック9要素:
1. 時代性/季節性 (Time/Seasonality): 時流や季節に合った内容
2. 画像/映像 (Images/Video): インパクトのある視覚的要素
3. 矛盾/対立 (Contradiction/Conflict): 常識に反する内容や対比
4. 地域性 (Regional): 特定地域に関する情報
5. 話題性 (Topicality): 現在のトレンドや話題
6. 社会性/公共性 (Social/Public Interest): 社会的意義や公共の利益
7. 新規性/独自性 (Novelty/Uniqueness): 初回や独自のアプローチ
8. 特級性/希少性 (Superlative/Rarity): 数値による特別感や希少性
9. 意外性 (Unexpectedness): 予想外の展開や驚きの要素

分析結果は必ず JSON 形式で返してください。";
    }

    private function buildAnalysisPrompt(string $content): string
    {
        return "以下のMarkdown記事を分析して、企業や組織の強みを抽出し、メディアフック9要素で分類してください。

【記事内容】
{$content}

【分析要求】
1. 記事から強みを抽出してください
2. 各強みをメディアフック9要素で分類してください
3. インパクトスコア（低/中/高）を付けてください
4. 不足している要素があれば指摘してください
5. 特に優れた強みをハイライトしてください

【出力形式】
以下のJSON形式で出力してください：

```json
{
  \"strengths\": [
    {
      \"content\": \"抽出された強み文\",
      \"category\": \"メディアフック要素名\",
      \"impact_score\": \"低/中/高\",
      \"type\": \"定量/定性\",
      \"position\": \"見出しや段落の位置情報\"
    }
  ],
  \"missing_elements\": [
    {
      \"element\": \"不足要素名\",
      \"suggestion\": \"具体的な改善提案\"
    }
  ],
  \"highlights\": [
    {
      \"content\": \"特に優れた強み\",
      \"reason\": \"優れている理由\"
    }
  ],
  \"summary\": {
    \"total_strengths\": 抽出した強みの総数,
    \"high_impact_count\": 高インパクトの強みの数,
    \"covered_elements\": [\"カバーしている要素のリスト\"],
    \"reference_url\": \"https://prtimes.jp/magazine/media-hook/\"
  }
}
```";
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

        return true;
    }
}