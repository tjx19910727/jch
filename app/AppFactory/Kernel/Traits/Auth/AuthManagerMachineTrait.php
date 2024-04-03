<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/14
 * Time: 17:15
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthManagerMachineModel;

trait AuthManagerMachineTrait
{

    public function getAuthManagerMachineColumn($where,$column)
    {
        return AuthManagerMachineModel::getColumn($where,$column);
    }

    /**
     * 获取指定字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getAuthManagerMachineValue($where,$value)
    {
        return AuthManagerMachineModel::getFieldValue($where,$value);
    }

    /**
     * 获取一条关联数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return AuthManagerMachineModel|array|mixed|null|\think\Model
     */
    public function getAuthManagerMachineFind($where,$field = '*', $order = 'manager_id desc')
    {
        return AuthManagerMachineModel::getFind($where,$field,$order);
    }

    /**
     * 获取账号关联设备列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return AuthManagerMachineModel|AuthManagerMachineModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getAuthManagerMachineList($where,$pageNum = 0, $field = '*', $order = 'manager_id desc')
    {
        $list = AuthManagerMachineModel::getList($where,$pageNum,$field,$order);
        if ($list) {
            foreach ($list as $key => $value) {
                $address =  "";
                $item = $this->getMachineFind(['m_id' => $value['m_id']],'country_id,state_id,city_id,regions_id,street');
                if (isset($item['country_id']) && $item['country_id']) $address .= $this->getEarthCountriesFind(['id' => $item['country_id']],'code,name,cname');
                if (isset($item['state_id']) && $item['state_id']) $address .= $this->getEarthStatesFind(['id' => $item['state_id']],'code,name,cname');
                if (isset($item['city_id']) && $item['city_id']) $address .= $this->getEarthCitiesFind(['id' => $item['city_id']],'code,name,cname');
                if (isset($item['regions_id']) && $item['regions_id']) $address .= $this->getEarthRegionsFind(['id' => $item['regions_id']],'code,name,cname');
                if (isset($item['street']) && $item['street'])  $address .= $item['street'];
                $list[$key]['address'] = $address;
            }
        }
        return $list;
    }

    /**
     * 添加账号关联设备信息
     * @param $insert
     * @return mixed
     */
    public function addAuthManagerMachine($insert)
    {
        $aum = AuthManagerMachineModel::create($insert);
        return $aum->id;
    }

    /**
     * 批量添加账号关联设备信息
     * @param $insert
     * @return \think\Collection
     * @throws \Exception
     */
    public function addAuthManagerMachineMore($insert)
    {
        $aum = new AuthManagerMachineModel();
        return $aum->saveAll($insert);
    }

    /**
     * 删除关联关系
     * @param $where
     * @return bool
     */
    public function delAuthManagerMachine($where)
    {
        return AuthManagerMachineModel::whereDel($where);
    }

}