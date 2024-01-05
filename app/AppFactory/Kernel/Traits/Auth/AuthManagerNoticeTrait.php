<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/8/2
 * Time: 19:22
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthManagerNoticeModel;

trait AuthManagerNoticeTrait
{
    public function getAuthManagerNoticeColumn($where,$column)
    {
        return AuthManagerNoticeModel::getColumn($where,$column);
    }

    /**
     * 获取一条通知数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return AuthManagerNoticeModel|array|mixed|null|\think\Model
     */
    public function getAuthManagerNoticeFind($where,$field = '*', $order = 'manager_id desc')
    {
        return AuthManagerNoticeModel::getFind($where,$field,$order);
    }

    /**
     * 获取账号通知开关列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return AuthManagerNoticeModel|AuthManagerNoticeModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getAuthManagerNoticeList($where,$pageNum = 0, $field = '*', $order = 'manager_id desc')
    {
        return AuthManagerNoticeModel::getList($where,$pageNum,$field,$order)->each(function ($item) {
            $item['store_name'] = $this->getStoreValue(['store_id' => $item['store_id']],'store_name');
            return $item;
        });
    }

    /**
     * 添加账号通知信息
     * @param $insert
     * @return mixed
     */
    public function addAuthManagerNotice($insert)
    {
        $amn = AuthManagerNoticeModel::create($insert);
        return $amn->amn_id;
    }

    /**
     * 修改账号通知信息
     * @param $update
     * @param array $where
     * @param array $field
     * @return AuthManagerNoticeModel
     */
    public function updateAuthManagerNotice($update,$where = [],$field = [])
    {
        return AuthManagerNoticeModel::update($update,$where,$field);
    }
}