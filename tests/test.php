<?php

$regex = '\d{4}年';
$text = '2020年';


function convertYearString($input) {
    // Sử dụng biểu thức chính quy để tìm chuỗi có dạng "YYYY年"
    $pattern = '/(\d{4})年/';
    $replacement = 'năm $1';

    // Sử dụng hàm preg_replace để thay thế
    $output = preg_replace($pattern, $replacement, $input);

    return $output;
}

//echo convertYearString('2020年'); // năm 2020


function convertRegexMeaning($text) {
    $arrRegex = [
        "#对(.*)来说(.*)是(.*)#",
        '#接下来看(.+)在干什么#'
    ];

    $arrReplace = ['đối với $1 mà nói thì $2 là $3', 'tiếp nhìn xem $1 đang làm gì'];

    return preg_replace($arrRegex, $arrReplace, $text);


    $output = "";
    foreach ($arrRegex as $regex => $replacement) {
        $output = preg_replace($regex, $replacement, $text);
    }

    $regex = '/对(.*)来说(.*)是(.*)/';
//    return preg_replace($regex, 'đối với $1 mà nói thì $2 là $3', $text);

    return $output;
}

var_dump(convertRegexMeaning('对AAA来说BBB是CCC'));
var_dump(convertRegexMeaning('接下来看YYYY在干什么'));

