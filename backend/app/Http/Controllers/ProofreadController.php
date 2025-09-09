<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProofReadingServices;

class ProofreadController extends Controller
{
    protected $openai;

    public function __construct(ProofReadingServices $openai)
    {
        $this->openai = $openai;
    }

    public function proofread(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:2000',
        ]);

        $result = $this->openai->proofreadText($validated['text']);

        return response()->json([
            'original' => $validated['text'],
            'proofread' => $result,
        ]);
    }
}