<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/9
 * Time: 11:17
 */

namespace app\management\controller\config;


use app\management\controller\Common;
use think\facade\Lang;

class ConfigScene extends Common
{
    protected $field = "id,`name`,`desc`,`status`,creator,create_time,update_time";
    protected $validatePath = 'app\management\validate\VConfigScene.';

    /**
     * 获取场景列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,["name" => "like"]);
        return $this->app->configScene->getList($where,$pageNum,$this->field,'id desc');
    }

    /**
     * 获取一条场景信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,[]);
        return $this->app->configScene->getFind($where,$this->field,'id desc');
    }

    /**
     * 添加场景信息
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'add');} catch (\Exception $e) { return returnValidate(Lang::get($e->getMessage()));}
        return $this->app->configScene->add($postData);
    }

    /**
     * 修改场景信息
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'update');} catch (\Exception $e) { return returnValidate(Lang::get($e->getMessage()));}
        return $this->app->configScene->update($postData);
    }

    /**
     * 删除场景信息
     * @return array|mixed|string
     */
    public function del()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'del');} catch (\Exception $e) { return returnValidate(Lang::get($e->getMessage()));}
        return $this->app->configScene->del($postData);
    }
}