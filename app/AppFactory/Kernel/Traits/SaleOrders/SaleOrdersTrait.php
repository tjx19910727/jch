<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 10:21
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;


use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersDetailsModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleHotelModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleHotelNightlyModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersUnclaimedModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Machine\MachineLevelDescModel;
use app\AppFactory\Kernel\Support\Validate\Api\VV2;
use app\AppFactory\Kernel\Model\Machine\MachineErrorCodeModel;
use app\AppFactory\Kernel\Model\Auth\AuthOrgMachineChannelModel;
use think\facade\Db;

trait SaleOrdersTrait
{
    public function getPayTypeNameMap()
    {
        return config('payment.pay_type_map') ?: [];
    }

    public function getPayMethodNameMap()
    {
        return config('payment.pay_method_map') ?: [];
    }

    public function getStrategyPayeeTypeNameMap()
    {
        return config('payment.strategy_payee_type_map') ?: [];
    }

    public function getPayChannelNameMap()
    {
        return config('payment.pay_channel_map') ?: [];
    }

    public function formatPayType($payType, $defaultPrefix = '支付类型#')
    {
        $payType = intval($payType);
        $map = $this->getPayTypeNameMap();
        return $map[$payType] ?? ($defaultPrefix . $payType);
    }

    public function formatPayMethod($payMethod, $defaultPrefix = '支付方式#')
    {
        $payMethod = intval($payMethod);
        $map = $this->getPayMethodNameMap();
        return $map[$payMethod] ?? ($defaultPrefix . $payMethod);
    }

    public function getPayTypeOptions($values = [])
    {
        $map = $this->getPayTypeNameMap();
        if ($values) {
            $map = array_intersect_key($map, array_flip(array_map('intval', $values)));
        }

        $data = [];
        foreach ($map as $value => $label) {
            $data[] = [
                'value' => intval($value),
                'label' => $label,
            ];
        }
        return $data;
    }

    public function getPayMethodOptions($values = [])
    {
        $map = $this->getPayMethodNameMap();
        if ($values) {
            $map = array_intersect_key($map, array_flip(array_map('intval', $values)));
        }

        $data = [];
        foreach ($map as $value => $label) {
            $data[] = [
                'value' => intval($value),
                'label' => $label,
            ];
        }
        return $data;
    }

    public function getSaleOrdersValue($where, $value)
    {
        return SaleOrdersModel::getFieldValue($where, $value);
    }

    /**
     * 订单主表字段求和
     * @param $where
     * @param $sum
     * @return float
     */
    public function getSaleOrdersSum($where, $sum)
    {
        return SaleOrdersModel::getSum($where, $sum);
    }

    /**
     * 获取一条订单数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return SaleOrdersModel|array|mixed|null|\think\Model
     */
    public function getSaleOrdersFind($where, $field = "*", $order = "", $group = "")
    {
        return SaleOrdersModel::getFind($where, $field, $order, $group);
    }

    /**
     * 定时统计商品日销售数据
     * @param $where
     * @param string $field
     * @param string $group
     * @return mixed
     */
    public function getSaleOrdersDetailsData($where, $field = "*", $group = "")
    {
        return SaleOrdersModel::collectDetailsData($where, $field, $group);
    }

