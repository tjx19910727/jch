<?php


namespace WeChatPayV3\Payment;


use WeChatPayV3\Kernel\ServiceContainer;
use WeChatPayV3\Payment\Transactions\TransactionsServiceProvider;
use WeChatPayV3\Payment\Transfer\TransferServiceProvider;

/**
 * Class Application
 * @property \WeChatPayV3\Payment\Transfer\TransferClient $transfer
 * @property \WeChatPayV3\Payment\Transactions\TransactionsClient $transactions
 * @package WeChatPayV3\Payment
 */
class Application extends ServiceContainer
{
    protected $providers = [
        TransferServiceProvider::class,
        TransactionsServiceProvider::class,
    ];

}