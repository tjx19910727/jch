<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:45
 */

namespace app\management\controller\goods;


use app\management\controller\Common;

class Goods extends Common
{
    protected $validatePath = 'app\management\validate\VGoods.';
    /**
     * 查询一条商品列表
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $field = "goods_id,goods_name,pic,bar_code,cost_price,retail_price,sell_by_date,is_public,status,gc_id,gc_name,creator";
        return $this->app->goods->getFind($where,$field);
    }

    /**
     * 获取商品列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,['goods_name' => "like"]);
        $where['creator'] = $this->manager['manager_id'];
        $field = "goods_id,goods_name,pic,bar_code,cost_price,retail_price,sell_by_date,is_public,status,gc_id,gc_name,creator";
        $result = $this->app->goods->getList($where,$pageNum,$field,'goods_id desc');
        return $result;
    }

    /**
     * 获取公用商品库
     * @return mixed
     */
    public function getPublic()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData);
        $where['is_public'] = 1;
        $field = "goods_id,goods_name,pic,bar_code,cost_price,retail_price,sell_by_date,is_public,status,gc_id,gc_name,creator";
        $result = $this->app->goods->getList($where,$pageNum,$field,'goods_id desc');
        return $result;
    }

    /**
     * 添加商品
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'add');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->goods->add($postData);
        return $result;
    }

    /**
     * 修改商品
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'update');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->goods->update($postData);
        return $result;
    }

    /**
     * 删除商品
     * @return mixed
     */
    public function del()
    {
        $id = input("goods_id");
        $result = $this->app->goods->del($id);
        return $result;
    }

    /**
     * 导入商品Excel
     * @return array|string
     */
    public function importExcel()
    {
        $postData = input();
//        $postData['file_path'] = "/uploads/excel/20231014.xlsx";
        return $this->app->goods->importExcel($postData);
    }
}