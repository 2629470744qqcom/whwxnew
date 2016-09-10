<?php
namespace Common\Api;
/**
 * 微信高级应用相关接口
 * zhangxinhe 2015-12-25
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WechatAuthApi{
	private $appid, $appsecret, $access_token;
	private $apiURL = 'https://api.weixin.qq.com';
	private $qrcodeURL = 'https://mp.weixin.qq.com/cgi-bin';
	private $requestCodeURL = 'https://open.weixin.qq.com/connect/oauth2/authorize';
	private $oauthApiURL = 'https://api.weixin.qq.com/sns';

	/**
	 * 构造方法，调用微信高级接口时实例化SDK
	 * @param string $appid 微信appid
	 * @param string $appsecret 微信appsecret
	 * @param string $access_token 微信令牌
	 *        zhangxinhe 2015-12-25
	 */
	public function __construct($appid, $appsecret, $access_token = null){
		if($appid && $appsecret){
			$this->appid = $appid;
			$this->appsecret = $appsecret;
			$access_token && $this->access_token = $access_token;
		}else{
			throw new \Exception('缺少参数appid和appsecret');
		}
	}

	/**
	 * 获取用户授权CODE
	 * @param string $redirect_uri 回调地址
	 * @param string $state 自定义参数
	 * @param string $scope 授权作用域 (snsapi_userinfo, snsapi_base)
	 * @return string URL地址
	 *         zhangxinhe 2015-12-25
	 */
	public function getRequestCodeURL($redirect_uri, $state = null, $scope = 'snsapi_userinfo'){
		$query = array('appid' => $this->appid, 'redirect_uri' => $redirect_uri, 'response_type' => 'code', 'scope' => $scope);
		if(!is_null($state) && preg_match('/[a-zA-Z0-9]+/', $state)){
			$query['state'] = $state;
		}
		$query = http_build_query($query);
		return "{$this->requestCodeURL}?{$query}#wechat_redirect";
	}

	/**
	 * 获取access_token
	 * @param string $type 类型
	 * @param string $code CODE 获取用户信息时用
	 * @return string access_token
	 *         zhangxinhe 2015-12-25
	 */
	public function getAccessToken($type = 'client', $code = null){
		$param = array('appid' => $this->appid, 'secret' => $this->appsecret);
		if($type == 'code'){
			$param['code'] = $code;
			$param['grant_type'] = 'authorization_code';
			$url = "{$this->oauthApiURL}/oauth2/access_token";
		}else{
			$param['grant_type'] = 'client_credential';
			$url = "{$this->apiURL}/cgi-bin/token";
		}
		
		$access_token_info = json_decode(self::http($url, $param), true);
		if($access_token_info['access_token']){
			$this->access_token = $access_token_info['access_token'];
			return $type == 'code' ? $access_token_info : $access_token_info['access_token'];
		}
		return false;
	}

	/**
	 * 获取粉丝列表
	 * @param string $next_openid 下一个openid，在用户数大于10000时有效
	 * @return array 用户列表
	 *         zhangxinhe 2015-12-25
	 */
	public function userGet($next_openid = ''){
		$param = array('next_openid' => $next_openid);
		return $this->api('cgi-bin/user/get', '', 'GET', $param);
	}

	/**
	 * 批量获取粉丝信息
	 * @param array $openid 粉丝OpenID
	 * @return array 粉丝数据
	 *         zhangxinhe 2015-12-25
	 */
	public function userGetMulit($openid){
		foreach($openid as $k => $v){
			$data[] = array('lang' => 'zh-CN', 'openid' => $v);
		}
		return $this->api('cgi-bin/user/info/batchget', array('user_list' => $data));
	}

	/**
	 * 获取指定用户的详细信息
	 * @param string $openid 用户的openid
	 * @param string $lang 需要获取数据的语言
	 *        zhangxinhe 2015-12-25
	 */
	public function userInfo($openid, $lang = 'zh_CN'){
		$param = array('openid' => $openid, 'lang' => $lang);
		return $this->api('cgi-bin/user/info', '', 'GET', $param);
	}

	/**
	 * 获取授权用户信息
	 * @param string $token access_token
	 * @param string $lang 返回信息语言
	 * @return array 用户信息
	 *         zhangxinhe 2015-12-25
	 */
	public function getUserInfo($token, $lang = 'zh_CN'){
		$param = array('access_token' => $token['access_token'], 'openid' => $token['openid'], 'lang' => $lang);
		$info = self::http("{$this->oauthApiURL}/userinfo", $param);
		return json_decode($info, true);
	}

	/**
	 * 给48小时内发过消息的粉丝发送消息（客服接口）
	 * @param string $openid 粉丝ID
	 * @param string $content 消息内容
	 * @param string $type 消息类型
	 * @return array 发送结果
	 *         zhangxinhe 2015-12-25
	 */
	public function sendMsg($openid, $content, $type = 'text'){
		$data = array('touser' => $openid, 'msgtype' => $type);
		$data[$type] = call_user_func(array(self, $type), $content);
		return $this->api('cgi-bin/message/custom/send', $data);
	}

	/**
	 * 发送模板消息
	 * @param string $openid 粉丝ID
	 * @param string $template_id 模板ID
	 * @param string $url 链接地址
	 * @param array $content 消息内容
	 *        zhangxinhe Jan 8, 2016
	 */
	public function sendTemplateMsg($openid, $template_id, $url, $content){
		array_walk_recursive($content, 'str_trim');
		$data = array('touser' => $openid, 'template_id' => $template_id, 'url' => C('site_url') . $url, 'data' => $content);
		return $this->api('cgi-bin/message/template/send', $data);
	}

	/**
	 * 模板消息数据格式化
	 *        zhangxinhe Jan 8, 2016
	 */
	public function str_trim(&$v){
		$v = strip_tags(trim($v));
	}

	/**
	 * 创建自定义菜单
	 * @param array $button zhangxinhe 2015-12-25
	 */
	public function menuCreate($button){
		$data = array('button' => $button);
		return $this->api('cgi-bin/menu/create', $data);
	}
	/**
	 * 下载微信文件到七牛云
	 * @param string $media_id 文件ID
	 * zhangxinhe Mar 8, 2016
	 */	
	public function getFileToQiniu($media_id){
		return qiniuFetch($this->apiURL . '/cgi-bin/media/get?access_token=' . $this->access_token . '&media_id=' . $media_id);
	}
	/**
	 * 获取所有的自定义菜单
	 * @return array 自定义菜单数组
	 *         zhangxinhe 2015-12-25
	 */
	public function menuGet(){
		return $this->api('cgi-bin/menu/get', '', 'GET');
	}

	/**
	 * 删除自定义菜单
	 * @return array 自定义菜单数组
	 *         zhangxinhe 2015-12-25
	 */
	public function menuDelete(){
		return $this->api('cgi-bin/menu/delete', '', 'GET');
	}

	/**
	 * 获取ApiTicket
	 * zhangxinhe 2015-12-25
	 */
	public function getApiTicket($type = 'jsapi'){
		return $this->api('cgi-bin/ticket/getticket', null, 'GET', array('type' => $type));
	}

	/**
	 * 获取JSSDK签名包
	 * @param string $nonceStr 随机字符串
	 * @param string $timestamp 时间戳
	 *        zhangxinhe 2015-12-25
	 */
	public function getJsSignPackage($nonceStr = null, $timestamp = null){
		// $ticketData = json_decode(file_get_contents(DATA_PATH."_wxdata/js_api_ticket_".$this->appid.".json"), true);
		$ticketData = F('jsapi_ticket');
		if($ticketData['expire_time'] < time()){
			$jsTicket = self::getApiTicket();
			if($jsTicket){
				$ticketData['expire_time'] = time() + 7000;
				$ticketData['js_api_ticket'] = $jsTicket['ticket'];
				F('jsapi_ticket', $ticketData);
				// $fp = fopen(DATA_PATH."_wxdata/js_api_ticket_".$this->appid.".json", "w");
				// fwrite($fp, json_encode($ticketData));
				// fclose($fp);
			}else{
				throw new \Exception('获取JsTicket失败！');
			}
		}
		// 生成URL
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$nonceStr = $nonceStr ? $nonceStr : getRandomString(16);
		$timestamp = $timestamp ? $timestamp : time();
		$string = "jsapi_ticket=" . $ticketData['js_api_ticket'] . "&noncestr=" . $nonceStr . "&timestamp=" . $timestamp . "&url=" . $url;
		$signature = sha1($string);
		return array("appid" => $this->appid, "nonceStr" => $nonceStr, "timestamp" => $timestamp, "url" => $url, "signature" => $signature, "rawString" => $string);
	}

	/**
	 * 获取卡券领取签名包
	 * @param string $card_id 卡券ID
	 * @param string $nonceStr 随机字符串
	 * @param string $timestamp 时间戳
	 *        zhangxinhe 2015-12-25
	 */
	public function getCardSignPackage($card_id, $nonceStr = null, $timestamp = null){
		// $ticketData = json_decode(file_get_contents(DATA_PATH."_wxdata/card_api_ticket_".$this->appid.".json"), true);
		$ticketData = F('cardapi_ticket');
		if($ticketData['expire_time'] < time()){
			$cardTicket = self::getApiTicket('wx_card');
			if($cardTicket){
				$ticketData['expire_time'] = time() + 7000;
				$ticketData['card_api_ticket'] = $cardTicket['ticket'];
				F('cardapi_ticket', $ticketData);
				// $fp = fopen(DATA_PATH."_wxdata/card_api_ticket_".$this->appid.".json", "w");
				// fwrite($fp, json_encode($ticketData));
				// fclose($fp);
			}else{
				throw new \Exception('获取CardTicket失败！');
			}
		}
		
		$nonceStr = $nonceStr ? $nonceStr : getRandomString(16);
		$timestamp = $timestamp ? $timestamp : time();
		$data = array('card_id' => $card_id, 'timestamp' => $timestamp, 'api_ticket' => $ticketData['card_api_ticket'], 'nonce_str' => $nonceStr);
		sort($data, SORT_STRING);
		return array('timestamp' => $timestamp, 'nonce_str' => $nonceStr, 'signature' => sha1(implode('', $data)));
	}

	/**
	 * 创建二维码，可创建指定有效期的二维码和永久二维码
	 * @param integer $scene_id 二维码参数
	 * @param integer $expire_seconds 二维码有效期，0-永久有效
	 *        zhangxinhe 2015-12-25
	 */
	public function qrcodeCreate($scene_id, $expire_seconds = 0){
		$data = array();
		if(is_numeric($expire_seconds) && $expire_seconds > 0){
			$data['expire_seconds'] = $expire_seconds;
			$data['action_name'] = 'QR_SCENE';
		}else{
			$data['action_name'] = 'QR_LIMIT_SCENE';
		}
		$data['action_info']['scene']['scene_id'] = $scene_id;
		return $this->api('cgi-bin/qrcode/create', $data);
	}

	/**
	 * 根据ticket获取二维码URL
	 * @param string $ticket 通过 qrcodeCreate接口获取到的ticket
	 * @return string 二维码URL
	 *         zhangxinhe 2015-12-25
	 */
	public function showqrcode($ticket){
		return "{$this->qrcodeURL}/showqrcode?ticket={$ticket}";
	}

	/**
	 * 长链接转短链接
	 * @param string $long_url 长链接
	 * @return string 短链接
	 *         zhangxinhe 2015-12-25
	 */
	public function shorturl($long_url){
		$data = array('action' => 'long2short', 'long_url' => $long_url);
		return $this->api('cgi-bin/shorturl', $data);
	}

	/**
	 * 调用微信api获取响应数据
	 * @param string $name API名称
	 * @param string $data POST请求数据
	 * @param string $method 请求方式
	 * @param string $param GET请求参数
	 * @return array api返回结果
	 */
	protected function api($name, $data = '', $method = 'POST', $param = ''){
		$params = array('access_token' => $this->access_token);
		if(!empty($param) && is_array($param)){
			$params = array_merge($params, $param);
		}
		$url = "{$this->apiURL}/{$name}";
		if(!empty($data)){
			array_walk_recursive($data, function (&$value){
				if(!is_bool($value)){
					$value = preg_match("/^\d+$/", $value) ? intval($value) : urlencode($value);
				}
			});
			$data = urldecode(json_encode($data));
		}
		$data = self::http($url, $params, $data, $method);
		return json_decode($data, true);
	}

	/**
	 * 发送HTTP请求方法，目前只支持CURL发送请求
	 * @param string $url 请求URL
	 * @param array $param GET参数数组
	 * @param array $data POST的数据，GET请求时该参数无效
	 * @param string $method 请求方法GET/POST
	 * @param string $cert 证书文件
	 * @return array 响应数据
	 */
	protected static function http($url, $param, $data = '', $method = 'GET', $cert = null){
		$opts = array(CURLOPT_TIMEOUT => 30, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false);
		
		$opts[CURLOPT_URL] = $url . '?' . http_build_query($param);
		if(strtoupper($method) == 'POST'){
			$opts[CURLOPT_POST] = 1;
			$opts[CURLOPT_POSTFIELDS] = $data;
			if(is_string($data)){
				$opts[CURLOPT_HTTPHEADER] = array('Content-Type: application/json; charset=utf-8', 'Content-Length: ' . strlen($data));
			}
		}
		
		if($cert){
			$opts[CURLOPT_SSLCERT] = 'PEM';
			$opts[CURLOPT_SSLCERT] = "/home/niu/work/www/weilt_new/cert/apiclient_cert.pem";
			$opts[CURLOPT_SSLKEYTYPE] = 'PEM';
			$opts[CURLOPT_SSLKEY] = "/home/niu/work/www/weilt_new/cert/apiclient_key.pem";
		}
		
		$ch = curl_init();
		curl_setopt_array($ch, $opts);
		$data = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		return $data;
	}

	private static function sign($data, $key){
		$data = array_filter($data);
		ksort($data);
		foreach($data as $k => $v){
			$str .= $k . '=' . $v . '&';
		}
		$str .= 'key=' . $key;
		return strtoupper(md5($str));
	}

	private static function text($content){
		$data['content'] = $content;
		return $data;
	}

	private static function image($media){
		$data['media_id'] = $media;
		return $data;
	}

	private static function voice($media){
		$data['media_id'] = $media;
		return $data;
	}

	private static function video($video){
		$data = array();
		list($data['media_id'], $data['title'], $data['description']) = $video;
		return $data;
	}

	private static function music($music){
		$data = array();
		list($data['title'], $data['description'], $data['musicurl'], $data['hqmusicurl'], $data['thumb_media_id']) = $music;
		return $data;
	}

	private static function news($news){
		$articles = array();
		foreach($news as $key => $value){
			list($articles[$key]['title'], $articles[$key]['description'], $articles[$key]['url'], $articles[$key]['picurl']) = $value;
			if($key >= 9)
				break; // 最多只允许10条图文信息
		}
		$data['articles'] = $articles;
		return $data;
	}
}
