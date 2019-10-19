<?php
/**
 * Created by PhpStorm.
 * User: Asus
 * Date: 2019/9/25
 * Time: 10:44
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MallStore extends Model
{

    protected $table = 'mall_store';

    protected $guarded = [];

    public function getPicAttribute($value)
    {

        if(empty($value)){
            $value = url()->previous() . '/images/store_pic.png';
        }

        return $value;

    }

    public function getBgImgAttribute($value)
    {

        return url()->previous() . '/images/store_bg_img.png?' . rand(10000, 9999);

    }

}