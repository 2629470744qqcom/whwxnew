<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 在线缴费
 * huying Dec 31, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class BillController extends AdminController{

	/**
	 * 账单
	 * huying Dec 31, 2015
	 */
	public function index(){
		$where = 'b.status<4 and a.id = b.aid';
		$where .= I('get.aid', 0, 'intval') > 0 ? ' and a.id=' . I('get.aid', 0, 'intval') : ' and a.id in(' . session('ruleInfo.aids') . ')';
		$where .= I('get.name') ? ' and b.name like "%' . I('get.name') . '%"' : '';
		$list = $this->getList('b.id,b.name,a.name as area,b.year,b.entry_time,b.down_time,b.times,b.status', 'whwx_bill as b , whwx_area as a', $where, 'id desc', true);
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 添加账单
	 * huying Dec 31, 2015
	 */
	public function add(){
		if(IS_POST){
			$_POST['times'] = time();
			$_POST['start_time'] = strtotime($_POST['start_time']);
			$_POST['end_time'] = strtotime($_POST['end_time']);
			$result = $this->updateData($_POST, 'bill');
			$this->returnResult($result);
		}else{
			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id desc');
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}

	/**
	 * 修改账单
	 * huying Dec 31, 2015
	 */
	public function edit(){
		if(IS_POST){
			$_POST['start_time'] = strtotime($_POST['start_time']);
			$_POST['end_time'] = strtotime($_POST['end_time']);
			$result = $this->updateData($_POST, 'bill', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,name,aid,excelfile,excelname,remark,start_time,end_time', 'bill', 'id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display('add');
		}
	}

	/**
	 * 删除
	 * huying Dec 31, 2015
	 */
	public function del(){
		$result = M('bill')->where('id=' . I('get.id', 0, 'intval'))->setField('status', 4);
		$this->returnResult($result);
	}

	/**
	 * 记录
	 * huying Dec 31, 2015
	 */
	public function record(){
		$where = 'd.rid = r.id and d.bill_id = ' . I('get.id', 0, 'intval');
		$where .= I('get.addr', '', 'strval') == '' ? '' : ' and addr like "' . I('get.addr', '', 'strval') . '%"';
		$list = $this->getList('d.id,d.porperty,d.porperty_pay,d.arrear_money,d.arrear_money_pay,d.status,d.carport,d.carport_pay,d.energy,d.energy_pay,d.water,d.water_pay,d.car_manger_pay,d.car_manger,d.total_money,r.addr', 'whwx_payment_detail as d,whwx_room as r', $where, 'd.id asc', true);
		$this->assign('list', $list);
		$info = $this->getInfo('id,entry_time,down_time,status', 'bill', 'id=' . I('get.id', 0, 'intval'));
		if(I('get.p', 1, 'intval') < 2){
			$totalInfo = M()->table('whwx_payment_detail as d,whwx_room as r')->field('sum(porperty) porperty_total,sum(porperty_pay) porperty_pay_total,sum(arrear_money) arrear_money_total,sum(arrear_money_pay) arrear_money_pay_total,sum(carport) carport_total,sum(carport_pay) carport_pay_total,sum(energy) energy_total,sum(energy_pay) energy_pay_total,sum(water) water_total,sum(water_pay) water_pay_total,sum(car_manger_pay) car_manger_pay_total,sum(car_manger) car_manger_total,sum(total_money) total_money')->where($where)->find();
			$this->assign('totalInfo', $totalInfo);
		}
		$this->assign('info', $info);
		$this->display();
	}

	/**
	 * 详细说明
	 * huying Jan 1, 2016
	 */
	public function detail(){
		$where = 'r.id = d.rid and d.id = ' . I('post.id', 0, 'intval');
		$info = $this->getInfo('d.id,d.porperty,d.arrear_money,d.carport,d.status,d.total_money,d.energy,d.note,d.water,d.car_manger,r.size,r.addr,d.porperty_pay,d.arrear_money_note,d.carport_pay,d.energy_pay,d.water_pay,d.car_manger_pay', 'whwx_payment_detail as d, whwx_area as a, whwx_room as r', $where);
		$this->assign('info', $info);
		$this->display();
	}

	/**
	 * 下发账单
	 * huying Jan 1, 2016
	 */
	public function down(){
		if(I('post.bill_id', 0, 'intval') > 0){
			$where = 'bill_id = ' . I('post.bill_id', 0, 'intval');
			$result = M('payment_detail')->where('bill_id=' . I('post.bill_id', 0, 'intval').' and status = 0')->setField('status', 1);
			if(result !== false){
				$this->updateData(array('id' => I('post.bill_id', 0, 'intval'), 'status' => 3, 'down_time' => time()), 'bill', 2);
				$this->ajaxReturn(array('status' => 1, 'info' => '下发成功'));
			}
		}
		$this->ajaxReturn(array('status' => 1, 'info' => '下发失败'));
	}

	/**
	 * 核对账单
	 * huying Jan 1, 2016
	 */
	public function check(){
		if(I('post.bill_id', 0, 'intval') > 0){
			$result = $this->updateData(array('id' => I('post.bill_id', 0, 'intval'), 'status' => 2, 'check_time' => time()), 'bill', 2);
			if($result !== false){
				$this->ajaxReturn(array('status' => 1, 'info' => '核对成功'));
			}
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '核对失败'));
	}
	/**
	 * 修改记录
	 * huying Mar 11, 2016
	 */
	public function edit_record(){
		if(IS_POST){
			$_POST['porperty'] = I('post.porperty', 0, 'intval'); $_POST['porperty_pay'] = I('post.porperty_pay', 0, 'intval');
			$_POST['water'] = I('post.water', 0, 'intval'); $_POST['water_pay'] = I('post.water_pay', 0, 'intval');
			$_POST['energy'] = I('post.energy', 0, 'intval'); $_POST['energy_pay'] = I('post.energy_pay', 0, 'intval');
			$_POST['carport'] = I('post.carport', 0, 'intval'); $_POST['carport_pay'] = I('post.carport_pay', 0, 'intval');
			$_POST['car_manger'] = I('post.car_manger', 0, 'intval'); $_POST['car_manger_pay'] = I('post.car_manger_pay', 0, 'intval');
			$_POST['arrear_money'] = I('post.arrear_money', 0, 'intval'); $_POST['arrear_money_pay'] = I('post.arrear_money_pay', 0, 'intval');
			if($_POST['porperty_pay'] > $_POST['porperty']){
				$this->ajaxReturn(array('status' => 0, 'info' => '物业费实收金额不应大于应收金额'));
			}
			if($_POST['water_pay'] > $_POST['water']){
				$this->ajaxReturn(array('status' => 0, 'info' => '二次供水费实收金额不应大于应收金额'));
			}
			if($_POST['energy_pay'] > $_POST['energy']){
				$this->ajaxReturn(array('status' => 0, 'info' => '能耗费实收金额不应大于应收金额'));
			}
			if($_POST['carport_pay'] > $_POST['carport']){
				$this->ajaxReturn(array('status' => 0, 'info' => '车位费实收金额不应大于应收金额'));
			}
			if($_POST['car_manger_pay'] > $_POST['car_manger']){
				$this->ajaxReturn(array('status' => 0, 'info' => '车位管理费实收金额不应大于应收金额'));
			}
			if($_POST['arrear_money_pay'] > $_POST['arrear_money']){
				$this->ajaxReturn(array('status' => 0, 'info' => '历年欠费实收金额不应大于应收金额'));
			}
			$_POST['total_money'] = $_POST['porperty'] + $_POST['arrear_money'] + $_POST['energy'] + $_POST['carport'] + $_POST['car_manger'] + $_POST['water'] - ($_POST['porperty_pay'] + $_POST['arrear_money_pay'] + $_POST['energy_pay'] + $_POST['carport_pay'] + $_POST['car_manger_pay'] + $_POST['water_pay']);
			$_POST['status'] = $_POST['total_money'] > 0 ? 1 : 2;
			$result = $this->updateData($_POST, 'payment_detail', 2);
			$this->returnResult($result, null, U('Bill/record?id='.$_POST['bid'].'&p='.$_POST['p']));
		}else{
			$where = 'r.id = d.rid and d.id = ' . I('get.id', 0, 'intval');
			$info = $this->getInfo('d.id,d.bill_id as bid,d.porperty,d.arrear_money,d.arrear_money_pay,d.porperty_pay,d.arrear_money_note,d.carport_pay,d.energy_pay,d.carport,d.status,d.total_money,d.energy,d.note,d.water,d.car_manger,r.size,r.addr,d.water_pay,d.car_manger_pay,d.pay_cate', 'whwx_payment_detail as d, whwx_room as r', $where);
			$this->assign('info', $info);
			$this->display();
		}
	}
	/**
	 * 获取业主的账单
	 * huying Dec 31, 2015
	 */
	public function getBill(){
		if($_POST['aid'] > 0 && $_POST['bid'] > 0 && $_POST['uid'] > 0 && $_POST['rid'] > 0 && $_POST['bill_id'] > 0){
			$paymentInfo = $this->getInfo('d.id,d.bill_id as bid,d.porperty,d.arrear_money,d.arrear_money_pay,d.porperty_pay,d.arrear_money_note,d.carport_pay,d.energy_pay,
				d.carport,d.status,d.total_money,d.energy,d.note,d.water,d.car_manger,r.size,r.addr,r.oid,d.water_pay,d.car_manger_pay', 'whwx_payment_detail as d, whwx_room as r', 'd.rid = r.id and d.bill_id=' . I('post.bill_id', 0, 'intval') . ' and d.rid=' . $_POST['rid']);
			//echo M()->_sql();
			if(empty($paymentInfo)){
				$this->ajaxReturn(array('status' => -1, 'info' => '没有找到该房号的账单信息'));
			}
			if($paymentInfo['oid'] > 0){
				$ownerInfo = $this->getInfo('name,phone', 'owner', 'id=' . $paymentInfo['oid']);
				$paymentInfo = array_merge($paymentInfo, $ownerInfo);
			}
			$this->assign('info', $paymentInfo);

			$left_money = ($paymentInfo['porperty'] - $paymentInfo['porperty_pay']) + ($paymentInfo['energy'] - $paymentInfo['energy_pay']) + ($paymentInfo['water'] - $paymentInfo['water_pay']) + ($paymentInfo['carport'] - $paymentInfo['carport_pay']) + ($paymentInfo['car_manger'] - $paymentInfo['car_manger_pay']) + ($paymentInfo['arrear_money'] - $paymentInfo['arrear_money_pay']);

			// echo "<pre>"; 

			// echo $paymentInfo['porperty'], $paymentInfo['porperty_pay'], "eneygy:",$paymentInfo['energy'],$paymentInfo['energy_pay'],"water:",$paymentInfo['water'],$paymentInfo['water_pay'], "carpot:",$paymentInfo['carport'],$paymentInfo['carport_pay'],"car_manger:",$paymentInfo['car_manger'],$paymentInfo['car_manger_pay'],"arrear:",$paymentInfo['arrear_money'],$paymentInfo['arrear_money_pay'];

			// print_r($left_money); print_r($paymentInfo);die;

			$this->assign('left_money', $left_money);

			$this->display();
		}else{
			$this->ajaxReturn(array('status' => -1, 'info' => '数据错误'));
		}
	}
	/**
	 * 导出账单
	 * huying Dec 31, 2015
	 */
	public function exportRecord(){
		$where = 'd.rid = r.id and d.bill_id = ' . I('get.id', 0, 'intval');
		$list = $this->getList('r.addr,d.porperty,d.porperty_pay,d.water,d.water_pay,d.energy,d.energy_pay,d.carport,d.carport_pay,d.car_manger,d.car_manger_pay,d.arrear_money,d.arrear_money_pay,d.arrear_money_note,d.total_money,d.status', 'whwx_payment_detail as d,whwx_room as r', $where, 'd.id asc');
		foreach ($list as $k => $v){
			$sumMoney = $v['porperty_pay'] + $v['energy_pay'] + $v['water_pay'] + $v['carport_pay'] + $v['car_manger_pay'] + $v['arrear_money_pay'];
			switch ($v['status']) {
				case 0: $list[$k]['status'] = '未下发'; break;
				case 2: $list[$k]['status'] = '已缴费'; break;
				default: $list[$k]['status'] = $sumMoney == $v['total_money'] ? '已缴费' : '未缴费';
			}
		}
		$title = array('房号', '物业费应收', '物业费实收', '二次供水费应收', '二次供水费实收', '能耗费应收', '能耗费实收', '车位费应收', '车位费实收', '车位管理费应收', '车位管理费实收', '历年欠费应收', '历年欠费实收', '历年欠费详情', '总费用', '状态');
		array_unshift($list, $title);
		$name = M('bill')->where('id=' . $_GET['id'])->getField('name');
		$file = \Common\Api\PHPExcelApi::exportExcel($list, $name, false);
		header('Location:' . $file);
	}
	/**
	 * 账单对比，查看哪些房号没有账单
	 * huying Dec 31, 2015
	 */
	public function compare(){
		$bill_id = I('get.bill_id', 0, 'intval');
		$aid = M('bill')->where('id=' . $bill_id)->getField('aid');
		$roomIds = M('room')->where('aid=' . $aid)->getField('id', true);
		$paymentIds = M('payment_detail')->where('bill_id=' . $bill_id)->getField('rid', true);
		$result = array_diff($roomIds, $paymentIds);
		if($result){
			$list = $this->getList('id,addr,size,owner,phone', 'room', 'id in (' . implode(',', $result) . ')', 'id desc', true);
		}
		$this->assign('list', $list);
		$this->display();
	}

	public function add_record(){
		if(IS_POST){
			$_POST['porperty'] = I('post.porperty', 0, 'intval'); $_POST['porperty_pay'] = I('post.porperty_pay', 0, 'intval');
			$_POST['water'] = I('post.water', 0, 'intval'); $_POST['water_pay'] = I('post.water_pay', 0, 'intval');
			$_POST['energy'] = I('post.energy', 0, 'intval'); $_POST['energy_pay'] = I('post.energy_pay', 0, 'intval');
			$_POST['carport'] = I('post.carport', 0, 'intval'); $_POST['carport_pay'] = I('post.carport_pay', 0, 'intval');
			$_POST['car_manger'] = I('post.car_manger', 0, 'intval'); $_POST['car_manger_pay'] = I('post.car_manger_pay', 0, 'intval');
			$_POST['arrear_money'] = I('post.arrear_money', 0, 'intval'); $_POST['arrear_money_pay'] = I('post.arrear_money_pay', 0, 'intval');
			if($_POST['porperty_pay'] > $_POST['porperty']){
				$this->ajaxReturn(array('status' => 0, 'info' => '物业费实收金额不应大于应收金额'));
			}
			if($_POST['water_pay'] > $_POST['water']){
				$this->ajaxReturn(array('status' => 0, 'info' => '二次供水费实收金额不应大于应收金额'));
			}
			if($_POST['energy_pay'] > $_POST['energy']){
				$this->ajaxReturn(array('status' => 0, 'info' => '能耗费实收金额不应大于应收金额'));
			}
			if($_POST['carport_pay'] > $_POST['carport']){
				$this->ajaxReturn(array('status' => 0, 'info' => '车位费实收金额不应大于应收金额'));
			}
			if($_POST['car_manger_pay'] > $_POST['car_manger']){
				$this->ajaxReturn(array('status' => 0, 'info' => '车位管理费实收金额不应大于应收金额'));
			}
			if($_POST['arrear_money_pay'] > $_POST['arrear_money']){
				$this->ajaxReturn(array('status' => 0, 'info' => '历年欠费实收金额不应大于应收金额'));
			}
			$_POST['total_money'] = $_POST['porperty'] + $_POST['arrear_money'] + $_POST['energy'] + $_POST['carport'] + $_POST['car_manger'] + $_POST['water'] - ($_POST['porperty_pay'] + $_POST['arrear_money_pay'] + $_POST['energy_pay'] + $_POST['carport_pay'] + $_POST['car_manger_pay'] + $_POST['water_pay']);
			$_POST['status'] = $_POST['total_money'] > 0 ? 1 : 2;
			$result = $this->updateData($_POST, 'payment_detail');
			$this->returnResult($result, null, U('Bill/compare?bill_id='.$_POST['bill_id']));
		}else{
			$this->display();
		}
	}
}