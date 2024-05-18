<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 14:45
 */

namespace app\AppFactory\Management\Template;


use app\AppFactory\Kernel\Exceptions\ValidateException;
use app\AppFactory\Kernel\Traits\Template\TemplateViewTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\VTemplateView;

class TemplateViewClient extends ManagementClient
{
    use TemplateViewTrait;

    public function checkAdd($postData)
    {
//        $plugin_data = json2arr($postData['plugin_data']);
//        try {
//            validate(VTemplateView::class)->scene("plugin_data")->check($plugin_data);
//        } catch (ValidateException $e) {
//            return $this->rFail($e->getMessage());
//        }
        return $this->rA($this->addTemplateView($postData));
    }
}