    /**
     * 查询销售订单列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFn
     * @param string $group
     * @return SaleOrdersModel|SaleOrdersModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getSaleOrdersList($where, $pageNum = 0, $field = "*", $order = "", $eachFn = '', $group = '', $limit = 0, $with = [])
    {
        $data = SaleOrdersModel::getListAndWith($where, $pageNum, $field, $order, $eachFn, $group, $limit, $with);
        actionLog($this->getLS(), '【SQL】订单列表主查询', 'sale_orders');
        if ($pageNum) {
            $data = $this->appendSaleOrderListRelations($data);
        }
        return $data;
    }

    /**
     * 批量装配订单列表关联数据，避免分页列表逐订单查询。
     * @param \think\Paginator|\think\Collection $data
     * @return \think\Paginator|\think\Collection
     */
    protected function appendSaleOrderListRelations($data)
    {
        $orderIds = [];
        $hotelOrderIds = [];
        $unclaimedOrderIds = [];
        $machineIds = [];
        $machineLevels = [];
        foreach ($data as $item) {
            $orderId = intval($item['order_id'] ?? 0);
            if (!$orderId) continue;
            $orderIds[] = $orderId;
            if (intval($item['has_hotel'] ?? 0) === 1) $hotelOrderIds[] = $orderId;
            if (intval($item['out_status'] ?? 0) === 6) $unclaimedOrderIds[] = $orderId;
            if (intval($item['m_id'] ?? 0) > 0) $machineIds[] = intval($item['m_id']);
            if (intval($item['machine_level'] ?? 0) > 0) $machineLevels[] = intval($item['machine_level']);
        }
        $orderIds = array_values(array_unique($orderIds));
        if (!$orderIds) return $data;

        $detailsMap = [];
        $details = SaleOrdersDetailsModel::whereIn('order_id', $orderIds)->select();
        $detailOrganizationIds = [];
        foreach ($details as $detail) {
            $detail['cost_price'] = round($detail['cost_price'], 2);
            $detail['retail_price'] = round($detail['retail_price'], 2);
            $detail['total_sod_price'] = round($detail['total_sod_price'], 2);
            if (intval($detail['ao_id'] ?? 0) > 0) {
                $detailOrganizationIds[] = intval($detail['ao_id']);
            }
        }
        $detailOrganizationMap = [];
        $detailOrganizationIds = array_values(array_unique($detailOrganizationIds));
        if ($detailOrganizationIds) {
            $detailOrganizationMap = Db::name('auth_organization')
                ->whereIn('ao_id', $detailOrganizationIds)
                ->column('organization_name', 'ao_id');
        }
        foreach ($details as $detail) {
            $detail['organization_name'] = $detailOrganizationMap[intval($detail['ao_id'] ?? 0)] ?? null;
            $detailsMap[intval($detail['order_id'])][] = $detail;
        }

        $hotelMap = [];
        $nightlyMap = [];
        if ($hotelOrderIds) {
            $hotels = SaleHotelModel::whereIn('order_id', array_values(array_unique($hotelOrderIds)))->select();
            $hotelIds = [];
            foreach ($hotels as $hotel) {
                $hotelMap[intval($hotel['order_id'])] = $hotel;
                $hotelIds[] = intval($hotel['sh_id']);
            }
            if ($hotelIds) {
                $nightlies = SaleHotelNightlyModel::whereIn('sh_id', array_values(array_unique($hotelIds)))->select();
                foreach ($nightlies as $nightly) {
                    $nightlyMap[intval($nightly['sh_id'])][] = $nightly;
                }
            }
        }

        $unclaimedMap = [];
        if ($unclaimedOrderIds) {
            $unclaimedList = SaleOrdersUnclaimedModel::whereIn('order_id', array_values(array_unique($unclaimedOrderIds)))
                ->field('sod_id,order_id,g_name,channel_code,is_match,is_claim,is_out,is_close')
                ->order('su_id desc')
                ->select();
            foreach ($unclaimedList as $unclaimed) {
                $unclaimedMap[intval($unclaimed['order_id'])][] = $unclaimed;
            }
        }

        $machineLevelByMachine = [];
        if ($machineIds) {
            $machineLevelByMachine = MachineModel::whereIn('m_id', array_values(array_unique($machineIds)))
                ->column('machine_level', 'm_id');
            $machineLevels = array_merge($machineLevels, array_map('intval', array_values($machineLevelByMachine)));
        }
        $machineLevelDescMap = [];
        $machineLevels = array_values(array_unique(array_filter($machineLevels)));
        if ($machineLevels) {
            $machineLevelDescMap = MachineLevelDescModel::whereIn('machine_level', $machineLevels)
                ->column('name', 'machine_level');
        }

        return $data->each(function ($item) use ($detailsMap, $hotelMap, $nightlyMap, $unclaimedMap, $machineLevelByMachine, $machineLevelDescMap) {
            $orderId = intval($item['order_id']);
            $item['details'] = $detailsMap[$orderId] ?? [];
            $machineLevel = intval($item['machine_level'] ?? 0);
            if (!$machineLevel) {
                $machineLevel = intval($machineLevelByMachine[intval($item['m_id'] ?? 0)] ?? 0);
                $item['machine_level'] = $machineLevel;
            }
            $item['machine_level_desc'] = $machineLevelDescMap[$machineLevel] ?? '';
            if (intval($item['has_hotel'] ?? 0) === 1) {
                $hotel = $hotelMap[$orderId] ?? null;
                if ($hotel) {
                    $hotel['nightly'] = $nightlyMap[intval($hotel['sh_id'])] ?? [];
                    $item['hotel'] = $hotel;
                    $item['retail_price'] = bcadd((string)$item['retail_price'], (string)$hotel['pay_amount'], 2);
                } else {
                    $item['hotel'] = [];
                }
            }
            if (intval($item['out_status'] ?? 0) === 6 && isset($unclaimedMap[$orderId])) {
                $item['unclaimed_status'] = $unclaimedMap[$orderId];
            }
            unset($item['m_id']);
            return $item;
        });
    }

