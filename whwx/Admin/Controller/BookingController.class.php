<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 预约服务列表和搜索功能
 * yaoyongli 2015年12月26日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class BookingController extends AdminController{

	public function index(){
		$where = 'b.sid = s.id and s.cate_id = t.id and b.oid = o.id and a.id = o.aid and a.id in(0,'.session('ruleInfo.aids').')';
		if (I('get.type') == 2){
			$where .= ' and submit_time<' . (time() - 300) .' and b.status = 1';			
		}else{
			$where .= I('get.id', 0, 'intval') > 0 ? ' and b.id = '.I('get.id', 0, 'intval') : '';
			$where .= I('get.aid') && I('get.aid') > 0 ? ' and a.id=' . I('get.aid', 'intval') : '';
			$where .= I('get.supplierid') && I('get.supplierid') > 0 ? ' and s.id=' . I('get.supplierid', 'intval') : '';
			$where .= I('get.typeid') && I('get.typeid') > 0 ? ' and t.id=' . I('get.typeid', 'intval') : '';
			$where .= I('get.owner', '', 'strval') == '' ? '' : ' and o.name like "%' . I('get.owner', '', 'strval') . '%"';
			$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and b.status=' . I('get.status', -1, 'intval');
		}
		$list = $this->getList('b.oid,b.sid,b.submit_time,b.day,b.hour,b.cate_id,b.status,s.id,s.name as title,o.id,b.name,b.phone,b.id as id,o.name as owner, o.addr,a.name as area', 'whwx_booking b,whwx_booking_supplier s,whwx_owner o, whwx_area as a,whwx_booking_type t', $where, 'submit_time desc', true);
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$supplierList = $this->getList('id ,name', 'booking_supplier','status =1');
		$this->assign('supplierList', $supplierList);
		$typeList = $this->getList('id ,name', 'booking_type','status =1');
		$this->assign('typeList', $typeList);
		$this->display();
	}

	/**
	 * 删除预约服务
	 * yaoyingli 2015年12月26日
	 */
	public function del(){
		M('warn')->where('type=2 and typeid=' . I('get.id', 0, 'intval'))->setField('status', 0);
		$result = M('booking')->where('id=' . I('get.id', 0, 'intval'))->setField('status', 4);
		$this->returnResult($result);
	}

	/**
	 * 处理完成
	 * huying Mar 7, 2016
	 */
	public function deal(){
		$result = M('booking')->where('id=' . I('get.id', 0, 'intval'))->setField('status', 3);
		if($result !== false){
			M('warn')->where('type = 2 and typeid='.I('get.id', 0, 'intval').' and status = 1')->setField('status', 0);
			$info = $this->getInfo('o.aid,o.bid,o.id', 'whwx_owner as o,whwx_booking as b', 'b.oid=o.id and b.id=' . $_GET["id"]);
			$service = $this->getInfo('id,name', 'service', 'status = 1 and aid=' . $info['aid'] . ' and FIND_IN_SET(' . $info['bid'] . ', bids)');
			M('service_notice')->where('oid = ' . $service['id'] . ' and type = 2 and typeid=' . I('get.id', 0, 'intval'))->setField('status', 0);
		}
		$this->returnResult($result);
	}
	/**
	 * 导出订单
	 * huying Jan 28, 2016
	 */
	public function export(){
		$where = 'b.sid = s.id and b.oid = o.id and o.aid = a.id ';
		$where .= I('post.sid', 0, 'intval') > 0 ? ' and b.sid=' . I('post.sid', 0, 'intval') : '';
		$where .= I('post.aid', 0, 'intval') > 0 ? ' and o.aid=' . I('post.aid', 0, 'intval') : '';
		$where .= I('post.status', -1, 'intval') == -1 ? '' : ' and b.status=' . I('post.status', -1, 'intval');
		$start_time = strtotime($_POST['start_time']);
		$end_time = strtotime($_POST['end_time']) + 24 * 3600;
		$where .= ' and b.submit_time > ' . $start_time . ' and b.submit_time <' . $end_time;
		$data = M('booking as b, whwx_booking_supplier as s, whwx_owner as o,whwx_area as a')->
		field('b.id,o.name as owner,b.name,b.phone,b.status,date_format(from_unixtime(b.submit_time),"%Y-%m-%d %H:%i:%s") as submit_time,date_format(from_unixtime(b.day),"%Y-%m-%d %H:%i:%s") as day,b.desc,a.name as area,s.name as supplier')->
		where($where)->limit(1, 10000)->select();
		if($data){
			foreach ($data as $k => $v){
				$commentInfo = $this->getInfo('score,desc', 'comment', 'type = 4 and rid=' . $v['id']);
				$data[$k]['comment_score'] = $commentInfo['score'];
				$data[$k]['comment_desc'] = $commentInfo['desc'];
				switch($v['status']){
					case 1: $data[$k]['status'] = '未完成';break;
					case 2: $data[$k]['status'] = '已评论';break;
					case 3: $data[$k]['status'] = '已完成';break;
					case 4: $data[$k]['status'] = '已删除';break;
				}
			}
			$title = array('序号', '业主', '联系人', '联系方式', '状态', '提交时间', '预约时间', '预约内容', '小区', '服务商', '评分', '评价');
			array_unshift($data, $title);
			$file = \Common\Api\PHPExcelApi::exportExcel($data, $_POST['name'], false);
			$this->ajaxReturn(array('info' => '导出成功', 'status' => 1, 'url' => $file));
		}else{
			$this->error('没有数据');
		}
	}
}