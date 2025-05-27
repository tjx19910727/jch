<?php
declare (strict_types = 1);

namespace app\AppFactory\Kernel\Model\Trip;

use app\AppFactory\Kernel\Model\BaseModel;

class TripMultipleMachineModel extends BaseModel
{
    protected $pk = "tmm_id";
    protected $name = "trip_multiple_machine";
}
