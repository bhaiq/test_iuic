<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreaditTransfer extends Model
{
    //
    protected $table = 'ecology_creadit_transfer';

    protected $fillable = ['uid','num','charge_rate','service_charge','true_num','created_at','updated_at','usdt_cny'];

}
