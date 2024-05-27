<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:11
 */

namespace app\AppFactory\Management;


use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;
use app\AppFactory\Kernel\Traits\ManagementTrait;
use think\facade\Config;

class ManagementClient extends BaseClient
{
    use ConfigTrait;
    use ManagementTrait;

    /**
     * @var Application
     */
    protected $app;
    protected $manager;
    protected $salt;
    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->manager = $this->app->getConfig();
        $this->salt = Config::get('app.salt');
        $this->systemInfo = $this->getSystemInfo();


        $this->ignoreList = (config("auth_manager_log_list.ignore")['management'] ?? []);
        $this->apiUrl = request()->action();
        $this->recordManagerLog($this->manager);
    }



}