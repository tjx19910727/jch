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

    // 镭射机图片上传（file）
    public function uploadImage()
    {
        try {
            $postData = input();
            $file = request()->file('file');
            if (!$file) {
                return returnState(100, Lang::get('VLaser.file_require'));
            }

            $signData = $postData;
            unset($signData['file']);
            if (!isset($signData['sign'])) {
                return returnState(100, Lang::get('VLaser.check_sign_fail'));
            }
            $machineId = $signData['machine_id'] ?? '';
            $signKey = '';
            if ($machineId && !env('CglPay.is_test')) {
                $signKey = MachineModel::getFieldValue(['machine_id' => $machineId], 'signKey');
            }
            if (!$signKey) {
                $signKey = env('api.md5Key');
            }
            if (!SignUtil::checkSign($signData, $signKey)) {
                return returnState(100, Lang::get('VLaser.check_sign_fail'));
            }

            validate(VCommon::class)->scene('file')->check(['file' => $file]);
            validate(VCommon::class)
                ->rule(['image' => 'fileSize:' . env('fileSystem.maxImageSize')])
                ->scene('uploadImage')
                ->check(['image' => $file]);

            $folder = input('folder', 'laser/' . date('Ymd'));
            $diskName = env('fileSystem.diskName');
            $saveName = Filesystem::disk($diskName)->putFile($folder, $file);
            $filePath = Filesystem::getDiskConfig($diskName, 'url') . str_replace('\\', '/', $saveName);

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
                'desc' => $postData['desc'] ?? '',
                'length' => $imageInfo[1] ?? 0,
                'width' => $imageInfo[0] ?? 0,
                'size' => intval($file->getSize()),
                'order_id' => intval($postData['order_id'] ?? 0),
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
    
}