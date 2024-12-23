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
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class IndexController extends Controller
{
    public function index() {
        return view('frontend/home/index');
    }

    public function postTranslate(Request $request) {
        $chinese = $request->get('chinese');

        $result = [
            'total_lines' => 0,
            'total_words' => 0,
            'unique_words'=> 0,
            'data' => []
        ];
        if ($chinese) {

            if (mb_strlen($chinese) >= 10000) {
                throw new BadRequestException('Limit 10,000 characters');
            }

            $translatedContent = $chinese;
            $translatedContent = str_replace("\r\n", '', $translatedContent);
            $translatedContent = str_replace("\n", '', $translatedContent);

            $lineByLineContent = $this->breakTextToArray($translatedContent);

            foreach ($lineByLineContent as $line) {
                $content = $this->translate2($line);
                $result['data'][] = $content;
            }
        }

        $result['total_lines'] = count($result['data']);
        preg_match_all('/\p{Han}/u', $chinese, $matches);
        $result['total_words'] = isset($matches[0]) ? count($matches[0]) : 0;
        $result['unique_words'] = count(array_unique($matches[0]));

        return $result;
    }

    public function translate($text) {
        $translatedContent = $text;

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

    public function translate2($text) {
        $translatedContent = $text;
        $phraseTokens = $this->processPhrase($translatedContent);
        $nameTokens = $this->processName($translatedContent);
        $wordTokens = $this->processWordByWord($translatedContent);

        foreach ($phraseTokens as $chinese => $x) {
            $translatedContent = str_replace($chinese, $x['meaning'] . ' ', $translatedContent);
        }

        foreach ($nameTokens as $chinese => $x) {
            $translatedContent = str_replace($chinese, $x['meaning'] . ' ', $translatedContent);
        }

        foreach ($wordTokens as $chinese => $x) {
            $translatedContent = str_replace($chinese, $x['meaning'] . ' ', $translatedContent);
        }

        return [
            'phrase' => $text,
            'translated' => $this->clearDirtyCharacters($translatedContent),
            'phrase_tokens' => $this->processPhrase($text),
            'name_tokens' => $this->processName($text),
            'word_tokens' => $this->processWordByWord($text),
        ];
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
        $translatedContent = str_replace('<', ' (', $translatedContent);
        $translatedContent = str_replace('>', ') ', $translatedContent);
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

    public function processWordByWord($chineseContent): array
    {
        $_chineseContent = $chineseContent;
        preg_match_all('/\p{Han}/u', $_chineseContent, $matches);
        $arrWord = array_unique($matches[0]);

        $meaning = Meaning::query()
            ->whereIn('word', $arrWord)
            ->orderBy('priority', 'DESC')
            ->get();

        $tokens = [];
        foreach ($meaning as $item) {
            $tokens[$item->word] = [
                'id' => Str::random(6),
                'original' => $item->word,
                'sino' => $item->sino,
                'meaning' =>trim($item->meaning)
            ];
        }

        return array_values($tokens);
    }

    public function processPhrase($chineseContent) {
        ini_set('memory_limit', '-1');

        $maxLength = Phrase::query()->max('priority');
        $strLen = mb_strlen($chineseContent);
        if ($strLen > $maxLength)  $strLen = $maxLength;

        $arrPhrase = [];
        for ($i = $strLen; $i >= 3; $i--) {
            $temp = $this->text2phrase($chineseContent, $i);
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

        $tokens = [];
        foreach ($meaningRows as $item) {
            if (mb_strpos($chineseContent, $item->phrase) !== false) {
                $tokens[$item->phrase] = [
                    'id' => Str::random(6),
                    'original' => $item->phrase,
                    'sino' => $item->sino ? $item->sino : $this->getSinoVietnamese($item->phrase),
                    'meaning' => $item->meaning
                ];
            }
        }

        return array_values($tokens);
    }

    public function processName($chineseContent) {
        $meaningRows = Meaning::query()
            ->where('type', 'NAME')
            ->orderBy('priority', 'DESC')
            ->orderBy('word_length', 'DESC')
            ->get();

        $tokens = [];
        foreach ($meaningRows as $item) {
            if (mb_strpos($chineseContent, $item->word) !== false) {
                $tokens[$item->word] = [
                    'id' => Str::random(6),
                    'original' => $item->word,
                    'sino' => $item->sino ? $item->sino : $this->getSinoVietnamese($item->word),
                    'meaning' => trim($item->meaning)
                ];
            }
        }

        return array_values($tokens);
    }

    public function processWithSyntax($translatedContent) {
        $cleanContent = $translatedContent;
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

        if ((!$type || $type === Meaning::TYPE['PHRASE']) && mb_strlen($chinese) >= 4) {
            $entity = 'Phrase';
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
                    'meaning' => $meaning,
                    'length' => $chineseLength,
                    'priority' => $chineseLength,
                    'updated_at' => Carbon::now()->toISOString()
                ]);
        }
        else {
            $entity = 'Meaning';
            /* @var Meaning|null $exist */
            $exist = Meaning::query()
                ->where('word', $chinese)
                ->where('type', $type)
                ->orderBy('priority', 'DESC')
                ->first();

            if ($exist) {
                $exist->priority = mb_strlen($chinese);
                $exist->word = $chinese;
                $exist->meaning = $meaning;
                $exist->type = $type;
                $exist->word_length = mb_strlen($chinese);
                $exist->sino = $this->getSinoVietnamese($chinese);
                $exist->save();
            }
            else {
                $m = new Meaning();
                $m->priority = mb_strlen($chinese);
                $m->word = $chinese;
                $m->meaning = $meaning;
                $m->type = $type;
                $m->word_length = mb_strlen($chinese);
                $m->sino = $this->getSinoVietnamese($chinese);
                $m->save();
            }
        }

        return response()->json([
            'entity' => $entity,
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
