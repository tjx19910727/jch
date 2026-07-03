<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/3
 * Time: 14:00
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineAppSettingsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineAppSettingsClient extends ManagementClient
{
    use MachineTrait;
    use MachineAppSettingsTrait;

    protected $settingType = 1;

    public function getSettingsList($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }

        $fieldMap = $this->getMachineAppSettingsFieldMap($this->settingType);

        $rows = $this->getMachineAppSettingsList(
            ['m_id' => $machine['m_id'], 'type' => $this->settingType],
            0,
            'id,name,`key`,`value`,value_type,`desc`,updated_at',
            'id asc'
        );
        $rows = obj2arr($rows);
        $rowMap = [];
        foreach ($rows as $row) {
            $rowMap[$row['key']] = $row;
        }

        $result = [];
        foreach ($fieldMap as $key => $meta) {
            $row = $rowMap[$key] ?? [];
            $rawValue = $row['value'] ?? $meta['default'];
            $valueType = $row['value_type'] ?? $meta['value_type'];
            $result[] = [
                'id' => $row['id'] ?? 0,
                'title' => $row['name'] ?? $meta['name'],
                'key' => $key,
                'value' => $this->castValueByType($rawValue, $valueType),
                'value_type' => $valueType,
                'desc' => $row['desc'] ?? $meta['desc'],
                'updated_at' => $row['updated_at'] ?? '',
            ];
        }

        return $this->rQ($result);
    }

    public function getSettingsFind($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }
        $fieldMap = $this->getMachineAppSettingsFieldMap($this->settingType);
        $this->ensureDefaultSettings($machine, $fieldMap);

        $where = [
            'm_id' => $machine['m_id'],
            'type' => $this->settingType,
        ];
        if (!empty($postData['id'])) {
            $where['id'] = $postData['id'];
        } else {
            $key = $postData['key'] ?? '';
            if (!$key) {
                return $this->rFail('key不能为空');
            }
            $where['key'] = $key;
        }

        $row = $this->getMachineAppSettingsFind($where, 'id,name,`key`,`value`,value_type,`desc`,updated_at');
        if (!$row) {
            return $this->rQ([]);
        }

        return $this->rQ([
            'id' => $row['id'],
            'title' => $row['name'],
            'key' => $row['key'],
            'value' => $this->castValueByType($row['value'], $row['value_type'] ?: 'string'),
            'value_type' => $row['value_type'] ?: 'string',
            'desc' => $row['desc'] ?? '',
            'updated_at' => $row['updated_at'] ?? '',
        ]);
    }

    /**
     * 新增配置（支持多设备，m_id格式: 127,188,199）
     * @return array|\think\response\Json
     */
    public function addSettings($postData)
    {
        $mIds = $postData['m_id'] ?? '';
        if (empty($mIds)) {
            return $this->rFail('m_id不能为空');
        }

        $mIdArr = explode(',', $mIds);
        $fieldMap = $this->getMachineAppSettingsFieldMap($this->settingType);
        $successCount = 0;
        $skipCount = 0;

        foreach ($mIdArr as $mId) {
            $mId = trim($mId);
            if (empty($mId)) {
                continue;
            }
            $machineId = $this->getMachineValue(['m_id' => $mId], 'machine_id');
            if (!$machineId) {
                continue;
            }
            $machine = ['m_id' => $mId, 'machine_id' => $machineId];

            foreach ($fieldMap as $key => $meta) {
                $exists = $this->getMachineAppSettingsValue(
                    ['m_id' => $mId, 'type' => $this->settingType, 'key' => $key],
                    'id'
                );
                if ($exists) {
                    $skipCount++;
                    continue;
                }
                $this->addMachineAppSettings([
                    'm_id' => $mId,
                    'name' => $meta['name'],
                    'machine_id' => $machineId,
                    'type' => $this->settingType,
                    'key' => $key,
                    'value' => $meta['default'],
                    'value_type' => $meta['value_type'],
                    'desc' => $meta['desc'],
                ]);
                $successCount++;
            }
        }

        if ($successCount == 0 && $skipCount > 0) {
            return $this->rFail('所选设备已存在配置，无需重复添加');
        }
        if ($successCount == 0) {
            return $this->rFail('未找到有效设备');
        }

        return $this->r(200, '新增成功', [
            'success_count' => $successCount,
            'skip_count' => $skipCount,
        ]);
    }

    /**
     * 新增表单：返回空数据的默认字段数组
     * @return array|\think\response\Json
     */
    public function getAddForm()
    {
        $fieldMap = $this->getMachineAppSettingsFieldMap($this->settingType);
        $result = [];
        foreach ($fieldMap as $key => $meta) {
            $result[] = [
                'id' => 0,
                'title' => $meta['name'],
                'key' => $key,
                'value' => $this->castValueByType($meta['default'], $meta['value_type']),
                'value_type' => $meta['value_type'],
                'desc' => $meta['desc'],
                'updated_at' => '',
            ];
        }
        return $this->rQ($result);
    }

    public function updateSettings($postData)
    {
        if (isset($postData['data']) && is_array($postData['data'])) {
            return $this->updateSettingsBatch($postData);
        }
        return $this->updateSettingsSingle($postData);
    }

    protected function updateSettingsSingle($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }
        $fieldMap = $this->getMachineAppSettingsFieldMap($this->settingType);
        $this->ensureDefaultSettings($machine, $fieldMap);

        $row = $this->findSettingRow($machine['m_id'], $postData);
        if (!$row) {
            return $this->rFail('配置不存在，不支持新增');
        }

        if (!array_key_exists('value', $postData)) {
            return $this->rFail('value不能为空');
        }

        $key = $row['key'];
        $check = $this->validateSettingValue($key, $postData['value']);
        if ($check !== true) {
            return $this->rFail($check);
        }

        $title = $fieldMap[$key]['name'] ?? $row['name'];
        $desc = $fieldMap[$key]['desc'] ?? ($row['desc'] ?? '');
        $valueType = $fieldMap[$key]['value_type'] ?? ($row['value_type'] ?: 'string');
        $value = $this->normalizeValueForStorage($postData['value'], $valueType);

        $result = $this->updateMachineAppSettings(
            [
                'name' => $title,
                'value' => $value,
                'value_type' => $valueType,
                'desc' => $desc,
            ],
            ['id' => $row['id']],
            ['name', 'value', 'value_type', 'desc', 'manager_id']
        );

        $this->pushSettingsUpdateMq($machine, [$key]);
        return $this->rA($result);
    }

    protected function updateSettingsBatch($postData)
    {
        $machine = $this->resolveMachine($postData);
        if (!$machine) {
            return $this->rFail('设备不存在');
        }
        $fieldMap = $this->getMachineAppSettingsFieldMap($this->settingType);
        $this->ensureDefaultSettings($machine, $fieldMap);

        $list = $postData['data'] ?? [];
        if (!$list) {
            return $this->rFail('data不能为空');
        }

        $updateRows = [];
        $changedKeys = [];
        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (!array_key_exists('value', $item)) {
                return $this->rFail('value不能为空');
            }
            $row = $this->findSettingRow($machine['m_id'], $item);
            if (!$row) {
                return $this->rFail('存在配置项不存在，不支持新增');
            }
            $key = $row['key'];
            $check = $this->validateSettingValue($key, $item['value']);
            if ($check !== true) {
                return $this->rFail($check);
            }
            $valueType = $fieldMap[$key]['value_type'] ?? ($row['value_type'] ?: 'string');
            $updateRows[$key] = [
                'id' => $row['id'],
                'key' => $key,
                'name' => $fieldMap[$key]['name'] ?? $row['name'],
                'desc' => $fieldMap[$key]['desc'] ?? ($row['desc'] ?? ''),
                'value_type' => $valueType,
                'value' => $this->normalizeValueForStorage($item['value'], $valueType),
            ];
            $changedKeys[$key] = $key;
        }

        if (!$updateRows) {
            return $this->rFail('data不能为空');
        }

        $this->startTrans();
        try {
            foreach ($updateRows as $row) {
                $this->updateMachineAppSettings(
                    [
                        'name' => $row['name'],
                        'value' => $row['value'],
                        'value_type' => $row['value_type'],
                        'desc' => $row['desc'],
                    ],
                    ['id' => $row['id']],
                    ['name', 'value', 'value_type', 'desc', 'manager_id']
                );
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }

        $this->pushSettingsUpdateMq($machine, array_values($changedKeys));
        return $this->r(200, $this->lang('update_success'));
    }

    protected function findSettingRow($mId, $payload)
    {
        $where = [
            'm_id' => $mId,
            'type' => $this->settingType,
        ];
        if (!empty($payload['id'])) {
            $where['id'] = $payload['id'];
        } else {
            $key = $payload['key'] ?? '';
            if (!$key) {
                return [];
            }
            $where['key'] = $key;
        }
        return $this->getMachineAppSettingsFind($where, 'id,`key`,name,`value`,value_type,`desc`');
    }

    protected function resolveMachine($postData)
    {
        $mId = $postData['m_id'] ?? 0;
        $machineId = $postData['machine_id'] ?? '';

        if (!$mId && $machineId) {
            $mId = $this->getMachineValue(['machine_id' => $machineId], 'm_id');
        }
        if (!$machineId && $mId) {
            $machineId = $this->getMachineValue(['m_id' => $mId], 'machine_id');
        }
        if (!$mId || !$machineId) {
            return [];
        }
        return ['m_id' => $mId, 'machine_id' => $machineId];
    }

    protected function ensureDefaultSettings($machine, $fieldMap)
    {
        foreach ($fieldMap as $key => $meta) {
            $exists = $this->getMachineAppSettingsValue(
                ['m_id' => $machine['m_id'], 'type' => $this->settingType, 'key' => $key],
                'id'
            );
            if ($exists) {
                continue;
            }
            $this->addMachineAppSettings([
                'm_id' => $machine['m_id'],
                'name' => $meta['name'],
                'machine_id' => $machine['machine_id'],
                'type' => $this->settingType,
                'key' => $key,
                'value' => $meta['default'],
                'value_type' => $meta['value_type'],
                'desc' => $meta['desc'],
            ]);
        }
    }

    protected function normalizeValueForStorage($value, $valueType)
    {
        if ($value === null) {
            return '';
        }
        if ($valueType === 'int') {
            return is_numeric($value) ? (string)($value + 0) : (string)$value;
        }
        return (string)$value;
    }

    protected function castValueByType($value, $valueType)
    {
        if ($valueType === 'int') {
            return is_numeric($value) ? $value + 0 : 0;
        }
        return (string)$value;
    }

    protected function validateSettingValue($key, $value)
    {
        $twoStateKeys = [
            'home_anim_enabled',
            'purchase_voice_enabled',
            'dispense_voice_enabled',
            'pickup_voice_enabled',
            'pay_voice_enabled',
            'ad_show_goods_enabled',
        ];
        if (in_array($key, $twoStateKeys, true)) {
            if (!in_array((string)$value, ['1', '2'], true)) {
                return $key . ' 仅支持1或2';
            }
            return true;
        }

        if ($key === 'home_anim_style' || $key === 'ad_goods_jump_target') {
            if (!in_array((string)$value, ['1', '2'], true)) {
                return $key . ' 仅支持1或2';
            }
            return true;
        }

        if ($key === 'home_anim_volume') {
            if (!is_numeric($value)) {
                return 'home_anim_volume 必须为数字';
            }
            $num = $value + 0;
            if ($num < 1 || $num > 100) {
                return 'home_anim_volume 范围为1-100';
            }
            return true;
        }

        return true;
    }

    protected function pushSettingsUpdateMq($machine, $changedKeys)
    {
        $payload = [
            'type' => $this->settingType,
            'event' => 'app_settings_changed',
            'm_id' => $machine['m_id'],
            'machine_id' => $machine['machine_id'],
            'changed_keys' => $changedKeys,
        ];
        $this->sendToMachine($machine, 'appSettingsUpdate', $payload);
    }

    /**
     * 讯飞文字转语音 (v2 WebSocket)
     * @param array $postData ['country_code' => 'CN', 'text' => '你好']
     * @return array
     */
    public function textToSpeech($postData)
    {
        $countryCode = $postData['country_code'] ?? '';
        $text = $postData['text'] ?? '';

        if (empty($text)) {
            return $this->rFail('文字内容不能为空');
        }

        // 讯飞TTS v2 配置
        $appId = '3c8e908e';
        $apiKey = '03b048cfd2a75652e40b46486c4e78f2';
        $apiSecret = 'YmI3NTA5YjMzMTJkYzcyNmU1ODI4OWE5';
        // $appId = 'b321285c';
        // $apiKey = '5aac878f27b77cef8cb5aafecadffa36';
        // $apiSecret = 'YmNjNzU2OGFjMGFiZjMzOGRjYjQ5ZWY1';

        $voiceName = $this->mapCountryCodeToVoice($countryCode);

        // 1. 构建鉴权URL
        $host = 'tts-api.xfyun.cn';
        $path = '/v2/tts';
        $date = gmdate('D, d M Y H:i:s') . ' GMT';

        $signatureOrigin = "host: {$host}\ndate: {$date}\nGET {$path} HTTP/1.1";
        $signature = base64_encode(hash_hmac('sha256', $signatureOrigin, $apiSecret, true));

        $authOrigin = "api_key=\"{$apiKey}\", algorithm=\"hmac-sha256\", headers=\"host date request-line\", signature=\"{$signature}\"";
        $authorization = base64_encode($authOrigin);

        $wsUrl = "{$path}?" . http_build_query([
            'authorization' => $authorization,
            'date' => $date,
            'host' => $host,
        ]);

        actionLog(['wsUrl' => $wsUrl, 'voiceName' => $voiceName], '讯飞TTS v2 连接参数', 'XUNFEI_TTS');

        // 2. 建立 WebSocket 连接
        $socket = $this->connectWebSocket($host, $wsUrl);
        if (!$socket) {
            return $this->rFail('讯飞WebSocket连接失败');
        }

        // 3. 发送合成请求（对标Java官方demo：aue=raw PCM流式，全部chunk拼起来）
        $request = [
            'common' => ['app_id' => $appId],
            'business' => [
                'aue' => 'raw',
                'auf' => 'audio/L16;rate=16000',
                'vcn' => $voiceName,
                'speed' => 50,
                'volume' => 50,
                'pitch' => 50,
                'tte' => 'UTF8',
                'ent' => 'intp65',
            ],
            'data' => [
                'status' => 2,
                'text' => base64_encode($text),
            ],
        ];

        $this->sendWebSocketFrame($socket, json_encode($request));
        actionLog($request, '讯飞TTS v2 发送数据', 'XUNFEI_TTS');

        // 4. 接收数据（v2 服务端所有帧都是TextMessage，音频base64编码在 data.audio 中）
        //    服务端可能将一个消息分多个WebSocket帧返回，通过getMessageFrame自动重组
        $audioB64 = '';
        $finalStatus = null;

        while (true) {
            $message = $this->getMessageFrame($socket);
            if ($message === false || $message === null) {
                break;
            }

            $opcode = $message['opcode'];
            $payload = $message['payload'];

            if ($opcode === 0x08) {
                break;
            }

            if ($opcode === 0x01) {
                $msg = json_decode($payload, true);
                actionLog($msg, '讯飞TTS v2 响应', 'XUNFEI_TTS');
                if ($msg === null) {
                    continue;
                }
                $code = $msg['code'] ?? null;
                if ($code !== null && $code !== 0) {
                    $errMsg = $msg['message'] ?? '未知错误';
                    $this->closeWebSocket($socket);
                    return $this->rFail('讯飞TTS错误: ' . $errMsg);
                }
                // 对标Java demo：append所有帧的audio（raw PCM流式）
                if (!empty($msg['data']['audio'])) {
                    $chunkLen = strlen($msg['data']['audio']);
                    $dataStatus = $msg['data']['status'] ?? 0;
                    actionLog("音频chunk长度={$chunkLen}, status={$dataStatus}, ced=" . ($msg['data']['ced'] ?? '?'), '讯飞TTS v2 音频片段', 'XUNFEI_TTS');
                    $audioB64 .= $msg['data']['audio'];
                }
                $finalStatus = $msg;
            }
        }

        $this->closeWebSocket($socket);

        // 检查是否成功
        $fsCode = $finalStatus['code'] ?? -1;
        if ($fsCode !== 0) {
            $errMsg = $finalStatus['message'] ?? '未收到响应';
            return $this->rFail('讯飞TTS错误: ' . $errMsg);
        }

        if (empty($audioB64)) {
            return $this->rFail('讯飞TTS未返回音频数据');
        }

        // base64解码得到PCM音频二进制，加上WAV头变成可播放的wav文件
        $pcmData = base64_decode($audioB64);
        if ($pcmData === false || empty($pcmData)) {
            return $this->rFail('讯飞TTS音频解码失败');
        }

        // 构建WAV头（16kHz, 16bit, mono）
        $sampleRate = 16000;
        $bitsPerSample = 16;
        $channels = 1;
        $byteRate = $sampleRate * $channels * ($bitsPerSample / 8);
        $blockAlign = $channels * ($bitsPerSample / 8);
        $dataSize = strlen($pcmData);
        $headerSize = 44;
        $fileSize = $headerSize + $dataSize;

        $wavHeader = pack('A4VA4A4VvvVVvvA4V',
            'RIFF', $fileSize - 8, 'WAVE',
            'fmt ', 16, 1, $channels, $sampleRate, $byteRate, $blockAlign, $bitsPerSample,
            'data', $dataSize
        );

        $audioData = $wavHeader . $pcmData;

        // 5. 保存音频文件
        $saveDir = 'uploads' . DIRECTORY_SEPARATOR . 'tts';
        $fileName = md5($text . $voiceName . time()) . '.wav';
        $relativePath = 'uploads/tts/' . date('Ymd') . '/' . $fileName;
        $absolutePath = public_path() . $saveDir . DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR . $fileName;

        $dir = dirname($absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($absolutePath, $audioData);
        actionLog(['fileSize' => strlen($audioData), 'text' => $text, 'audioB64Len' => strlen($audioB64)], '讯飞TTS v2 保存文件', 'XUNFEI_TTS');

        $fileUrl = $this->host . '/' . $relativePath;

        return $this->r(200, '语音合成成功', [
            'file_url' => $fileUrl,
            'file_name' => $fileName,
            'voice_name' => $voiceName,
        ]);
    }

    /**
     * 建立 WSS 连接
     */
    private function connectWebSocket($host, $pathWithQuery)
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $errno = 0;
        $errstr = '';
        $socket = stream_socket_client(
            "ssl://{$host}:443",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            actionLog("errno={$errno} errstr={$errstr}", 'WebSocket连接失败', 'XUNFEI_TTS');
            return false;
        }

        stream_set_timeout($socket, 30);

        // WebSocket 握手
        $key = base64_encode(random_bytes(16));
        $handshake = "GET {$pathWithQuery} HTTP/1.1\r\n"
            . "Host: {$host}\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Key: {$key}\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "\r\n";

        fwrite($socket, $handshake);

        $response = '';
        while (true) {
            $line = fgets($socket, 2048);
            if ($line === false || $line === "\r\n") {
                break;
            }
            $response .= $line;
        }

        if (strpos($response, '101') === false) {
            actionLog($response, 'WebSocket握手失败', 'XUNFEI_TTS');
            fclose($socket);
            return false;
        }

        actionLog($response, 'WebSocket握手成功', 'XUNFEI_TTS');
        return $socket;
    }

    /**
     * 发送 WebSocket 文本帧
     */
    private function sendWebSocketFrame($socket, $data)
    {
        $len = strlen($data);
        $frame = chr(0x81); // FIN + text opcode

        // MASK=1，客户端发送必须掩码
        if ($len <= 125) {
            $frame .= chr(0x80 | $len);
        } elseif ($len <= 65535) {
            $frame .= chr(0x80 | 126) . pack('n', $len);
        } else {
            $frame .= chr(0x80 | 127) . pack('J', $len);
        }

        $mask = random_bytes(4);
        $frame .= $mask;

        for ($i = 0; $i < $len; $i++) {
            $frame .= chr(ord($data[$i]) ^ ord($mask[$i % 4]));
        }

        fwrite($socket, $frame);
    }

    /**
     * 接收 WebSocket 帧
     */
    private function recvWebSocketFrame($socket)
    {
        $header = $this->recvAll($socket, 2);
        if ($header === false || strlen($header) < 2) {
            return null;
        }

        $firstByte = ord($header[0]);
        $fin = ($firstByte & 0x80) !== 0;
        $opcode = $firstByte & 0x0F;
        $secondByte = ord($header[1]);
        $masked = ($secondByte & 0x80) !== 0;
        $payloadLen = $secondByte & 0x7F;

        if ($payloadLen === 126) {
            $ext = $this->recvAll($socket, 2);
            if ($ext === false) return null;
            $payloadLen = unpack('n', $ext)[1];
        } elseif ($payloadLen === 127) {
            $ext = $this->recvAll($socket, 8);
            if ($ext === false) return null;
            $payloadLen = unpack('J', $ext)[1];
        }

        $maskKey = '';
        if ($masked) {
            $maskKey = $this->recvAll($socket, 4);
            if ($maskKey === false) return null;
        }

        $payload = $this->recvAll($socket, $payloadLen);
        if ($payload === false) return null;

        if ($masked && $maskKey) {
            for ($i = 0; $i < strlen($payload); $i++) {
                $payload[$i] = chr(ord($payload[$i]) ^ ord($maskKey[$i % 4]));
            }
        }

        return ['fin' => $fin, 'opcode' => $opcode, 'payload' => $payload];
    }

    /**
     * 获取完整消息帧（自动处理WebSocket分帧重组）
     */
    private function getMessageFrame($socket)
    {
        $messageOpcode = null;
        $messagePayload = '';

        while (true) {
            $frame = $this->recvWebSocketFrame($socket);
            if ($frame === false || $frame === null) {
                return $frame;
            }

            // 控制帧直接返回（ping/pong/close）
            if ($frame['opcode'] >= 0x08) {
                return ['opcode' => $frame['opcode'], 'payload' => $frame['payload']];
            }

            // 首帧记录opcode
            if ($frame['opcode'] !== 0x00) {
                $messageOpcode = $frame['opcode'];
            }

            $messagePayload .= $frame['payload'];

            if ($frame['fin']) {
                break;
            }
        }

        return ['opcode' => $messageOpcode, 'payload' => $messagePayload];
    }

    /**
     * 可靠读取指定长度字节
     */
    private function recvAll($socket, $length)
    {
        $data = '';
        $remaining = $length;
        while ($remaining > 0) {
            $chunk = @fread($socket, $remaining);
            if ($chunk === false || $chunk === '') {
                $info = stream_get_meta_data($socket);
                if ($info['timed_out']) {
                    return false;
                }
                // 非超时，再试一次
                usleep(10000);
                continue;
            }
            $data .= $chunk;
            $remaining -= strlen($chunk);
        }
        return $data;
    }

    /**
     * 关闭 WebSocket 连接
     */
    private function closeWebSocket($socket)
    {
        if (is_resource($socket)) {
            $frame = chr(0x88) . chr(0x80);
            $mask = random_bytes(4);
            fwrite($socket, $frame . $mask . $mask);
            fclose($socket);
        }
    }

    /**
     * 国家编码映射讯飞发音人
     * @param string $countryCode
     * @return string
     */
    protected function mapCountryCodeToVoice($countryCode)
    {
        // v2 发音人格式为 x4_xxx，需先在控制台「语音合成」添加试用发音人
        $map = [
            'CN' => 'x4_xiaoyan',     // 中国大陆 - 普通话
            'HK' => 'x3_xiaoyue',    // 香港 - 粤语
            'TW' => 'x4_xiaofeng',    // 台湾 - 台湾普通话
            'MO' => 'x4_xiaoqing',    // 澳门 - 粤语
            'US' => 'x4_enus_luna_assist',   // 美国 - 英文
            // 'GB' => 'x4_catherine',   // 英国 - 英文
            // 'AU' => 'x4_catherine',   // 澳大利亚 - 英文
            // 'CA' => 'x4_catherine',   // 加拿大 - 英文
            // 'JP' => 'x4_xiaoyan',     // 日本
            // 'KR' => 'x4_xiaoyan',     // 韩国
        ];

        $code = strtoupper($countryCode);
        return $map[$code] ?? 'x4_xiaoyan';
    }
}
