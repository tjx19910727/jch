<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 15:42
 */

namespace app\AppFactory\Management\RemoteActionLog;


use app\AppFactory\Kernel\Traits\RemoteActionLog\RemoteActionLogTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class RemoteActionLogClient extends ManagementClient
{
    use RemoteActionLogTrait;

    public function getRemoteActionLogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getRALogsList($where, $pageNum, $field, $order, $eachFun, $group));
    }
    
    public function addRemoteActionLog($postData)
    {
        return $this->rA($this->addRALog($postData));
    }

    public function updateRemoteActionLog($update, $where = [], $field = [])
    {
        return $this->rU($this->updateRALog($update, $where, $field));
    }

    public function delRemoteActionLog($where)
    {
        return $this->rD($this->delRALog($where));
    }

    /**
     * 获取远程出货记录。
     *
     * @param array $where
     * @param int $pageNum
     * @return array|\think\response\Json
     */
    public function getRemoteOutGoodsList($where, $pageNum = 0)
    {
        $query = $this->buildRemoteOutGoodsQuery($where)
            ->field($this->getRemoteOutGoodsFields())
            ->order('ral.id desc');

        if ($pageNum) {
            $list = $query->paginate($pageNum, false, ['query' => request()->param()]);
            $list = $list->each(function ($item) {
                return $this->formatRemoteOutGoodsItem($item);
            });
        } else {
            $list = $query->select();
            $list = $list->each(function ($item) {
                return $this->formatRemoteOutGoodsItem($item);
            });
        }

        return $this->rQ($list);
    }

    /**
     * 导出远程出货记录。
     *
     * @param array $where
     * @return array|\think\response\Json
     */
    public function exportRemoteOutGoodsList($where)
    {
        $list = $this->buildRemoteOutGoodsQuery($where)
            ->field($this->getRemoteOutGoodsFields())
            ->order('ral.id desc')
            ->select()
            ->toArray();
        if (!$list) {
            return $this->rNoData();
        }

        foreach ($list as &$item) {
            $item = $this->formatRemoteOutGoodsItem($item);
        }
        unset($item);

        $title = [
            'id' => '记录ID',
            'm_id' => '设备ID',
            'machine_id' => '设备编号',
            'machine_name' => '设备名称',
            'trade_no' => '订单号',
            'sod_id' => '子订单编号',
            'type_name' => '操作类型',
            'channel_code' => '货道编号',
            'status_name' => '状态',
            'operator_at' => '操作时间',
            'manager_id' => '操作人ID',
        ];
        $filename = '远程出货记录-' . date('YmdHis');

        return $this->sendToExport('设备管理-远程出货记录', $filename, $title, $list);
    }

    /**
     * 列表和导出共用基础查询，并固定只查询远程出货、继续出货两类记录。
     *
     * @param array $where
     * @return \think\db\Query
     */
    protected function buildRemoteOutGoodsQuery($where)
    {
        return Db::name('remote_action_log')->alias('ral')
            ->leftJoin('machine m', 'm.machine_id = ral.machine_id')
            ->leftJoin('sale_orders so', 'so.order_id = ral.order_id')
            ->where($where)
            ->whereIn('ral.type', ['remoteOutGoods', 'continueOutGoods']);
    }

    /**
     * @return string
     */
    protected function getRemoteOutGoodsFields()
    {
        return 'ral.id,ral.machine_id,m.m_id,m.machine_name,ral.type,ral.msgType,
            ral.order_id,so.trade_no,ral.sod_id,ral.goods_id,ral.channel_code,
            ral.status,ral.operator_at,ral.manager_id,ral.field';
    }

    /**
     * @param array|\think\Model $item
     * @return array|\think\Model
     */
    protected function formatRemoteOutGoodsItem($item)
    {
        $typeMap = [
            'remoteOutGoods' => '远程出货',
            'continueOutGoods' => '继续出货',
        ];
        $statusMap = [
            1 => '已发送',
            2 => '设备已接收',
            3 => '操作成功',
            4 => '操作失败',
        ];

        $type = $item['type'] ?? '';
        $status = intval($item['status'] ?? 0);
        $item['type_name'] = $typeMap[$type] ?? $type;
        $item['status_name'] = $statusMap[$status] ?? ('未知状态' . $status);
        if (!empty($item['field'])) {
            $item['field'] = checkStrDomain($item['field']);
        }

        return $item;
    }
}
