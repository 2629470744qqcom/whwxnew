<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class UnitController extends AdminController{

	public function index(){
		$where = '1=1';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and id=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.name', '', 'strval') == '' ? '' : ' and name like "%' . I('get.name', '', 'strval') . '%"';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and status=' . I('get.status', -1, 'intval');
		$list = $this->getList('id,name,status,info', 'unit', $where, 'id asc', true);
		$this->assign('list', $list);
		$this->display();
	}

	/**
	 * 添加单元
	 * yaoyingli 2015年12月29日
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'unit');
			$this->returnResult($result);
		}else{
			$this->display();
		}
	}

	/**
	 * 修改单元
	 * yaoyingli 2015年12月29日
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'unit', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,name,status,info', 'unit', 'id=' . $_GET['id']);
			$this->assign('info', $info);
			$this->display('add');
		}
	}

	/**
	 * 删除单元
	 * yaoyingli 2015年12月29日
	 */
	public function del(){
		$result = $this->deleteData('id=' . $_GET['id'], 'unit');
		$this->returnResult($result);
	}

	/**
	 * 根据楼栋号获取单元名
	 * huying Dec 28, 2015
	 */
	public function getUnitByBid(){
		$units = M('block')->where('id = ' . I('get.bid', 0, 'intval'))->getField('units');
		$list = $this->getList('id,name', 'unit', 'id in (' . trim($units, ',') . ')', 'id asc');
		$this->ajaxReturn($list);
	}
}