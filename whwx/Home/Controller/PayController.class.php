<?php
namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 支付处理
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class PayController extends BaseController{

	/**
	 * 发起支付
	 */
	public function index(){
		if(I('get.id', 0, 'intval') > 0){
			$info = $this->getInfo('id,type,typeid,money,point,change_money,remark,status', 'payment', 'id=' . I('get.id', 0, 'intval'));
			if($info['status'] == 1){
				if($info['type'] == 1){
					$status = M('payment_detail')->where('id=' . $info['typeid'])->getField('status');
					if($status == 2){
						exit('该物业费已经有人缴过了');
					}
				}
				switch(I('get.type', 0, 'intval')){
					case 1 : // 微支付
						$point = I('get.point', 0, 'intval');
						$notify_url = 'Home/Pay/weipayNotify';
						$return_url = 'Home/Pay/result';
						$money = round(round($info['money'], 2) - round($info['change_money'], 2), 2);
						$weipay = new \Common\Api\WeipayApi();
						$weipay->weipayJs($info['id'], $info['remark'], $money, $notify_url, $return_url);
						break;
				}
			}
		}
		exit('请求失败');
	}

	/**
	 * 微信支付回调
	 */
	public function weipayNotify(){
		if($_GET['zxh'] == 888){
	 		$data = array('out_trade_no' => 1101, 'total_fee' => 74600, 'transaction_id' => '6666668888888');
		}else{
			$weipay = new \Common\Api\WeipayApi();
			$data = $weipay->weipayNotify();
		}
		if($data){
			$real_money = $data['total_fee'] / 100;
			$this->updateData(array('id' => $data['out_trade_no'], 'real_money' => $real_money, 'pay_type' => 1, 'pay_time' => time(), 'status' => 3, 'transaction_id' => $data['transaction_id']), 'payment', 2);
			$info = $this->getInfo('oid,aid,type,typeid,point,creat_time,pay_time,pay_type,transaction_id,pay_cate', 'payment', 'id=' . $data['out_trade_no']);
			$get_point = floor($real_money * C('get_point') / C('get_money'));
			switch($info['type']){
				case 1 : // 缴费
					$payInfo['id'] = $info['typeid'];
					$payInfo['oid'] = $info['oid'];
					if(!empty($info['pay_cate'])){
						$pay_cate_arr = array_filter(explode(',', $info['pay_cate']));
						foreach($pay_cate_arr as $v){
							$new_pay_cate_arr[] = $v . '_pay';
						}
						$detail = $this->getInfo($info['pay_cate'] . ',' . implode(',', $new_pay_cate_arr) . ',total_money', 'payment_detail', 'id=' . $info['typeid']);
						foreach($pay_cate_arr as $v){
							$payInfo[$v . '_pay'] = $detail[$v];
							$pay_data['money'] = $pay_data['real_money'] = $detail[$v] - $detail[$v . '_pay'];
							$pay_data['status'] = 2;
							$pay_data = array_merge($pay_data, $info);
							$pay_data['pay_cate'] = $v;
							$pay_data['remark'] = '在线缴费';
							M('payment')->add($pay_data);
						}
						$payInfo['total_money'] = $detail['total_money'] > $info['real_money'] ? ($detail['total_money'] - $real_money) : 0;
						$payInfo['status'] = $payInfo['total_money'] > 0 ? 1 : 2;
					}
					$result = $this->updateData($payInfo, 'payment_detail', 2);
					if($info['point'] > 0){
						$result2 = $this->updateData(array('oid' => $info['oid'], 'point' => $info['point'], 'name' => '在线缴费抵用', 'type' => 0, 'act' => 8, 'times' => time()), 'point');
						if($result2 !== false){
							M('owner')->where('id=' . $info['oid'])->setDec('point', $info['point']);
						}
					}
					$owner = $this->getInfo('aid,bid,name', 'owner', 'id=' . $info['oid']);
					// 获取用户的专属客服
					$service = $this->getInfo('id,name', 'service', 'status = 1 and aid=' . $owner['aid'] . ' and FIND_IN_SET(' . $owner['bid'] . ', bids)');
					// 给客服发送缴费通知(非模板消息)
					$this->updateData(array('sid' => $service['id'], 'oid' => $info['oid'], 'type' => 1, 'name' => $owner['name'] . '的缴费通知', 'times' => time(), 'typeid' => $data['out_trade_no'], 'desc' => '业主' . $owner['name'] . '于' . date('Y年m月d日 H时i分s秒') . '缴费' . $real_money . '元。', 
						'status' => 1), 'service_notice');
					// 缴费送积分
					if($get_point > 0){
						$this->changePoint($info['oid'], $get_point, '缴物业费赠送', 2, $info['typeid']);
					}
					break;
				case 2 : // 报修
					$result = M('repair')->where('id=' . $info['typeid'])->setField('status', 8);
					// 支付成功给维修发一个模板消息
					$fansInfo = $this->getInfo('f.openid', 'whwx_repair as r, whwx_wxfans as f', 'f.type = 3 and f.oid = r.cate_id and r.id = ' . $info['typeid']);
					$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
					$data = array('first' => array('value' => '业主的报修已经付款，请查看', 'color' => '#ff0000'), 'orderMoneySum' => array('value' => $real_money . '元', 'color' => '#173177'), 'orderProductName' => array('value' => '报修支付', 'color' => '#173177'), 
						'remark' => array('value' => session('fansInfo.name') . '微信支付报修金额：' . $real_money . '元', 'color' => '#173177'));
					$wechatAuth->sendTemplateMsg($fansInfo['openid'], '6CFjMv8AwKScxy6m53beJ0O8Fc-HftFFxXA0oxv96fE', '/Wap/Repairman/my_order?id=' . $info['typeid'], $data);
					// 报修送积分
					if($get_point > 0){
						$this->changePoint($info['oid'], $get_point, '报修支付赠送', 3, $info['typeid']);
					}
					break;
				case 3 : // 特惠团
					$result = $this->updateData(array('id' => $info['typeid'], 'status' => 2, 'pay_type' => 1, 'pay_time' => time(), 'pay_amount' => $real_money, 'pay_order' => $data['transaction_id']), 'group_orders', 2);
					// 特惠团送积分
					if($get_point > 0){
						$this->changePoint($info['oid'], $get_point, '特惠团购买商品赠送', 4, $info['typeid']);
					}
					break;
				case 4 : // 装修申请
					$result = M('decorate')->where('id=' . $info['typeid'])->setField('status', 1);
					if($get_point > 0){
						$this->changePoint($info['oid'], $get_point, '装修申请赠送', 0, $info['typeid']);
					}
					break;
			}
			$result = array('return_code' => 'SUCCESS', 'return_msg' => 'OK');
			exit(xmlencode($result));
		}
	}

	public function result(){
		$this->redirect('/Wap/Payment/cont?id=' . $_GET['id']);
	}

	/**
	 * 旅游支付回调
	 */
	public function tourNotify(){
		if($_GET['zxh'] == 888){
	 		$data = array('out_trade_no' => '160729174658774', 'total_fee' => 66, 'transaction_id' => '6666668888888');
		}else{
			$weipay = new \Common\Api\WeipayApi();
			$data = $weipay->weipayNotify();
		}
		if($data){
			$real_money = $data['total_fee'] / 100;
			$result = $this->updateData(array('id' => $data['out_trade_no'], 'real_money' => $real_money, 'pay_type' => 'weipay', 'pay_time' => time(), 'status' => 2, 'pay_id' => $data['transaction_id']), 'tour_orders', 2);
			if($result){
				$result = array('return_code' => 'SUCCESS', 'return_msg' => 'OK');
				exit(xmlencode($result));
			}
			\Think\Log::write('SQL_' . M()->_sql() . '_Result_' . $result, 'pay_debug');
		}
	}

	public function tourReturn(){
		$this->redirect('/Wap/Tour/ordersInfo?orders_id=' . $_GET['id']);
	}
}