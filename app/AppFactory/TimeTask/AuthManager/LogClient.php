<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/31
 * Time: 11:30
 */

namespace app\AppFactory\TimeTask\AuthManager;


use app\AppFactory\TimeTask\TimeTaskBase;

class LogClient extends TimeTaskBase
{
    public function clearLog()
    {
        $where[] = ['create_time',];
    }
}