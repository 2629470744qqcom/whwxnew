<?php
namespace Common\Api;
/**
 * 微信消息加密解码
 * zhangxinhe 2015-12-25
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WechatCrypt{
    private $cyptKey = '';
    private $appId = '';
    /**
     * 构造方法，初始化加密KEY
     * @param string $key 加密KEY
     * @param string $appid 微信APPID
     * zhangxinhe 2015-12-25
     */
    public function __construct($key, $appid){
        if($key && $appid){
            $this->appId = $appid;
            $this->cyptKey = base64_decode($key.'=');
        }else{
        	throw new \Exception('缺少参数 APP_ID 和加密KEY!');
        }
    }
    /**
     * 微信明文消息加密
     * @param string $text 需要加密的字符串
     * @return string 密文字符串
     * zhangxinhe 2015-12-25
     */
    public function encrypt($text){
        $random = getRandomString(16);
        $size = pack("N", strlen($text));
        $text = $random.$size.$text.$this->appId;
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        $text = self::PKCS7Encode($text, mcrypt_enc_get_key_size($td));
        mcrypt_generic_init($td, $this->cyptKey, substr($this->cyptKey, 0, 16));
        $encrypt = mcrypt_generic($td, $text);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return base64_encode($encrypt);
    }
    /**
     * 微信密文解密
     * @param string $text 需要解密的字符串
     * @return string 明文字符串
     * zhangxinhe 2015-12-25
     */
    public function decrypt($encrypt){
        $encrypt = base64_decode($encrypt);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        mcrypt_generic_init($td, $this->cyptKey, substr($this->cyptKey, 0, 16));
        $decrypt = mdecrypt_generic($td, $encrypt);
        $decrypt = self::PKCS7Decode($decrypt, mcrypt_enc_get_key_size($td));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        if(strlen($decrypt) < 16){
            throw new \Exception("非法密文字符串！");
        }
        $decrypt = substr($decrypt, 16);
		$size = unpack("N", substr($decrypt, 0, 4));
        $size = $size[1];
        $appid = substr($decrypt, $size + 4);
        if($appid !== $this->appId){
            throw new \Exception("非法APP_ID！");
        }
        return substr($decrypt, 4, $size);
    }
    /**
     * PKCS7填充字符
     * @param string $text 被填充字符串
     * @param string $size Block长度
     * @return string 填充后字符串
     * zhangxinhe 2015-12-25
     */
    private static function PKCS7Encode($text, $size){
        $str_size = strlen($text);
        $pad_size = $size - ($str_size % $size);
        $pad_size = $pad_size ? : $size;
        $pad_chr = chr($pad_size);
        return str_pad($text, $str_size + $pad_size, $pad_chr, STR_PAD_RIGHT);
    }
    /**
     * 删除PKCS7填充的字符
     * @param string  $text 已填充的字符
     * @param integer $size Block长度
     * @return string 删除后字符串
     * zhangxinhe 2015-12-25
     */
    private static function PKCS7Decode($text, $size){
    	$pad_str = ord(substr($text, -1));
    	if ($pad_str < 1 || $pad_str > $size) {
            return '';
        } else {
            return substr($text, 0, strlen($text) - $pad_str);
        }
    }
}
