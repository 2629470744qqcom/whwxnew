<?php
namespace Wap\Controller;
use Common\Controller\WapController;

/**
 *在线缴费
 * yaoyongli 2016年1月4日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class PaymentController extends WapController{
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
		//获取业主信息
		$ownerInfo = $this->getInfo('o.id,o.addr,o.pic,a.name as area,o.rid,o.point', 'whwx_owner as o, whwx_area as a', 'a.id = o.aid and o.id='.session('fansInfo.oid'));
		$ownerInfo['addr'] = array_reverse(explode('-', $ownerInfo['addr']));
		$this->assign('ownerInfo', $ownerInfo);
		//获取缴费
		$where = 'b.id = d.bill_id and d.status > 0 and b.status = 3 and d.rid = '.$ownerInfo['rid'];
		$list = $this->getList('d.id,d.porperty,d.energy,d.arrear_money,d.carport,d.car_manger,d.water,d.status,d.total_money,d.porperty_pay,d.energy_pay,d.arrear_money_pay,d.car_manger_pay,d.carport_pay,d.water_pay,b.name', 'whwx_payment_detail as d, whwx_bill as b', $where, 'b.start_time desc');
		//dump($list);exit;
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 详情
	 * huying Jan 22, 2016
	 */
	public function cont(){
		$info = $this->getInfo('id,creat_time,real_money,change_money,point,status,money,type,typeid', 'payment', 'id='.I('get.id', 0, 'intval'));
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 列表
	 * huying Jan 22, 2016
	 */
	public function lists(){
		//获取所有的缴费记录
		$list = $this->getList('id,money,real_money,remark,creat_time,status', 'payment', ' status<>2 and oid='.session('fansInfo.oid'), 'creat_time desc');
		foreach ($list as $k => $v){
			if($v['creat_time'] >= strtotime(date('Y-m-01'))){//本月
				$data['this'][] = $v; 
			}else if($v['creat_time'] > (strtotime(date('Y').'-'.(date('m')-1).'-01'))){//上月
				$data['last'][] = $v;
			}else{//其余
				$data['other'][] = $v;
			}
		}
		$this->assign('data', $data);
		$this->display();
	}
	/**
	 * 积分兑换
	 * huying Feb 17, 2016
	 */
	public function point(){
		$point = M('owner')->where('id='.session('fansInfo.oid'))->getField('point');
		if($_POST['type'] == 1){
			$this->ajaxReturn(array('point' => $point, 'site_money' => C('money'), 'site_point' => C('point')));
		}else{
			$pay_money = M('payment_detail')->where('id='.I('post.id'))->getField('total_money');
			//$max_point = intval(ceil(ceil($pay_money)/C('money')*C('point')));
			$change_money = sprintf("%.2f", $_POST['point']*C('money')/C('point'));
			if($_POST['point'] > $point){
				$this->ajaxReturn(array('status' => 0, 'info' => '你只有'.$point.'积分'));
			}
			if(preg_match('/^(0|[1-9][0-9]*)$/', $_POST['point'])){
				$info = $change_money >= $pay_money ? '确定用'.$_POST['point'].'积分兑换？' : '可抵用'.$change_money.'元'; 
				$type = $change_money >= $pay_money ? 1 : 0; 
				$this->ajaxReturn(array('status' => 1, 'info' => $info, 'point' => $_POST['point'], 'type' => $type));
			}else{
				$this->ajaxReturn(array('status' => 0, 'info' => '请输入大于等于零的正整数'));
			}
		}
	}
	/**
	 * 积分支付
	 * huying Feb 17, 2016
	 */
	public function point_pay(){
		$point = I('post.point', 0, 'intval');
		$cate = I('post.cate', '');
		if(!$cate){
			$this->ajaxReturn(array('status' => 0, 'info' => '请选择缴费项'));
		}
		$id = I('post.id', 0, 'intval');
		if($id > 0){
			$cate_arr = array_filter(explode(',', $cate));
			foreach($cate_arr as $value) {
				$cate_pay[] = $value . '_pay';
			}
			$info = $this->getInfo('bill_id,status,' . $cate . ',' . implode(',', $cate_pay), 'payment_detail', 'id=' . $id);
			if($info['status'] == 2){
				$this->ajaxReturn(array('status' => 0, 'info' => '该物业费已经有人缴过了'));
			}
			$this->deleteData('status = 1 and type = 1 and typeid=' . $id . ' and oid=' . session('fansInfo.oid'), 'payment');
			$aid = M('bill')->where('id=' . $info['bill_id'])->getField('aid');
			foreach($cate_arr as $value) {
				$money += $info[$value] - $info[$value . '_pay'];
			}
			$result = $this->updateData(array('oid' => session('fansInfo.oid'), 'status' => 1, 'aid' => $aid, 'change_money' => 0, 'money' => $money, 'creat_time' => time(), 'type' => 1, 'typeid' => $id, 'remark' => '在线缴费', 'point' => $point, 'pay_cate' => $cate), 'payment');
			if($result){
				$this->ajaxReturn(array('status' => 2, 'info' => '缴费成功', 'result' => $result));
			}
			$this->returnResult(false, array('操作成功', '缴费失败'));
		}
		$this->returnResult(false, array('操作成功', '缴费失败'));
		/**zxh 20160401 积分兑换功能暂时隐藏 start
		$type = explode(',', I('post.cate'));
		$sum = implode('+', $type);
		$info = $this->getInfo('id,bill_id,total_money,status,pay_cate,sum('.$sum.') as summoney,'.trim(I('post.cate'), ','), 'payment_detail', 'id='.I('post.id', 0, 'intval'));
		if($info['status'] < 2){
			$change_money = sprintf("%.2f", $point*C('money')/C('point'));
// 			$max_point = intval(ceil(ceil($info['money'])/C('money')*C('point')));
			$this->deleteData('status = 1 and type = 1 and typeid='.$info['id'].' and oid='.session('fansInfo.oid'), 'payment');
			$aid = M('bill')->where('id='.$info['bill_id'])->getField('aid');
			$data = array('oid' => session('fansInfo.oid'), 'status' => 1, 'aid' => $aid, 'change_money' => $change_money, 'money' => $info['summoney'], 'creat_time' => time(), 'type' => 1, 'typeid' => $info['id'], 'remark' => '缴费账单', 'point' => $point, 'pay_cate' => I('post.cate'));
			$result = $this->updateData($data, 'payment');
			if($change_money >= $info['summoney']){
				$result2 = $this->changePoint(session('fansInfo.oid'), $point, '缴物业费', 8, I('post.id', 0, 'intval'), 0);
				if($result2){
// 					if()
// 					$this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 2, 'oid' => session('fansInfo.oid')), 'payment_detail', 2);
// 					M('payment_detail')->where('id='.I('post.id', 0, 'intval'))->setField('status', 2);
					$payResult = $this->updateData(array('id' => $result, 'pay_type' => 3, 'real_money' => 0, 'pay_time' => time(), 'status' => 2), 'payment', 2);
					//将实收的费用写入详情表
					if($payResult !== false){
						foreach ($type as $k => $v){
							$detail[$v.'_pay'] = $info[$v];
						}
						$detail['id'] = I('post.id', 0, 'intval');
						$detail['oid'] = session('fansInfo.oid');
						$detail['pay_cate'] = $info['pay_cate'].','.I('post.cate');
						$this->updateData($detail, 'payment_detail', 2);
					}
					$owner = $this->getInfo('aid,bid,name', 'owner', 'id='.session('fansInfo.oid'));
					//获取用户的专属客服
					$service = $this->getInfo('id,name', 'service', 'status = 1 and aid='.$owner['aid'].' and FIND_IN_SET('.$owner['bid'].', bids)');
					//给客服发送缴费通知(非模板消息)
					$this->updateData(array('sid' => $service['id'], 'oid' => session('fansInfo.oid'),'type' => 1, 'name' => session('fansInfo.name').'的缴费通知', 'times' => time(), 'typeid' => $result, 'desc' => '业主'.session('fansInfo.name').'于'.date('Y年m月d日 H时i分s秒').'缴费'.$info['summoney'].'元。', 'status' => 1), 'service_notice');
					$this->ajaxReturn(array('status' => 1, 'info' => '缴费成功', 'result' => $result));
				}
			}else{
				//积分不能抵用全部的金额，剩下金额使用微信支付
				$this->ajaxReturn(array('status' => 2, 'info' => '缴费成功', 'result' => $result));
			}
			$this->ajaxReturn(array('status' => 0, 'info' => '操作失败，请稍后再试'));
		}else{
			$this->ajaxReturn(array('status' => 0, 'info' => '该物业费已经有人缴过了'));
		}
		zxh  end*/
// 		$info = $this->getInfo('id,typeid,money,remark,status', 'payment', 'id='.I('post.id', 0, 'intval'));
// 		$max_point = intval(ceil(ceil($info['money'])/C('money')*C('point')));
// 		$point = I('post.point', 0, 'intval');
// 		if($max_point <= $point){
// 			$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'real_money' => 0, 'pay_type' => 3, 'pay_time' => time(), 'status' => 2, 'point' => $point), 'payment', 2);
// 			if($result !== false){
// 				$result2 = M('payment_detail')->where('id='.$info['typeid'])->setField('status', 2);
// 				$this->changePoint(session('fansInfo.oid'), $point, '缴物业费', 8, 0);
// 				$this->ajaxReturn(array('status' => 1, 'info' => '缴费成功'));
// 			}
// 		}
// 		$this->ajaxReturn(array('status' => 0, 'info' => '兑换失败，请稍后再试'));
	}
	public function pay_status(){
		$this->display();
	}
	
}