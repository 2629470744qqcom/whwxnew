<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 客服
 * huying Jan 19, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ServiceController extends WapController{
	private $manInfo;
	protected function _initialize() {
		parent::_initialize ();
// 		获取客服的信息
		$this->manInfo = $this->getInfo('o.id,o.name,o.pic,o.phone,a.name as area,o.bids', 'whwx_service as o, whwx_area as a', 'o.aid = a.id and o.id='.session('fansInfo.oid'));
		$this->assign('manInfo', $this->manInfo);
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}
	
	/**
	 * 客服首页
	 * huying Jan 19, 2016
	 */
	public function index(){
		$bids = trim($this->manInfo['bids'], ',');
		$where = '(r.cate =  2 and r.cate_id = '.session('fansInfo.oid').') or (r.type = 5 and r.oid = '.session('fansInfo.oid').')';
		switch(I('get.type', 0, 'intval')){
			case 1://缴费通知
				$list = $this->getList('id,name,desc,times', 'service_notice', 'type = 1 and sid='.session('fansInfo.oid'), 'times desc', true);
				break;
			case 2://投诉建议
				$bids = trim($this->manInfo['bids'], ',');
				$list = $this->getList('c.id,c.desc,c.times,c.status', 'whwx_complaint as c, whwx_owner as o', '(o.bid in ('.$bids.') and c.oid=o.id and c.oid>0) or (c.bid in ('.$bids.') and o.id=0 and c.bid>0)', 'c.times desc', true);
				break;
			default://报修订单
// 				$where = 'r.status > 1 and o.id = r.oid and o.bid in ('.$bids.')';
				$list = $this->getList('r.id,r.name,r.desc,r.creat_time,r.address,r.status,r.type', 'repair as r', $where, 'r.creat_time desc', true);
				break;
		}
		$this->assign('list', $list);
		$count['pay'] = M('service_notice')->where('type = 1 and sid='.session('fansInfo.oid'))->count();
		$count['suggest'] = M('complaint as c, whwx_owner as o')->where('o.bid in ('.$bids.') and c.oid = o.id')->count();
		$count['repair'] = M('repair as r')->where($where)->count();
		$count['booking'] = M('service_notice')->where('oid = '.session('fansInfo.oid').' and type = 2 and status = 1')->count();
		$this->assign('count', $count);
		$this->display();
	}
	/**
	 * 投诉详情
	 * huying Jan 21, 2016
	 */
	public function complaint(){
		//$info = $this->getInfo('o.name,o.phone,o.addr,c.feedback,c.feedback_pic,a.name as area,c.deal_time,c.desc,c.times,c.pics,c.status', 'whwx_complaint as c, whwx_owner as o, whwx_area as a', 'a.id = o.aid and c.oid = o.id and c.id='.I('get.id', 0, 'intval'));
		$info = $this->getInfo('c.name,c.tel phone,c.feedback,c.feedback_pic,a.name as area,c.deal_time,c.desc,c.times,c.pics,c.status', 'whwx_complaint as c,whwx_area as a', 'a.id = c.aid and c.id='.I('get.id', 0, 'intval'));
		if(!empty($info['pics'])){
			$info['pics'] = explode(',', $info['pics']);
		}
		if(!empty($info['feedback_pic'])){
			$info['feedback_pic'] = explode(',', $info['feedback_pic']);
		}
		if($info['status'] == 2){
			$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type = 5 and rid = '.I('get.id', 0, 'intval'));
		}
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 报修详情
	 * huying Jan 21, 2016
	 */
	public function repair(){
		$orderInfo = $this->getInfo('r.id,r.name,r.status,r.pics,r.desc,r.creat_time,r.feedback,r.address,r.repairman_pic,o.name as owner,o.phone', 'whwx_owner as o,whwx_repair as r', 'r.oid = o.id and r.id='.I('get.id', 0, 'intval'));
		if(!empty($orderInfo['pics'])){
			$orderInfo['pics'] = explode(',', $orderInfo['pics']);
		}
		if(!empty($orderInfo['repairman_pic'])){
			$orderInfo['repairman_pic'] = explode(',', $orderInfo['repairman_pic']);
		}
		if($orderInfo['status'] > 7){
			$orderInfo['payment'] = $this->getInfo('id,creat_time,money,status,pay_time,pay_type,real_money', 'payment', 'type = 2 and typeid='.I('get.id', 0, 'intval'));
		}
		if($orderInfo['status'] == 9){ // 获取评价
			$orderInfo['comment'] = $this->getInfo('times,desc,score', 'comment', 'type < 3 and rid = ' . I('get.id', 0, 'intval'));
		}
		$this->assign('orderInfo', $orderInfo);
		$this->display();
	}
	/**
	 * 缴费详情
	 * huying Jan 21, 2016
	 */
	public function notice(){
		$info = $this->getInfo('o.name,o.phone,o.addr,c.times,c.desc,c.name as notice', 'whwx_service_notice as c, whwx_owner as o', 'c.oid = o.id and c.id='.I('get.id', 0, 'intval'));
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 投诉反馈
	 * huying Jan 21, 2016
	 */
	public function feedback(){
		$info = $this->getInfo('id,oid', 'complaint', 'id='.I('post.id', 0, 'intval'));
		if($_POST['pics']){
			$pics = array_filter(explode(',', $_POST['pics']));
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			foreach ($pics as $value) {
				$feedback_pic[] = $wechatAuth->getFileToQiniu($value);
			}
		}
		$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'feedback' => $_POST['feedback'], 'feedback_pic' => implode(',', $feedback_pic), 'deal_time' => time(), 'status' => 1), 'complaint', 2);
		if($result !== false){
			M('warn')->where('type = 3 and typeid='.I('post.id', 0, 'intval').' and status = 1')->setField('status', 0);
			$result2 = \Common\Api\CommonApi::addNotice($info['oid'], $_POST['feedback'], '你的投诉/建议已受理', 5, $info['id']);
			$openid = M('wxfans')->where('type=1 and oid=' . $info['oid'])->getField('openid');
			if($openid){
				$data = array('first' => array('value' => '您的投诉建议已处理完成！', 'color' => '#ff0000'), 'keyword1' => array('value' => session('fansInfo.name'), 'color' => '#173177'), 'keyword2' => array('value' => session('fansInfo.phone'), 'color' => '#173177'), 
					'keyword3' => array('value' => date('Y-m-d H:i', time()), 'color' => '#173177'), 'keyword4' => array('value' => '已受理', 'color' => '#173177'), 'remark' => array('value' => '点击查看详情', 'color' => '#173177'));
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				$wechatAuth->sendTemplateMsg($openid, C('advise_return_template'), U('Complaint/detail?id=' . $info['id']), $data);
			}
		}
		$this->returnResult($result);
	}
	/**
	 * 处理完成
	 * huying Jan 9, 2016
	 */
	public function complete(){
		if($_POST['pics']){
			$pics = array_filter(explode(',', $_POST['pics']));
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			foreach ($pics as $value) {
				$repairman_pic[] = $wechatAuth->getFileToQiniu($value);
			}
		}
		$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 8, 'update_time' => time(), 'repairman_pic' => implode(',', $repairman_pic), 'feedback' => $_POST['result']), 'repair', 2);
		if($result > 0){
			\Common\Api\CommonApi::addFollow(session('fansInfo.oid'), session('fansInfo.name'), session('fansInfo.phone'), 1, I('post.id', 0, 'intval'), '处理结果：'.$_POST['result']);
			$this->ajaxReturn(array('status' => 1, 'info' => '操作成功'));
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '提交失败，请稍后再试'));
	}
	/**
	 * 处理报错
	 * huying Jan 9, 2016
	 */
	public function wrong(){
		$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 2, 'update_time' => time()), 'repair', 2);
		if($result > 0){
			\Common\Api\CommonApi::addFollow(session('fansInfo.oid'), session('fansInfo.name'), session('fansInfo.phone'), 1, I('post.id', 0, 'intval'), '维修报错：'.$_POST['desc']);
			$this->ajaxReturn(array('status' => 1, 'info' => '报错成功'));
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '提交失败，请稍后再试'));
	}
	/**
	 * 预约详情
	 * huying Mar 5, 2016
	 */
	public function booking(){
		if(IS_POST){
			if(I('post.id', 0, 'intval') > 0){
				$result = M('booking')->where('id='.I('post.id', 0, 'intval'))->setField('status', 3);
				if($result !== false){
					M('warn')->where('type = 2 and typeid='.I('post.id', 0, 'intval').' and status = 1')->setField('status', 0);
					M('service_notice')->where('oid='.session('fansInfo.oid').' and type = 2 and typeid = '.I('post.id', 0, 'intval'))->setField('status', 0);
					$owner = $this->getInfo('b.oid', 'whwx_booking as b,whwx_owner as o', 'b.oid = o.id and b.id='.I('post.id', 0, 'intval'));
					//添加通知消息
					$typeid = \Common\Api\CommonApi::addNotice($owner['oid'], '预约新进度', '你的预约已经处理，请及时查看', 6, I('post.id', 0, 'intval'));
					$this->ajaxReturn(array('status' => 1, 'info' => '操作成功'));
				}
			}
			$this->ajaxReturn(array('status' => 0, 'info' => '操作失败'));
		}else{
			$info = $this->getInfo('b.id,b.status,b.day,b.hour,b.name,b.phone,b.desc,b.submit_time,t.name as catename,s.name as company,s.phone as com_phone', 'whwx_booking as b, whwx_booking_type as t, whwx_booking_supplier as s', ' b.cate_id = t.id and b.sid = s.id and b.id='.I('get.id', 0, 'intval'));
			if($info['status'] == 2){
				$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type = 4 and rid = '.I('get.id', 0, 'intval'));
			}
			$this->assign('info', $info);
			$this->display();
		}
	}
	/**
	 * 预约列表
	 * huying Mar 5, 2016
	 */
	public function bookingList(){
		$list = $this->getList('s.id,s.name,s.times,s.desc,s.typeid,s.status,b.status as bstatus', 'whwx_service_notice as s,whwx_booking as b', 's.typeid = b.id and s.type = 2 and s.oid='.session('fansInfo.oid'), 'times desc');
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 上传头像
	 * huying Mar 7, 2016
	 */
	public function upload(){
		$result = M('service')->where('id='.session('fansInfo.oid'))->setField('pic', $_POST['pic'][0]);
		$this->returnResult($result);
	}
	/**
	 * 我要报修
	 * huying Mar 18, 2016
	 */
	public function my_repair(){
		if(IS_POST){
			$type = 5;
			$data = array('name' => '公共区域报修', 'address' => $_POST['address'], 'type' => $type, 'desc' => $_POST['desc'], 'pics' => implode(',', $_POST['pic']), 'oid' => session('fansInfo.oid'), 'status' => 3, 'creat_time' => time(), 'cate' => 1,
					'aid' => session('fansInfo.aid'), 'owner' => session('fansInfo.name'), 'phone' => session('fansInfo.phone'));
			$result = $this->updateData($data, 'repair');
			if($result > 0){
				$info = array('first' => array('value' => '有新的报修订单，快去抢单吧！', 'color' => '#ff0000'), 'keyword1' => array('value' => session('fansInfo.name'), 'color' => '#173177'), 'keyword2' => array('value' => session('fansInfo.phone'), 'color' => '#173177'),
						'keyword3' => array('value' => $_POST['address'], 'color' => '#173177'), 'keyword4' => array('value' => '公共区域报修', 'color' => '#173177'), 'remark' => array('value' => $data['desc'], 'color' => '#173177'));
				// 获取业主所在小区的维修工的信息
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				$manList = $this->getList('r.id,f.openid', 'whwx_repairman as r, whwx_wxfans as f', 'f.type = 3 and f.oid = r.id and r.aid = ' . session('fansInfo.aid'), 'r.id desc');
				foreach($manList as $k => $v){
					$result3 = $wechatAuth->sendTemplateMsg($v['openid'], C('repair_template'), U('Repairman/order?id=' . $result), $info);
				}
			}
			$this->returnResult($result, null, U('Service/index'));
		}else{
			$this->display();
		}
	}
	/**
	 * 报修详情
	 * huying Mar 18, 2016
	 */
	public function repair_detail(){
		$info = $this->getInfo('id,name,desc,type,cate,address,pics,cate_id,status,creat_time,price,feedback', 'repair', 'id=' . I('get.id', 0, 'intval'));
		if($info['cate_id'] > 0){
			$table = $info['cate'] == 1 ? 'repairman' : 'service';
			$info['cateInfo'] = $this->getInfo('name,phone', $table, 'id=' . $info['cate_id']);
		}
		if(!empty($info['pics'])){
			$info['pics'] = explode(',', $info['pics']);
		}
		if($info['status'] == 9){ // 获取评价
			$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type < 3 and rid = ' . I('get.id', 0, 'intval'));
		}
		$this->assign('info', $info);
		$this->display();
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
					$this->ajaxReturn(array('status' => 1, 'info' => '评价成功'));
				}
			}
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '评价失败'));
	}
	
}