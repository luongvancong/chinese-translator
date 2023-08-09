<?php

namespace App\Console\Commands;

use App\Models\Meaning;
use App\Modules\SinoVietNamese\Util\Util;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateSino extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:sino';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update sino';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ini_set('memory_limit', '-1');

        $meaningRows = Meaning::query()
            ->whereNull('sino')
            ->get();

        $affectedRows = 0;
        foreach ($meaningRows as $row) {
            $row->sino = \App\Modules\SinoVietNamese\Util\Util::getSinoVietNamese($row->word);
            $row->save();
            $affectedRows ++;
        }

        Log::info(sprintf('Row affected is: %s', $affectedRows));

    }


}
