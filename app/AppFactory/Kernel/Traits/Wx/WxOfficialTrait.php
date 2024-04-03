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
        $wx = WxOfficialModel::create($insert);
        return $wx->id;
    }

    public function updateWxOfficial($update,$where = [], $field = [])
    {
        return WxOfficialModel::update($update,$where,$field);
    }

    /**
     * 处理授权信息
     * @param $authorizeInfo
     * @param $manager
     * @return WxOfficialTrait|array|mixed|null|\think\Model
     */
//    public function handleAuthorize($authorizeInfo,$manager)
//    {
//        $where['app_id'] = $authorizeInfo['authorizer_appid'];
//        $wx = $this->getWxOfficialFind($where);
//        if ($wx) {
//            $authData['wx_id'] = $wx['wx_id'];
//            $authData['update_id'] = $manager['manager_id'];
//            $authData['status'] = 1;
//            WxOfficialModel::update($authData);
//        } else {
//            $wx = $authData;
//            $wx['creator'] = $manager['manager_id'];
//            $wx['wx_id'] = $this->addWxOfficial($authData);
//        }
//        $func = $this->getWxOfficialFuncFind(['wf_id' => $wx['wx_id']]);
//        if (!$func) {
//            // 保存权限集
//            $funcData = [
//                'wf_id' => $wx['wx_id'],
//                'func_info' => json_encode($authorizeInfo['func_info'],JSON_UNESCAPED_UNICODE),
//            ];
//            $this->addWxOfficialFunc($funcData);
//        } else {
//            $func = $func->toArray();
//            $func['func_info'] = json_encode($authorizeInfo['func_info'],JSON_UNESCAPED_UNICODE);
//            $this->updateWxOfficialFunc($func);
//        }
//        return $wx;
//    }


    /**
     * 初始化微信公众号
     * @return array|bool|string
     */
    public function initWxApp($pidList)
    {
        $where[] = ['creator','in', $pidList];
        $where['status'] = 1;
        $wx = $this->getWxOfficialFind($where,'*','id desc');
        if (!$wx) {
            $return = $this->rFail("查无微信公众号配置信息");
            return $return;
        }
        $this->wx_app = Factory::officialAccount($wx);
        return true;
    }
}