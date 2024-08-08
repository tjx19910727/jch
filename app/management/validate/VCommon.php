<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 16:00
 */

namespace app\management\validate;


use think\Validate;

class VCommon extends Validate
{

    protected $rule = [
        'file' => "file|fileExt:jpg,jpeg,gif,png,xls,xlsx,crt,csr,txt,pem,mp3,mp4,wav,aiff,aac,flac,ogg,m4a,amr,wma,pcm,zip",
        'image' => "fileSize:3145728|fileExt:jpg,jpeg,gif,png",
    ];

    protected $message = [
        "file.file" => "上传非文件",
        "file.fileExt" => "不支持的上传文件类型",
        "image.fileSize" => "文件大小超过限制：3M",
        "image.fileExt" => "文件类型不是图片类型：jpg,jpeg,gif,png",
    ];

    protected $scene = [
        "file" => ["file"],
        "uploadImage" => ["image"],
    ];
}