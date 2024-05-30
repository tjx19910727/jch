<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/22
 * Time: 12:02
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerLogTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthNodeTrait;
use app\AppFactory\Management\ManagementClient;

class AuthManagerLogClient extends ManagementClient
{
    use AuthManagerLogTrait,AuthNodeTrait;


    public function getMlList($where,$pageNum = 0, $field = "*", $order = "")
    {
        $otherComment = config("auth_manager_log_list.otherComment");
        $otherPath = config("auth_manager_log_list.otherPath");

        $pathList = $this->getAuthNodeList([],0,'url,name');
        if ($pathList) $pathList = $pathList->toArray();
        $pathList = array_merge($pathList,$otherPath);
        $fieldComment = $this->getFieldComment();
        $fieldComment = array_merge($fieldComment,$otherComment);
        $data = $this->getAuthManagerLogList($where,$pageNum,$field,$order,function ($item) use ($fieldComment,$pathList) {
            if ($item['path']) {
                if ($pathList) {
                    foreach ($pathList as $pk => $pv) {
                        if ($item['path'] == $pv['url']) {
                            $item['path'] = $pv['name'];
                            break;
                        }
                    }
                }
            }
            if ($item['params']) {
                $params = json2arr($item['params']);
                if (isset($params['sign'])) unset($params['sign']);
                if (isset($params['timestamp'])) unset($params['timestamp']);
                if (isset($params['msg_id'])) unset($params['msg_id']);
                if (isset($params['password'])) unset($params['password']);
                if (isset($params['uniqid'])) unset($params['uniqid']);
                foreach ($params as $pk => $pv) {
                    foreach ($fieldComment as $key => $value) {
                        if ($pk == $value['Field']) {
                            unset($params[$pk]);
                            $params[$value['Comment']] = $pv;
                            break;
                        }
                    }
                }
                $item['params'] = $params;
            }
            return $item;
        });
        return $this->rQ($data);
    }
}