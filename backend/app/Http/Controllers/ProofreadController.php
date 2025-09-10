<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Proofreading;

class ProofreadController extends Controller
{
    protected $openai;

    public function __construct(Proofreading $openai)
    {
        $this->openai = $openai;
    }

    public function proofread(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:50000', // 文字数上限を増加
        ]);

        try {
            $result = $this->openai->proofreadText($validated['text']);
            
            // 新しい構造化されたレスポンスを返す
            return response()->json([
                'success' => true,
                'original' => $result['original'],
                'proofread' => $result['corrected_text'],
                'corrected_text' => $result['corrected_text'],
                'suggestions' => $result['suggestions'],
                'overall_assessment' => $result['overall_assessment'],
                'has_changes' => $result['has_changes'],
                'message' => '校正が完了しました'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'original' => $validated['text'],
                'proofread' => $validated['text'], // エラー時は原文をそのまま返す
                'corrected_text' => $validated['text'],
                'suggestions' => [],
                'overall_assessment' => 'エラーが発生しました。',
                'has_changes' => false,
            ], 500);
        }
    }
}