<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/15
 * Time: 11:12
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinExtract extends Model
{

    protected $table = 'coin_extract';

    protected $guarded = [];

    const STATUS = [
        0 => '审核中',
        1 => '成功',
        9 => '失败',
    ];

}