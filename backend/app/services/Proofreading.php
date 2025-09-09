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

    public function proofreadText(string $text): string
    {
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key is not configured. Please set OPENAI_API_KEY in .env file.');
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini', // 割と安いはず
                'messages' => [
                    ['role' => 'system', 'content' => 'あなたは文章校正者です。誤字脱字や不自然な表現を直し、自然でわかりやすい文にしてください。'],
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
}