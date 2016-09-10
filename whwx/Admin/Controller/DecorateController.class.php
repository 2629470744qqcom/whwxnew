<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 装修申请管理
 * huying Dec 31, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class DecorateController extends AdminController{

	/**
	 * 装修申请
	 * huying Jan 9, 2016
	 */
	public function index(){
		$where = 'p.type=4 and p.typeid=d.id and d.rid=r.id and p.aid=a.id and p.aid in (0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.name') ? ' and d.name like "%' . I('get.name') . '%"' : '';
		$where .= I('get.phone') ? ' and d.phone like "%' . I('get.phone') . '%"' : '';
		$where .= I('get.addr') ? ' and r.addr like "' . I('get.addr') . '%"' : '';
		$where .= I('get.aid') > 0 ? ' and r.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.pay_type', 0, 'intval') > 0 ? 'and p.pay_type =' . I('get.pay_type', 0, 'intval') : '';
		$list = $this->getList('d.id,d.name as owner,d.status,d.phone,d.times,d.id_pic,d.company_pic,d.design_pic,p.money,p.pay_time,p.pay_type,p.real_money,a.name as area,r.addr,r.size', 'whwx_payment as p, whwx_decorate as d, whwx_room as r, whwx_area as a', $where, 'd.times desc', true);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->assign('list', $list);
		$this->display();
	}

	/**
	 * 线下缴费
	 * huying Jan 9, 2016
	 */
	public function addDecorate(){
		if(IS_POST){
			$rid = I('post.rid', 0, 'intval');
			$status = M('decorate')->where('rid=' . $rid)->getField('status');
			if($status == 1){
				$this->returnResult(false, array(null, '已缴费'));
			}
			$data = M('owner')->field('aid,name,phone')->where('pid=0 and rid=' . $rid)->find();
			if(!$data){
				$data['aid'] = M('room')->where('id=' . $rid)->getField('aid');
				$data['name'] = $data['phone'] = '未绑定';
			}
			$data['rid'] = $rid;
			$data['status'] = 1;
			$data['money'] = I('post.money', 0, 'intval');
			$data['times'] = time();
			$result = $this->updateData($data, 'decorate');
			if($result){
				M('payment')->add(array('aid' => $data['aid'], 'creat_time' => time(), 'status' => 2, 'oid' => 0, 'type' => 4, 'typeid' => $result, 'pay_time' => time(), 'pay_type' => 2, 'remark' => '装修垃圾清理费', 'money' => I('post.money', 0, 'intval'), 'real_money' => I('post.money', 0, 'intval')));
				$this->returnResult(true);
			}
			$this->returnResult(false);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}

	/**
	 * 获取业主的信息
	 */
	public function getOwner(){
		$rid = I('post.rid', 0, 'intval');
		if($rid){
			$owner = $this->getInfo('o.id oid,r.id,o.name,o.phone,r.size,r.addr,a.decorate', 'whwx_owner as o,whwx_room as r,whwx_area as a', 'o.id=r.oid and a.id=r.aid and r.id=' . $rid);
			if($owner){
				$id = M('decorate')->where('status=1 and rid=' . $rid)->getField('id');
				if($owner['oid'] == 0){
					$owner['name'] = '未绑定';
					$owner['phone'] = '未绑定';
				}
				$owner['status'] = $id > 0 ? 2 : 1;
				$owner['money'] = round($owner['size'] * $owner['decorate']);
				$this->ajaxReturn($owner);
			}
			$this->ajaxReturn(array('status' => -1, 'info' => '没有找到该房号的信息'));
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '数据错误'));
	}

	/**
	 * 线下审核
	 * zhangxinhe 2016-04-05
	 */
	public function check(){
		$id = I('post.id', 0, 'intval');
		$status = I('post.status', 0, 'intval');
		$result = M('decorate')->where('id=' . $id)->save(array('status' => $status, 'reason' => I('post.reason')));
		$paymentInfo = M('payment')->field('oid,money,status')->where(array('type' => 4, 'typeid' => $id))->find();
		if($status == 1 && $paymentInfo['status'] == 1){
			M('payment')->where(array('type' => 4, 'typeid' => $id))->save(array('status' => 2, 'real_money' => $paymentInfo['money'], 'pay_time' => time(), 'pay_type' => 2));
		}
		$openid = M('wxfans')->where('type=1 and oid=' . $paymentInfo['oid'])->getField('openid');
		if($openid){
			$data = array('first' => array('value' => '您的装修申请已处理！', 'color' => '#ff0000'), 'keyword1' => array('value' => '装修申请', 'color' => '#173177'), 'keyword2' => array('value' => $status == 1 ? '审核通过' : '审核未通过', 'color' => '#173177'), 
				'keyword3' => array('value' => I('post.reason', '无'), 'color' => '#173177'), 'remark' => array('value' => '点击查看详情', 'color' => '#173177'));
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			$wechatAuth->sendTemplateMsg($openid, C('decorate_check_template'), U('Wap/Apply/index'), $data);
		}
		$this->returnResult($result);
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
		$list = $this->getList('a.name as area,r.addr as room,b.name as bill,p.oid,p.money,p.real_money,p.pay_time,p.point,p.change_money,p.pay_type,p.remark', 'whwx_payment as p, whwx_payment_detail as d, whwx_bill as b, whwx_room as r,whwx_area as a', $where, 'p.pay_time desc');
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
		}
		$title = array('小区', '房号', '账单名称', '订单金额', '实际缴费', '缴费时间', '兑换积分', '兑换金额', '支付方式', '备注', '缴费姓名', '手机号码');
		array_unshift($list, $title);
		$file = \Common\Api\PHPExcelApi::exportExcel($list, $_POST['name'], false);
		$this->ajaxReturn(array('info' => '导出成功', 'status' => 1, 'url' => $file));
	}
}