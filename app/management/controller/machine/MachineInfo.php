<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 16:25
 */

namespace app\management\controller\machine;


use app\AppFactory\AppFactory;
use app\management\controller\Common;

class MachineInfo extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\VMachineInfo.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineInfo->getList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineInfo->getFind($where);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineInfo->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineInfo->update($postData);
    }

    public function updateMoreMi()
    {
        $postData = input();
        return $this->app->machineInfo->updateMore($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineInfo->del($postData);
    }

    /**
     * 获取设备实时图片
     * @return array|string
     */
    public function getImg()
    {
        $field = input('field');
        $machine_id = input('machine_id');
        if (!in_array($field,["screen_img","camera_img","exchange_img"])) return returnState(100,lang("query_out_range"));
        if (!$machine_id) return returnState(100,lang("VMachineInfo.machine_id_require"));
        $send = "";
        $n = 0;
        while(1) {
            $shotImg = $this->app->machineInfo->getMachineInfoValue(['machine_id' => $machine_id],$field);
            if ($shotImg) {
                $this->app->machineInfo->updateMachineInfo([$field => ""],['machine_id' => $machine_id]);
                return returnState(200,lang("query_success"),$shotImg);
            }
            if (!$send) {
                $config = [
                    "machine_id" => $machine_id,
                    "key" => env("api.md5Key"),
                ];
                $app = AppFactory::machine($config);
                $send = $app->sendMq->getImg($field);
            }
            sleep(1);
            $n++;
            if ($n >= 120) {
                return returnState(100,lang("action_machine_overtime"));
            }
        }
    }

}