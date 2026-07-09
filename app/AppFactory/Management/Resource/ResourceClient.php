<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/10
 * Time: 14:16
 */

namespace app\AppFactory\Management\Resource;

use app\AppFactory\Kernel\Model\Resource\ResourceModel;
use app\AppFactory\Kernel\Traits\Resource\ResourceTrait;
use app\AppFactory\Management\ManagementClient;

class ResourceClient extends ManagementClient
{
    use ResourceTrait;

    /**
     * 素材更新（过滤无用参数，自动设置更新人和更新时间）
     * @param array $data 前端传入的原始数据
     * @return mixed
     */
    public function updateResourceData($data)
    {
        // 先查询素材是否存在
        $resource = ResourceModel::getFind(['res_id' => $data['res_id'] ?? 0], 'res_id');
        if (!$resource) {
            return $this->rFail('素材不存在');
        }

        // 白名单：只保留表中实际存在的字段
        $allowedFields = ['title', 'file_path', 'type', 'file_name', 'desc', 'length', 'width', 'size', 'status'];
        $update = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        // 后端自动设置更新人和更新时间
        $update['update_id'] = $this->manager['manager_id'] ?? 0;
        $update['update_time'] = time();
        return $this->rU(ResourceModel::update($update, ['res_id' => $data['res_id']]));
    }
}