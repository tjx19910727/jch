<?php


namespace app\AppFactory\Kernel;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerLogTrait;
use app\AppFactory\Kernel\Traits\CacheTrait;
use app\AppFactory\Kernel\Traits\CommonTrait;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;
use app\AppFactory\Kernel\Traits\CurlTrait;
use app\AppFactory\Kernel\Traits\DbTrait;
use app\AppFactory\Kernel\Traits\ReturnTrait;

use ZipArchive;

class BaseClient
{
    use DbTrait, CacheTrait, ReturnTrait, CommonTrait, ConfigTrait, CurlTrait;
    use AuthManagerLogTrait;
    protected $app;
    protected $config;
    protected $host;

    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
        $this->config = $app->getConfig();
        $this->host = env("app.host");
    }



    /**
     * 打包文件
     * @param $file_name * Zip文件全路径
     * @param $file_list * 打包的文件路径列表 path文件全路径，压缩包内显示的文件名
     */
    public function makeZip($file_name, $file_list)
    {
        if (file_exists($file_name)){
            unlink($file_name);
        }

        $zip = new ZipArchive();
        if ($zip->open($file_name,ZIPARCHIVE::CREATE) !== true){
            exit("无法打开文件，或者文件创建失败");
        }
        foreach ($file_list as $val){
            if (file_exists($val['path'])){
                $zip->addFile($val['path']);
                $zip->renameName($val['path'],$val['name']);
            }
        }
        $zip->close();
        if (!file_exists($file_name)){
            exit("无法找到文件");
        }
    }

    /**
     * 下载文件  下载内容为文件夹路径 + 文件名
     * @param $file * 文件夹路径
     * @param $file_name * 文件名
     */
    public function download($file,$file_name){
        if (file_exists($file)){
            header("Content-Description: File Transfer");
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.$file_name);
            header('Content-Transfer-Encoding: binary');
            header("Expires: 0");
            header("Cache-Control: must-revalidate");
            header("Pragma: public");
            header("Content-Length: ". filesize($file.$file_name));
            ob_clean();
            flush();
            readfile($file.$file_name);
            exit;
        }
    }

    /**
     * 获取随机字符串
     * @param int $length
     * @param string $type
     * @return string
     */
    public function get_rand_string($length=0,$type='chars_num')
    {
        $num = '0123456789';
        $upperChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerChars = 'abcdefghijklmnopqrstuvwxyz';
        $hex = 'ABCDEF';
        $string = '';
        $key = '';
        switch($type){
            case 'all':
                $string = $num.$upperChars.$lowerChars.$hex;
                break;
            case 'chars':
                $string = $upperChars.$lowerChars;
                break;
            case 'upper_chars':
                $string = $upperChars;
                break;
            case 'lower_chars':
                $string = $lowerChars;
                break;
            case 'chars_num':
                $string = $num.$upperChars.$lowerChars;
                break;
            case 'lower_num':
                $string = $num.$lowerChars;
                break;
            case 'upper_num':
                $string = $num.$upperChars;
                break;
            case 'num':
                $string = $num;
                break;
            case 'hex':
                $string = $num.$hex;
                break;
        }
        for($i=0;$i<$length;$i++){
            $key .= $string[mt_rand(0,strlen($string)-1)];
        }
        return $key;
    }
}


