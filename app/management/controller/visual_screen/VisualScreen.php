<?php

namespace app\management\controller\visual_screen;

use app\management\controller\Common;
use app\management\service\VisualScreenService;
use think\facade\Config;

/**
 * 数字大屏 /visual-screen 管理端 HTTP 接口（与 WS 协议字段对齐）
 */
class VisualScreen extends Common
{
    /**
     * 全量快照（等同 WS 事件 visual_screen_snapshot 的 payload）
     * 参数：regionType, regionName, cycle, machinePage, machinePageSize, lastOrderId
     */
    public function getSnapshot()
    {
        $ctx = [
            'regionType' => input('regionType/s', 'national'),
            'regionName' => input('regionName/s', ''),
            'cycle' => input('cycle/s', 'day'),
            'machinePage' => input('machinePage/d', 1),
            'machinePageSize' => input('machinePageSize/d', 128),
            'lastOrderId' => input('lastOrderId/d', 0),
        ];
        $svc = new VisualScreenService($this->app);
        $data = $svc->buildSnapshot($ctx);
        return returnState(200, '查询成功', $data);
    }


    public function getMachineList(){
        $body = [];
        $rawInput = request()->getInput();
        $contentType = strtolower((string) request()->header('content-type', ''));
        $looksLikeJson = strpos($contentType, 'application/json') !== false;
        if (!$looksLikeJson && is_string($rawInput) && trim($rawInput) !== '') {
            $looksLikeJson = (bool) preg_match('/^\s*[\{\[]/', $rawInput);
        }
        if (is_string($rawInput) && $rawInput !== '') {
            $decoded = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $body = $decoded;
            } elseif ($looksLikeJson) {
                return returnState(400, '请求体 JSON 格式错误，请使用 "pageSize": 3 这样的键值写法', []);
            }
        }
        $regionType = input('regionType/s', '');

        if ($regionType === '' && array_key_exists('regionType', $body)) {
            $regionType = (string) $body['regionType'];
        }
        if ($regionType === '') {
            $regionType = 'national';
        }

        $regionName = input('regionName/s', '');
        if ($regionName === '' && array_key_exists('regionName', $body)) {
            $regionName = (string) $body['regionName'];
        }
        $regionName = trim($regionName);

        $pageRaw = input('page', null);
        if (($pageRaw === null || $pageRaw === '') && array_key_exists('page', $body)) {
            $pageRaw = $body['page'];
        }
        if (($pageRaw === null || $pageRaw === '') && array_key_exists('machinePage', $body)) {
            $pageRaw = $body['machinePage'];
        }

        $pageSizeRaw = input('pageSize', null);
        if ($pageSizeRaw === null || $pageSizeRaw === '') {
            $pageSizeRaw = input('machinePageSize', null);
        }
        if (($pageSizeRaw === null || $pageSizeRaw === '') && array_key_exists('pageSize', $body)) {
            $pageSizeRaw = $body['pageSize'];
        }
        if (($pageSizeRaw === null || $pageSizeRaw === '') && array_key_exists('machinePageSize', $body)) {
            $pageSizeRaw = $body['machinePageSize'];
        }

        $ctx = [
            'regionType' => $regionType,
            'regionName' => $regionName,
            'page' => max(1, (int) ($pageRaw ?? 1)),
            'pageSize' => min(256, max(1, (int) ($pageSizeRaw ?? 15))),
        ];
        $svc = new VisualScreenService($this->app);
        $data = $svc->getMachineList($ctx); 
        return returnState(200, '查询成功', $data);
    }

    /**
     * 销售趋势（等同 WS 事件 visual_screen_sales_trend_update 的 payload）
     */
    public function getSalesTrend()
    {
        $ctx = [
            'regionType' => input('regionType/s', 'national'),
            'regionName' => input('regionName/s', ''),
            'cycle' => input('cycle/s', 'day'),
        ];
        $svc = new VisualScreenService($this->app);
        $data = $svc->buildSalesTrend($ctx);
        return returnState(200, '查询成功', $data);
    }

    /**
     * WebSocket 连接说明（前端拼接 wss 地址；实际监听见 config/visual_screen.php）
     */
    public function getWsInfo()
    {
        $cfg = Config::get('visual_screen');
        $path = $cfg['ws_path'] ?? '/ws/visual-screen';
        $port = (int) ($cfg['ws_port'] ?? 2351);
        $scheme = request()->isSsl() ? 'wss' : 'ws';
        $host = request()->host();
        $url = $scheme . '://' . $host . $path;
        return returnState(200, '查询成功', [
            'url' => $url,
            'path' => $path,
            'port' => $port,
            'hint' => '生产环境请用 Nginx 将 ' . $path . ' 反代到 Workerman 端口 ' . $port . '；连接 query 需带 token=管理端登录 token',
        ]);
    }
}
