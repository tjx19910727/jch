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
                    $this->getWxApp($this->wx);
                    $list = $this->wx_app->menu->list();
                    $current = $this->wx_app->menu->current();
                    dd($list);

                    $menu = json_encode([
                        'list' => $list,
                        'current' => $current
                    ]);
                    dd($menu);
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
            default:
                return '未知事件';
                break;
        }
    }

    /**
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


}