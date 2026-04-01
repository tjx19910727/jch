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
        $pageNum = $postData['pageNum'] ?? 20;
        $where = $this->getWhere($postData, false, ["machine_name" => "like"]);
        return $this->app->machine->getSubMList($where,$pageNum,$this->field,"m_id desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machine->getSubMFind($where,$this->field);
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

    /**
     * 导出副柜
     * @return array|\think\response\Json
     */
    public function export()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ["machine_name" => "like"]);
        return $this->app->machine->exportSubM($where);
    }

    /**
     * 导入副柜
     * @return array|string
     */
    public function import()
    {
        $postData = input();
        if (empty($postData['file_path'])) return returnValidate("请先上传文件");
        return $this->app->machine->importSubM($postData);
    }

    public function bind()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.bind');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->bindMainMachine($postData);
    }

    /**
     * 副柜新增货道（仅边柜）
     * @return array|string
     */
    public function addChannel()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.addChannel');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->addSubMChannel($postData);
    }

    /**
     * 副柜删除货道（仅边柜）
     * @return array|string
     */
    public function delChannel()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.delChannel');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->delSubMChannel($postData);
    }

}