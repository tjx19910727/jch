<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 11:25
 */

namespace app\AppFactory\Kernel\Traits;



use app\AppFactory\Kernel\Model\Auth\AuthManagerModel;
use app\AppFactory\Kernel\Support\Qr;
use think\facade\Lang;

trait ManagementTrait
{

    /**
     * 公用获取一条数据
     * Client文件需要引入对应Model的Trait文件，在Trait文件中方法格式固定为"get[控制器名称]Find"
     * Trait文件中方法需要传入的参数为条件、字段、排序
     * @param array $where
     * @param string $field
     * @param string $order
     * @param int $rQ
     * @return array|string
     */
    public function getFind($where = [],$field = "*", $order = "",$rQ = 1)
    {
        $this->getController($controller);
        if (!$controller) return $this->rFail("控制器名不能为空");
        $action = "get" . $controller . "Find";
        $data = $this->$action($where,$field,$order);
        if ($rQ) {
            return $this->rQ($data);
        }
        return $data;
    }

    /**
     * 公用获取列表
     * Client文件需要引入对应Model的Trait文件，在Trait文件中方法格式固定为"get[控制器名称]List"
     * Trait文件中方法需要传入的参数为条件、页面数据条数、字段、排序
     * 隐藏参数：page，页码，请求时放在一级参数中
     * @param array $where
     * @param string $field
     * @param int $pageNum  页面数据条数，大于0查翻页数据，等于0或不传时查纯列表
     * @param string $order
     * @param int $rQ
     * @return mixed
     */
    public function getList($where = [],$pageNum = 0, $field = "*", $order = "",$rQ = 1)
    {
        $this->getController($controller);
        if (!$controller) return $this->rFail(Lang::get("controller_name_require"));
        $action = "get" . $controller . "List";
        $data = $this->$action($where,$pageNum,$field,$order);
        if ($rQ) {
            return $this->rQ($data);
        }
        return $data;
    }

    /**
     * 公用增加一条数据
     * Client文件需要引入对应Model的Trait文件，在Trait文件中方法格式固定为"add[控制器名称]"
     * 添加成功时返回主键ID
     * @param $insert
     * @param int $rA
     * @return mixed
     */
    public function add($insert,$rA = 1)
    {
        $this->getController($controller);
        if (!$controller) return $this->rFail(Lang::get("controller_name_require"));
//        $action = request()->action();
//        $check = $this->checkFrequency($controller . ucwords($action));
//        if ($check !== true) return $check;
        $action = "add" . $controller;
        $data = $this->$action($insert);
        if ($rA) return $this->rA($data);
        return $data;
    }

    /**
     * 公用修改数据
     * Client文件需要引入对应Model的Trait文件，在Trait文件中方法格式固定为"update[控制器名称]"
     * @param array|string $update  修改的数据，包含主键值时可不用传$where
     * @param array $where
     * @param array $field
     * @param int $rU
     * @return mixed
     */
    public function update($update,$where = [],$field = [],$rU = 1)
    {
        $this->getController($controller);
        if (!$controller) return $this->rFail(Lang::get("controller_name_require"));
//        $action = request()->action();
//        $check = $this->checkFrequency($controller . ucwords($action));
//        if ($check !== true) return $check;
        $action = "update" . $controller;
        $result = $this->$action($update,$where,$field);
        if ($rU) return $this->rU($result);
        return $result;
    }

    /**
     * 公用软删除
     * Client文件需要引入对应Model的Trait文件，在Trait文件中方法格式固定为"update[控制器名称]"
     * @param $where
     * @param int $rU
     * @return mixed
     */
    public function isDel($where,$rU = 1)
    {
        $this->getController($controller);
        if (!$controller) return $this->rFail(Lang::get("controller_name_require"));
        $action = "update" . $controller;
        $result = $this->$action(["is_del" => 1],$where,["is_del"]);
        if ($rU) return $this->rU($result);
        return $result;
    }

    /**
     * 公用彻底删除
     * Client文件需要引入对应Model的Trait文件，在Trait文件中方法格式固定为"del[控制器名称]"
     * @param $where
     * @param int $rD
     * @return mixed
     */
    public function del($where,$rD = 1)
    {
        $this->getController($controller);
        if (!$controller) return $this->rFail(Lang::get("controller_name_require"));
        $action = "del" . $controller;
        $result = $this->$action($where);
        if ($rD) return $this->rD($result);
        return $result;
    }

    /**
     * 生成二维码
     * @param $url
     * @param array $config  配置信息
     * @var string $folder 保存目录，自动增加根目录public/uploads/qr，不指定目录则保存至public/uploads/qr/[年月日] 目录下
     * @var string $name   保存图片名称，不指定文件名则以当前时间戳为图片名称
     * @var string $text     二维码下方增加文字说明
     * @var string $logoPath   LOGO地址，为空时不加LOG，可指定LOGO地址
     * @var int $size   二维码边长，像素
     * @var int $margin   二维码外间距
     * @var int $resizeToWidth   LOGO图片边长
     * @return mixed
     */
    public function makeQr($url,$config = [])
    {
        return Qr::mkQrCode($url,$config);
    }



}