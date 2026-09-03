<?php

namespace app\management\controller\machine;

use app\management\controller\Common;

class MachineChannelTemplate extends Common
{
    protected $validatePath = '';

    public function getList()
    {
        return $this->app->machineChannelTemplate->getList();
    }

    public function getDetail()
    {
        return $this->app->machineChannelTemplate->getDetail();
    }

    public function save()
    {
        return $this->app->machineChannelTemplate->save();
    }

    public function del()
    {
        return $this->app->machineChannelTemplate->del();
    }
}

