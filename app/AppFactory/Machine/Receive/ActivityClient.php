<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/5
 * Time: 16:11
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;

class ActivityClient extends ReceiveBaseClient
{
    use ActivityCouponTrait,ActivityCouponUsedTrait;
    use ActivityMachineTrait,ActivityGoodsTrait;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
    }

    /**
     * 获取适用设备的优惠券活动列表
     * @return array|string
     */
    public function getByMachine()
    {
        return $this->rQ($this->getAcByMachine());
    }

    /**
     * 使用优惠券码获取优惠券信息
     * @return array|string
     */
    public function getByCode()
    {
        $ac = $this->getAcByCode();
        if (is_string($ac)) {
            return $this->rFail($ac);
        }
        return $this->r(200,$this->lang("query_success"), ['ac' => $ac]);
    }
}