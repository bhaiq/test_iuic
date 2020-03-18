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
        'total', 'energy_cny', 'energy_frozen_cny', 'total_cny', 'consumer_cny'
    ];

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
        return bcmul($this->total, $this->getEnergyCny(), 8);
    }

    public function getConsumerCnyAttribute()
    {
        return bcmul($this->consumer_num, $this->getEnergyCny(), 8);
    }

    // 获取能量资产对人民币的比例
    public function getEnergyCny()
    {
         return config('shop.energy_cny', 1);
    }

    public static function getCnyEnergy()
    {
        return config('shop.energy_cny', 1);
    }

    // 验证用户余额是否充足
    public static function checkWallet($uid, $num)
    {

        $uw = UserWallet::where('uid', $uid)->first();
        if(!$uw || $uw->energy_num < $num){
            return 0;
        }

        return 1;

    }

    // 能量冻结数量增加
    public static function addEnergyFrozenNum($uid, $num)
    {
        UserWallet::where('uid', $uid)->increment('energy_frozen_num', $num);

        \Log::info('用户' . $uid . '的能量冻结数量增加' . $num);

        return true;
    }

    // 能量冻结数量减少
    public static function reduceEnergyFrozenNum($uid, $num)
    {
        UserWallet::where('uid', $uid)->decrement('energy_frozen_num', $num);

        \Log::info('用户' . $uid . '的能量冻结数量减少' . $num);

        return true;
    }

    // 能量资产增加
    public static function addEnergyNum($uid, $num)
    {
        UserWallet::where('uid', $uid)->increment('energy_num', $num);

        \Log::info('用户' . $uid . '的能量数量增加' . $num);

        return true;
    }

    // 能量资产减少
    public static function reduceEnergyNum($uid, $num)
    {
        UserWallet::where('uid', $uid)->decrement('energy_num', $num);

        \Log::info('用户' . $uid . '的能量数量减少' . $num);

        return true;
    }

    // 消费者积分增加
    public static function addConsumerNum($uid, $num)
    {
        UserWallet::where('uid', $uid)->increment('consumer_num', $num);

        \Log::info('用户' . $uid . '的消费者积分数量增加' . $num);

        return true;
    }

    // 消费者积分减少
    public static function reduceConsumerNum($uid, $num)
    {
        UserWallet::where('uid', $uid)->decrement('consumer_num', $num);

        \Log::info('用户' . $uid . '的消费者积分数量减少' . $num);

        return true;
    }

    // 锁仓能量资产增加
    public static function addEnergyLockNum($uid, $num)
    {
        UserWallet::where('uid', $uid)->increment('energy_lock_num', $num);

        \Log::info('用户' . $uid . '的锁仓能量数量增加' . $num);

        return true;
    }

    // 锁仓能量资产减少
    public static function reduceEnergyLockNum($uid, $num)
    {
        UserWallet::where('uid', $uid)->decrement('energy_lock_num', $num);

        \Log::info('用户' . $uid . '的锁仓能量数量减少' . $num);

        return true;
    }

}