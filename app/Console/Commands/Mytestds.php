<?php

namespace App\Console\Commands;

use App\Models\Mytest;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;



class Mytestds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Mytestds';

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

        $this->testds();

    }

    private function testds()
    {
        Log::info('========开始测试=======');
        $mytest = new Mytest;
        $mytest->uid=26;
        $mytest->num=100;
        $m=$mytest->save();
        
        Log::info('=========结束测试======'.$m);
    }

}
