<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:14
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthNodeTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthRoleNodeTrait;
use app\AppFactory\Management\ManagementClient;

class AuthNodeClient extends ManagementClient
{
    use AuthNodeTrait,AuthRoleNodeTrait;

    /**
     * 删除节点
     * @param $node_id
     * @return array|bool|string|\think\response\Json
     */
    public function delNode($node_id)
    {
        try {
            $this->startTrans();
            $flag[] = $this->delAuthNode(['node_id' => $node_id]);
            $flag[] = $this->delChild($node_id);
            $flag[] = $this->updateAuthRoleNode(['is_del' => 1], ['node_id' => $node_id]);
            $result = flag_check($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 递归删除子节点
     * @param $node_id
     * @return array|bool|string|\think\response\Json
     */
    protected function delChild($node_id)
    {
        $result = true;
        $child = $this->getAuthNodeList(['pid' => $node_id]);
        if ($child) {
            foreach ($child as $key => $value) {
                $result = $this->delChild($value['node_id']);
                if ($result) {
                    $result = $this->delAuthNode(['node_id' => $value['node_id']]);
                    if ($result)
                        continue;
                }
                break;
            }
        }
        return $result;
    }
}