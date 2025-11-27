<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/22
 * Time: 9:43
 */

namespace app\wx\controller;



use app\AppFactory\AppFactory;
use app\BaseController;
use app\AppFactory\Kernel\Traits\ReturnTrait;

class Official extends BaseController
{
    use ReturnTrait;

    // 接收微信公众号通知
    public function receive()
    {
        $data = input();
        if ($data)
            actionLog($data, '接收到的数据');
        if (isset($data['echostr'])) {
            die($data['echostr']);
        }
        $xml = file_get_contents("php://input");
        actionLog($xml, "xml");
        $message = FromXml($xml);
        $message = json_decode(json_encode($message), true);
        actionLog($message,'XML转格式');
        AppFactory::wx()->official->receiveHandle($message);
    }

    // 公众号菜单栏获取
    public function getMenu(){
        $data = input(); 
        if (empty($data['gh_id'])) {
            return $this->rFail('未传入公众号原始ID');
        }
        AppFactory::wx()->official->menuList($data);
    }

    // 公众号菜单栏修改
    public function editMenu(){
        $data = input();
        if ($data)
            actionLog($data, '修改菜单数据');
        if (empty($data['gh_id'])) return $this->rFail('未传入原始id');
        if (empty($data['menu']))  return $this->rFail('未正确传入菜单数据');
        AppFactory::wx()->official->editMenu($data);
    }
}