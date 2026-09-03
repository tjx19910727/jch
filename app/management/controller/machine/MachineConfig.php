<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:58
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineConfig;

class MachineConfig extends Common
{

    protected $field = "*";
    protected $validatePath = VMachineConfig::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $filterCurrency = '';
        if (isset($postData['currency_code']) && trim((string)$postData['currency_code']) !== '') {
            $filterCurrency = strtoupper(trim((string)$postData['currency_code']));
            unset($postData['currency_code']);
        }
        $where = $this->getWhere($postData, false, []);
        if ($filterCurrency && !preg_match('/^[A-Z]{3}$/', $filterCurrency)) $filterCurrency = '';
        if ($filterCurrency) {
            $where[] = ['currency_code', '=', $filterCurrency];
        }
        return $this->app->machineConfig->getList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineConfig->getFind($where);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfig->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfig->updateMcV2($postData);
    }

    public function updateMoreMc()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.updateMoreMc');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfig->updateMoreMc($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfig->del($postData);
    }

    public function currencyReadiness()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.currencySwitch');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfig->currencyReadiness($postData);
    }

    public function switchCurrency()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.currencySwitch');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfig->switchCurrency($postData);
    }

    public function switchCurrencyBatch()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.currencySwitchBatch');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfig->switchCurrencyBatch($postData);
    }
}
