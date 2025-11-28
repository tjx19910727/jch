<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/22
 * Time: 9:56
 */

namespace app\AppFactory\Wx\Official;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\Wx\WxBaseClient;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;

class OfficialClient extends WxBaseClient
{
    use WxOfficialTrait;
    use UserTrait;
    use AuthManagerTrait;


    /**
     * @param $message
     */
    public function receiveHandle($message)
    {
        try {
            $this->wx = $this->getWxOfficialFind(['gh_id' => $message['ToUserName']]);
            if (!$this->wx) {
                actionLog($this->getLS(),'查无微信配置SQL');
            }
            if ($this->wx) {
                $this->wx = $this->wx->toArray();
                if ($this->wx) {
                    $this->getWxApp($this->wx);
                    $this->wx_app->server->push(function ($message) {
                        $this->open_id = $message['FromUserName'];
                        switch ($message['MsgType']) {
                            case "event":
                                return $this->receive_event($message);
                                break;
                            case "text":
                                return "哇喔，很幸运被小主翻牌了\n开心到飞起\n感谢小主的好眼光\n今天最美的瞬间就是遇到您🎉\nbiubiu~";
                                break;
                            case "image":
                                return "哇喔，很幸运被小主翻牌了\n开心到飞起\n感谢小主的好眼光\n今天最美的瞬间就是遇到您🎉\nbiubiu~";
                                break;
                            default:
                                return "哇喔，很幸运被小主翻牌了\n开心到飞起\n感谢小主的好眼光\n今天最美的瞬间就是遇到您🎉\nbiubiu~~";
                                break;
                        }
                    });
                    $this->wx_app->server->serve()->send();
                }
            }
        } catch (BadRequestException $e) {
            actionException($e,1);
        } catch (InvalidArgumentException $e) {
            actionException($e,1);
        } catch (InvalidConfigException $e) {
            actionException($e,1);
        } catch (\ReflectionException $e) {
            actionException($e,1);
        }
    }

    /**
     * 获取微信公众号菜单栏
     * @return json
     */
    public function menuList($message){
        try {
            $this->wx = $this->getWxOfficialFind(['gh_id' => $message['gh_id']]);
            if (!$this->wx) {
                actionLog($this->getLS(),'查无微信配置SQL');
            } else {
                $this->wx = $this->wx->toArray();
                if($this->wx){
                    $this->wx_app = $this->getWxApp($this->wx);
                    actionLog($this->wx_app,'wx_app');
                    $current = $this->wx_app->menu->current();
                    actionLog($current,'current');
                    $list = $this->wx_app->menu->list();
                    actionLog($list,'wx_app_menu_list');
                    if (isset($list['errcode']) && $list['errcode'] !== 0) {
                        return $this->rFail($list['errmsg'],'查询失败');
                    }elseif(isset($list['menu'])){
                        $menu = [
                            'list' => $list,
                            'current' => $current
                        ];
                        return returnData($menu,'获取成功');
                    }
                }
            }
        } catch (BadRequestException $e) {
            actionLog($e,'BadRequestException');
            actionException($e,1);
        } catch (InvalidArgumentException $e) {
            actionLog($e,'InvalidArgumentException');
            actionException($e,1);
        } catch (InvalidConfigException $e) {
            actionLog($e,'InvalidConfigException');
            actionException($e,1);
        } catch (\ReflectionException $e) {
            actionLog($e,'ReflectionException');
            actionException($e,1);
        }
    }

