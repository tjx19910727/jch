<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 9:17
 */

namespace app\AppFactory\Kernel\Traits\Email;


use app\AppFactory\Kernel\Model\Email\EmailTemplateLogModel;

trait EmailTemplateLogTrait
{
    public function getEmailTemplateLogList($where,$pageNum = 0, $field = "*", $order = "ec_id desc")
    {
        return EmailTemplateLogModel::getList($where,$pageNum,$field,$order);
    }

    public function getEmailTemplateLogFind($where,$field = '*', $order = "")
    {
        return EmailTemplateLogModel::getFind($where,$field,$order);
    }

    public function addEmailTemplateLog($insert)
    {
        $etl = EmailTemplateLogModel::create($insert);
        actionLog($this->getLS(),'生成发送日志记录SQL');
        return $etl->etl_id;
    }

    public function updateEmailTemplateLog($update,$where = [], $field = [])
    {
        return EmailTemplateLogModel::update($update,$where,$field);
    }

    public function delEmailTemplateLog($where)
    {
        return EmailTemplateLogModel::whereDel($where);
    }
}