<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/09
 * Time: 08:50
 */

namespace app\management\controller\card;


use app\management\controller\Common;

class Logs  extends Common
{

    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->card->getCardPointsChangeLogsInfoList($where, $pageNum, $this->field, 'id desc');
    }

    public function getBalanceLogsList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->card->getCardBalanceChangeLogsList($where, $pageNum, $this->field, 'id desc');
    }

   
}