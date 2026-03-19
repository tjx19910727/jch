<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:50
 */

namespace app\management\controller\machine;


use app\AppFactory\AppFactory;
use app\management\controller\Common;
use app\AppFactory\Kernel\Traits\Machine\MachineErrorCodeTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;

use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\management\validate\Machine\VSubMachine;

class SubMachine extends Common
{
    use MachineErrorCodeTrait,SaleOrdersTrait,AfterOrderPaymentTrait;

    protected $field = "*";
    protected $validatePath = VSubMachine::class;

    public function getList()
    {
        $postData = input();
        $machineIds = [];
        if (!empty($postData['machine_group_id'])) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']],'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }
        $pageNum = $postData['pageNum'] ?? 20;
        $vending_machine_type = $postData['vending_machine_type'] ?? '';
        unset($postData['vending_machine_type']);
        $where = $this->getWhere($postData, false, ["machine_name" => "like"]);
    
        if (!empty($machineIds)) $where[] = ['machine_id', 'in',$machineIds];
        return $this->app->machine->getSubMList($where,$pageNum,'a.*,mc.channel_position,mc.channel_name',"a.m_id desc",$vending_machine_type);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machine->getMFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->addSubM($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->updateSubM($postData);
    }

    public function updateMore()
    {
        $postData = input();
        return $this->app->machine->updateMore($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->delSubM($postData['m_id']);
    }

    
}