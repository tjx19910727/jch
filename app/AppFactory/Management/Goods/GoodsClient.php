<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:56
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsClient extends ManagementClient
{
    use GoodsTrait;
    use AuthManagerTrait;

    public function getPageList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $data = $this->getGoodsList($where,$pageNum,$field,$order);
        return $this->rQ($data);
    }

    /**
     * 导入Excel
     * @param $data
     * @return array|string
     */
    public function importExcel($data)
    {
        $path = root_path() . "public" . $data['file_path'];
        $title = ["goods_name","pic","bar_code","cost_price","retail_price","sell_by_date","is_public","status","gc_id","gc_name"];
        $other = ['creator' => $this->manager['manager_id']];
        $goods = Excel::importExcel($path,$title,$other);
        $result = "";
        if ($goods) {
            $result = $this->addMoreGoods($goods);
        }
        return $this->rAction($result);
    }
}