<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/8/3
 * Time: 9:42
 */

namespace app\AppFactory\Kernel\Traits\OpenPlatform;


use app\AppFactory\Kernel\Model\OpenPlatform\OpenPlatformWxTemplateModel;

trait OpenPlatformWxTemplateTrait
{
    public function getOpenPlatformWxTemplateList($where,$pageNum = 0, $field = '*',$order = 'wxt_id desc')
    {
        return OpenPlatformWxTemplateModel::getList($where,$pageNum,$field,$order);
    }

    public function getOpenPlatformWxTemplateFind($where,$field = '*',$order = 'wxt_id desc')
    {
        return OpenPlatformWxTemplateModel::getFind($where,$field,$order);
    }

    public function addOpenPlatformWxTemplate($insert)
    {
        if (isset($this->manager['manager_id']) && !isset($insert['creator'])) $insert['creator'] = $this->manager['manager_id'];
        $wxt = OpenPlatformWxTemplateModel::create($insert);
        return $wxt->wxt_id;
    }

    public function updateOpenPlatformWxTemplate($update,array $where = [], array $field = [])
    {
        if (isset($this->manager['manager_id']) && !isset($update['update_id'])) $update['update_id'] = $this->manager['manager_id'];
        return OpenPlatformWxTemplateModel::update($update,$where,$field);
    }

    /**
     * 赋值模板消息数据
     * @param $params
     * @param array $data
     * @return mixed
     */
    public function replaceTemplateData($params,$data = [])
    {
        $tempData = json2arr($this->temp['data']);
        if (!$tempData) return $this->rFail('缺少模板内容');
        foreach ($tempData as $key => $value) {
            try {
                if ($value) {
                    // value值为变量名，直接赋值
                    if (isset($data[$value])) {
                        $params['data'][$key]['value'] = $data[$value];
                    } else {
                        // 模板消息内容有固定字符串，检查字符串里是否有符合的变量，有则替换数据
                        foreach ($data as $dk => $dv) {
                            if (strpos($value,"{{". $dk . "}}") !== false) {
                                $value = str_replace("{{" . $dk . "}}",$dv,$value);
                            }
                        }
                        // 固定字符串赋值
                        $params['data'][$key]['value'] = $value;
                    }
                }
            } catch (\Exception $e) {
                return $this->rFail("缺少参数：" . $value);
            }
        }
        return $params;
    }

}