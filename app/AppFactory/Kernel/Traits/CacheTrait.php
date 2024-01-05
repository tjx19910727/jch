<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/5
 * Time: 17:29
 */

namespace app\AppFactory\Kernel\Traits;


use think\facade\Cache;

trait CacheTrait
{
    public function getCache($name)
    {
        return Cache::get($name);
    }

    public function setCache($name,$value)
    {
        return Cache::set($name,$value);
    }

    public function delCache($key)
    {
        return Cache::delete($key);
    }

    public function clearCache()
    {
        return Cache::clear();
    }
}