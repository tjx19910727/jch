<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:38
 */

namespace app\AppFactory\Kernel\Traits\Strategy;


use app\AppFactory\Kernel\Model\Strategy\StrategyPayeeModel;
use app\AppFactory\Kernel\Support\Validate\Pay\VAliPay;
use app\AppFactory\Kernel\Support\Validate\Pay\VJdPay;
use app\AppFactory\Kernel\Support\Validate\Pay\VTlPay;
use app\AppFactory\Kernel\Support\Validate\Pay\VWxPay;
use think\exception\ValidateException;

trait StrategyPayeeTrait
{
    public function getStrategyPayeeFind($where, $field = "*",$order = "")
    {
        $data = StrategyPayeeModel::getFind($where,$field,$order);
        return $data;
    }

    public function getStrategyPayeeList($where,$pageNum = 0,$field = "*", $order = "")
    {
        $data = StrategyPayeeModel::getList($where,$pageNum,$field,$order);
        return $data;
    }

    public function addStrategyPayee($insert)
    {
        $insert['content'] = arr2json($insert['content']);
        $insert['creator'] = $this->manager['manager_id'];
        $data = StrategyPayeeModel::create($insert);
        return $data->sp_id;
    }

    public function updateStrategyPayee($update,$where = [], $field = [])
    {
        if (isset($update['content']) && is_array($update['content'])) $update['content'] = arr2json($update['content']);
        $update['creator'] = $this->manager['manager_id'];
        return StrategyPayeeModel::update($update,$where,$field);
    }

    public function delStrategyPayee($where)
    {
        return StrategyPayeeModel::destroy($where);
    }

    protected $vClass = [
        1 => VWxPay::class,
        2 => VAliPay::class,
        3 => VTlPay::class,
        4 => VJdPay::class,
    ];
    protected $scene = [
        1 => "wx",
        2 => "ali",
        3 => "tl",
        4 => "jd",
    ];

    /**
     * 获取一条收款方配置内容
     * @param $where
     * @param string $field
     * @param string $order
     * @return array
     */
    public function getStrategyPayeeContent($where,$field = "*",$order = "ss.sort asc, update_time desc")
    {
        $payee = $this->getStorePayeeFind($where,$field,$order);
        if (!$payee) {
            return $this->rFail("查无收款方配置信息");
        }
        if (is_string($payee)) return $this->rFail($payee);
        $content = json2arr($payee['content']);
        if (!$content) return $this->rFail('收款方配置信息格式错误，不是JSON格式');
        $content['sp_id'] = $payee['sp_id'];
        try {
            validate($this->vClass[$payee['payee_type']])->scene($this->scene[$payee['payee_type']])->check($content);
        } catch (ValidateException $e) {
            return $this->rFail($e->getMessage());
        }
        return array_merge($payee,$content);
    }

    /**
     * 通过门店ID跟支付类型查询支付配置
     * @param $store_id
     * @param $payee_type
     * @return array
     */
    public function getStrategyPayeeContentByStoreId($store_id,$payee_type)
    {
        $s_ids = $this->getStrategyStoreColumn(['store_id' => $store_id,"s_type" => 1],'s_id');
        if (!$s_ids) return $this->rFail("当前门店未绑定收款方配置");
        $payee = $this->getStrategyPayeeFind([['sp_id','in',$s_ids],'status' => 1,'payee_type' => $payee_type],'sp_id,content','update_time desc');
        if (!$payee) return $this->rFail("查无收款方配置信息");
        $content = json2arr($payee['content']);
        if (!$content) return $this->rFail('收款方配置信息格式错误，不是JSON格式');
        $content['sp_id'] = $payee['sp_id'];
        try {
            validate($this->vClass[$payee_type])->scene($this->scene[$payee_type])->check($content);
        } catch (ValidateException $e) {
            return $this->rFail($e->getMessage());
        }
        return $content;
    }

    /**
     * 通过创建人ID跟支付类型查询支付配置
     * @param $creator
     * @param $payee_type
     * @return array
     */
    public function getStrategyPayeeContentByCreator($creator,$payee_type)
    {
        $payee = $this->getStrategyPayeeFind(['creator' => $creator,'payee_type' => $payee_type],'sp_id,content','update_time desc');
        if (!$payee) return $this->rFail("查无收款方配置信息");
        $content = json2arr($payee['content']);
        if (!$content) return $this->rFail('收款方配置信息格式错误，不是JSON格式');
        $content['sp_id'] = $payee['sp_id'];
        try {
            validate($this->vClass[$payee_type])->scene($this->scene[$payee_type])->check($content);
        } catch (ValidateException $e) {
            return $this->rFail($e->getMessage());
        }
        return $content;
    }

}