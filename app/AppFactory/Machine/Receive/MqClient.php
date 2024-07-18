<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/30
 * Time: 9:00
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Support\Validate\Machine\VReport;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryConfigTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickTrait;
use app\AppFactory\Kernel\Traits\Api\ApiAdvanceTrait;
use app\AppFactory\Kernel\Traits\Api\ApiCallbackTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsChangeTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsHitTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineErrorCodeTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineVersionPlanTrait;
use app\AppFactory\Kernel\Traits\Mq\OutGoodsTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;

class MqClient extends ReceiveBaseClient
{
    use SaleOrdersTrait,OutGoodsTrait;
    use MachineInfoTrait,MachineChannelTrait,MachineVersionPlanTrait;
    use MachineErrorCodeTrait;
    use GoodsTrait,GoodsHitTrait,GoodsChangeTrait;
    use ApiAdvanceTrait,ApiCallbackTrait;
    use ActivityFdUsedTrait,ActivityFdTrait,ActivityFdContentTrait;
    use ActivityCouponTrait,ActivityCouponUsedTrait;
    use ActivityPickTrait,ActivityPickCodeTrait;
    use ActivityLotteryTrait,ActivityLotteryConfigTrait,ActivityLotteryContentTrait,ActivityLotteryUsedTrait,ActivityLotteryUsedGoodsTrait;
    use EarthCitiesTrait,EarthRegionsTrait,EarthCountriesTrait,EarthStatesTrait;

    protected $message;
    protected $order;
    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        if (!isset($this->data['mac']) && !isset($this->data['sign']))
            json(['state' => 100,"msg" => '缺少签名'])->send();
        if ($this->checkSign($this->data) !== true) {
            actionLog($this->data,'验签失败',"DataUpload");
//            die(json_encode(["state" => 200, "msg" => "验签失败"],320));
        } else {
            $this->message = json2arr($this->data['data']);
            actionLog($this->message, '消息数据', "DataUpload");
            try {
                validate(VReport::class)->scene('onMessage')->check($this->data);
            } catch (\Exception $e) {
                actionLog($e->getMessage(), '数据格式错误', 'DataUpload');
//                die(json_encode(["state" => 200, "msg" => $e->getMessage(), $this->data], 320));
            }
            $this->dataRecord(2);
        }
    }

    /**
     * 处理设备上报
     * msgType: outGoods、heartbeat、updateComplete、goodsHit、transactionVideo、img、channelImg、light、volume、errorCode、
     * @return int
     */
    public function onMessage()
    {
        try {
            if ($this->message) {
                $func_name = $this->message['msgType'];
                if (method_exists(self::class, $func_name)) {
                    try {
                        validate(VReport::class)->scene($this->message['msgType'])->check($this->message);
                    } catch (\Exception $e) {
                        actionLog($e->getMessage(), '数据格式错误', 'DataUpload');
                        return 1;
                    }
                    return $this->$func_name();
                }
            }
            return 1;
        } catch (\Exception $e) {
            actionException($e,1);
            return 1;
        }
    }

}