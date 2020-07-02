<?php

namespace App\Http\Controllers;

use App\Constants\HttpConstant;
use Illuminate\Http\Request;
use  \Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{

    public $service;
    public $user;

    public function __construct()
    {
    }

    /**
     * @param       $post
     * @param       $rules
     * @param array $message
     * @param array $attribute
     */
    public function validate($post, $rules, $message = [], $attribute = [])
    {
        $validator = Validator::make($post, $rules, $message, $attribute);
        if ($validator->fails()) {
            $str = '';
            foreach ($validator->errors()->toArray() as $k => $v) {
                //                $str .= $k . ':';
                foreach ($v as $val) {
                    $str .= $val;
                }
                $str .= ';    ';
            };
            $this->responseError($str);
        }
    }

    public function responseError(string $message, $code = HttpConstant::CODE_400_BAD_REQUEST)
    {
        abort($code, json_encode(['detail' => trans($message)]));
    }

    /**
     * @param string $message
     * @param int    $code
     */
    public function responseSuccess(string $message = 'system.success', $code = HttpConstant::CODE_200_OK)
    {
        abort($code, json_encode(['detail' => trans($message)]));
    }

    /**
     * @param array $data
     * @param int   $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function response(array $data, $code = HttpConstant::CODE_200_OK)
    {
        return response()->json($data, $code);
    }

    /**
     * @param         $build
     * @param Request $request
     * @param string  $order
     * @param string  $sort
     * @return array
     */
    public function page($build, Request $request, string $order = 'id', string $sort = 'desc')
    {
        $page_count = $request->get('page_count', 10);

        $request->has('last_id') && $build->where('id', '<', $request->get('last_id'));

        $data = $build->orderBy($order, $sort)->take($page_count)->get()->toArray();

        $last_id = $request->get('last_id', 0);

        return [
            'last_id'    => $last_id + (count($data) >= 1 ? $data[count($data) - 1]['id'] : 0),
            'page_count' => $page_count,
            'is_last'    => count($data) < $page_count,
            'data'       => $data,
        ];

    }

    /**
     * @param         $build
     * @param array   $parameters
     * @param Request $request
     */
    public function select($build, array $parameters, Request $request)
    {
        foreach ($parameters as $k => $v) {
            if ($request->has($k)) {
                if ($v) {
                    $this->validate($request->only($k), [
                        $k => $v
                    ]);
                }

                $build->where($k, $request->get($k));
            }

        }
    }
}
