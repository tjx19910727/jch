<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 9:17
 */

namespace app\AppFactory\Kernel\Traits\Email;


use app\AppFactory\Kernel\Model\Email\EmailTemplateModel;

trait EmailTemplateTrait
{
    public function getEmailTemplateList($where,$pageNum = 0, $field = "*", $order = "ec_id desc")
    {
        return EmailTemplateModel::getList($where,$pageNum,$field,$order);
    }

    public function getEmailTemplateFind($where,$field = '*', $order = "")
    {
        return EmailTemplateModel::getFind($where,$field,$order);
    }

    public function addEmailTemplate($insert)
    {
        $et = EmailTemplateModel::create($insert);
        return $et->et_id;
    }

    public function updateEmailTemplate($update,$where = [], $field = [])
    {
        return EmailTemplateModel::update($update,$where,$field);
    }

    public function delEmailTemplate($where)
    {
        return EmailTemplateModel::whereDel($where);
    }

}