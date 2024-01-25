<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 9:42
 */

namespace app\AppFactory\Machine\Receive;



use app\AppFactory\Kernel\Support\Validate\Machine\VReport;

class SocketClient extends ReceiveBaseClient
{

    protected $type = [
        "login",
        "subCar",
        "",
    ];

    public function onMessage($postData)
    {
        if ($this->checkSign($postData) !== true)
            return $this->rValidate($this->lang("check_sign_fail"));
        $this->client_id = $postData['client_id'];
        $this->message = $postData['message'];
        try {
            validate(VReport::class)->scene('onMessage')->check($this->message);
        } catch (\Exception $e) {
            return $this->rValidate($e->getMessage());
        }



    }
}