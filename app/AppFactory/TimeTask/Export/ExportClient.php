<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/29
 * Time: 17:35
 */

namespace app\AppFactory\TimeTask\Export;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Export\ExportLogTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\OrderTypeTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRefundTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\TimeTask\TimeTaskBase;
use think\facade\Db;

class ExportClient extends TimeTaskBase
{
    use ExportLogTrait;
    use OrderTypeTrait;
    use SaleOrdersTrait, SaleOrdersRefundTrait;

    protected function buildSqlCaseByMap($column, $map, $defaultPrefix)
    {
        if (!is_array($map) || !$map) {
            return "IFNULL(CONCAT('" . str_replace("'", "''", $defaultPrefix) . "',{$column}), '')";
        }

        $cases = [];
        foreach ($map as $value => $label) {
            $cases[] = "WHEN " . intval($value) . " THEN '" . str_replace("'", "''", $label) . "'";
        }
        return "(CASE {$column} " . implode(' ', $cases) . " ELSE CONCAT('" . str_replace("'", "''", $defaultPrefix) . "',{$column}) END)";
    }

    protected function buildOrderTypeCaseSql($column)
    {
        $tableMap = $this->getOrderTypeNameMapFromTable(false);
        $map = $tableMap ?: [
            1 => '普通订单',
            2 => '优惠券订单',
            3 => '取货码订单',
            4 => '付费抽奖订单',
            5 => '满减满送订单',
            6 => '叠加营销活动订单',
        ];
        return $this->buildSqlCaseByMap($column, $map, '订单类型#');
    }

    /**
     * 生成Excel文件并且修改记录
     * @param $data
     * @return bool
     */
    public function makeExcel($data)
    {
        $exportId = 0;
        try {
            $data = json2arr($data);
            if ($data) {
                $exportId = intval($data['export_id'] ?? 0);
                actionLog([
                    'export_id' => $exportId,
                    'filename' => $data['filename'] ?? '',
                    'title_count' => isset($data['title']) && is_array($data['title']) ? count($data['title']) : 0,
                    'row_count' => isset($data['list']) && is_array($data['list']) ? count($data['list']) : 0,
                ], '导出Excel的数据摘要');
                $data['filename'] = $data['filename'] . date('His');
                $result = Excel::exportExcel($data['list'], $data['title'], $data['filename'], 0,
                    $data['otherData']['startRow'] ?? 1,
                    $data['otherData']['merge'] ?? [],
                    $data['otherData'] ?? []);
                $updateEL["export_id"] = $data['export_id'];
                $updateEL["file_name"] = $data['filename'];
                $updateEL["file_path"] = $result;
                $updateEL["export_time"] = time();
                $updateEL["status"] = 2;
                $this->updateExportLog($updateEL);
                return true;
            }
        } catch (\PHPExcel_Writer_Exception $e) {
            actionException($e, 1);
            if ($exportId) $this->updateExportLog(['export_id' => $exportId, 'status' => 4]);
        } catch (\PHPExcel_Exception $e) {
            actionException($e, 1);
            if ($exportId) $this->updateExportLog(['export_id' => $exportId, 'status' => 4]);
        } catch (\Throwable $e) {
            actionException($e, 1);
            if ($exportId) $this->updateExportLog(['export_id' => $exportId, 'status' => 4]);
        }
        @actionLog($this->getLS(), "【SQL】修改导出记录");
        return false;
    }

    /**
     * 多Sheet导出Excel
     * @param $data
     * @return bool
     */
    public function makeMultiSheetExcel($data)
    {
        $exportId = 0;
        try {
            $data = json2arr($data);
            if ($data) {
                $exportId = intval($data['export_id'] ?? 0);
                $sheets = $data['sheets'] ?? [];
                $sheetCount = is_array($sheets) ? count($sheets) : 0;
                actionLog([
                    'export_id' => $exportId,
                    'filename' => $data['filename'] ?? '',
                    'sheet_count' => $sheetCount,
                ], '多Sheet导出Excel的数据摘要');
                $data['filename'] = $data['filename'] . date('His');
                $result = Excel::exportMultiSheetExcel($sheets, $data['filename']);
                $updateEL = [
                    'export_id' => $exportId,
                    'file_name' => $data['filename'],
                    'file_path' => $result,
                    'export_time' => time(),
                    'status' => 2,
                ];
                $this->updateExportLog($updateEL);
                return true;
            }
        } catch (\PHPExcel_Writer_Exception $e) {
            actionException($e, 1);
            if ($exportId) $this->updateExportLog(['export_id' => $exportId, 'status' => 4]);
        } catch (\PHPExcel_Exception $e) {
            actionException($e, 1);
            if ($exportId) $this->updateExportLog(['export_id' => $exportId, 'status' => 4]);
        } catch (\Throwable $e) {
            actionException($e, 1);
            if ($exportId) $this->updateExportLog(['export_id' => $exportId, 'status' => 4]);
        }
        @actionLog($this->getLS(), "【SQL】修改导出记录");
        return false;
    }

