<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/17
 * Time: 9:58
 */

namespace app\AppFactory\Management\Suggest;


use app\AppFactory\Kernel\Traits\Suggest\SuggestTrait;
use app\AppFactory\Management\ManagementClient;

class SuggestClient extends ManagementClient
{
    use SuggestTrait;
}