<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 12:00
 */
namespace app\AppFactory\Kernel\Model\RemoteActionLog;

use app\AppFactory\Kernel\Model\BaseModel;

class RemoteActionLogModel extends BaseModel
{
    /**
     * Primary key for the mall table
     *
     * @var string
     */
    protected $pk = "id";

    /**
     * Table name
     *
     * @var string
     */
    protected $name = "remote_action_log";

}