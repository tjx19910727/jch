<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/8
 * Time: 18:00
 */

namespace app\AppFactory\Kernel\Traits\Wx;


use app\AppFactory\Kernel\Model\Wx\WxOfficialModel;
use EasyWeChat\Factory;
use EasyWeChat\OfficialAccount\Application;
use think\facade\Cache;

trait WxOfficialTrait
{

    /**
     * @var Application
     */
    protected $wx_app;

    public function getWxOfficialValue($where,$value)
    {
        return WxOfficialModel::getFieldValue($where,$value);
    }
    public function getWxOfficialFind($where,$field = "*", $order = "")
    {
        return WxOfficialModel::getFind($where,$field,$order);
    }

    public function getWxOfficialList($where,$pageNum = 0, $field = "*", $order = "id desc")
    {
        return WxOfficialModel::getList($where,$pageNum,$field,$order);
    }

    public function addWxOfficial($insert)
    {
        if (!isset($insert['ao_id'])) $insert['ao_id'] = $this->manager['ao_id'];
        if (isset($this->manager['manager_id'])) $insert['creator'] = $this->manager['manager_id'];
        $wx = WxOfficialModel::create($insert);
        return $wx->id;
    }

    public function updateWxOfficial($update,$where = [], $field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'];
        return WxOfficialModel::update($update,$where,$field);
    }

    public function delWxOfficial($where)
    {
        return WxOfficialModel::whereDel($where);
    }

    /**
     * 初始化微信公众号
     * @return array|bool|string
     */
    public function initWxApp($pidList)
    {
        try {
            $where[] = ['creator', 'in', $pidList];
            $where['status'] = 1;
            $wx = $this->getWxOfficialFind($where, '*', 'id desc');
            if (!$wx) {
                $return = $this->rFail("查无微信公众号配置信息");
                return $return;
            }
            $wx = $wx->toArray();
            $this->getWxApp($wx);
            return true;
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    public function getWxApp($wx)
    {
        $this->wx_app = Factory::officialAccount($wx);
        $this->wx_app->access_token->setCache(Cache::store('file'));
        return $this->wx_app;
    }
}