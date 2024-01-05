<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/26
 * Time: 9:58
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreModel;
use app\AppFactory\Kernel\Support\Qr;

trait StoreTrait
{
    public function getStoreCount($where)
    {
        return StoreModel::getCount($where);
    }

    public function getStoreColumn($where,$column)
    {
        return StoreModel::getColumn($where,$column);
    }

    public function getStoreValue($where,$value)
    {
        return StoreModel::getFieldValue($where,$value);
    }

    public function getStoreFind($where,$field = "*", $order = "",$details = 1)
    {
        return StoreModel::getDetails($where,$field,$order,$details);
    }

    public function delStore($where)
    {
        return StoreModel::destroy($where);
    }

    /**
     * 获取门店列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return StoreModel|StoreModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getStoreList($where,$pageNum = 0, $field = "*", $order = "")
    {
        $data = StoreModel::getList($where,$pageNum,$field,$order,function($item){
            if (isset($item['province_id'])) $item['province'] = $this->getCityValue(['city_id' => $item['province_id']],'city_title');
            if (isset($item['city_id'])) $item['city'] = $this->getCityValue(['city_id' => $item['city_id']],'city_title');
            if (isset($item['area_id'])) $item['area'] = $this->getCityValue(['city_id' => $item['area_id']],'city_title');
            if (isset($item['store_manager'])) $item['store_manager_name'] = $this->getAuthManagerValue(['manager_id' => $item['store_manager']],'nickname');
            return $item;
        });
        return $data;
    }

    /**
     * 添加门店信息
     * @param $insert
     * @return mixed
     */
    public function addStore($insert)
    {
        $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $store = StoreModel::create($insert);
        if ($store) {
            $qrConfig = [
                "store_id" => $store['store_id'],
                "text" => $store['terminal_no'],
                "inType" => 1,
            ];
            // 生成进店二维码
            $this->makeStoreDoorQr($qrConfig);
            // 生成离店二维码
            $qrConfig['inType'] = 2;
            $this->makeStoreDoorQr($qrConfig);
        }
        return $store->store_id;
    }

    public $sendGatewaySwitch = 1;

    /**
     * 修改门店信息
     * @param $update
     * @param array $where
     * @param array $field
     * @return StoreModel
     */
    public function updateStore($update,$where = [],$field = [])
    {
        if (isset($this->manager['manager_id']) && $this->manager['manager_id']) {
            $update['update_id'] = $this->manager['manager_id'];
        }
        $result = StoreModel::update($update,$where,$field);
        if ($result && $this->sendGatewaySwitch) {
            $whereStore = [];
            if (isset($update['store_id'])) $whereStore['store_id'] = $update['store_id'];
            if (isset($update['terminal_no'])) $whereStore['terminal_no'] = $update['terminal_no'];
            if ($whereStore) {
                $store = $this->getStoreFind($whereStore);
                if ((isset($this->message) && $this->message['msgType'] != "login") || (!isset($this->message) && $store['terminal_no'])) {
                    $this->sendGateway($store['terminal_no'], $this->r(200, '更新门店信息', $store), "storeUpdate");
                }
            }
        }
        return $result;
    }

    /**
     * 生成二维码
     * @param $config
     * @return string
     */
    public function makeStoreDoorQr($config)
    {
        $inType = $config['inType'] ?? 1;
        $qr_code = $this->getUrl("/mobile/mini.entrance/index?store_id=" . $config['store_id'] . "&inType=$inType");
        $folder = "storeDoor";
        if (isset($config['folder']) && $config['folder']) {
            $folder .= "/" . $config['folder'];
        }
        $config['folder'] = $folder;
        $config['name'] = $config['name'] ?? $config['store_id'] . "_" . $inType;
        $result = $this->makeQr($qr_code,$config);
        if ($result['state'] == 200) {
            $this->updateStore(['store_id' => $config['store_id'],'door_qr_code' . $inType => $result['data']]);
        }
        return arr2json($result);
    }


    /**
     * 打包门店进门二维码下载
     * @return mixed
     * @throws \Exception
     */
    public function packZip($postData)
    {
        $zipName = root_path() . "public/zip/";
        $where[] = ['store_id',"in",$postData['ids']];
        $store = $this->getStoreList($where,0,'store_id,store_name,door_qr_code1,door_qr_code2');
        if (!$store) return $this->rFail("查无门店信息");
        $store = $store->toArray();
        $start = "";
        $end = "";
        foreach ($store as $key => $value) {
            if ($key == 0) $start = $value['store_id'];
            if ($key == count($store) - 1) $end = $value['store_id'];
            $file_list[] = [
                'path' => root_path() . "public" . $value['door_qr_code1'],
                'name' => $value['store_name'] . substr($value['door_qr_code1'],-5)];
            $file_list[] = [
                'path' => root_path() . "public" . $value['door_qr_code2'],
                'name' => $value['store_name'] . substr($value['door_qr_code2'],-5)];
        }
        $file_name = $start == $end ? $start . ".zip" : $start . "_" . $end . ".zip";
        $this->makeZip($zipName . $file_name, $file_list);
        $this->download($zipName, $file_name);
        if (file_exists($zipName . $file_name)) @unlink($zipName . $file_name);
    }

}