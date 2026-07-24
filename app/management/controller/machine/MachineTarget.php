<?php

namespace app\management\controller\machine;

use app\management\controller\Common;
use app\management\service\MachineTargetService;

class MachineTarget extends Common
{
    /**
     * 目标配置列表（用于编辑入口）
     */
    public function configList()
    {
        $postData = input();
        $ctx = [
            'm_id' => $postData['m_id'] ?? '',
            'date' => $postData['date'] ?? '',
            'page' => $postData['page'] ?? 1,
            'page_size' => $postData['page_size'] ?? 20,
            'auth_where' => $this->getWhere([]),
        ];

        $svc = new MachineTargetService($this->app);
        $res = $svc->configList($ctx);
        return returnState(intval($res['state'] ?? 100), strval($res['msg'] ?? '查询失败'), $res['data'] ?? []);
    }

    /**
     * 新增设备目标配置
     */
    public function add()
    {
        $postData = input();
        $ctx = [
            'm_id' => $postData['m_id'] ?? '',
            'date' => $postData['date'] ?? '',
            'price' => $postData['price'] ?? 0,
            'target_amount' => $postData['target_amount'] ?? 0,
            'auth_where' => $this->getWhere([]),
        ];

        $svc = new MachineTargetService($this->app);
        $res = $svc->add($ctx);
        return returnState(intval($res['state'] ?? 100), strval($res['msg'] ?? '保存失败'), $res['data'] ?? []);
    }

    /**
     * 修改设备目标配置
     */
    public function update()
    {
        $postData = input();
        $ctx = [
            'id' => $postData['id'] ?? 0,
            'm_id' => $postData['m_id'] ?? '',
            'date' => $postData['date'] ?? '',
            'price' => $postData['price'] ?? 0,
            'target_amount' => $postData['target_amount'] ?? 0,
            'auth_where' => $this->getWhere([]),
        ];

        $svc = new MachineTargetService($this->app);
        $res = $svc->update($ctx);
        return returnState(intval($res['state'] ?? 100), strval($res['msg'] ?? '保存失败'), $res['data'] ?? []);
    }


    /**
     * 读取目标配置详情
     */
    public function detail()
    {
        $postData = input();
        $mId = intval($postData['m_id'] ?? 0);
        if ($mId <= 0) {
            return returnState(100, 'm_id不能为空', []);
        }

        $svc = new MachineTargetService($this->app);
        $res = $svc->detail($mId, $this->getWhere([]));
        return returnState(intval($res['state'] ?? 100), strval($res['msg'] ?? '查询失败'), $res['data'] ?? []);
    }

    /**
     * 获取配置过目标值的设备下拉
     */
    public function devices()
    {
        $postData = input();
        $ctx = [
            'date' => $postData['date'] ?? date('Y-m'),
            'auth_where' => $this->getWhere([]),
        ];

        $svc = new MachineTargetService($this->app);
        $res = $svc->devices($ctx);
        return returnState(200, '查询成功', $res);
    }

    /**
     * 目标统计汇总
     */
    public function stats()
    {
        $postData = input();
        $ctx = [
            'm_id' => $postData['m_id'] ?? '',
            'date' => $postData['date'] ?? date('Y-m'),
            'auth_where' => $this->getWhere([]),
        ];

        $svc = new MachineTargetService($this->app);
        $res = $svc->statsSummary($ctx);
        return returnState(intval($res['state'] ?? 100), strval($res['msg'] ?? '查询失败'), $res['data'] ?? []);
    }

    /**
     * 目标统计列表
     */
    public function statsList()
    {
        $postData = input();
        $ctx = [
            'm_id' => $postData['m_id'] ?? '',
            'date' => $postData['date'] ?? date('Y-m'),
            'auth_where' => $this->getWhere([]),
        ];

        $svc = new MachineTargetService($this->app);
        $res = $svc->statsList($ctx);
        return returnState(intval($res['state'] ?? 100), strval($res['msg'] ?? '查询失败'), $res['data'] ?? []);
    }

    /**
     * 导出目标统计列表（筛选条件与列表一致）
     */
    public function exportStatsList()
    {
        $postData = input();
        $ctx = [
            'm_id' => $postData['m_id'] ?? '',
            'date' => $postData['date'] ?? date('Y-m'),
            'auth_where' => $this->getWhere([]),
        ];

        $svc = new MachineTargetService($this->app);
        $res = $svc->exportStatsList($ctx);
        return returnState(intval($res['state'] ?? 100), strval($res['msg'] ?? '查询失败'), $res['data'] ?? []);
    }
}
