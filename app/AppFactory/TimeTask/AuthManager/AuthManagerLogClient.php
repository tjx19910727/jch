<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/31
 * Time: 11:30
 */

namespace app\AppFactory\TimeTask\AuthManager;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerLogTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class AuthManagerLogClient extends TimeTaskBase
{
    use AuthManagerLogTrait;

    /**
     * 删除180天前的用户事件日志数据
     */
    public function clearLog()
    {
        $where[] = ['create_time','<',time() - 86400 * 180];
        $result = $this->delAuthManagerLog($where);
        actionLog($result,'删除半年前的用户事件');
        return "处理完成";
    }
}