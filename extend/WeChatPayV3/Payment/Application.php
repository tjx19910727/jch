<?php


namespace WeChatPayV3\Payment;


use WeChatPayV3\Kernel\ServiceContainer;
use WeChatPayV3\Payment\Refund\RefundServiceProvider;
use WeChatPayV3\Payment\Transactions\TransactionsServiceProvider;
use WeChatPayV3\Payment\Transfer\TransferServiceProvider;

/**
 * Class Application
 * @property \WeChatPayV3\Payment\Transfer\TransferClient $transfer   转账
 * @property \WeChatPayV3\Payment\Transactions\TransactionsClient $transactions   支付交易
 * @property \WeChatPayV3\Payment\Refund\RefundClient   $refund   退款
 * @package WeChatPayV3\Payment
 */
class Application extends ServiceContainer
{
    protected $providers = [
        TransferServiceProvider::class,
        TransactionsServiceProvider::class,
        RefundServiceProvider::class,
    ];

}