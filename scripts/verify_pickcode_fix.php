<?php
/**
 * 验证修复：reserve_order 取货码（pick_type=3, ap_id=0）设备端查询
 * 对比：
 *  1. 修复前逻辑：getActivityPickCodeFindWithPick（inner join activity_pick）→ 应查不到
 *  2. 修复后逻辑：getActivityPickCodeFind（pick_type=3, ap_id=0 直查）→ 应能查到
 */

namespace think;

require __DIR__ . '/../vendor/autoload.php';

(new App())->initialize();

use app\AppFactory\Kernel\Model\Activity\Pick\ActivityPickCodeModel;

$code = '92997457';
$now = time();

echo "==================== 取货码：{$code} ====================\n\n";

// 1. 修复前逻辑（原 getFindWithPick inner join）
echo "【1】修复前逻辑 getActivityPickCodeFindWithPick（inner join activity_pick）\n";
$where['apc.code'] = $code;
$old = ActivityPickCodeModel::getFindWithPick($where);
if ($old) {
    echo "  ✅ 查询到：" . json_encode($old->toArray(), JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "  ❌ 查询结果为空 → 设备端返回【查无活动】（这就是原 bug）\n";
}

// 2. 修复后逻辑（getActivityPickCodeFind 直查 pick_type=3, ap_id=0, status=1）
echo "\n【2】修复后逻辑 getActivityPickCodeFind（pick_type=3, ap_id=0, status=1 直查）\n";
$new = ActivityPickCodeModel::getFind([
    'code'      => $code,
    'pick_type' => 3,
    'ap_id'     => 0,
    'status'    => 1,
], 'apc_id,ap_id,code,order_id,trade_no,m_id,machine_id,machine_name,pick_type,status,used_time');
if ($new) {
    echo "  ✅ 查询到：" . json_encode($new->toArray(), JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "  ❌ 查询结果为空（可能取货码不存在或状态非1）\n";
}

// 3. 关联订单信息
echo "\n【3】关联订单信息\n";
$apc = $new ? $new->toArray() : null;
if ($apc && $apc['order_id']) {
    $order = \think\facade\Db::name('sale_orders')
        ->where('order_id', $apc['order_id'])
        ->field('order_id,trade_no,machine_id,pay_type,pay_status,out_status,ao_id,total_price')
        ->find();
    if ($order) {
        echo "  " . json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "  ❌ 未找到订单\n";
    }
}

echo "\n==================== 验证完成 ====================\n";