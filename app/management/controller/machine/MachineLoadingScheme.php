<?php

namespace app\management\controller\machine;

use app\management\controller\Common;

class MachineLoadingScheme extends Common
{
    protected $validatePath = '';

    public function getGoodsList()
    {
        return $this->app->machineLoadingScheme->getGoodsList();
    }

    public function getDetail()
    {
        return $this->app->machineLoadingScheme->getDetail();
    }

    public function save()
    {
        return $this->app->machineLoadingScheme->save();
    }
}

