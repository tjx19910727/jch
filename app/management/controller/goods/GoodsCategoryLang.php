<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 9:11
 */

namespace app\management\controller\goods;


use app\management\controller\Common;

class GoodsCategoryLang extends Common
{

    protected $field = "gcl_id,gc_id,gc_name,desc,lang";
    protected $validatePath = 'app\management\validate\VGoodsCategoryLang.';

    /**
     * 获取商品分类多语言列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["gc_name" => "like"]);
        return $this->app->goodsCategoryLang->getList($where,$pageNum,$this->field,'gc_id desc');
    }

    /**
     * 获取商品分类信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->goodsCategoryLang->getFind($where,$this->field,'gc_id desc');
    }

    /**
     * 添加商品分类附加多语言信息
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsCategoryLang->add($postData);
    }

    /**
     * 修改商品分类附加多语言信息
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsCategoryLang->update($postData);
    }

    /**
     * 删除商品分类多语言信息
     * @return array|mixed|string
     */
    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsCategoryLang->del($postData);
    }
}