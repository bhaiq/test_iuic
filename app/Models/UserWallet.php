<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/12/18
 * Time: 10:13
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{

    protected $table = 'user_wallet';

    protected $guarded = [];

    protected $appends = [
        'total', 'energy_cny', 'energy_frozen_cny', 'total_cny'
    ];

    public static function addEnergyFrozenNum($uid, $num)
    {
        UserWallet::where('uid', $uid)->increment('energy_frozen_num', $num);

        \Log::info('用户' . $uid . '的能量冻结数量增加' . $num);

        return true;
    }

    public function getTotalAttribute()
    {
        return bcadd($this->energy_num, $this->energy_frozen_num, 8);
    }

    public function getEnergyCnyAttribute()
    {
        return bcmul($this->energy_num, $this->getEnergyCny(), 8);
    }

    public function getEnergyFrozenCnyAttribute()
    {
        return bcmul($this->energy_frozen_num, $this->getEnergyCny(), 8);
    }

    public function getTotalCnyAttribute()
    {
        return bcadd($this->total, $this->getEnergyCny(), 8);
    }

    // 获取能量资产对人民币的比例
    public function getEnergyCny()
    {
         return config('shop.energy_cny', 1);
    }

}