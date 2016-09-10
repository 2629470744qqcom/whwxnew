<?php
namespace Common\Api;
/**
 * 微信支付接口类
 * zhangxinhe Jan 15, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WeipayApi{
	private $appid, $appsecret, $mchid, $apikey;
	private $unifiedorderUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
	public function __construct(){
		$this->appid = C('site_appid');
		$this->appsecret = C('site_appsecret');
		$this->mchid = C('site_mchid');
		$this->apikey = C('site_apikey');
		if(!$this->appid || !$this->appsecret || !$this->mchid || !$this->apikey){
			throw new \Exception('参数不完整！');
		}
	}
	/**
	 * 微信支付H5页面JS方式
	 * @param string $out_trade_no 订单号
	 * @param string $body 商品描述
	 * @param string $total_fee 总金额
	 * @param string $notify_url 回调通知URL地址
	 * @param string $notify_url 支付完成调整URL地址
	 *        zhangxinhe Jan 16, 2016
	 */
	public function weipayJs($out_trade_no, $body, $total_fee, $notify_url, $return_url){
		$ordersInfo = $this->unifiedorder($out_trade_no, $body, $total_fee, $notify_url);
		if(!$ordersInfo['prepay_id']){
			throw new \Exception('获取prepay_id失败');
		}
		$data = array('appId' => $this->appid, 'timeStamp' => (string)time(), 'nonceStr' => getRandomString(), 'package' => 'prepay_id=' . $ordersInfo['prepay_id'], 'signType' => 'MD5');
		$data['paySign'] = $this->sign($data);
		$html = "<script type='text/javascript'>function jsApiCall(){WeixinJSBridge.invoke('getBrandWCPayRequest', " . json_encode($data) . ", function(res){if(res.err_msg == 'get_brand_wcpay_request:ok'){alert('支付成功！');}else{alert('支付失败或被取消，请重试！');}location.href = '" . C('site_url') . U($return_url, array('id' => $out_trade_no)) . "'});}";
		$html .= "window.onload = function(){if(typeof WeixinJSBridge == 'undefined'){if( document.addEventListener ){document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);}else if (document.attachEvent){document.attachEvent('WeixinJSBridgeReady', jsApiCall);document.attachEvent('onWeixinJSBridgeReady', jsApiCall);}else{jsApiCall();}}};</script>";
		exit($html);
	}
	/**
	 * 微信扫码支付
	 * @param string $out_trade_no 订单号
	 * @param string $body 商品描述
	 * @param string $total_fee 总金额
	 * @param string $notify_url 回调通知URL地址
	 *        zhangxinhe Jan 16, 2016
	 */
	public function weipayNative($out_trade_no, $body, $total_fee, $notify_url){
		$ordersInfo = $this->unifiedorder($out_trade_no, $body, $total_fee, $notify_url, 'NATIVE');
		if(!$ordersInfo['code_url']){
			throw new \Exception('获取code_url失败');
		}
		return $ordersInfo['code_url'];
	}
	/**
	 * 支付完成通知处理
	 * zhangxinhe Jan 18, 2016
	 */
	public function weipayNotify(){
		$data = xmldecode($GLOBALS['HTTP_RAW_POST_DATA']);
		if($data['sign'] == $this->sign($data)){
			if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS'){
				return array('out_trade_no' => $data['out_trade_no'], 'total_fee' => $data['total_fee'], 'time_end' => $data['time_end'], 'transaction_id' => $data['transaction_id']);
			}
		}
		return false;
	}
	/**
	 * 统一下单接口
	 * @param string $out_trade_no 订单号
	 * @param string $body 产品名称
	 * @param string $total_fee 订单金额
	 * @param string $notify_url 回调通知URL
	 * @param string $trade_type 交易类型
	 *        zhangxinhe Jan 18, 2016
	 */
	private function unifiedorder($out_trade_no, $body, $total_fee, $notify_url, $trade_type = 'JSAPI'){
		$total_fee = $total_fee * 100;
		$notify_url = C('site_url') . U($notify_url);
		$nonce_str = getRandomString();
		$spbill_create_ip = get_client_ip();
		$openid = session('fansInfo.openid');
		if(!$out_trade_no || !$body || !$total_fee || !$notify_url || !$nonce_str || !$spbill_create_ip || !$openid){
			throw new \Exception('参数不完整！');
		}
		$data = array('appid' => $this->appid, 'mch_id' => $this->mchid, 'out_trade_no' => $out_trade_no, 'body' => $body, 'total_fee' => $total_fee, 'notify_url' => $notify_url, 'trade_type' => $trade_type, 'nonce_str' => $nonce_str, 'spbill_create_ip' => $spbill_create_ip, 'openid' => $openid);
		$data['sign'] = $this->sign($data);
		$data = xmlencode($data);
		$result = http($this->unifiedorderUrl, null, $data, 'POST');
		return xmldecode($result);
	}
	/**
	 * 支付签名
	 * @param array $data 参与签名的数据
	 *        zhangxinhe Jan 18, 2016
	 */
	private function sign($data){
		ksort($data);
		foreach($data as $k => $v){
			if($k != 'sign' && $v != ''){
				$str .= $k . '=' . $v . '&';
			}
		}
		$str = trim($str, '&');
		$str .= '&key=' . $this->apikey;
		return strtoupper(md5($str));
	}
}
