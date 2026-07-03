<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/24
 */

namespace app\management\validate;


class VMachineVoiceTemplate extends VCommon
{
    protected $rule = [
        'id' => 'require|number|gt:0',
        'status' => 'require|in:1,2',
        'title' => 'require',
    ];

    protected $message = [
        'id.require' => 'VMachineVoiceTemplate.id_require',
        'id.number' => 'VMachineVoiceTemplate.id_number',
        'id.gt' => 'VMachineVoiceTemplate.id_gt',
        'status.require' => 'VMachineVoiceTemplate.status_require',
        'status.in' => 'VMachineVoiceTemplate.status_in',
        'title.require' => 'VMachineVoiceTemplate.title_require',
    ];

    protected $scene = [
        'add' => ['status', 'title'],
        'update' => ['id', 'title'],
        'del' => ['id'],
        'assignMachine' => ['id'],
        'setStatus' => ['id', 'status'],
        'copy' => ['id', 'title'],
    ];
}
