<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:43
 */

namespace app\AppFactory\Management\Earth;


use app\AppFactory\Kernel\Traits\Earth\EarthAreaTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthContinentsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthTimezoneTrait;
use app\AppFactory\Management\ManagementClient;

class EarthClient extends ManagementClient
{
    use EarthAreaTrait,EarthCitiesTrait,EarthContinentsTrait,EarthCountriesTrait,EarthRegionsTrait,EarthStatesTrait,EarthTimezoneTrait;
}