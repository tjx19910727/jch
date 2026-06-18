<?php

namespace app\AppFactory\Kernel\Providers\Management;

use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Inspection\InspectionStaffClient;

class InspectionProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['inspectionStaff'] = function ($app) {
            return new InspectionStaffClient($app);
        };
    }
}
