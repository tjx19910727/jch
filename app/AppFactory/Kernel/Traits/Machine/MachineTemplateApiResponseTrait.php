<?php

namespace app\AppFactory\Kernel\Traits\Machine;

trait MachineTemplateApiResponseTrait
{
    protected function templateApiResponse($state, $msg = 'success', $data = null)
    {
        return json([
            'state' => intval($state),
            'code' => intval($state),
            'msg' => (string)$msg,
            'data' => $data,
        ]);
    }

    protected function templateApiSuccess($data = null, $msg = 'success')
    {
        return $this->templateApiResponse(200, $msg, $data);
    }

    protected function templateApiError($state, $msg, $data = null)
    {
        return $this->templateApiResponse($state, $msg, $data);
    }
}

