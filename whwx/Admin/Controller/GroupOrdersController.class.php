<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class GroupOrdersController extends AdminController{

	public function index(){
		$where = 's.pid =p.id and s.oid = o.id and s.aid = a.id and s.aid in(0,'.session('ruleInfo.aids').')';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and s.pid=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.status', -1, 'intval') == -1 ? ' and s.status<6' : ' and s.status=' . I('get.status', -1, 'intval');
		$where .= I('get.pay_type', -1, 'intval') == -1 ? '' : ' and s.pay_type=' . I('get.pay_type', -1, 'intval');
		$where .= I('get.gname')? ' and p.name like "%'.I('get.gname').'%"' : '';
		$list = $this->getList('s.order_amount,s.pay_amount,s.single_time,s.pay_time,s.pay_type,s.status,s.oid,s.pid,s.total,s.prix,p.name,s.id as id,a.name as area', 'whwx_group_orders s,whwx_group_product p,whwx_area a,whwx_owner o', $where, 'single_time desc', true);
		$this->assign('list', $list);
		$product = $this->getList('id,name', 'group_product', 'status = 1', 'id desc');
		$this->assign('product', $product);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 删除特惠团订单
	 * yaoyingli 2015年12月28日
	 */
	public function del(){
		// $result= M('group_orders')->where('id=' . $_GET['id'] . ' and status='. 3 )->delete();
		$result = $this->deleteData('id=' . $_GET['id'] . ' and status=3', 'group_orders');
		$this->returnResult($result ? true : false, array('操作成功', '订单未完成,不可删除!'));
	}

	/**
	 * 录入快递信息
	 * huying Jan 14, 2016
	 */
	public function input(){
		$act = I('post.act', 0, 'intval');
		if($act == 1){
			$info = $this->getInfo('id,ems_name,ems_num', 'logistics', 'orderid=' . I('post.id', 0, 'intval'));
			$this->ajaxReturn($info);
		}else{
			$result = $this->updateData($_POST, 'logistics', 2, 'orderid = ' . $_POST['orderid']);
			if($result !== false){
				$info = $this->getInfo('oid', 'group_orders', 'id=' . $_POST['orderid']);
				// 订单通知
				\Common\Api\CommonApi::addNotice($info['oid'], '订单通知', '你购买的特惠团产品发货啦，快去看看吧', 4, $_POST['orderid']);
				M('group_orders')->where('id=' . I('post.orderid', 0, 'intval'))->setField('status', 3);
			}
			$this->returnResult($result);
		}
	}

	/**
	 * 特惠团物流信息查看
	 * yaoyingli 2015年12月28日
	 */
	public function logistics(){
		$info = $this->getInfo('id,name,phone,address,ems_name,ems_num', 'logistics', 'orderid=' . I('get.id', 0, 'intval'));
		$this->assign('info', $info);
		$list = \Common\Api\EmsApi::findEms(\Common\Api\EmsApi::searchExpress($info['ems_name']), $info['ems_num']);
		// $this->ajaxReturn(array('info'=>$info,'list'=>$list) );
		// $result = \Common\Api\EmsApi::findEms('yuantong', '881054701237537978');
		$this->assign('list', $list);
		$this->display();
	}

	/**
	 * 特惠团订单查看/反馈
	 * yaoyingli 2015年12月28日
	 */
	public function detail(){
		if(IS_POST){
			if(empty($_POST['content'])){
				$this->ajaxReturn(array('status' => -1, 'info' => '请输入反馈内容!'));
			}
			$result = \Common\Api\CommonApi::addFollow(session('aid'), session('ainfo.name'), session('ainfo.tel'), 2, I('post.id', 0, 'intval'), $_POST['content']);
			if($result > 0){
				$this->ajaxReturn(array('status' => $result, 'info' => '添加成功'));
			}else{
				$this->ajaxReturn(array('status' => -1, 'info' => '添加失败'));
			}
		}else{
			$info = $this->getInfo('o.name,o.phone,o.addr,s.total,s.pay_time,s.pay_type,p.pics,p.content,p.name as title,s.id as id,s.single_time,s.status,s.prix,s.order_amount,s.pay_amount,s.pay_order,s.oid', 'whwx_group_orders s,whwx_group_product as p, whwx_owner as o', 's.pid=p.id  and s.oid = o.id  and s.id=' . I('get.id', 0, 'intval'));
			// 用户地址的处理
			$info['owner'] = $this->getInfo('a.id as aid,o.addr,o.aid,o.id,a.name as area', 'whwx_owner o,whwx_area a', 'o.aid =a.id and o.id=' . $info['oid']);
			$info['owner']['addr'] = array_reverse(explode('-', $info['owner']['addr']));
			$info["addr"] = $info['owner']["area"] . $info['owner']['addr'][2] . "栋" . $info['owner']['addr'][1] . "单元" . $info['owner']['addr'][0] . '室';
			$info['order'] = $this->getInfo('transaction_id', 'payment', 'typeid=' . I('get.id', 0, 'intval'));
			$info['pay_order'] = $info['order']['transaction_id'];
			// dump($info['pay_order_num']);
			if($info['status'] >= 5){
				$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type = 3 and rid = ' . I('get.id', 0, 'intval'));
			}
			if($info['status'] >= 3){
				$info['ems'] = $this->getInfo('ems_name,ems_num,address', 'logistics', 'orderid=' . I('get.id', 0, 'intval'));
			}
			if(!empty($info['pics'])){
				$info['pics'] = explode(',', $info['pics']);
			}
			$this->assign('info', $info);
			// 订单跟踪
			$list = $this->getList('id,oid,oname,otel,content,times', 'follow', 'type = 2 and typeid=' . I('get.id', 0, 'intval'), 'times asc');
			$this->assign('list', $list);
			$this->display();
		}
	}

	/**
	 * 导出订单
	 * huying Jan 28, 2016
	 */
	public function export(){
		$where = 'o.id = l.orderid and o.oid = o2.id and o2.aid = a.id and g.id = o.pid';
		$where .= I('post.gname')? ' and g.name like "%'.I('post.gname').'%"' : '';
		$where .= I('post.aid', 0, 'intval') > 0 ? ' and o2.aid=' . I('post.aid', 0, 'intval') : '';
		$start_time = strtotime($_POST['start_time']);
		$end_time = strtotime($_POST['end_time']) + 24 * 3600;
		$where .= ' and o.single_time > ' . $start_time . ' and o.single_time <' . $end_time;
		$data = M('group_orders as o, whwx_logistics as l, whwx_area as a, whwx_owner as o2, whwx_group_product as g')->
				field('o.id,date_format(from_unixtime(o.single_time),"%Y-%m-%d %H:%i:%s") as single_time,o.status,g.name as gname,
						g.present_price,o.total,o.order_amount,o.pay_type,date_format(from_unixtime(o.pay_time),"%Y-%m-%d %H:%i:%s") as pay_time,
						l.name,l.phone,a.name as area,l.address,l.ems_name,l.ems_num')->where($where)->limit(0, 10000)->select();
		if($data){
			foreach ($data as $k => $v){
// 				if($v['pay_type'] == 1 || $v['pay_type'] == 2){
					//获取支付金额和支付ID
				$payment = $this->getInfo('real_money,transaction_id', 'payment', 'type = 3 and typeid='.$v['id']);
				$data[$k]['real_money'] = empty($payment['real_money']) ? '无' : $payment['real_money'];
				$data[$k]['transaction_id'] = empty($payment['transaction_id'])? '无' : $payment['transaction_id'];
// 				}
				switch($v['pay_type']){
					case 1: $data[$k]['pay_type'] = '微信支付';break;
					case 2: $data[$k]['pay_type'] = '货到付款';break;
					case 3: $data[$k]['pay_type'] = '积分兑换';break;
					default: $data[$k]['pay_type'] = '未支付';
				}
				$commentInfo = $this->getInfo('score,desc', 'comment', 'type = 3 and rid=' . $v['id']);
				$data[$k]['comment_score'] = $commentInfo['score'];
				$data[$k]['comment_desc'] = $commentInfo['desc'];
				
				switch($v['status']){
					case 0: $data[$k]['status'] = '未支付';break;
					case 1: $data[$k]['status'] = '已取消';break;
					case 2: $data[$k]['status'] = '已支付';break;
					case 3: $data[$k]['status'] = '已发货';break;
					case 4: $data[$k]['status'] = '未评价';break;
					case 5: $data[$k]['status'] = '已完成';break;
					case 6: $data[$k]['status'] = '已删除';break;
				}
			}
			$title = array('订单号', '下单时间', '订单状态', '产品', '单价', '数量', '总价', '支付方式', '支付时间', '收货人', '联系电话', '小区','收货地址', '物流公司', '物流单号', '支付金额', '支付ID', '评分','评价');
			array_unshift($data, $title);
			$file = \Common\Api\PHPExcelApi::exportExcel($data, $_POST['name'], false);
			$this->ajaxReturn(array('info' => '导出成功', 'status' => 1, 'url' => $file));
		}else{
			$this->error('没有数据');
		}
	}
}