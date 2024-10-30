<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/13
 * Time: 16:04
 */

namespace app\AppFactory\Kernel\Support;


use think\Exception;
use think\facade\Lang;

class Excel
{

    /**
     * 功能：导入excel表格
     * @param $filePath
     * @param array $list
     * @param array $other
     * @return array|string
     */
    public static function importExcel($filePath, $list=[],$other = [],$startRow = 2)
    {
        try {
            $data = [];
            if (file_exists($filePath)) {
                require_once root_path() . '/extend/PHPExcel/PHPExcel.php';
                require_once root_path() . '/extend/PHPExcel/PHPExcel/Writer/Excel2007.php';
                $header_arr = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];
                $objReader = new \PHPExcel_Reader_Excel2007();
                $objPHPExcel = $objReader->load($filePath, $encode = 'utf-8');//获取excel文件
                $sheet = $objPHPExcel->getSheet(0); //激活当前的表
                $highestRow = $sheet->getHighestRow(); // 取得总行数

                $imageFilePath =  './uploads/excel_img/'.date('Ymd').'/';//图片在本地存储的路径
                if (!file_exists($imageFilePath)) {
                    @mkdir("$imageFilePath");
                    @chmod($imageFilePath,0777);
                }
                $imgList = self::getImg($sheet,$imageFilePath);
                if (is_string($imgList)) return returnState(100,$imgList);
                for ($i = $startRow; $i <= $highestRow; $i++) {
                    $row = [];
                    foreach ($list as $key => $value) {
                        if (!isset($imgList[$header_arr[$key] . $i])) {
                            $row[$value] = $objPHPExcel->getActiveSheet()->getCell($header_arr[$key] . $i)->getValue();
                            if ($row[$value] === null) $row[$value] = "";
                        } else {
                            $row[$value] = $imgList[$header_arr[$key] . $i];
                        }
                    }
                    if ($other) {
                        $row = array_merge($row, $other);
                    }
                    if ($row) $data[] = $row;
                }
            }
            return $data;
        } catch (\PHPExcel_Reader_Exception $e) {
            actionException($e,1);
            return returnTryCatch($e->getMessage());
        } catch (\PHPExcel_Exception $e) {
            actionException($e,1);
            return returnTryCatch($e->getMessage());
        }
    }

    /**
     * 获取导入数据中的图片信息，保存图片至文件夹并返回路径
     * @param \PHPExcel_Worksheet $worksheet
     * @param $imageFilePath
     * @return array|string
     */
    public static function getImg(\PHPExcel_Worksheet $worksheet,$imageFilePath)
    {
        try {
            $data = [];
            foreach ($worksheet->getDrawingCollection() as $drawing) {
                $xy = $drawing->getCoordinates();//得到单元数据 比如G2单元
                if ($drawing instanceof \PHPExcel_Worksheet_Drawing) {//支持excel2007后缀为（.xlsx）
                    $filename = $drawing->getPath();
                    $imgData = file_get_contents($filename);
                    if (strlen($imgData) > env("fileSystem.maxImageSize")) {
                        throw new Exception($xy . "," . Lang::get("fileSize") . "：" . round((strlen($imgData) / 1024 / 1024),3) . "MB" . "/" . round((env("fileSystem.maxImageSize") / 1024 / 1024),3) . "MB");
                    }
                    $imageFileName = $drawing->getIndexedFilename();
                    $type = explode(".", $imageFileName);
                    $imageName = $imageFilePath . md5(time() . rand(00000000, 99999999)) . '.' . $type[1];
                    if (file_put_contents($imageName, $imgData)) {
                        $data[$xy] = env("APP.host") . substr($imageName, 1);
                    }  //把文件保存到本地
                } elseif ($drawing instanceof \PHPExcel_Worksheet_MemoryDrawing) {//支持excel2003后缀为（.xls）
                    $imageFileNames = $drawing->getIndexedFilename();
                    ob_start();
                    call_user_func(
                        $drawing->getRenderingFunction(),
                        $drawing->getImageResource()
                    );
                    $imageContents = ob_get_contents();
                    if (strlen($imageContents) > env("fileSystem.maxImageSize")) {
                        throw new \Exception($xy . "," . Lang::get("fileSize") . "：" . strlen($imageContents) . "/" . env("fileSystem.maxImageSize"));
                    }
                    ob_end_clean();
                    $type = explode(".", $imageFileNames);
                    $imageName = $imageFilePath . md5(time() . rand(00000000, 99999999)) . '.' . $type[1];
                    if (file_put_contents($imageName, $imageContents)) {
                        $data[$xy] =  env("APP.host") .substr($imageName, 1);
                    }  //把文件保存到本地
                }
            }
            return $data;
        } catch (\Exception $e) {
            actionException($e,1);
            return $e->getMessage();
        }
    }

    /**
     * 导出
     * @param $list
     * @param $title
     * @param $filename
     * @param int $isDown
     * @param int $startRow
     * @param array $mergeCells
     * @return bool|string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function exportExcel($list,$title,$filename,$isDown = 0,$startRow = 1,$mergeCells = [])
    {
        if(empty($filename)) return false;
        if(!is_array($title)) return false;
        require_once root_path() . '/extend/PHPExcel/PHPExcel.php';
        require_once root_path() . '/extend/PHPExcel/PHPExcel/Writer/Excel2007.php';
        $header_arr= ['A','B','C','D','E','F','G','H','I','J','K','L','M', 'N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM', 'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $filename = $filename.'.xlsx';
        array_unshift($list,$title);
        $indexKey = [];
        foreach ($title as $k=>$v){
            $indexKey[] = $k;
        }
        //接下来就是写数据到表格里面去
        $objActSheet = $objPHPExcel->getActiveSheet();
        if ($mergeCells) {
            foreach ($mergeCells as $mk => $mv) {
                if (strpos($mv['merge'], ":") !== false) $objActSheet->mergeCells($mv['merge']);
                if (isset($mv['cell']) && isset($mv['name']))$objActSheet->setCellValueExplicit($mv["cell"],$mv["name"],\PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        $styleArray = array(
            'alignment' => array(
                'wrap' => true, // 设置自动换行
            ),
        );
        foreach ($list as $row) {
            foreach ($indexKey as $key => $value){
                //这里是设置单元格的内容
//                $objActSheet->getStyle($header_arr[$key].$startRow)->applyFromArray($styleArray);
                $objActSheet->setCellValueExplicit($header_arr[$key].$startRow,$row[$value],\PHPExcel_Cell_DataType::TYPE_STRING);
            }
            $startRow++;
        }
        // 保存到本地
        $savePath = "/export/excel/" . date("Ymd");
        $path = root_path() . "public" . $savePath;
        if (!is_dir($path)) {
            @mkdir($path);
            @chmod($path,0777);
        }
        $path .= ("/" . $filename);
        $objWriter->save($path);

        if ($isDown) {
            self::outExcelHeader($filename);
            $objWriter->save("php://output");
        }
        return $savePath . "/" . $filename;
    }

    /**
     * 下载头部
     * @param $fileName
     */
    public static function outExcelHeader($fileName){
        ob_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename='.$fileName.'');
        header("Content-Transfer-Encoding:binary");
    }
}