    /**
     * 修改微信公众号菜单
     * @return json
     */
    public function editMenu($data){
        try{
            $menu = $data['menu'];
            $validationErrors = $this->validateMenuData($menu);

            if (!empty($validationErrors)) {
                return $this->r(100,'菜单数据验证失败: ' . implode('; ', $validationErrors));
            }

            $this->wx = $this->getWxOfficialFind(['gh_id' => $data['gh_id']]);
            if (!$this->wx) {
                actionLog($this->getLS(),'查无微信配置SQL');
            } else {
                $this->wx = $this->wx->toArray();
                if($this->wx){
                    $this->wx_app = $this->getWxApp($this->wx);
                    actionLog($this->wx_app->menu,'wx_app_menu');
                    // 先删除旧菜单 再创建新菜单
                    $del_rtn = $this->wx_app->menu->delete();
                    actionLog($del_rtn,'del_rtn');
                    actionLog($menu,'待执行的menu');
                    $result = $this->wx_app->menu->create($menu['button']);
                    actionLog($result,'创建菜单查询结果');
                    if ($result['errcode'] == 0) {
                        sleep(2);
                        $current = $this->wx_app->menu->current();
                        actionLog($current,'current');
                        if ($current['is_menu_open'] == 1 && !empty($current['selfmenu_info']['button'])){
                            return returnData($current,'创建成功');
                        }
                    }else{
                        return $this->rFail($result,'创建失败');
                    }
                }
            }
        } catch (BadRequestException $e) {
            actionLog($e,'BadRequestException');
            actionException($e,1);
        } catch (InvalidArgumentException $e) {
            actionLog($e,'InvalidArgumentException');
            actionException($e,1);
        } catch (InvalidConfigException $e) {
            actionLog($e,'InvalidConfigException');
            actionException($e,1);
        } catch (\ReflectionException $e) {
            actionLog($e,'ReflectionException');
            actionException($e,1);
        }
    }

    /**
     * 处理事件
     * @param $message
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    private function receive_event($message)
    {
        $event = $message['Event'];
        switch ($event) {
            // 关注事件、扫描二维码
            case "subscribe":case "SCAN":case "VIEW":
                // 处理关注事件
                return $this->receive_subscribe($message);
                break;
            // 取消关注事件
            case "unsubscribe":
                return $this->receive_unsubscribe();
                break;
            case "CLICK":
                return $this->receive_handle_event_key($message);
                break;
            default:
                return '用户事件未定义';
                break;
        }
    }

    /**
     * 根据EventKey处理CLICK事件
     * @param $message
     */
    private function receive_handle_event_key($message){
        $eventKey = $message['EventKey'];
        switch ($eventKey) {
            // 默认回复
            case "CUSTOMER_SERVICE":
                return "您好！客服小助手为您服务。请描述您的问题，我们将尽快为您解答";
                break;
            // 呼叫技术客服
            case "TECH_SUPPORT":
                return "技术客服已就位，请详细描述您遇到的技术问题，我们将安排专业工程师为您解决。";
                break;
            case "PRODUCT_INFO":
                return "感谢您对我们产品的关注！请告诉我们您想了解哪款产品，我们将为您提供详细介绍。";
                break;
            case "ABOUT_US":
                return "我们是一家专注于技术创新的公司，致力于为用户提供优质的产品和服务。了解更多请访问我们的官网。";
                break;
            case "USE_HELP":
                return "使用帮助：\n• 输入关键词获取信息\n• 联系客服请回复人工\n• 业务咨询请拨打电话";
                break;
            default:
                return '用户事件未定义';
                break;
        }
    }

    /*;
     * 处理关注事件
     * @param $event
     * @return string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    private function receive_subscribe($event)
    {
        $user_info = $this->wx_app->user->get($this->open_id);
        actionLog($user_info,'获取微信用户信息');
        $info = $this->getUserFind(['openid' => $this->open_id]);
        $auth_user = $this->setUserInfo($user_info,$info);
        $reply = "感谢关注此公众号\n";
        if (isset($event['EventKey']) && trim($event['EventKey']) != '') {
            $key = $event['EventKey'];
            if (false !== strpos($key, 'qrscene_')) $key = str_replace('qrscene_', '', $key);
            $qrScene = explode('_', $key);
            actionLog($qrScene, 'qrSceneArr');
            if ($qrScene) {
                $wx_id = $qrScene[0] ?? 0;
                $type = $qrScene[1] ?? 0;
                // 管理员绑定微信用户
                if ($type == 1 || $type == 2) {
                    $manager_id = $qrScene[2];
                    $auth_user['manager_id'] = $manager_id;
                    $this->updateUser($auth_user);
                    $this->updateAuthManager(['manager_id' => $manager_id,'user_id' => $auth_user['user_id'],"wx_id" => $wx_id,'openid' => $this->open_id]);
                    actionLog($this->getLS(),'绑定账号微信OPENID');
                    $reply .= "绑定管理员成功";
                }
            }
        }
        return $reply;
    }

    /**
     * 处理取消关注事件
     * @return string
     */
    private function receive_unsubscribe()
    {
        $user  = $this->getUserFind(['openid' => $this->open_id]);
        if ($user) {
            $user = $user->toArray();
            $user['unsubscribe_num']++;
            // 用户是否关注公众号 1：关注 0：未关注 2：取消关注，取消关注次数+1
            $this->updateUser(["user_id" => $user['user_id'],'unsubscribe_num' => $user['unsubscribe_num'], 'subscribe' => 2]);
            actionLog($this->getLS(),'用户取消关注');
        }
        return '处理成功！';
    }


