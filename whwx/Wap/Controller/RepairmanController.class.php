<?php
namespace Wap\Controller;
use Common\Controller\WapController;

/**
 * 维修工
 * 手机端登录页面
 * yaoyongli 2016年1月5日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class RepairmanController extends WapController{
	private $manInfo;
	protected function _initialize() {
		parent::_initialize ();
		//获取维修工的信息
		$this->manInfo = $this->getInfo('name,phone,pic,score,aid', 'repairman', 'id='.session('fansInfo.oid'));
		$areaInfo = M('area')->where('id in ('.$this->manInfo['aid'].')')->getField('name', true);
		$this->manInfo['area'] = implode(',', $areaInfo);
		$this->assign('manInfo', $this->manInfo);
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}
	/**
	 * 维修工首页
	 * huying Jan 9, 2016
	 */
	public function index(){
		$where1 = 'r.status > 1 and r.status < 10 and r.cate = 1 and r.cate_id='.session('fansInfo.oid');
		//$where2 = 'r.status = 3 and r.aid='.$this->manInfo['aid'];
		$where2 = 'r.status = 3 and r.aid in ('.$this->manInfo['aid'].')';
		$where = $_GET['type'] == 1 ? $where1 : $where2;
		$order = $_GET['type'] == 1 ? 'r.status asc, r.id desc' : ' r.id desc';
		$repairList = $this->getList('r.id,r.name,r.desc,r.creat_time,r.address,r.status,r.type', 'repair as r', $where, $order, true);
		$this->assign('repairList', $repairList);
		$count = M('repair as r')->where($_GET['type'] == 1 ? $where2 : $where1)->count();
		$this->assign('count', $count);
		$this->assign('count1', M('repair as r')->where($_GET['type'] == 1 ? $where1 : $where2)->count());
		$this->display();
	}
	/**
	 * 订单
	 * huying Jan 9, 2016
	 */
	public function order(){
		$orderInfo = $this->getInfo('id,name,status,desc,creat_time,address,owner,phone,pics', 'repair', 'id='.I('get.id', 0, 'intval'));
		if(!empty($orderInfo['pics'])){
			$orderInfo['pics'] = explode(',', $orderInfo['pics']);
		}
		$this->assign('orderInfo', $orderInfo);
		$this->display();
	}
	/**
	 * 我的订单
	 * huying Jan 9, 2016
	 */
	public function my_order(){
		$orderInfo = $this->getInfo('id,name,type,status,desc,pics,creat_time,address,owner,phone,type,price,repairman_pic,update_time', 'repair', 'id='.I('get.id', 0, 'intval'));
		if(!empty($orderInfo['pics'])){
			$orderInfo['pics'] = explode(',', $orderInfo['pics']);
		}
		if(!empty($orderInfo['repairman_pic'])){
			$orderInfo['repairman_pic'] = explode(',', $orderInfo['repairman_pic']);
		}
		if($orderInfo['status'] > 7){
			$orderInfo['payment'] = $this->getInfo('id,creat_time,money,status,pay_time,pay_type,real_money', 'payment', 'type = 2 and typeid='.I('get.id', 0, 'intval'));
		}
		if($orderInfo['status'] == 9 && $orderInfo['type'] != 4){ // 获取评价
			$orderInfo['comment'] = $this->getInfo('times,desc,score', 'comment', 'type < 3 and rid = ' . I('get.id', 0, 'intval'));
		}
		$this->assign('orderInfo', $orderInfo);
		$this->display();
	}
	//抢单成功
	public function order_success(){
		$this->display();
	}
	/**
	 * 抢单
	 * huying Jan 9, 2016
	 */
	public function rob(){
		$info = $this->getInfo('id,name,status,oid,type', 'repair', 'id='.I('post.id', 0, 'intval'));
		if(empty($info)){
			$this->ajaxReturn(array('status' => -1, 'info' => '抢单失败'));
		}
		if($info['status'] < 5 && $info['status'] > 2 && empty($info['cate_id'])){
			M('warn')->where('type = 1 and typeid='.I('post.id', 0, 'intval').' and status = 1')->setField('status', 0);
			$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'cate_id' => session('fansInfo.oid'), 'status' => 5, 'update_time' => time()), 'repair', 2);
			if($result > 0){
				\Common\Api\CommonApi::addFollow(session('fansInfo.oid'), session('fansInfo.name'), session('fansInfo.phone'), 1, I('post.id', 0, 'intval'), '抢单成功');
				$info['type'] == 5 || \Common\Api\CommonApi::addNotice($info['oid'], '你有新的报修处理', '你申请的报修已有人处理，快去瞅瞅吧', 2, I('post.id', 0, 'intval'));
				$this->ajaxReturn(array('status' => 1, 'info' => '抢单成功'));
			}
		}
		// $this->ajaxReturn(array('status' => -1, 'info' => '你下手晚了一步'));
		$this->ajaxReturn(array('status' => -1, 'info' => '该单子已经过了抢单时间啦，不能抢单了哦，到主页刷新一下再试试吧'));
	}
	/**
	 * 维修完成
	 * huying Jan 9, 2016
	 */
	public function complete(){
		if(!preg_match('/^(0|(([1-9][0-9]*)|(([0]\.\d{1,2}|[1-9][0-9]*\.\d{1,2}))))$/', I('post.price'))){
			$this->ajaxReturn(array('status' => -1, 'info' => '请输入正确的金额'));
		}
		$info = $this->getInfo('id,aid,name,status,oid,type', 'repair', 'id='.I('post.id', 0, 'intval'));
		if(empty($info)){
			$this->ajaxReturn(array('status' => 1, 'info' => '提交失败，请稍后再试'));
		}
		if($_POST['pics']){
			$pics = array_filter(explode(',', $_POST['pics']));
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			foreach ($pics as $value) {
				$repairman_pic[] = $wechatAuth->getFileToQiniu($value);
			}
		}
		if(I('post.price') > 0){
			$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 7, 'price' => I('post.price'), 'repairman_pic' => implode(',', $repairman_pic), 'update_time' => time()), 'repair', 2);
		}else{
			$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 8, 'price' => 0, 'repairman_pic' => implode(',', $repairman_pic), 'update_time' => time()), 'repair', 2);
		}
		if($result > 0){
			if(I('post.price') > 0){
				M('payment')->add(array('money' => I('post.price'), 'aid' => $info['aid'], 'creat_time' => time(), 'status' => 1, 'type' =>  2, 'typeid' => I('post.id', 0, 'intval'), 'oid' => $info['oid'], 'remark' => '报修支付'));
			}
			if($info['type'] != 4){
				//模板消息
				$data = array('first' => array('value' => '你的报修已维修完成！', 'color' => '#ff0000'),
						'keyword1' => array('value' => date('Y-m-d H:i'), 'color' => '#173177'),
						'keyword2' => array('value' => $info['name'], 'color' => '#173177'),
						'keyword3' => array('value' => I('post.price').'元', 'color' => '#173177'),
						'remark' => array('value' => '点击去评价', 'color' => '#0000ff'));
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				$where = $info['type'] == 5 ? 'type = 4' : 'type = 1';
				$openid = M('wxfans')->where($where.' and oid='.$info['oid'].' and status = 1')->getField('openid');
				$url = $info['type'] == 5 ? U('Service/repair_detail?id='.$info['id']) : U('Fix/mine_status?id='.$info['id']);
				$result3 = $wechatAuth->sendTemplateMsg($openid, C('fix_complete_template'), $url, $data);
			}
			$this->ajaxReturn(array('status' => 1, 'info' => '维修完成'));
		}
		$this->ajaxReturn(array('status' => 1, 'info' => '提交失败，请稍后再试'));
	}
	/**
	 * 后台报修完成并支付
	 * huying Mar 8, 2016
	 */
	public function pay(){
		if(!preg_match('/^(0|(([1-9][0-9]*)|(([0]\.\d{1,2}|[1-9][0-9]*\.\d{1,2}))))$/', I('post.price'))){
			$this->ajaxReturn(array('status' => -1, 'info' => '请输入正确的金额'));
		}
		$info = $this->getInfo('id,aid,name,status,oid,owner', 'repair', 'id='.I('post.id', 0, 'intval'));
		if(empty($info)){
			$this->ajaxReturn(array('status' => 1, 'info' => '提交失败，请稍后再试'));
		}
		if($_POST['pics']){
			$pics = array_filter(explode(',', $_POST['pics']));
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			foreach ($pics as $value) {
				$repairman_pic[] = $wechatAuth->getFileToQiniu($value);
			}
		}
		$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 8, 'price' => I('post.price'), 'repairman_pic' => implode(',', $repairman_pic), 'update_time' => time()), 'repair', 2);
		if($result > 0){
			if(I('post.price') > 0){
				M('payment')->add(array('money' => I('post.price'), 'real_money' => I('post.price'), 'aid' => $info['aid'], 'creat_time' => time(), 'pay_time' => time(), 'status' => 2, 'type' =>  2, 'typeid' => I('post.id', 0, 'intval'), 'oid' => $info['oid'], 'remark' => '报修支付'));
			}
			$this->ajaxReturn(array('status' => 1, 'info' => '维修完成'));
		}
		$this->ajaxReturn(array('status' => 1, 'info' => '提交失败，请稍后再试'));
	}
	/**
	 * 维修报错
	 * huying Jan 9, 2016
	 */
	public function wrong(){
		$id = I('post.id', 0, 'intval');
		$result = $this->updateData(array('id' => $id, 'status' => 2, 'update_time' => time()), 'repair', 2);
		if($result > 0){
			\Common\Api\CommonApi::addFollow(session('fansInfo.oid'), session('fansInfo.name'), session('fansInfo.phone'), 1, $id, '维修报错：'.$_POST['desc']);
			//模板消息
			$info = $this->getInfo('id,name,status,oid,type', 'repair', 'id=' . $id);
			$data = array('first' => array('value' => '你的报修被维修员报错！', 'color' => '#ff0000'),
					'keyword1' => array('value' => date('Y-m-d H:i'), 'color' => '#173177'),
					'keyword2' => array('value' => $info['name'], 'color' => '#173177'),
					'keyword3' => array('value' => '0元', 'color' => '#173177'),
					'remark' => array('value' => $_POST['desc'], 'color' => '#0000ff'));
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			$where = $info['type'] == 5 ? 'type = 4' : 'type = 1';
			$openid = M('wxfans')->where($where.' and oid='.$info['oid'].' and status = 1')->getField('openid');
			$url = $info['type'] == 5 ? U('Service/repair_detail?id='.$info['id']) : U('Fix/mine_status?id='.$info['id']);
			$result3 = $wechatAuth->sendTemplateMsg($openid, C('fix_complete_template'), $url, $data);
			$this->ajaxReturn(array('status' => 1, 'info' => '报错成功'));
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '提交失败，请稍后再试'));
	}
	/**
	 * 上传头像
	 * huying Mar 7, 2016
	 */
	public function upload(){
		$result = M('repairman')->where('id='.session('fansInfo.oid'))->setField('pic', $_POST['pic'][0]);
		$this->returnResult($result);
	}
}