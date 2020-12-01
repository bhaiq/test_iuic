<?php

namespace App\Console\Commands;

use App\Models\KuangchiServiceCharge;
use Illuminate\Console\Command;

class SumServiceCharge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //计算手续费总数,给指定用户加上iuic(法币)
        $all_num = KuangchiServiceCharge::where('id','>',0)->sum('all_num');


    }
}
