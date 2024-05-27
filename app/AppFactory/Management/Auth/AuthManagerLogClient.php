<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/22
 * Time: 12:02
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerLogTrait;
use app\AppFactory\Management\ManagementClient;

class AuthManagerLogClient extends ManagementClient
{
    use AuthManagerLogTrait;

    public function getMlList($where,$pageNum = 0, $field = "*", $order = "")
    {
        $data = $this->getAuthManagerLogList($where,$pageNum,$field,$order,function ($item) {
            $params = json2arr($item['params']);
            if (isset($params['sign'])) unset($params['sign']);
            if (isset($params['timestamp'])) unset($params['timestamp']);
            if (isset($params['msg_id'])) unset($params['msg_id']);
            if (isset($params['password'])) unset($params['password']);
            return $item;
        });
        return $this->rQ($data);
    }
}