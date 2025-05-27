<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/25
 * Time: 14:46
 */

return [
    "host" => env('redis.host', '127.0.0.1'),
    "port" => env('redis.port', '6379'),
    "password" => env('redis.password', ''),
    "timeout" => env('redis.timeout', 0),
    "reserved" => env('redis.reserved', null),
    "retry_interval" => env('redis.retry_interval', 0),
];