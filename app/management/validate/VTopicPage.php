<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/10
 * Time: 11:43
 */

namespace app\management\validate;


class VTopicPage extends VCommon
{
    protected $rule = [
        'id' => 'require|number|gt:0',
        'status' => 'require|in:1,2',
        'title' => 'require',
    ];

    protected $message = [
        'id.require' => 'VTopicPage.id_require',
        'id.number' => 'VTopicPage.id_number',
        'id.gt' => 'VTopicPage.id_gt',
        'status.require' => 'VTopicPage.status_require',
        'status.in' => 'VTopicPage.status_in',
        'title.require' => 'VTopicPage.title_require',
    ];

    protected $scene = [
        'add' => ['status', 'title'],
        'update' => ['id', 'title'],
        'del' => ['id'],
        'assignMachine' => ['id'],
        'setStatus' => ['id', 'status'],
    ];
}
