<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 9:46
 */

namespace app\management\controller\auth;


use app\management\controller\Common;
use app\management\validate\VAuth;

class AuthManager extends Common
{
    /**
     * 查询一条管理员信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->authManager->getFind($where);
    }

    /**
     * 获取管理员列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,['nickname' => "like"],'au.');
        if (isset($where['ao_id'])) {
            $where['au.ao_id'] = $where['ao_id'];
            unset($where['ao_id']);
        }
        $field = "au.manager_id,au.nickname,au.account,au.pid,au.openid,au.audit_status,
        au.bill_account,au.real_name,au.level,au.sex,au.pic,au.status,au.creator,au.ao_id,au.wx_notice,au.email_notice,au.email,au.openid,
        au.query_start_time,au.query_start_urls,au.use_role_template,au.role_template_id,
        (SELECT name FROM auth_role_template art WHERE art.art_id = au.role_template_id) role_template_name,
        au.create_time,ao.organization_name";
        $result = $this->app->authManager->getList($where,$pageNum,$field);
        return $result;
    }

    /**
     * 获取所有启用账户的简要列表
     * @return mixed
     */
    public function getEnabledManagerList()
    {
        $where = $this->getWhere(['au.status' => 1],false,[],'');
        return $this->app->authManager->getList(
            $where,
            0,
            'au.manager_id,au.nickname',
            'au.manager_id asc'
        );
    }

