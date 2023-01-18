<?php

namespace App\Console\Commands;

use App\Models\Meaning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InitMeaningData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:data:meaning';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Init data meaning';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $file = resource_path('txt/phienam.txt');

        $handle = fopen($file, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $lineData = str_replace("\n", '', $line);
                list($word, $meaning) = explode("=", $lineData);
                if ($word && $meaning) {
                    $meaningObj = new Meaning();
                    $meaningObj->word = $word;
                    $meaningObj->meaning = $meaning;
                    $meaningObj->priority = 1;
                    $meaningObj->save();
                    Log::info("Insert {$lineData}");
                }
            }

            fclose($handle);
        }


    }
}
