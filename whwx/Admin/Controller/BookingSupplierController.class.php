<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class BookingSupplierController extends AdminController{
	public function index(){
		$where = 's.cate_id=t.id';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and t.id=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.typeid') && I('get.typeid') > 0 ? ' and t.id=' . I('get.typeid', 'intval') : '';
		$where .= I('get.name', '', 'strval') == '' ? '' : ' and s.name like "%' . I('get.name', '', 'strval') . '%"';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and s.status=' . I('get.status', -1, 'intval');
		$list = $this->getList('s.id,s.cate_id,s.name,s.sort,s.phone,s.address,s.score,s.desc,s.status,s.pic,t.name as type', 'whwx_booking_supplier s,whwx_booking_type t', $where, 'sort asc', true);
		$this->assign('list', $list);
		$typeList = $this->getList('id ,name', 'booking_type','status =1');
		$this->assign('typeList', $typeList);
		$this->display();
	}

	/**
	 * 添加供应商
	 * yaoyingli 2015年12月29日
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'booking_supplier');
			$this->returnResult($result);
		}else{
			$typeList = $this->getList('id,name as type', 'booking_type', 'status=1', 'id desc');
			$this->assign('typeList', $typeList);
			$this->display();
		}
	}
	/**
	 * 修改供应商
	 * yaoyingli 2015年12月29日
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'booking_supplier', 2);
			$this->returnResult($result);
		}else{
			$typeList = $this->getList('id,name as type', 'booking_type', 'status=1', 'id desc');
			$this->assign('typeList', $typeList);
			$info = $this->getInfo('id,cate_id,name,sort,phone,address,desc,service,price,status,pic', 'booking_supplier', 'id=' . $_GET['id']);
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除供应商
	 * yaoyingli 2015年12月29日
	 */
	public function del(){
		$result = $this->deleteData('id=' . $_GET['id'], 'booking_supplier');
		$this->returnResult($result);
// 		$info=$this->getInfo('', 'booking_supplier', 'id=' . $_GET['id']);
		$result1 = $this->deleteData('rid=' . $_GET['id'], 'comment');
		$this->returnResult($result1);
	}
}