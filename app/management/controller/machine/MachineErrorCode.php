<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/30
 * Time: 14:13
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineErrorCode extends Common
{

    protected $field = "me_id,m_id,machine_id,machine_name,address,error_position,errorCode,remark,msg,create_time";

    protected $videoField = "me_id,m_id,machine_id,machine_name,address,error_position,errorCode,remark,msg,trade_no,transaction_video,create_time,creator_id";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['machine_id' => "like","errorCode" => "like"]);
        $where['status'] = 1;
        return $this->app->machineErrorCode->getEcList($where,$pageNum,$this->field,'create_time desc');
    }

    /**
     * 获取错误码类别列表
     * @return array|\think\response\Json
     */
    public function getTypeList()
    {
        $errCodeType = config("error_code_list");
        $list = [];
        foreach ($errCodeType as $key => $value) {
            $list[] = [
                "type" => $key,
                "name" => lang("deviceErrorCodeType." . $value['name']),
            ];
        }
        return returnState(200,lang("query_success"),$list);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['machine_id' => "like","errorCode" => "like"]);
        return $this->app->machineErrorCode->getFind($where,$this->field,'create_time desc');
    }

    /**
     * 确认已处理
     * @return array|mixed|\think\response\Json
     */
    public function confirmHandle()
    {
        $me_id = input('me_id');
        if (!$me_id) return returnValidate(lang("VMachineErrorCode.me_id_require"));
        return $this->app->machineErrorCode->confirmHandle($me_id);
    }

    /**
     * 获取系统日志
     * @return array|\think\response\Json
     */
    public function getErrorCodeLog()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $codeList = [];
        if (isset($postData['errorCodeType']) && $postData['errorCodeType']) {
            $errorCodeType = config("error_code_list");
            $codeList = $errorCodeType[$postData['errorCodeType']]['codeList'];
            unset($postData['errorCodeType']);
        }
        $where = $this->getWhere($postData, false, ['machine_id' => "like","errorCode" => "like"]);
        if ($codeList) $where[] = ['errorCode','in',$codeList];
        return $this->app->machineErrorCode->getEcList($where,$pageNum,$this->field,'create_time desc');
    }

    /**
     * 导出系统日志
     * @return array|bool|string|\think\response\Json
     */
    public function exportErrorCode()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, ['machine_id' => "like","errorCode" => "like"]);
        if (!isset($postData['create_time']) || !$postData['create_time']) $where[] = ['create_time','>=',strtotime("-1 month")];
        return $this->app->machineErrorCode->exportEc($where,$this->field,'create_time desc');
    }

    /**
     * 根据故障记录ID查询模板消息通知日志
     * wx_template_log.me_id = 传入me_id
     * @return array|\think\response\Json
     */
    public function getTemplateNoticeList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $me_id = $postData['me_id'] ?? 0;
        if (!$me_id) return returnValidate(lang("VMachineErrorCode.me_id_require"));
        $where = [
            'me_id' => intval($me_id),
        ];
        return $this->app->wxTemplateLog->getTemplateLogList($where, $pageNum, '*', 'create_time desc');
    }

    
    /**
     * 后台视频统一入口列表，新开接口，方便权限控制
     * 当前仅支持营业逻辑中柜门打开（1200000、1200010、1200020）
     * @return array|\think\response\Json
     */
    public function getVideoList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['machine_id' => "like", "errorCode" => "like"]);
        $where['status'] = 1;
        $where[] = ['errorCode', 'in', [1200000, 1200010, 1200020]]; // 营业逻辑中柜门打开的错误码
        return $this->app->machineErrorCode->getEcVideoList($where, $pageNum, $this->videoField, 'create_time desc');
    }
}