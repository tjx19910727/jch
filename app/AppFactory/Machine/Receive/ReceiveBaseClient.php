<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/24
 * Time: 10:48
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineDetailsTrait;
use app\AppFactory\Machine\MachineBaseClient;
use think\exception\ValidateException;
use think\facade\Filesystem;

class ReceiveBaseClient extends MachineBaseClient
{
    use MachineOnlineDetailsTrait;
    use AuthManagerTrait;

    protected $message = [];

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->data = json2arr($this->config['data']);
        $this->machine['last_online_time'] = time();
        $this->machine['online'] = 1;
        if (!isset($this->data['msgType']) || (isset($this->data['msgType']) && $this->data['msgType'] != "heartbeat")) {
            $this->heartbeat();
        }
        $this->newRecord();

        $this->ignoreList = (config("auth_manager_log_list.ignore")['machine'] ?? []);
        $this->apiUrl = request()->action();
        $this->recordManagerLog();
    }


    /**
     * 设备上传媒体文件
     * @param string $folder
     * @return array|string
     */
    public function uploadFiles()
    {
        $folder = $this->data['folder'];
        $file = request()->file("file");
        if (!$file) return returnState(100,'上传失败，file不能为空');
        if ($folder) {
            $folderPath = root_path("public/uploads/" . $folder);
            if (!is_dir($folderPath)) {
                @mkdir($folderPath);
                @chmod($folderPath,0777);
            }
        }
        try {
            validate(
                [
                    'file' => [
//                        "fileSize" => 2 * 1024 * 1024,
                        "fileExt" => "jpg,jpeg,gif,png,mp4,flv,wav,aiff,aac,flac,ogg,m4a,amr,wma,pcm",
                    ],
                ],
                [
//                    "file.fileSize" => "fileSize",
                    "file.fileExt" => "fileExt",
                ]
            )->check(['file' => $file]);
            $diskName = env("fileSystem.diskName");     // 上传本地
//            $diskName = "aliyun";    // 上传OSS服务器
            $saveName = Filesystem::disk($diskName)->putFile($folder, $file);
            $path = Filesystem::getDiskConfig($diskName, 'url') . str_replace('\\', '/', $saveName);
            return returnState(200,$this->lang("uploadSuccess"),$path);
        } catch (ValidateException $e) {
            return returnValidate($this->lang($e->getMessage()));
        }
    }
}