<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:38
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineInfoModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersDetailsModel;

trait MachineInfoTrait
{
    use MachineAuxiliaryTrait;
    use MachineChannelTrait;
    /**
     * 获取设备信息字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineInfoValue($where,$value)
    {
        return MachineInfoModel::getFieldValue($where,$value);
    }

    /**
     * 获取一条设备信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return MachineInfoModel|array|mixed|null|\think\Model
     */
    public function getMachineInfoFind($where,$field = "*",$order = "")
    {
        // return MachineInfoModel::getFind($where,$field,$order);
        $info = MachineInfoModel::getFind($where,$field,$order);
        if($info){
            $info = $info->toArray();
            //$count = $this->getMachineMainRelationCount(['main_mc_id' => $info['m_id']],'*');
            //直接查询边柜货道数量，数量大于0则有边柜
            $count = $this->getMachineChannelCount(['m_id' => $info['m_id'],'channel_position' => 3]);
            $info['sub_cabinet_2'] = $count > 0 ? 1 : 2;
            //查询此设备挂接的副柜
            $subCabinet = $this->getMachineAuxiliaryList(['main_m_id' => $info['m_id']]);
            $info['sub_cabinet_list'] = $subCabinet ? $subCabinet->toArray() : [];
        }
        return $info;
    }

    /**
     * 获取设备信息列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFun
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMachineInfoList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineInfoModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    /**
     * 添加设备信息
     * @param $insert
     * @return mixed
     */
    public function addMachineInfo($insert)
    {
        $data = MachineInfoModel::create($insert);
        return $data->mi_id;
    }

    /**
     * 修改设备信息
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineInfoModel
     */
    public function updateMachineInfo($update,$where = [],$field = [])
    {
        return MachineInfoModel::update($update,$where,$field);
    }

    /**
     * 删除设备信息
     * @param $where
     * @return bool
     */
    public function delMachineInfo($where)
    {
        $result = MachineInfoModel::whereDel($where);
        return $result;
    }

    /**
     * 设备上报
     * @return MachineInfoModel
     */
    public function img()
    {
        if (isset($this->message['sod_id'])) {
            actionLog($this->message, "远程退货照片保存地址记录执行");
            $result = SaleOrdersDetailsModel::update(['refund_photo' => $this->message['path']], ['sod_id' => $this->message['sod_id']]);
            return $result;
        }
        $result = $this->updateMachineInfo([$this->message['field'] => $this->message['path']],['machine_id' => $this->machine['machine_id']]);
        actionLog($this->getLS(),'【SQL】写入图片路径');
        return $result;
    }

    /**
     * 终端上报machine_info的数据
     * @return MachineInfoModel|bool
     */
    public function uploadInfo()
    {
        try {
            $fieldList = $this->getFieldComment("machine_info");
            $fields = array_column($fieldList, 'Field');
            actionLog($fieldList, '字段及备注', "uploadInfo");
            actionLog($fields, '字段名', "uploadInfo");
            $update = [];
            $messageKey = array_keys($this->message);
            actionLog($messageKey, '接收数据参数名', "uploadInfo");
            foreach ($messageKey as $value) {
                if (in_array($value, $fields)) {
                    $update[$value] = $this->message[$value];
                }
            }
            actionLog($update, '修改数据', "uploadInfo");
            if ($update) {
                // 20250613 有副柜状态，并且副柜不可用，检查副柜货道库存，并将库存退回到设备商品库，如果设备商品库没有相关联的商品，只生成新的设备商品库信息
                if (isset($update['sub_cabinet']) && $update['sub_cabinet'] == 2) {
                    $this->subCabinetReturnInventory();
                }
                $result = $this->updateMachineInfo($update, ['m_id' => $this->machine['m_id']]);
                actionLog($this->getLS(), '【SQL】修改设备信息', "uploadInfo");
                actionLog($result, '修改设备信息结果', "uploadInfo");
                return $result;
            }
            return false;
        } catch (\Exception $e) {
            actionException($e,1);
            return false;
        }
    }

    /**
     * 20250613
     * 副柜退回设备商品库备用库存
     * 1. 查询库存大于0，有绑定设备商品库商品，副柜货道
     * 2. 循环货道列表，修改货道库存为0，增加对应设备商品库库存值。
     */
    public function subCabinetReturnInventory()
    {
        $whereSubMc['m_id'] = $this->machine['m_id'];
        $whereSubMc['channel_position'] = 2;
        $whereSubMc[] = ['stock','>',0];
        $whereSubMc[] = ['mg_id','>',0];
        $mc = $this->getMachineChannelList($whereSubMc,0,'mc_id,mg_id,g_id,stock');
        actionLog($this->getLS(),'【SQL】查询副柜货道');
        actionLog($mc,'查询到的副柜货道数据');
        if ($mc) {
            $flag = [];
            foreach ($mc as $key => $value) {
                $updateMc['stock'] = 0;
                $updateMc['mc_id'] = $value['mc_id'];
                $updateMc['status'] = 2;
                $flag[] = $this->updateMachineChannel($updateMc);
                actionLog($this->getLS(),'退副柜货道库存');
                $flag[] = $this->setMachineGoodsInc(['mg_id' => $value['mg_id']],'standby_stock',$value['stock']);
                actionLog($this->getLS(),'增加设备商品库库存');
            }
            actionLog($flag,'副柜退库存结果集');
        }
    }
}