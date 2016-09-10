<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class AreaController extends AdminController{
	public function index(){
		$where = 'id in(0,'.session('ruleInfo.aids').')';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and id=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.name', '', 'strval') == '' ? '' : ' and name like "%' . I('get.name', '', 'strval') . '%"';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and status=' . I('get.status', -1, 'intval');		

		$list = $this->getList('id,name,sort,status,phone', 'area', $where, 'id desc', true);
		$this->assign('list', $list);
		$this->display();
	}
	
	/**
	 * 添加小区
	 * yaoyingli 2015年12月29日
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'area');
			$this->returnResult($result);
		}else{
			$this->display();
		}
	}
	/**
	 * 修改小区
	 * yaoyingli 2015年12月29日
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'area', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,name,decorate,desc,sort,status,phone', 'area', 'id=' . $_GET['id']);
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除小区
	 * yaoyingli 2015年12月29日
	 */
	public function del(){
		$result = $this->deleteData('id=' . $_GET['id'], 'area');
		$this->returnResult($result);
	}
	
}