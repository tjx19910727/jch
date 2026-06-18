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
use app\AppFactory\Kernel\Model\Auth\AuthManagerRoleModel;
use app\AppFactory\Kernel\Model\Auth\AuthManagerMachineModel;
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
     * 导出账户列表（队列方式）
     * @param array $where 查询条件
     * @return array
     */
    public function exportAccount($where)
    {
        $field = "au.manager_id,au.nickname,au.account,au.pid,au.openid,au.bill_account,au.real_name,au.sex,au.status,au.ao_id,au.wx_notice,au.email_notice,au.email,ao.organization_name";
        $list = $this->getAuthManagerList($where, 0, $field, '');
        if ($list) {
            $list = $list->toArray();
        } else {
            $list = [];
        }
        if (empty($list)) {
            return $this->rFail('没有需要导出的数据');
        }

        $managerIds = array_column($list, 'manager_id');
        $pids = array_unique(array_filter(array_column($list, 'pid')));

        // 批量查询归属上级昵称
        $parentNames = [];
        if ($pids) {
            $parents = $this->getAuthManagerList([['manager_id', 'in', $pids]], 0, 'manager_id,nickname');
            if ($parents) {
                foreach ($parents as $p) {
                    $parentNames[$p['manager_id']] = $p['nickname'];
                }
            }
        }

        // 批量查询角色名称
        $roleNameMap = [];
        if ($managerIds) {
            $roleRows = AuthManagerRoleModel::getJoinRoleList([['mr.manager_id', 'in', $managerIds], 'is_del' => 2], 0, 'mr.manager_id,ar.name');
            if ($roleRows) {
                foreach ($roleRows as $r) {
                    $roleNameMap[$r['manager_id']][] = $r['name'];
                }
            }
        }

        // 批量查询管理的设备数
        $machineCountMap = [];
        if ($managerIds) {
            $machineRows = AuthManagerMachineModel::where('manager_id', 'in', $managerIds)
                ->field('manager_id,count(*) as cnt')
                ->group('manager_id')
                ->select();
            if ($machineRows) {
                foreach ($machineRows as $m) {
                    $machineCountMap[$m['manager_id']] = $m['cnt'];
                }
            }
        }

        $sexMap = [1 => '保密', 2 => '男', 3 => '女'];
        $statusMap = [1 => '启用', 2 => '禁用'];
        $noticeTypeMap = [
            'online'     => '上线通知',
            'offline'    => '离线通知',
            'fault'      => '故障通知',
            'understock' => '库存不足通知',
            'sale'       => '销售通知',
            'tException' => '交易异常',
            'mFault'     => '机械故障',
        ];

        $title = ['账户名称', '登录账户', '真实姓名', '管理的设备数', '用户微信', '归属上级', '用户性别', '分润账户', '状态', '归属组织', '所属权限', '邮箱地址', '微信通知', '邮件通知'];
        $rows = [];
        foreach ($list as $row) {
            $rows[] = [
                $row['nickname'] ?? '',
                $row['account'] ?? '',
                $row['real_name'] ?? '',
                $machineCountMap[$row['manager_id']] ?? 0,
                $row['openid'] ?? '',
                $parentNames[$row['pid']] ?? '',
                $sexMap[$row['sex']] ?? '保密',
                $row['bill_account'] ?? '',
                $statusMap[$row['status']] ?? '禁用',
                $row['organization_name'] ?? '',
                isset($roleNameMap[$row['manager_id']]) ? implode(',', $roleNameMap[$row['manager_id']]) : '',
                $row['email'] ?? '',
                $this->formatNoticeTypes($row['wx_notice'] ?? '', $noticeTypeMap),
                $this->formatNoticeTypes($row['email_notice'] ?? '', $noticeTypeMap),
            ];
        }

        return $this->sendToExport('management', '账户列表_' . date('YmdHis'), $title, $rows);
    }

    /**
     * 将通知类型编码转为中文名称
     * @param string $noticeStr 逗号分隔的编码，如 "online,offline"
     * @param array $map 编码到中文的映射
     * @return string
     */
    private function formatNoticeTypes($noticeStr, $map)
    {
        if ($noticeStr === '') {
            return '';
        }
        $codes = explode(',', $noticeStr);
        $names = [];
        foreach ($codes as $code) {
            $code = trim($code);
            if (isset($map[$code])) {
                $names[] = $map[$code];
            }
        }
        return implode(',', $names);
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
                'is_default' => 1,
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