    /**
     * 添加管理员
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        $postData['ao_id'] = $this->manager['ao_id'];
        $postData = $this->normalizeQueryStartConfig($postData);
        try { $this->validate($postData,'app\management\validate\VAuth.AuthManagerAdd');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->authManager->add($postData);
        return $result;
    }

    /**
     * 修改管理员
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        $postData = $this->normalizeQueryStartConfig($postData);
        try { $this->validate($postData,'app\management\validate\VAuth.AuthManagerUpdate');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->authManager->update($postData);
        return $result;
    }

    protected function normalizeQueryStartConfig(array $postData): array
    {
        if (isset($postData['query_start_time'])) {
            if ($postData['query_start_time'] === '') {
                $postData['query_start_time'] = 0;
            } elseif (!is_numeric($postData['query_start_time'])) {
                $postData['query_start_time'] = strtotime($postData['query_start_time']) ?: 0;
            } else {
                $postData['query_start_time'] = intval($postData['query_start_time']);
            }
        }

        if (isset($postData['query_start_urls'])) {
            if (is_array($postData['query_start_urls'])) {
                $urls = array_map('trim', $postData['query_start_urls']);
                $urls = array_values(array_filter($urls, function ($url) {
                    return $url !== '';
                }));
                $postData['query_start_urls'] = implode(',', array_unique($urls));
            } else {
                $postData['query_start_urls'] = trim((string)$postData['query_start_urls']);
            }
        }

        return $postData;
    }

    /**
     * 修改账号密码
     * @return array|mixed|string
     */
    public function updatePassword()
    {
        $postData = input();
        try { $this->validate($postData,'app\management\validate\VAuth.UpdatePassword');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $update['manager_id'] = $postData['manager_id'];
        $update['password'] = $postData['password'];
        $result = $this->app->authManager->update($update);
        return $result;
    }

    /**
     * 修改自己密码
     * @return array|\think\response\Json
     */
    public function updateSelfPwd()
    {
        $postData = input();
        try {
            $this->validate($postData, VAuth::class . '.UpdateSelfPwd');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->authManager->updateSelfPwd($postData);
    }

    /**
     * 删除管理员
     * @return mixed
     */
    public function del()
    {
        $postData = input();
        $this->app->authManager->startTrans();
        try {
            $result = $this->app->authManager->del(['manager_id' => $postData['manager_id']], 0);
            if ($result) {
                $this->app->authManagerMachine->delAuthManagerMachine(['manager_id' => $postData['manager_id']]);
                $this->app->authManagerRole->delAuthManagerRole(["manager_id" => $postData['manager_id']]);
            }
            return $this->app->authManager->checkTrans($result);
        } catch (\Exception $e) {
            $this->app->authManager->rollbackTrans();
            actionException($e,1);
            return $this->app->authManager->rValidate($e->getMessage());
        }
    }

    /**
     * 工作人员绑定公众号接收微信模板消息通知
     * @return array|bool|string
     */
    public function bindWx()
    {
        $manager_id = input('manager_id');
        if (!$manager_id) return returnState(100,'账号ID不能为空');
        $unbind = input("unbind");
        $result = $this->app->authManager->getWxQr($manager_id,$unbind);
        return $result;
    }

    //查询分账记录
    public function getAuthOrgRevenueLogs(){
        $postData = input();
        $pageNum = $postData['pageNum'] ?? '';
        $where = $this->getWhere($postData, false, []);
        return $this->app->authManager->getAuthOrgRevenueLogData($where, $pageNum);
    }

    //根据当前用户身份，返回对应的提现数据
    public function getWithDrawApply(){
        $postData = input();
        $pageNum = $postData['pageNum'] ?? '';

        if($this->manager['audit_status'] == 1){
             $where = $this->getWhere($postData, false, []);
        }else{
            //找提交人
            $strategy_managers = $this->app->strategyManager->getStrategyManagerColumnDatas(['s_type' => 2], 'manager_id');
            if(empty($strategy_managers)) $where['ao_id'] = ''; 
            if(in_array($this->manager['manager_id'], $strategy_managers)){
                $where['ao_id'] = $this->manager['ao_id'];
            }
        }
        return $this->app->authManager->getAuthWithdrawRequestData($where, $pageNum);
    }
    /**
     * 发起组织提现申请
     * 必填：amount, account, account_type（可选：remark）
     */
    public function applyWithdraw()
    {
        $postData = input();
        $amount = $postData['amount'] ?? 0;
        if (!$amount || $amount <= 0) return returnState(100,'提现金额必须大于0');
        $account_type = $postData['account_type'] ?? ''; 
        if (!$account_type) return returnState(100,'请选择提现类型');
        $account = $postData['account'] ?? '';
        if (!$account) return returnState(100,'提现账户不能为空');

        //查询当前账户ao_id关联的可提现manager_id
        $strategy_manager = $this->app->strategyManager->getStrategyManagerData(['s_type' => 2, 'ao_id' => $this->manager['ao_id']]);
        if(!$strategy_manager) return returnState(100,'查不到分账账户信息');
        $strategy_manager = $strategy_manager->toArray();
        if($strategy_manager['manager_id'] != $this->manager['manager_id']) return returnState(100,'没有权限，请联系管理员');

        $remark = $postData['remark'] ?? '';
      
        $insert = [
            'ao_id' => $this->manager['ao_id'] ?? 0,
            'requester_manager_id' => $this->manager['manager_id'] ?? 0,
            'amount' => $amount,
            'account' => $account,
            'account_type' => $account_type,
            'remark' => $remark,
            'status' => 1,
            'creator' => $this->manager['manager_id'] ?? 0,
        ];
        $this->app->authManager->startTrans();
        try {
            $wr_id = $this->app->authManager->addAuthWithdrawRequestData($insert);
            if (!$wr_id) throw new \Exception('创建提现申请失败');
            return $this->app->authManager->checkTrans($wr_id);
        } catch (\Exception $e) {
            $this->app->authManager->rollbackTrans();
            actionException($e,1);
            return returnState($e->getMessage());
        }
    }

    /**
     * 取消提现审核
     */
    public function cancelApplyWithdraw(){
        $strategy_managers = $this->app->strategyManager->getStrategyManagerColumnDatas(['s_type' => 2], 'manager_id');
        if(empty($strategy_managers)) return returnState(100, 'failed', ['无权限操作']);
        if(in_array($this->manager['manager_id'], $strategy_managers)){
            $postData = input();
            return returnState(200,'success', $this->app->strategyManager->updateStrategyManagerData($postData,['wr_id' => $postData['wr_id']]));
        }
    }

    /**
     * 审核提现申请
     * 必填：wr_id, status(2通过,3拒绝)
     */
    public function auditWithdraw()
    {
        $postData = input();
        $wr_id = $postData['wr_id'] ?? 0;
        $status = $postData['status'] ?? 0;
        $remark = $postData['remark'] ?? '';
        if (!$wr_id) return returnState(100,'申请ID不能为空');
        if (!in_array($status,[2,3])) return returnState(100,'状态值不合法');

        $this->app->authManager->startTrans();
        try {
            $res = $this->app->authManager->auditAuthWithdrawRequest($wr_id, $this->manager['manager_id'], $status, $remark);
            if (!$res) throw new \Exception('审核失败');
            return $this->app->authManager->checkTrans($res);
        } catch (\Exception $e) {
            $this->app->authManager->rollbackTrans();
            actionException($e,1);
            return returnState($e->getMessage());
        }
    }

    /**
     * 组织分账日志列表（管理端）
     */
    public function getOrgRevenueList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, [], 'a.');
        if (isset($where['ao_id'])) {
            $where['a.ao_id'] = $where['ao_id'];
            unset($where['ao_id']);
        }
        $field = 'a.*';
        $result = $this->app->authManager->getAuthOrgRevenueLogData($where, $pageNum, $field, 'create_time desc');
        return $result;
    }