    /**
     * 生成订单
     * @param $insert
     * @return mixed
     */
    public function addSaleOrders($insert)
    {
        $insert = $this->appendRevenueCouponCode($insert);
        $insert = $this->appendOrderPayChannel($insert);
        $order = SaleOrdersModel::create($insert);
        actionLog($this->getLS(), '生成订单SQL');
        actionLog($order, '生成订单结果');
        return $order->order_id;
    }

    /**
     * 修改订单
     * @param $update
     * @param array $where
     * @param array $field
     * @return SaleOrdersModel
     */
    public function updateSaleOrders($update, $where = [], $field = [])
    {
        $update = $this->appendOrderPayChannelForUpdate($update, $where, $field);
        return SaleOrdersModel::update($update, $where, $field);
    }

    /**
     * 补充分账优惠券编码，独立于营销优惠券 coupon_code。
     * @param array $order
     * @return array
     */
    protected function appendRevenueCouponCode($order)
    {
        if (!empty($order['revenue_coupon_code'])) {
            $couponCode = trim(strval($order['revenue_coupon_code']));
        } elseif (isset($this->data) && !empty($this->data['revenue_coupon_code'])) {
            $couponCode = trim(strval($this->data['revenue_coupon_code']));
        } elseif (isset($this->config['params']) && !empty($this->config['params']['revenue_coupon_code'])) {
            $couponCode = trim(strval($this->config['params']['revenue_coupon_code']));
        } else {
            return $order;
        }

        if (preg_match('/^[1-9][0-9]{5}$/', $couponCode)) {
            $order['revenue_coupon_code'] = $couponCode;
        } else {
            unset($order['revenue_coupon_code']);
        }

        return $order;
    }

    /**
     * 自动补全订单分类（创建时）
     * @param array $order
     * @return array
     */
    protected function appendOrderPayChannel($order)
    {
        if (is_object($order)) {
            $order = method_exists($order, 'toArray') ? $order->toArray() : (array)$order;
        }
        if (!is_array($order)) {
            return $order;
        }
        if (isset($order['pay_channel']) && intval($order['pay_channel']) > 0) {
            if (empty($order['pay_channel_name'])) {
                $order['pay_channel_name'] = $this->getPayChannelName(intval($order['pay_channel']));
            }
            return $order;
        }
        $result = $this->buildOrderPayChannel($order);
        $order['pay_channel'] = $result['pay_channel'];
        $order['pay_channel_name'] = $result['pay_channel_name'];
        return $order;
    }

    /**
     * 自动补全订单分类（更新时）
     * @param array $update
     * @param array $where
     * @param array $field
     * @return array
     */
    protected function appendOrderPayChannelForUpdate($update, array &$where, array &$field)
    {
        if (is_object($update)) {
            $update = method_exists($update, 'toArray') ? $update->toArray() : (array)$update;
        }
        if (!is_array($update)) {
            return $update;
        }

        $refreshPayChannel = intval($update['refresh_pay_channel'] ?? 0);
        unset($update['refresh_pay_channel']);

        // 显式传入 pay_channel 时，仅兜底补 pay_channel_name
        if (isset($update['pay_channel']) && intval($update['pay_channel']) > 0) {
            if (empty($update['pay_channel_name'])) {
                $update['pay_channel_name'] = $this->getPayChannelName(intval($update['pay_channel']));
                if ($field && !in_array('pay_channel_name', $field, true)) {
                    $field[] = 'pay_channel_name';
                }
            }
            unset($update['has_wc_order_no']);
            return $update;
        }

        if (!$refreshPayChannel) {
            unset($update['has_wc_order_no']);
            return $update;
        }

        $orderId = intval($update['order_id'] ?? 0);
        if ($orderId <= 0 && isset($where['order_id'])) {
            $orderId = intval($where['order_id']);
        }
        if ($orderId <= 0 && !empty($update['trade_no'])) {
            $orderId = intval($this->getSaleOrdersValue(['trade_no' => $update['trade_no']], 'order_id'));
        }
        if ($orderId <= 0 && !empty($where['trade_no'])) {
            $orderId = intval($this->getSaleOrdersValue(['trade_no' => $where['trade_no']], 'order_id'));
        }
        if ($orderId <= 0) {
            unset($update['has_wc_order_no']);
            return $update;
        }

        $order = $this->getSaleOrdersFind(
            ['order_id' => $orderId],
            'order_id,order_type,pay_type,pay_method,total_cost_points,gift_points,total_points,acp_id,pay_channel,pay_channel_name'
        );
        if (!$order) {
            unset($update['has_wc_order_no']);
            return $update;
        }

        $order = is_object($order) ? (method_exists($order, 'toArray') ? $order->toArray() : (array)$order) : $order;
        $snapshot = array_merge($order, $update);
        $snapshot['order_id'] = $orderId;
        if (!array_key_exists('has_wc_order_no', $snapshot)) {
            $snapshot['has_wc_order_no'] = $this->hasWcOrderNo($orderId) ? 1 : 0;
        }

        $result = $this->buildOrderPayChannel($snapshot);
        $update['pay_channel'] = $result['pay_channel'];
        $update['pay_channel_name'] = $result['pay_channel_name'];
        unset($update['has_wc_order_no']);
        if ($field) {
            if (!in_array('pay_channel', $field, true)) {
                $field[] = 'pay_channel';
            }
            if (!in_array('pay_channel_name', $field, true)) {
                $field[] = 'pay_channel_name';
            }
        }
        return $update;
    }

