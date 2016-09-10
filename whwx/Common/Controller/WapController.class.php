<?php
namespace Common\Controller;
/**
 * 手机端基类
 * zhangxinhe 2014-12-5
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WapController extends BaseController{

	protected function _initialize(){
		parent::_initialize();
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false){
			exit('请在微信中打开当前页面');
		}
		// 未成功注册时，刷新粉丝信息
		if(!IS_AJAX && (session('fansInfo.type') == false || session('fansInfo.id') == false)){
			session('fansInfo', null);
		}
		// 认证服务号获取粉丝信息
		if(!IS_AJAX && !session('?fansInfo')){
			if($_GET['fid'] && $_GET['openid']){
				$fansInfo = M('wxfans')->field('id,openid,type,oid')->where(array('status' => 1, 'id' => $_GET['fid'], 'openid' => $_GET['openid']))->find();
			}
			if(!$fansInfo){
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				if(empty($_GET['code'])){
					$getCodeUrl = $wechatAuth->getRequestCodeURL(geturi(), null, 'snsapi_base');
					header('Location:' . $getCodeUrl);
					exit();
				}else{
					$codeAccessToken = $wechatAuth->getAccessToken('code', $_GET['code']);
					if($codeAccessToken['openid']){
						$fansInfo = M('wxfans')->field('id,openid,type,oid')->where(array('status' => 1, 'openid' => $codeAccessToken['openid']))->find();
					}
				}
			}
			// 获取不同身份的人员信息
			if($fansInfo['id'] > 0 && $fansInfo['type'] > 0 && $fansInfo['oid'] > 0){
				switch($fansInfo['type']){
					case 5 :
						$table = 'tour_merchant';
						break;
					case 4 :
						$table = 'service';
						break;
					case 3 :
						$table = 'repairman';
						break;
					default :
						$table = 'owner';
						$addressInfo = M()->table('whwx_owner as o, whwx_area as a')->field('o.addr,a.name')->where('a.id = o.aid and o.id=' . $fansInfo['oid'])->find();
						$addressInfo['addr'] = array_reverse(explode('-', $addressInfo['addr']));
						if(count($addressInfo['addr']) >= 3){
							$address = $addressInfo['name'] . ($addressInfo['addr'][3] ? $addressInfo['addr'][3] . '区' : '') . $addressInfo['addr'][2] . $addressInfo['addr'][1] . $addressInfo['addr'][0];
						}else if(count($addressInfo['addr']) == 2){
							$address = $addressInfo['name'] . $addressInfo['addr'][2] . $addressInfo['addr'][0];
						}else{
							$address = $addressInfo['name'] . $addressInfo['addr'][0];
						}
						break;
				}
				$info = M($table)->field('name,phone,pic,aid,status')->where(array('id' => $fansInfo['oid']))->find();
				if($info['status'] == 1){
					$fansInfo = array_merge($info, $fansInfo);
					$fansInfo['address'] = $address;
				}else{
					M('wxfans')->where(array('id' => $fansInfo['id']))->save(array('type' => 0, 'oid' => 0));
					$fansInfo = null;
				}
			}
			$fansInfo && session('fansInfo', $fansInfo);
		}
		// 粉丝身份浏览
		if(I('get.is_fans', 0, 'intval') == 1 || (CONTROLLER_NAME == 'Forum' && ACTION_NAME == 'details' && session('fansInfo.type') == false)){
			session('fansInfo.type', -1);
		}
		// 不同身份跳转
		if(!IS_AJAX){
			switch(session('fansInfo.type')){
				case 5 :
					$model = 'TourMerchant';
					CONTROLLER_NAME == $model || $this->redirect('Wap/' . $model . '/index');
					break;
				case 4 :
					$model = 'Service';
					CONTROLLER_NAME == $model || $this->redirect('Wap/' . $model . '/index');
					break;
				case 3 :
					$model = 'Repairman';
					CONTROLLER_NAME == $model || $this->redirect('Wap/' . $model . '/index');
					break;
				case 0 :
					$model = 'Public';
					CONTROLLER_NAME == $model || $this->redirect('Wap/' . $model . '/index');
					break;
				case -1 :
					$arr = array('Forum', 'About', 'Black', 'Rental', 'Index', 'Public', 'Recommend', 'Shop', 'Community', 'Preferential', 'Booking');
					if(!in_array(CONTROLLER_NAME, $arr)){
						$this->redirect('Wap/Index/index?show=' . $_GET['show']);
					}
			}
		}
	}

	/**
	 * 获取分享JS
	 * @param string $title 分享标题
	 * @param string $pic 分享图片
	 * @param string $url 分享链接
	 * @param string $desc 分享描述
	 * @param string $callbackSuccess 分享成功回调函数
	 * @param string $callbackCancel 分享失败回调函数
	 *        zhangxinhe 2015年7月30日
	 */
	public static function getShareJs($title, $pic, $url, $desc = null, $callbackSuccess = 'function(){}', $callbackCancel = 'function(){}'){
		if(!strpos($url, 'http:\\') || !strpos($url, 'https:\\')){
			$url = C('site_url') . $url;
		}
		$pic .= '?imageView2/1/h/100/w/100/q/80';
		$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
		$signPackage = $wechatAuth->getJsSignPackage();
		if($signPackage){
			$title = str_replace(array('"', "'"), array('“', "‘"), $title);
			$desc = str_replace(array('"', "'"), array('“', "‘"), $desc);
			$shareJs = '<script>wx.config({debug:false, appId:"' . C('site_appid') . '", timestamp:' . $signPackage["timestamp"] . ', nonceStr:"' . $signPackage["nonceStr"] . '", signature:"' . $signPackage["signature"] . '", jsApiList:["onMenuShareTimeline", "onMenuShareAppMessage", "onMenuShareQQ", "onMenuShareWeibo", "chooseImage", "previewImage", "uploadImage", "openLocation", "getLocation", "closeWindow", "hideMenuItems"]});';
			$shareJs .= 'wx.ready(function(){var data = {title:"' . $title . '", desc:"' . $desc . '", link:"' . $url . '", imgUrl:"' . $pic . '", success:' . $callbackSuccess . ', cancel:' . $callbackCancel . '}; wx.onMenuShareTimeline(data); wx.onMenuShareAppMessage(data); wx.onMenuShareQQ(data); wx.onMenuShareWeibo(data);});</script>';
			return $shareJs;
		}
		return false;
	}
}
?>