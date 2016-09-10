<?php
namespace Wap\Controller;
use Common\Controller\WapController;

class TourMerchantController extends WapController{
	private $manInfo;

	protected function _initialize(){
		parent::_initialize();
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}

	public function index(){
		if($_GET['type'] == 1){
			$list = $this->getList('id,name,sale,status,if(locate(",", pics), left(pics, locate(",", pics) - 1), pics) pics', 'tour_line', 'mid=' . session('fansInfo.oid'), 'id desc', true);
		}else{
			$pid = I('get.id', 0, 'intval');
			$where = 'o.pid=l.id and o.mid=' . session('fansInfo.oid');
			$where .= $pid > 0 ? ' and o.pid=' . $pid : '';
			$where .= I('get.dates') ? ' and o.dates="' . I('get.dates') . '"' : '';
			$list = $this->getList('o.id,o.pname,o.pnum,o.pprice,o.money,o.dates,o.status,if(locate(",", pics), left(pics, locate(",", pics) - 1), pics) pics,l.dates ldates', 'whwx_tour_orders o,whwx_tour_line l', $where, 'times desc', true);
			if($pid > 0){
				$dates = json_encode(explode(',', $list[0]['ldates']));
				$this->assign('dates', $dates);
			}
		}
		$this->assign('list', $list);
		$this->display();
	}

	public function ordersInfo(){
		if(IS_POST){
			$result = M('tour_orders')->where(array('id' => I('post.id')))->save(array('status' => I('post.status'), 'use_time' => time()));
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('*', 'tour_orders', array('id' => I('get.id')));
			$lineInfo = M('tour_line')->field('if(locate(",", pics), left(pics, locate(",", pics) - 1), pics) pics')->where(array('id' => $info['pid']))->find();
			$info['pics'] = $lineInfo['pics'];
			$info['user'] = json_decode($info['user'], true);
			$this->assign('info', $info);
			$this->display();
		}
	}

	public function lineInfo(){
		if(IS_POST){
			$result = M('tour_line')->where(array('id' => I('post.id')))->setField('status', I('post.status'));
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('*', 'tour_line', array('id' => I('get.id')));
			$info['pics'] = explode(',', $info['pics']);
			$this->assign('info', $info);
			$this->display();
		}
	}
}