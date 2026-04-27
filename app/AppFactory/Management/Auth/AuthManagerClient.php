<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:10
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\Management\ManagementClient;
use app\AppFactory\Kernel\Traits\Auth\AuthWithdrawRequestTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrgRevenueTrait;
use think\facade\Db;

class AuthManagerClient extends ManagementClient
{
    use AuthManagerTrait;
    use WxOfficialTrait;
    use AuthWithdrawRequestTrait;
    use AuthOrgRevenueTrait;

    public function updateSelfPwd($postData)
    {
        if ($this->manager['password'] != md5($postData['old_pwd'] .$this->salt )) {
            return $this->r(100,$this->lang("VLogin.pwd_incorrect"));
        }
        $update['password'] = $postData['password'];
        $update['manager_id'] = $this->manager['manager_id'];
        return $this->rU($this->updateAuthManager($update));
    }

    /**
     * 获取微信公众号带参二维码
     * @param $manager_id
     * @param $unbind
     * @return array|bool|string
     */
    public function getWxQr($manager_id,$unbind = 0)
    {
        $manager = $this->getAuthManagerFind(['manager_id' => $manager_id]);
        if ($manager['openid'] && $unbind) {
            $result = $this->updateAuthManager(['manager_id' => $manager_id,'wx_id' => 0,"openid" => ""]);
            if ($result) {
                return $this->r(200,$this->lang("VWxOfficial.unbind_success"));
            }
        }
        try {
            $pid = $this->getAuthManagerValue(['manager_id' => $manager_id], 'pid');
            if (!$pid) {
                $pid = $this->getAuthManagerValue(['manager_id' => $manager_id],'creator');
            }
            $pidList = $this->getParentIdList($pid);
            $pidList[] = $manager_id;
            actionLog($pidList,'创建人树');
            $where[] = ['creator', 'in', $pidList];
            $where['status'] = 1;
            $config = $this->getWxOfficialFind($where,'*','id desc');
            if (!$config) {
                actionLog($this->getLS(),'查询配置SQL');
                return $this->r(100, $this->lang("VWxOfficial.official_no_data"));
            }
            $config = $config->toArray();
            $qrScene = $config['id'] . "_2_" . $manager_id;
            $this->getWxApp($config);
            $result = $this->wx_app->qrcode->temporary($qrScene, 5 * 60);
            if (isset($result['ticket'])) {
                if ($config['status'] != 1) $this->updateWxOfficial(['id' => $config['id'], 'status' => 1]);
                $url = $this->wx_app->qrcode->url($result['ticket']);
                return $this->r(200, 'success', $url);
            }
            if ($config['status'] != 2) $this->updateWxOfficial(['id' => $config['id'], 'status' => 2]);
            return $this->r(100, 'fail', $result['errorMsg'] ?? "");
        } catch (\Exception $e) {
            return $this->r(100,'微信返回错误信息：' . $e->getMessage());
        }
    }

    public function addAuthWithdrawRequestData($insert){
        return $this->rA($this->addAuthWithdrawRequest($insert));
    }

    public function getAuthWithdrawRequestData($where, $pageNum = '', $field = '*', $order = '', $eachFn = "", $group = "", $limit = 0){
        return $this->rQ($this->getAuthWithdrawRequestList($where, $pageNum, $field, $order, $eachFn, $group, $limit));
    }

    public function getAuthOrgRevenueLogData($where, $pageNum = 0, $field = '*', $order = '')
    {
        return $this->rQ($this->getAuthOrgRevenueLogList($where, $pageNum, $field, $order));
    }

    /**
     * 审核提现申请
     * @param $wr_id
     * @param $auditor_manager_id
     * @param $status 2=approved,3=rejected
     * @param string $remark
     * @return bool|int
     */
    public function auditAuthWithdrawRequest($wr_id, $manager_id, $status, $remark = '')
    {
        $req = $this->getAuthWithdrawRequestFind(['wr_id' => $wr_id]);
        if (!$req) return $this->rFail('申请不存在');
        $req = $req->toArray() ?: obj2arr($req);
        if ($req['status'] != 1) return $this->rFail('该申请已处理');
        $update = [
            'wr_id' => $wr_id,
            'status' => $status,
            'manager_id' => $manager_id,
            'remark' => $remark,
        ];
        $res = $this->updateAuthWithdrawRequest($update);
        if ($res && $status == 2) {
            // TODO: 通过后执行实际打款/记录流水（此处只记录状态，具体出款留给业务侧）
        }
        return $res;
    }

    /**
     * 获取账号通知配置（故障模板）
     */
    public function getNoticeConfig($managerId, $noticeType = 'mFault')
    {
        $data = Db::name('auth_manager_notice_config')
            ->where([
                'manager_id' => intval($managerId),
                'notice_type' => $noticeType,
            ])
            ->order('id desc')
            ->find();
        if (!$data) {
            $data = [
                'manager_id' => $managerId,
                'notice_type' => $noticeType,
                'interval_minutes' => 0,
                'day_count' => 0,
                'status' => 1,
            ];
        }
        return $this->r(200, 'success', $data);
    }

    /**
     * 保存账号通知配置（故障模板）
     */
    public function saveNoticeConfig($postData)
    {
        $managerId = intval($postData['manager_id'] ?? 0);
        $noticeType = strval($postData['notice_type'] ?? 'mFault');
        $intervalMinutes = intval($postData['interval_minutes'] ?? 0);
        $dayCount = intval($postData['day_count'] ?? 0);
        $is_default = isset($postData['is_default']) ? intval($postData['is_default']) : 1;
        $exists = Db::name('auth_manager_notice_config')
            ->where([
                'manager_id' => $managerId,
                'notice_type' => $noticeType,
            ])
            ->find();

        if($is_default == 2){
            //频率最小为1，次数最大为50
            if($intervalMinutes < 1){
                return $this->rFail('通知频率最小为1分钟');
            }
            if($dayCount > 50){
                return $this->rFail('每天通知次数最大为50次');
            }
        }
        if ($exists) {
            $update = [
                'interval_minutes' => $intervalMinutes,
                'day_count' => $dayCount,
                'is_default' => $is_default,
            ];
            $result = Db::name('auth_manager_notice_config')->where('id', $exists['id'])->update($update);
            return $this->rU($result);
        }
        
        $insert = [
            'manager_id' => $managerId,
            'notice_type' => $noticeType,
            'interval_minutes' => $intervalMinutes,
            'day_count' => $dayCount,
            'is_default' => $is_default,
        ];
        $id = Db::name('auth_manager_notice_config')->insertGetId($insert);
        return $this->rA($id);
    }

}