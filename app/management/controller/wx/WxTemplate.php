<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 9:27
 */

namespace app\management\controller\wx;


use app\management\controller\Common;

class WxTemplate extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\Wx\VWxTemplate.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["template_name" => "like"]);
        return $this->app->wxTemplate->getList($where,$pageNum,$this->field,'update_time desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->wxTemplate->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        $postData = json2arr($postData);
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        if (isset($postData['miniprogram'])) $postData['miniprogram'] = json_encode($postData['miniprogram'],320);
        $postData['body'] = json_encode($postData['body'],320);
        return $this->app->wxTemplate->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->wxTemplate->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->wxTemplate->del($postData);
    }
}