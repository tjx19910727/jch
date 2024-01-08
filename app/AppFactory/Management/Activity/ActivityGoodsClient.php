<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/9
 * Time: 14:12
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Store\StoreShelvesTrait;
use app\AppFactory\Kernel\Traits\Store\StoreTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\VActivity;

class ActivityGoodsClient extends ManagementClient
{
    use ActivityGoodsTrait;
    use StoreShelvesTrait;
    use StoreTrait;

    public function addInfo($data)
    {
        $this->startTrans();
        foreach ($data as $gk => $gv) {
            $gv['a_id'] = $data['a_id'];
            $gv['a_type'] = $data['a_type'];
            try {
                validate(VActivity::class)->scene("addGoods")->check($gv);
            } catch (\Exception $e) {
                $this->rollbackTrans();
                return $this->rValidate($e->getMessage());
            }
            $gv = array_merge($gv,$this->getStoreShelvesFind(['ss_id' => $gv['ss_id']],"ss_id,shelves_number,wg_id,goods_id,goods_name,goods_pic,goods_c_id,goods_c_name")->toArray());
            $flag[] = $this->addActivityGoods($gv);
        }
        $result = flag_check($flag);
        return $this->checkTrans($result);
    }
}