<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/24
 */

namespace app\management\controller\template;


use app\management\controller\Common;
use app\management\validate\VMachineVoiceTemplate;

class VoiceTemplate extends Common
{
    protected $field = "*";
    protected $validatePath = VMachineVoiceTemplate::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineVoiceTemplate->getVoiceList($where, $pageNum, $this->field, 'id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineVoiceTemplate->getVoiceFind($where, $this->field);
    }

    public function add()
    {
        $postData = input();
        // try {
        //     $postData['status'] = $postData['status'] ?? 0;
        //     $this->validate($postData, $this->validatePath . 'add');
        // } catch (\Exception $e) {
        //     return returnValidate($e->getMessage());
        // }
        return $this->app->machineVoiceTemplate->add($postData);
    }

    public function update()
    {
        $postData = input();
        // try {
        //     $this->validate($postData, $this->validatePath . 'update');
        // } catch (\Exception $e) {
        //     return returnValidate($e->getMessage());
        // }
        return $this->app->machineVoiceTemplate->updateVoice($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVoiceTemplate->delVoice($postData);
    }

    public function assignMachine()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'assignMachine');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVoiceTemplate->assignMachine($postData);
    }

    public function setStatus()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'setStatus');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVoiceTemplate->setStatus($postData);
    }

    public function copy()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'copy');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVoiceTemplate->copyVoice($postData);
    }
}
