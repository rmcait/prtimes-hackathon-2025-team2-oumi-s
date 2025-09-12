<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Proofreading
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function proofreadText(string $text): array
    {
        if (empty($this->apiKey)) {
            // APIキーがない場合はモックレスポンスを返す
            return $this->getMockProofreadResponse($text);
        }

        $systemPrompt = '
あなたは文章校正者です。入力された文章について以下の形式でJSONを返してください：

{
    "corrected_text": "修正後の文章全体",
    "suggestions": [
        {
            "original": "修正前の文章",
            "corrected": "修正後の文章", 
            "reason": "修正理由",
            "type": "修正タイプ（誤字脱字/表現改善/句読点/表記統一など）",
            "severity": "重要度（high/medium/low）",
            "position": "修正箇所の位置情報"
        }
    ],
    "overall_assessment": "全体的な評価コメント"
}

修正する観点：
- 誤字脱字の修正
- 不自然な表現の改善
- 句読点の適切な配置
- 表記統一
- 法令や公序良俗への遵守確認
- 第三者への差別・誹謗中傷がないことの確認
- 日時、曜日、数値の整合性確認

重要度の基準：
- high: 誤字脱字、事実の誤り、不適切な表現
- medium: 表現の改善、読みやすさの向上
- low: 表記統一、微細な調整
';

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $text],
                ],
                'response_format' => ['type' => 'json_object']
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI API request failed: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        
        if (empty($content)) {
            throw new \Exception('OpenAI API returned empty response. Response: ' . $response->body());
        }

        $result = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from OpenAI API: ' . $content);
        }

        // 結果の検証とフォーマット
        return [
            'original' => $text,
            'corrected_text' => $result['corrected_text'] ?? $text,
            'suggestions' => $result['suggestions'] ?? [],
            'overall_assessment' => $result['overall_assessment'] ?? '文章を確認しました。',
            'has_changes' => !empty($result['suggestions']),
        ];
    }

    /**
     * APIキーがない場合のモックレスポンス
     */
    private function getMockProofreadResponse(string $text): array
    {
        // 簡単な校正例を返す
        $suggestions = [];
        
        // 基本的な修正例
        if (str_contains($text, 'ハッカソン受付開始')) {
            $suggestions[] = [
                'original' => 'ハッカソン受付開始',
                'corrected' => 'ハッカソン受付を開始',
                'reason' => 'より自然な表現にするため、助詞「を」を追加しました。',
                'type' => '表現改善',
                'severity' => 'medium',
                'position' => 'タイトル部分'
            ];
        }
        
        if (str_contains($text, '特に優秀な方には年収500万円以上の中途採用基準での内定をお出しします。')) {
            $suggestions[] = [
                'original' => '特に優秀な方には年収500万円以上の中途採用基準での内定をお出しします。',
                'corrected' => '特に優秀な方には、年収500万円以上の中途採用基準での内定をお出しします。',
                'reason' => '読点を追加して、読みやすさを向上させました。',
                'type' => '句読点',
                'severity' => 'low',
                'position' => '本文2段落目'
            ];
        }
        
        if (str_contains($text, '等')) {
            $suggestions[] = [
                'original' => 'PR TIMES」等を',
                'corrected' => 'PR TIMES」などを',
                'reason' => '「等」よりも「など」の方が読みやすく、一般的です。',
                'type' => '表記統一',
                'severity' => 'low',
                'position' => '本文中'
            ];
        }
        
        if (str_contains($text, 'リニューアします')) {
            $suggestions[] = [
                'original' => 'メディアリスト機能をリニューアします',
                'corrected' => 'メディアリスト機能をリニューアルします',
                'reason' => '「リニューアル」の正しい表記に修正しました。',
                'type' => '誤字脱字',
                'severity' => 'high',
                'position' => 'タイトル部分'
            ];
        }
        
        // 修正後のテキストを生成
        $correctedText = $text;
        foreach ($suggestions as $suggestion) {
            $correctedText = str_replace($suggestion['original'], $suggestion['corrected'], $correctedText);
        }
        
        return [
            'original' => $text,
            'corrected_text' => $correctedText,
            'suggestions' => $suggestions,
            'overall_assessment' => count($suggestions) > 0 
                ? count($suggestions) . '箇所の改善点を見つけました。文章の品質向上にご活用ください。'
                : '文章を確認しました。特に修正が必要な箇所は見つかりませんでした。',
            'has_changes' => count($suggestions) > 0,
        ];
    }
}