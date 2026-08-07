<?php

namespace app\AppFactory\Kernel\Traits\SaleOrders;

use app\AppFactory\Kernel\Model\SaleOrders\OrderTypeModel;

trait OrderTypeTrait
{
    protected static $orderTypeNameMapCache = [];

    public function getOrderTypeFind($where, $field = "*", $order = "ot_id desc")
    {
        return OrderTypeModel::getFind($where, $field, $order);
    }

    public function getOrderTypeList($where = [], $pageNum = 0, $field = "*", $order = "sort asc, order_type asc")
    {
        return OrderTypeModel::getList($where, $pageNum, $field, $order);
    }

    public function addOrderType($insert)
    {
        if (isset($this->manager['manager_id']) && !isset($insert['creator'])) {
            $insert['creator'] = $this->manager['manager_id'];
        }
        $data = OrderTypeModel::create($insert);
        self::$orderTypeNameMapCache = [];
        return $data->ot_id;
    }

    public function updateOrderType($update, $where = [], $field = [])
    {
        $result = OrderTypeModel::update($update, $where, $field);
        self::$orderTypeNameMapCache = [];
        return $result;
    }

    public function delOrderType($where)
    {
        $result = OrderTypeModel::destroy($where);
        self::$orderTypeNameMapCache = [];
        return $result;
    }

    protected function getOrderTypeNameMapFromTable($onlyEnabled = false)
    {
        $cacheKey = $onlyEnabled ? 'enabled' : 'all';
        if (isset(self::$orderTypeNameMapCache[$cacheKey])) {
            return self::$orderTypeNameMapCache[$cacheKey];
        }

        try {
            $where = $onlyEnabled ? ['status' => 1] : [];
            $list = OrderTypeModel::getList($where, 0, "order_type,order_type_name", "sort asc, order_type asc");
        } catch (\Throwable $e) {
            self::$orderTypeNameMapCache[$cacheKey] = [];
            return [];
        }

        $map = [];
        foreach ($list as $item) {
            $orderType = intval($item['order_type']);
            $name = trim((string)($item['order_type_name'] ?? ''));
            if ($name !== '') {
                $map[$orderType] = $name;
            }
        }
        self::$orderTypeNameMapCache[$cacheKey] = $map;
        return $map;
    }
}
