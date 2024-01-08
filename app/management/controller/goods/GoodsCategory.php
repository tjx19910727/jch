<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:51
 */

namespace app\management\controller\goods;


use app\management\controller\Common;

class GoodsCategory extends Common
{
    protected $validatePath = 'app\management\validate\VGoodsCategory.';
    /**
     * 查询一个分类
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->goodsCategory->getFind($where);
    }

    /**
     * 获取列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,['gc_name' => "like"]);
        $result = $this->app->goodsCategory->getList($where, $pageNum);
        return $result;
    }

    /**
     * 添加分类
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'add');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->goodsCategory->add($postData);
        return $result;
    }

    /**
     * 修改分类
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'update');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->goodsCategory->update($postData);
        return $result;
    }

    /**
     * 删除分类
     * @return mixed
     */
    public function del()
    {
        $id = input("gc_id");
        $result = $this->app->goodsCategory->del($id);
        return $result;
    }
}