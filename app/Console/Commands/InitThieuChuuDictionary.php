<?php

namespace App\Console\Commands;

use App\Models\ThieuChuuDictionary;
use Carbon\Carbon;
use Illuminate\Console\Command;

class InitThieuChuuDictionary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:thieu-chuu-dictionary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Init Thieu Chuu Dictionnay';

    public function handle() {
        $file = resource_path('txt/thieuchuu.txt');

        $text = 'nhất [yi1]\n\t1. Một, là số đứng đầu các số đếm. Phàm vật gì chỉ có một đều gọi là Nhất cả.\n\t2. Cùng. Như sách Trung Dung 中庸 nói. Cập kì thành công nhất dã 及其成工一也 nên công cùng như nhau vậy. \n\t3. Dùng về lời nói hoặc giả thế chăng. Như vạn nhất 萬一 muôn một, nhất đán 一旦 một mai, v.v. \n\t4. Bao quát hết thẩy. Như nhất thiết 一切 hết thẩy, nhất khái 一概 một mực như thế cả, v.v. \n\t5. Chuyên môn về một mặt. Như nhất vị 一味 một mặt, nhất ý 一意 một ý, v.v.
';
        $tempMeaningHtml = \App\Modules\SinoVietNamese\Util\Util::thieuChuuMeaningToHtml($text);

        dd($tempMeaningHtml);

        $handle = fopen($file, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $lineData = str_replace("\n", '', $line);
                list($word, $meaning) = explode("=", $lineData);

                $tempMeaningHtml = \App\Modules\SinoVietNamese\Util\Util::thieuChuuMeaningToHtml($meaning);

                ThieuChuuDictionary::query()
                    ->insertOrIgnore([
                        'word' => $word,
                        'sino' => \App\Modules\SinoVietNamese\Util\Util::getSinoVietNamese($word),
                        'meaning' => $meaning,
                        'meaning_html' => $tempMeaningHtml,
                        'created_at' => Carbon::now()->toISOString(),
                        'updated_at' => Carbon::now()->toISOString(),
                    ]);
            }
        }
    }
}
