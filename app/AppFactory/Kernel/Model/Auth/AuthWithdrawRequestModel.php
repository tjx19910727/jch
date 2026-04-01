<?php
/**
 * 组织提现申请表 Model
 */
namespace app\AppFactory\Kernel\Model\Auth;

use app\AppFactory\Kernel\Model\BaseModel;

class AuthWithdrawRequestModel extends BaseModel
{
    protected $table = 'auth_withdraw_requests';

    public function managerData()
    {
        return $this->hasOne(AuthManagerModel::class,"manager_id","requester_manager_id");
    }

    public function auditData()
    {
        return $this->hasOne(AuthManagerModel::class,"manager_id","manager_id");
    }
}
