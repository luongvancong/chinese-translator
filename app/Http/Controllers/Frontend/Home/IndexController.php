<?php

namespace App\Http\Controllers\Frontend\Home;

use App\Http\Controllers\Controller;
use App\Models\Meaning;
use App\Models\SyntaxMeaning;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class IndexController extends Controller
{
    public function index() {
        return view('frontend/home/index');
    }

    public function postTranslate(Request $request) {
        $chinese = $request->get('chinese');
        $translatedContent = "";
        if ($chinese) {
            $translatedContent = $chinese;

            $translatedContent = $this->processName($translatedContent);
            $translatedContent = $this->processWithSyntax($translatedContent);

            $meaningRows = Meaning::query()
                ->where('priority', '>', 1)
                ->orderBy('priority', 'DESC')
                ->orderBy('word_length', 'DESC')
                ->get();

            foreach ($meaningRows as $item) {
                $translatedContent = str_replace($item->word, $item->meaning . ' ', $translatedContent);
            }

            preg_match_all('/\p{Han}/u', $translatedContent, $matches);
            $arrWord = array_unique($matches[0]);
            $meaning = Meaning::query()
                ->whereIn('word', $arrWord)
                ->orderBy('priority', 'DESC')
                ->get();

            foreach ($meaning as $item) {
                $translatedContent = str_replace($item->word, $item->meaning . ' ', $translatedContent);
                $translatedContent = preg_replace('!\s+!', ' ', $translatedContent);
                $translatedContent = str_replace('、', ',', $translatedContent);
                $translatedContent = str_replace('，', ',', $translatedContent);
                $translatedContent = str_replace(' ,', ', ', $translatedContent);
                $translatedContent = str_replace('。', '.', $translatedContent);
                $translatedContent = str_replace(' .', '. ', $translatedContent);
                $translatedContent = str_replace("\r\n", '<br/>', $translatedContent);
                $translatedContent = str_replace("\n", '<br/>', $translatedContent);
            }

            $translatedContent = str_replace('；', ', ', $translatedContent);
            $translatedContent = str_replace('、', ', ', $translatedContent);
            $translatedContent = str_replace('。', '. ', $translatedContent);
        }


        return response()->json([
            'translatedContent' => $translatedContent
        ]);
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

        return $translatedContent;
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

        return $translatedContent;
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

        $exist = Meaning::query()
            ->where('word', $chinese)
            ->where('priority', mb_strlen($chinese))
            ->where('type', $type)
            ->first();

        if ($exist) {
            return response()->json([
                'message' => sprintf("%s with priority %s has been existed", $chinese, mb_strlen($chinese))
            ], 400);
        }

        $m = new Meaning();
        $m->word = $chinese;
        $m->meaning = $meaning;
        $m->type = $type;
        $m->priority = mb_strlen($chinese);
        $m->word_length = mb_strlen($chinese);
        $m->save();

        return response()->json([
            'message' => sprintf("%s has been successfully added", $chinese),
            'data' => $m
        ]);
    }
}
