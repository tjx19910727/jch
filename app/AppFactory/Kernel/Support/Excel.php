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
                        $data[$xy] = substr($imageName, 1);
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
                        $data[$xy] =  substr($imageName, 1);
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
     * @param array $otherData 可选扩展：imageFields/ imageWidth / imageHeight
     * @return bool|string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function exportExcel($list,$title,$filename,$isDown = 0,$startRow = 1,$mergeCells = [],$otherData = [])
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

        $imageFields = $otherData['imageFields'] ?? [];
        $imageWidth  = (int)($otherData['imageWidth'] ?? 220);
        $imageHeight = (int)($otherData['imageHeight'] ?? 70);
        $tempDir = null;
        if ($imageFields) {
            $tempDir = root_path() . 'public/uploads/excel_img/' . date('Ymd') . '/';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }
        }

        //接下来就是写数据到表格里面去
        $objActSheet = $objPHPExcel->getActiveSheet();
        if ($mergeCells) {
            foreach ($mergeCells as $mk => $mv) {
                if (strpos($mv['merge'], ":") !== false) $objActSheet->mergeCells($mv['merge']);
                if (isset($mv['cell']) && isset($mv['name']))$objActSheet->setCellValueExplicit($mv["cell"],$mv["name"],\PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        foreach ($list as $row) {
            $rowHasImage = false;
            foreach ($indexKey as $key => $value){
                $cellValue = $row[$value] ?? '';
                $colLetter = $header_arr[$key];
                // 图片列且值是http(s) URL → 嵌入图片本体
                if ($imageFields && in_array($value, $imageFields, true) && preg_match('#^https?://#i', (string)$cellValue)) {
                    $localPath = self::resolveExportImage((string)$cellValue, $tempDir);
                    if ($localPath) {
                        $objActSheet->setCellValue($colLetter . $startRow, '');
                        $drawing = new \PHPExcel_Worksheet_Drawing();
                        $drawing->setPath($localPath);
                        $drawing->setCoordinates($colLetter . $startRow);
                        $drawing->setWidth($imageWidth);
                        $drawing->setHeight($imageHeight);
                        $drawing->setOffsetX(3);
                        $drawing->setOffsetY(3);
                        $drawing->setWorksheet($objActSheet);
                        $rowHasImage = true;
                        continue;
                    }
                }
                // 普通文本
                $objActSheet->setCellValueExplicit($colLetter.$startRow, $cellValue, \PHPExcel_Cell_DataType::TYPE_STRING);
            }
            if ($rowHasImage) {
                $objActSheet->getRowDimension($startRow)->setRowHeight(max(20, $imageHeight * 0.75));
            }
            $startRow++;
        }
        // 保存到本地
        $savePath = "/export/excel/" . date("Ymd");
        $path = root_path() . "public" . $savePath;
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
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
     * 多Sheet导出
     * @param array $sheets [['sheetName' => '汇总', 'title' => [...], 'list' => [...], 'merge' => [...], 'imageFields' => [...], 'startRow' => 2], ...]
     * @param string $filename
     * @return bool|string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function exportMultiSheetExcel($sheets, $filename)
    {
        if (empty($filename)) return false;
        if (!is_array($sheets) || !$sheets) return false;

        require_once root_path() . '/extend/PHPExcel/PHPExcel.php';
        require_once root_path() . '/extend/PHPExcel/PHPExcel/Writer/Excel2007.php';

        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $filename = $filename . '.xlsx';

        $firstSheet = true;
        foreach ($sheets as $index => $sheet) {
            $sheetName = $sheet['sheetName'] ?? ('Sheet' . ($index + 1));
            $list = $sheet['list'] ?? [];
            $title = $sheet['title'] ?? [];
            $mergeCells = $sheet['merge'] ?? [];
            $otherData = $sheet['otherData'] ?? [];
            $startRow = (int)($otherData['startRow'] ?? $sheet['startRow'] ?? 1);
            $imageFields = $otherData['imageFields'] ?? $sheet['imageFields'] ?? [];

            if (!$title || !$list) {
                continue;
            }

            if ($firstSheet) {
                $objActSheet = $objPHPExcel->getActiveSheet();
                $firstSheet = false;
            } else {
                $objActSheet = $objPHPExcel->createSheet();
            }
            $objActSheet->setTitle($sheetName);

            self::writeSheetData($objActSheet, $list, $title, $startRow, $mergeCells, $imageFields, $otherData);
        }

        if ($firstSheet) {
            // no sheet was written
            return false;
        }

        $savePath = "/export/excel/" . date("Ymd");
        $path = root_path() . "public" . $savePath;
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
            @chmod($path, 0777);
        }
        $path .= ("/" . $filename);
        $objWriter->save($path);

        return $savePath . "/" . $filename;
    }

    /**
     * 写入单个Sheet的数据
     * @param \PHPExcel_Worksheet $objActSheet
     * @param array $list
     * @param array $title
     * @param int $startRow
     * @param array $mergeCells
     * @param array $imageFields
     * @param array $otherData
     */
    private static function writeSheetData($objActSheet, $list, $title, $startRow, $mergeCells, $imageFields, $otherData)
    {
        $header_arr = ['A','B','C','D','E','F','G','H','I','J','K','L','M', 'N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM', 'AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];

        array_unshift($list, $title);
        $indexKey = [];
        foreach ($title as $k => $v) {
            $indexKey[] = $k;
        }

        $imageWidth  = (int)($otherData['imageWidth'] ?? 220);
        $imageHeight = (int)($otherData['imageHeight'] ?? 70);
        $tempDir = null;
        if ($imageFields) {
            $tempDir = root_path() . 'public/uploads/excel_img/' . date('Ymd') . '/';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }
        }

        if ($mergeCells) {
            foreach ($mergeCells as $mv) {
                if (strpos($mv['merge'], ":") !== false) $objActSheet->mergeCells($mv['merge']);
                if (isset($mv['cell']) && isset($mv['name'])) $objActSheet->setCellValueExplicit($mv["cell"], $mv["name"], \PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }

        foreach ($list as $row) {
            $rowHasImage = false;
            foreach ($indexKey as $key => $value) {
                $cellValue = $row[$value] ?? '';
                $colLetter = $header_arr[$key];
                if ($imageFields && in_array($value, $imageFields, true) && preg_match('#^https?://#i', (string)$cellValue)) {
                    $localPath = self::resolveExportImage((string)$cellValue, $tempDir);
                    if ($localPath) {
                        $objActSheet->setCellValue($colLetter . $startRow, '');
                        $drawing = new \PHPExcel_Worksheet_Drawing();
                        $drawing->setPath($localPath);
                        $drawing->setCoordinates($colLetter . $startRow);
                        $drawing->setWidth($imageWidth);
                        $drawing->setHeight($imageHeight);
                        $drawing->setOffsetX(3);
                        $drawing->setOffsetY(3);
                        $drawing->setWorksheet($objActSheet);
                        $rowHasImage = true;
                        continue;
                    }
                }
                $objActSheet->setCellValueExplicit($colLetter . $startRow, $cellValue, \PHPExcel_Cell_DataType::TYPE_STRING);
            }
            if ($rowHasImage) {
                $objActSheet->getRowDimension($startRow)->setRowHeight(max(20, $imageHeight * 0.75));
            }
            $startRow++;
        }
    }

    /**
     * 下载远程图片并校验有效性，返回本地路径
     * @param string $url
     * @param string $dir
     * @return string|false
     */
    private static function resolveExportImage($url, $dir)
    {
        $contents = @file_get_contents($url);
        if ($contents === false || $contents === '') {
            return false;
        }
        $ext = 'png';
        if (function_exists('getimagesizefromstring')) {
            $info = @getimagesizefromstring($contents);
            if (!$info || empty($info['mime']) || strpos($info['mime'], 'image/') !== 0) {
                return false;
            }
            if ($info['mime'] === 'image/jpeg') {
                $ext = 'jpg';
            }
        }
        $filename = $dir . md5($url) . '.' . $ext;
        if (@file_put_contents($filename, $contents)) {
            return $filename;
        }
        return false;
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
