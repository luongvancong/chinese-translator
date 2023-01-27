<?php

namespace App\Http\Controllers\Frontend\Home;

use App\Http\Controllers\Controller;
use App\Models\Meaning;
use App\Models\SyntaxMeaning;
use Illuminate\Http\Request;

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
            preg_match_all('/\p{Han}/u', $chineseClean, $matches);
            $arrWord = array_unique($matches[0]);

            $translatedContent = $chinese;

            // Ưu tiên dịch theo mẫu trước
            $syntaxMeaningRows = SyntaxMeaning::query()
                ->orderBy('priority', 'DESC')
//                ->where('id', 'b77f649d-c5ea-42fb-a33b-671d8364012f')
                ->get();
            foreach ($syntaxMeaningRows as $item) {
                preg_match('/'.$item->pattern.'/u', $chineseClean, $matches);

                if (isset($matches[2])) {
                    $str = $matches[0];
                    $word = $matches[2];
                    $word1 = $matches[3] ?? null;
                    $meaning = Meaning::query()
                        ->where('word', $word)
                        ->first();

                    if ($word1) {
                        $meaning1 = Meaning::query()
                            ->where('word', $word1)
                            ->first();
                    }

                    if ($meaning) {
                        $meaningVn = str_replace('{any}', $meaning->meaning, $item->meaning);
                        if ($word1) {
                            $meaningVn = str_replace('{any1}', $meaning1->meaning, $meaningVn);
                        }
                        $translatedContent = str_replace($str, $meaningVn . ' ', $translatedContent);
                    }
                }
            }

            for ($i = 15; $i >= 2; $i--) {
                $meaningRows = Meaning::query()
                    ->where('priority', $i)
                    ->orderBy('word_length', 'DESC')
                    ->get();

                foreach ($meaningRows as $item) {
                    $translatedContent = str_replace($item->word, $item->meaning . ' ', $translatedContent);
                }
            }

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
}
