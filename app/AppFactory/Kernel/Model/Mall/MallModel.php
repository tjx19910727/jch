<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 12:00
 */
namespace app\AppFactory\Kernel\Model\Mall;

use app\AppFactory\Kernel\Model\BaseModel;

class MallModel extends BaseModel
{
    /**
     * Primary key for the mall table
     *
     * @var string
     */
    protected $pk = "mall_id";

    /**
     * Table name
     *
     * @var string
     */
    protected $name = "mall";

    public static function getJoinMallMachineList($where, $pageNum = 0, $field = "*", $order = "mall_id desc")
    {
        $data = self::alias("m")
            ->where($where)
            ->field($field)
            ->order($order);
        if ($pageNum) {
            $data = $data->paginate($pageNum);
        } else {
            $data = $data->select();
        }
        return $data->each(function ($item) {
                $item['mall_machine_num'] = MallMachineModel::getCount(['mall_id' => $item['mall_id'], 'status' => 1]) ?? 0;
                return $item;
            });;
    }
}