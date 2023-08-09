<?php

namespace App\Modules\SinoVietNamese\Util;

use App\Models\SinoVietNamese;

class Util
{
    public static function getSinoVietNamese($chinese) {
        preg_match_all('/\p{Han}/u', $chinese, $matches);
        $arrWord = array_unique($matches[0]);

        $meaning = SinoVietNamese::query()
            ->whereIn('word', $arrWord)
            ->get();

        foreach ($meaning as $item) {
            $chinese = str_replace($item->word, $item->sino . ' ', $chinese);
        }

        $chinese = str_replace(' ，', ', ', $chinese);

        return trim($chinese);
    }
}
