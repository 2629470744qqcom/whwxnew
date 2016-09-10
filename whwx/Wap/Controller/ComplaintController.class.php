<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 投诉建议
 * huying Feb 20, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ComplaintController extends WapController{
	/**
	 * 投诉建议
	 * huying Feb 20, 2016
	 */
	public function index(){
		$list = $this->getList('id,desc,times,status', 'complaint', 'oid='.session('fansInfo.oid'), 'times desc',true);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 详细情况
	 * huying Feb 20, 2016
	 */
	public function detail(){
		M('owner_notice')->where('type = 5 and typeid='.I('get.id',0,'intval'))->setField('status', 0);
		$info = $this->getInfo('id,desc,pics,times,feedback,feedback_pic,deal_time,status', 'complaint', 'id='.I('get.id',0,'intval'));
		if(!empty($info['pics'])){
			$info['pics'] = explode(',', $info['pics']);
		}
		if(!empty($info['feedback_pic'])){
			$info['feedback_pic'] = explode(',', $info['feedback_pic']);
		}
		if($info['status'] >= 2){
			$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type = 5 and rid = ' . I('get.id', 0, 'intval'));
		}
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 评论
	 * huying Mar 5, 2016
	 */
	public function comment(){
		$owner = $this->getInfo('id,bid,aid', 'owner', 'id='.session('fansInfo.oid'));
		$service = $this->getInfo('s.id', 'service as s', 's.aid = '.$owner['aid'].' and s.bids like "%,'.$owner['bid'].',%"');
		$result = $this->updateData(array('oid' => session('fansInfo.oid'), 'times' => time(), 'type' => 5, 'typeid' => $service['id'], 'score' => I('post.score', 0, 'intval'), 'desc' => $_POST['desc'], 'rid' => I('post.id', 0, 'intval'), 'status' => 1, 'aid' => session('fansInfo.aid')), 'comment');		if($result > 0){
			M('complaint')->where('id=' . I('post.id', 0, 'intval'))->setField('status', 2);
			//赠送积分
			$point = C('score_point');
			if($point > 0){
				$this->changePoint(session('fansInfo.oid'), $point, '投诉建议评价', 10, I('post.id', 0, 'intval'));
			}
			$this->ajaxReturn(array('status' => 1, 'info' => '评价成功'));
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '评价失败'));
	}
}