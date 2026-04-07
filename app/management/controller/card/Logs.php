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

    public function getBalanceList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->buildWhere($postData);
        return $this->app->card->getCardBalanceLogsList($where, $pageNum, $this->field, 'id desc');
    }

    protected function buildWhere($postData)
    {
        $where = $this->getWhere($postData, false, []);

        if (!empty($postData['created_at']) && is_string($postData['created_at']) && strpos($postData['created_at'], '~') !== false) {
            $range = explode('~', $postData['created_at'], 2);
            $start = trim($range[0] ?? '');
            $end = trim($range[1] ?? '');

            if ($start !== '' && $end !== '') {
                foreach ($where as $idx => $item) {
                    if (is_array($item) && ($item[0] ?? '') === 'created_at') {
                        unset($where[$idx]);
                    }
                }
                $where[] = ['created_at', 'between', [$start, $end]];
                $where = array_values($where);
            }
        }

        return $where;
    }

   
}