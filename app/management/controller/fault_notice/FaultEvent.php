<?php

namespace app\management\controller\fault_notice;

use app\management\controller\Common;

/**
 * 故障事件列表后台接口。
 */
class FaultEvent extends Common
{
    /**
     * 请求地址：/management/fault_notice.fault_event/getList
     */
    public function getList()
    {
        $params = input();
        $pageNum = intval($params['pageNum'] ?? 20);
        $pageNum = $pageNum > 0 ? min($pageNum, 100) : 20;
        return $this->app->faultNotice->getEventList($params, $pageNum);
    }

    /**
     * 请求地址：/management/fault_notice.fault_event/export
     */
    public function export()
    {
        return $this->app->faultNotice->exportFaultEvent(input());
    }

    /**
     * 请求地址：/management/fault_notice.fault_event/getDetail
     * 基本信息与事件详情扁平返回，remark直接位于data第一层。
     */
    public function getDetail()
    {
        $postData = input();
        $meId = intval($postData['me_id'] ?? ($postData['event_id'] ?? 0));
        if ($meId <= 0) {
            return returnValidate('事件ID不能为空');
        }
        return $this->app->faultNotice->getEventDetail($meId);
    }

    /**
     * 请求地址：/management/fault_notice.fault_event/getNoticeList
     */
    public function getNoticeList()
    {
        $postData = input();
        $meId = intval($postData['me_id'] ?? ($postData['event_id'] ?? 0));
        if ($meId <= 0) {
            return returnValidate('事件ID不能为空');
        }
        $pageNum = intval($postData['pageNum'] ?? 20);
        $pageNum = $pageNum > 0 ? min($pageNum, 100) : 20;
        return $this->app->faultNotice->getEventNoticeList($meId, $pageNum);
    }
}
