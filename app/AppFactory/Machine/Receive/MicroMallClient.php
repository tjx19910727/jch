<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/10
 * Time: 10:09
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\MicroMall\MicroMallMachineTrait;
use app\AppFactory\Kernel\Traits\MicroMall\MicroMallTrait;
use app\AppFactory\Machine\MachineBaseClient;

class MicroMallClient extends MachineBaseClient
{
    use MicroMallTrait,MicroMallMachineTrait;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->dataRecord();
    }

    /**
     * 销毁实例时触发
     */
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $result = $this->updateMachineMqRecord(['status' => 2, 'msg_id' => $this->data['msg_id']], ['msg_id' => $this->data['msg_id']]);
        actionLog($result, '处理完成时修改状态为已处理');
    }

    public function getMall()
    {
        $where['m_id'] = $this->machine['m_id'];
        $mm_ids = $this->getMicroMallMachineColumn($where,"mm_id");
        $data = $this->getMicroMallFind([['mm_id','in',$mm_ids]],'*','bind_id');
        if ($data) {
            return $this->r(200,$this->lang("query_success"),$data);
        }
        return $this->r(100,$this->lang("query_fail"));
    }

}