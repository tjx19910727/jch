<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/1/11
 * Time: 16:32
 */

namespace AliPay\trade\page;


use AliPay\Kernel\BaseClient;

class PageClient extends BaseClient
{

    public function pay($data)
    {
        $this->onceName = "AlipayTradePagePayRequest";
        $this->bizContent = $data;
        return $this->pageExecute();
    }
}