    /**
     * 根据筛选条件生成销售订单Excel，避免接口侧查询全量数据和传递超大MQ消息。
     * @param $data
     * @return bool
     */
    public function makeSaleOrdersExcel($data)
    {
        $exportId = 0;
        try {
            $data = json2arr($data);
            $exportId = intval($data['export_id'] ?? 0);
            $title = $data['title'] ?? [];
            $filename = ($data['filename'] ?? '订单交易') . date('His');
            $list = $this->buildSaleOrdersExportRows($data);

            actionLog([
                'export_id' => $exportId,
                'filename' => $filename,
                'title_count' => is_array($title) ? count($title) : 0,
                'row_count' => count($list),
            ], '销售订单导出Excel的数据摘要');

            $result = Excel::exportExcel($list, $title, $filename, 0,
                $data['otherData']['startRow'] ?? 1,
                $data['otherData']['merge'] ?? []);
            $this->updateExportLog([
                'export_id' => $exportId,
                'file_name' => $filename,
                'file_path' => $result,
                'export_time' => time(),
                'status' => 2,
            ]);
            return true;
        } catch (\Throwable $e) {
            actionException($e, 1);
            if ($exportId) $this->updateExportLog(['export_id' => $exportId, 'status' => 4]);
        }
        return false;
    }

