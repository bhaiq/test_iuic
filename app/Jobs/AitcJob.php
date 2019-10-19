<?php

namespace App\Jobs;

use App\Services\AitcCoinServer;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AitcJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $aitcCoinServer;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(AitcCoinServer $aitcCoinServer)
    {
        $this->aitcCoinServer = $aitcCoinServer;
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $transactionList = $this->aitcCoinServer->getTransactionList();

        var_dump($transactionList);
    }

    public function checkTransaction($hash) {

    }
}
