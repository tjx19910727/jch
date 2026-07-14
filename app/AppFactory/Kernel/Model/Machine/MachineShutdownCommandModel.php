<?php

namespace app\AppFactory\Kernel\Model\Machine;

use app\AppFactory\Kernel\Model\BaseModel;

class MachineShutdownCommandModel extends BaseModel
{
    protected $pk = 'msc_id';
    protected $name = 'machine_shutdown_command';
}
