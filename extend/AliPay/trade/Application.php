<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 14:39
 */

namespace AliPay\trade;


use AliPay\Kernel\ServiceContainer;
use AliPay\trade\order\OrderProvider;
use AliPay\trade\page\PageProvider;
use AliPay\trade\royalty\RelationProvider;
use AliPay\trade\wap\WapProvider;

/**
 * Class Application
 * @property \AliPay\trade\order\OrderClient   $order   订单
 * @property \AliPay\trade\royalty\RelationClient  $relation   分账关系
 * @property \AliPay\trade\page\PageClient  $page   PC场景下单
 * @property \AliPay\trade\wap\WapClient $wap   手机端下单
 * @property \AliPay\trade\TradeClient $trade  交易
 * @package AliPay\trade
 */
class Application extends ServiceContainer
{
    protected $providers = [
        OrderProvider::class,
        RelationProvider::class,
        PageProvider::class,
        WapProvider::class,
        TradeProvider::class,
    ];
}