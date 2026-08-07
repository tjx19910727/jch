<?php

namespace app\AppFactory\Kernel\Traits\Payment;

use app\AppFactory\Kernel\Model\Payment\PayTypeModel;

trait PayTypeTrait
{
    protected static $payTypeNameMapCache = [];

    public function getPayTypeFind($where, $field = "*", $order = "pt_id desc")
    {
        return PayTypeModel::getFind($where, $field, $order);
    }

    public function getPayTypeList($where = [], $pageNum = 0, $field = "*", $order = "sort asc, pay_type asc")
    {
        return PayTypeModel::getList($where, $pageNum, $field, $order);
    }

    public function addPayType($insert)
    {
        if (isset($this->manager['manager_id']) && !isset($insert['creator'])) {
            $insert['creator'] = $this->manager['manager_id'];
        }
        $data = PayTypeModel::create($insert);
        self::$payTypeNameMapCache = [];
        return $data->pt_id;
    }

    public function updatePayType($update, $where = [], $field = [])
    {
        $result = PayTypeModel::update($update, $where, $field);
        self::$payTypeNameMapCache = [];
        return $result;
    }

    public function delPayType($where)
    {
        $result = PayTypeModel::destroy($where);
        self::$payTypeNameMapCache = [];
        return $result;
    }

    protected function getPayTypeNameMapFromTable($onlyEnabled = false)
    {
        $cacheKey = $onlyEnabled ? 'enabled' : 'all';
        if (isset(self::$payTypeNameMapCache[$cacheKey])) {
            return self::$payTypeNameMapCache[$cacheKey];
        }

        try {
            $where = $onlyEnabled ? ['status' => 1] : [];
            $list = PayTypeModel::getList($where, 0, "pay_type,pay_type_name", "sort asc, pay_type asc");
        } catch (\Throwable $e) {
            self::$payTypeNameMapCache[$cacheKey] = [];
            return [];
        } catch (\Exception $e) {
            self::$payTypeNameMapCache[$cacheKey] = [];
            return [];
        }

        $map = [];
        foreach ($list as $item) {
            $payType = intval($item['pay_type']);
            $name = trim((string)($item['pay_type_name'] ?? ''));
            if ($name !== '') {
                $map[$payType] = $name;
            }
        }
        self::$payTypeNameMapCache[$cacheKey] = $map;
        return $map;
    }
}
