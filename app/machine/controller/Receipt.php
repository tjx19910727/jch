<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/10/14
 * Time: 17:07
 */

namespace app\machine\controller;


use app\BaseController;
use think\View;

class Receipt extends BaseController
{
    public function receipt(View $view)
    {
        $data = [
            "trade_no" => 123456,
            'total_price' => 12.99,
            'create_date' => date("Y-m-d"),
            'receipt_code1' => "",
            'receipt_code2' => "",
            'receipt_code3' => "",
        ];
        $view->assign($data);
        return $view->fetch();
    }
}