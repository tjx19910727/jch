<?php

namespace app\management\controller\machine;

use app\management\controller\Common;
use app\management\service\MachineTargetService;

class MachineTarget extends Common
{
    protected function requestBody(): array
    {
        $body = [];
        $rawInput = request()->getInput();
        if (is_string($rawInput) && trim($rawInput) !== '') {
            $decoded = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $body = $decoded;
            }
        }
        return $body;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    protected function pickInput(string $name, array $body, $default = null)
    {
        $all = input();
        if (array_key_exists($name, $all) && $all[$name] !== '' && $all[$name] !== null) {
            return $all[$name];
        }
        if (array_key_exists($name, $body) && $body[$name] !== '' && $body[$name] !== null) {
            return $body[$name];
        }
        return $default;
    }

    /**
     * 保存设备目标配置（新增/编辑）
     */
    public function save()
    {
        $body = $this->requestBody();
        $ctx = [
            'id' => intval($this->pickInput('id', $body, 0)),
            'm_id' => $this->pickInput('m_id', $body, ''),
            'date' => $this->pickInput('date', $body, ''),
            'price' => $this->pickInput('price', $body, 0),
        ];

        $svc = new MachineTargetService($this->app);
        $res = $svc->save($ctx);
        return returnState(intval($res['state'] ?? 100), strval($res['msg'] ?? '保存失败'), $res['data'] ?? []);
    }

    /**
     * 读取目标配置详情
     */
    public function detail()
    {
        $body = $this->requestBody();
        $id = intval($this->pickInput('id', $body, 0));
        if ($id <= 0) {
            return returnState(100, 'id不能为空', []);
        }

        $svc = new MachineTargetService($this->app);
        $res = $svc->detail($id);
        return returnState(intval($res['state'] ?? 100), strval($res['msg'] ?? '查询失败'), $res['data'] ?? []);
    }

    /**
     * 获取配置过目标值的设备下拉
     */
    public function devices()
    {
        $body = $this->requestBody();
        $ctx = [
            'date' => $this->pickInput('date', $body, date('Y-m')),
        ];

        $svc = new MachineTargetService($this->app);
        $res = $svc->devices($ctx);
        return returnState(200, '查询成功', $res);
    }

    /**
     * 目标统计
     */
    public function stats()
    {
        $body = $this->requestBody();
        $ctx = [
            'm_id' => $this->pickInput('m_id', $body, ''),
            'date' => $this->pickInput('date', $body, date('Y-m')),
        ];

        $svc = new MachineTargetService($this->app);
        $res = $svc->stats($ctx);
        return returnState(intval($res['state'] ?? 100), strval($res['msg'] ?? '查询失败'), $res['data'] ?? []);
    }
}
