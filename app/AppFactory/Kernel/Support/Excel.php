<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/13
 * Time: 16:04
 */

namespace app\AppFactory\Kernel\Support;


class Excel
{

    /**
     * 功能：导入excel表格
     * @param $filePath
     * @param array $list
     * @param array $other
     * @return array|string
     */
    public static function importExcel($filePath, $list=[],$other = [])
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

                $imageFilePath='./uploads/goods_img/'.date('Ymd').'/';//图片在本地存储的路径
                if (!file_exists ($imageFilePath)) {
                    mkdir("$imageFilePath");
                    chmod($imageFilePath,0777);
                }
                $imgList = self::getImg($sheet,$imageFilePath);

                //接下来就是写数据到表格里面去
                for ($i = 2; $i <= $highestRow; $i++) {
                    $row = [];
                    foreach ($list as $key => $value) {
                        if (!isset($imgList[$header_arr[$key] . $i])) {
                            $row[$value] = $objPHPExcel->getActiveSheet()->getCell($header_arr[$key] . $i)->getValue();
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
     * @return array
     */
    public static function getImg(\PHPExcel_Worksheet $worksheet,$imageFilePath)
    {
        $data = [];
        foreach ($worksheet->getDrawingCollection() as $drawing) {
            $xy=$drawing->getCoordinates();//得到单元数据 比如G2单元
            if ($drawing instanceof \PHPExcel_Worksheet_Drawing) {//支持excel2007后缀为（.xlsx）
                $filename = $drawing->getPath();
                $imgData = file_get_contents($filename);
                $imageFileName = $drawing->getIndexedFilename();
                $type = explode(".", $imageFileName);
                $imageName = $imageFilePath . md5(time() . rand(00000000,99999999)) . '.' . $type[1];
                if (file_put_contents($imageName, $imgData)) {
                    $data[$xy] = substr($imageName, 1);
                }  //把文件保存到本地
            }elseif($drawing instanceof \PHPExcel_Worksheet_MemoryDrawing) {//支持excel2003后缀为（.xls）
                $imageFileNames = $drawing->getIndexedFilename();
                ob_start();
                call_user_func(
                    $drawing->getRenderingFunction(),
                    $drawing->getImageResource()
                );
                $imageContents = ob_get_contents();
                ob_end_clean();
                $type = explode(".",$imageFileNames);
                $imageName = $imageFilePath.md5(time().rand(00000000,99999999)).'.'.$type[1];
                if (file_put_contents($imageName,$imageContents)){
                    $data[$xy] = substr($imageName,1);
                }  //把文件保存到本地
            }
        }
        return $data;
    }
}