<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class TourLineController extends AdminController{

	/**
	 * 旅游线路管理
	 * yaoyingli 2015年12月24日
	 */
	public function index(){
		$where = 'c.id=l.cid and m.id=l.mid';
		$where .= I('get.mid', 0, 'intval') > 0 ? ' and mid=' . I('get.mid', 0, 'intval') : '';
		$where .= I('get.cid', 0, 'intval') > 0 ? ' and cid=' . I('get.cid', 0, 'intval') : '';
		$where .= I('get.name', '', 'strval') == '' ? '' : ' and l.name like "%' . I('get.name', '', 'strval') . '%"';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and status=' . I('get.status', -1, 'intval');
		$list = $this->getList('l.*,c.name cname,m.name mname', 'whwx_tour_line l,whwx_tour_classify c,whwx_tour_merchant m', $where, 'id desc', true);
		$this->assign('list', $list);
		$clist = $this->getList('id,name', 'tour_classify', 'status=1', 'sort desc');
		$this->assign('clist', $clist);
		$mlist = $this->getList('id,name', 'tour_merchant');
		$this->assign('mlist', $mlist);
		$this->display();
	}

	/**
	 * 添加
	 * yaoyingli 2015年12月24日
	 */
	public function add(){
		if(IS_POST){
			$_POST['pics'] = implode(',', $_POST['pic']);
			$result = $this->updateData($_POST, 'tour_line');
			$this->returnResult($result);
		}else{
			$clist = $this->getList('id,name', 'tour_classify', 'status=1', 'sort desc');
			$this->assign('clist', $clist);
			$mlist = $this->getList('id,name', 'tour_merchant');
			$this->assign('mlist', $mlist);
			$this->display();
		}
	}

	/**
	 * 修改
	 * yaoyingli 2015年12月24日
	 */
	public function edit(){
		if(IS_POST){
			$_POST['index'] = $_POST['index'] == 1 ? 1 : 0;
			$_POST['self'] = $_POST['self'] == 1 ? 1 : 0;
			$_POST['recom'] = $_POST['recom'] == 1 ? 1 : 0;
			$_POST['jing'] = $_POST['jing'] == 1 ? 1 : 0;
			$_POST['hui'] = $_POST['hui'] == 1 ? 1 : 0;
			$_POST['re'] = $_POST['re'] == 1 ? 1 : 0;
			$_POST['pics'] = implode(',', $_POST['pic']);
			$result = $this->updateData($_POST, 'tour_line', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('*', 'tour_line', 'id=' . $_GET['id']);
			$info['pics'] = explode(',', $info['pics']);
			$this->assign('info', $info);
			$clist = $this->getList('id,name', 'tour_classify', 'status=1', 'sort desc');
			$this->assign('clist', $clist);
			$mlist = $this->getList('id,name', 'tour_merchant');
			$this->assign('mlist', $mlist);
			$this->display('add');
		}
	}

	/**
	 * 删除
	 * yaoyingli 2015年12月24日
	 */
	public function del(){
		$result = $this->deleteData('id=' . $_GET['id'], 'tour_line');
		$this->returnResult($result);
	}
}