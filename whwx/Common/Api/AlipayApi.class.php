<?php
namespace Common\Api;
/**
 * 支付宝支付接口
 * zhangxinhe Jan 19, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class AlipayApi{
	private $alipay_partner, $alipay_key;
	private $alipay_input_charset = 'utf-8';
	private $alipay_sign_type = 'MD5';
	private $alipay_payment_type = '1';
	private $alipay_gateway = 'https://mapi.alipay.com/gateway.do?';
	/**
	 * 构造函数
	 * @param string $alipay_partner 合作身份ID
	 * @param string $alipay_key 安全校验码
	 *        zhangxinhe Jan 19, 2016
	 */
	public function __construct(){
		$this->alipay_partner = C('alipay_partner');
		$this->alipay_key = C('alipay_key');
		if(!$this->alipay_partner || !$this->alipay_key){
			// throw new \Exception('参数不完整');
		}
	}
	/**
	 * 支付宝PC端支付
	 * @param string $out_trade_no 订单号
	 * @param string $subject 商品描述
	 * @param string $total_fee 总金额
	 * @param string $notify_url 回调通知URL地址
	 * @param string $notify_url 支付完成调整URL地址
	 * @param integer $type 支付类型 1、PC 2、Wap
	 *        zhangxinhe Jan 16, 2016
	 */
	public function alipay($out_trade_no, $subject, $total_fee, $notify_url, $return_url, $type = 1){
		$service = $type == 1 ? 'create_direct_pay_by_user' : 'alipay.wap.create.direct.pay.by.user';
		$data = array('service' => $service, 'partner' => $this->alipay_partner, '_input_charset' => $this->alipay_input_charset, 'sign_type' => $this->alipay_sign_type, 'notify_url' => C('site_url') . U($notify_url), 'return_url' => C('site_url') . U($return_url, array('id' => $out_trade_no)), 
			'out_trade_no' => $out_trade_no, 'subject' => $subject, 'payment_type' => $this->alipay_payment_type, 'total_fee' => $total_fee, 'seller_id' => $this->alipay_partner);
		$data['sign'] = $this->sign($data);
		$html = "<meta charset='UTF-8' /><div style='font-size:22px;'>订单处理中，请稍后……</div><form style='display:none' name='alipaysubmit' action='" . $this->alipay_gateway . "_input_charset=" . $this->alipay_input_charset . "' method='GET'>";
		foreach($data as $k => $v){
			$html .= '<input type="hidden" name="' . $k . '" value="' . $v . '"/>';
		}
		$html .= '<input type="submit"/></form><script>document.forms["alipaysubmit"].submit();</script>';
		exit($html);
	}
	/**
	 * 支付宝页面跳转处理
	 * zhangxinhe Jan 19, 2016
	 */
	public function alipayReturn(){
		if($this->verifyRequest($_GET['notify_id'])){
			if($_GET['sign'] == $this->sign($_GET)){
				return ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS');
			}
		}
		return false;
	}
	/**
	 * 支付宝异步通知处理
	 * zhangxinhe Jan 19, 2016
	 */
	public function alipayNotify(){
		if($this->verifyRequest($_POST['notify_id'])){
			if($_POST['sign'] == $this->sign($_POST)){
				return ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS');
			}
		}
		return false;
	}
	/**
	 * 验证请求是否来自支付宝
	 * zhangxinhe Jan 19, 2016
	 */
	private function verifyRequest($notify_id){
		if($notify_id){
			$result = file_get_contents('https://mapi.alipay.com/gateway.do?service=notify_verify&partner=' . $this->alipay_partner . '&notify_id=' . $_GET['notify_id']);
			return $result == 'true' ? true : false;
		}
		return false;
	}
	/**
	 * 支付签名
	 * @param string $data 数据
	 *        zhangxinhe Jan 19, 2016
	 */
	private function sign($data){
		ksort($data);
		foreach($data as $k => $v){
			if($k != 'sign' && $k != 'sign_type' && $v != ''){
				$str .= $k . '=' . $v . '&';
			}
		}
		$str = trim($str, '&') . $this->alipay_key;
		return md5($str);
	}
}