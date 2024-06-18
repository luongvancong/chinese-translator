<?php

namespace App\Console\Commands;

use App\Models\Meaning;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddNamed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:named';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Named Data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ini_set('memory_limit', '-1');
        $files = [
            resource_path('csv/1.60_hoa_giap.csv'),
            resource_path('csv/2.cach-cuc.csv'),
            resource_path('csv/3.thap-than.csv'),
            resource_path('csv/4.thuat-ngu.csv'),
            resource_path('csv/5.than-sat.csv')
        ];

        foreach ($files as $file) {
            $data = [];
            if (($handle = fopen($file, 'r')) !== false) {
                // Đọc từng dòng của file CSV
                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    $data[] = $row;
                }
                fclose($handle);
            }

            foreach ($data as $row) {
                $length = mb_strlen($row[0]);

                DB::table('meaning')
                    ->upsert([
                        'word' => $row[0],
                        'sino' => $this->getSinoVietnamese($row[0]),
                        'meaning' => $row[1],
                        'priority' => $length,
                        'type' => 'NAME',
                        'created_at' => Carbon::now()->toISOString(),
                        'updated_at' => Carbon::now()->toISOString()
                    ], ['word', 'priority', 'type'], [
                        'priority' => $length,
                        'meaning' => $row[1]
                    ]);
            }
        }
    }

    public function getSinoVietnamese($content)
    {
        preg_match_all('/\p{Han}/u', $content, $matches);
        $arrWord = array_unique($matches[0]);

        $meaning = Meaning::query()
            ->whereIn('word', $arrWord)
            ->orderBy('priority', 'DESC')
            ->get();

        foreach ($meaning as $item) {
            $content = str_replace($item->word, $item->sino . ' ', $content);
        }

        $content = str_replace(' ，', ', ', $content);

        return trim($content);
    }
}
