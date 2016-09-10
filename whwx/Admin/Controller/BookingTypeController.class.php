<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class BookingTypeController extends AdminController{
	public function index(){
		$where = '1=1';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and id=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.name', '', 'strval') == '' ? '' : ' and name like "%' . I('get.name', '', 'strval') . '%"';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and status=' . I('get.status', -1, 'intval');
		$list = $this->getList('id,name,desc,sort,status', 'booking_type', $where, 'id desc', true);
		$this->assign('list', $list);
		$this->display();
	}
	
	/**
	 * 添加供应商类型
	 * yaoyingli 2015年12月29日
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'booking_type');
			$this->returnResult($result);
		}else{
			$this->display();
		}
	}
	/**
	 * 修改供应商类型
	 * yaoyingli 2015年12月29日
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'booking_type', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,name,desc,sort,status,pic', 'booking_type', 'id=' . $_GET['id']);
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除供应商类型
	 * yaoyingli 2015年12月29日
	 */
	public function del(){
		$result = $this->deleteData('id=' . $_GET['id'], 'booking_type');
		$this->returnResult($result);
	}
	
}