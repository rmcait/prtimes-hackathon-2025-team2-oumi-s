<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Proofreading;

class ProofreadText extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'proofread {text : 校正したい文章}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'OpenAIを使って文章を校正する';

    protected $openai;

    public function __construct(Proofreading $openai)
    {
        parent::__construct();
        $this->openai = $openai;
    }

    public function handle()
    {
        $text = $this->argument('text');

        $this->info("Original: " . $text);

        $result = $this->openai->proofreadText($text);

        $this->info("Proofread: " . $result);

        return Command::SUCCESS;
    }
}