<?php

namespace App\Console\Commands;

use App\Models\Meaning;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddPhraseFromJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:phrase-from-json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Phrase Data From JSON';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ini_set('memory_limit', '-1');
        $file = resource_path('json/phrase.json');

        $json = file_get_contents($file);
        $json = json_decode($json, true);

        foreach ($json as $cn => $vn) {
            $length = mb_strlen($cn);
            $hasSlash = mb_strpos($cn, '/');
            if ($cn && $vn && $length >= 4 && false === $hasSlash) {
                DB::table('phrase')
                    ->upsert([
                        'phrase' => $cn,
                        'sino' => $this->getSinoVietnamese($cn),
                        'meaning' => $vn,
                        'length' => $length,
                        'priority' => $length,
                        'created_at' => Carbon::now()->toISOString(),
                        'updated_at' => Carbon::now()->toISOString()
                    ], ['phrase'], [
                        'length' => $length,
                        'priority' => $length
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
