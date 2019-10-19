<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\OtcOrder;
use App\Models\UsLog;
use Illuminate\Support\Facades\Redis;

class OtcOrderService
{
    /**
     * @var OtcOrder|OtcOrder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    private $order;

    public function __construct(int $id)
    {
        $this->order = OtcOrder::findOrFail($id);
    }

    public function pay()
    {
        $this->isBuyerOrFail();
        $this->isAppealAndFail();
        $this->isUnderwayOrFail();

        if ($this->isPay()) abort(400, trans('otcOrder.status_has_pay'));

        $this->order->is_pay = true;
        $this->order->save();

    }

    public function payCoin()
    {
        $this->isSellerOrFail();
        $this->isAppealAndFail();
        $this->isUnderwayOrFail();

        if (!$this->isPay()) abort(400, trans('otcOrder.error_pay_not'));
        if ($this->isPayCoin()) abort(400, trans('otcOrder.error_has_pay_coin'));

        $this->lockOrFail(__FUNCTION__);

        $this->transferCoin();
        $this->order->is_pay_coin = true;
        $this->order->status      = OtcOrder::STATUS_OVER;

        $this->order->save();

        $this->log();
    }

    public function log()
    {
        $buyer = $this->getBuyer();
        Service::account()->createLog($buyer->id, $this->order->coin_id, $this->order->amount, AccountLog::SCENE_LEGAL_IN);
        $seller = $this->getSeller();
        Service::account()->createLog($seller->id, $this->order->coin_id, $this->order->amount, AccountLog::SCENE_LEGAL_OUT);
    }

    public function appeal()
    {
        $this->isAppealAndFail();
        $this->order->appeal_uid = Service::auth()->getUser()->id;
        $this->order->save();
    }

    public function del()
    {
        $this->isPayAndFail();
        $this->isUnderwayOrFail();

        switch ($this->order->type) {
            case OtcOrder::TYPE_SELL:
                if (Service::auth()->getUser()->id == $this->getSeller()->id) abort(400, trans('system.illegal'));
                $this->order->otcPublishSell()->increment('amount_lost', $this->order->amount);
                $publish = $this->order->otcPublishSell;
                break;
            case OtcOrder::TYPE_BUY:
                if (Service::auth()->getUser()->id == $this->getBuyer()->id) abort(400, trans('system.illegal'));
                $this->order->otcPublishBuy()->increment('amount_lost', $this->order->amount);
                $publish = $this->order->otcPublishBuy;

                $this->account($this->getSeller()->id)->increment('amount', $this->order->amount);
                $this->account($this->getSeller()->id)->decrement('amount_freeze', $this->order->amount);
                break;
        }

        $publish->is_over = 0;
        $publish->save();

        $this->order->status = OtcOrder::STATUS_CANCEL;
        $this->order->save();
    }

    public function transferCoin()
    {
        $this->account($this->getSeller()->id)->decrement('amount_freeze', $this->order->amount);
        $this->account($this->getBuyer()->id)->increment('amount', $this->order->amount);
    }

    /**
     * @param int $uid
     * @return Account|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function account(int $uid)
    {
        return Account::whereUid($uid)->whereCoinId($this->order->coin_id)->whereType(Account::TYPE_LC)->first();
    }

    public function isUnderwayOrFail()
    {
        if (!$this->isUnderway()) {
            switch ($this->order->status) {
                case OtcOrder::STATUS_OVER:
                    return abort(400, '订单已结束!');
                    break;
                case OtcOrder::STATUS_CANCEL:
                    return abort(400, '订单已取消');
                    break;
            }
        }
    }

    /**
     * @return bool
     */
    public function isUnderway()
    {
        return $this->order->status == OtcOrder::STATUS_INIT;
    }

    public function isAppealAndFail()
    {
        $this->isAppeal() && abort(400, trans('otcOrder.error_appeal_on'));
    }

    /**
     * @return bool
     */
    public function isAppeal()
    {
        return boolval($this->order->appeal_uid);
    }

    public function isPayAndFail()
    {
        $this->isPay() && abort(400, trans('otcOrder.error_has_pay'));
    }

    /**
     * @return bool|mixed
     */
    public function isPay()
    {
        return $this->order->is_pay;
    }

    /**
     * @return bool|mixed
     */
    public function isPayCoin()
    {
        return $this->order->is_pay_coin;
    }

    /**
     * 判断是否是下单人
     *
     * @return bool
     */
    public function isOwner()
    {
        return Service::auth()->getUser()->id == $this->order->uid;

    }

    /**
     * 判断是否是发布者
     *
     * @return bool
     */
    public function isPublisher()
    {
        return Service::auth()->getUser()->id == $this->getPublisher()->id;
    }

    /**
     * 获取发布人
     *
     * @return \App\Models\User
     */
    public function getPublisher()
    {
        return $this->order->otcPublish->user;
    }

    public function isBuyerOrFail()
    {
        $this->isBuyer() || abort(400, trans('system.illegal'));
    }

    public function isSellerOrFail()
    {
        $this->isSeller() || abort(400, trans('system.illegal'));
    }

    /**
     * @return bool
     */
    public function isSeller()
    {
        return $this->getSeller()->id == Service::auth()->getUser()->id;
    }

    /**
     * 判断是否是购买者
     *
     * @return bool
     */
    public function isBuyer()
    {
        return $this->getBuyer()->id == Service::auth()->getUser()->id;
    }

    /**
     * @return \App\Models\User|mixed
     */
    public function getBuyer()
    {
        return $this->order->buyer;
    }

    /**
     * @return \App\Models\User|mixed
     */
    public function getSeller()
    {
        return $this->order->seller;
    }

    public function lockOrFail($func)
    {
        $key = $this->redisLockKey($func);
        if (Redis::get($key)) {
            abort(400, trans('otcOrder.error_order_on'));
        } else {
            $this->redisLock($key);
        }
    }

    public function redisLockKey($func)
    {
        return $key = __CLASS__ . '_' . $func . '_' . $this->order->id;
    }

    public function redisLock($key)
    {
        Redis::set($key, $this->order->id);
    }

}
