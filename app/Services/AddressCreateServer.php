<?php


namespace App\Services;


use App\Models\Wallet;


class AddressCreateServer
{


    /**
     * @param $uid
     * @param $type 1:eth, 2:omini, 3:cosmos, 50: aitc.9
     * @return Wallet|\Illuminate\Database\Eloquent\Model
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createAddress($uid, $type)
    {
        switch ($type) {
            case Wallet::TYPE_ETH:
                return (new EthService())->createEthAddress($uid);
            case Wallet::TYPE_AITC9:
                return (new AitcCoinServer())->getNewAddress();
        }
    }

    public function addressSave($uid, $type)
    {
        $find = Wallet::whereUid($uid)->where('type', $type)->first();
        if($find == null) {
            $wallet = new Wallet;
            $wallet->address = $this->createAddress($uid, $type);
            $wallet->type = $type;
            $wallet->uid = $uid;
            $wallet->save();
            return $wallet->address;
        }else{
            return $find['address'];
        }
    }
}
