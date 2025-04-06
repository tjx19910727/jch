<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/19
 * Time: 10:19
 */

namespace app\management\controller\goods;


use app\management\controller\Common;
use app\management\validate\Goods\VGoodsMultiple;

class GoodsMultiple extends Common
{

    protected $field = "*";
    protected $validatePath = VGoodsMultiple::class . ".";

    /**
     * 获取组合商品列表数据
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->goodsMultiple->getGmList($where,$pageNum,$this->field,'gm_id desc');
    }

    /**
     * 获取一条组合商品数据
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->goodsMultiple->getGmFind($where,$this->field);
    }

    /**
     * 添加组合商品
     * @return array|bool|string|\think\response\Json
     */
    public function add()
    {
        $postData = input();
        $postData = json2arr($postData);
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsMultiple->addGm($postData);
    }

    /**
     * 修改组合商品数据
     * @return array|bool|string|\think\response\Json
     */
    public function update()
    {
        $postData = input();
        $postData = json2arr($postData);
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsMultiple->updateGm($postData);
    }

    /**
     * 删除组合商品数据
     * @return array|\think\response\Json
     */
    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsMultiple->delGm($postData['gm_id']);
    }
}