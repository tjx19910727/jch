<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 9:17
 */

namespace app\AppFactory\Kernel\Traits\Email;


use app\AppFactory\Kernel\Model\Email\EmailConfigModel;

trait EmailConfigTrait
{
    public function getEmailConfigList($where,$pageNum = 0, $field = "*", $order = "ec_id desc")
    {
        return EmailConfigModel::getList($where,$pageNum,$field,$order);
    }

    public function getEmailConfigFind($where,$field = '*', $order = "")
    {
        return EmailConfigModel::getFind($where,$field,$order);
    }

    public function addEmailConfig($insert)
    {
        $ec = EmailConfigModel::create($insert);
        return $ec->ec_id;
    }

    public function updateEmailConfig($update,$where = [], $field = [])
    {
        return EmailConfigModel::update($update,$where,$field);
    }

    public function delEmailConfig($where)
    {
        return EmailConfigModel::whereDel($where);
    }
}