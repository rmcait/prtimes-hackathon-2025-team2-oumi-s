<?php

namespace App\services;

use Illuminate\Support\Facades\Http;

class SixTwoReviewer{
     protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function proofreadText(string $text): string
    {
        if (empty($this->apiKey)) {
            return $this->getMockSixTwoReview($text);
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini', // 割と安いはず
                'messages' => [
                    ['role' => 'system', 'content' => '6W2Hが記事に含まれているかをレビューして下さい。'],
                    ['role' => 'user', 'content' => $text],
                ],
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI API request failed: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        
        if (empty($content)) {
            throw new \Exception('OpenAI API returned empty response. Response: ' . $response->body());
        }

        return $content;
    }

    private function getMockSixTwoReview(string $text): string
    {
        return "## 6W2Hレビュー結果（デモモード）

**📋 現在の記事の6W2H状況:**

✅ **Who（誰が）**: 企業・開発チームが明記されています
✅ **What（何を）**: 新商品の内容が記載されています  
✅ **When（いつ）**: 来月リリース予定と明記
❌ **Where（どこで）**: 販売場所・対象地域が不明
⚠️  **Why（なぜ）**: 開発理由はあるが、より具体的な背景があると良い
✅ **How（どのように）**: 機能の説明がされています
❌ **How much（いくら）**: 価格情報が含まれていません
⚠️  **How many（どのくらい）**: 数量や規模の情報が限定的

**💡 改善提案:**
- 販売チャネルや対象地域を明記
- 価格帯の情報を追加
- 生産数量や市場規模の情報を補完

**⭐ 全体評価:** 6W2Hの要素のうち5つが含まれており、基本的な情報は網羅されています。価格と場所の情報を追加することで、より完成度の高い記事になるでしょう。";
    }
}