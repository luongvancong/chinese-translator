<?php

namespace App\Http\Controllers\Frontend\Home;

use App\Http\Controllers\Controller;
use App\Models\Meaning;
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
            preg_match_all('/./u', $chinese, $matches);
            $arrWord = $matches[0];
            $arrWord = array_filter($arrWord, function($value) {
                return !!$value;
            });
            $meaning = Meaning::query()
                ->whereIn('word', $arrWord)
                ->get();

            $translatedContent = $chinese;
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