    /**
     * 保存/修改用户信息
     * @param array $user_info 微信用户信息
     * @param array $info 用户信息
     * @return array|false|\PDOStatement|string|\think\Model
     */
    private function setUserInfo($user_info = [], $info = [])
    {
        $setData = [
            "subscribe" => 1,
            "name" => $user_info['nickname'] ?? "微信用户",
            "type" => 2,
        ];
        if (!$info) {
            $setData["wx_id"] = $this->wx['id'];
            $setData["openid"] = $user_info['openid'];
            $setData["creator"] = $this->wx['creator'];
            $setData['user_id'] = $this->addUser($setData);
        } else {
            $setData['user_id'] = $info['user_id'];
            $this->updateUser($setData);
        }
        return $setData;
    }

    /**
     * 验证菜单数据结构
     */
    public function validateMenuData($menuData) {
        $errors = [];
        
        // 检查根结构
        if (!isset($menuData['button']) || !is_array($menuData['button'])) {
            $errors[] = "菜单数据必须包含button数组";
        }
        
        $buttons = $menuData['button'];
        
        // 检查一级菜单数量
        if (count($buttons) > 3) {
            $errors[] = "一级菜单最多3个，当前" . count($buttons) . "个";
        }
        
        foreach ($buttons as $index => $button) {
            $buttonNum = $index + 1;
            
            // 检查菜单名称
            if (!isset($button['name']) || empty(trim($button['name']))) {
                $errors[] = "第{$buttonNum}个菜单缺少name或name为空";
            } elseif (mb_strlen($button['name'], 'UTF-8') > 16) {
                $errors[] = "第{$buttonNum}个菜单名称过长（最多16个字符）";
            }
            
            // 检查是否有子菜单
            if (isset($button['sub_button'])) {
                if (!is_array($button['sub_button'])) {
                    $errors[] = "第{$buttonNum}个菜单的sub_button必须是数组";
                } else {
                    // 验证子菜单
                    $subButtons = $button['sub_button'];
                    if (count($subButtons) > 5) {
                        $errors[] = "第{$buttonNum}个菜单的子菜单最多5个，当前" . count($subButtons) . "个";
                    }
                    foreach ($subButtons as $subIndex => $subButton) {
                        $subErrors = $this->validateButton($subButton, "第{$buttonNum}个菜单的第" . ($subIndex + 1) . "个子菜单");
                        if ($subErrors) {
                            $errors = array_merge($errors, $subErrors);
                        }
                    }
                }
            } else {
                // 验证普通按钮
                $buttonErrors = $this->validateButton($button, "第{$buttonNum}个菜单");
                if ($buttonErrors) {
                    $errors = array_merge($errors, $buttonErrors);
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * 验证单个按钮
     */
    private function validateButton($button, $location) {
        $errors = [];
        
        // 检查type
        if (!isset($button['type'])) {
            $errors[] = "{$location}缺少type字段";
        } else {
            $validTypes = ['click', 'view', 'scancode_push', 'scancode_waitmsg', 'pic_sysphoto', 'pic_photo_or_album', 'pic_weixin', 'location_select', 'media_id', 'view_limited','miniprogram'];
            if (!in_array($button['type'], $validTypes)) {
                $errors[] = "{$location}的type无效，必须是: " . implode(', ', $validTypes).",当前是：".$button['type'];
            }
        }
        
        // 根据type验证必要字段
        if (isset($button['type'])) {
            switch ($button['type']) {
                case 'click':
                    if (!isset($button['key'])) {
                        $errors[] = "{$location}的click类型必须包含key字段";
                    }
                    break;
                case 'view':
                    if (!isset($button['url'])) {
                        $errors[] = "{$location}的view类型必须包含url字段";
                    }
                    break;
                case 'miniprogram':
                    if (!isset($button['url']) || !isset($button['appid']) || !isset($button['pagepath'])) {
                        $errors[] = "{$location}的miniprogram类型必须包含url、appid、pagepath字段";
                    }
                    break;
            }
        }
        
        return $errors;
    }

}