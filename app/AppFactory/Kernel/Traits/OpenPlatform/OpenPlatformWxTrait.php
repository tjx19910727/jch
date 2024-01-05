<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/13
 * Time: 16:30
 */

namespace app\AppFactory\Kernel\Traits\OpenPlatform;


use app\AppFactory\Kernel\Model\OpenPlatform\OpenPlatformWxModel;
use EasyWeChat\Factory;
use EasyWeChat\OpenPlatform\Application;

trait OpenPlatformWxTrait
{
    /**
     * @var Application
     */
    protected $opApp;
    public function getOpenPlatformWxValue($where,$value)
    {
        return OpenPlatformWxModel::getFieldValue($where,$value);
    }
    public function getOpenPlatformWxFind($where,$field = "*", $order = "")
    {
        return OpenPlatformWxModel::getFind($where,$field,$order);
    }

    public function getOpenPlatformWxList($where,$pageNum = 0, $field = "*", $order = "op_id desc")
    {
        return OpenPlatformWxModel::getList($where,$pageNum,$field,$order);
    }

    public function addOpenPlatformWx($insert)
    {
        $op = OpenPlatformWxModel::create($insert);
        return $op->wx_id;
    }

    public function updateOpenPlatformWx($update,$where = [], $field = [])
    {
        return OpenPlatformWxModel::update($update,$where,$field);
    }

    /**
     * 处理授权信息
     * @param $authorizeInfo
     * @param $manager
     * @return OpenPlatformWxTrait|array|mixed|null|\think\Model
     */
    public function handleAuthorize($authorizeInfo,$manager)
    {
        $authData = [
            'authorizer_appid' => $authorizeInfo['authorizer_appid'],
            'authorizer_access_token' => $authorizeInfo['authorizer_access_token'],
            'expires_in' => $authorizeInfo['expires_in'],
            'authorizer_refresh_token' => $authorizeInfo['authorizer_refresh_token'],
            'status' => 1,
            'wx_type' => $authorizeInfo['wx_type'],
        ];
        $where['authorizer_appid'] = $authorizeInfo['authorizer_appid'];
        $wx = $this->getOpenPlatformWxFind($where);
        if ($wx) {
            $authData['wx_id'] = $wx['wx_id'];
            $authData['update_id'] = $manager['manager_id'];
            $authData['status'] = 1;
            OpenPlatformWxModel::update($authData);
        } else {
            $wx = $authData;
            $wx['creator'] = $manager['manager_id'];
            $wx['wx_id'] = $this->addOpenPlatformWx($authData);
        }
        $func = $this->getOpenPlatformWxFuncFind(['wf_id' => $wx['wx_id']]);
        if (!$func) {
            // 保存权限集
            $funcData = [
                'wf_id' => $wx['wx_id'],
                'func_info' => json_encode($authorizeInfo['func_info'],JSON_UNESCAPED_UNICODE),
            ];
            $this->addOpenPlatformWxFunc($funcData);
        } else {
            $func = $func->toArray();
            $func['func_info'] = json_encode($authorizeInfo['func_info'],JSON_UNESCAPED_UNICODE);
            $this->updateOpenPlatformWxFunc($func);
        }
        return $wx;
    }


    /**
     * 初始化微信开放平台
     * @return array|bool|string
     */
    public function initWxApp()
    {
        $where['config_name'] = "openPlatform";
        $where['config_switch'] = 1;
        $config = $this->getConfigContent($where);
        if (!$config) {
            $return = $this->rFail("查无开放平台配置信息");
            return $return;
        }
        $this->opConfig = $config;
        $this->opApp = Factory::openPlatform($config);
        return true;
    }
}