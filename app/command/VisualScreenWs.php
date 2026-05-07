<?php

declare(strict_types=1);

namespace app\command;

use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Support\TDESUtil;
use app\management\service\VisualScreenService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Config;
use think\facade\Db;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * 数字大屏 WebSocket：协议见文档 visual-screen（subscribe / switch_region / switch_cycle / ping）
 *
 * 启动：php think visual_screen_ws start
 * 连接示例：ws://127.0.0.1:2351?token=管理端token
 */
class VisualScreenWs extends Command
{
    protected function configure()
    {
        $this->setName('visual_screen_ws')
            ->addArgument('action', Argument::OPTIONAL, 'start|stop|restart|reload|status|connections', 'start')
            ->setDescription('数字大屏 WebSocket（Workerman）');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action') ?: 'start';
        if (DIRECTORY_SEPARATOR === '\\' && $action !== 'start') {
            $output->writeln('<error>Windows 下仅支持 start，请使用: php think visual_screen_ws start</error>');
            return;
        }
        if (DIRECTORY_SEPARATOR !== '\\') {
            global $argv;
            $argv = $argv ?? [];
            array_shift($argv);
            array_shift($argv);
            array_unshift($argv, 'think', $action);
        }

        $cfg = Config::get('visual_screen');
        $host = $cfg['ws_host'] ?? '0.0.0.0';
        $port = (int) ($cfg['ws_port'] ?? 2351);
        $socket = 'websocket://' . $host . ':' . $port;

        $worker = new Worker($socket);
        $worker->name = 'visual_screen_ws';
        $worker->count = 1;

        $worker->onWebSocketConnect = function (TcpConnection $connection, $httpBuffer) {
            $token = '';
            if (is_string($httpBuffer) && preg_match('/GET\s+([^\s]+)\s+HTTP/i', $httpBuffer, $m)) {
                $q = parse_url($m[1], PHP_URL_QUERY);
                if ($q) {
                    parse_str($q, $qs);
                    $token = isset($qs['token']) ? (string) $qs['token'] : '';
                }
            }
            $mgr = self::authManagerByToken($token);
            if (!$mgr) {
                $connection->send(json_encode(VisualScreenService::wsPushPayload('error', [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'token invalid',
                ]), JSON_UNESCAPED_UNICODE));
                $connection->close();
                return;
            }
            $connection->visualScreenManager = $mgr;
            $connection->visualScreenCtx = [
                'regionType' => 'national',
                'regionName' => '',
                'cycle' => 'day',
                'machinePage' => 1,
                'machinePageSize' => 128,
            ];
        };

        $worker->onMessage = function (TcpConnection $connection, $data) {
            if (!isset($connection->visualScreenManager)) {
                return;
            }
            $packet = json_decode((string) $data, true);
            if (!is_array($packet)) {
                $connection->send(json_encode(VisualScreenService::wsPushPayload('error', [
                    'code' => 'BAD_REQUEST',
                    'message' => 'invalid json',
                ]), JSON_UNESCAPED_UNICODE));
                return;
            }
            $event = isset($packet['event']) ? (string) $packet['event'] : '';
            $traceId = isset($packet['traceId']) ? (string) $packet['traceId'] : null;
            $payload = isset($packet['payload']) && is_array($packet['payload']) ? $packet['payload'] : [];

            $app = AppFactory::management($connection->visualScreenManager);
            $svc = new VisualScreenService($app);
            $ctx = $connection->visualScreenCtx;

            try {
                switch ($event) {
                    case 'visual_screen_subscribe':
                        $ctx['regionType'] = $payload['regionType'] ?? 'national';
                        $ctx['regionName'] = isset($payload['regionName']) ? (string) $payload['regionName'] : '';
                        $ctx['cycle'] = $payload['cycle'] ?? 'day';
                        $ctx['machinePage'] = (int) ($payload['machinePage'] ?? 1);
                        $ctx['machinePageSize'] = (int) ($payload['machinePageSize'] ?? 128);
                        $connection->visualScreenCtx = $ctx;
                        $snap = $svc->buildSnapshot($ctx);
                        $connection->send(json_encode(
                            VisualScreenService::wsPushPayload('visual_screen_snapshot', $snap, $traceId),
                            JSON_UNESCAPED_UNICODE
                        ));
                        break;
                    case 'visual_screen_switch_region':
                        $ctx['regionType'] = $payload['regionType'] ?? 'national';
                        $ctx['regionName'] = isset($payload['regionName']) ? (string) $payload['regionName'] : '';
                        $connection->visualScreenCtx = $ctx;
                        $snap = $svc->buildSnapshot($ctx);
                        $connection->send(json_encode(
                            VisualScreenService::wsPushPayload('visual_screen_snapshot', $snap, $traceId),
                            JSON_UNESCAPED_UNICODE
                        ));
                        break;
                    case 'visual_screen_switch_cycle':
                        $ctx['cycle'] = $payload['cycle'] ?? 'day';
                        $connection->visualScreenCtx = $ctx;
                        $trend = $svc->buildSalesTrend($ctx);
                        $connection->send(json_encode(
                            VisualScreenService::wsPushPayload('visual_screen_sales_trend_update', $trend, $traceId),
                            JSON_UNESCAPED_UNICODE
                        ));
                        break;
                    case 'ping':
                        $connection->send(json_encode(
                            VisualScreenService::wsPushPayload('pong', [
                                'ok' => 1,
                                'clientTime' => $payload['clientTime'] ?? null,
                            ], $traceId),
                            JSON_UNESCAPED_UNICODE
                        ));
                        break;
                    default:
                        $connection->send(json_encode(
                            VisualScreenService::wsPushPayload('error', [
                                'code' => 'UNSUPPORTED_EVENT',
                                'message' => 'unsupported event',
                            ], $traceId),
                            JSON_UNESCAPED_UNICODE
                        ));
                }
            } catch (\Throwable $e) {
                $connection->send(json_encode(
                    VisualScreenService::wsPushPayload('error', [
                        'code' => 'INTERNAL_ERROR',
                        'message' => $e->getMessage(),
                    ], $traceId),
                    JSON_UNESCAPED_UNICODE
                ));
            }
        };

        $output->writeln('Listening ' . $socket . ' (connect with ?token=...)');
        Worker::runAll();
    }

    /**
     * @return array<string,mixed>|null
     */
    protected static function authManagerByToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $key = Config::get('app.salt');
        $plain = TDESUtil::decrypt($token, $key);
        $tokenArr = json_decode($plain, true);
        if (!is_array($tokenArr) || !isset($tokenArr['manager_id'])) {
            return null;
        }
        if (time() - (int) ($tokenArr['timeout'] ?? 0) >= 24 * 3600 * 365) {
            return null;
        }
        $row = Db::name('auth_manager')->where('manager_id', (int) $tokenArr['manager_id'])->find();
        if (!$row) {
            return null;
        }
        return is_array($row) ? $row : $row->toArray();
    }
}
