<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/26
 * Time: 17:31
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Goods\GoodsHitModel;

trait GoodsHitTrait
{
    public function getGoodsHitFind($where,$field = "*",$order = "")
    {
        return GoodsHitModel::getFind($where,$field,$order);
    }

    public function getGoodsHitList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = "")
    {
        return GoodsHitModel::getList($where,$pageNum,$field,$order,$eachFun,$group);
    }

    public function getGoodsHitSum($where,$sum)
    {
        return GoodsHitModel::getSum($where,$sum);
    }

    public function addGoodsHit($insert)
    {
        $data = GoodsHitModel::create($insert);
        return $data->gh_id;
    }

    public function updateGoodsHit($update,$where = [],$field = [])
    {
        return GoodsHitModel::update($update,$where,$field);
    }

    public function delGoodsHit($where)
    {
        $result = GoodsHitModel::whereDel($where);
        return $result;
    }

    /**
     * 设备上报商品点击
     * @return mixed
     */
    public function goodsHit()
    {
        $g = $this->getGoodsFind(['g_id' => $this->message['g_id']],'g_id,g_name,sku,pic,gc_id,gc_name');
        if ($g) {
            $g = $g->toArray();
            $insert = [
                "m_id" => $this->machine['m_id'],
                "machine_id" => $this->machine['machine_id'],
                "machine_name" => $this->machine['machine_name'],
                "ao_id" => $this->machine['ao_id'],
                "create_date" => strtotime(date("Y-m-d")),
            ];
            $insert = array_merge($insert,$g);
            $result = $this->addGoodsHit($insert);
        }
        actionLog($this->getLS(),'【SQL】商品点击率',"DataUpload");
        return $this->rAction($result);
    }

}