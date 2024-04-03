<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:50
 */

namespace app\management\controller\machine;


use app\AppFactory\AppFactory;
use app\management\controller\Common;

class Machine extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\VMachine.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machine->getMList($where,$pageNum,$this->field,"machine_id desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machine->getMFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->addM($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->updateM($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->delM($postData['m_id']);
    }

    /**
     * 发送主体控制命令
     * @return array|string
     */
    public function sendMainControl()
    {
        $postData = input();
        $config = [
            "machine_id" => $postData['machine_id'],
            "key" => env("api.md5Key"),
        ];
        $app = AppFactory::machine($config);
        return $app->sendMq->main_control($postData['msgType'],$postData['time_point'] ?? "");
    }

    /**
     * 设置灯光亮度
     * @return array|string
     */
    public function setLight()
    {
        $machine_id = input("machine_id");
        $light = input("light");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        if (!$light) return returnValidate(lang("VMachine.light_require"));
        if ($light%10 != 0) return returnValidate(lang("VMachine.light_multiple"));
        $config = [
            "machine_id" => $machine_id,
            "key" => env("api.md5Key"),
        ];
        $app = AppFactory::machine($config);
        return $app->sendMq->setLight($light);
    }

    /**
     * 设置音量
     * @return array|string
     */
    public function setVolume()
    {
        $machine_id = input("machine_id");
        $volume = input("volume");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        if (!$volume) return returnValidate(lang("VMachine.volume_require"));
        if ($volume%10 != 0) return returnValidate(lang("VMachine.volume_multiple"));
        $config = [
            "machine_id" => $machine_id,
            "key" => env("api.md5Key"),
        ];
        $app = AppFactory::machine($config);
        return $app->sendMq->setVolume($volume);
    }
}