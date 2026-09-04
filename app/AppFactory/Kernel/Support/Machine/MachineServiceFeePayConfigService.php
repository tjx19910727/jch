<?php

namespace app\AppFactory\Kernel\Support\Machine;

/**
 * 设备服务费使用公司的固定收款账号。
 *
 * 本服务是唯一配置入口，不读取.env、config目录或strategy_payee表。
 * 生产发布前请填写对应渠道的商户信息和证书路径；未填写完整的渠道不会
 * 出现在管理端支付方式列表中，也不能创建二维码或处理支付/退款回调。
 */
class MachineServiceFeePayConfigService
{
    const QR_EXPIRE_SECONDS = 300;
    const GRACE_SECONDS = 86400;
    const MAX_RENEW_YEARS = 5;

    /**
     * 公司微信支付商户配置。
     * cert_path、key_path可填写项目内绝对路径，也可填写项目根目录下的相对路径。
     */
    private static $wx = [
        'app_id' => 'wx6add64bdd5e90857',
        'mch_id' => '1680258663',
        'key' => '8CQcW5XZ7SQVVnPOS6XReW8wNLm8exTW',
        'cert_path' => 'cert/machine_service_fee/wx/apiclient_cert.pem',
        'key_path' => 'cert/machine_service_fee/wx/apiclient_key.pem',
    ];

    /**
     * 公司支付宝商户配置。
     * 证书路径可填写项目内绝对路径，也可填写项目根目录下的相对路径。
     */
    private static $ali = [
        'app_id' => '2021004156601139',
        'pid' => '2088250137551256',
        'private_key_path' => 'cert/machine_service_fee/ali/app_private_key.txt',
        'ali_public_key_path' => 'cert/machine_service_fee/ali/ali_public_key_path.crt',
        'ali_root_cert_path' => 'cert/machine_service_fee/ali/ali_root_cert_path.crt',
        'app_public_key_path' => 'cert/machine_service_fee/ali/app_public_key_path.crt',
    ];

    public static function getPayConfig($channel)
    {
        $channel = strtolower(trim((string)$channel));
        if (!in_array($channel, ['wx', 'ali'], true)) {
            throw new \InvalidArgumentException('不支持的设备服务费支付渠道');
        }

        $config = $channel === 'wx' ? self::$wx : self::$ali;
        $required = $channel === 'wx'
            ? ['app_id', 'mch_id', 'key', 'cert_path', 'key_path']
            : ['app_id', 'pid', 'private_key_path', 'ali_public_key_path', 'ali_root_cert_path', 'app_public_key_path'];
        $missing = [];
        foreach ($required as $field) {
            if (trim((string)($config[$field] ?? '')) === '') {
                $missing[] = $field;
            }
        }
        if ($missing) {
            throw new \RuntimeException(
                '公司设备服务费' . ($channel === 'wx' ? '微信' : '支付宝') .
                '收款配置未完成：' . implode('、', $missing)
            );
        }

        $pathFields = $channel === 'wx'
            ? ['cert_path', 'key_path']
            : ['private_key_path', 'ali_public_key_path', 'ali_root_cert_path', 'app_public_key_path'];
        foreach ($pathFields as $field) {
            $config[$field] = self::projectAbsolutePath($config[$field]);
            if (!is_file($config[$field])) {
                throw new \RuntimeException(
                    '公司设备服务费' . ($channel === 'wx' ? '微信' : '支付宝') .
                    '证书文件不存在：' . $field
                );
            }
        }

        if ($channel === 'wx') {
            // 兼容项目当前EasyWeChat支付组件使用的字段名。
            $config['mchid'] = $config['mch_id'];
            $config['privateKey'] = $config['key_path'];
        } else {
            $config['isObject'] = false;
        }
        return $config;
    }

    public static function isPayConfigured($channel)
    {
        try {
            self::getPayConfig($channel);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function projectAbsolutePath($path)
    {
        $path = trim((string)$path);
        if (preg_match('/^[a-zA-Z]:[\\\\\/]|^\//', $path)) {
            return $path;
        }
        return dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . ltrim($path, "\\/");
    }
}
