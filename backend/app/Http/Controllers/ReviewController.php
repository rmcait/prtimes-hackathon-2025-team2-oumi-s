<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Proofreading;
use Jfcherng\Diff\DiffHelper;

class ReviewController extends Controller
{
    public function index()
    {
        return view('review');
    }

    public function proofread(Request $request, Proofreading $proofreading)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'renderer' => ['nullable', 'string'],
        ]);

        $original = $validated['content'];
        $proofread = $proofreading->proofreadText($original);

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
        ]);
    }
}


