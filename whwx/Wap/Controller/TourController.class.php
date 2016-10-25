<?php
namespace Wap\Controller;
use Common\Controller\WapController;

/**
 * 旅游模块
 * huying Jan 18, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class TourController extends WapController{

	protected function _initialize(){
		parent::_initialize();
		// 分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}else{
			if(session('fansInfo.type') != 1){
				$this->ajaxReturn(array('status' => -1, 'info' => '你没有权限'));
			}
		}
	}

	public function index(){
		$slideList = M('tour_slide')->field('pic,url')->where('status=1 and type=0')->order('sort desc')->limit(6)->select();
		$this->assign('slideList', $slideList);
		$classifyList = M('tour_classify')->field('id,name,pic,link')->where('status=1')->order('sort desc')->limit(6)->select();
		$this->assign('classifyList', $classifyList);
		$keyword = I('get.keyword');
		if($keyword){
			$where = 'status=1 and name like "%' . $keyword . '%"';
			$order = 'sort desc';
		}else{
			$where = 'status=1';
			$order = '`index` desc,sort desc';
		}
		$lineList = M('tour_line')->field('id,name,if(locate(",", pics), left(pics, locate(",", pics) - 1), pics) pics,sale,self,jing,re,hui,tag')->where($where)->order($order)->limit(6)->select();
		$this->assign('lineList', $lineList);
		$this->display();
	}

	public function lists(){
		$id = I('get.id', 0, 'intval');
		if($id > 0){
			$slideList = M('tour_slide')->field('pic,url')->where('status=1 and type=' . $id)->order('sort desc')->limit(6)->select();
			$this->assign('slideList', $slideList);
			$lineList = M('tour_line')->field('id,name,if(locate(",", pics), left(pics, locate(",", pics) - 1), pics) pics,sale,self,recom,jing,re,hui,tag')->where('status=1 and cid=' . $id)->order('recom desc,sort desc')->select();
			$this->assign('lineList', $lineList);
			$this->display();
		}
	}

	public function info(){
		$id = I('get.id', 0, 'intval');
		if($id > 0){
			$info = M('tour_line')->field('id,mid,name,pics,sale,price,phone,content,self,jing,re,hui,tag')->where('id=' . $id)->find();
			$info['pics'] = explode(',', $info['pics']);
			$this->assign('info', $info);
			$phone = M('tour_merchant')->where(array('id' => $info['mid']))->getField('phone');
			$this->assign('phone', $phone);
			if($_GET['type'] == 1){
				$commentList = M()->table('whwx_tour_orders t,whwx_owner o')->field('o.id oid,o.nickname,o.pic,t.comment_score,t.comment_time,t.comment')->where('o.id=t.oid and t.comment_status=1 and t.comment_score>0 and t.pid=' . $id)->select();
				$this->assign('commentList', $commentList);
			}
			$this->display();
		}
	}

	public function buy(){
		if(IS_POST){
			$data = array('aid' => session('fansInfo.aid'), 'oid' => session('fansInfo.oid'), 'times' => time(), 'pnum' => count($_POST['name']));
			$info = $this->getInfo('name pname,sale pprice', 'tour_line', array('id' => I('post.pid', 0, 'intval')));
			$info['money'] = $info['pprice'] * count($_POST['name']);
			foreach($_POST['name'] as $k => $v){
				$userInfo[] = array('name' => $v, 'idcard' => $_POST['idcard'][$k]);
			}
			$_POST['id'] = date('ymdHis') . mt_rand(100, 999);
			$_POST['user'] = json_encode($userInfo);
			$data = array_merge($data, $_POST, $info);
			$result = $this->updateData($data, 'tour_orders');

			//这个发送到旅行社模板消息要在支付成功的时候，发送， 下单但未支付时，不发送
			// if($result){
			// 	$openid = M()->table('whwx_tour_merchant m,whwx_wxfans f')->where('m.fid>0 and m.fid=f.id and m.id=' . I('post.mid', 0, 'intval'))->getField('openid');
			// 	if($openid){
			// 		$msgData = array('first' => array('value' => '有新的旅游订单，快去看看吧！', 'color' => '#ff0000'), 'tradeDateTime' => array('value' => date('Y-m-d H:i'), 'color' => '#173177'), 'orderType' => array('value' => $info['pname'], 'color' => '#173177'),
			// 			'customerInfo' => array('value' => $_POST['name'][0] . ' ' . I('post.phone'), 'color' => '#173177'), 'orderItemName' => array('value' => '游玩信息'), 'orderItemData' => array('value' => I('post.dates') . '出发 共' . $data['pnum'] . '人', 'color' => '#173177'), 'remark' => array('value' => '点击查看更多信息', 'color' => '#173177'));
			// 		// 获取业主所在小区的维修工的信息
			// 		$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			// 		$wechatAuth->sendTemplateMsg($openid, C('tour_template'), U('TourMerchant/ordersInfo?id=' . $_POST['id']), $msgData);
			// 	}
			// }
			
			$this->returnResult($result, null, U('Tour/pay') . '?orders_id=' . $_POST['id']);
		}else{
			$id = I('get.id', 0, 'intval');
			if($id > 0){
				$info = M('tour_line')->field('id,mid,name,if(locate(",", pics), left(pics, locate(",", pics) - 1), pics) pics,sale,dates')->where('id=' . $id)->find();
				$info['dates'] = json_encode(explode(',', $info['dates']));
				$this->assign('info', $info);
				$this->display();
			}
		}
	}

	public function pay(){
		$type = I('get.type');
		$id = I('get.orders_id');
		if($id > 0 && $type){
			$info = M('tour_orders')->field('id,pname,money,status')->where(array('id' => $id))->find();
			if($info['status'] == 1 && $type == 'weipay'){
				$weipay = new \Common\Api\WeipayApi();
				$weipay->weipayJs($info['id'], $info['pname'], $info['money'], 'Home/Pay/tourNotify', 'Home/Pay/tourReturn');
			}else{
				$this->redirect('Tour/ordersInfo', array('orders_id' => $id));
			}
		}
	}

	public function orders(){
		if($_GET['type'] == 2){
			$list = $this->getList('*', 'tour_custom', 'status=1 and oid=' . session('fansInfo.oid'), 'times desc');
		}else{
			$list = $this->getList('o.id,o.pname,o.pnum,o.pprice,o.money,o.times,o.status,if(locate(",", pics), left(pics, locate(",", pics) - 1), pics) pics', 'whwx_tour_orders o,whwx_tour_line l', 'o.pid=l.id and o.oid=' . session('fansInfo.oid'), 'times desc');
		}
		$this->assign('list', $list);
		$this->display();
	}

	public function ordersInfo(){
		$info = $this->getInfo('*', 'tour_orders', array('id' => I('get.orders_id')));
		$lineInfo = M('tour_line')->field('if(locate(",", pics), left(pics, locate(",", pics) - 1), pics) pics')->where(array('id' => $info['pid']))->find();
		$info['pics'] = $lineInfo['pics'];
		$info['user'] = json_decode($info['user'], true);
		$this->assign('info', $info);
		$this->display();
	}

	public function custom(){
		if(IS_POST){
			$_POST['oid'] = session('fansInfo.oid');
			$_POST['times'] = time();
			$result = $this->updateData($_POST, 'tour_custom');
			$this->returnResult($result, array('提交成功，请耐心等待工作人员与你联系', '提交失败，请稍后重试'));
		}else{
			$city = F('tour_city');
			$this->assign('info', array('origin' => array_filter(explode('|', $city['origin'])), 'target' => array_filter(explode('|', $city['target']))));
			$this->display();
		}
	}

	public function ajax(){
		$type = I('get.type');
		$id = I('get.id');
		if($type == 1){
			// 取消订单
			$result = M('tour_orders')->where(array('id' => $id))->setField('status', 5);

			if ($result) {
				$info = M('tour_orders')->where('id='.$id)->find();
				$openid = M()->table('whwx_tour_merchant m,whwx_wxfans f')->where('m.fid>0 and m.fid=f.id and m.id='.$info['mid'])->getField('openid');
				$users = explode(',', $info['user']);
				$contact_name = $users[0]['name'];
				if($openid){
					$msgData = array(
									'first' => array(
										'value' => '真遗憾，有人取消订单啦', 
										'color' => '#ff0000'
									), 
									'tradeDateTime' => array(
										'value' => date('Y-m-d H:i'), 
										'color' => '#173177'
									), 
									'orderType' => array(
										'value' => $info['pname'],
										'color' => '#173177'
									),
									'customerInfo' => array(
										'value' => $contact_name . ' ' . $info['phone'],
										'color' => '#173177'
									), 
									'orderItemName' => array(
										'value' => '游玩信息'
									), 
									'orderItemData' => array(
										'value' => $info['dates'] . '出发 共' . $info['pnum'] . '人', 
										'color' => '#173177'
									), 
									'remark' => array(
										'value' => '点击查看更多信息', 
										'color' => '#173177'
									)
								);

					// 获取业主所在小区的维修工的信息
					$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
					$wechatAuth->sendTemplateMsg($info['mid'], C('tour_template'), U('TourMerchant/ordersInfo?id='.$id), $msgData);
				}
			}

			$this->returnResult($result);
		}elseif($type == 2){
			// 订单评论
			$_POST['id'] = $id;
			$_POST['comment_time'] = time();
			$_POST['status'] = 4;
			$result = $this->updateData($_POST, 'tour_orders', 2);
			$this->returnResult($result);
		}elseif($type == 3){
			// 取消个人定制订单
			$result = M('tour_custom')->where(array('id' => $id))->setField('status', 2);
			$this->returnResult($result);
		}
	}
}
?>