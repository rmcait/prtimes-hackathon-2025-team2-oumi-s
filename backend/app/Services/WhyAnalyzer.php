<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhyAnalyzer
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    /**
     * なぜなぜ分析のチャット会話を開始または継続する
     */
    public function analyzeWhy(string $content, array $chatHistory = []): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key is not configured. Please set OPENAI_API_KEY in .env file.');
        }

        $prompt = $this->buildWhyAnalysisPrompt($content, $chatHistory);
        
        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7, // チャット的な会話のため適度な創造性
                'max_tokens' => 1000,
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI API request failed: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        
        if (empty($content)) {
            throw new \Exception('OpenAI API returned empty response. Response: ' . $response->body());
        }

        return $this->parseWhyAnalysisResult($content);
    }

    /**
     * 会話を続行して更に深い質問をする
     */
    public function continueConversation(string $originalContent, array $chatHistory, string $userResponse): array
    {
        // チャット履歴にユーザーの回答を追加
        $chatHistory[] = [
            'type' => 'user_response',
            'content' => $userResponse,
            'timestamp' => now()->toISOString()
        ];

        return $this->analyzeWhy($originalContent, $chatHistory);
    }

    private function getSystemPrompt(): string
    {
        return "あなたは PR TIMES の「なぜなぜ分析」チャットボットです。

目的：
- プレスリリース用記事や企画の独自性や魅力を深掘りし、投稿者が気づいていない価値を発見する
- 「なぜ」を繰り返し聞くことで、商品・イベント・企画の本質的なストーリーを引き出す
- 投稿者との対話を通じて、プレスリリースとしての訴求力と魅力を最大化する
- メディアや読者に効果的に伝わる独自性を明確にする

対話スタイル：
- フレンドリーで親しみやすい口調
- 相手の立場に共感しつつ、建設的な質問をする
- プレスリリースとしての視点も踏まえたアドバイス
- 一度に複数の質問をせず、1つずつ深掘りする
- 相手が答えやすい具体的な質問を心がける

なぜなぜ分析の進め方：
1. 記事の中で独自性がありそうな要素を特定
2. その要素について「なぜ？」を問いかける
3. 回答に対してさらに「なぜ？」を重ねる
4. 5回程度「なぜ」を繰り返して本質に迫る
5. 最終的にストーリーや魅力をまとめる

重要：
- 批判的ではなく、好奇心旺盛で建設的な質問をする
- 相手のアイデアや取り組みを肯定的に受け取る
- 具体的なエピソードや背景を引き出す質問を心がける";
    }

    private function buildWhyAnalysisPrompt(string $content, array $chatHistory): string
    {
        $historyText = "";
        if (!empty($chatHistory)) {
            $historyText = "\n【これまでの会話履歴】\n";
            foreach ($chatHistory as $index => $item) {
                $historyText .= ($index + 1) . ". ";
                if ($item['type'] === 'bot_question') {
                    $historyText .= "Bot: " . $item['content'] . "\n";
                } elseif ($item['type'] === 'user_response') {
                    $historyText .= "User: " . $item['content'] . "\n";
                }
            }
        }

        if (empty($chatHistory)) {
            // 最初の質問
            return "以下のプレスリリース用記事/企画内容を分析して、独自性がありそうで深掘りできる要素を1つ特定し、「なぜなぜ分析」の最初の質問をしてください。

【プレスリリース用記事/企画内容】
この記事はプレスリリースとして配信予定のコンテンツです。メディアや読者に効果的に訴求するための独自性を重視して分析してください。

{$content}

【出力形式】
以下のJSON形式で出力してください：

```json
{
  \"bot_response\": \"フレンドリーな挨拶 + 特定した独自要素についての最初の『なぜ？』質問\",
  \"identified_element\": \"注目した独自性のある要素\",
  \"analysis_stage\": 1,
  \"minimum_reached\": false,
  \"suggested_follow_up\": \"次に聞きたい質問の候補\"
}
```";
        } else {
            // 会話継続
            return "以下のプレスリリース用記事内容とこれまでの会話を踏まえて、なぜなぜ分析を継続してください。

【元のプレスリリース用記事/企画内容】
{$content}
{$historyText}

【指示】
- この記事はプレスリリースとして配信予定のため、メディア価値や読者への訴求力の観点も考慮してください
- 最新のユーザー回答に対して、さらに深い「なぜ？」を問いかけてください
- 3回未満の場合は引き続き「なぜ」を深掘り
- 3回以上の場合でも、ユーザーが希望すれば継続可能
- 各回答でこれまでの分析から見えてきた洞察や価値も提示してください

【出力形式】
以下のJSON形式で出力してください：

```json
{
  \"bot_response\": \"ユーザーの回答への共感 + 次の『なぜ？』質問\",
  \"analysis_stage\": " . (count($chatHistory) + 1) . ",
  \"insight\": \"これまでの分析から見えてきた洞察や価値\",
  \"can_continue\": true,
  \"suggested_follow_up\": \"次に聞きたい質問の候補\",
  \"minimum_reached\": " . ((count($chatHistory) + 1) >= 3 ? 'true' : 'false') . "
}
```";
        }
    }

    private function parseWhyAnalysisResult(string $content): array
    {
        // JSON部分を抽出
        $jsonMatch = [];
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $jsonMatch)) {
            $jsonString = $jsonMatch[1];
        } else {
            $jsonString = $content;
        }

        $result = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse analysis result as JSON: ' . json_last_error_msg());
        }

        // メタデータを追加
        $result['analysis_metadata'] = [
            'analyzed_at' => now()->toISOString(),
            'processing_status' => 'completed',
            'analysis_type' => 'why_analysis'
        ];

        return $result;
    }

    /**
     * 最終的な洞察とストーリーをまとめる
     */
    public function generateFinalInsight(string $originalContent, array $fullChatHistory): array
    {
        $prompt = $this->buildFinalInsightPrompt($originalContent, $fullChatHistory);
        
        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.5,
                'max_tokens' => 1500,
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI API request failed: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        
        if (empty($content)) {
            throw new \Exception('OpenAI API returned empty response. Response: ' . $response->body());
        }

        return $this->parseWhyAnalysisResult($content);
    }

    private function buildFinalInsightPrompt(string $originalContent, array $chatHistory): string
    {
        $historyText = "";
        foreach ($chatHistory as $index => $item) {
            $historyText .= ($index + 1) . ". ";
            if ($item['type'] === 'bot_question') {
                $historyText .= "Bot: " . $item['content'] . "\n";
            } elseif ($item['type'] === 'user_response') {
                $historyText .= "User: " . $item['content'] . "\n";
            }
        }

        return "以下のプレスリリース用記事内容となぜなぜ分析の全会話履歴から、最終的な洞察とストーリーをまとめ、プレスリリースとして効果的な具体的で実践的な記事活用例を詳細に提示してください。

【元のプレスリリース用記事/企画内容】
この記事はプレスリリースとして配信予定のコンテンツです。メディアや読者に効果的に情報を伝えるための記事改善案を重視してください。

{$originalContent}

【全会話履歴】
{$historyText}

【出力形式】
以下のJSON形式で出力してください。プレスリリースとして効果的な記事活用例を具体的で実践的な内容にしてください：

```json
{
  \"final_insight\": \"なぜなぜ分析から見えてきた本質的な価値や魅力\",
  \"story_elements\": [\"ストーリー要素1\", \"ストーリー要素2\", \"ストーリー要素3\"],
  \"hidden_values\": [\"気づかなかった価値1\", \"気づかなかった価値2\"],
  \"pr_recommendations\": [\"プレスリリースでアピールすべき点1\", \"プレスリリースでアピールすべき点2\"],
  \"emotional_hooks\": [\"感情に訴える要素1\", \"感情に訴える要素2\"],
  \"article_applications\": [
    {
      \"section\": \"タイトル\",
      \"before_example\": \"改善前のタイトル例\",
      \"after_example\": \"改善後のタイトル例\",
      \"reason\": \"なぜその改善が効果的なのか\",
      \"tips\": \"タイトル作成のコツ\"
    },
    {
      \"section\": \"リード文\",
      \"before_example\": \"改善前のリード文例\",
      \"after_example\": \"改善後のリード文例\",
      \"reason\": \"改善理由と効果\",
      \"tips\": \"リード文作成のポイント\"
    },
    {
      \"section\": \"本文構成\",
      \"before_example\": \"従来の構成例\",
      \"after_example\": \"推奨する構成例\",
      \"reason\": \"構成変更の効果\",
      \"tips\": \"読者を惹きつける構成のコツ\"
    },
    {
      \"section\": \"キーメッセージ\",
      \"before_example\": \"一般的な表現例\",
      \"after_example\": \"独自価値を活かした表現例\",
      \"reason\": \"差別化できる理由\",
      \"tips\": \"印象的なメッセージ作成法\"
    }
  ]
}
```";
    }
}
