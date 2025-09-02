<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:33
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Pick\ActivityPickCodeModel;

trait ActivityPickCodeTrait
{

    public function getActivityPickCodeCount($where)
    {
        return ActivityPickCodeModel::getCount($where);
    }

    public function getActivityPickCodeValue($where,$value)
    {
        return ActivityPickCodeModel::getFieldValue($where,$value);
    }

    public function getActivityPickCodeColumn($where,$column)
    {
        return ActivityPickCodeModel::getColumn($where,$column);
    }

    public function getActivityPickCodeFind($where,$field = "*",$order = "apc_id desc")
    {
        return ActivityPickCodeModel::getFind($where,$field,$order);
    }

    public function getActivityPickCodeList($where,$pageNum = 0,$field = "*", $order = "apc_id desc")
    {
        return ActivityPickCodeModel::getList($where,$pageNum,$field,$order);
    }

    public function addActivityPickCodeMore($insertAll)
    {
        $apc = new ActivityPickCodeModel();
        return $apc->saveAll($insertAll);
    }

    public function addActivityPickCode($insert)
    {
        $apc = ActivityPickCodeModel::create($insert);
        $id = $apc->getPk();
        return $apc->$id;
    }

    public function updateActivityPickCode($update,$where = [],$field = [])
    {
        return ActivityPickCodeModel::update($update,$where,$field);
    }

    public function delActivityPickCode($where)
    {
        return ActivityPickCodeModel::whereDel($where);
    }

    public function checkActivityPickCode($ap_id,$code){
        $sel = ActivityPickCodeModel::getList(['ap_id'=>$ap_id],null,'code');
        $sel = $sel->toArray();
        return in_array(['code'=>$code],$sel);
    }

    /**
     * 生成取货码记录，对外API预订商品提货码
     * @return int|\think\response\Json
     */
    protected function createApc()
    {
        $apc = $this->getActivityPickCodeFind(['code' => $this->params['pick_code'],'pick_type' => 3]);
        if ($apc) return $this->returnData(7,$this->lang("msg." . 7) . "：" . $this->lang("reserve_order.apc_already_exist"));
        $insert = [
            "code" => $this->params['pick_code'],
            "order_id" => $this->order['order_id'],
            "trade_no" => $this->order['trade_no'],
            "m_id" => $this->order['m_id'],
            "machine_id" => $this->order['machine_id'],
            "machine_name" => $this->order['machine_name'],
            "pick_type" => 3,
            "ao_id" => $this->order['ao_id'] ,
            "status" => 1,
        ];
        $apc_id = $this->addActivityPickCode($insert);
        if (!$apc_id) {
            return $this->returnData(18,$this->lang("msg." . 18) . "：" . $this->lang("reserve_order.apc_id_add_fail"));
        }
        $this->order['apc_id'] = $apc_id;
        return 1;
    }
}