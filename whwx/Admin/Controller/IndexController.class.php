<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 后台管理
 * huying Dec 23, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class IndexController extends AdminController{
	public function index(){
		// 获取投诉建议
		$suggestList = $this->getList('id,desc,times', 'complaint', 'status = 1', 'times desc', true, 2);
		$this->assign('suggestList', $suggestList);
		// 统计总数
		$count['repair'] = M('repair')->where('aid in (0,'.session("ruleInfo.aids").')')->count();
		$count['rental'] = M('rental_service')->where('aid in (0,'.session("ruleInfo.aids").')')->count();
		$count['wxmsg'] = M('wxmsg')->count();
		$count['owner'] = M('owner')->where('aid in (0,'.session("ruleInfo.aids").')')->where('status =1')->count();
		$this->assign('count', $count);
		//投诉建议、预约、特惠团订单
		$where = ' and a.id in(0,'.session("ruleInfo.aids").')';
		$complaintList = M()->table('whwx_complaint as c, whwx_owner as o,whwx_area as a')->field('c.id,c.desc,c.times,c.status')->where('c.status=0 and c.aid = a.id and c.oid=o.id' . $where)->order('c.times desc')->limit(10)->select();
		$this->assign('complaintList', $complaintList); 
		$bookingList = M()->table('whwx_booking as b, whwx_owner as o,whwx_area as a')->field('b.id,b.name,b.phone,b.day,b.hour,b.desc')->where('b.status=1 and b.aid = a.id and b.oid=o.id' . $where)->order('b.submit_time desc')->limit(5)->select();
		$this->assign('bookingList', $bookingList);
		$ordersList = M()->table('whwx_group_orders s,whwx_group_product p')->field('s.order_amount,s.single_time,s.status,s.total,p.name')->where('s.status=2 and s.pid=p.id and aid in(0,'.session("ruleInfo.aids").')')->order('s.single_time desc')->limit(5)->select();
		$this->assign('ordersList', $ordersList);
		//统计相关
		$countWhere = 'aid=0 and times<' . date('Ymd') . ' and times>=' . date('Ymd', time() - 7 * 24 * 3600);
		$repair = M('count_repair')->field('creat_num,complate_num,handle_num,times')->where($countWhere)->order('times desc')->select();
		$this->assign('repair', $repair);
		$score = M('count_score')->field('sum(num) num,score')->where($countWhere)->group('score')->select();
		foreach($score as $v){
			$scoreData['num' . $v['score']] = $v['num'];
		}
		$this->assign('score', $scoreData);
		$this->display();
	}
}