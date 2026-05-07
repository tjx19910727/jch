<?php

return [
    // WebSocket 监听（think visual_screen_ws start）
    'ws_host' => env('visual_screen.ws_host', '0.0.0.0'),
    'ws_port' => (int) env('visual_screen.ws_port', 2351),
    // 前端拼接 wss 时参考路径（需 Nginx 反代到 ws_port）
    'ws_path' => env('visual_screen.ws_path', '/ws/visual-screen'),
];
