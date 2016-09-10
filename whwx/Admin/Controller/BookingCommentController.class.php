<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 预约服务列表和搜索功能
 * yaoyongli 2015年12月29日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class BookingCommentController extends AdminController{
	public function index(){
		$_POST['times'] = time();
		// $type = I('get.type', 4, 'intval');
		$where = 'c.oid = o.id and c.typeid = s.id and s.cate_id =t.id and c.type = 4 and o.aid = a.id and a.id in(0,'.session('ruleInfo.aids').')';
		// 		供应商筛选
		$where .= I('get.supplierid') && I('get.supplierid') > 0 ? ' and s.id=' . I('get.supplierid', 'intval') : '';
		// 		类型筛选
		$where .= I('get.typeid') && I('get.typeid') > 0 ? ' and t.id=' . I('get.typeid', 'intval') : '';
		$where .= I('get.status', -1) > -1 ? ' and c.status = ' . I('get.status') : '';
		$list = $this->getList('c.id,c.oid,c.status,c.score,c.times,c.desc,c.pics,c.type,c.typeid,c.rid,o.id,o.name,o.phone,s.name as supplier,s.phone as tel', 'whwx_comment c,whwx_owner o, whwx_area as a,whwx_booking_supplier s,whwx_booking_type t', $where, 'times desc', true);
		$this->assign('list', $list);
		$supplierList = $this->getList('id ,name', 'booking_supplier','status =1');
		$this->assign('supplierList', $supplierList);
		$typeList = $this->getList('id ,name', 'booking_type','status =1');
		$this->assign('typeList', $typeList);
		$this->display();
	}

	/**
	 * 预约详情
	 * yaoyongli 2016年1月14日
	 */
	public function detail(){
		if(IS_POST){
			if(empty($_POST['content'])){
				$this->ajaxReturn(array('status' => -1, 'info' => '请输入反馈内容'));
			}
			$result = \Common\Api\CommonApi::addFollow(session('aid'), session('ainfo.name'), session('ainfo.tel'), 4, I('post.id', 0, 'intval'), $_POST['content']);
			if($result > 0){
				$this->ajaxReturn(array('status' => $result, 'info' => '添加成功'));
			}else{
				$this->ajaxReturn(array('status' => -1, 'info' => '添加失败'));
			}
		}else{
			$info = $this->getInfo('b.cate_id,b.desc,b.submit_time,s.name as title,s.phone as tel,s.address,t.name as tname,t.id as tid,b.id as id,o.name,o.phone,b.status', 'whwx_booking as b,whwx_booking_type as t,whwx_owner o,whwx_booking_supplier s', 'b.oid =o.id and s.id=b.sid and b.cate_id = t.id and b.id=' . I('get.id', 0, 'intval'));
			$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type = 4 and rid = ' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			// 订单跟踪
			$list = $this->getList('id,oid,oname,otel,content,times', 'follow', 'type = 4 and typeid=' . I('get.id', 0, 'intval'), 'times asc');
			$this->assign('list', $list);
			$this->display();
		}
	}
}