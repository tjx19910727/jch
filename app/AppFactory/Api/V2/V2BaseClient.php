<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 15:25
 */

namespace app\AppFactory\Api\V2;


use app\AppFactory\Api\ApiBaseClient;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Support\Validate\Api\VV2;
use app\AppFactory\Kernel\Traits\Config\ConfigApiTrait;

class V2BaseClient extends ApiBaseClient
{
    use ConfigApiTrait;
    public $authConfig;
    public $ip;
    public $params;

    // https://cad.cereson.cn/web/?#/1?page_id=4

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
        if (isset($this->config['auth_name']) && $this->config['auth_name']) {
            $this->authConfig = $this->getConfigApiFind(['auth_name' => $this->config['auth_name']]);
        }
        actionLog($this->authConfig, 'API配置信息');
        if (!$this->authConfig) {
            $this->returnData(2, $this->lang("msg." . 2))->send();
            die();
        }
        $this->authConfig = $this->authConfig->toArray();
    }

    /**
     * 2 检查IP
     */
    public function checkIp()
    {
        $this->authConfig['white_list'] = explode(",", $this->authConfig['white_list']);
//        $this->ip = request()->ip();
        $this->ip = $this->getClientIp();
        actionLog($this->ip, '请求IP地址');
        if ($this->authConfig['white_list'] && !in_array($this->ip, $this->authConfig['white_list'])) {
            $this->returnData(1, $this->lang("msg." . 1) . "：" . $this->ip)->send();
            die();
        }
    }

    /**
     * 获取客户端真实 IP 地址
     */
    protected function getClientIp()
    {
        $request = request();

        // 优先从 X-Forwarded-For 头中获取 IP
        $ip = $request->header('x-forwarded-for');
        if ($ip) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]); // 取第一个 IP
        } else {
            $ip = $request->ip(); // 默认获取 IP
        }

        // 验证 IP 地址是否合法
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return '0.0.0.0'; // 如果 IP 不合法，返回默认值
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
            // 4秒内，同IP同api同样params的数据
//            if ($frequency[$this->config['api']]['params'] == $this->config['params'] && time() - $frequency[$this->config['api']]['time'] <= 4) {
//                actionLog($this->config,"访问限流新数据");
//                actionLog($frequency[$this->config['api']],"访问限流旧数据");
//                $this->returnData(9, $this->lang("msg." . 9))->send();
//                die();
//            }
            // 同1个IP调用同1个接口超过1天总限制次数8640次
//            if ($frequency[$this->config['api']]['num'] >= 8640) {
//                $this->returnData(8, $this->lang("msg." . 8))->send();
//                di
//            }
            $frequency[$this->config['api']]['num']++;
            $frequency[$this->config['api']]['time'] = time();
        }
        $time = strtotime(date("Y-m-d", strtotime("+1 day"))) - time();
        cache($this->ip, $frequency, $time);
    }

    /**
     * 4 解析params的JSON格式
     */
    public function checkParams()
    {
        if ($this->config['params']) {
            $this->config['params'] = json_decode($this->config['params'], true);
            actionLog($this->config['params'], '接口参数');
            if (!$this->config['params']) {
                $this->returnData(5, $this->lang("msg." . 5))->send();
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
        actionLog($sign, '生成的签名');
        if ($sign != $this->config['sign']) {
            $this->returnData(17, $this->lang("msg." . 17))->send();
            die();
        }
    }

    /**
     * 验证Params参数
     */
    public function validateParams()
    {
        try {
            if ($this->config['api'] != "get_machines") {
                validate(VV2::class)->scene($this->config['api'])->check($this->config['params']);
            }
            $this->params = $this->config['params'];
        } catch (\Exception $e) {
            $this->returnData(6, $this->lang("msg." . 6) . "：" . $this->lang($e->getMessage()))->send();
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
        $signStr = $string1 . implode(",", $signArr);
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
    public function returnData($status_code, $msg = "", $data = "", $isJson = 1)
    {
//        if (is_array($data)) $data = json_encode($data,320);
        $return = ["status_code" => $status_code, "msg" => $msg, "data" => $data];
        if ($isJson) return json($return);
        return $return;
    }
}