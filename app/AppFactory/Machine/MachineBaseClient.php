<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 20:12
 */

namespace app\AppFactory\Machine;


use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;

class MachineBaseClient extends BaseClient
{
    use MachineTrait;

    public $machine;
    public $data;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);

        $this->machine = $this->getMachineFind(['machine_id' => $this->config['machine_id']]);
        if (!$this->machine) return $this->rFail($this->lang("query_machine_no_data"));
    }


}