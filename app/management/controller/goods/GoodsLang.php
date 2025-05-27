<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 16:16
 */

namespace app\management\controller\goods;


use app\management\controller\Common;
use app\management\validate\VGoodsLang;

class GoodsLang extends Common
{

    protected $field = "*";
    protected $validatePath = VGoodsLang::class . '.';

    /**
     * 查询商品多语言列表
     * @return array|mixed|\think\response\Json
     */
    public function getList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'getList');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->goodsLang->getList($where,$pageNum,$this->field,'gl_id desc');
    }

    /**
     * 查询一条商品多语言数据
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->goodsLang->getFind($where,$this->field);
    }

    /**
     * 添加商品多语言数据
     * @return array|\think\response\Json
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsLang->addGl($postData);
    }

    /**
     * 修改商品多语言数据
     * @return array|mixed|\think\response\Json
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsLang->update($postData);
    }

    /**
     * 删除商品多语言数据
     * @return array|mixed|\think\response\Json
     */
    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsLang->del($postData);
    }
}