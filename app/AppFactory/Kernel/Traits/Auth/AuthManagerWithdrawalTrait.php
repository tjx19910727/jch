<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/7
 * Time: 19:48
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthManagerWithdrawalModel;

trait AuthManagerWithdrawalTrait
{
    /**
     * 获取提现记录列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return AuthManagerWithdrawalModel|AuthManagerWithdrawalModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getAuthManagerWithdrawalList($where,$pageNum = 0, $field = "*", $order = "wd_id desc")
    {
        return AuthManagerWithdrawalModel::getList($where,$pageNum,$field,$order);
    }

    /**
     * 获取一条提现记录
     * @param $where
     * @param string $field
     * @param string $order
     * @return AuthManagerWithdrawalModel|array|mixed|null|\think\Model
     */
    public function getAuthManagerWithdrawalFind($where,$field = "*", $order = "wd_id desc")
    {
        return AuthManagerWithdrawalModel::getFind($where,$field,$order);
    }

    /**
     * 添加提现记录
     * @param $insert
     * @return mixed
     */
    public function addAuthManagerWithdrawal($insert)
    {
        if (isset($this->manager['manager_id'])) $insert['manager_id'] = $this->manager['manager_id'];
        $wd = AuthManagerWithdrawalModel::create($insert);
        return $wd->wd_id;
    }

    /**
     * 修改提现记录
     * @param $update
     * @param array $where
     * @param array $field
     * @return AuthManagerWithdrawalModel
     */
    public function updateAuthManagerWithdrawal($update, $where = [], $field = [])
    {
        if (isset($this->manager['manager_id'])) $update['examiner'] = $this->manager['manager_id'];
        return AuthManagerWithdrawalModel::update($update,$where,$field);
    }

    /**
     * 删除提现记录
     * @param $where
     * @return bool
     */
    public function delAuthManagerWithdrawal($where)
    {
        return AuthManagerWithdrawalModel::whereDel($where);
    }
}