<?php
namespace App\Services\AiRobert;

use App\Services\LineBot\ReplyEngine;
use App\Services\LineBot\LineReplyMessage;
use App\Services\LineBot\LineMessage;
use Illuminate\Support\Facades\Log;

class FileBaseAi implements ReplyEngine
{
    protected array $knowledge = [];
    public int $TopK = 3;
    private $initFlag = 0;

    public function _defaultInit(){
        $this->fileInit(__DIR__.'/test.txt');
    }
    /**
     * init by file records;
     */
    public function fileInit(string $filePath)
    {
        if($this->initFlag){
            return ;
        }
        Log::warning('Bot init: ' . $filePath);
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }

        $content = file_get_contents($filePath);
        $blocks = explode("===END===", $content);

        foreach ($blocks as $block) {
            if (preg_match('/===QUESTION===\s*(.*?)\s*===ANSWER===\s*(.*)/s', $block, $m)) {
                $q = trim($m[1]);
                $a = trim($m[2]);

                if ($q && $a) {
                    $this->knowledge[] = [
                        'question' => $q,
                        'answer' => $a,
                        'tokens_word' => $this->tokenizeWords($q),
                        'tokens_char' => $this->tokenizeChars($q),
                        'tokens_ngram' => $this->tokenizeNGram($q, 2),
                    ];
                }
            }
        }
        $this->initFlag = 1;
    }

    public function handle(LineMessage $lineMessage, LineReplyMessage &$lineReplyMessage)
    {
        $this->_defaultInit();
        if($lineMessage->getMessageType() != $lineMessage::MESSAGE_TYPE_TEXT){
            return $lineReplyMessage;
        }

        $content = $lineMessage->getMessageContent()['text'] ?? '';
        $reply = $lineReplyMessage;

        $defaultText = "from easy Ai; message has handled。";
        if ($lineMessage->getMessageType() === LineMessage::MESSAGE_TYPE_TEXT) {
            $defaultText .= " text content: " . $content;
        }
        $reply->appendText($defaultText);

        $inputWord = $this->tokenizeWords($content);
        $inputChar = $this->tokenizeChars($content);
        $inputNGram = $this->tokenizeNGram($content, 2);

        $results = [];

        foreach ($this->knowledge as $item) {
            $scoreWord = $this->jaccard($inputWord, $item['tokens_word']);
            $scoreChar = $this->dice($inputChar, $item['tokens_char']);
            $scoreNGram = $this->dice($inputNGram, $item['tokens_ngram']);

            $finalScore = (count($inputWord) <= 2)
                ? ($scoreNGram * 0.7 + $scoreChar * 0.3)
                : ($scoreWord * 0.6 + $scoreNGram * 0.3 + $scoreChar * 0.1);

            $results[] = [
                'question' => $item['question'],
                'answer' => $item['answer'],
                'score' => $finalScore,
            ];
        }

        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
        $topResults = array_slice($results, 0, $this->TopK);

        if (!empty($topResults)) {
            foreach ($topResults as $r) {
                $reply->appendText(
                    "【match question】" . $r['question'] . PHP_EOL .
                    "【match answer】" . $r['answer'] . PHP_EOL .
                    "【match score】" . $r['score']
                );
                Log::warning('Matched: ' . $r['question']);
            }
        } else {
            $reply->appendText("sorry, cannot match any answers;");
            Log::warning('No match for: ' . $content);
        }

        return $reply;
    }

    // ------------------------------
    // Token tool
    // ------------------------------

    protected function tokenizeWords(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{Han}\p{Hiragana}\p{Katakana}a-z0-9]+/u', ' ', $text);
        $arr = preg_split('/\s+/u', trim($text));
        return array_values(array_filter($arr));
    }

    protected function tokenizeChars(string $text): array
    {
        $text = preg_replace('/[^\p{Han}\p{Hiragana}\p{Katakana}a-z0-9]/u', '', $text);
        return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    protected function tokenizeNGram(string $text, int $n = 2): array
    {
        $chars = $this->tokenizeChars($text);
        $ngrams = [];
        $len = count($chars);
        for ($i = 0; $i <= $len - $n; $i++) {
            $ngrams[] = implode('', array_slice($chars, $i, $n));
        }
        return $ngrams;
    }

    // ------------------------------
    // alg
    // ------------------------------

    protected function jaccard(array $a, array $b): float
    {
        if (empty($a) || empty($b)) return 0.0;
        $a = array_unique($a);
        $b = array_unique($b);
        $inter = array_intersect($a, $b);
        $union = array_unique(array_merge($a, $b));
        return count($inter) / count($union);
    }

    protected function dice(array $a, array $b): float
    {
        if (empty($a) || empty($b)) return 0.0;
        $inter = array_intersect($a, $b);
        return (2 * count($inter)) / (count($a) + count($b));
    }
}
