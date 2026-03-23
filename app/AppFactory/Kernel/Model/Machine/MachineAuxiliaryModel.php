<?php
/**
 * Created by VSCode.
 * User: lgf
 * Date: 2025/12/08
 * Time: 12:00
 */

namespace app\AppFactory\Kernel\Model\Machine;

use app\AppFactory\Kernel\Model\BaseModel;

class MachineAuxiliaryModel extends BaseModel
{
    /**
     * Primary key for the machine_auxiliary table
     *
     * @var string
     */
    protected $pk = "m_id";

    /**
     * Table name
     *
     * @var string
     */
    protected $name = "machine_auxiliary";
}