    /**
     * 导出组织分账日志（返回CSV字符串，前端可以下载）
     */
    public function exportOrgRevenue()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, [], 'a.');
        if (isset($where['ao_id'])) {
            $where['a.ao_id'] = $where['ao_id'];
            unset($where['ao_id']);
        }
        $field = 'a.*';
        // fetch all
        $res = $this->app->authManager->getAuthOrgRevenueLogData($where, 0, $field, 'create_time desc');
        if ($res['code'] != 200) return $res;
        $list = $res['data'] ?? [];
        // build CSV header and rows
        $headers = ['aor_id','ao_id','order_id','order_no','sod_id','sod_uid','machine_id','machine_sn','machine_channel_code','si_id','income_value','income_amount','status','create_time','process_time'];
        $lines = [];
        $lines[] = implode(',', $headers);
        foreach ($list as $row) {
            $cols = [];
            foreach ($headers as $h) {
                $val = isset($row[$h]) ? $row[$h] : '';
                // escape double quotes
                $val = str_replace('"', '""', (string)$val);
                // wrap with quotes
                $cols[] = '"' . $val . '"';
            }
            $lines[] = implode(',', $cols);
        }
        $csv = implode("\n", $lines);
        return returnState(200, 'success', ['csv' => $csv]);
    }

    /**
     * 导出账户列表（返回CSV字符串，前端可以下载）
     */
    public function exportAccount()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['nickname' => "like"], 'au.');
        if (isset($where['ao_id'])) {
            $where['au.ao_id'] = $where['ao_id'];
            unset($where['ao_id']);
        }
        return $this->app->authManager->exportAccount($where);
    }

    /**
     * 获取账号通知配置（故障模板）
     */
    public function getNoticeConfig()
    {
        $postData = input();
        $managerId = intval($postData['manager_id'] ?? 0);
        $noticeType = strval($postData['notice_type'] ?? 'mFault');
        if ($managerId <= 0) {
            return returnState(100, 'manager_id不能为空');
        }
        return $this->app->authManager->getNoticeConfig($managerId, $noticeType);
    }

    /**
     * 保存账号通知配置（故障模板）
     */
    public function saveNoticeConfig()
    {
        $postData = input();
        $managerId = intval($postData['manager_id'] ?? 0);
        if ($managerId <= 0) {
            return returnState(100, 'manager_id不能为空');
        }
        return $this->app->authManager->saveNoticeConfig($postData);
    }
}
