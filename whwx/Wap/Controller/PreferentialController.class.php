<?php
namespace Wap\Controller;
use Common\Controller\WapController;
use Common\Api\CommonApi;
use Common\Api\EmsApi;

/**
 * 特惠团
 * yaoyongli 2016年1月8日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class PreferentialController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
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
	 * 首页
	 * yaoyongli 2016年1月8日
	 */
	public function index(){
		$list = $this->getList('id,name,present_price,sort,status,pics,category,credit,label', 'group_product', 'status=1 and num>0', 'sort desc', true);
		//$info['label']='#label' .implode(',#label', explode(',',$info['label']));
		foreach ($list as $k => $v){
			if(!empty($v['label'])){
				$list[$k]['label'] = explode(' ', $v['label']);
			}
		}
		foreach($list as $k => $v){
			if(!empty($v['pics'])){
				$list[$k]['pics'] = explode(',', $v['pics']);
			}
		}
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 特惠团我的订单页面
	 * yaoyongli 2016年1月8日
	 */
	public function orders(){
		$_POST['single_time'] = time();
		$list = $this->getList('p.id,p.name,s.prix,s.style,p.sort,p.status,p.pics,s.total,s.pid,s.oid,s.status,s.single_time,s.order_amount,s.id as id', 'whwx_group_product p,whwx_group_orders s', 's.pid=p.id and s.status<>6 and s.oid='.session('fansInfo.oid'), 'single_time desc',true);
		foreach($list as $k => $v){
			if(!empty($v['pics'])){
				$list[$k]['pics'] = explode(',', $v['pics']);
			}
		}
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 特惠团我的订单页面的取消订单按钮状态
	 * yaoyongli 2016年1月12日
	 */
	public function status(){
		$statusinfo = $this->getInfo('status,pid,total', 'group_orders', 'id=' . I('get.id', 0, 'intval'));
		if($statusinfo['status'] == 0){
			$data = array('pay_type' => 0, 'status' => 1, 'id' => I('get.id', 0, 'intval'));
			$result = $this->updateData($data, 'group_orders', 2);
			if($result !== false){
				M('group_product')->where('id=' . $statusinfo['pid'])->setInc('num', $statusinfo['total']);
				$this->ajaxReturn(array('status' => 1, 'info' => '操作成功'));
			}else{
				$this->ajaxReturn(array('status' => -1, 'info' => '操作失败'));
			}
		}else{
			$this->ajaxReturn(array('status' => -1, 'info' => '操作失败,你已下单,请刷新!'));
		}
	}
	/**
	 * 特惠团我的订单页面逻辑删除,status为6
	 * yaoyongli 2016年2月24日
	 */
	public function del(){
		$statusinfo = $this->getInfo('status', 'group_orders', 'id=' . I('get.id', 0, 'intval'));
		if($statusinfo['status'] == 1 or $statusinfo['status'] == 5){
			$data = array('status' => 6, 'id' => I('get.id', 0, 'intval'));
			$result = $this->updateData($data, 'group_orders', 2);
			if($result !== false){
				$this->ajaxReturn(array('status' => 6, 'info' => '操作成功'));
			}else{
				$this->ajaxReturn(array('status' => -1, 'info' => '操作失败'));
			}	
		}
	}
	/**
	 * 特惠团我的订单详情页面
	 * yaoyongli 2016年1月8日
	 */
	public function orders_details(){
// 		$info=$this->getInfo('p.status as product,s.status as order', 'whwx_group_product p,whwx_group_orders s', 's.pid =p.id and s.id='. $_GET['id']);
// 		if($info['product']<>1 and $info['order']==0){
// 			$this->ajaxReturn(array('status' => -1, 'info' => '物品已下架!'));
// 		}
		if(I('get.typeid', 0, 'intval') > 0){
			M('owner_notice')->where('id='.I('get.typeid', 0, 'intval'))->setField('status', 0);
		}
		$info = $this->getInfo("p.name as title,p.status as product,p.id,p.pics,s.prix,s.style,s.pid,s.oid,s.order_amount,s.single_time,s.pay_type,s.pay_amount,s.total,s.id,s.status", "whwx_group_product p,whwx_group_orders s", 's.pid=p.id and s.id=' . $_GET['id']);
// 		if($info['product']<>1 and $info['status']==0){
// 			$info['pstatus']=1;
// 		}
			if(!empty($info['pics'])){
				$info['pics'] = explode(',', $info['pics']);
			}
			$infoman = $this->getInfo('name,phone,address', 'logistics', 'orderid=' . $_GET['id']);
			$this->assign('infoman', $infoman);
			$this->assign('info', $info);
		
		
		$this->display();
	}
	/**
	 * 特惠团详情页面
	 * yaoyongli 2016年1月8日
	 */
	public function favorable(){
		$info = $this->getInfo('id,name,present_price,original_price,num,limit_num,sort,content,status,pics,category,credit', 'group_product', 'status=1 and id=' . $_GET['id'], 'sort desc', true);
		if(!empty($info['pics'])){
			$info['pics'] = explode(',', $info['pics']);
		}
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 特惠团物流信息页面
	 * yaoyongli 2016年1月8日
	 */
	public function logistics(){
		$info = $this->getInfo('id,name,phone,address,ems_name,ems_num', 'logistics', 'orderid=' . I('get.id', 0, 'intval'));
		$this->assign('info', $info);
		$list = \Common\Api\EmsApi::findEms(\Common\Api\EmsApi::searchExpress($info['ems_name']), $info['ems_num']);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 特惠团评价页面
	 * yaoyongli 2016年1月18日
	 */
	public function evaluate(){
		$info = $this->getInfo("o.id,o.name,o.phone,o.address,p.id,s.pid,s.oid,s.id,s.status", "whwx_owner o,whwx_group_product p,whwx_group_orders s", 'p.status=1 and s.pid=p.id and s.oid=o.id and s.id=' . I('get.id', 0, 'intval'));
		if($info['status'] == 5){
			$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type = 3 and rid = '.I('get.id', 0, 'intval'));
		}
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 特惠团评价
	 * yaoyongli 2016年1月18日
	 */
	public function comment(){
		$info = $this->getInfo('pid,pay_amount', 'group_orders', 'id=' . I('post.id', 0, 'intval'));
		if(!empty($info)){
			$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 5), 'group_orders', 2);
			if($result > 0){
				$result1 = $this->updateData(array('oid' => session('fansInfo.oid'), 'times' => time(), 'type' => 3, 'typeid' => $info['pid'], 'score' => I('post.score', 0, 'intval'), 'desc' => $_POST['desc'], 'rid' => I('post.id', 0, 'intval'), 'status' => 1,'aid'=>session('fansInfo.aid')), 'comment');
				if($result1 > 0){
					$point = C('score_point');
					if($point > 0){
						$this->changePoint(session('fansInfo.oid'), $point, '特惠团评价', 4, I('post.id', 0, 'intval'));
					}
					$this->ajaxReturn(array('status' => 5, 'info' => '评价成功'));
				}
			}
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '评价失败'));
	}
	/**
	 * 获取的订单id
	 * yaoyongli 2016年1月22日
	 */
	public function getPid(){
		$pid = M('payment')->where('type = 3 and typeid=' . I('post.id', 0, 'intval'))->getField('id');
		$this->ajaxReturn($pid);
	}
	/**
	 * 特惠团我的订单详情页面的支付方式
	 * yaoyongli 2016年1月8日
	 */
	public function type(){
		$payment = $this->getInfo('id,status,money', 'payment', 'type =3 and typeid=' . $_GET['id']);
		// dump($payment);
		$orderinfo = $this->getInfo('order_amount,status', 'group_orders', 'id=' . $_GET['id']);
		if($orderinfo['status'] == 0){
			if($_GET['type']==3){
			    $maninfo = $this->getInfo('point', 'owner', 'id=' . session('fansInfo.oid'));
				if($maninfo["point"] < $orderinfo['order_amount']){
					$this->ajaxReturn(array('status' => -1, 'info' => '积分不足'));
				}else{
					$point = $orderinfo['order_amount'];
					$this->changePoint(session('fansInfo.oid'), $point, '特惠团积分支付', 7, $_GET['id'], 0);
				};
		       $data = array('pay_type' => 3, 'status' => 2, 'pay_amount' =>$orderinfo['order_amount'], 'pay_time' => time(), 'id' => I('get.id', 0, 'intval'));
			} else{
				   $data = array('pay_type' => 2, 'status' => 2, 'pay_amount' => 0, 'pay_time' => time(), 'id' => I('get.id', 0, 'intval'));
			}             
			$result1 = $this->updateData(array('id' => $payment['id'], 'pay_type' => 2, 'status' => 2, 'real_money' => $payment['money'], 'pay_time' => time()), 'payment', 2);
			$result = $this->updateData($data, 'group_orders', 2);
			if($result !== false){
				if($_GET['type'] != 3){
					$point = floor($payment['money'] / C('get_money') * C('get_point'));
					$this->changePoint(session('fansInfo.oid'), $point, '特惠团购买赠送', 4, $_GET['id']);
				}
				$this->ajaxReturn(array('status' => 2, 'info' => '操作成功'));
			}else{
				$this->ajaxReturn(array('status' => -1, 'info' => '操作失败'));
			}
		}
	}
	/**
	 * 特惠团我的订单详情页面的确认收货方式
	 * yaoyongli 2016年1月8日
	 */
	public function typea(){
		$payment = $this->getInfo('id,status,money', 'payment', 'type =3 and typeid=' . $_GET['id']);
		$orderinfo = $this->getInfo('order_amount,status', 'group_orders', 'id=' . $_GET['id']);
		if($orderinfo['status'] == 3){
			$result1 = $this->updateData(array('id' => $payment['id'], 'real_money' => $payment['money'], 'pay_time' => time()), 'payment', 2);
			$data = array('status' => 4, 'pay_amount' => $orderinfo['order_amount'], 'id' => I('get.id', 0, 'intval'));
			$result = $this->updateData($data, 'group_orders', 2);
			if($result !== false){
				$this->ajaxReturn(array('status' => 4, 'info' => '操作成功'));
			}else{
				$this->ajaxReturn(array('status' => -1, 'info' => '操作失败'));
			}
		}else{
			$this->ajaxReturn(array('status' => -1, 'info' => '操作失败'));
		}
	}
	/**
	 * 立即购买按钮事件
	 * 增加订单
	 * yaoyongli 2016年1月12日
	 */
	public function addOrder(){
		$productInfo = $this->getInfo('present_price, num,category,credit,status', "group_product", 'id=' . I('get.id', 0, 'intval'));
		if($productInfo['num'] < I('get.num', 0, 'intval')){
			$this->ajaxReturn(array('status' => -1, 'info' => '库存不足'));
		}
		if($productInfo["status"]==0){
			$this->ajaxReturn(array('status' => -1, 'info' => '抱歉!商品已下架'));
		}
		$_POST['single_time'] = time();
		$_POST['total'] = $_GET['num'];
		// 判断钱币还是积分,实现各自的累加,将钱币或积分放在订单的单价prix中
		if($productInfo["category"] == 0){
			$_POST['order_amount'] = intval($_GET['num']) * $productInfo['present_price'];
			$_POST["prix"] = $productInfo['present_price'];
		}else{
			$_POST['order_amount'] = intval($_GET['num']) * $productInfo['credit'];
			$_POST["prix"] = $productInfo['credit'];
		};
		$_POST['oid'] = session('fansInfo.oid');
		$_POST['pid'] = I('get.id', 0, 'intval');
		$_POST['aid'] = session("fansInfo.aid");
		// 订单表中的钱币或积分
		$_POST['style'] = $productInfo["category"];
		$result = $this->updateData($_POST, 'group_orders');
		if($result !== false){
			if($_POST['style'] == 0){
				$result3 = $this->updateData(array('money' => $_POST['order_amount'], 'creat_time' => time(), 'status' => 1, 'type' => 3, 'typeid' => $result, 'oid' => session('fansInfo.oid'), 'remark' => '特惠团支付','aid'=>session("fansInfo.aid")), 'payment');
			}
			$info = $this->getInfo('o.name ,o.phone,o.address,o.addr,o.aid,a.name as area,o.id', 'whwx_owner o,whwx_area a', 'o.aid=a.id and o.id=' . session('fansInfo.oid'));
			$_POST['name'] = $info['name'];
			$_POST['phone'] = $info['phone'];
// 		    判断地址是否存在,不存在读取addr字段
			if(empty($info['address'])){
// 				$info['addr'] = array_reverse(explode('-', $info['addr']));
// 				if(empty($info["addr"][3])){
// 					$_POST["address"]=$info["area"].$info["addr"][2]."栋".$info["addr"][1]."单元".$info["addr"][0].'室';
// 				}else{
// 					$_POST["address"]=$info["area"].$info["addr"][3]."区".$info["addr"][2]."栋".$info["addr"][1]."单元".$info["addr"][0].'室';
// 				}
                $_POST['address']=session("fansInfo.address");
			}else{
				$_POST['address'] = $info['address'];
			}
			if($_POST){
				$_POST["orderid"] = $result;
				$result2 = $this->updateData($_POST, 'logistics');
			}
 			M('group_product')->where('id=' . I('get.id', 0, 'intval'))->setDec('num', I('get.num', 0, 'intval'));
 			$this->ajaxReturn(array('status' => 1, 'id' => $result, 'info' => '操作成功'));
 			//$productInfo = $this->getInfo('present_price, limit_num,category,credit,status', "group_product", 'id=' . I('get.id', 0, 'intval'));
			//if($productInfo['limit_num'] >= I('get.num', 0, 'intval')){
 			//}
		}else{
			$this->ajaxReturn(array('status' => -1, 'info' => '操作失败'));
		}
	}
}