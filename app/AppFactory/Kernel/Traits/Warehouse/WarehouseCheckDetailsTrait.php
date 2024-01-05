<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/21
 * Time: 8:58
 */

namespace app\AppFactory\Kernel\Traits\Warehouse;


use app\AppFactory\Kernel\Model\Warehouse\WarehouseCheckDetailsModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

trait WarehouseCheckDetailsTrait
{
    public function addWarehouseCheckDetails($insert)
    {
        $wcd = WarehouseCheckDetailsModel::create($insert);
        return $wcd->wcd_id;
    }

    /**
     *
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|\think\Collection|\think\Paginator
     */
    public function getWarehouseCheckDetailsList($where,$pageNum = 0, $field = "*", $order = "")
    {
        try {
            return WarehouseCheckDetailsModel::getList($where, $pageNum, $field, $order);
        } catch (DataNotFoundException $e) {
            return $this->rValidate($e->getMessage());
        } catch (ModelNotFoundException $e) {
            return $this->rValidate($e->getMessage());
        } catch (DbException $e) {
            return $this->rValidate($e->getMessage());
        }
    }
}