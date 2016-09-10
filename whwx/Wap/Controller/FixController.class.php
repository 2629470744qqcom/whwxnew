<?php
namespace Wap\Controller;
use Common\Controller\WapController;

/**
 * 在线报修
 * 手机端登录页面
 * yaoyongli 2016年1月4日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class FixController extends WapController{

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

	/**
	 * 评价
	 * huying Jan 9, 2016
	 */
	public function evaluate(){
		$info = $this->getInfo('cate,cate_id,price', 'repair', 'id=' . I('post.id', 0, 'intval'));
		if(!empty($info)){
			$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 9, 'update_time' => time()), 'repair', 2);
			if($result > 0){
				$result1 = $this->updateData(array('oid' => session('fansInfo.oid'), 'aid' => session('fansInfo.aid'), 'times' => time(), 'type' => $info['cate'], 'typeid' => $info['cate_id'], 'score' => I('post.score', 0, 'intval'), 'desc' => $_POST['desc'], 'rid' => I('post.id', 0, 'intval'), 'status' => 1), 'comment');
				if($result1 > 0){
					$point = C('score_point');
					if($point > 0){
						$this->changePoint(session('fansInfo.oid'), $point, '报修评价', 3, I('post.id', 0, 'intval'));
					}
					$this->ajaxReturn(array('status' => 1, 'info' => '评价成功'));
				}
			}
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '评价失败'));
	}

	/**
	 * 我的报修
	 * huying Jan 8, 2016
	 */
	public function mine(){
		$list = $this->getList('id,name,type,cate,cate_id,status,creat_time', 'repair', 'del != 2 and oid=' . session('fansInfo.oid').' and type != 5', 'creat_time desc', true);
		foreach($list as $k => $v){
			if($v['cate_id'] > 0){
				$table = $v['cate'] == 1 ? 'repairman' : 'service';
				$list[$k]['cateInfo'] = $this->getInfo('name,phone', $table, 'id=' . $v['cate_id']);
			}
		}
		$this->assign('list', $list);
		$this->display();
	}

	/**
	 * 报修详情
	 * huying Jan 8, 2016
	 */
	public function mine_status(){
		if(I('get.typeid', 0, 'intval') > 0){
			M('owner_notice')->where('id=' . I('get.typeid', 0, 'intval'))->setField('status', 0);
		}
		$info = $this->getInfo('id,name,desc,type,cate,address,pics,cate_id,status,creat_time,price,repairman_pic,feedback', 'repair', 'id=' . I('get.id', 0, 'intval'));
		if($info['cate_id'] > 0){
			$table = $info['cate'] == 1 ? 'repairman' : 'service';
			$info['cateInfo'] = $this->getInfo('name,phone', $table, 'id=' . $info['cate_id']);
// 			if($info['cate'] == 2){
				
// 			}
		}
		if(!empty($info['pics'])){
			$info['pics'] = explode(',', $info['pics']);
		}
		if(!empty($info['repairman_pic'])){
			$info['repairman_pic'] = explode(',', $info['repairman_pic']);
		}
		if($info['status'] == 9){ // 获取评价
			$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type < 3 and rid = ' . I('get.id', 0, 'intval'));
		}
		$this->assign('info', $info);
		$this->display();
	}

	/**
	 * 其他报修/公共报修
	 * huying Jan 8, 2016
	 */
	public function other(){
		if(IS_POST){
			$type = $_POST['type'] == 2 ? 2 : 0;
			$data = array('name' => $type == 2 ? '其他报修' : '公共区域报修', 'address' => $_POST['address'], 'type' => $type, 'desc' => $_POST['desc'], 'pics' => implode(',', $_POST['pic']), 'oid' => session('fansInfo.oid'), 'status' => 3, 'creat_time' => time(), 'cate' => 1, 
					'aid' => session('fansInfo.aid'), 'owner' => session('fansInfo.name'), 'phone' => session('fansInfo.phone'));
			$result = $this->updateData($data, 'repair');
			if($result > 0){
				$area = M('area')->where(array('id' => session('fansInfo.aid')))->getField('name');
				$info = array('first' => array('value' => '有新的报修订单，快去抢单吧！', 'color' => '#ff0000'), 'keyword1' => array('value' => session('fansInfo.name'), 'color' => '#173177'), 'keyword2' => array('value' => session('fansInfo.phone'), 'color' => '#173177'),
						'keyword3' => array('value' => $area . ' ' . $_POST['address'], 'color' => '#173177'), 'keyword4' => array('value' => $type == 2 ? '其他报修' : '公共区域报修', 'color' => '#173177'), 'remark' => array('value' => $data['desc'], 'color' => '#173177'));
				// 获取业主所在小区的维修工的信息
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				//$manList = $this->getList('r.id,f.openid', 'whwx_repairman as r, whwx_wxfans as f', 'f.type = 3 and f.oid = r.id and r.aid = ' . session('fansInfo.aid'), 'r.id desc');
				$manList = $this->getList('r.id,f.openid', 'whwx_repairman as r, whwx_wxfans as f', 'f.type = 3 and f.oid = r.id and find_in_set(' . session('fansInfo.aid') . ',r.aid)', 'r.id desc');
				foreach($manList as $k => $v){
					$result3 = $wechatAuth->sendTemplateMsg($v['openid'], C('repair_template'), U('Repairman/order?id=' . $result), $info);
				}
				// 添加预警
				//$result2 = $this->updateData(array('type' => 1, 'type_id' => $result, 'time' => time()), 'warn');
			}
			$this->returnResult($result, null, U('Fix/mine_status?id=' . $result));
		}else{
			$this->display();
		}
	}
	/**
	 * 房屋质量维修
	 * huying Mar 4, 2016
	 */
	public function quality(){
		if(IS_POST){
			$owner = $this->getInfo('id,bid,aid', 'owner', 'id='.session('fansInfo.oid'));
			$sid = M('service')->where('aid = '.$owner['aid'].' and bids like "%,'.$owner['bid'].',%" and status = 1')->getField('id');
			$data = array('name' => '房屋质量报修', 'address' => $_POST['address'], 'type' => I('post.type'), 'desc' => $_POST['desc'], 'pics' => implode(',', $_POST['pic']), 'oid' => session('fansInfo.oid'), 'status' => $sid > 0 ? 5 : 3, 'creat_time' => time(), 'cate' => 2, 'cate_id' => $sid > 0 ? $sid : 0, 
					'aid' => session('fansInfo.aid'), 'owner' => session('fansInfo.name'), 'phone' => session('fansInfo.phone'));
			$result = $this->updateData($data, 'repair');
			if($sid > 0){
				$service = $this->getInfo('id,openid', 'wxfans', 'type = 4 and oid = '.$sid);
				if($result > 0){
					$info = array('first' => array('value' => '有业主提交了房屋质量报修，请处理', 'color' => '#ff0000'), 'keyword1' => array('value' => session('fansInfo.name'), 'color' => '#173177'), 'keyword2' => array('value' => session('fansInfo.phone'), 'color' => '#173177'),
							'keyword3' => array('value' => $_POST['address'], 'color' => '#173177'), 'keyword4' => array('value' => $type == 2 ? '其他报修' : '公共区域报修', 'color' => '#173177'), 'remark' => array('value' => $data['desc'], 'color' => '#173177'));
					// 获取业主所在楼栋的专属客服
					$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
					$result3 = $wechatAuth->sendTemplateMsg($service['openid'], C('repair_template'), U('Service/repair?id=' . $result), $info);
				}
			}else{
				//添加一条预警消息
				$this->updateData(array('type' => 1, 'typeid' => $result, 'time' => time(), 'name' => '房屋质量报修无客服处理', 'status' => 0), 'warn');
			}
			$this->returnResult($result, null, U('Fix/mine_status?id=' . $result));
		}else{
			$this->display();
		}
	}
	/**
	 * 固定报修列表
	 * huying Jan 8, 2016
	 */
	public function preson(){
		$list = $this->getList('id,name', 'repair_fixe', 'status = 1', 'sort desc');
		$this->assign('list', $list);
		$this->display();
	}

	/**
	 * 室内报修
	 * huying Jan 8, 2016
	 */
	public function preson_cont(){
		if(IS_POST){
			$info = $this->getInfo('id,name,material,price,desc', 'repair_fixe', 'id=' . I('post.fixe_id', 0, 'intval'));
			if(empty($info)){
				$this->returnResult(false);
			}
			$data = array('name' => '室内区域报修', 'address' => session('fansInfo.address'), 'type' => 1, 'desc' => $info['name'] . '<br /> 业主备注：' . $_POST['desc'], 'oid' => session('fansInfo.oid'), 'status' => 3, 'creat_time' => time(), 'cate' => 1, 
					'pics' => implode(',', $_POST['pic']), 'aid' => session('fansInfo.aid'), 'owner' => session('fansInfo.name'), 'phone' => session('fansInfo.phone'));
			$result = $this->updateData($data, 'repair');
			if($result > 0){
				$area = M('area')->where(array('id' => session('fansInfo.aid')))->getField('name');
				$info = array('first' => array('value' => '有新的报修订单，快去抢单吧！', 'color' => '#ff0000'), 'keyword1' => array('value' => session('fansInfo.name'), 'color' => '#173177'), 'keyword2' => array('value' => session('fansInfo.phone'), 'color' => '#173177'), 
					'keyword3' => array('value' => $area . ' ' . session('fansInfo.address'), 'color' => '#173177'), 'keyword4' => array('value' => '室内区域报修', 'color' => '#173177'), 'remark' => array('value' => $_POST['desc'], 'color' => '#173177'));
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				$manList = $this->getList('r.id,f.openid', 'whwx_repairman as r, whwx_wxfans as f', 'f.type = 3 and f.oid = r.id and r.aid = ' . session('fansInfo.aid'), 'r.id desc');
				foreach($manList as $k => $v){
					$result2 = $wechatAuth->sendTemplateMsg($v['openid'], C('repair_template'), U('Repairman/order?id=' . $result), $info);
				}
				// 添加预警
				//$result2 = $this->updateData(array('type' => 1, 'type_id' => $result, 'time' => time()), 'warn');
			}
			$this->returnResult($result, null, U('Fix/mine_status?id=' . $result));
		}else{
			$info = $this->getInfo('id,name,material,price,desc', 'repair_fixe', 'id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$this->display();
		}
	}

	/**
	 * 线下支付
	 * huying Jan 9, 2016
	 */
	public function pay(){
		$payment = $this->getInfo('id,status,money', 'payment', 'type = 2 and typeid=' . I('post.id', 0, 'intval'));
		if($payment['status'] == 1){
			$result = $this->updateData(array('id' => $payment['id'], 'real_money' => $payment['money'], 'pay_type' => 2, 'pay_time' => time(), 'status' => 2), 'payment', 2);
			if($result !== false){
				$result1 = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 8), 'repair', 2);
				//积分变化
				$point = floor($payment['money'] * C('get_point') / C('get_money'));
				if($point > 0){
					$this->changePoint(session('fansInfo.oid'), $point, '报修支付赠送', 3, I('post.id', 0, 'intval'));
				}
				// 支付成功给维修发一个模板消息
				$fansInfo = $this->getInfo('f.openid', 'whwx_repair as r, whwx_wxfans as f', 'f.type = 3 and f.oid = r.cate_id and r.id = ' . I('post.id', 0, 'intval'));
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
// 				$data = array('业主的报修已经付款，请查看', $payment['money'] . '元', '报修支付', session('fansInfo.name') . '线下支付报修金额：' . $payment['money'] . '元');
				$data = array('first' => array('value' => '业主的报修已经付款，请查看', 'color' => '#ff0000'), 'orderMoneySum' => array('value' => $payment['money'] . '元', 'color' => '#173177'), 
						'orderProductName' => array('value' => '报修支付', 'color' => '#173177'),
						'remark' => array('value' => session('fansInfo.name').'线下支付报修金额：' . $payment['money'] . '元', 'color' => '#173177'));
				$result3 = $wechatAuth->sendTemplateMsg($fansInfo['openid'], C('notice_template'), U('Repairman/my_order?id=' . I('post.id', 0, 'intval')), $data);
				$this->ajaxReturn(array('status' => 1, 'info' => '支付成功'));
			}
			$this->ajaxReturn(array('status' => -1, 'info' => '支付失败'));
		}else{
			$this->ajaxReturn(array('status' => -1, 'info' => '已支付'));
		}
	}

	/**
	 * 获取报修的账单id
	 * huying Jan 22, 2016
	 */
	public function getPid(){
		$pid = M('payment')->where('type = 2 and typeid=' . I('post.id', 0, 'intval'))->getField('id');
		$this->ajaxReturn($pid);
	}
	/**
	 * 业主删除
	 * huying Mar 11, 2016
	 */
	public function del(){
		$result = M('repair')->where('id in ('.$_POST['ids'].')')->setField('del', 2);
		$this->returnResult($result);
	}
}