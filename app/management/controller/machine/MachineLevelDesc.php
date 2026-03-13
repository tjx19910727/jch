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
use app\management\validate\Machine\VMachineLevelDesc;

class MachineLevelDesc extends Common
{
    use MachineErrorCodeTrait,SaleOrdersTrait,AfterOrderPaymentTrait;

    protected $field = "*";
    protected $validatePath = VMachineLevelDesc::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["name" => "like"]);
        return $this->app->machine->getLevelList($where,$pageNum,$this->field,"created_at desc,machine_level desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machine->getLevelFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->addMLevel($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->updateMLevel($postData);
    }
    // public function del()
    // {
    //     $postData = input();
    //     try {
    //         $this->validate($postData, $this->validatePath . '.del');
    //     } catch (\Exception $e) {
    //         return returnValidate($e->getMessage());
    //     }
    //     return $this->app->machine->delM($postData['m_id']);
    // }

}