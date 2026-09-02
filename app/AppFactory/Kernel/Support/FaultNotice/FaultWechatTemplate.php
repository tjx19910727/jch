<?php

namespace app\AppFactory\Kernel\Support\FaultNotice;

/**
 * 固定故障微信模板配置读取器。
 */
class FaultWechatTemplate
{
    public static function all()
    {
        $templates = FaultNoticeConfig::wechatTemplates();
        return is_array($templates) ? $templates : [];
    }

    public static function types()
    {
        return array_keys(self::all());
    }

    public static function find($templateType)
    {
        $templateType = strval($templateType);
        $templates = self::all();
        return isset($templates[$templateType]) && is_array($templates[$templateType])
            ? $templates[$templateType]
            : [];
    }

    /**
     * 根据当前环境取得实际发送使用的微信模板ID。
     */
    public static function getTemplateId($templateType)
    {
        $template = self::find($templateType);
        $field = filter_var(env('CglPay.is_test', false), FILTER_VALIDATE_BOOLEAN)
            ? 'test_template_id'
            : 'template_id';
        return trim(strval($template[$field] ?? ''));
    }

    public static function options()
    {
        $items = [];
        foreach (self::all() as $type => $template) {
            if (!self::isValid($type)) {
                continue;
            }
            $items[] = [
                'template_type' => strval($type),
                'template_name' => strval($template['template_name'] ?? ''),
                'display_name' => strval($template['display_name'] ?? ($template['template_name'] ?? '')),
                'template_no' => strval($template['template_no'] ?? ''),
            ];
        }
        return $items;
    }

    public static function isFaultType($templateType)
    {
        return in_array(strval($templateType), self::types(), true);
    }

    public static function isValid($templateType)
    {
        $template = self::find($templateType);
        return $template
            && self::getTemplateId($templateType) !== ''
            && !empty($template['body'])
            && is_array($template['body']);
    }
}
