<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/19
 * Time: 18:24
 */

namespace app\AppFactory\Kernel\Traits\PaymentCharge;


use tonglian\ApiWeb\Application;
use tonglian\TlFactory;

trait TlChargeTrait
{

    /**
     * @var Application
     */
    protected $tlApp;
    protected $config;

    protected $tlPaymentMethod = [
        "31" => "chargeUnitOrderPay",
//        "32" => "",
    ];
    /**
     * 通联支付
     * @return mixed
     */
    public function tlChargePay()
    {
        if (!in_array($this->storeCharge['payment_method'],array_keys($this->tlPaymentMethod)))
            return $this->rFail("支付方式不在允许范围内");
        $manager = $this->getAuthManagerFind(['pid' => 0,'status' => 1],'manager_id','manager_id desc');
        $this->config = $this->getStrategyPayeeContentByCreator($manager['manager_id'],$this->storeCharge['payment_type']);
        if (!is_array($this->config)) return $this->config;
        $this->storeCharge['sp_id'] = $this->config['sp_id'];
        $this->tlApp = TlFactory::apiWeb($this->config);
        $func_name = $this->tlPaymentMethod[$this->storeCharge['payment_method']];
        return $this->$func_name();
    }

    /**
     * 通联支付——门店收费支付链接
     * @param $config
     * @param string $payType
     * @return array|string
     * @throws \tonglian\Kernel\Exceptions\Exception
     */
    public function chargeUnitOrderPay()
    {
        $params = [
            'cusid' => $this->config['mch_id'],
            'appid' => $this->config['app_id'],
            'version' => '11',
            'trxamt' => bcmul($this->storeCharge['fee_amount'],100),
            'reqsn' => $this->storeCharge['trade_no'],
            'paytype' => $this->payType,
            'randomstr' => substr($this->storeCharge['trade_no'],14,6),
            'validtime' => '5',
            'notify_url' => $this->getUrl('/http/pay.tl/chargeNotify'),
            'signtype' => "RSA",
            'remark' => $this->storeCharge['store_name'] . "【" .date("YmdHis"). "】收费支付",
        ];
        actionLog($params,'请求扫码支付参数');
        $result = $this->tlApp->unitOrder->pay($params);
        $result = json2arr($result);
        actionLog($result,'请求扫码支付结果');
        if ($result['retcode'] == "SUCCESS") {
            if ($result['trxstatus'] == "0000") {
                $this->storeCharge['id'] = $this->addStoreCharge($this->storeCharge);
                if (!$this->storeCharge['id']) return $this->rFail("生成收费记录失败");
                return $this->r(200,'请求支付成功',['scanQr' => $result['payinfo'],"result" => $result,"storeCharge" => $this->storeCharge]);
            }
            return $this->rFail('请求支付失败：'. $result['errmsg']);
        }
        return $this->rFail('请求支付失败：'. $result['retmsg']);
    }

}