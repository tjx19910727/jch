<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 10:34
 */

namespace app\management\controller\email;


use app\management\controller\Common;

class EmailTemplate extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\Email\VEmailTemplate.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["subject" => "like"]);
        return $this->app->emailTemplate->getList($where,$pageNum,$this->field,'et_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->emailTemplate->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->emailTemplate->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->emailTemplate->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->emailTemplate->del($postData);
    }
}