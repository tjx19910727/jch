<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/24
 * Time: 10:48
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\GatewayWorker\GatewayWorkerTrait;
use app\AppFactory\Machine\MachineBaseClient;

class ReceiveBaseClient extends MachineBaseClient
{
    use GatewayWorkerTrait;

    protected $client_id = "";
    protected $message = [];

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->machine['last_online_time'] = time();
        $this->machine['online'] = 1;
        $this->updateMachine(['m_id' => $this->machine['m_id'],'last_online_time' => time(),'online' => 1]);
    }
}