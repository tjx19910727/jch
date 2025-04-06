<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/5/4
 * Time: 16:38
 */

namespace Jd\Kernel;


interface ServiceProviderInterface
{

    public function register(Container $app);
}