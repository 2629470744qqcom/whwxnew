<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class GroupProductController extends AdminController{
	public function index(){
		$where = '1=1';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and id=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.name', '', 'strval') == '' ? '' : ' and name like "%' . I('get.name', '', 'strval') . '%"';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and status=' . I('get.status', -1, 'intval');
		$where .= I('get.category', -1, 'intval') == -1 ? '' : ' and category=' . I('get.category', -1, 'intval');
		$list = $this->getList('id,name,num,present_price,original_price,sort,status,category,credit', 'group_product', $where, 'id desc', true);
		$this->assign('list', $list);
		$this->display();
	}
	
	/**
	 * 添加特惠团产品
	 * yaoyingli 2015年12月24日
	 */
	public function add(){
		if(IS_POST){
			$_POST['pics'] = implode(',', $_POST['pic']);
			$_POST['limit_num']=$_POST['num'];
			$result = $this->updateData($_POST, 'group_product');
			$this->returnResult($result);
		}else{
			$this->display();
		}
	}
	/**
	 * 修改特惠团产品
	 * yaoyingli 2015年12月24日
	 */
	public function edit(){
		if(IS_POST){
			$_POST['pics'] = implode(',', $_POST['pic']);
			$result = $this->updateData($_POST, 'group_product', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,name,num,present_price,original_price,sort,status,pics,content,category,credit,label', 'group_product', 'id=' . $_GET['id']);
			$info['pics'] = explode(',', $info['pics']);
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除特惠团产品
	 * yaoyingli 2015年12月24日
	 */
	public function del(){
		$result = $this->deleteData('id=' . $_GET['id'], 'group_product');
		$this->returnResult($result);
	}
	/**
	 * 特惠团产品状态
	 * yaoyingli 2015年2月23日
	 */
	public function setStatus(){
		$status = I('get.status', 0, 'intval');
		$result = M('group_product')->where('id=' . I('get.id', 0, 'intval'))->setField('status',$status );
		$this->returnResult($result);
	}
}