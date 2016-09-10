<?php
/**
 * 获取当前访问页面URL
 * @return string
 * zhangxinhe 2015-12-25
 */
function geturi(){
	$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
	$php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
	$path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	$relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
	if(strpos($relate_url, '.html')){
		$relate_url = str_replace('.html', '', $relate_url);
	}
	$uri = $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
	return $uri;
}
/**
 * 截取中文字符串
 * @param string $str 要截取的字符串
 * @param number $start 开始位置
 * @param number $length 截取长度
 * @param string $suffix 是否带省略号
 * @param string $charset 字符编码
 * @return string zhangxinhe 2015-12-25
 */
function msubstr($str, $start = 0, $length, $suffix = true, $charset = "utf-8"){
	$suffix = $suffix && strlen($str) > $length ? '...' : '';
	if(function_exists("mb_substr")){
		return mb_substr($str, $start, $length, $charset) . $suffix;
	}elseif(function_exists('iconv_substr')){
		return iconv_substr($str, $start, $length, $charset) . $suffix;
	}else{
		$re['utf-8'] = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
		$re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
		$re['gbk'] = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
		$re['big5'] = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
		preg_match_all($re[$charset], $str, $match);
		$slice = join("", array_slice($match[0], $start, $length));
		return $slice . $suffix;
	}
}
/**
 * 个性化时间格式
 * @param Unix时间戳 $time
 * @return string zhangxinhe 2015-12-25
 */
function formatTime($time){
	$today = strtotime(date('Y-m-d'));
	$yesterday = $today - 24 * 3600;
	$tomorrow = $today + 24 * 3600;
	if($time < $today && $time >= $yesterday){
		return '昨天 ' . date('H:i', $time);
	}elseif($time >= $today && $time < $tomorrow){
		return '今天 ' . date('H:i', $time);
	}elseif($time >= $tomorrow && $time < $tomorrow + 24 * 3600){
		return '明天 ' . date('H:i', $time);
	}else{
		return date('Y-m-d H:i', $time);
	}
}
/**
 * 生成随机字符串
 * @param number $length 长度
 * @param number $type 类型 1数字 2小写字母 3大写字母 4大小写字母 5数字+大小写 6数字+大小写+特殊符合
 *        zhangxinhe 2015-12-25
 */
function getRandomString($length = 32, $type = 5){
	switch($type){
		case 1 :
			$chars = '0123456789';
			break;
		case 2 :
			$chars = 'abcdefghijklmnopqrstuvwxyz';
			break;
		case 3 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
		case 4 :
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			break;
		case 6 :
			$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@#$%^&*';
			break;
		default :
			$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}
	$strlen = strlen($chars) - 1;
	for($i = 0; $i < $length; $i++){
		$str .= substr($chars, mt_rand(0, $strlen), 1);
	}
	return $str;
}
/**
 * 数组编码为XML
 * @param array $data 数据
 * @return mixed 编码后数据
 *         zhangxinhe Jan 18, 2016
 */
function xmlencode($data){
	$xml = new \SimpleXMLElement('<xml></xml>');
	arrayToXml($xml, $data);
	return $xml->asXML();
}
/**
 * 数组转换XML
 * @param object $xml XML对象
 * @param array $data 数据
 * @param string $item Item
 *        zhangxinhe Jan 18, 2016
 */
function arrayToXml($xml, $data, $item = 'item'){
	foreach($data as $key => $value){
		is_numeric($key) && $key = $item;
		if(is_array($value) || is_object($value)){
			$child = $xml->addChild($key);
			self::data2xml($child, $value, $item);
		}else{
			if(is_numeric($value)){
				$child = $xml->addChild($key, $value);
			}else{
				$child = $xml->addChild($key);
				$node = dom_import_simplexml($child);
				$cdata = $node->ownerDocument->createCDATASection($value);
				$node->appendChild($cdata);
			}
		}
	}
}
/**
 * XML转换为数组
 * @param string $xml XML数据
 *        zhangxinhe Jan 18, 2016
 */
function xmldecode($xml){
	$xml = new \SimpleXMLElement($xml);
	$data = array();
	foreach($xml as $key => $value){
		$data[$key] = strval($value);
	}
	return $data;
}
/**
 * 七牛云抓取文件
 * @param string $url 文件URL地址
 * zhangxinhe Jan 20, 2016
 */
function qiniuFetch($url){
	$encodedURL = str_replace(array('+', '/'), array('-', '_'), base64_encode($url));
	$encodedEntryURI = str_replace(array('+', '/'), array('-', '_'), base64_encode(C('QINIU_BUCKET')));
	$url = '/fetch/' . $encodedURL . '/to/' . $encodedEntryURI;
	$sign = hash_hmac('sha1', $url . "\n", C('QINIU_SK'), true);
	$token = C('QINIU_AK') . ':' . str_replace(array('+', '/'), array('-', '_'), base64_encode($sign));
	$header = array('Host: iovip.qbox.me', 'Content-Type:application/x-www-form-urlencoded', 'Authorization: QBox ' . $token);
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, trim('http://iovip.qbox.me' . $url, '\n'));
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, "");
	$result = json_decode(curl_exec($curl), true);
	curl_close($curl);
	return $result['key'] ? C('QINIU_HOST') . $result['key'] : false;
}
/**
 * 发起HTTP请求
 * @param string $url 请求地址
 * @param array $param URL参数
 * @param string $data POST数据
 * @param string $method 请求方法
 *        zhangxinhe 2015-12-25
 */
function http($url, $param, $data, $method = 'GET'){
	$url .= is_array($param) ? '?' . http_build_query($param) : '';
	$opts = array(CURLOPT_URL => $url, CURLOPT_TIMEOUT => 30, CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false);
	if($method == 'POST'){
		$opts[CURLOPT_POST] = true;
		$opts[CURLOPT_POSTFIELDS] = $data;
	}
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}
?>
