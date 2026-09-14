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
     * 表头文本归一化：BOM、全角字符、首尾空白、尾部冒号/星号等噪声。
     * 仅用于“识别表头”，不影响单元格写入值。
     * @param string $header
     * @return string
     */
    public static function normalizeHeaderText($header)
    {
        $header = (string)$header;
        // 去 BOM（xlsx 首格常见：UTF-8 BOM / UTF-16 BOM）
        $header = str_replace(["\xEF\xBB\xBF", "\xFF\xFE", "\xFE\xFF"], '', $header);
        // 全角字母数字 → 半角（如 ＳＫＵ → SKU）；扩展缺失时跳过
        if (function_exists('mb_convert_kana')) {
            $header = mb_convert_kana($header, 'as');
        }
        // 全角标点 → 半角
        $header = str_replace(
            ['：', '（', '）', '，', '　', '．', '／', '＊', '＃', '？', '！'],
            [':', '(', ')', ',', ' ', '.', '/', '*', '#', '?', '!'],
            $header
        );
        // 首尾空白与尾部噪声字符（部分模板用 “状态*”“关联SKU:” 标注）。
        // 注意：trim/rtrim 的字符集合按字节匹配，这里只能使用 ASCII 字符，
        // 否则会截断以中文结尾的表头（如“状态”末字节 0x81 会被误删）。
        return trim(rtrim(trim($header), ":*#?!"));
    }

    /**
     * 构建表头查找表：原始键精确匹配 + 归一化（忽略大小写/冒号）匹配。
     * @param array $headerMap 表头 => 字段
     * @return array [rawMap, normalizedMap]
     */
    protected static function buildHeaderLookup(array $headerMap)
    {
        $rawMap = [];
        $normalizedMap = [];
        foreach ($headerMap as $header => $field) {
            $rawMap[(string)$header] = $field;
            $normalizedMap[strtolower(self::normalizeHeaderText($header))] = $field;
        }
        return [$rawMap, $normalizedMap];
    }

    /**
     * 命中表头对应字段：先原样精确匹配，再归一化（忽略大小写、忽略尾部冒号）匹配。
     * @param string $header
     * @param array  $lookup
     * @return string|null
     */
    protected static function matchHeaderField($header, array $lookup)
    {
        $raw = trim((string)$header);
        if ($raw !== '' && isset($lookup[0][$raw])) {
            return $lookup[0][$raw];
        }
        $normalized = strtolower(self::normalizeHeaderText($raw));
        if ($normalized !== '' && isset($lookup[1][$normalized])) {
            return $lookup[1][$normalized];
        }
        return null;
    }

    /**
     * 按首行标题识别字段，允许列顺序调整并忽略未知列。
     *
     * 兼容规则（保证历史/旧模板、目标模板都能导入）：
     * - 表头匹配忽略大小写、忽略尾部冒号（如 sku/SKU、关联SKU:/关联SKU）；
     * - 同一字段被多列命中（如同时存在 SKU 与 sku）时，保留最左列，其余列忽略并写日志，
     *   避免“后出现的列覆盖先出现列”的隐式行为。
     */
    public static function importExcelByHeader($filePath, array $headerMap, $other = [], $startRow = 2, $imageFields = null)
    {
        if (!file_exists($filePath)) return [];
        require_once root_path() . '/extend/PHPExcel/PHPExcel.php';
        require_once root_path() . '/extend/PHPExcel/PHPExcel/Writer/Excel2007.php';
        $reader = new \PHPExcel_Reader_Excel2007();
        $excel = $reader->load($filePath, 'utf-8');
        $sheet = $excel->getSheet(0);
        $highestColumn = \PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
        $lookup = self::buildHeaderLookup($headerMap);
        $fields = [];
        $ignoredHeaders = [];
        $duplicatedHeaders = [];
        for ($index = 0; $index < $highestColumn; $index++) {
            $rawHeader = (string)$sheet->getCellByColumnAndRow($index, 1)->getValue();
            $field = self::matchHeaderField($rawHeader, $lookup);
            if ($field === null) {
                $fields[] = '__ignore_' . $index;
                if (trim($rawHeader) !== '') {
                    $ignoredHeaders[] = ['column' => $index + 1, 'header' => trim($rawHeader)];
                }
                continue;
            }
            if (in_array($field, $fields, true)) {
                $duplicatedHeaders[] = ['column' => $index + 1, 'header' => trim($rawHeader), 'field' => $field];
                $fields[] = '__ignore_' . $index;
                continue;
            }
            $fields[] = $field;
        }
        $rows = self::importExcel($filePath, $fields, $other, $startRow, $imageFields);
        if (!is_array($rows)) return $rows;
        foreach ($rows as &$row) {
            foreach (array_keys($row) as $field) {
                if (strpos($field, '__ignore_') === 0) unset($row[$field]);
            }
        }
        unset($row);
        if ($ignoredHeaders) actionLog($ignoredHeaders, 'importExcelByHeader_ignored_headers');
        if ($duplicatedHeaders) actionLog($duplicatedHeaders, 'importExcelByHeader_duplicated_headers');
        return $rows;
    }

    /**
     * 功能：导入excel表格
     * @param $filePath
     * @param array $list
     * @param array $other
     * @return array|string
     */
    public static function importExcel($filePath, $list=[],$other = [],$startRow = 2,$imageFields = null)
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
                    // 递归创建：首次部署时 uploads/excel_img 上级目录可能尚不存在，非递归 mkdir 会导致整份表格导入失败
                    @mkdir($imageFilePath, 0777, true);
                    @chmod($imageFilePath,0777);
                }
                $imgList = self::getImg($sheet,$imageFilePath);
                if (is_string($imgList)) return returnState(100,$imgList);
                $ignoredImgList = [];
                for ($i = $startRow; $i <= $highestRow; $i++) {
                    $row = [];
                    foreach ($list as $key => $value) {
                        $cellName = $header_arr[$key] . $i;
                        if (isset($imgList[$cellName]) && ($imageFields === null || in_array($value, $imageFields, true))) {
                            $row[$value] = $imgList[$cellName];
                        } else {
                            if (isset($imgList[$cellName])) {
                                $ignoredImgList[] = ['cell' => $cellName, 'field' => $value, 'image' => $imgList[$cellName]];
                            }
                            $cellValue = $objPHPExcel->getActiveSheet()->getCell($cellName)->getValue();
                            // 富文本单元格取值为 PHPExcel_RichText 对象，转纯文本后再使用，
                            // 否则后续校验/入库会出现“对象无法转换为字符串”的错误。
                            if ($cellValue instanceof \PHPExcel_RichText) {
                                $cellValue = $cellValue->getPlainText();
                            }
                            if ($cellValue === null) $cellValue = "";
                            $row[$value] = $cellValue;
                        }
                    }
                    if ($other) {
                        $row = array_merge($row, $other);
                    }
                    if ($row) $data[] = $row;
                }
                if ($ignoredImgList) actionLog($ignoredImgList, 'importExcel_ignored_images');
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
            $tempDir = root_path() . 'public/uploads/excel_img_export/' . date('Ymd') . '/';
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
        $lastDataRow = $startRow - 1;
        $lastColumn = $header_arr[max(0, count($indexKey) - 1)];
        if (isset($otherData['columnWidth'])) {
            foreach (array_slice($header_arr, 0, count($indexKey)) as $column) {
                $objActSheet->getColumnDimension($column)->setWidth((float)$otherData['columnWidth']);
            }
        }
        if (!empty($otherData['wrapText']) && $lastDataRow > 0) {
            $objActSheet->getStyle('A1:' . $lastColumn . $lastDataRow)->getAlignment()->setWrapText(true);
        }
        if (!empty($otherData['vertical']) && $lastDataRow > 0) {
            $objActSheet->getStyle('A1:' . $lastColumn . $lastDataRow)->getAlignment()->setVertical($otherData['vertical']);
        }
        if (!empty($otherData['rowHeights']) && is_array($otherData['rowHeights'])) {
            foreach ($otherData['rowHeights'] as $rowNumber => $height) {
                $objActSheet->getRowDimension((int)$rowNumber)->setRowHeight((float)$height);
            }
        }
        if (!empty($otherData['boldRows']) && is_array($otherData['boldRows'])) {
            foreach ($otherData['boldRows'] as $rowNumber) {
                $objActSheet->getStyle('A' . (int)$rowNumber . ':' . $lastColumn . (int)$rowNumber)
                    ->getFont()->setBold(true);
            }
        }
        if (!empty($otherData['fontSizeRows']) && is_array($otherData['fontSizeRows'])) {
            foreach ($otherData['fontSizeRows'] as $rowNumber => $fontSize) {
                $objActSheet->getStyle('A' . (int)$rowNumber . ':' . $lastColumn . (int)$rowNumber)
                    ->getFont()->setSize((float)$fontSize);
            }
        }
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
            $tempDir = root_path() . 'public/uploads/excel_img_export/' . date('Ymd') . '/';
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