    /**
     * 商品交易列表导出（条件导出：消费端按筛选条件查询生成Excel，避免超大MQ消息）
     * @param $data
     * @return bool
     */
    public function makeGoodsSoExcel($data)
    {
        $exportId = 0;
        try {
            $data = json2arr($data);
            $exportId = intval($data['export_id'] ?? 0);
            $where = isset($data['where']) && is_array($data['where']) ? $data['where'] : [];
            $hasCostPriceAuth = !empty($data['has_cost_price_auth']);
            $costPriceField = $hasCostPriceAuth ? 'sod.cost_price' : '0 cost_price';
            $refundCostPriceField = $hasCostPriceAuth ? 'sod.cost_price' : '0 cost_price';
            $soOrderTypeCase = $this->buildOrderTypeCaseSql('so.order_type');

            $field = "so.machine_id,so.machine_name,so.trade_no,so.mch_no,sod.sku,sod.g_name,sod.channel_code,sod.retail_price,sod.discount_price,
        sod.total_sod_price,sod.total_sod_cost_points,sod.total_sod_points,so.factory,so.inventory_location,
            (CASE so.out_status WHEN 2 THEN '已发出货命令' WHEN 3 THEN '设备已接收' WHEN 4 THEN '出货成功' WHEN 5 THEN '出货失败' END) out_status,
            {$soOrderTypeCase} order_type,
            (CASE so.pay_type 
            WHEN 0 THEN '免支付' 
            WHEN 1 THEN '微信' 
            WHEN 2 THEN '支付宝'
            WHEN 4 THEN '京东收银'
            WHEN 5 THEN '会员支付'
            WHEN 6 THEN '丽呈线上支付'
            WHEN 7 THEN '机器人线上支付'
            WHEN 8 THEN '八达通COGOLINK'
            ELSE '' END) pay_type,
            (CASE so.pay_method 
            WHEN 0 THEN '免支付' 
            WHEN 1 THEN '扫码支付' 
            WHEN 41 THEN '扫码支付' 
            WHEN 2 THEN '被扫支付'
            ELSE '' END) pay_method,
            (CASE so.out_status 
                WHEN 1 THEN '正常'
                WHEN 2 THEN '已发出货指令'
                WHEN 3 THEN '设备已接收'
                WHEN 4 THEN (CASE WHEN so.refund_amount > 0 THEN so.refund_amount ELSE '正常' END)
                WHEN 5 THEN '出货失败'
                WHEN 6 THEN '未取商品'
                END 
            ) order_status,
            FROM_UNIXTIME(so.pay_time,'%Y-%m-%d %H:%i:%s') pay_time,
            FROM_UNIXTIME(so.out_time,'%Y-%m-%d %H:%i:%s') out_time,
            (sod.quantity) quantity,
            (sod.success_quantity) success_quantity,
            (SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = sod.sod_ao_id) organization_name,{$costPriceField}";
            $list = $this->getSaleOrdersDetailsJoinOrderList($where, 0, $field);
            if (!$list) {
                if ($exportId) $this->updateExportLog(['export_id' => $exportId, 'status' => 4]);
                return false;
            }
            $list = $list->toArray();

            // 合并已退款数据（与页面商品交易列表保持一致）
            $where['sor.status'] = 2;
            if (isset($where[0][0]) && strpos($where[0][0], "create_time") !== false) $where[0][0] = "sor.update_time";
            $refundField = "sor.machine_id,sor.machine_name,sor.trade_no,so.mch_no,so.factory,so.inventory_location,sod.sku,sor.g_name,sor.channel_code,sod.retail_price,sod.discount_price,(0-sor.refund_amount) total_sod_price,
                            (0-sod.refund_cost_points) total_sod_cost_points,(0-sod.refund_points) total_sod_points,
                            (CASE so.out_status WHEN 1 THEN '待取货' WHEN 2 THEN '已发出货命令' WHEN 3 THEN '设备已接收' WHEN 4 THEN '出货成功' WHEN 5 THEN '出货失败' END) out_status,
                        {$soOrderTypeCase} order_type,
                        (CASE so.pay_type 
                        WHEN 0 THEN '免支付' 
                        WHEN 1 THEN '微信' 
                        WHEN 2 THEN '支付宝'
                        WHEN 4 THEN '京东收银'
                        WHEN 5 THEN '会员支付'
                        WHEN 6 THEN '丽呈线上支付'
                        WHEN 7 THEN '机器人线上支付'
                        WHEN 8 THEN '八达通COGOLINK'
                        ELSE '' END) pay_type,
                        (CASE so.pay_method 
                        WHEN 0 THEN '免支付' 
                        WHEN 1 THEN '扫码支付' 
                        WHEN 41 THEN '扫码支付' 
                        WHEN 2 THEN '被扫支付'
                        ELSE '' END) pay_method,
                        ('已退款') order_status,
                        FROM_UNIXTIME(sor.update_time,'%Y-%m-%d %H:%i:%s') pay_time,
                        FROM_UNIXTIME(so.out_time,'%Y-%m-%d %H:%i:%s') out_time,
                        (sor.refund_quantity) quantity,
                        (sod.success_quantity) success_quantity,
                        (SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = sod.sod_ao_id) organization_name,{$refundCostPriceField}";
            $refund = $this->getSaleOrdersRefundListJoinSoSod($where, 0, $refundField);
            if ($refund) $list = array_merge($list, $refund->toArray());

            $title = [
                "machine_id" => "设备编号",
                "machine_name" => "设备名称",
                "trade_no" => "交易号",
                "mch_no" => "支付编号",
                "sku" => "SKU",
                "g_name" => "商品名称",
                "channel_code" => "槽位",
                "retail_price" => "单价",
                "discount_price" => "优惠价",
                "total_sod_price" => "支付金额",
                "total_sod_cost_points" => "消费积分",
                "total_sod_points" => "赠送积分",
                'factory' => "所属工厂",
                'inventory_location' => "库存地点",
                "out_status" => "出货状态",
                "order_status" => "订单状态",
                "order_type" => "订单类型",
                "pay_type" => "支付类型",
                "pay_method" => "支付方式",
                "pay_time" => "支付时间",
                "out_time" => "出货时间",
                "quantity" => "商品总数",
                "success_quantity" => "出货成功数量",
                "organization_name" => "所属组织",
            ];
            if ($hasCostPriceAuth) $title['cost_price'] = "成本价";
            // 发布端文件名已包含 YmdHis，此处不再追加时间，避免双时间戳
            $filename = $data['filename'] ?? ('商品交易列表-' . date('YmdHis'));
            actionLog([
                'export_id' => $exportId,
                'filename' => $filename,
                'title_count' => count($title),
                'row_count' => count($list),
            ], '商品交易导出Excel的数据摘要');

            $result = Excel::exportExcel($list, $title, $filename, 0,
                $data['otherData']['startRow'] ?? 1,
                $data['otherData']['merge'] ?? [],
                $data['otherData'] ?? []);
            $this->updateExportLog([
                'export_id' => $exportId,
                'file_name' => $filename,
                'file_path' => $result,
                'export_time' => time(),
                'status' => 2,
            ]);
            return true;
        } catch (\Throwable $e) {
            actionException($e, 1);
            if ($exportId) $this->updateExportLog(['export_id' => $exportId, 'status' => 4]);
        }
        return false;
    }

