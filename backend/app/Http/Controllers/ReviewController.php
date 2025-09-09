<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Proofreading;
use Jfcherng\Diff\DiffHelper;
use App\services\SixTwoReviewer;

class ReviewController extends Controller
{
    public function index()
    {
        return view('review');
    }

    public function proofread(Request $request, Proofreading $proofreading, SixTwoReviewer $sixTwo)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'renderer' => ['nullable', 'string'],
        ]);

        $original = $validated['content'];
        $proofread = $proofreading->proofreadText($original);
        $sixTwoReview = $sixTwo->proofreadText($original);

        $renderer = $validated['renderer'] ?? 'SideBySide';
        if (!in_array($renderer, ['Inline', 'SideBySide'], true)) {
            $renderer = 'SideBySide';
        }

        $differOptions = [
            'context' => 3,
            'ignoreCase' => false,
            'ignoreWhitespace' => true,
        ];

        $rendererOptions = [
            'detailLevel' => 'word',
            'tabSize' => 4,
            'wordThreshold' => 0.25,
            'renderHeaders' => false,
        ];

        $diffHtml = DiffHelper::calculate($original, $proofread, $renderer, $differOptions, $rendererOptions);

        return response()->json([
            'original' => $original,
            'proofread' => $proofread,
            'diffHtml' => $diffHtml,
            'renderer' => $renderer,
            'sixTwoReview' => $sixTwoReview,
        ]);
    }

    public function sixTwoReview(Request $request, SixTwoReviewer $sixTwo)
    {
        try {
            $validated = $request->validate([
                'content' => ['required', 'string', 'min:10', 'max:50000'],
            ]);

            $content = $validated['content'];
            $review = $sixTwo->proofreadText($content);

            return response()->json([
                'success' => true,
                'message' => '6W2Hレビューが完了しました',
                'data' => [
                    'review' => $review,
                    'content_length' => mb_strlen($content)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '6W2Hレビューでエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }
}


