<?php

namespace App\Models;

/**
 * App\Models\ModePay
 *
 * @property int        $id
 * @property int        $uid
 * @property string     $qr_code 二维码
 * @property string     $number 支付号码
 * @property string     $name 支付机构名
 * @property int        $type 支付类型 0 微信 1 支付宝 2 银行卡
 * @property int|null   $created_at
 * @property int|null   $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay whereQrCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read mixed $mode_name
 * @property array      $bank 银行信息
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ModePay whereBank($value)
 */
class ModePay extends Model
{
    protected $table = 'mode_of_payment';
    protected $fillable = ['uid', 'qr_code', 'number', 'type', 'name', 'bank'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'bank'       => 'array',
    ];
    protected $appends = [
        'mode_name'
    ];

    const TYPE_WECHAT = 0;
    const TYPE_ALI = 1;
    const TYPE_BANK = 2;

    public function getModeNameAttribute()
    {
        return $this->trans('ModePay.type.' . $this->type);
    }
}
