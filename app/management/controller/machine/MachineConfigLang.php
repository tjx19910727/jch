<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 15:17
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineConfigLang;

class MachineConfigLang extends Common
{
    
    protected $field = "*";
    protected $validatePath = VMachineConfigLang::class . ".";

    /**
     * 获取设备配置多语言表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'getList');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineConfigLang->getList($where, $pageNum, $this->field);
    }

    /**
     * 获取一条设备配置多语言数据
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineConfigLang->getFind($where, $this->field);
    }

    /**
     * 添加设备配置多语言数据
     * @return array|mixed|\think\response\Json
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfigLang->addMcl($postData);
    }

    /**
     * 修改设备配置多语言数据
     * @return array|mixed|\think\response\Json
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfigLang->update($postData);
    }

    /**
     * 批量修改设备配置多语言数据
     * @return array|\think\response\Json
     */
    public function updateMoreMcl()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.updateMoreMcl');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfigLang->updateMoreMcl($postData);
    }

    /**
     * 删除设备配置多语言
     * @return array|mixed|\think\response\Json
     */
    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfigLang->del($postData);
    }
}