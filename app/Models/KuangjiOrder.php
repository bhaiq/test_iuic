<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/10/16
 * Time: 11:55
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KuangjiOrder extends Model
{

    protected $table = 'kuangji_order';

    protected $fillable = ['total_day'];

    protected $guarded = [];

}