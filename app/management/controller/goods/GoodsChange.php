<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/18
 * Time: 14:07
 */

namespace app\management\controller\goods;


use app\management\controller\Common;
use app\management\validate\Goods\VGoodsChange;

class GoodsChange extends Common
{

    protected $field = "*";
    protected $validatePath = VGoodsChange::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["machine_id" => "like","machine_name" => "like","sku" => "like","g_name" => "like"]);
        return $this->app->goodsChange->getList($where,$pageNum,$this->field,'create_time desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->goodsChange->getFind($where,$this->field);
    }

    /**
     * 导出商品事件
     * @return array|\think\response\Json
     */
    public function exportGc()
    {
        try {
            $postData = input();
            $this->validate($postData, $this->validatePath . '.exportGc');
            $where = $this->getWhere($postData, false, ["machine_id" => "like", "machine_name" => "like", "sku" => "like", "g_name" => "like"]);
            return $this->app->goodsChange->exportGoodsChange($where);
        } catch (\PHPExcel_Writer_Exception $e) {
            return returnValidate($e->getMessage());
        } catch (\PHPExcel_Exception $e) {
            return returnValidate($e->getMessage());
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
    }

}