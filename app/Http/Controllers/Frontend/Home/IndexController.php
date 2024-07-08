<?php

namespace App\Http\Controllers\Frontend\Home;

use App\Http\Controllers\Controller;
use App\Models\Meaning;
use App\Models\Phrase;
use App\Models\SinoVietNamese;
use App\Models\SyntaxMeaning;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class IndexController extends Controller
{
    public function index() {
        return view('frontend/home/index');
    }

    public function postTranslate(Request $request) {
        $chinese = $request->get('chinese');

        $result = collect();
        if ($chinese) {

            if (mb_strlen($chinese) >= 10000) {
                throw new BadRequestException('Limit 10,000 characters');
            }

            $translatedContent = $chinese;
            $translatedContent = strip_tags($translatedContent);
            $translatedContent = str_replace("\r\n", '', $translatedContent);
            $translatedContent = str_replace("\n", '', $translatedContent);

            $lineByLineContent = $this->breakTextToArray($translatedContent);

            foreach ($lineByLineContent as $line) {
                $content = ucfirst($this->translate($line));
                $sino = ucfirst($this->getSinoVietnamese($line));
                $result->push([
                    'sino' => $sino,
                    'sino_tokens' => $this->getSinoTokens($line),
                    'source' => $line,
                    'predict' => $content
                ]);
            }
        }

        return $result;
    }

    public function translate($text) {
        $translatedContent = $text;

        $translatedContent = strip_tags($translatedContent);
        $translatedContent = $this->processPhrase($translatedContent);
        $translatedContent = $this->processName($translatedContent);
        $translatedContent = $this->processWithSyntax($translatedContent);

        $meaningRows = Meaning::query()
            ->where('priority', '>', 1)
            ->where('priority', '<=', mb_strlen($text))
            ->orderBy('priority', 'DESC')
            ->orderBy('word_length', 'DESC')
            ->get();

        foreach ($meaningRows as $item) {
            $translatedContent = str_replace($item->word, $item->meaning . ' ', $translatedContent);
        }

        $translatedContent = $this->processWordByWord($translatedContent);
        $translatedContent = $this->clearDirtyCharacters($translatedContent);

        return $translatedContent;
    }

    public function clearDirtyCharacters($translatedContent): string {
        $translatedContent = str_replace('、', ',', $translatedContent);
        $translatedContent = str_replace('，', ',', $translatedContent);
        $translatedContent = str_replace(' ,', ', ', $translatedContent);
        $translatedContent = str_replace('。', '.', $translatedContent);
        $translatedContent = str_replace(' .', '. ', $translatedContent);
        $translatedContent = str_replace('；', ', ', $translatedContent);
        $translatedContent = str_replace('、', ', ', $translatedContent);
        $translatedContent = str_replace('。', '. ', $translatedContent);
        return trim($translatedContent);
    }

    public function getSinoVietnamese($translatedContent)
    {
        return \App\Modules\SinoVietNamese\Util\Util::getSinoVietNamese($translatedContent);
    }

    public function getSinoTokens($translatedContent) {
        $rawArrWord = mb_str_split($translatedContent);
        $arrWord = array_unique($rawArrWord);
        $tokens = [];

        $meaning = Meaning::query()
            ->whereIn('word', $arrWord)
            ->orderBy('priority', 'DESC')
            ->get();

        foreach ($rawArrWord as $i => $word) {
            $tokens[$i][$word] = $word;
            foreach ($meaning as $item) {
                if ($word === $item->word) {
                    $tokens[$i][$word] = $item->sino;
                    break;
                }
            }
        }

        return $tokens;
    }

    public function processWordByWord($translatedContent): string
    {
        $_translatedContent = $translatedContent;
        preg_match_all('/\p{Han}/u', $_translatedContent, $matches);
        $arrWord = array_unique($matches[0]);

        $meaning = Meaning::query()
            ->whereIn('word', $arrWord)
            ->orderBy('priority', 'DESC')
            ->get();

        foreach ($meaning as $item) {
            $_translatedContent = str_replace($item->word, $item->meaning . ' ', $_translatedContent);
            $_translatedContent = preg_replace('!\s+!', ' ', $_translatedContent);
        }

        return trim($_translatedContent);
    }

    public function processPhrase($translatedContent) {
        ini_set('memory_limit', '-1');

        $maxLength = Phrase::query()->max('priority');
        $strLen = mb_strlen($translatedContent);
        if ($strLen > $maxLength)  $strLen = $maxLength;

        $arrPhrase = [];
        for ($i = $strLen; $i >= 3; $i--) {
            $temp = $this->text2phrase($translatedContent, $i);
            foreach ($temp as $x) {
                $arrPhrase[] = $x;
            }
        }
        $arrPhrase = array_unique($arrPhrase);

        $minLengthPhrase = 0;
        $maxLengthPhrase = 0;
        foreach ($arrPhrase as $x) {
            $xLen = mb_strlen($x);
            if ($xLen > $maxLengthPhrase) {
                $maxLengthPhrase = $xLen;
            }

            if ($xLen < $minLengthPhrase) {
                $minLengthPhrase = $xLen;
            }
        }

        $meaningRows = Phrase::query()
            ->whereIn('phrase', $arrPhrase)
            ->where('priority', '>=', $minLengthPhrase)
            ->where('priority', '<=', $maxLengthPhrase)
            ->orderBy('priority', 'DESC')
            ->get();

        foreach ($meaningRows as $item) {
            $translatedContent = str_replace($item->phrase, $item->meaning . ' ', $translatedContent);
        }

        return trim($translatedContent);
    }

    public function processName($translatedContent) {
        $meaningRows = Meaning::query()
            ->where('type', 'NAME')
            ->orderBy('priority', 'DESC')
            ->orderBy('word_length', 'DESC')
            ->get();

        foreach ($meaningRows as $item) {
            $translatedContent = str_replace($item->word, $item->meaning . ' ', $translatedContent);
        }

        return trim($translatedContent);
    }

    public function processWithSyntax($translatedContent) {
        $cleanContent = strip_tags($translatedContent);
        $syntaxMeaningRows = SyntaxMeaning::query()
            ->orderBy('priority', 'DESC')
            ->get();

        $patterns = $syntaxMeaningRows->pluck('pattern')
            ->map(function($pattern) {
                return sprintf('#%s#', $pattern);
            })->toArray();
        $replacements = $syntaxMeaningRows->pluck('meaning')->toArray();

        $arr = explode('，', $cleanContent);
        $arrTranslate = [];
        foreach ($arr as $text) {
            $temp = preg_replace($patterns, $replacements, $text);
            $arrTranslate[] = $temp;
        }

        $cleanContent = implode('，', $arrTranslate);

        return trim($cleanContent);
    }

    public function addWords(Request $request) {
        $chinese = $request->get('chinese');
        $meaning = $request->get('meaning');
        $type = $request->get('type');

        if (!$chinese || !$meaning) {
            return response()->json([
                'message' => 'Bad request'
            ], 400);
        }

        $chinese = trim($chinese);
        $meaning = trim($meaning);

        if ($type && !in_array($type, [Meaning::TYPE['NAME'], Meaning::TYPE['PHRASE']])) {
            $meaning = mb_strtolower($meaning);
        }

        if (mb_strlen($chinese) < 1) {
            return response()->json([
                'message' => 'Word length minimum is 1'
            ], 400);
        }

        $chinese = trim($chinese);
        $chinese = str_replace("\n", "", $chinese);
        $chinese = str_replace("\r\n", "", $chinese);

        $chineseLength = mb_strlen($chinese);
        $meaning = trim($meaning);
        $meaning = str_replace("\n", "", $meaning);
        $meaning = str_replace("\r\n", "", $meaning);

        if (!$type && mb_strlen($chinese) >= 4) {
            Phrase::query()
                ->upsert([
                    'phrase' => $chinese,
                    'sino' => $this->getSinoVietnamese($chinese),
                    'meaning' => $meaning,
                    'length' => $chineseLength,
                    'priority' => $chineseLength,
                    'created_at' => Carbon::now()->toISOString(),
                    'updated_at' => Carbon::now()->toISOString()
                ], ['phrase'], [
                    'length' => $chineseLength,
                    'priority' => $chineseLength,
                    'updated_at' => Carbon::now()->toISOString()
                ]);
        }
        else {
            $exist = Meaning::query()
                ->where('word', $chinese)
                ->where('type', $type)
                ->orderBy('priority', 'DESC')
                ->first();

            $m = new Meaning();
            $m->priority = mb_strlen($chinese);
            $m->word = $chinese;
            $m->meaning = $meaning;
            $m->type = $type;
            $m->word_length = mb_strlen($chinese);
            $m->sino = $this->getSinoVietnamese($chinese);

            if ($exist) {
                $m->priority = $exist->priority + 1;
            }

            $m->save();
        }

        return response()->json([
            'message' => sprintf("%s has been successfully added", $chinese),
        ]);
    }

    public function breakTextToArray($text): array {
        $arr = explode("。", $text);
        return array_filter($arr, function($value) {
            return !!$value;
        });
    }

    public function text2phrase($string, $phraseLength): array
    {
        $result = [];
        $ignoreChars = ['，', '：', '、', '。', ' ', ',', '.'];

        foreach ($ignoreChars as $c) {
            $phrases = explode($c, $string);
            foreach ($phrases as $phrase) {
                $phrase = str_replace($ignoreChars, "", $phrase);
                $len = mb_strlen($phrase);
                for ($i = 0; $i < $len; $i++) {
                    $temp = mb_substr($phrase, $i, $phraseLength);
                    if (mb_strlen($temp) == $phraseLength) {
                        $result[] = $temp;
                    }
                }
            }
        }

        return array_unique($result);
    }
}
