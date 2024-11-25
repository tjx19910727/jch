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
        if (isset($this->manager['manager_id']) && (!isset($insert['creator']) || !$insert['creator'])) $insert['creator'] = $this->manager['manager_id'];
        if (isset($this->manager['ao_id']) && (!isset($insert['ao_id']) || !$insert['ao_id'])) $insert['ao_id'] = $this->manager['ao_id'];
        $ec = EmailConfigModel::create($insert);
        $id = $ec->getPk();
        return $ec->$id;
    }

    public function updateEmailConfig($update,$where = [], $field = [])
    {
        if (isset($this->manager['manager_id']) && (!isset($update['update_id']) || !$update['update_id'])) $update['update_id'] = $this->manager['manager_id'];
        return EmailConfigModel::update($update,$where,$field);
    }

    public function delEmailConfig($where)
    {
        return EmailConfigModel::whereDel($where);
    }
}