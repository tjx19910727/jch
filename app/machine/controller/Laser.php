<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/04/28
 * Time: 11:43
 */

namespace app\machine\controller;



use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Traits\Laser\LaserResourceTrait;
use app\AppFactory\Kernel\Util\SignUtil;
use app\BaseController;
use app\AppFactory\Kernel\Traits\ReturnTrait;
use app\management\validate\VCommon;
use think\facade\Filesystem;
use think\facade\Lang;

class Laser extends BaseController
{
    use ReturnTrait;
    use LaserResourceTrait;
    protected $signData = [];
    protected $machineId = '';

    /**
     * 初始化公共验签
     */
    protected function initialize()
    {
        parent::initialize();
        try {
            $this->signData = input();
            unset($this->signData['file']);

            if (!isset($this->signData['sign'])) {
                returnState(100, Lang::get('VLaser.check_sign_fail'))->send();
                die();
            }

            $this->machineId = $this->signData['machine_id'] ?? '';
            $signKey = '';
            if ($this->machineId && !env('CglPay.is_test')) {
                $signKey = MachineModel::getFieldValue(['machine_id' => $this->machineId], 'signKey');
            }
            if (!$signKey) {
                $signKey = env('api.md5Key');
            }
            if (!SignUtil::checkSign($this->signData, $signKey) && !env('CglPay.is_test')) {
                returnState(100, Lang::get('VLaser.check_sign_fail'))->send();
                die();
            }
        } catch (\Exception $e) {
            returnState(300, Lang::get($e->getMessage()))->send();
            die();
        }
    }

    // 镭射机图片上传（file）
    public function uploadImage()
    {
        try {
            $signData = $this->signData;
            $machineId = $this->machineId;
            $file = request()->file('file');
            if (!$file) {
                return returnState(100, Lang::get('VLaser.file_require'));
            }

            //通过trade_no获取订单id
            if(empty($signData['trade_no'])){
                return returnState(100, Lang::get('VLaser.trade_no_require'));
            }
            $order = [];
            // $order = SaleOrdersModel::where('trade_no', $signData['trade_no'])->field('order_id,trade_no')->find();
            // if (!$order) {
            //     return returnState(100, Lang::get('VLaser.order_not_found'));
            // }
            validate(VCommon::class)->scene('file')->check(['file' => $file]);
            validate(VCommon::class)
                ->rule(['image' => 'fileSize:5242880'])
                ->scene('uploadImage')
                ->check(['image' => $file]);

            $folder = input('folder', 'laser/' . date('Ymd'));
            $diskName = env('fileSystem.diskName');
            $saveName = Filesystem::disk($diskName)->putFile($folder, $file);
            if (is_array($saveName)) {
                $saveName = $saveName['saveName'] ?? $saveName['savename'] ?? '';
            }
            if (!is_string($saveName) || $saveName === '') {
                return returnState(300, Lang::get('VLaser.upload_image_fail'));
            }
            $diskUrl = Filesystem::getDiskConfig($diskName, 'url');
            if (is_array($diskUrl)) {
                $diskUrl = '';
            }
            $filePath = $diskUrl . str_replace('\\', '/', $saveName);

            $diskRoot = strval(Filesystem::getDiskConfig($diskName, 'root'));
            $localName = str_replace('/', DIRECTORY_SEPARATOR, $saveName);
            $localName = str_replace('\\', DIRECTORY_SEPARATOR, $localName);
            $localPath = rtrim($diskRoot, "\\/") . DIRECTORY_SEPARATOR . $localName;
            $imageInfo = @getimagesize($localPath);
            if (!$imageInfo) {
                return returnState(300, Lang::get('VLaser.get_image_info_fail'));
            }

            $insert = [
                'file_path' => $filePath,
                'type' => 1,
                'file_name' => $file->getOriginalName(),
                'desc' => $this->signData['desc'] ?? '',
                'length' => $imageInfo[1] ?? 0,
                'width' => $imageInfo[0] ?? 0,
                'size' => intval($file->getSize()),
                'order_id' => intval($order['order_id'] ?? 0),
                'trade_no' => $this->signData['trade_no'] ?? '',
                'create_time' => time(),
            ];
            $resId = $this->addLaserResource($insert);

            $app = AppFactory::machine(['machine_id' => $machineId]);
            $mqResult = $app->sendMq->sendMq('laserImage', [
                'filepath' => $insert['file_path'],
                'res_id' => $resId,
            ]);
            $mqResult = obj2arr($mqResult);
            if (!isset($mqResult['state']) || intval($mqResult['state']) !== 200) {
                return returnState(300, Lang::get('VLaser.upload_image_fail'), [
                    'res_id' => $resId,
                    'file_path' => $insert['file_path'],
                    'mq_result' => $mqResult,
                ]);
            }
            return returnState(200, Lang::get('VLaser.upload_image_success'), [
                'res_id' => $resId,
                'file_path' => $insert['file_path'],
                'type' => $insert['type'],
                'file_name' => $insert['file_name'],
                'desc' => $insert['desc'],
                'length' => $insert['length'],
                'width' => $insert['width'],
                'size' => $insert['size'],
                'order_id' => $insert['order_id'],
                'create_time' => $insert['create_time'],
            ]);
        } catch (\Exception $e) {
            actionException($e, 1);
            return returnTryCatch($e->getMessage());
        }
    }

    // 设备轮询：根据trade_no查询最新一条镭射数据
    public function queryLatest()
    {
        try {
            $tradeNo = $this->signData['trade_no'] ?? '';
            if (empty($tradeNo)) {
                return returnState(100, Lang::get('VLaser.trade_no_require'));
            }

            $field = 'res_id,file_path,type,file_name,`desc`,length,width,size,order_id,trade_no,create_time';
            $data = $this->getLaserResourceFind(['trade_no' => $tradeNo], $field, 'res_id desc');
            if (!$data) {
                return returnStateV2(200, 'fail', []);
            }

            return returnState(200, 'success', $data);
        } catch (\Exception $e) {
            actionException($e, 1);
            return returnTryCatch($e->getMessage());
        }
    }
    
}