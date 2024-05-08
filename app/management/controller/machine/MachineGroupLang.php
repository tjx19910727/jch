<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 9:11
 */

namespace app\management\controller\Machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineGroupLang;

class MachineGroupLang extends Common
{

    protected $field = "mgl_id,mg_id,mg_name,desc,lang";
    protected $validatePath = VMachineGroupLang::class;

    /**
     * 获取设备分组多语言列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["mg_name" => "like"]);
        return $this->app->machineGroupLang->getList($where,$pageNum,$this->field,'mgl_id desc');
    }

    /**
     * 获取设备分组信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineGroupLang->getFind($where,$this->field,'mgl_id desc');
    }

    /**
     * 添加设备分组附加多语言信息
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineGroupLang->add($postData);
    }

    /**
     * 修改设备分组附加多语言信息
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineGroupLang->update($postData);
    }

    /**
     * 删除设备分组多语言信息
     * @return array|mixed|string
     */
    public function del()
    {
        $postData = input();
        if (!isset($postData['mgl_id']) && !isset($postData['mg_id'])) return returnValidate("设备分组ID与多语言信息ID不能同时为空");
        return $this->app->machineGroupLang->del($postData);
    }
}