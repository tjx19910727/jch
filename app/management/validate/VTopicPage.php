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
        'is_service_phone' => 'in:1,2',
        'title' => 'require',
        'claim_goods_title' => 'require',
        'out_goods_title' => 'require',
        'deal_success_title' => 'require',
        'deal_success_sub_title' => 'require',
        'deal_abnormal_pic' => 'require',
        'deal_fail_title' => 'require',
        'deal_fail_sub_title' => 'require',
    ];

    protected $message = [
        'id.require' => 'VTopicPage.id_require',
        'id.number' => 'VTopicPage.id_number',
        'id.gt' => 'VTopicPage.id_gt',
        'status.require' => 'VTopicPage.status_require',
        'status.in' => 'VTopicPage.status_in',
        'title.require' => 'VTopicPage.title_require',
        'claim_goods_title.require' => 'VTopicPage.claim_goods_title_require',
        'out_goods_title.require' => 'VTopicPage.out_goods_title_require',
        'deal_success_title.require' => 'VTopicPage.deal_success_title_require',
        'deal_success_sub_title.require' => 'VTopicPage.deal_success_sub_title_require',
        'deal_abnormal_pic.require' => 'VTopicPage.deal_abnormal_pic_require',
        'deal_fail_title.require' => 'VTopicPage.deal_fail_title_require',
        'deal_fail_sub_title.require' => 'VTopicPage.deal_fail_sub_title_require',
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
