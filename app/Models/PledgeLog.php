<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/12/24
 * Time: 16:43
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PledgeLog extends Model
{

    protected $table = 'pledge_log';

    protected $guarded = [];

    const STATUS_NAME = [
        0 => '审核中',
        1 => '已完成',
        2 => '已失败',
    ];

}