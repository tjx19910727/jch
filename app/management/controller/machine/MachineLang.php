<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 19:57
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineLang;

class MachineLang extends Common
{
    
    protected $field = "ml_id,m_id,machine_id,currency,logo,`desc`,lang";
    protected $validatePath = VMachineLang::class . ".";

    /**
     * 获取列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        if (!isset($postData['m_id'])) return lang("VMachineLang.m_id_require");
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineLang->getList($where, $pageNum, $this->field);
    }

    /**
     * 查询一条信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineLang->getFind($where, $this->field);
    }

    /**
     * 添加多语言
     * @return array|\think\response\Json
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineLang->addMl($postData);
    }

    /**
     * 修改多语言
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
        return $this->app->machineLang->update($postData);
    }

    /**
     * 删除多语言
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
        return $this->app->machineLang->del($postData);
    }
}