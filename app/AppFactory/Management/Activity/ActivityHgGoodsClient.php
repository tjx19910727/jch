<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/7
 * Time: 14:05
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityHgGoodsTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\VActivity;

class ActivityHgGoodsClient extends ManagementClient
{
    use ActivityHgGoodsTrait, GoodsTrait;


    /**
     * 添加加价换购活动
     * @param $data
     * @return array|string
     */
    public function addInfo($data)
    {
        try {
            $ah_id = $data['ah_id'];
            $hgGoodsList = json2arr($data['hgGoodsList']);
            $flag = [];
            $this->startTrans();
            foreach ($hgGoodsList as $hk => $hv) {
                $hv['ah_id'] = $ah_id;
                try {
                    validate(VActivity::class)->scene("addHgGoods")->check($hv);
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    return $this->rValidate($e->getMessage());
                }
                $goods = $this->getGoodsFind(['goods_id' => $hv['goods_id']], 'goods_name,pic goods_pic,gc_id  goods_c_id, gc_name goods_c_name');
                if (!$goods) {
                    $this->rollbackTrans();
                    return $this->rValidate("查无商品信息");
                }
                $hv = array_merge($hv, $goods);
                $flag[] = $this->addActivityHgGoods($hv);
            }
            $result = flag_check($flag);
            $result ? $this->commitTrans() : $this->rollbackTrans();
            return $this->rA($result);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }
}