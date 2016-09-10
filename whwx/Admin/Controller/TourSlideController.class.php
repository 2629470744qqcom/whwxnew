<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 幻灯片
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class TourSlideController extends AdminController{

	/**
	 * 幻灯片
	 * huying Dec 28, 2015
	 */
	public function index(){
		$where = '1=1';
		$where .= I('get.type', -1, 'intval') > -1 ? ' and type=' . I('get.type') : '';
		$where .= I('get.status', -1) > -1 ? ' and status=' . I('get.status') : '';
		$list = $this->getList('*', 'tour_slide', $where, 'id desc', true);
		$this->assign('list', $list);
		$clist = $this->getList('id,name', 'tour_classify');
		$this->assign('clist', $clist);
		$this->display();
	}

	/**
	 * 添加幻灯片
	 * huying Dec 28, 2015
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'tour_slide');
			$this->returnResult($result);
		}else{
			$clist = $this->getList('id,name', 'tour_classify');
			$this->assign('clist', $clist);
			$this->display();
		}
	}

	/**
	 * 修改幻灯片
	 * huying Dec 28, 2015
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'tour_slide', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('*', 'tour_slide', 'id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$clist = $this->getList('id,name', 'tour_classify');
			$this->assign('clist', $clist);
			$this->display('add');
		}
	}

	/**
	 * 删除幻灯片
	 * huying Dec 28, 2015
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'tour_slide');
		$this->returnResult($result);
	}
}