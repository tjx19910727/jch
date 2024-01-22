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

class ConfigSize extends Common
{
    protected $field = "s_id,label,length,width,type";
    protected $validatePath = 'app\management\validate\VConfigSize.';

    /**
     * 获取尺寸列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,["label" => "like"]);
        return $this->app->configSize->getList($where,$pageNum,$this->field,'s_id desc');
    }

    /**
     * 获取一条尺寸信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,[]);
        return $this->app->configSize->getFind($where,$this->field,'s_id desc');
    }

    /**
     * 添加尺寸信息
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'add');} catch (\Exception $e) { return returnValidate(Lang::get($e->getMessage()));}
        return $this->app->configSize->add($postData);
    }

    /**
     * 修改尺寸信息
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'update');} catch (\Exception $e) { return returnValidate(Lang::get($e->getMessage()));}
        return $this->app->configSize->update($postData);
    }

    /**
     * 删除尺寸信息
     * @return array|mixed|string
     */
    public function del()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'del');} catch (\Exception $e) { return returnValidate(Lang::get($e->getMessage()));}
        return $this->app->configSize->del($postData);
    }
}