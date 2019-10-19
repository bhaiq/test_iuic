<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * App\Models\SymbolHistory
 *
 * @property int      $id
 * @property int      $team_id 交易对ID
 * @property float    $o 开盘价
 * @property float    $c 收盘价
 * @property float    $h 最高价
 * @property float    $l 最低价
 * @property float    $v 成交量
 * @property int|null $created_at
 * @property int|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory whereC($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory whereH($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory whereL($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory whereO($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory whereV($value)
 * @mixin \Eloquent
 * @property int      $type 0 一分钟线; 1 15分钟线; 2 1小时; 3 4小时线; 4 1天线;5 周线
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SymbolHistory whereType($value)
 */
class SymbolHistory extends Model
{
    protected $table = 'symbol_history';
    protected $fillable = ['team_id', 'o', 'c', 'h', 'l', 'v', 'created_at', 'type'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    const TYPE_1 = 0;
    const TYPE_15 = 1;
    const TYPE_1H = 2;
    const TYPE_4H = 3;
    const TYPE_1D = 4;
    const TYPE_W = 5;

    /**
     * @param int $team_id
     * @return SymbolHistory|\Illuminate\Database\Eloquent\Model
     */
    public static function getByCreated(int $team_id)
    {
        $created_at = Carbon::createFromDate(date('Y-m-d H:i'))->toDateTimeString();

        $model = self::where(['type' => self::TYPE_1, 'team_id' => $team_id, 'created_at' => $created_at])->first();

        if (!$model) {

            $last = SymbolHistory::whereTeamId($team_id)->whereType(self::TYPE_1)->orderBy('id', 'desc')->first();
            if (!$last) {
                $c = 0;
            } else {
                $c = $last->c;
            }

            $model = self::create(['type' => self::TYPE_1, 'team_id' => $team_id, 'created_at' => $created_at, 'o' => $c]);
        }

        return $model;
    }

    public static function getType($val)
    {
        switch ($val) {
            case 1:
                return self::TYPE_1;
            case 15:
                return self::TYPE_15;
            case 60:
                return self::TYPE_1H;
            case 240:
                return self::TYPE_4H;
            case 'D':
                return self::TYPE_1D;
            case 'W':
                return self::TYPE_W;
            default:
                return 0;
        }
    }

}
