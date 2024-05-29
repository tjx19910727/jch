<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/22
 * Time: 11:30
 */

namespace app\AppFactory\Kernel\Traits\Auth;



use app\AppFactory\Kernel\Model\Auth\AuthManagerLogModel;

trait AuthManagerLogTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getAuthManagerLogValue($where, $value)
    {
        return AuthManagerLogModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getAuthManagerLogColumn($where, $column)
    {
        return AuthManagerLogModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getAuthManagerLogCount($where)
    {
        return AuthManagerLogModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFun
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getAuthManagerLogList($where, $pageNum = 0, $field = "*", $order = "",$eachFun = "")
    {
        return AuthManagerLogModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getAuthManagerLogFind($where, $field = "*", $order = "")
    {
        return AuthManagerLogModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addAuthManagerLog($insert)
    {
        $data = AuthManagerLogModel::create($insert);
        return $data->getKey();
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return AuthManagerLogModel
     */
    public function updateAuthManagerLog($update,$where = [],$field = [])
    {
        return AuthManagerLogModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delAuthManagerLog($where)
    {
        return AuthManagerLogModel::whereDel($where);
    }

    // url转事件类型
    protected $log_type = [
        "/machine/receive/login" => "login",
    ];

    // 忽略不记的API地址列表
    protected $ignoreList = [];
    // API地址
    protected $apiUrl = "";
    /**
     * 用户操作事件记录
     * @param $manager
     * @return mixed
     */
    public function recordManagerLog($manager = [])
    {
        if (!$this->apiUrl) $this->apiUrl = request()->baseUrl();
        if ($this->apiUrl) {
            if (!in_array($this->apiUrl,$this->ignoreList)) {
                $params = input();
                $path = request()->baseUrl();
                $log = $this->getAuthManagerLogFind(['path' => $path,['create_time','>=',bcsub(time(),3)]],'ml_id');
                if (!$log) {
                    if (!$manager) {
                        $where = [];
                        if (isset($params['account'])) $where['account'] = $params['account'];
                        if (isset($params['operator'])) $where['manager_id'] = $params['operator'];
                        if (isset($params['manager_id'])) $where['manager_id'] = $params['manager_id'];
                        if ($where) $manager = $this->getAuthManagerFind($where, 'manager_id,nickname,account,ao_id');
                    }
                    if ($manager) {
                        $params = json_encode($params, 320);
                        $params = (strlen($params) <= 1024 ? $params : substr($params, 0, 1024));
                        $log = [
                            "ao_id" => $manager['ao_id'] ?? 0,
                            "manager_id" => $manager['manager_id'] ?? 0,
                            "nickname" => $manager['nickname'] ?? "",
                            "account" => $manager['account'] ?? "",
                            "path" => $path,
                            "params" => $params,
                        ];
                        @$this->addAuthManagerLog($log);
                    }
                }
            }
        }
    }
}