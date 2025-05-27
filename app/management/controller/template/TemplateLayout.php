<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 9:40
 */

namespace app\management\controller\template;


use app\management\controller\Common;

class TemplateLayout extends Common
{

    protected $field = "id,name,template_id,height,width,left,top,plugin_id,update_time";
    protected $validatePath = 'app\management\validate\VTemplateLayout.';

    public function getList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'query');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->templateLayout->getList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->templateLayout->getFind($where);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->templateLayout->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->templateLayout->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->templateLayout->del($postData);
    }
}