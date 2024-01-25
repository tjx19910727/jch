<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 9:13
 */

namespace Jd\Base;


use Jd\Base\Agent\AgentClient;
use Jd\Base\Agent\AgentProvider;
use Jd\Kernel\ServiceContainer;

/**
 * Class Application
 * @property AgentClient $agent 代理操作
 * @package Jd\Base
 */
class Application extends ServiceContainer
{
    protected $providers = [
        AgentProvider::class,
    ];
}