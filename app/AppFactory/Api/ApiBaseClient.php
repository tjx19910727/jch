<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 15:25
 */

namespace app\AppFactory\Api;


use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Support\Validate\Api\VV2;
use app\AppFactory\Kernel\Traits\Config\ConfigApiTrait;

class ApiBaseClient extends BaseClient
{
    use ConfigApiTrait;
    public $authConfig;
    public $ip;

    // https://cad.cereson.cn/web/?#/1?page_id=4
    public $msg = [
        0 => "Success",
        1 => "Invalid IP Address",
        2 => "Invalid User",
        3 => "Wrong Password",
        4 => "No Such API",
        5 => "JSON Syntax Error",
        6 => "Missing Fields",
        7 => "Invalid Fields",
        8 => "Requests Count Limited",
        9 => "Requests Interval Limited",
        10 => "Data not Found",
        11 => "Can not Contact the Kiosk",
        12 => "License Expired",
        13 => "Exceed Date Range(30 days)",
        14 => "Product is unavailable",
        15 => "table is nonexistent.",
        16 => "Invalid Timestamp",
        17 => "Sign Error",
        99 => "Service Unavailable",
    ];

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->getAuthConfig();
        $this->checkIp();
//        $this->checkApiFrequency();
        $this->checkParams();
        $this->checkApiSign();
    }

    /**
     * 1 获取用户信息
     */
    public function getAuthConfig()
    {
        $this->authConfig = $this->getConfigApiFind(['auth_name' => $this->config['auth_name']]);
        if (!$this->authConfig) {
            $this->returnData(99, $this->msg[99])->send();
            die();
        }
        $this->authConfig = $this->authConfig->toArray();
    }

    /**
     * 2 检查IP
     */
    public function checkIp()
    {
        $this->authConfig['white_list'] = explode(",",$this->authConfig['white_list']);
        $this->ip = request()->ip();
        if ($this->authConfig['white_list'] && !in_array($this->ip,$this->authConfig['white_list'])) {
            $this->returnData(1,$this->msg[1])->send();
            die();
        }
    }

    /**
     * 3 检查接口访问频次
     */
    public function checkApiFrequency()
    {
        $frequency = cache($this->ip);
        if (!isset($frequency[$this->config['api']])) {
            $frequency[$this->config['api']]['num'] = 1;
            $frequency[$this->config['api']]['time'] = time();
            $frequency[$this->config['api']]['params'] = $this->config['params'];
        } else {
            // 同1个IP调用同1个接口超过1天总限制次数1000次
            if ($frequency[$this->config['api']]['num'] >= 1000) {
                $this->returnData(8,$this->msg[8])->send();
                die();
            }
            // 10秒内，同IP同api同样params的数据
            if ($frequency[$this->config['api']]['params'] == $this->config['params'] && time() - $frequency[$this->config['api']]['time'] <= 10) {
                $this->returnData(9,$this->msg[9])->send();
                die();
            }
            $frequency[$this->config['api']]['num']++;
            $frequency[$this->config['api']]['time'] = time();
        }
        $time = strtotime(date("Y-m-d",strtotime("+1 day"))) - time();
        cache($this->ip,$frequency,$time);
    }

    /**
     * 4 解析params的JSON格式
     */
    public function checkParams()
    {
        if ($this->config['params']) {
            $this->config['params'] = json_decode($this->config['params'],true);
            if (!$this->config['params']) {
                $this->returnData(5,$this->msg[5])->send();
                die();
            }
            $this->validateParams();
        }
    }

    /**
     * 5 检查API签名
     */
    public function checkApiSign()
    {
        $sign = $this->makeApiSign();
        if ($sign != $this->config['sign']) {
            $this->returnData(17,$this->msg[17])->send();
            die();
        }
    }

    /**
     * 验证Params参数
     */
    public function validateParams()
    {
        try {
            validate(VV2::class)->scene($this->config['api'])->check($this->config['params']);
        } catch (\Exception $e) {
            $this->returnData(6,$this->msg[6] . "：" . $this->lang("VV2." . $e->getMessage()))->send();
            die();
        }
    }

    /**
     * 生成API签名
     * @return string
     */
    public function makeApiSign()
    {
        $string1 = strtoupper(md5($this->authConfig['auth_password'] . $this->config['timestamp']));
        ksort($this->config['params']);
        foreach ($this->config['params'] as $k => $v) {
            $signArr[] = $k . "=" . $v;
        }
        $signStr = $string1 . implode(",",$signArr);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }

    /**
     * API返回格式
     * @param $status_code
     * @param string $msg
     * @param string $data
     * @param int $isJson
     * @return array|\think\response\Json
     */
    public function returnData($status_code,$msg = "", $data = "",$isJson = 1)
    {
//        if (is_array($data)) $data = json_encode($data,320);
        $return = ["status_code" => $status_code,"msg" => $msg,"data" => $data];
        if ($isJson) return json($return);
        return $return;
    }
}