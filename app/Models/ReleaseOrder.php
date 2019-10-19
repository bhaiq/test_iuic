<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/7/6
 * Time: 16:54
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseOrder extends Model
{

    protected $table = 'release_order';

    protected $guarded = [];

    public function scopeUnFinish($query)
    {
        $query->where('status', 0);
    }

}