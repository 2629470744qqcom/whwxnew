<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 特惠团列表和搜索功能
 * yaoyongli 2015年12月29日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class GroupCommentController extends AdminController{
	public function index(){
		$_POST['times'] = time();
		$where = 'c.oid = o.id and c.type = 3 and c.typeid = g.id and  g.id=go.pid and c.rid=go.id and  c.aid in(0,'.session('ruleInfo.aids').')';
		$list = $this->getList('c.id,c.oid,c.score,c.times,c.desc,c.pics,c.type,c.typeid,c.rid,o.id,o.name,c.status,o.phone,g.name as product', 'whwx_comment c,whwx_owner o,whwx_group_product as g,whwx_group_orders as go', $where, 'times desc', true);
		$this->assign('list', $list);
		$this->display();
	}
	
	/**
	 * 特惠团详情
	 * yaoyongli 2016年1月20日
	 */
	public function detail(){
		if(IS_AJAX){
			$info = $this->getInfo('p.id as id,s.pid,s.oid,p.content as texts,s.pay_time,o.name as owner,o.phone,p.pics,p.name as title,s.id as id,s.status', 'whwx_group_product as p, whwx_owner as o,whwx_group_orders s', 's.pid=p.id and s.oid = o.id and s.id=' . I('get.id', 0, 'intval'));
			if($info['status'] == 5){
				$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type = 3 and rid = ' . I('get.id', 0, 'intval'));
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
}