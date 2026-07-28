<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/27
 * Time: 16:49
 */

namespace app\AppFactory\Management\Export;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerRoleTrait;
use app\AppFactory\Kernel\Traits\Export\ExportLogTrait;
use app\AppFactory\Management\ManagementClient;

class ExportLogClient extends ManagementClient
{
    use ExportLogTrait, AuthManagerRoleTrait;

    const SUPER_ADMIN_ROLE_ID = 1;

    /**
     * 超级管理员可查询全部导出记录，其他账号只能查询本人创建的记录。
     *
     * @param array $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param int $rQ
     * @return mixed
     */
    public function getList($where = [], $pageNum = 0, $field = "*", $order = "", $rQ = 1)
    {
        if (!$this->hasSuperAdminRole()) {
            $where['creator'] = intval($this->manager['manager_id']);
        }
        $data = $this->getExportLogList($where, $pageNum, $field, $order);
        return $rQ ? $this->rQ($data) : $data;
    }

    /**
     * @return bool
     */
    protected function hasSuperAdminRole()
    {
        $role = $this->getAuthManagerRoleFind([
            'manager_id' => intval($this->manager['manager_id']),
            'role_id' => self::SUPER_ADMIN_ROLE_ID,
        ], 'mr_id');
        return !empty($role);
    }
}