    /**
     * 订单分类统一判定
     * @param array $order
     * @return array
     */
    public function buildOrderPayChannel(array $order)
    {
        $payType = intval($order['pay_type'] ?? 0);
        $payMethod = intval($order['pay_method'] ?? 0);
        $orderType = intval($order['order_type'] ?? 0);
        $acpId = intval($order['acp_id'] ?? 0);
        $totalCostPoints = floatval($order['total_cost_points'] ?? 0);
        $giftPoints = floatval($order['gift_points'] ?? 0);
        if ($giftPoints <= 0) {
            $giftPoints = floatval($order['total_points'] ?? 0);
        }

        if ($payType === 20) {
            return $this->formatPayChannel(6);
        }
        if ($totalCostPoints > 0) {
            return $this->formatPayChannel(4);
        }

        $hasWcOrderNo = intval($order['has_wc_order_no'] ?? -1);
        if ($hasWcOrderNo < 0) {
            $orderId = intval($order['order_id'] ?? 0);
            $hasWcOrderNo = $this->hasWcOrderNo($orderId) ? 1 : 0;
        }

        if ($giftPoints > 0 && !$hasWcOrderNo) {
            return $this->formatPayChannel(3);
        }
        if ($hasWcOrderNo) {
            return $this->formatPayChannel(1);
        }
        if ($payType === 7) {
            return $this->formatPayChannel(2);
        }
        if ($orderType === 3 && $acpId > 0) {
            return $this->formatPayChannel(5);
        }
        if (in_array($payType, [1, 11, 12], true)) {
            return $this->formatPayChannel(7);
        }
        if (in_array($payType, [2, 21, 22], true)) {
            return $this->formatPayChannel(8);
        }
        if (in_array($payMethod, [3, 4, 5], true) || in_array($payType, [4, 10, 33, 34, 35], true)) {
            return $this->formatPayChannel(9);
        }
        if (in_array($payMethod, [6, 7], true) || in_array($payType, [36, 37], true)) {
            return $this->formatPayChannel(10);
        }
        return $this->formatPayChannel(11);
    }

