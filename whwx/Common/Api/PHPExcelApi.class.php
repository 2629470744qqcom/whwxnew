<?php
namespace Common\Api;
class PHPExcelApi{

	/**
	 * 导出数据为CSV文件
	 * @param array $data 数据
	 * @param string $filename 文件名
	 * @param string $download 是否直接下载，默认下载
	 * @return string zhangxinhe Mar 14, 2016
	 */
	static function exportExcel($data, $filename, $download = true){
		$filename = '/whwx/Runtime/Temp/' . $filename . '.csv';
		foreach($data as $key => $value){
			$str .= implode(',', str_replace(array(",", "\n", "\r", "\r\n"), '，', $value)) . "\n";
		}
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . $filename, $str);
		if($download){
			header('Location:' . $filename);
		}else{
			return $filename;
		}
	}

	/**
	 * 读取CSV文件，返回数据数组
	 * @param string $file CVS文件路径
	 *        zhangxinhe Dec 25, 2015
	 */
	static function improtExcel($file){
		if(substr($file, -3) == 'csv' && file_exists($file)){
			$file = file($file);
			foreach($file as $value){
				$value = iconv('GBK', 'UTF-8//IGNORE', $value);
				$data[] = explode(',', $value);
			}
			return $data;
		}
		return false;
	}
}
?>