<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class TourOrdersController extends AdminController{

	public function index(){
		$where = 'o.aid=a.id and o.aid in (0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.aid', 0, 'intval') > 0 ? ' and o.aid=' . I('get.aid', -1, 'intval') : '';
		$where .= I('get.mid', 0, 'intval') > 0 ? ' and o.mid=' . I('get.mid', -1, 'intval') : '';
		$where .= I('get.status', -1, 'intval') > -1 ? ' and o.status=' . I('get.status', -1, 'intval') : ' and o.status<6';
		$where .= I('get.pay_type') ? ' and o.pay_type="' . I('get.pay_type') . '"' : '';
		$where .= I('get.dates') ? ' and o.dates="' . I('get.dates') . '"' : '';
		$where .= I('get.name') ? ' and o.pname like "%' . I('get.name') . '%"' : '';
		$where .= I('get.start_time') ? ' and o.times>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and o.times<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		$list = $this->getList('o.*,a.name area', 'whwx_tour_orders o,whwx_area a', $where, 'times desc', true);
		foreach($list as $k => $v){
			$list[$k]['user'] = json_decode($v['user'], true);
		}
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$merchantList = $this->getList('id,name', 'tour_merchant');
		$this->assign('merchantList', $merchantList);
		$this->display();
	}

	public function ajax(){
		$type = I('get.type', 0, 'intval');
		$id = I('get.id');
		if($type == 1){
			$result = M('tour_orders')->where(array('id' => $id))->save(array('use_time' => time(), 'status' => 3));
		}elseif($type == 4){
			$status = M('tour_orders')->where(array('id' => $id))->getField('status');
			if($status == 1){
				$result = M('tour_orders')->where(array('id' => $id))->save(array('pay_type' => 'offline', 'pay_time' => time(), 'status' => 2));
			}else{
				$this->returnResult(false, array('', '当前状态不可支付'));
			}
		}elseif($type == 5){
			$status = I('get.status', 0, 'intval');
			$result = M('tour_orders')->where(array('id' => $id))->setField('comment_status', $status == 1 ? 0 : 1);
		}else{
			$result = M('tour_orders')->where(array('id' => $id))->setField('status', $type == 2 ? 4 : 6);
		}
		$this->returnResult($result);
	}

	public function custom(){
		$where = '1=1';
		$where .= I('get.start_time') ? ' and times>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and times<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		$where .= I('get.status', 0, 'intval') > 0 ? ' and status=' . I('get.status') : '';
		if($_GET['type'] == 'export'){
			$list = $this->getList('name,phone,from_unixtime(times),origin,target,date,day,people,money,desc,remark', 'tour_custom', $where, 'times desc');
			$title = array('姓名', '手机', '报名时间', '出发地', '目的地', '出发日期', '游玩天数', '游玩人数', '人均预算', '其他要求', '备注');
			array_unshift($list, $title);
			\Common\Api\PHPExcelApi::exportExcel($list, '旅游个人定制订单', true);
		}else{
			$list = $this->getList('*', 'tour_custom', $where, 'times desc', true);
			$this->assign('list', $list);
			$this->display();
		}
	}

	public function config(){
		if(IS_POST){
			$result = F('tour_city', $_POST);
			$this->returnResult($result, null, U('TourOrders/custom'));
		}else{
			$info = F('tour_city');
			$this->assign('info', $info);
			$this->display();
		}
	}

	public function remark(){
		$result = M('tour_custom')->where(array('id' => I('post.id')))->setField('remark', I('post.remark'));
		$this->returnResult($result);
	}

	public function export(){
		// $where = 'o.aid=a.id and o.aid in (0,' . session('ruleInfo.aids') . ')';
		$where = 'o.aid in (0,' . session('ruleInfo.aids') . ')';

		$where .= I('get.aid', 0, 'intval') > 0 ? ' and o.aid=' . I('get.aid', -1, 'intval') : '';
		$where .= I('get.mid', 0, 'intval') > 0 ? ' and o.mid=' . I('get.mid', -1, 'intval') : '';
		$where .= I('get.status', -1, 'intval') > -1 ? ' and o.status=' . I('get.status', -1, 'intval') : ' and o.status<5';
		$where .= I('get.pay_type') ? ' and o.pay_type="' . I('get.pay_type') . '"' : '';
		$where .= I('get.dates') ? ' and o.dates="' . I('get.dates') . '"' : '';
		$where .= I('get.name') ? ' and o.pname like "%' . I('get.name') . '%"' : '';
		$where .= I('get.start_time') ? ' and o.times>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and o.times<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		
		// $list = $this->getList('concat("\t", o.id) id,a.name area,o.times,o.pname,o.pnum,o.pprice,o.money,o.status,o.pay_type,o.pay_id,o.pay_time,o.use_time,o.oid,o.phone,o.dates,o.user,o.comment,o.comment_score,o.comment_time', 'whwx_tour_orders o,whwx_area a', $where, 'times desc', true);

		$list = M('tour_orders')->alias('o')->join('whwx_area a on o.aid = a.id')->where($where)->order('times desc')->field('concat("\t", o.id) id,a.name area,o.times,o.pname,o.pnum,o.pprice,o.money,o.status,o.pay_type,o.pay_id,o.pay_time,o.use_time,o.oid,o.phone,o.dates,o.user,o.comment,o.comment_score,o.comment_time')->select();

		foreach($list as $k => $v){
			$list[$k]['times'] = date('Y-m-d H:i:s', $v['times']);
			$list[$k]['pay_time'] = $v['pay_time'] ? date('Y-m-d H:i:s', $v['pay_time']) : '';
			$list[$k]['use_time'] = $v['use_time'] ? date('Y-m-d H:i:s', $v['use_time']) : '';
			$list[$k]['comment_time'] = $v['comment_time'] ? date('Y-m-d H:i:s', $v['comment_time']) : '';
			$list[$k]['pay_type'] = $v['pay_type'] == 'weipay' ? '微信支付' : '线下支付';
			$user = json_decode($v['user'], true);
			$list[$k]['oid'] = $user[0]['name'];
			foreach($user as $value){
				$user_str .= '姓名：' . $value['name'] . ' 身份证：' . $value['idcard'] . "\n";
			}
			$list[$k]['user'] = $user_str;
			switch($v['status']){
				case 1: $status = '已下单'; break;
				case 2: $status = '已支付'; break;
				case 3: $status = '已发团'; break;
				case 4: $status = '已完成'; break;
				case 5: $status = '已取消'; break;
				case 6: $status = '已删除'; break;
			}
			$list[$k]['status'] = $status;

			$user_str = '';
		}
		
		$title = array('订单号', '小区', '下单时间', '线路名称', '人数', '单价', '总价', '状态', '支付类型', '回执ID', '支付时间', '发团时间', '联系人', '手机号', '出行日期', '游客信息', '评论内容', '评分', '评论时间');
		array_unshift($list, $title);
		\Common\Api\PHPExcelApi::exportExcel($list, '旅游订单', true);
	}


	public function getRemark () {
		$id = I("get.id", 0, 'intval');

		$content = M('tour_orders')->where("id=".$id)->getField("remark");

		$arr = array();
		if ($content) {
			foreach (explode('^^^', $content) as $value) {
				$t = explode('@@', $value);
				
				$tmp['name'] = $t[0];
				$tmp['date'] = date('Y-m-d H:i:s', $t[1]);
				$tmp['content'] = $t[2];

				$arr[] = $tmp;
			}
		}

		$this->ajaxReturn($arr);
	}

	public function setRemark () {
		$id = I("get.id", 0, 'intval');
		$content = I('get.content', '', 'strval');

		$oldContent = M('tour_orders')->where('id='.$id)->getField('remark');
		$str = session('aname') . "@@" . time() . "@@" . $content;

		if ($oldContent) {
			$newContent = join('^^^', array($oldContent, $str));
		} else {
			$newContent = $str;
		}

		$status = M('tour_orders')->where("id=".$id)->setField('remark', $newContent);
		
		return $status;
	}
}