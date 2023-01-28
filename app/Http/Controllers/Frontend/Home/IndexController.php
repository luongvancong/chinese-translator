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
            $chineseClean = strip_tags($chinese);

            $translatedContent = $chinese;
            $translatedContent = str_replace('、', ', ', $translatedContent);
            $translatedContent = str_replace('。', '. ', $translatedContent);

            $translatedContent = $this->processWithSyntax($translatedContent);


//            dd($translatedContent);

            for ($i = 15; $i >= 2; $i--) {
                $meaningRows = Meaning::query()
                    ->where('priority', $i)
//                    ->where('id', '4ebaeea4-c1da-48d3-993f-285e2d5950a5')
                    ->orderBy('word_length', 'DESC')
                    ->get();

                foreach ($meaningRows as $item) {
                    $translatedContent = str_replace($item->word, $item->meaning . ' ', $translatedContent);
//                    dd($translatedContent);
                }
            }

            preg_match_all('/\p{Han}/u', $translatedContent, $matches);
            $arrWord = array_unique($matches[0]);
            $meaning = Meaning::query()
                ->whereIn('word', $arrWord)
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
        }


        return response()->json([
            'translatedContent' => $translatedContent
        ]);
    }

    public function processWithSyntax($translatedContent) {
        $cleanContent = strip_tags($translatedContent);
        $syntaxMeaningRows = SyntaxMeaning::query()
//            ->where('id', '8616f862-d0bb-45ca-89e0-0a2fd752e3b3')
            ->orderBy('priority', 'DESC')
            ->get();

        foreach ($syntaxMeaningRows as $item) {
            preg_match_all('/'.$item->pattern.'/u', $cleanContent, $matches);

//            dd($matches);

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
}
