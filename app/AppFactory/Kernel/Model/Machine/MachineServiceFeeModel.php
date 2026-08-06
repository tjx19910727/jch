<?php

namespace app\AppFactory\Kernel\Model\Machine;

use app\AppFactory\Kernel\Model\BaseModel;

/**
 * 每台设备当前生效的服务年费及服务期限。
 *
 * 未生成本记录的历史设备在后台展示为“未配置”；设备端限制本阶段未启用。
 */
class MachineServiceFeeModel extends BaseModel
{
    protected $pk = 'msf_id';
    protected $name = 'machine_service_fee';
}
