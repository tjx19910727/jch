<?php
/**
 * 生成 reserve_order 接口可用请求参数（pay_type=7）
 * 从数据库读取 auth_name=weicheng 的 auth_password 计算签名
 * 输出：原始表单字段格式 + 可直接执行的 curl 命令 + 可选 --send 实际发送
 */

// 读取 .env
$env = parse_ini_file(__DIR__ . '/../.env', true);
$db = $env['DATABASE'];

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8', $db['HOSTNAME'], $db['HOSTPORT'] ?? '3336', $db['DATABASE']),
    $db['USERNAME'],
    $db['PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// 1. 查询 weicheng 的 auth_password
$stmt = $pdo->prepare("SELECT auth_password FROM config_api WHERE auth_name = ? LIMIT 1");
$stmt->execute(['weicheng']);
$authPassword = $stmt->fetchColumn();
if (!$authPassword) {
    fwrite(STDERR, "未找到 auth_name=weicheng 的配置\n");
    exit(1);
}

$authName = 'weicheng';
$now = date('Y-m-d H:i:s');
$timestamp = (string)round(microtime(true) * 1000); // 毫秒级时间戳，与原请求格式一致

// 2. 手动构造 order_detail JSON 字符串，保持 2394.00 小数格式（与原始请求一致）
//    注意：json_encode 会把 2394.00 转成 2394，导致签名时字符串与发送后解码不一致
$orderDetailJson = '[{"quantity":1,"item_price":2394.00,"discount_amount":0,"product_id":"1001","type":"sale","charge_amount":2394.00}]';

// 3. 构造 params（业务参数，value 均为原始值，不发JSON转义）
$params = [
    "kiosk_id"       => "JCHM-H2D-0064",          // 设备ID（已调整）
    "order_no"       => "O" . date('ymdHis') . random_int(100, 999), // 新订单号
    "pay_type"       => "7",                        // 丽呈API取货码
    "payment_method" => "wechat",
    "customer_name"  => "林琼虹",
    "charge_time"    => $now,                       // 当前时间
    "expire_time"    => date('Y-m-d H:i:s', strtotime('+1 day')), // 有效期1天
    "notify_url"     => "https://exapi.ivcheng.com/msvc-ota/v1/jch/jch-callback/notice",
    "order_detail"   => $orderDetailJson,           // 商品明细 JSON 字符串
];

// 4. 计算签名（与 V2BaseClient::makeApiSign 完全一致）
$string1 = strtoupper(md5($authPassword . $timestamp));
ksort($params);
$signArr = [];
foreach ($params as $k => $v) {
    $signArr[] = $k . "=" . $v;
}
$signStr = $string1 . implode(",", $signArr);
$sign = strtoupper(md5($signStr));

// 5. 原始表单字段值（params 是未转义的 JSON 字符串）
echo "==================== 表单字段值（multipart/form-data POST /api/v2/index） ====================\n";
echo "auth_name = {$authName}\n";
echo "timestamp = {$timestamp}\n";
echo "api       = reserve_order\n";
echo "params    = " . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
echo "lang      = zh-cn\n";
echo "sign      = {$sign}\n";

$paramsRaw = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

echo "\n==================== 可直接执行的 curl 命令（Linux/macOS） ====================\n";
echo "curl -X POST 'https://api.kiosk-uat.kalos-blocks.com/api/v2/index' \\\n";
echo "  -F 'auth_name={$authName}' \\\n";
echo "  -F 'timestamp={$timestamp}' \\\n";
echo "  -F 'api=reserve_order' \\\n";
echo "  -F 'params=" . addcslashes($paramsRaw, "'") . "' \\\n";
echo "  -F 'lang=zh-cn' \\\n";
echo "  -F 'sign={$sign}'\n";

echo "\n==================== 参数明细 ====================\n";
echo "order_no      : " . $params['order_no'] . "\n";
echo "kiosk_id      : " . $params['kiosk_id'] . "\n";
echo "product_id    : 1001\n";
echo "charge_time   : " . $params['charge_time'] . "\n";
echo "expire_time   : " . $params['expire_time'] . "\n";
echo "timestamp     : " . $timestamp . "\n";
echo "sign          : " . $sign . "\n";

// 6. 可选：实际发送
if (in_array('--send', $argv, true)) {
    echo "\n==================== 正在发送请求... ====================\n";
    $postData = [
        'auth_name' => $authName,
        'timestamp' => $timestamp,
        'api'       => 'reserve_order',
        'params'    => $paramsRaw,
        'lang'      => 'zh-cn',
        'sign'      => $sign,
    ];
    $ch = curl_init('https://api.kiosk-uat.kalos-blocks.com/api/v2/index');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); // PHP 数组自动以 multipart/form-data 发送
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        echo "CURL错误: " . $err . "\n";
        exit(1);
    }
    echo "响应: " . $response . "\n";
}