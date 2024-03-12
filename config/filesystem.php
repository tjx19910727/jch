<?php

return [
    // 默认磁盘
    'default' => env('filesystem.driver', 'local'),
    // 磁盘列表
    'disks' => [
        'local' => [
            'type' => 'local',
            'root' => app()->getRuntimePath() . 'uploads/',
        ],
        'public' => [
            // 磁盘类型
            'type' => 'local',
            // 磁盘路径
            'root' => app()->getRootPath() . 'public/uploads/',
            // 磁盘路径对应的外部URL路径
            'url' => '/uploads/',
            // 可见性
            'visibility' => 'public',
        ],
        // 更多的磁盘配置信息
        'aliyun' => [
            'type' => 'aliyun',
            'accessId' => '******',
            'accessSecret' => '******',
            'bucket' => 'bucket',
            'endpoint' => 'oss-cn-hongkong.aliyuncs.com',
            'url' => 'http://oss-cn-hongkong.aliyuncs.com',//不要斜杠结尾，此处为URL地址域名。
        ],
        'qiniu' => [
            'type' => 'qiniu',
            'accessKey' => '******',
            'secretKey' => '******',
            'bucket' => 'bucket',
            'url' => '',//不要斜杠结尾，此处为URL地址域名。
        ],
    ],
];
