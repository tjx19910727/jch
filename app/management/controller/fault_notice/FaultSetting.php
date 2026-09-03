<?php

namespace app\management\controller\fault_notice;

use app\management\controller\Common;

/**
 * 故障通知设置后台接口。
 */
class FaultSetting extends Common
{
    public function getGlobal()
    {
        return $this->app->faultNotice->getGlobalSetting();
    }

    public function saveGlobal()
    {
        return $this->app->faultNotice->saveGlobalSetting(input());
    }

    public function getLevelStrategyList()
    {
        return $this->app->faultNotice->getLevelStrategyList();
    }

    public function addLevelStrategy()
    {
        return $this->app->faultNotice->addLevelStrategy(input());
    }

    public function updateLevelStrategy()
    {
        return $this->app->faultNotice->updateLevelStrategy(input());
    }

    public function deleteLevelStrategy()
    {
        $postData = input();
        $level = intval($postData['level'] ?? 0);
        if ($level <= 0) {
            return returnValidate('故障等级不能为空');
        }
        return $this->app->faultNotice->deleteLevelStrategy($level);
    }

    public function getOperationLog()
    {
        $postData = input();
        $pageNum = intval($postData['pageNum'] ?? 20);
        $pageNum = $pageNum > 0 ? min($pageNum, 100) : 20;
        return $this->app->faultNotice->getFaultSettingOperationLog($postData, $pageNum);
    }
}
