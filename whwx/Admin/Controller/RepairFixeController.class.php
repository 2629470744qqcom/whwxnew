<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 有偿服务
 * huying Dec 25, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class RepairFixeController extends AdminController{
	/**
	 * 有偿服务列表
	 * huying Dec 25, 2015
	 */
	public function index(){
		$where = '1 = 1';
		$where .= I('get.name') && I('get.name') != '' ? ' and name like "%' . I('get.name') . '%"' : '';
		$where .= I('get.status', -1) > -1 ? ' and status =' . I('get.status') : '';
		$list = $this->getList('id,name,price,material,sort,status', 'repair_fixe', $where, 'id desc',true);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 添加
	 * huying Dec 25, 2015
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'repair_fixe');
			$this->returnResult($result);
		}else{
			$this->display();
		}
	}
	/**
	 * 修改
	 * huying Dec 25, 2015
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'repair_fixe', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,name,price,sort,status,desc,material', 'repair_fixe', 'id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除
	 * huying Dec 25, 2015
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'repair_fixe');
		$this->returnResult($result);
	}
}