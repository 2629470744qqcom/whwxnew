<?php
namespace Common\Api;
/**
 * 公共通用接口
 * zhangxinhe 2015-12-25
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class CommonApi{
	/**
	 * 初始化微信高级接口
	 * zhangxinhe 2015-12-25
	 */
	public static function wechatAuthInfo(){
		$access_token = F('access_token');
		if ($access_token['expire_in'] < time()){
			$wechatAuth = new WechatAuthApi(C('site_appid'), C('site_appsecret'));
			$access_token = $wechatAuth->getAccessToken();
			F('access_token', array('access_token' => $access_token, 'expire_in' => time() + 7000));
			return $wechatAuth;
		}else{
			return new WechatAuthApi(C('site_appid'), C('site_appsecret'), $access_token['access_token']);
		}
	}
	/**
	 * 添加消息记录
	 * @param intger $fid 粉丝ID
	 * @param string $content 消息内容
	 * @param number $type 消息类型 0 关键字消息（粉丝发） 1 普通消息（粉丝发 默认） 2 回复消息（公众号发）
	 * zhangxinhe 2015-12-25
	 */
	public static function addWxmsg($fid, $content, $type = 1){
		return M('wxmsg')->add(array('fid' => $fid, 'type' => $type, 'times' => time(), 'content' => $content));
	}
	/**
	 * 添加信息跟踪
	 * @param intger $oid
	 * @param string $oname
	 * @param string $otel
	 * @param number $type 报修： 1 ，特惠团： 2 ，租房售房： 3 ，预约： 4, 投诉建议 ：5
	 * @param intger $typeid
	 * @param string $content
	 * huying Dec 30, 2015
	 */
	public static function addFollow($oid, $oname, $otel, $type, $typeid, $content){
		return M('follow')->add(array('oid' => $oid, 'oname' => $oname, 'otel' => $otel, 'type' => $type, 'typeid' => $typeid, 'times' => time(), 'content' => $content));
	}
	/**
	 * 添加业主的通知
	 * @param intger $oid 身份的id
	 * @param string $title 标题
	 * @param string $desc 说明
	 * @param intger $type 类型（1：系统公告；2：报修进度处理，3：亲属/租客申请，4：订单处理通知,5:投诉建议受理通知,6:预约处理）
	 * @param intger $typeid 类型id
	 * huying Jan 13, 2016
	 */
	public static function addNotice($oid, $title, $desc, $type, $typeid){
		return M('owner_notice')->add(array('oid' => $oid, 'title' => $title, 'desc' => $desc, 'type' => $type, 'typeid' => $typeid, 'times' => time()));
	}
}