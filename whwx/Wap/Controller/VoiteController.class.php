<?php
namespace Wap\Controller;
use Common\Controller\WapController;

/**
 * 投诉建议
 * 手机端登录页面
 * yaoyongli 2016年1月4日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class VoiteController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}
	/**
	 * 首页
	 * yaoyongli 2016年1月7日
	 */
	public function index(){
		if(IS_POST){
			$_POST['oid'] = session('fansInfo.oid');
			$_POST['aid'] = session('fansInfo.aid');
			$_POST['name'] = session('fansInfo.name');
			$_POST['tel'] = session('fansInfo.phone');
			$_POST['times'] = time();
			$_POST['pics'] = implode(',', $_POST['pic']);
			$owner = $this->getInfo('id,bid,aid', 'owner', 'id='.session('fansInfo.oid'));
			$service = $this->getInfo('f.id,f.openid,s.id as sid', 'whwx_service as s, whwx_wxfans as f', 's.aid = '.$owner['aid'].' and s.bids like "%,'.$owner['bid'].',%" and f.type = 4 and s.id = f.oid and s.status = 1');
			$_POST['sid'] = $service['sid'];
			$result = $this->updateData($_POST, 'complaint');
			if($result !== false){
				$info = array('first' => array('value' => '有新的投诉建议，快去处理吧！', 'color' => '#ff0000'), 'keyword1' => array('value' => session('fansInfo.name'), 'color' => '#173177'), 'keyword2' => array('value' => session('fansInfo.phone'), 'color' => '#173177'),
						'keyword3' => array('value' => date('Y-m-d H:i', time()), 'color' => '#173177'), 'keyword4' => array('value' => $_POST['desc'], 'color' => '#173177'), 'remark' => array('value' => '点击查看详情', 'color' => '#173177'));
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				$result3 = $wechatAuth->sendTemplateMsg($service['openid'], C('advise_template'), U('Service/complaint?id=' . $result), $info);
			}
			$this->returnResult($result);
		}else{
			$this->display();
		}
	}
}