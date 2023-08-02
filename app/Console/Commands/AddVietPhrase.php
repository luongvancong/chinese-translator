<?php

namespace App\Console\Commands;

use App\Models\Meaning;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddVietPhrase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:vietphrase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add VietPhrase Data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ini_set('memory_limit', '-1');
        $file = resource_path('txt/vietphrase.txt');

        $handle = fopen($file, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $lineData = str_replace("\n", '', $line);
                list($word, $meaning) = explode("=", $lineData);

                $length = mb_strlen($word);
                $hasSlash = mb_strpos($word, '/');
                if ($word && $meaning && $length >= 4 && false === $hasSlash) {
                    DB::table('phrase')
                        ->upsert([
                            'phrase' => $word,
                            'sino' => $this->getSinoVietnamese($word),
                            'meaning' => $meaning,
                            'length' => $length,
                            'priority' => $length,
                            'created_at' => Carbon::now()->toISOString(),
                            'updated_at' => Carbon::now()->toISOString()
                        ], ['phrase'], [
                            'length' => $length,
                            'priority' => $length
                        ]);
                    Log::info("Insert {$lineData}");
                }
            }

            fclose($handle);
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
