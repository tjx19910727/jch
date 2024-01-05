<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:22
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthManagerModel;

trait AuthManagerTrait
{
    public function getAuthManagerValue($where,$value)
    {
        return AuthManagerModel::getFieldValue($where,$value);
    }

    public function getAuthManagerFind($where,$field = "*",$order = "")
    {
        if (isset($this->manager) && $this->manager && !isset($where['manager_id'])) $where[] = ['manager_id','=',$this->manager['manager_id']];
        return AuthManagerModel::getFind($where,$field,$order);
    }

    public function getAuthManagerColumn($where,$column)
    {
        return AuthManagerModel::getColumn($where,$column);
    }

    public function getAuthManagerList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $result = AuthManagerModel::getList($where,$pageNum,$field,$order);
        return $result;
    }

    public function addAuthManager($insert)
    {
        if (isset($insert['password'])) $insert['password'] = md5($insert['password'] . $this->salt);
        $insert['creator'] = $this->manager['manager_id'];
        $insert['pid'] = $this->manager['manager_id'];
        $insert['level'] = $this->manager['level'] + 1;
        $data = AuthManagerModel::create($insert);
        return $data->manager_id;
    }

    public function updateAuthManager($update,$where = [],$field = [])
    {
        if (isset($update['password'])) $update['password'] = md5($update['password'] . $this->salt);
        if (!isset($update['update_id'])) $update['update_id'] = $this->manager['manager_id'] ?? 0;
        return AuthManagerModel::update($update,$where,$field);
    }

    public function delAuthManager($where)
    {
        return AuthManagerModel::destroy($where);
    }

    public function incAuthManager($where,$field, $inc = 1)
    {
        return AuthManagerModel::setInc($where,$field,$inc);
    }

    public function decAuthManager($where,$field,$dec = 1)
    {
        return AuthManagerModel::setDec($where,$field,$dec);
    }


    /**
     * 获取下级账号ID列表
     * @param $pid
     * @param array $ids
     * @param int $level
     * @param int $maxLevel
     * @return array
     */
    public function getChildIdList($pid,$ids = [],$level = 1,$maxLevel = 999)
    {
        $level++;
        if ($maxLevel < $level) return $ids;
        $where['pid'] = $pid;
        $where['level'] = $level;
        $childId = $this->getAuthManagerList($where,0,'manager_id,level');
        if ($childId) {
            $childId = $childId->toArray();
            foreach ($childId as $value) {
                $ids[] = $value['manager_id'];
                $ids = $this->getChildIdList($value['manager_id'],$ids,$value['level'],$maxLevel);
            }
            return $ids;
        }
        return $ids;
    }

    /**
     * 获取上级链账号ID列表
     * @param $pid
     * @param array $ids
     * @return array
     */
    public function getParentIdList($pid,$ids = [])
    {
        $where = [];
        $where['manager_id'] = $pid;
        $parent = $this->getAuthManagerFind($where,'manager_id,pid');
        if ($parent) {
            $ids[] = $parent['manager_id'];
            if ($parent['pid']) {
                $ids = $this->getParentIdList($parent['pid'],$ids);
            }
            return $ids;
        }
        return $ids;
    }
}