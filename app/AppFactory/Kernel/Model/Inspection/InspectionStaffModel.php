<?php

namespace app\AppFactory\Kernel\Model\Inspection;

use app\AppFactory\Kernel\Model\BaseModel;

class InspectionStaffModel extends BaseModel
{
    protected $pk = 'staff_id';
    protected $name = 'inspection_staff';

    protected $schema = [
        'staff_id' => 'int',
        'staff_code' => 'string',
        'account_name' => 'string',
        'mobile' => 'string',
        'contact_address' => 'string',
        'expire_time' => 'int',
        'ao_id' => 'int',
        'status' => 'int',
        'remark' => 'string',
        'creator' => 'int',
        'update_id' => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
    ];
}
