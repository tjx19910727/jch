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
        if (isset($postData['gc_pid']) && $postData['gc_pid'] == 0) $where['gc_pid'] = 0;
        $result = $this->app->goodsCategory->getList($where, $pageNum,'*','gc_id desc');
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
        $result = $this->app->goodsCategory->addGc($postData);
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
        $postData = input();
        $result = $this->app->goodsCategory->del($postData);
        return $result;
    }
}