<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 预约服务
 * yaoyongli 2016年1月8日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class BookingController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}else{
			if(session('fansInfo.type') != 1){
				$this->ajaxReturn(array('status' => -1, 'info' => '你没有权限'));
			}
		}
	}
	/**
	 * 首页
	 * yaoyongli 2016年1月8日
	 */
	public function index(){
		//获取分类
		$cateList = $this->getList('id,name,pic', 'booking_type', 'status = 1', 'sort desc');
		//获取公司
		// $company = $this->getList('id,cate_id,name,pic,price,service', 'booking_supplier', 'status = 1', 'score desc');
		// foreach ($cateList as $k => $v){
		// 	foreach ($company as $k1 => $v1){
		// 		if ($v['id'] == $v1['cate_id']){
		// 			$list[$k]['name'] = $v['name'];
		// 			$list[$k]['company'][] = $v1;
		// 		}
		// 	}
		// }
		// $this->assign('list', $list);
		$this->assign('list', $cateList);
		$this->display();
	}

	public function lists(){
		$cata_id = I('get.cata_id', 0, 'intval');

		//获取公司
		$company = $this->getList('id,cate_id,name,pic,price,service', 'booking_supplier', 'status = 1 and cate_id = '.$cata_id, 'score desc');

		$this->assign('company', $company);
		$this->display();
	}

	/**
	 * 预约详情页
	 * yaoyongli 2016年1月8日
	 */
	public function details(){
		//获取供应商的信息
		$info = $this->getInfo('s.id,s.name,s.phone,s.price,s.pic,s.address,s.service,s.desc,s.cate_id,t.name as catename', 'whwx_booking_supplier as s, whwx_booking_type as t', 't.id = s.cate_id and s.id='.I('get.id', 0, 'intval'));
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 我的预约评价页
	 * yaoyongli 2016年1月8日
	 */
	public function evaluate(){
		M('owner_notice')->where('type = 6 and typeid='.I('get.id',0,'intval'))->setField('status', 0);
		$info = $this->getInfo('b.id,b.status,b.day,b.hour,b.name,b.phone,b.desc,b.submit_time,t.name as catename,s.name as company', 'whwx_booking as b, whwx_booking_type as t, whwx_booking_supplier as s', ' b.cate_id = t.id and b.sid = s.id and b.id='.I('get.id', 0, 'intval'));
		if($info['status'] == 2){
			$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type = 4 and rid = '.I('get.id', 0, 'intval'));
		}
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 我的预约页
	 * yaoyongli 2016年1月8日
	 */
	public function mine(){
		$list = $this->getList('id,desc,submit_time', 'booking', 'oid='.session('fansInfo.oid'), 'submit_time desc',true);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 添加
	 * huying Jan 12, 2016
	 */
	public function add(){
		$_POST['day'] = strtotime($_POST['day']);
		$_POST['oid'] = session('fansInfo.oid');
		$_POST['aid'] = session('fansInfo.aid');
		$_POST['submit_time'] = time();
		$_POST['desc'] = '预约'.$_POST['catename'].'&nbsp;&nbsp;'.'时间：'.date('Y-m-d H:i', $_POST['day']);
		$result = $this->updateData($_POST, 'booking');
		if($result !== false){
			//预约成功后，发送模板消息
			$owner = $this->getInfo('id,bid,aid', 'owner', 'id='.session('fansInfo.oid'));
			$service = $this->getInfo('f.id,f.openid,s.id as sid', 'whwx_service as s, whwx_wxfans as f', 's.aid = '.$owner['aid'].' and s.bids like "%,'.$owner['bid'].',%" and f.type = 4 and s.id = f.oid and s.status = 1');
			$info = array('first' => array('value' => '有新的预约，快去处理吧！', 'color' => '#ff0000'), 'keyword1' => array('value' => $_POST['name'], 'color' => '#173177'), 'keyword2' => array('value' => $_POST['phone'], 'color' => '#173177'),
					'keyword3' => array('value' => date('Y-m-d H:i:s', $_POST['day']), 'color' => '#173177'), 'keyword4' => array('value' => session('fansInfo.address'), 'color' => '#173177'), 'keyword5' => array('value' => '预约'.$_POST['catename'], 'color' => '#173177'), 'remark' => array('value' => '点击查看详情', 'color' => '#173177'));
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			$result3 = $wechatAuth->sendTemplateMsg($service['openid'], C('booking_template'), U('Service/booking?id=' . $result), $info);
			$this->updateData(array('oid' => $service['sid'], 'name' => '预约通知', 'times' => time(), 'type' => 2, 'typeid' => $result , 'status' => 1, 'desc' => $_POST['desc']), 'service_notice');
			$this->ajaxReturn(array('status' => 1, 'info' => '预约成功', 'id' => $result));
		}else{
			$this->ajaxReturn(array('status' => -1, 'info' => '预约失败，请稍后重试'));
		}
	}
	/**
	 * 评论
	 * huying Jan 12, 2016
	 */
	public function comment(){
		$info = $this->getInfo('sid', 'booking', 'id='.I('post.id', 0, 'intval'));
		if(!empty($info)){
			$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 2), 'booking', 2);
			if($result > 0){
				$result1 = $this->updateData(array('oid' => session('fansInfo.oid'), 'aid' => session('fansInfo.aid'), 'times' => time(), 'type' => 4, 'typeid' => $info['sid'], 'score' => I('post.score', 0, 'intval'), 'desc' => $_POST['desc'], 'rid' => I('post.id', 0, 'intval'), 'status' => 1), 'comment');
				if($result1 > 0){
					$point = C('score_point');
					if($point > 0){
						$this->changePoint(session('fansInfo.oid'), $point, '预约评价', 10, I('post.id', 0, 'intval'));
					}
					M('warn')->where('type = 2 and typeid='.I('post.id', 0, 'intval').' and status = 1')->setField('status', 0);
					$this->ajaxReturn(array('status' => 1, 'info' => '评价成功'));
				}
			}
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '评价失败'));
	}
}