    /**
     * 是否存在非空微程订单号
     * @param int $orderId
     * @return bool
     */
    protected function hasWcOrderNo($orderId)
    {
        if ($orderId <= 0) {
            return false;
        }
        try {
            $wcOrderNoList = Db::name('sale_orders_details')
                ->where(['order_id' => $orderId])
                ->column('wc_order_no');
        } catch (\Exception $e) {
            actionException($e, 1);
            return false;
        }

        if (!$wcOrderNoList) {
            return false;
        }
        foreach ($wcOrderNoList as $wcOrderNo) {
            if (!$this->isEmptyWcOrderNo($wcOrderNo)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 批量计算订单是否存在有效 wc_order_no
     * @param array $orderIds
     * @return array
     */
    protected function buildOrderHasWcOrderNoMap($orderIds = [])
    {
        $map = [];
        if (!$orderIds) {
            return $map;
        }

        $rows = Db::name('sale_orders_details')
            ->whereIn('order_id', $orderIds)
            ->field('order_id,wc_order_no')
            ->select()
            ->toArray();
        if (!$rows) {
            return $map;
        }

        foreach ($rows as $row) {
            $orderId = intval($row['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }
            if (!isset($map[$orderId])) {
                $map[$orderId] = 0;
            }
            if (!$this->isEmptyWcOrderNo($row['wc_order_no'] ?? null)) {
                $map[$orderId] = 1;
            }
        }
        return $map;
    }

    /**
     * 判断 wc_order_no 是否为空
     * @param mixed $value
     * @return bool
     */
    protected function isEmptyWcOrderNo($value)
    {
        if ($value === null) {
            return true;
        }
        if (is_array($value)) {
            return empty($value);
        }
        return trim((string)$value) === '';
    }

    /**
     * 获取分类名称
     * @param int $payChannel
     * @return string
     */
    protected function getPayChannelName($payChannel)
    {
        $map = $this->getPayChannelNameMap();
        return $map[intval($payChannel)] ?? '其他';
    }

    /**
     * 格式化分类结果
     * @param int $payChannel
     * @return array
     */
    protected function formatPayChannel($payChannel)
    {
        $payChannel = intval($payChannel);
        return [
            'pay_channel' => $payChannel,
            'pay_channel_name' => $this->getPayChannelName($payChannel),
        ];
    }

    public function joinSoSodColumn($where, $column, $group = "")
    {
        return SaleOrdersModel::joinSodColumn($where, $column, $group);
    }

    public function getSaleOrdersDetailsJoinOrderList($where, $pageNum = 0, $field = "*", $order = "", $group = "")
    {
        return SaleOrdersDetailsModel::joinOrderList($where, $pageNum, $field, $order, $group);
    }

    public function getSaleOrdersDetailsJoinOrderFind($where, $field = "*")
    {
        return SaleOrdersDetailsModel::joinOrderFind($where, $field);
    }

    /**
     * 获取一条订单详情
     * @param $where
     * @param string $field
     * @param string $order
     * @return SaleOrdersDetailsModel|array|mixed|null|\think\Model
     */
    public function getSaleOrdersDetailsFind($where, $field = "*", $order = "")
    {
        return SaleOrdersDetailsModel::getFind($where, $field, $order);
    }

    /**
     * 获取订单详情列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return SaleOrdersDetailsModel|SaleOrdersDetailsModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getSaleOrdersDetailsList($where, $pageNum = 0, $field = "*", $order = "")
    {
        $data = SaleOrdersDetailsModel::getList($where, $pageNum, $field, $order)->each(function ($item) {
            $item['cost_price'] = round($item['cost_price'], 2);
            $item['retail_price'] = round($item['retail_price'], 2);
            $item['total_sod_price'] = round($item['total_sod_price'], 2);
            return $item;
        });
        return $data;
    }

    /**
     * 获取门票核销码
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function getDetailsCheckOffCode()
    {
        while (1) {
            $code = $this->get_rand_string(8, 'num');
            if (!SaleOrdersDetailsModel::getCount(['checkOff_code' => $code, 'checkOff_status' => 1])) {
                break;
            }
        }
        return $code;
    }

    /**
     * 获取销售订单字段列
     * @param $where
     * @param $column
     * @return array
     */
    public function getSaleOrdersColumn($where, $column)
    {
        $data = SaleOrdersDetailsModel::getColumn($where, $column);
        return $data;
    }

    /**
     * 获取销售订单详情字段列
     * @param $where
     * @param $column
     * @return array
     */
    public function getSaleOrdersDetailsColumn($where, $column)
    {
        $data = SaleOrdersDetailsModel::getColumn($where, $column);
        return $data;
    }

    /**
     * 订单副表字段求和
     * @param $where
     * @param $sum
     * @return float
     */
    public function getSaleOrdersDetailsSum($where, $sum)
    {
        return SaleOrdersDetailsModel::getSum($where, $sum);
    }

    public function joinSaleOrdersSum($where, $sum)
    {
        return SaleOrdersDetailsModel::joinOrderSum($where, $sum);
    }

    /**
     * 统计订单副表条数
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getSaleOrdersDetailsCount($where)
    {
        return SaleOrdersDetailsModel::getCount($where);
    }

    /**
     * 生成订单详情
     * @param $insert
     * @return mixed
     */
    public function addSaleOrdersDetails($insert)
    {
        if (!isset($insert['sod_ao_id']) || intval($insert['sod_ao_id']) <= 0) {
            $insert['sod_ao_id'] = $this->resolveSaleOrderDetailAoId($insert);
        }
        $sod = SaleOrdersDetailsModel::create($insert);
        actionLog($this->getLS(), '生成订单详情SQL');
        actionLog($sod, '生成订单详情结果');
        return $sod->sod_id;
    }

    /**
     * 解析订单详情所属组织（子单组织）
     * 规则：显式值 > 货道商品组织 > 设备租赁组织 > 订单主组织
     * @param array $insert
     * @return int
     */
    protected function resolveSaleOrderDetailAoId(array $insert): int
    {
        $sodAoId = intval($insert['sod_ao_id'] ?? 0);
        if ($sodAoId > 0) {
            return $sodAoId;
        }

        $mgAoId = 0;
        $mgId = intval($insert['mg_id'] ?? 0);
        if ($mgId > 0) {
            $mgAoId = intval(Db::name('machine_goods')->where(['mg_id' => $mgId])->value('ao_id'));
        }

        if ($mgAoId <= 0) {
            $mcId = intval($insert['mc_id'] ?? 0);
            if ($mcId > 0) {
                $mcMgId = intval(Db::name('machine_channel')->where(['mc_id' => $mcId])->value('mg_id'));
                if ($mcMgId > 0) {
                    $mgAoId = intval(Db::name('machine_goods')->where(['mg_id' => $mcMgId])->value('ao_id'));
                }
            }
        }

        if ($mgAoId > 0) {
            return $mgAoId;
        }

        $order = [];
        $orderId = intval($insert['order_id'] ?? 0);
        if ($orderId > 0) {
            $order = SaleOrdersModel::getFind(['order_id' => $orderId], 'order_id,m_id,machine_id,ao_id');
            $order = obj2arr($order);
        }

        $rentAoId = 0;
        if (!empty($order['m_id']) || !empty($order['machine_id'])) {
            $rentWhere = [];
            if (!empty($order['m_id'])) {
                $rentWhere['m_id'] = $order['m_id'];
            }
            if (!empty($order['machine_id'])) {
                $rentWhere['machine_id'] = $order['machine_id'];
            }

            if ($rentWhere) {
                $rentAoIds = AuthOrgMachineChannelModel::getColumn($rentWhere, 'ao_id');
                $rentAoIds = array_values(array_unique(array_filter(array_map('intval', $rentAoIds))));
                if (count($rentAoIds) === 1) {
                    $rentAoId = intval($rentAoIds[0]);
                }
            }
        }

        if ($rentAoId > 0) {
            return $rentAoId;
        }

        return intval($order['ao_id'] ?? 0);
    }

    /**
     * 对外API预订商品生成订单
     */
    protected function createSo()
    {
        $pay_method = 1;
        actionLog($this->machine, '对外接口预下订单，添加工厂、仓库信息');
        $this->order = [
            "trade_no" => $this->config['params']['order_no'],
            "mch_no" => $this->config['params']['order_no'],
            "user_name" => $this->config['params']['customer_name'] ?? "",
            "m_id" => $this->machine['m_id'],
            "machine_name" => $this->machine['machine_name'],
            "machine_id" => $this->machine['machine_id'],
            "ao_id" => $this->machine['ao_id'],
            "pay_type" => $this->config['params']['pay_type'] ?? 0,
            "pay_method" => $pay_method,
            "pay_status" => 3,
            "pay_time" => strtotime($this->config['params']['charge_time']) ?? "",
            "pay_code" => $this->params['pick_code'],
            "goods_type" => $this->params['goods_type'] ?? 1,
            "cost_price" => 0,
            "market_price" => 0,
            "retail_price" => 0,
            "total_quantity" => 0,
            "total_price" => 0,
            "discount_price" => 0,
            "factory" => $this->machine['factory'] ?? '',
            "inventory_location" => $this->machine['inventory_location'] ?? '',
            "create_date" => strtotime(date("Y-m-d")),
        ];
        $this->order['order_id'] = $this->addSaleOrders($this->order);
    }

    /**
     * 对外API预订商品生成订单数据
     * 创建订单副表数据，增加货架冻结库存，减少货架售卖库存
     * @return array|int|\think\response\Json
     */
    protected function createSod()
    {
        $flag = [];
        $details = json2arr($this->config['params']['order_detail']);
        actionLog($details, '商品数据');
        foreach ($details as $dk => $dv) {
            try {
                validate(VV2::class)->scene("order_detail")->check($dv);
            } catch (\Exception $e) {
                actionException($e, 1);
                return $this->returnData(6, $this->lang("msg." . 6) . "：" . $e->getMessage());
            }
            $g_id = $this->config['params']['pay_type'] == 7 ? ($dv['product_id'] ?? 0) : $dk;
            if (!$g_id) return $this->returnData(6, $this->lang("msg" . 6) . "：product_id");
            $whereMc = [];
            $whereMc['m_id'] = $this->machine['m_id'];
            $whereMc['g_id'] = $g_id;
            $whereMc['status'] = 1;
            $mc = $this->getMachineChannelList(
                $whereMc,
                0,
                'mc_id,channel_code,frozen_stock,stock,shelf_way,channel_position,manufacture_time,sell_by_date,
                        mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,batch_number,
                        cost_price,market_price',
                "stock desc"
            );
            actionLog($this->getLS(), '【SQL】查询设备货架');
            if (!$mc) return $this->returnData(10, $this->lang("msg." . 10));
            if (is_string($mc)) return $this->returnData(10, $this->lang("msg." . 10) . "：" . $mc);
            $mc = $mc->toArray();
            if (!$mc) return $this->returnData(10, $g_id . $this->lang("msg." . 10));
            actionLog($mc, "该设备下货架列表数据");
            // 总库存不足
            $totalStock = array_sum(array_column($mc, "stock"));
            if ($totalStock < $dv['quantity']) {
                $this->returnData = ["success" => false, "order_no" => $this->config['params']['order_no'], "error_msg" => [$g_id => $dv['quantity']]];
                return $this->returnData(14, $this->lang("msg." . 14) . "：" . $this->lang("reserve_order.under_stock"), $this->returnData);
            }

            $insertSod = [];
            $insertSod['order_id'] = $this->order['order_id'];
            $insertSod['quantity'] = 0;
            $insertSod['discount_price'] = 0;
            $insertSod['retail_price'] = bcdiv($dv['item_price'], 100, 3);
            foreach ($mc as $mck => $mcv) {
                $insertDetails = array_merge($mcv, $insertSod);
                unset($insertDetails['frozen_stock'], $insertDetails['stock']);
                $totalQuantity = 0;
                // 一条货道库存不够
                if ($dv['quantity'] > $mcv['stock']) {
                    $totalQuantity = $mcv['stock'];
                }
                if ($dv['quantity'] <= $mcv['stock']) {
                    $totalQuantity = $dv['quantity'];
                }
                // 循环生成一个商品一条数据的订单详情
                for ($i = 1; $i <= $totalQuantity; $i++) {
                    $insertDetails['quantity'] = 1;
                    $insertDetails['total_sod_price'] = 0;
                    $insertDetails['discount_price'] = 0;
                    $insertDetails['out_port'] = $dv['out_port'] ?? 1;
                    // 赠品
                    if ($dv['type'] == "gift") {
                        $insertDetails['is_gift'] = 1;
                    }
                    $dv['quantity'] = bcsub($dv['quantity'], $insertDetails['quantity']);
                    $updateMc = [
                        'frozen_stock' => bcadd($mcv['frozen_stock'], $insertDetails['quantity']),
                        'stock' => bcsub($mcv['stock'], $insertDetails['quantity']),
                        "mc_id" => $mcv['mc_id'],
                    ];
                    $flag[] = $this->addSaleOrdersDetails($insertDetails);
                    actionLog($this->getLS(), '生成订单详情');
                    $flag[] = $this->updateMachineChannel($updateMc);
                    $this->order['cost_price'] = bcadd($this->order['cost_price'], $insertDetails['cost_price'], 3);
                    $this->order['market_price'] = bcadd($this->order['market_price'], $insertDetails['market_price'], 3);
                    $this->order['retail_price'] = bcadd($this->order['retail_price'], $insertDetails['retail_price'], 3);
                }
                //                $this->order['total_quantity'] = bcadd($this->order['total_quantity'], $insertDetails['quantity'], 3);
                $insertDetails = [];
                if ($dv['quantity'] == 0)
                    break;
            }
            //            $this->order['total_price'] = bcadd($this->order['total_price'], bcdiv($dv['charge_amount'], 100, 3), 3);
            //            $this->order['discount_price'] = bcadd($this->order['discount_price'], bcdiv($dv['discount_amount'], 100, 3), 3);
        }
        actionLog($flag, '生成订单详情结果集');
        $result = flag_check($flag);
        if (!$result) return $this->returnData(14, $this->lang("msg." . 14));
        return $result;
    }


    /**
     * 修改订单详情
     * @param $update
     * @param array $where
     * @param array $field
     * @return SaleOrdersDetailsModel
     */
    public function updateSaleOrdersDetails($update, $where = [], $field = [])
    {
        return SaleOrdersDetailsModel::update($update, $where, $field);
    }

    /**
     * 增加订单副表字段值
     * @param $where
     * @param $field
     * @param int $inc
     * @return mixed
     */
    public function incSaleOrdersDetails($where, $field, $inc = 1)
    {
        return SaleOrdersDetailsModel::setInc($where, $field, $inc);
    }

    /**
     * 获取订单编号
     * @param string $msg
     * @return string
     */
    public function getSaleOrdersTradeNo($msg = "")
    {
        while (1) {
            $trade_no = date("YmdHis") . ($msg ? $msg : $this->get_rand_string(6));
            if (!SaleOrdersModel::be(['trade_no' => $trade_no])) {
                return $trade_no;
            }
        }
    }

    /**
     * 获取商品排行榜
     * @param $where
     * @param $pageNum
     * @param string $field
     * @param string $order
     * @param string $group
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getSaleGoodsRankingList($where, $pageNum, $field = '*', $order = '', $group = '')
    {
        return SaleOrdersDetailsModel::goodsRankingList($where, $pageNum, $field, $order, $group);
    }

    /**
     * 上传订单交易视频路径
     * @return SaleOrdersModel
     */
    public function transactionVideo()
    {
        if (strstr($this->message['trade_no'], "remote_out_goods")) {
            actionLog($this->message, "远程出货视频保存地址记录执行");
            $sod_id = str_replace("remote_out_goods_", "", $this->message['trade_no']);
            $sod_id = intval($sod_id);
            return $this->updateSaleOrdersDetails(['remote_out_goods_video' => $this->message['transaction_video']], ['sod_id' => $sod_id]);
        }
        if (strstr($this->message['trade_no'], "door_open")) {
            actionLog($this->message, "开门视频保存地址记录执行");
            return MachineErrorCodeModel::update(['transaction_video' => $this->message['transaction_video']], ['trade_no' => $this->message['trade_no']]);
        }
        return $this->updateSaleOrders(['transaction_video' => $this->message['transaction_video']], ['trade_no' => $this->message['trade_no']]);
    }


    /**
     * 修复11月份已支付订单，但订单信息不完整的问题
     * @param $postData
     */
    public function fixOrdersInfo($postData)
    {
        if(isset($postData['sql'])){
            $res = Db::query($postData['sql']);
            dd($res);
        }
        return SaleOrdersModel::fixOrdersInfo($postData);
    }
    
    /**
     * 查询异常订单列表（左连接异常处理表）
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFn
     * @param string $group
     * @param int $limit
     * @return SaleOrdersModel|SaleOrdersModel[]|array|\think\Collection|\think\Paginator
     */
    public function getSaleOrdersExceptionList($where, $pageNum = 0, $field = "*", $order = "", $eachFn = '', $group = '', $limit = 0)
    {
        $join = [
            ['join' => 'sale_orders_exception se','on' => 'se.order_id = a.order_id','type' => 'left',],
            ['join' => 'auth_manager am', 'on' => 'am.manager_id = se.manager_id', 'type' => 'left'],
        ];
        $data = SaleOrdersModel::getListAndWith($where, $pageNum, $field, $order, $eachFn, $group, $limit, [], $join);
        $data = $data->each(function ($item) {
            //去掉null显示
            $item['exception_status'] = is_null($item['exception_status']) ? 2 : 1;
            $item['exception_remark'] = $item['exception_remark'] ?: '';
            $item['manager_account'] = $item['manager_account'] ?: '';
            $item['manager_nickname'] = $item['manager_nickname'] ?: '';
            $item['exception_create_time'] = !empty($item['exception_create_time']) ? date('Y-m-d H:i:s', $item['exception_create_time']) : '';
            $item['details'] = $this->getSaleOrdersDetailsList(['order_id' => $item['order_id']], 0);
            if (($item['has_hotel'] ?? 0) == 1) {
                $hotel = $this->getSaleHotelFind(['order_id' => $item['order_id']]);
                if ($hotel) {
                    $hotel['nightly'] = $this->getSaleHotelNightlyList(['sh_id' => $hotel['sh_id']]);
                    $item['hotel'] = $hotel;
                    $item['retail_price'] = bcadd((string)($item['retail_price'] ?? 0), (string)($hotel['pay_amount'] ?? 0), 2);
                } else {
                    $item['hotel'] = [];
                }
            }
            if (($item['out_status'] ?? 0) == 6) {
                $unclaimed = $this->getSaleOrdersUnclaimedList(['order_id' => $item['order_id']], 0, 'sod_id,g_name,channel_code,is_match,is_claim,is_out,is_close');
                if ($unclaimed) {
                    $item['unclaimed_status'] = $unclaimed->toArray();
                }
            }
            return $item;
        });
        return $data;
    }
}
