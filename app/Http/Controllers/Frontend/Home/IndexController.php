<?php

namespace App\Http\Controllers\Frontend\Home;

use App\Http\Controllers\Controller;
use App\Models\Meaning;
use App\Models\SyntaxMeaning;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

            if (mb_strlen($chinese) >= 100) {
                throw new BadRequestException('Limit 10,000 characters');
            }

            $translatedContent = $chinese;
            $translatedContent = strip_tags($translatedContent);
            $translatedContent = str_replace("\r\n", '', $translatedContent);
            $translatedContent = str_replace("\n", '', $translatedContent);

            $lineByLineContent = $this->breakTextToArray($translatedContent);

            foreach ($lineByLineContent as $line) {
                $content = $this->translate($line);
                $result->push([
                    'sino' => $this->getSinoVietnamese($line),
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
            ->where('priority', '<=', 100)
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

    public function getSinoVietnamese($translatedContent): string
    {
        preg_match_all('/\p{Han}/u', $translatedContent, $matches);
        $arrWord = array_unique($matches[0]);
        $meaning = Meaning::query()
            ->whereIn('word', $arrWord)
            ->orderBy('priority', 'DESC')
            ->get();

        foreach ($meaning as $item) {
            $translatedContent = str_replace($item->word, $item->sino . ' ', $translatedContent);
            $translatedContent = preg_replace('!\s+!', ' ', $translatedContent);
        }

        $translatedContent = str_replace(' ，', ', ', $translatedContent);

        return trim($translatedContent);
    }

    public function processWordByWord($translatedContent): string
    {
        $translatedContent = mb_strtolower($translatedContent);
        preg_match_all('/\p{Han}/u', $translatedContent, $matches);
        $arrWord = array_unique($matches[0]);
        $meaning = Meaning::query()
            ->whereIn('word', $arrWord)
            ->orderBy('priority', 'DESC')
            ->get();

        foreach ($meaning as $item) {
            $translatedContent = str_replace($item->word, $item->meaning . ' ', $translatedContent);
            $translatedContent = preg_replace('!\s+!', ' ', $translatedContent);
        }

        return trim($translatedContent);
    }

    public function processPhrase($translatedContent) {
        $meaningRows = Meaning::query()
            ->where('type', 'PHRASE')
            ->orderBy('priority', 'DESC')
            ->orderBy('word_length', 'DESC')
            ->get();

        foreach ($meaningRows as $item) {
            $translatedContent = str_replace($item->word, $item->meaning . ' ', $translatedContent);
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

        foreach ($syntaxMeaningRows as $item) {
            preg_match_all('/'.$item->pattern.'/u', $cleanContent, $matches);

            if (isset($matches[2])) {
                foreach ($matches[2] as $i => $word) {
                    $word = strip_tags($word);
                    $word = trim($word);

                    $str = Arr::get($matches[0], $i);
                    $str = strip_tags($str);
                    $str = trim($str);

                    $meaning = Meaning::query()
                        ->where('word', $word)
                        ->first();

                    $word1 = Arr::has($matches, 3) ? Arr::get($matches[3], $i) : null;
                    $word2 = Arr::has($matches, 4) ? Arr::get($matches[4], $i) : null;

                    if ($meaning) {
                        $meaningVn = str_replace('{any}', $meaning->meaning, $item->meaning);
                        if ($word1) {
                            $meaning1 = Meaning::query()
                                ->where('word', $word1)
                                ->first();

                            if ($meaning1) {
                                $meaningVn = str_replace('{any1}', $meaning1->meaning, $meaningVn);
                            }
                            else {
                                $meaningVn = str_replace('{any1}', $word1, $meaningVn);
                            }
                        }

                        if ($word2) {
                            $meaning2 = Meaning::query()
                                ->where('word', $word2)
                                ->first();

                            if ($meaning2) {
                                $meaningVn = str_replace('{any2}', $meaning1->meaning, $meaningVn);
                            }
                            else {
                                $meaningVn = str_replace('{any2}', $word2, $meaningVn);
                            }
                        }
                    }
                    else {
                        $meaningVn = str_replace('{any}', $word, $item->meaning);
                    }

                    $translatedContent = str_replace($str, $meaningVn . ' ', $translatedContent);
                }
            }
        }

        return trim($translatedContent);
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

        if (!in_array($type, [Meaning::TYPE['NAME'], Meaning::TYPE['PHRASE']])) {
            $meaning = mb_strtolower($meaning);
        }

        if (mb_strlen($chinese) < 2) {
            return response()->json([
                'message' => 'Word length minimum is 2'
            ]);
        }

        $chinese = trim($chinese);
        $chinese = str_replace("\n", "", $chinese);
        $chinese = str_replace("\r\n", "", $chinese);
        $meaning = trim($meaning);
        $meaning = str_replace("\n", "", $meaning);
        $meaning = str_replace("\r\n", "", $meaning);

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
        $m->sino = $this->processWordByWord($meaning);

        if ($exist) {
            $m->priority = $exist->priority + 1;
        }

        $m->save();

        return response()->json([
            'message' => sprintf("%s has been successfully added", $chinese),
            'data' => $m
        ]);
    }

    public function breakTextToArray($text): array {
        $arr = explode("。", $text);
        return array_filter($arr, function($value) {
            return !!$value;
        });
    }
}
