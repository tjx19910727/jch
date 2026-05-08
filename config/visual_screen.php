<?php

return [
    // WebSocket 监听（think visual_screen_ws start）
    'ws_host' => env('visual_screen.ws_host', '0.0.0.0'),
    'ws_port' => (int) env('visual_screen.ws_port', 2351),
    // 自动推送轮询间隔（秒）：检测到新支付订单后广播快照
    'auto_push_poll_interval' => (float) env('visual_screen.auto_push_poll_interval', 2),
    // 前端拼接 wss 时参考路径（需 Nginx 反代到 ws_port）
    'ws_path' => env('visual_screen.ws_path', '/ws/visual-screen'),
];
