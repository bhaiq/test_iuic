<?php
/**
 * Created by PhpStorm.
 * User: ouakira
 * Date: 2019/4/15
 * Time: 6:23 PM
 */

namespace App\Models;

use App\Constants\HttpConstant;
use App\Libs\StringLib;
use Illuminate\Database\Eloquent\Model as BaseModle;

/**
 * App\Models\Model
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Model newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Model newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Model query()
 * @mixin \Eloquent
 */
class Model extends BaseModle
{
    /**
     * @param mixed $id
     * @param array $columns
     * @return \Illuminate\Support\Collection|null|static
     * @throws \Exception
     */
    public static function findOrFail($id, $columns = ['*'])
    {
        $model = self::find($id, $columns);

        if (!$model) abort(HttpConstant::CODE_400_BAD_REQUEST, "记录不存在");

        return $model;
    }

    /**
     * @param string $val
     * @param array  $data
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    public function trans(string $val, array $data = [])
    {
        return trans('model.' . $val, $data);
    }

    /**
     * @param array $raw_data
     * @param array $rule_data
     * @return array
     */
    public function num_formate(array &$raw_data, array $rule_data): array
    {
        foreach ($rule_data as $k => $v) {
            isset($raw_data[$k]) && $raw_data[$k] = StringLib::sprintN($raw_data[$k], $v);
        }
        return $raw_data;
    }

}
