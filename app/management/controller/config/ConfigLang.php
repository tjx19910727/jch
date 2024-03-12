<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 16:47
 */

namespace app\management\controller\config;


use app\management\controller\Common;

class ConfigLang extends Common
{
    protected $field = "l_id,name,lang,creator,create_time,update_id,update_time";
    protected $validatePath = 'app\management\validate\VConfigLang.';

    /**
     * 获取语言列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["lang" => "like"]);
        return $this->app->configLang->getList($where,$pageNum,$this->field,'create_time desc,l_id desc');
    }

    /**
     * 获取一条语言信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->configLang->getFind($where,$this->field);
    }

    /**
     * 添加语言信息
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->configLang->add($postData);
    }

    /**
     * 修改语言信息
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->configLang->update($postData);
    }

    /**
     * 删除语言信息
     * @return array|mixed|string
     */
    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->configLang->del($postData);
    }
}