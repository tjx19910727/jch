<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/3
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineAppSettingsModel;

trait MachineAppSettingsTrait
{
    public function getMachineAppSettingsValue($where, $field, $order = '')
    {
        return MachineAppSettingsModel::getFieldValue($where, $field, $order);
    }

    public function getMachineAppSettingsFind($where, $field = '*', $order = '')
    {
        return MachineAppSettingsModel::getFind($where, $field, $order);
    }

    public function getMachineAppSettingsList($where, $pageNum = 0, $field = '*', $order = '', $eachFun = '')
    {
        return MachineAppSettingsModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addMachineAppSettings($insert)
    {
        if (!isset($insert['manager_id'])) {
            $insert['manager_id'] = $this->manager['manager_id'] ?? 0;
        }
        $data = MachineAppSettingsModel::create($insert);
        return $data->id;
    }

    public function updateMachineAppSettings($update, $where = [], $field = [])
    {
        if (!isset($update['manager_id']) && isset($this->manager['manager_id'])) {
            $update['manager_id'] = $this->manager['manager_id'];
        }
        return MachineAppSettingsModel::update($update, $where, $field);
    }

    public function delMachineAppSettings($where)
    {
        return MachineAppSettingsModel::whereDel($where);
    }

    public function getMachineAppSettingsFieldMap($type = 1)
    {
        $map = [
            1 => [
                'home_anim_enabled' => [
                    'name' => '首页动画状态',
                    'value_type' => 'int',
                    'default' => '2',
                    'desc' => '1开启，2关闭',
                ],
                'home_anim_style' => [
                    'name' => '首页动画样式',
                    'value_type' => 'int',
                    'default' => '1',
                    'desc' => '1:S形，2:直线',
                ],
                'home_anim_volume' => [
                    'name' => '首页动画音量',
                    'value_type' => 'int',
                    'default' => '70',
                    'desc' => '1-100',
                ],
                'purchase_voice_enabled' => [
                    'name' => '购买语音状态',
                    'value_type' => 'int',
                    'default' => '1',
                    'desc' => '1开启，2关闭',
                ],
                'purchase_voice_text' => [
                    'name' => '购买语音自定义文字',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '购买语音自定义文字',
                ],
                'purchase_voice_text_zh_hant' => [
                    'name' => '购买语音自定义文字(繁体)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '购买语音自定义文字繁体',
                ],
                'purchase_voice_text_en' => [
                    'name' => '购买语音自定义文字(英文)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '购买语音自定义文字英文',
                ],
                'purchase_voice_file_path' => [
                    'name' => '购买语音文件',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '购买语音文件路径',
                ],
                'purchase_voice_file_path_zh_hant' => [
                    'name' => '购买语音文件(繁体)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '购买语音文件繁体',
                ],
                'purchase_voice_file_path_en' => [
                    'name' => '购买语音文件(英文)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '购买语音文件英文',
                ],
                'dispense_voice_enabled' => [
                    'name' => '出货语音状态',
                    'value_type' => 'int',
                    'default' => '1',
                    'desc' => '1开启，2关闭',
                ],
                'dispense_voice_text' => [
                    'name' => '出货语音自定义文字',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '出货语音自定义文字',
                ],
                'dispense_voice_text_zh_hant' => [
                    'name' => '出货语音自定义文字(繁体)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '出货语音自定义文字繁体',
                ],
                'dispense_voice_text_en' => [
                    'name' => '出货语音自定义文字(英文)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '出货语音自定义文字英文',
                ],
                'dispense_voice_file_path' => [
                    'name' => '出货语音文件',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '出货语音文件',
                ],
                'dispense_voice_file_path_zh_hant' => [
                    'name' => '出货语音文件(繁体)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '出货语音文件繁体',
                ],
                'dispense_voice_file_path_en' => [
                    'name' => '出货语音文件(英文)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '出货语音文件英文',
                ],
                'pickup_voice_enabled' => [
                    'name' => '取货语音状态',
                    'value_type' => 'int',
                    'default' => '1',
                    'desc' => '1开启，2关闭',
                ],
                'pickup_voice_text' => [
                    'name' => '取货语音自定义文字',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '取货语音自定义文字',
                ],
                'pickup_voice_text_zh_hant' => [
                    'name' => '取货语音自定义文字(繁体)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '取货语音自定义文字繁体',
                ],
                'pickup_voice_text_en' => [
                    'name' => '取货语音自定义文字(英文)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '取货语音自定义文字英文',
                ],
                'pickup_voice_file_path' => [
                    'name' => '取货语音文件',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '取货语音文件',
                ],
                'pickup_voice_file_path_zh_hant' => [
                    'name' => '取货语音文件(繁体)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '取货语音文件繁体',
                ],
                'pickup_voice_file_path_en' => [
                    'name' => '取货语音文件(英文)',
                    'value_type' => 'string',
                    'default' => '',
                    'desc' => '取货语音文件英文',
                ],
                'ad_show_goods_enabled' => [
                    'name' => '广告页是否显示商品',
                    'value_type' => 'int',
                    'default' => '1',
                    'desc' => '1开启，2关闭',
                ],
                'ad_goods_jump_target' => [
                    'name' => '广告页商品跳转',
                    'value_type' => 'int',
                    'default' => '1',
                    'desc' => '1商品详情页，2首页',
                ],
            ],
        ];
        return $map[$type] ?? [];
    }
}
