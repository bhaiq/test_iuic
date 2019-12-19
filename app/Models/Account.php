<?php

namespace App\Models;

use App\Libs\StringLib;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;
use function PHPSTORM_META\type;

/**
 * App\Models\Account
 *
 * @property int $id
 * @property int $uid
 * @property int $coin_id 币种ID
 * @property int $type 0 币币账户 1 法币账户
 * @property float $amount
 * @property float $amount_freeze
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property-read \App\Models\Coin $coin
 * @property-read mixed $amount_cny
 * @property-read mixed $amount_freeze_cny
 * @property-read mixed $cny
 * @property-read mixed $total
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereAmountFreeze($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereCoinId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereCoinType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Account whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Account extends Model
{
    protected $table = 'account';
    protected $fillable = ['uid', 'coin_id', 'amount', 'amount_freeze', 'type'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'coin_type' => 'array',
    ];
    const TYPE_CC = 0;
    const TYPE_LC = 1;


    protected $appends = [
        'total', 'amount_cny', 'amount_freeze_cny', 'cny'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'uid', 'id');
    }

    public function coin()
    {
        return $this->belongsTo('App\Models\Coin', 'coin_id', 'id');
    }

    public function getTotalAttribute()
    {
        return bcadd($this->amount, $this->amount_freeze, 8);
    }

    public function getAmountCnyAttribute()
    {
        return bcmul($this->amount, $this->getPrice(), 8);
    }

    public function getAmountFreezeCnyAttribute()
    {
        return bcmul($this->amount_freeze, $this->getPrice(), 8);
    }

    public function getCnyAttribute()
    {
        return bcmul($this->total, $this->getPrice(), 8);
    }

    public function toArray()
    {
        $data = parent::toArray();

        $this->attr_format($data, [
            'amount', 'amount_freeze', 'total', 'amount_cny', 'amount_freeze_cny', 'cny'
        ]);

        return $data;
    }

    protected function attr_format(array &$raw_data, array $rule_data): array
    {
        foreach ($rule_data as $v) {
            isset($raw_data[$v]) && $raw_data[$v] = StringLib::sprintN($raw_data[$v], 4);
        }
        return $raw_data;
    }

    public function getPrice()
    {
        $coin = Coin::whereName('USDT')->first();
        if ($coin->id == $this->coin_id) {
            return self::getRate();
        }
       
        $team = ExTeam::whereCoinIdLegal($coin->id)->whereCoinIdGoods($this->coin_id)->first();
        return ExTeam::curPrice($team->id) * self::getRate();
    }

    public static function getRate()
    {
        $key = 'ExchangeRate:usd-cn';

        $val = Redis::get($key);
        if ($val) {
            return $val;
        }

        $client = new Client();
        //        $response = $client->get('http://web.juhe.cn:8080/finance/exchange/rmbquot?type=1&bank=3&key=a2013292237a26379da1bc88d2309d5d');
        $response = $client->get('https://data.gateio.co/api2/1/ticker/usdt_cny');
        $data = json_decode($response->getBody()->getContents(), true);
        $res = $data['last'];

        Redis::setex($key, 60 * 60 * 3, $res);

        return $res;
    }

    // 用户账户余额递减
    public static function reduceAmount($uid, $coinId, $num, $type = Account::TYPE_LC)
    {

        Account::where(['uid' => $uid, 'coin_id' => $coinId, 'type' => $type])->decrement('amount', $num);

        \Log::info('ID为' . $uid . '用户ID为' . $coinId . '的币种数量减少' . $num, ['type' => $type]);

        return true;

    }

    // 用户账户余额递增
    public static function addAmount($uid, $coinId, $num, $type = Account::TYPE_LC)
    {

        Account::where(['uid' => $uid, 'coin_id' => $coinId, 'type' => $type])->increment('amount', $num);

        \Log::info('ID为' . $uid . '用户ID为' . $coinId . '的币种数量增加' . $num, ['type' => $type]);

        return true;

    }

    // 用户账户余额增加冻结
    public static function addFrozen($uid, $coinId, $num, $type = Account::TYPE_LC)
    {

        Account::where(['uid' => $uid, 'coin_id' => $coinId, 'type' => $type])->increment('amount_freeze', $num);

        \Log::info('ID为' . $uid . '用户ID为' . $coinId . '的币种冻结数量增加' . $num, ['type' => $type]);

        return true;

    }

    // 用户账户余额减少冻结
    public static function reduceFrozen($uid, $coinId, $num, $type = Account::TYPE_LC)
    {

        Account::where(['uid' => $uid, 'coin_id' => $coinId, 'type' => $type])->decrement('amount_freeze', $num);

        \Log::info('ID为' . $uid . '用户ID为' . $coinId . '的币种冻结数量减少' . $num, ['type' => $type]);

        return true;

    }

    // 获取币种信息的余额
    public static function getCoinCnyPrice($coinId)
    {
        $coin = Coin::whereName('USDT')->first();
        if ($coin->id == $coinId) {
            return self::getRate();
        }

        return ExTeam::curPrice(1) * self::getRate();
    }

}
