<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/4
 * Time: 14:04
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Management\ManagementClient;

class ActivityCouponUsedClient extends ManagementClient
{
    use ActivityCouponTrait, ActivityCouponUsedTrait;

    /**
     * 批量添加
     * @param $postData
     * @return array|string
     * @throws \Exception
     */
    public function addMore($postData)
    {
        $field = "c_id,reduction,c_type,code";
        $coupon = $this->getActivityCouponFind(['c_id' => $postData['c_id']], $field);
        if (!$coupon) return $this->r(100, '查无优惠券信息');
        $coupon = $coupon->toArray();
        if ($coupon['code']) return $this->r(100, '当前优惠券不使用随机码，无法生成随机码列表');
        unset($coupon['code']);
        $codeList = $this->getActivityCouponUsedColumn(["status" => 1], 'code');
        $codeList = array_merge($codeList,$this->getActivityCouponColumn([['status','in',[1,2]]],'code'));
        for ($i = 0; $i < $postData['quantity']; $i++) {
            $insert = $coupon;
            while (1) {
                $code = $this->leftHandZero(random_int(000000, 999999), 6);
                if (!in_array($code, $codeList)) {
                    $codeList[] = $code;
                    break;
                }
            }
            $insert['code'] = $code;
            $insertAll[] = $insert;
        }
        $result = $this->addActivityCouponUsedMore($insertAll);
        return $this->rAction($result);
    }

    /**
     * 导出优惠券码
     * @param $postData
     * @return array|string
     */
    public function exportCode($postData)
    {
        $coupon = $this->getActivityCouponFind(['c_id' => $postData['c_id']], "c_name,code");
        if (!$coupon) return $this->r(100, '查无优惠券信息');
        if ($coupon['code']) return $this->r(100, '当前优惠券不使用随机码，无法获取随机码列表');
        $list = $this->getActivityCouponUsedList(['c_id' => $postData['c_id']], 0, 'code');
        if ($list) {
            $list = $list->toArray();
            $title = ["code" => "优惠券码"];
            $filename = "【" . $coupon['c_name'] . "】优惠券码-" . date("YmdHis");
            return $this->sendToExport("营销活动-优惠券", $filename, $title, $list);
        }
        return $this->rFail("查无优惠券码");
    }

    /**
     * 导出使用报表
     * @param $postData
     * @return array|string
     */
    public function exportUsedList($postData)
    {
        $coupon = $this->getActivityCouponFind(['c_id' => $postData['c_id']], "c_name,code,desc");
        if (!$coupon) return $this->r(100, '查无优惠券信息');
        $list = $this->getActivityCouponUsedList(['c_id' => $postData['c_id']], 0,
            'pay_limit,machine_id,machine_name,
                reduction,original_price,discount_price,retail_price,code,trade_no,
                IFNULL((SELECT GROUP_CONCAT(t.g_name SEPARATOR ",") FROM (SELECT MAX(sod.g_name) g_name FROM sale_orders_details sod WHERE sod.order_id = a.order_id GROUP BY sod.sku) t), "") g_name,
                IFNULL((SELECT GROUP_CONCAT(DISTINCT sod.sku SEPARATOR ",") FROM sale_orders_details sod WHERE sod.order_id = a.order_id), "") sku,
                (CASE c_type WHEN 1 THEN "立减金额" WHEN 2 THEN "优惠折扣" END) c_type,
                (CASE status WHEN 1 THEN "未使用" WHEN 2 THEN "已使用" WHEN 3 THEN "已过期" WHEN 4 THEN "已作废" END ) status, 
                FROM_UNIXTIME(used_time,"%Y-%m-%d %H:%i:%s") used_time');
        if ($list) {
            $list = $list->toArray();
            foreach ($list as &$item) {
                $item['c_name'] = $coupon['c_name'] ?? '';
                $item['desc'] = $coupon['desc'] ?? '';
            }
            unset($item);
            $title = [
//                "c_name" => "优惠券名称",
//                "desc" => "简介",
//                "pay_limit" => "订单最低消费金额",
//                "machine_name" => "设备名称",
                "code" => "优惠码",
                "g_name" => "商品名称",
                "sku" => "SKU",
                "trade_no" => "订单编号",
                "machine_id" => "设备编号",
                "c_type" => "优惠券类型",
                "status" => "状态",
                "discount_price" => "优惠金额",
                "retail_price" => "支付金额",
                "used_time" => "使用时间",
            ];
            $filename = "【" . $coupon['c_name'] . "】使用报表-" . date("Ymd");
            return $this->sendToExport("营销活动-优惠券", $filename, $title, $list);
        }
        return $this->rFail("查无使用报表信息");
    }
}
