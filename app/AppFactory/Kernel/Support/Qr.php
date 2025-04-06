<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/24
 * Time: 14:38
 */

namespace app\AppFactory\Kernel\Support;


use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Class Qr
 * @method static mkCode()   mkQrCode($url,$config = []) 生成二维码静态方法
 * @package app\AppFactory\Kernel\Support
 */
class Qr
{
    /**
     * @var array 配置信息
     * @param string $folder 保存目录，自动增加根目录public/uploads/qr/，不指定目录则保存至public/uploads/qr/[年月日] 目录下
     * @param string $name   保存图片名称，不指定文件名则以当前时间戳为图片名称
     * @param string $text     二维码下方增加文字说明
     * @param string $logoPath   LOGO地址，为空时不加LOG，可指定LOGO地址
     * @param string $size   二维码边长，像素
     * @param string $margin   二维码外间距
     * @param string $resizeToWidth   LOGO图片边长
     */
    protected $config = [
        "folder" => "",
        "name" => "",
        "text" => "",
        "logoPath" => "",
        "size" => "150",
        "margin" => "20",
        "resizeToWidth" => "20",
    ];

    protected static $method = [
        "mkQrCode" => "mkCode",
    ];

    public static function __callStatic($name, $arguments)
    {
        // TODO: Implement __callStatic() method.
        $app = new self;
        $name = self::$method[$name];
        return $app->$name(...$arguments);
    }

    /**
     * 生成二维码
     * @param string $url 跳转链接
     * @param array $config 二维码配置信息
     * @return bool|string|array
     * @throws \Exception
     */
    public function mkCode(string $url,$config = [])
    {
        try {
            $this->config = $config + $this->config;
            $label = null;
            $logo = null;
            $writer = new PngWriter();
            $qrCode = QrCode::create($url)
                ->setEncoding(new Encoding("UTF-8"))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
                ->setSize($this->config['size'])
                ->setMargin($this->config['margin'])
                ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));
            if ($this->config['text']) {
                $label = Label::create($this->config['text'])->setTextColor(new Color(0, 0, 0));
            }
            if ($this->config['logoPath'] == true) $this->config['logoPath'] = "static/logo.png";
            if ($this->config['logoPath']) {
                $logo_path = root_path() . "/public/" . $this->config['logoPath'];
                $logo = Logo::create($logo_path)->setResizeToWidth($this->config['resizeToWidth']);
            }
            $result = $writer->write($qrCode, $logo, $label);//        header("Content-Type:" . $result->getMineType());
            $result->getString();
            $this->config['folder'] = $this->config['folder'] ? : date("Ymd");
            $file_path = root_path() . '/public/uploads/qr/' . $this->config['folder'] . "/";
            $saveName = ($this->config['name'] ? $this->config['name'] : time()) . ".png";
            if (!file_exists($file_path)) {
                mkdir($file_path);
                chmod($file_path, 0777);
            }
            $result->saveToFile($file_path . $saveName);
            return ['state' => 200,"msg" => "生成成功","data" => '/uploads/qr/' . $this->config['folder'] . "/" . $saveName];
        } catch (\Exception $e) {
            return ['state' => 300,"msg" => $e->getMessage()];
        }
    }

}