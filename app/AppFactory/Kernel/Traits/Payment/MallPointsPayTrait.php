<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/11
 * Time: 14:25
 */

namespace app\AppFactory\Kernel\Traits\Payment;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;

trait MallPointsPayTrait
{
    use AfterOrderPaymentTrait;

    public $m_AppID = '';
    public $m_PublicKey = '';
    public $m_PrivateKey = '';
    public $reduce_points_url = '';
    public $increase_points_url = '';
    public $query_points_event_url = '';
   
    public $mall;
    public $machine;
    public $mallMachine;

    
    public function initMallPointsPay()
    {
        $this->m_AppID = $this->strategyPayee['app_id'] ?? '';
        $this->m_PublicKey = $this->strategyPayee['publicKey'] ?? '';
        $this->m_PrivateKey = $this->strategyPayee['privateKey'] ?? '';
        $this->reduce_points_url = "https://openapi10.mallcoo.cn/User/Score/v1/Subtract/ByCardNo/";//扣减积分接口
        $this->increase_points_url = "https://openapi10.mallcoo.cn/User/Score/v1/Plus/ByCardNo/";//增加积分接口
        $this->query_points_event_url = "https://openapi10.mallcoo.cn/User/Score/v1/Check/ByTransID";//查询积分变动是否成功
        
        $this->mallMachine= $this->getMallMachineFind(['status' => 1, 'machine_id' => $this->order['machine_id']]);
        if(!$this->mallMachine){
            return $this->rFail($this->lang("VOrderPay.mall_machine_no_data"));
        }
        $this->machine = $this->getMachineFind(['machine_id' => $this->order['machine_id']]);
        if (!$this->machine) {
            return $this->rFail($this->lang("VOrderPay.machine_no_data"));
        }
        $this->mall = $this->getMallFind(['mall_id' => $this->mallMachine['mall_id'], 'status' => 1]);
        if (!$this->mall) {
            return $this->rFail($this->lang("VOrderPay.mall_no_data"));
        }
        if($this->mall['type'] == 1){
            return $this->rFail($this->lang("VOrderPay.mall_disable_points_payment"));
        }
        return true;
    }

    
    /**
     * curl post 请求
     *
     * @param string $url
     * @param array $aHeader
     * @param string $sParams
     * @param string $cookie
     * @return string
     */
    private function curl_post($url, $aHeader, $sParams, $cookie='') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sParams);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        if ($result === false){
        }
        return $result;
    }

    private function generateRandomString($length = 16) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    
    private function mallcooPost($sUrl, $aPostData){
        $sPostData = json_encode($aPostData);
        $nTimeStamp = date('YmdHis',time());
        $sS = "{publicKey:".$this->m_PublicKey.",timeStamp:".$nTimeStamp.",data:".$sPostData.",privateKey:".$this->m_PrivateKey."}";
        $sSign = strtoupper(substr(md5($sS), 8, 16));
        $aHeader = array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($sPostData),
            'AppID: '.$this->strategyPayee['app_id'],
            'TimeStamp: '.$nTimeStamp,
            'PublicKey: '.$this->m_PublicKey,
            'Sign: '.$sSign,
        );
        $sR = $this->curl_post($sUrl, $aHeader, $sPostData);
        $rtn = json_decode(html_entity_decode($sR), true);
        //请求之前， 记录一下请求日志
        $this->addMallRequestLogs([
            'mall_id' => $this->mall['mall_id'],
            'order_id' => $this->order['order_id'],
            'request_url' => $sUrl,
            'request_headers' => json_encode($aHeader),
            'request_body' => json_encode($sPostData),
            'response_status' => $rtn['Code'],
            'response_body' => $rtn['Message'],
        ]);      
        return $rtn;
    }


    /**
     * 会员支付
     * @return mixed
     */
    public function mallPointsPay()
    {
        $this->initMallPointsPay();
        $total_price = $this->order['total_price'];
        $score = $total_price * $this->mall['intergral_rate'];
        $rtn = $this->mallcooPost($this->reduce_points_url, [
            'CardNo' => $this->order['pay_code'],
            'ScoreEvent' => 'BonusSum',
            'Score' => $score,
            'Reason' => $this->order['trade_no'].'订单消费扣减积分',
            'TransID' => $this->generateRandomString(16),
        ]);
                                 
        if($rtn['Code'] == 1){
            //扣减积分成功，更新订单状态为已支付
            $this->order['pay_status'] = 3;
            $this->order['pay_time'] = time();
            $this->order['total_price'] = 0;
            $this->order['intergral_rate'] = $this->mall['intergral_rate'];
            $this->order['total_points'] = $score;
            $uOrder = $this->updateSaleOrders($this->order, [], ['pay_status']);
            if ($uOrder) {
                actionLog($this->getLS(), '修改订单支付状态信息');
                $this->outGoods();
                return $this->rSuccess($this->lang("pay_status3"));
            }
        }
        return $this->rFail($this->lang("VOrderPay.update_order_pay_info_fail").$rtn['Message']);
    }

    /**
     * 积分消费
     * @return mixed
     */
    protected function mallPointsRefund()
    {
        $this->initMallPointsPay();
        $result = $this->mallcooPost($this->increase_points_url, [
            'CardNo' => $this->order['pay_code'],
            'ScoreEvent' => 'BonusSum',
            'Score' => $this->refundData['refund_amount'] * $this->mall['intergral_rate'],
            'Reason' => $this->order['trade_no'].'订单退款返回积分',
            'TransID' => $this->generateRandomString(16),
        ]);
        
        actionLog($result, '退款申请结果');
        if($result['Code'] == 1){
            return $this->r(200, "退款申请成功");
        }
        return $this->rFail( '退款失败：' . $result['Message']);
    }

}