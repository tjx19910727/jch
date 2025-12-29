<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/1
 * Time: 11:47
 */

namespace app\management\controller\goods;


use app\management\controller\Common;

class GoodsCorner extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\VGoodsCorner.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["corner_name" => "like"]);
        $where['ao_id'] = $this->manager['ao_id'];
        return $this->app->goodsCorner->getCornerAgAmList($where,$pageNum,$this->field,'id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->goodsCorner->getCornerAgAmFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsCorner->addCorner($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsCorner->updateCorner($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->goodsCorner->delCorner($postData['id']);
    }

    /**
     * 商品角标主动下架
     * @return array|bool|string
     */
    public function takeDown()
    {
        $id = input("id");
        strpos($id,',') !== false ? $where[] = ['id',"in",$id] : $where['id'] = $id;
        return $this->app->goodsCorner->cornerTakeDown($where);
    }
}