<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\AitcTransaction;
use App\Models\Wallet;
use App\Services\AitcCoinServer;
use App\Services\Service;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AitcTransactionCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AitcTransactionCheck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $aitcCoinServer;
    protected $aitcTransaction;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->aitcCoinServer = new AitcCoinServer();
        $this->aitcTransaction = new AitcTransaction();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Log::info('AitcTransactionCheck ：' . Carbon::now());
        $transactionList = $this->aitcCoinServer->getTransactionList();

        foreach ($transactionList as $key => $value) {
            $this->checkTransaction($value);
        }
    }

    public function checkTransaction($transaction)
    {
        $find = AitcTransaction::whereUnit($transaction['unit'])->first();

        $transactio = [
            'unit' => $transaction['unit'],
            'action' => $transaction['action'],
            'amount' => '',
            'my_address' => '',
            'address_to' => '',
            'extends' => json_encode($transaction)
        ];

        $res = '';
        if($find === null && $transaction['confirmations']) {
            switch ($transaction['action']) {
                case 'sent':
                    $res = $this->handleSentTypeTransaction($transaction);
                    break;
                case  'moved':
                    $res = $this->handleMovedTypeTransaction($transaction);
                    break;
                case  'received':
                    $res = $this->handleReceivedTypeTransaction($transaction);
                    break;
            }
            $transactio['amount'] = $res['amount'];
            $transactio['my_address'] = $res['my_address'];
            $transactio['address_to'] = $res['address_to'];

            DB::transaction(function() use ($transactio) {
                AitcTransaction::create($transactio);
                if($transactio['action'] == 'received') {
                    $this->topUpAmount($transactio);
                }
            });

        }
    }

    public function handleSentTypeTransaction($transaction) {
        return [
            'my_address' => null,
            'address_to' => $transaction['addressTo'],
            'amount' => $transaction['amount'] / (10 ** 8)
        ];
    }

    public function handleMovedTypeTransaction($transaction) {
        return [
            'my_address' => $transaction['my_address'],
            'address_to' => $transaction['addressTo'],
            'amount' => $transaction['amount'] / (10 ** 8)
        ];
    }

    public function handleReceivedTypeTransaction($transaction) {
        return [
            'my_address' => $transaction['my_address'],
            'address_to' => null,
            'amount' => $transaction['amount'] / (10 ** 8)
        ];
    }

    public function topUpAmount($transaction) {
//        var_dump($transaction['my_address']);
        $uid = Wallet::whereAddress($transaction['my_address'])->where('type', 50)->value('uid');
        if($uid !== null) {
            $coinId = 3;
            Account::whereUid($uid)->where('coin_id', $coinId)->whereType(Account::TYPE_CC)->increment('amount', $transaction['amount']);
            Service::account()->createLog($uid, $coinId, $transaction['amount'],AccountLog::SCENE_RECHARGE, compact('hash'));
        }
    }
}
