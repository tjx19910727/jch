<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 10:11
 */

namespace app\AppFactory\Management\Wx;


use app\AppFactory\Kernel\Traits\Wx\WxTemplateLogTrait;
use app\AppFactory\Management\ManagementClient;

class WxTemplateLogClient extends ManagementClient
{
    use WxTemplateLogTrait;

    public function getTemplateLogList($where, $pageNum, $field, $order)
    {
        $list = $this->getWxTemplateLogList($where, $pageNum, $field, $order, function ($row) {
            return $this->formatTemplateLogTime($row);
        });
        if ($list && method_exists($list, 'toArray')) {
            $list = $list->toArray();
        }

        // 非分页查询下，模型层不会执行 each，这里做一次兜底格式化。
        if (is_array($list) && !isset($list['data']) && $pageNum == 0) {
            foreach ($list as $k => $row) {
                if (is_array($row)) $list[$k] = $this->formatTemplateLogTime($row);
            }
        }
        return $this->rQ($list);
    }

    protected function formatTemplateLogTime($row)
    {
        if (isset($row['confirm_time'])) {
            $row['confirm_time'] = $row['confirm_time'] > 0 ? date('Y-m-d H:i:s', $row['confirm_time']) : '';
        }
        if (isset($row['create_time'])) {
            $row['create_time'] = $row['create_time'] > 0 ? date('Y-m-d H:i:s', $row['create_time']) : '';
        }
        return $row;
    }
}