    protected function buildSaleOrdersExportRows($data)
    {
        $where = $data['where'] ?? [];
        $postData = $data['post_data'] ?? [];
        $mIds = $data['m_ids'] ?? [];
        $hasCostPriceAuth = !empty($data['has_cost_price_auth']);
        $costPriceField = $hasCostPriceAuth ? 'a.cost_price' : '0 cost_price';
        $refundCostPriceField = $hasCostPriceAuth ? 'so.cost_price' : '0 cost_price';
        $aOrderTypeCase = $this->buildOrderTypeCaseSql('a.order_type');
        $soOrderTypeCase = $this->buildOrderTypeCaseSql('so.order_type');

        $whereRaw = $where['raw'] ?? '';
        unset($where['raw']);
        $mainWhere = $this->prefixWhereForAlias($where, 'a.');
        $field = 'a.order_id,a.m_id,a.machine_id,a.machine_name,a.machine_level,IFNULL(mld.name,"") machine_level_desc,(CASE a.run_mode WHEN 2 THEN "测试模式" ELSE "生产模式" END) run_mode_desc,a.pay_status,a.trade_no,a.mch_no,a.total_quantity,a.total_price,a.total_cost_points,a.total_points,a.discount_price,a.retail_price,a.factory,a.inventory_location,
            (SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = a.ao_id) organization_name,
            ' . $aOrderTypeCase . ' order_type,
            (CASE a.out_status
                WHEN 1 THEN "正常"
                WHEN 2 THEN "已发出货命令"
                WHEN 3 THEN "设备已接收"
                WHEN 4 THEN (CASE a.refund_status WHEN 1 THEN "正常" WHEN 2 THEN "已退款" WHEN 3 THEN "退款失败" END)
                WHEN 5 THEN "出货失败"
                WHEN 6 THEN "未取商品"
                END
            ) refund_status,
            (CASE a.pay_type
                WHEN 1 THEN "微信支付"
                WHEN 2 THEN "支付宝支付"
                WHEN 3 THEN ""
                WHEN 4 THEN "京东收银"
                WHEN 5 THEN "会员支付"
                WHEN 6 THEN "丽呈线上支付"
                WHEN 7 THEN "机器人线上支付"
                WHEN 8 THEN "八达通COGOLINK"
                WHEN 0 THEN "免支付" END) pay_type,
            FROM_UNIXTIME(a.pay_time,"%Y-%m-%d %H:%i:%s") pay_time,
            FROM_UNIXTIME(a.out_time,"%Y-%m-%d %H:%i:%s") out_time,' . $costPriceField;

        $query = Db::name('sale_orders')->alias('a')
            ->leftJoin('machine_level_desc mld', 'mld.machine_level = a.machine_level')
            ->where($mainWhere)
            ->field($field)
            ->order('a.order_id asc');
        if ($whereRaw) $query = $query->whereRaw($whereRaw);
        $list = $query->select()->toArray();

        $refundWhere = $this->prefixWhereForAlias($where, 'so.');
        if ($whereRaw) {
            $refundRaw = str_replace('pay_status', 'so.pay_status', $whereRaw);
        } else {
            $refundRaw = '';
        }
        $refundWhere['sor.status'] = 2;
        if ($mIds) $refundWhere[] = ['sor.m_id', 'in', $mIds];
        if (!empty($postData['m_id'])) $refundWhere['sor.m_id'] = $postData['m_id'];
        if (!empty($postData['mch_no'])) $refundWhere[] = ['so.mch_no', 'like', '%' . $postData['mch_no'] . '%'];
        if (!empty($postData['trade_no'])) $refundWhere[] = ['sor.trade_no', 'like', '%' . $postData['trade_no'] . '%'];
        if (!empty($postData['pay_time'])) {
            $time = explode('~', $postData['pay_time']);
            if (count($time) >= 2) $refundWhere[] = ['sor.update_time', 'between', [strtotime($time[0]), strtotime($time[1])]];
        }
        if (!empty($postData['machine_name'])) $refundWhere[] = ['sor.machine_name', 'like', '%' . $postData['machine_name'] . '%'];
        if (!empty($postData['out_time'])) {
            $time = explode('~', $postData['out_time']);
            if (count($time) >= 2) $refundWhere[] = ['so.out_time', 'between', [strtotime($time[0]), strtotime($time[1])]];
        }

        $refundField = 'sor.order_id,so.m_id,sor.machine_id,sor.machine_name,so.machine_level,IFNULL(mld.name,"") machine_level_desc,(CASE so.run_mode WHEN 2 THEN "测试模式" ELSE "生产模式" END) run_mode_desc,sor.trade_no,so.mch_no,so.factory,so.inventory_location,
            (SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = so.ao_id) organization_name,
            sor.refund_quantity total_quantity,
            (0-sor.refund_amount) total_price,("-") total_cost_points,("-") total_points,("-") discount_price,("-") retail_price,
            ("已退款") refund_status,
            ' . $soOrderTypeCase . ' order_type,
            (CASE so.pay_type
                WHEN 1 THEN "微信支付"
                WHEN 2 THEN "支付宝支付"
                WHEN 3 THEN ""
                WHEN 4 THEN "京东收银"
                WHEN 5 THEN "会员支付"
                WHEN 6 THEN "丽呈线上支付"
                WHEN 7 THEN "机器人线上支付"
                WHEN 8 THEN "八达通COGOLINK"
                WHEN 0 THEN "免支付" END) pay_type,
            FROM_UNIXTIME(sor.update_time,"%Y-%m-%d %H:%i:%s") pay_time,("-") out_time,' . $refundCostPriceField;
        $refundQuery = Db::name('sale_orders_refund')->alias('sor')
            ->leftJoin('sale_orders so', 'so.order_id = sor.order_id')
            ->leftJoin('machine_level_desc mld', 'mld.machine_level = so.machine_level')
            ->where($refundWhere)
            ->field($refundField)
            ->order('sor.update_time asc');
        if ($refundRaw) $refundQuery = $refundQuery->whereRaw($refundRaw);
        $refund = $refundQuery->select()->toArray();
        $list = array_merge($list, $refund);

        foreach ($list as $k => $item) {
            unset($list[$k]['m_id']);
        }
        return $list;
    }

    protected function prefixWhereForAlias($where, $prefix)
    {
        $result = [];
        foreach ($where as $key => $value) {
            if (is_int($key) && is_array($value) && isset($value[0]) && strpos($value[0], '.') === false) {
                $value[0] = $prefix . $value[0];
                $result[] = $value;
                continue;
            }
            if (!is_int($key) && strpos((string)$key, '.') === false) {
                $result[$prefix . $key] = $value;
                continue;
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * 超过3天删除Excel表格
     * 定时任务：php think time_task export clearExcel
     * @return string
     */
    public function clearExcel()
    {
        $where[] = ['create_time', '<=', strtotime("-3 days")];
        $where[] = ['status','<',3];
        $log = $this->getExportLogList($where);
        if ($log) {
            $log = $log->toArray();
            foreach ($log as $k => $v) {
                if (file_exists(root_path() . 'public' . $v['file_path'])) {
                    @unlink(root_path() . 'public' . $v['file_path']);
                }
                $this->updateExportLog(['export_id' => $v['export_id'], 'status' => 3]);
                actionLog($this->getLS(),'修改导出记录');
            }
        }
        return "处理完成";
    }
}
