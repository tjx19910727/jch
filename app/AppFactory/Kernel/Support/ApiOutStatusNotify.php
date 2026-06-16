<?php

namespace app\AppFactory\Kernel\Support;

class ApiOutStatusNotify
{
    public const API_OUT_BASE_URL = 'https://api-robot.jchtechnologies.com';
    // public const API_OUT_BASE_URL = 'https://7qu4689qm405.vicp.fun';
    public const ORDER_OUT_STATUS_NOTIFY_PATH = '/phpShipment/isShipping';
    public const ORDER_OUT_STATUS_NOTIFY_URL = self::API_OUT_BASE_URL . self::ORDER_OUT_STATUS_NOTIFY_PATH;
    public const MOBILE_VENDING_MACHINE_LEVELS = [5];

    public static function getOrderOutStatusNotifyUrl(): string
    {
        $baseUrl = defined('API_OUT_BASE_URL') ? constant('API_OUT_BASE_URL') : self::API_OUT_BASE_URL;
        return rtrim((string)$baseUrl, '/') . '/' . ltrim(self::ORDER_OUT_STATUS_NOTIFY_PATH, '/');
    }
}
