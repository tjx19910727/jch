<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 17:51
 */

namespace app\management\validate\Machine;


use app\management\validate\VCommon;

class VMachineChannel extends VCommon
{
    protected $rule = [
        "mc_id" => "require",
        "m_id" => "require",
        "machine_id" => "require",
        "channel_code" => "require",
        "mc_ids" => "require|checkMcIds",
    ];

    protected $message = [
        "mc_id.require" => "VMachineChannel.mc_id_require",
        "m_id.require" => "VMachineChannel.m_id_require",
        "machine_id.require" => "VMachineChannel.machine_id_require",
        "channel_code.require" => "VMachineChannel.channel_code_require",
        "mc_ids.require" => "VMachineChannel.mc_id_require",
        "mc_ids.checkMcIds" => "货道ID必须是数组或英文逗号分隔的正整数ID",
    ];

    protected $scene = [
        'interruptRemoteRemoval' => ['mc_id'],
        "add" => ["m_id", "machine_id", "channel_code"],
        "update" => ["mc_id"],
        "updateAll" => ["mc_ids"],
        "remoteRemoval" => ["mc_id"],
        "del" => ["mc_id"],
    ];

    /**
     * 批量货道ID校验：兼容数组与英文逗号分隔字符串（历史调用方一直传 "1,2,3"），
     * 要求至少一个正整数，避免全非法值穿透到业务层。
     * @param mixed $value
     * @return bool
     */
    public function checkMcIds($value)
    {
        if (is_array($value)) {
            $ids = $value;
        } elseif (is_scalar($value)) {
            // 字符串（含英文逗号分隔）与单个数字都放行，归一化由客户端统一处理。
            $ids = explode(',', (string)$value);
        } else {
            return false;
        }
        $valid = 0;
        foreach ($ids as $id) {
            if (intval(trim((string)$id)) <= 0) {
                return false;
            }
            $valid++;
        }
        return $valid > 0;
    }
}
