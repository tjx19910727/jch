<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 13:49
 */

namespace app\AppFactory\Kernel\Traits\User;


use app\AppFactory\Kernel\Model\User\UserModel;

trait UserTrait
{
    public function getUserValue($where,$value)
    {
        return UserModel::getFieldValue($where,$value);
    }
    public function getUserFind($where,$field = "*", $order = "")
    {
        return UserModel::getFind($where,$field,$order);
    }

    public function getUserList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return UserModel::getList($where,$pageNum,$field,$order);
    }

    public function addUser($insert)
    {
        $user = UserModel::create($insert);
        dump($this->getLS());
        $pk = $user->getPk();
        return $user->$pk;
    }

    public function updateUser($update,$where = [],$field = [])
    {
        return UserModel::update($update,$where,$field);
    }
}