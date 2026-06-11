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
use app\AppFactory\TimeTask\TimeTaskBase;
use think\facade\Db;

class ExportClient extends TimeTaskBase
{
    use ExportLogTrait;

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

    protected function buildSaleOrdersExportRows($data)
    {
        $where = $data['where'] ?? [];
        $postData = $data['post_data'] ?? [];
        $mIds = $data['m_ids'] ?? [];
        $hasCostPriceAuth = !empty($data['has_cost_price_auth']);
        $costPriceField = $hasCostPriceAuth ? 'a.cost_price' : '0 cost_price';
        $refundCostPriceField = $hasCostPriceAuth ? 'so.cost_price' : '0 cost_price';

        $whereRaw = $where['raw'] ?? '';
        unset($where['raw']);
        $mainWhere = $this->prefixWhereForAlias($where, 'a.');
        $field = 'a.order_id,a.m_id,a.machine_id,a.machine_name,a.machine_level,IFNULL(mld.name,"") machine_level_desc,a.pay_status,a.trade_no,a.mch_no,a.total_quantity,a.total_price,a.total_cost_points,a.total_points,a.discount_price,a.retail_price,a.factory,a.inventory_location,
            (SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = a.ao_id) organization_name,
            (CASE a.order_type
                WHEN 1 THEN "普通订单"
                WHEN 2 THEN "优惠券订单"
                WHEN 3 THEN "取货码订单"
                WHEN 4 THEN "盲盒活动"
                WHEN 5 THEN "满减满送活动"
                WHEN 6 THEN "叠加营销活动"
                END
            ) order_type,
            IFNULL(NULLIF(a.pay_channel_name,""),(CASE a.pay_channel
                WHEN 1 THEN "微程小程序订单"
                WHEN 2 THEN "机械车小程序订单"
                WHEN 3 THEN "售卖机会员积分订单"
                WHEN 4 THEN "商场积分订单"
                WHEN 5 THEN "取货码订单"
                WHEN 6 THEN "余额支付订单"
                WHEN 7 THEN "微信支付"
                WHEN 8 THEN "支付宝支付"
                WHEN 9 THEN "POS/刷卡支付"
                WHEN 10 THEN "现金支付"
                WHEN 11 THEN "其他"
                ELSE "其他" END)) pay_channel,
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

        $refundField = 'sor.order_id,so.m_id,sor.machine_id,sor.machine_name,so.machine_level,IFNULL(mld.name,"") machine_level_desc,sor.trade_no,so.mch_no,so.factory,so.inventory_location,
            (SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = so.ao_id) organization_name,
            sor.refund_quantity total_quantity,
            (0-sor.refund_amount) total_price,("-") total_cost_points,("-") total_points,("-") discount_price,("-") retail_price,
            ("已退款") refund_status,
            (CASE so.order_type
                WHEN 1 THEN "普通订单"
                WHEN 2 THEN "优惠券订单"
                WHEN 3 THEN "取货码订单"
                WHEN 4 THEN "盲盒活动"
                WHEN 5 THEN "满减满送活动"
                WHEN 6 THEN "叠加营销活动"
                END
            ) order_type,
            IFNULL(NULLIF(so.pay_channel_name,""),(CASE so.pay_channel
                WHEN 1 THEN "微程小程序订单"
                WHEN 2 THEN "机械车小程序订单"
                WHEN 3 THEN "售卖机会员积分订单"
                WHEN 4 THEN "商场积分订单"
                WHEN 5 THEN "取货码订单"
                WHEN 6 THEN "余额支付订单"
                WHEN 7 THEN "微信支付"
                WHEN 8 THEN "支付宝支付"
                WHEN 9 THEN "POS/刷卡支付"
                WHEN 10 THEN "现金支付"
                WHEN 11 THEN "其他"
                ELSE "其他" END)) pay_channel,
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
