<?php
namespace Common\Api;
use Common\Api\WechatCrypt;
/**
 * 微信消息回复接口
 * zhangxinhe 2015-12-25
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WechatApi{
	//消息类型常量
	const MSG_TYPE_TEXT     			= 'text';
	const MSG_TYPE_IMAGE    			= 'image';
	const MSG_TYPE_VOICE    			= 'voice';
	const MSG_TYPE_VIDEO    			= 'video';
	const MSG_TYPE_MUSIC    			= 'music';
	const MSG_TYPE_NEWS     			= 'news';
	const MSG_TYPE_LINK     			= 'link';
	const MSG_TYPE_LOCATION 			= 'location';
	const MSG_TYPE_SHORTVIDEO			= 'shortvideo';
	const MSG_TYPE_EVENT    			= 'event';
	//事件类型常量
	const MSG_EVENT_SUBSCRIBE         	= 'subscribe';
	const MSG_EVENT_UNSUBSCRIBE			= 'unsubscribe';
	const MSG_EVENT_SCAN              	= 'SCAN';
	const MSG_EVENT_LOCATION          	= 'LOCATION';
	const MSG_EVENT_CLICK            	= 'CLICK';
	const MSG_EVENT_VIEW				= 'VIEW';
	const MSG_EVENT_MASSSENDJOBFINISH	= 'MASSSENDJOBFINISH';
	//微信推送的数据
	private $data = array();
	private static $token = '';
	private static $appId = '';
	private static $encodingAESKey = '';
	private static $msgSafeMode = false;
	/**
	 * 构造方法，实例化微信SDK
	 * @param string $token 微信后台填写的TOKEN
	 * @param string $appid 微信APPID (安全模式和兼容模式有效)
	 * @param string $key 消息加密KEY (EncodingAESKey)
	 * zhangxinhe 2015-12-25
	 */
	public function __construct($token, $appid = '', $encodingAESKey = ''){
		$_GET['encrypt_type'] == 'aes' && self::$msgSafeMode = true;
        if(self::$msgSafeMode){
            if(empty($encodingAESKey) || empty($appid)){
                throw new \Exception('缺少参数encodingAESKey或appid！');
            }
            self::$appId = $appid;
            self::$encodingAESKey = $encodingAESKey;
        }
        if($token){
            self::auth($token) || exit;
            if(IS_GET){
                exit($_GET['echostr']);
            }else{
                self::$token = $token;
                $xml = file_get_contents("php://input");
                $data = self::xml2data($xml);
                if(self::$msgSafeMode){
                	$data = self::extract($data['Encrypt']);
				}
				$this->data = $data;
            }
        }else{
        	throw new \Exception('缺少参数TOKEN！');
        }
    }
	/**
	 * 获取微信推送的数据
	 * @return array 转换为数组后的数据
	 * zhangxinhe 2015-12-25
	 */
	public function request(){
		return $this->data;
	}
	/**
	 * 回复微信发送的消息
	 * @param  array  $content 消息内容
	 * @param  string $type    消息类型
	 * zhangxinhe 2015-12-25
	 */
	public function response($content, $type = self::MSG_TYPE_TEXT){
		$data = array('ToUserName' => $this->data['FromUserName'], 'FromUserName' => $this->data['ToUserName'], 'CreateTime' => NOW_TIME, 'MsgType' => $type);
		$content = call_user_func(array(self, $type), $content);
		if($type == self::MSG_TYPE_TEXT || $type == self::MSG_TYPE_NEWS){
			$data = array_merge($data, $content);
		} else {
			$data[ucfirst($type)] = $content;
		}
		if(self::$msgSafeMode){
			$data = self::generate($data);
		}
		$xml = new \SimpleXMLElement('<xml></xml>');
		self::data2xml($xml, $data);
		exit($xml->asXML());
	}
	/**
	 * 数据XML编码
	 * @param  object $xml  XML对象
	 * @param  mixed  $data 数据
	 * @param  string $item 数字索引时的节点名称
	 * @return string 编码后的数据
	 * zhangxinhe 2015-12-25
	 */
	public static function data2xml($xml, $data, $item = 'item') {
		foreach ($data as $key => $value) {
			is_numeric($key) && $key = $item;
			if(is_array($value) || is_object($value)){
				$child = $xml->addChild($key);
				self::data2xml($child, $value, $item);
			} else {
				if(is_numeric($value)){
					$child = $xml->addChild($key, $value);
				} else {
					$child = $xml->addChild($key);
					$node  = dom_import_simplexml($child);
					$cdata = $node->ownerDocument->createCDATASection($value);
					$node->appendChild($cdata);
				}
			}
		}
	}
	/**
	 * XML数据解码
	 * @param  string $xml 原始XML字符串
	 * @return array       解码后的数组
	 * zhangxinhe 2015-12-25
	 */
	public static function xml2data($xml){
		$xml = new \SimpleXMLElement($xml);
		if(!$xml){
			throw new \Exception('非法XML');
		}
		$data = array();
		foreach ($xml as $key => $value) {
			$data[$key] = strval($value);
		}
		return $data;
	}
	/**
	 * 对数据进行签名认证，确保是微信发送的数据
	 * @param  string $token 微信开放平台设置的TOKEN
	 * @return boolean       true-签名正确，false-签名错误
	 * zhangxinhe 2015-12-25
	 */
	protected static function auth($token){
		$data = array($_GET['timestamp'], $_GET['nonce'], $token);
		sort($data, SORT_STRING);
		$signature = sha1(implode($data));
		return $signature === $_GET['signature'];
	}
	/**
	 * 验证并解密密文数据
	 * @param  string $encrypt 密文
	 * @return array           解密后的数据
	 * zhangxinhe 2015-12-25
	 */
	private static function extract($encrypt){
		$signature = self::sign($_GET['timestamp'], $_GET['nonce'], $encrypt);
		if($signature != $_GET['msg_signature']){
			throw new \Exception('数据签名错误！');
		}
		$WechatCrypt = new WechatCrypt(self::$encodingAESKey, self::$appId);
		$decrypt = $WechatCrypt->decrypt($encrypt);
		return self::xml2data($decrypt);
	}
	/**
	 * 加密并生成密文消息数据
	 * @param  array $data 获取到的加密的消息数据
	 * @return array       生成的加密消息结构
	 * zhangxinhe 2015-12-25
	 */
	private static function generate($data){
		$xml = new \SimpleXMLElement('<xml></xml>');
		self::data2xml($xml, $data);
		$xml = $xml->asXML();
		$WechatCrypt = new WechatCrypt(self::$encodingAESKey, self::$appId);
		$encrypt = $WechatCrypt->encrypt($xml);
		$nonce = mt_rand(0, 9999999999);
		$signature = self::sign(NOW_TIME, $nonce, $encrypt);
		return array('Encrypt' => $encrypt, 'MsgSignature' => $signature, 'TimeStamp' => NOW_TIME, 'Nonce' => $nonce);
	}
	/**
	 * 生成数据签名
	 * @param  string $timestamp 时间戳
	 * @param  string $nonce     随机数
	 * @param  string $encrypt   被签名的数据
	 * @return string            SHA1签名
	 * zhangxinhe 2015-12-25
	 */
	private static function sign($timestamp, $nonce, $encrypt){
		$sign = array(self::$token, $timestamp, $nonce, $encrypt);
		sort($sign, SORT_STRING);
		return sha1(implode($sign));
	}
	
	private static function text($content){
        $data['Content'] = $content;
        return $data;
    }
    
    private static function image($media){
        $data['MediaId'] = $media;
        return $data;
    }
    
    private static function voice($media){
        $data['MediaId'] = $media;
        return $data;
    }
    
    private static function video($video){
        $data = array();
        list($data['MediaId'], $data['Title'], $data['Description']) = $video;
        return $data;
    }
    
    private static function music($music){
        $data = array();
        list($data['Title'], $data['Description'], $data['MusicUrl'], $data['HQMusicUrl'], $data['ThumbMediaId']) = $music;
        return $data;
    }
    
    private static function news($news){
        $articles = array();
        foreach ($news as $key => $value) {
            list($articles[$key]['Title'], $articles[$key]['Description'], $articles[$key]['Url'], $articles[$key]['PicUrl']) = $value;
            if($key >= 9) break; //最多只允许10条图文信息
        }
        $data['ArticleCount'] = count($articles);
        $data['Articles']     = $articles;
        return $data;
    }
}