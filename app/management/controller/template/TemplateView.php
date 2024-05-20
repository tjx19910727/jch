<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 9:42
 */

namespace app\management\controller\template;


use app\management\controller\Common;
use app\management\validate\VTemplateView;

class TemplateView extends Common
{

    protected $field = "*";
    protected $validatePath = VTemplateView::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->templateView->getList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->templateView->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        $postData = json2arr($postData);
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->templateView->checkAdd($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->templateView->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->templateView->del($postData);
    }
}