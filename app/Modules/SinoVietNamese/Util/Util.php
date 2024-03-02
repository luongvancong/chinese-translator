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

    public static function thieuChuuMeaningToHtml($text) {
        $tempMeaningHtml = $text;

        $tempMeaningHtml = str_replace('\n', '<br/>', $tempMeaningHtml);
        $tempMeaningHtml = str_replace('\t', "", $tempMeaningHtml);

//        $tempMeaningHtml = nl2br($tempMeaningHtml);

        preg_match_all('/\p{Han}+/u', $tempMeaningHtml, $matches);
        $arrWord = array_unique($matches[0]);
        $arrWord = array_filter($arrWord, function($value) {
            return !!$value;
        });
        usort($arrWord, function($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });

        foreach ($arrWord as $index => $word) {
            $tempMeaningHtml = str_replace($word,'<strong>{{'.$index.'}}</strong>', $tempMeaningHtml);
        }

        for ($i = count($arrWord) - 1; $i >= 0; $i --) {
            $tempMeaningHtml = str_replace('{{'.$i.'}}', $arrWord[$i], $tempMeaningHtml);
        }

        return $tempMeaningHtml;
    }
}
