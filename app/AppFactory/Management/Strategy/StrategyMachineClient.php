<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:40
 */

namespace app\AppFactory\Management\Strategy;


use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Management\ManagementClient;

class StrategyMachineClient extends ManagementClient
{
    use StrategyMachineTrait,StrategyIncomeTrait, MachineTrait,StrategyPayeeTrait;

    public function exportStrategyMachine($where)
    {
        //导出为非分页，不走回调处理machine相关数据，新增getStrategyMachineWithList
        $list = $this->getStrategyMachineWithList($where,0,"*","sm_id desc","","",0,["machineData","strategyData"]);
        if(is_null($list) || $list->isEmpty()){
            return $this->rFail("没有数据可导出");

        }
        $payType = ["未定义策略","收款方配置策略","收费策略","分润策略","微信公众号","微信小程序","托管策略","免责协议策略"];
        $list = $list->toArray();
        foreach ($list as &$item) {
            $item['machine_id'] = $item['machineData']['machine_id'] ?? "未知";
            $item['name'] = $item['strategyData']['sp_name'] ?? "未知";
            $item['s_type_text'] = $payType[$item['s_type']] ?? "未知";
        }
        $title = [
            "name" => "策略名称",
            "machine_id" => "设备编号",
            "organization_name" => "组织名称",
            "s_type_text" => "策略类型",
        ];
        $filename = "收款绑定-" . date("YmdHis");
        return $this->sendToExport("收款绑定-报表", $filename, $title, $list);
    }
}