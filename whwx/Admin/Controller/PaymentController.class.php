<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
use Common;

/**
 * 缴费管理
 * huying Dec 31, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class PaymentController extends AdminController{

	/**
	 * 已缴费记录
	 * huying Dec 31, 2015
	 */
	public function index(){
		$where = 'p.status=2 and p.type = 1 and p.typeid = d.id and d.bill_id = b.id and p.aid=a.id and d.rid = r.id and r.aid = a.id and a.id in(0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.aid') > 0 ? ' and r.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.bid') > 0 ? ' and d.bill_id=' . I('get.bid', 'intval') : '';
		$where .= I('get.addr') != '' ? ' and r.addr like "' . I('get.addr') . '%"' : '';
		$where .= I('get.pay_cate') ? ' and p.pay_cate="' . I('get.pay_cate') . '"' : '';
		$where .= I('get.pay_type', 0, 'intval') > 0 ? ' and p.pay_type=' . I('get.pay_type', 0, 'intval') : '';
		$where .= I('get.start_time') ? ' and p.pay_time>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and p.pay_time<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		$list = $this->getList('p.id,p.oid,p.money,p.pay_time,p.real_money,p.point,p.remark,p.pay_type,p.pay_cate,p.change_money,b.name as bill,r.addr as room,a.name area', 'whwx_payment as p, whwx_payment_detail as d, whwx_bill as b, whwx_room as r,whwx_area as a', $where, 'p.pay_time desc', true, 12);
		foreach($list as $k => $v){
			if($v['oid'] > 0){
				$list[$k]['owner'] = $this->getInfo('name,phone', 'owner', 'id=' . $v['oid']);
			}
		}
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		if(I('get.aid') > 0){
			$bill_list = $this->getList('id,name', 'bill', 'aid=' . $_GET['aid']);
			$this->assign('bill_list', $bill_list);
		}
		if($_GET['p'] < 2){
			$totalInfo = M()->table('whwx_payment as p, whwx_payment_detail as d, whwx_bill as b, whwx_room as r,whwx_area as a')->field('sum(p.real_money) total,p.pay_cate')->where($where . ' and p.pay_cate not like "%,%"')->group('p.pay_cate')->select();
			foreach($totalInfo as $k => $v){
				$totalData[$v['pay_cate']] = $v['total'];
			}
			$totalData['total'] = array_sum($totalData);
			$this->assign('totalData', $totalData);
		}
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 支付记录
	 * huying Dec 31, 2015
	 */
	public function offlinepay(){
		$where = 'p.status=1 and p.type=9 and p.aid=a.id and p.aid in (0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.aid') > 0 ? ' and p.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.remark', 0, 'string') ? ' and p.remark like "%' . I('get.remark', 0, 'string') . '%"' : '';
		$where .= I('get.start_time') ? ' and p.pay_time>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and p.pay_time<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		if($_GET['act'] == 1){
			$list = $this->getList('a.name area,p.id,p.real_money,from_unixtime(p.pay_time) pay_time,p.remark,p.pay_cate', 'whwx_payment as p,whwx_area as a', $where, 'p.pay_time desc');
			$title = array('小区', '订单号', '支付金额', '支出时间', '支出项目', '操作者');
			array_unshift($list, $title);
			$file = \Common\Api\PHPExcelApi::exportExcel($list, '财务支出数据', true);
		}else{
			$list = $this->getList('p.id,a.name area,p.real_money,p.pay_time,p.remark,p.pay_cate', 'whwx_payment as p,whwx_area as a', $where, 'p.pay_time desc', true);
			$this->assign('list', $list);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			if($_GET['p'] < 2){
				$totalMoney = M()->table('whwx_payment as p,whwx_area as a')->where($where)->sum('real_money');
				$this->assign('totalMoney', $totalMoney);
			}
			$this->display();
		}
	}

	/**
	 * 添加支出记录
	 * huying Dec 31, 2015
	 */
	public function offlinepayAdd(){
		if(IS_POST){
			$_POST['type'] = 9;
			$_POST['status'] = 1;
			$_POST['pay_type'] = session('aid');
			$_POST['pay_cate'] = session('aname');
			$_POST['pay_time'] = strtotime($_POST['pay_time']);
			$result = $this->updateData($_POST, 'payment');
			$this->returnResult($result, null, U('Payment/offlinepay'));
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}

	/**
	 * 修改支出记录
	 * huying Dec 31, 2015
	 */
	public function offlinepayEdit(){
		if(IS_POST){
			$_POST['pay_type'] = session('aid');
			$_POST['pay_cate'] = session('aname');
			$_POST['pay_time'] = strtotime($_POST['pay_time']);
			$result = $this->updateData($_POST, 'payment', 2);
			$this->returnResult($result, null, U('Payment/offlinepay'));
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$info = $this->getInfo('id,aid,real_money,pay_time,remark', 'payment', 'type=9 and id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$this->display('offlinepayAdd');
		}
	}

	/**
	 * 删除支出记录
	 * huying Dec 31, 2015
	 */
	public function offlinepayDel(){
		$result = M('payment')->where(array('id' => I('get.id', 0, 'intval')))->setField('status', 0);
		$this->returnResult($result);
	}

	/**
	 * 报修收款记录
	 * huying Dec 31, 2015
	 */
	public function repair(){
		$where = 'p.status>1 and p.type = 2 and p.typeid = r.id and p.aid=a.id and a.id in(0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.aid') > 0 ? ' and p.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.pay_type', 0, 'intval') > 0 ? ' and p.pay_type=' . I('get.pay_type', 0, 'intval') : '';
		$where .= I('get.start_time') ? ' and p.pay_time>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and p.pay_time<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		if($_GET['act'] == 1){
			$list = $this->getList('a.name area,r.owner,r.phone,r.name,p.money,p.real_money,p.pay_time,p.pay_type,p.remark', 'whwx_payment as p,whwx_area as a,whwx_repair r', $where, 'p.pay_time desc');
			foreach($list as $k => $v){
				$list[$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
				$list[$k]['pay_type'] = $v['pay_type'] == 1 ? '微信支付' : '线下支付';
			}
			$title = array('小区', '业主姓名', '手机号码', '报修类型', '订单金额', '实际缴费', '缴费时间', '支付方式', '备注');
			array_unshift($list, $title);
			$file = \Common\Api\PHPExcelApi::exportExcel($list, '报修缴费数据', true);
		}else{
			$list = $this->getList('a.name area,r.owner,r.phone,r.name,p.money,p.real_money,p.pay_time,p.pay_type,p.remark', 'whwx_payment as p,whwx_area as a,whwx_repair r', $where, 'p.pay_time desc', true, 12);
			$this->assign('list', $list);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			if($_GET['p'] < 2){
				$totalInfo = M()->table('whwx_payment as p,whwx_repair as r, whwx_area as a')->field('sum(p.real_money) total,p.pay_type')->where($where)->group('p.pay_type')->select();
				foreach($totalInfo as $k => $v){
					$totalData['pay' . $v['pay_type']] = $v['total'];
				}
				$totalData['total'] = array_sum($totalData);
				$this->assign('totalData', $totalData);
			}
			$this->display();
		}
	}

	/**
	 * 特惠团收款记录
	 * huying Dec 31, 2015
	 */
	public function group(){
		$where = 'p.status != 1 and p.type = 3 and p.typeid = g.id and p.aid=a.id and a.id in(0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.aid') > 0 ? ' and p.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.pay_type', 0, 'intval') > 0 ? ' and p.pay_type=' . I('get.pay_type', 0, 'intval') : '';
		$where .= I('get.start_time') ? ' and p.pay_time>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and p.pay_time<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		if($_GET['act'] == 1){
			$list = $this->getList('a.name area,g.id,g.single_time,p.money,p.real_money,p.pay_time,p.pay_type,p.remark', 'whwx_payment as p,whwx_area as a,whwx_group_orders g', $where, 'p.pay_time desc');
			foreach($list as $k => $v){
				$list[$k]['single_time'] = date('Y-m-d H:i:s', $v['single_time']);
				$list[$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
				$list[$k]['pay_type'] = $v['pay_type'] == 1 ? '微信支付' : '线下支付';
			}
			$title = array('小区', '订单号', '下单时间', '订单金额', '实际支付', '缴费时间', '支付方式', '备注');
			array_unshift($list, $title);
			$file = \Common\Api\PHPExcelApi::exportExcel($list, '特惠团收款数据', true);
		}else{
			$list = $this->getList('a.name area,g.id,g.single_time,p.money,p.real_money,p.pay_time,p.pay_type,p.remark', 'whwx_payment as p,whwx_area as a,whwx_group_orders g', $where, 'p.pay_time desc', true, 12);
			$this->assign('list', $list);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			if($_GET['p'] < 2){
				$totalInfo = M()->table('whwx_payment as p,whwx_area as a,whwx_group_orders g')->field('sum(p.real_money) total,p.pay_type')->where($where)->group('p.pay_type')->select();
				foreach($totalInfo as $k => $v){
					$totalData['pay' . $v['pay_type']] = $v['total'];
				}
				$totalData['total'] = array_sum($totalData);
				$this->assign('totalData', $totalData);
			}
			$this->display();
		}
	}

	/**
	 * 装修垃圾清理费收款记录
	 * huying Dec 31, 2015
	 */
	public function decorate(){
		$where = 'p.status>1 and p.real_money>0 and p.type = 4 and p.typeid = d.id and p.aid=a.id and a.id in(0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.aid') > 0 ? ' and p.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.pay_type', 0, 'intval') > 0 ? ' and p.pay_type=' . I('get.pay_type', 0, 'intval') : '';
		$where .= I('get.start_time') ? ' and p.pay_time>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and p.pay_time<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		if($_GET['act'] == 1){
			$list = $this->getList('a.name area,d.name,d.phone,p.money,p.real_money,p.pay_time,p.pay_type,p.remark', 'whwx_payment as p,whwx_area as a,whwx_decorate d', $where, 'p.pay_time desc');
			foreach($list as $k => $v){
				$list[$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
				$list[$k]['pay_type'] = $v['pay_type'] == 1 ? '微信支付' : '线下支付';
			}
			$title = array('小区', '业主姓名', '手机号码', '订单金额', '实际缴费', '缴费时间', '支付方式', '备注');
			array_unshift($list, $title);
			$file = \Common\Api\PHPExcelApi::exportExcel($list, '装修垃圾清理费收款数据', true);
		}else{
			$list = $this->getList('a.name area,d.name,d.phone,p.money,p.real_money,p.pay_time,p.pay_type,p.remark', 'whwx_payment as p,whwx_area as a,whwx_decorate d', $where, 'p.pay_time desc', true, 12);
			$this->assign('list', $list);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			if($_GET['p'] < 2){
				$totalInfo = M()->table('whwx_payment as p,whwx_area as a,whwx_decorate d')->field('sum(p.real_money) total,p.pay_type')->where($where)->group('p.pay_type')->select();
				foreach($totalInfo as $k => $v){
					$totalData['pay' . $v['pay_type']] = $v['total'];
				}
				$totalData['total'] = array_sum($totalData);
				$this->assign('totalData', $totalData);
			}
			$this->display();
		}
	}

	/**
	 * 旅游模块收账记录
	 * huying Dec 31, 2015
	 */
	public function tour(){
		$where = 't.status=2 and t.aid=a.id and a.id in(0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.aid') > 0 ? ' and t.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.pay_type') ? ' and t.pay_type="' . I('get.pay_type') . '"' : '';
		$where .= I('get.start_time') ? ' and t.pay_time>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and t.pay_time<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		if($_GET['act'] == 1){
			$list = $this->getList('a.name area,t.id,t.pname,t.money,t.pay_time,t.pay_type', 'whwx_area as a,whwx_tour_orders t', $where, 't.pay_time desc');
			foreach($list as $k => $v){
				$list[$k]['id'] = "\t" . $v['id'];
				$list[$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
				$list[$k]['pay_type'] = $v['pay_type'] == 'weipay' ? '微信支付' : '线下支付';
			}
			$title = array('小区', '订单号', '产品名称', '订单金额', '支付时间', '支付方式');
			array_unshift($list, $title);
			$file = \Common\Api\PHPExcelApi::exportExcel($list, '旅游收款数据', true);
		}else{
			$list = $this->getList('a.name area,t.id,t.pname,t.money,t.pay_time,t.pay_type', 'whwx_area as a,whwx_tour_orders t', $where, 't.pay_time desc', true);
			$this->assign('list', $list);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			if($_GET['p'] < 2){
				$totalInfo = M()->table('whwx_area as a,whwx_tour_orders t')->field('sum(t.money) total,t.pay_type')->where($where)->group('t.pay_type')->select();
				foreach($totalInfo as $k => $v){
					$totalData['pay' . $v['pay_type']] = $v['total'];
				}
				$totalData['total'] = array_sum($totalData);
				$this->assign('totalData', $totalData);
			}
			$this->display();
		}
	}

	/**
	 * 线下缴费
	 * huying Dec 31, 2015
	 */
	public function offline(){
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$billList = $this->getList('b.id,b.name,b.aid', 'whwx_bill as b,whwx_area as a', 'b.aid = a.id and a.id in(0,' . session('ruleInfo.aids') . ') and b.status = 3', 'year desc');
		$this->assign('billList', $billList);
		$this->display();
	}

	/**
	 * 删除缴费
	 * Sandny 2016年4月26日
	 */
	public function delOffline(){
		$id = I('get.id', 0, 'intval');
		$paymentInfo = M('payment')->field('typeid,real_money,pay_cate')->where(array('pay_type' => 2, 'id' => $id))->find();
		if($paymentInfo){
			$paymentDetail = M('payment_detail')->field('total_money,' . $paymentInfo['pay_cate'] . '_pay')->where(array('id' => $paymentInfo['typeid']))->find();
			$paymentDetail['total_money'] += $paymentInfo['real_money'];
			$paymentDetail[$paymentInfo['pay_cate'] . '_pay'] -= $paymentInfo['real_money'];
			$paymentDetail['status'] = 1;
			$result = M('payment_detail')->where(array('id' => $paymentInfo['typeid']))->save($paymentDetail);
			if($result){
				M('payment')->where(array('pay_type' => 2, 'id' => $id))->delete();
				$this->returnResult(true);
			}
		}
		$this->returnResult(false);
	}

	/**
	 * 修改备注
	 * Sandny 2016年4月26日
	 */
	public function editRemark(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'payment',2);
			$this->returnResult($result);
		}
	}
	
	/**
	 * 导入数据账单
	 * huying Dec 31, 2015
	 */
	public function import(){
		$billInfo = $this->getInfo('aid,excelfile', 'bill', 'id=' . I('post.bill_id', 0, 'intval'));
		if(empty($billInfo['excelfile'])){
			$this->ajaxReturn(array('status' => -1, 'info' => '文件错误，请重新上传'));
		}
		$data = Common\Api\PHPExcelApi::improtExcel($_SERVER["DOCUMENT_ROOT"] . $billInfo['excelfile']);
		//dump($data);
		foreach($data as $k => $value){
			if($k > 1){
				$rid = M('room')->where('aid=' . $billInfo['aid'] . ' and addr="' . trim($value[1]) . '"')->getField('id');
				if($rid > 0){
					array_walk($value, function (&$val){
						$val = trim($val);
						$val = is_numeric($val) ? round($val, 2) : $val;
					});
					$arrear_money_note = $value[17] ? $data[1][17] . '：' . $value[17] . '元；' : '';
					$arrear_money_note .= $value[18] ? $data[1][18] . '：' . $value[18] . '元；' : '';
					$arrear_money_note .= $value[19] ? $data[1][19] . '：' . $value[19] . '元；' : '';
					$arrear_money_note .= $value[20] ? $data[1][20] . '：' . $value[20] . '元；' : '';
					$arrear_money_note .= $value[21] ? $data[1][21] . '：' . $value[21] . '元；' : '';
					$list[] = array('bill_id' => I('post.bill_id', 0, 'intval'), 'rid' => $rid, 'porperty' => $value[6], 'porperty_pay' => $value[7], 'water' => $value[8], 'water_pay' => $value[9], 'energy' => $value[10], 
						'energy_pay' => $value[11], 'carport' => $value[12], 'carport_pay' => $value[13], 'car_manger' => $value[14], 'car_manger_pay' => $value[15], 'status' => $value[22] == 0 ? 2 : 0, 
						'note' => $value[23], 'total_money' => $value[22], 'arrear_money' => $value[17] + $value[18] + $value[19] + $value[20] + $value[21], 'arrear_money_note' => $arrear_money_note);
				}
			}
			if($k % 1000 == 0){
				$result = M('payment_detail')->addAll($list);
				$list = array();
				//echo M()->_sql().'<br />';
			}
		}
		if(!empty($list)){
			$result = M('payment_detail')->addAll($list);
		}
		//echo M()->_sql().'<br />';
		if($result === false){
			$this->ajaxReturn(array('status' => -1, 'info' => '导入数据失败'));
		}else{
			$this->updateData(array('id' => I('post.bill_id', 0, 'intval'), 'status' => 1, 'entry_time' => time()), 'bill', 2);
			$this->ajaxReturn(array('status' => 1, 'info' => '数据导入成功'));
		}
	}

	/**
	 * 缴费
	 * huying Jan 1, 2016
	 */
	public function pay(){
		if(IS_POST){
			// echo "<pre>"; print_r($_POST);exit;
			if(empty($_POST['type'])){
				$this->ajaxReturn(array('status' => 0, 'info' => '请选择缴费项'));
			}
			$pid = I('post.pid', 0, 'intval');
			if($pid > 0){
				$info = $this->getInfo('id,total_money,' . I('post.type'), 'payment_detail', 'id=' . $pid);
				if($info){
					$info['total_money'] -= I('post.real_money');
					$type_arr = array_filter(explode(',', I('post.type')));
					foreach($type_arr as $v){
						$info[$v] += $_POST[$v];
						M('payment')->add(array('type' => 1, 'money' => $_POST[$v], 'creat_time' => time(), 'typeid' => $info['id'], 'pay_type' => 2, 'pay_time' => time(), 'real_money' => $_POST[$v], 'status' => 2, 'remark' => $_POST['remark'], 'pay_cate' => substr($v, 0, -4), 'aid' => $_POST['aid']));
					}
					//$real_money = round(I('post.real_money'),2);
					$info['status'] = $info['total_money'] > 0 ? 1 : 2;
					$result = $this->updateData($info, 'payment_detail', 2);
					$this->returnResult(true, array('缴费成功', '缴费失败'));
				}
				$this->returnResult(false, array('操作成功', '缴费失败'));
			}
		}
	}
	/**
	 * 获取特定小区订单
	 * zhangxinhe 2016-04-05
	 */
	public function getBillList(){
		$list = $this->getList('id,name', 'bill', 'status=3 and aid=' . $_GET['aid']);
		$this->ajaxReturn($list);
	}
	/**
	 * 数据导出
	 * zhangxinhe 2016-04-05
	 */
	public function export(){
		$where = 'p.status=2 and p.type = 1 and p.typeid = d.id and d.bill_id = b.id and d.rid = r.id and r.aid = a.id and a.id in(0,' . session('ruleInfo.aids') . ')';
		$where .= I('post.aid') > 0 ? ' and r.aid=' . I('post.aid', 'intval') : '';
		$where .= I('post.bid') > 0 ? ' and d.bill_id=' . I('post.bid', 'intval') : '';
		$where .= I('post.pay_cate') ? ' and p.pay_cate="' . I('post.pay_cate') . '"' : '';
		$where .= I('post.pay_type', 0, 'intval') > 0 ? ' and p.pay_type=' . I('post.pay_type', 0, 'intval') : '';
		$where .= I('post.start_time') ? ' and p.pay_time>' . strtotime(I('post.start_time')) : '';
		$where .= I('post.end_time') ? ' and p.pay_time<' . (strtotime(I('post.end_time')) + 24 * 3600) : '';
		$list = $this->getList('a.name as area,r.addr as room,b.name as bill,p.oid,p.money,p.real_money,p.pay_time,p.point,p.change_money,p.pay_type,p.pay_cate,p.remark', 'whwx_payment as p, whwx_payment_detail as d, whwx_bill as b, whwx_room as r,whwx_area as a', $where, 'p.pay_time desc');
		foreach($list as $k => $v){
			if($v['oid'] > 0){
				$ownerInfo = $this->getInfo('name,phone', 'owner', 'id=' . $v['oid']);
				$list[$k] = array_merge($list[$k], $ownerInfo);
			}
			unset($list[$k]['oid']);
			$list[$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
			switch($v['pay_type']){
				case 1 :
					$list[$k]['pay_type'] = '微信支付';
					break;
				case 2 :
					$list[$k]['pay_type'] = '积分兑换';
					break;
				default :
					$list[$k]['pay_type'] = '线下支付';
			}
			
			switch($v['pay_cate']){
				case 'porperty' :
					$list[$k]['pay_cate'] = '物业费';
					break;
				case 'energy' :
					$list[$k]['pay_cate'] = '能耗费';
					break;
				case 'water' :
					$list[$k]['pay_cate'] = '二次供水费';
					break;
				case 'carport' :
					$list[$k]['pay_cate'] = '车位费';
					break;
				case 'car_manger' :
					$list[$k]['pay_cate'] = '车位管理费';
					break;
				case 'arrear_money' :
					$list[$k]['pay_cate'] = '历年欠费';
					break;
			}
		}
		$title = array('小区', '房号', '账单名称', '订单金额', '实际缴费', '缴费时间', '兑换积分', '兑换金额', '支付方式', '费用类别', '备注', '缴费姓名', '手机号码');
		array_unshift($list, $title);
		$file = \Common\Api\PHPExcelApi::exportExcel($list, $_POST['name'], false);
		$this->ajaxReturn(array('info' => '导出成功', 'status' => 1, 'url' => $file));
	}
}