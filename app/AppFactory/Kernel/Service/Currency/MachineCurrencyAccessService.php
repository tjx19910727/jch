<?php

namespace app\AppFactory\Kernel\Service\Currency;

use think\facade\Db;

class MachineCurrencyAccessService
{
    public function assertManagementAccess($mId, array $manager)
    {
        $machine = Db::name('machine')->where('m_id', intval($mId))
            ->field('m_id,machine_id,machine_name,ao_id,creator')->find();
        if (!$machine) {
            throw new \InvalidArgumentException('设备不存在');
        }
        if (intval(isset($manager['pid']) ? $manager['pid'] : 0) <= 0 || in_array(intval(isset($manager['ao_id']) ? $manager['ao_id'] : 0), [0, 1], true)) {
            return $machine;
        }
        $managerId = intval(isset($manager['manager_id']) ? $manager['manager_id'] : 0);
        $bound = Db::name('auth_manager_machine')->where(['manager_id' => $managerId, 'm_id' => intval($mId)])->count();
        if ($bound > 0 || intval($machine['creator']) === $managerId || intval($machine['ao_id']) === intval($manager['ao_id'])) {
            return $machine;
        }
        throw new \InvalidArgumentException('无权操作该设备');
    }
}
