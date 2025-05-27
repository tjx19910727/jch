<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 9:14
 */

namespace Jd\Base\Agent;


use Jd\Kernel\Container;
use Jd\Kernel\ServiceProviderInterface;

class AgentProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['agent'] = function ($app) {
            return new AgentClient($app);
        };
    }
}