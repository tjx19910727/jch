<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/22
 * Time: 16:30
 */

namespace app\management\controller\sale;


use app\management\controller\Common;

class SaleOrdersUnclaimed extends Common
{

    protected $field = "su_id,order_id,trade_no,machine_id,machine_name,channel_code,
    g_name,sku,retail_price,is_match,is_claim,is_out,is_close,duration,deliver_pics,remark,status,transfer_time,ao_id,create_time";
    protected $validatePath = 'app\management\validate\VSaleOrdersUnclaimed.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['trade_no' => 'like','machine_id' => 'like','channel_code' => 'like','g_name' => "like"]);
        return $this->app->saleOrdersUnclaimed->getList($where,$pageNum,$this->field,'create_time desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->saleOrdersUnclaimed->getFind($where,$this->field);
    }

    public function operation()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'operation');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->saleOrdersUnclaimed->updateSuStatus($postData);
    }

    /**
     * 导出Excel表格
     * @return array|string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function export()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['trade_no' => 'like','machine_id' => 'like','channel_code' => 'like','g_name' => "like"]);
        return $this->app->saleOrdersUnclaimed->export($where);
    }
}