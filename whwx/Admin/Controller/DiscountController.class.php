<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 特惠团列表和搜索功能
 * yaoyongli 2015年12月24日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class DiscountController extends AdminController{
	public function index(){
		$where = '1=1';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and id=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.name', '', 'strval') == '' ? '' : ' and name like "%' . I('get.name', '', 'strval') . '%"';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and status=' . I('get.status', -1, 'intval');
		$list = $this->getList('id,name,num,present_price,original_price,start_time,last_time,sort,status', 'discount', $where, 'sort asc', true);
		//dump($list);
		$this->assign('list', $list);
		$this->display();
	}
	
	/**
	 * 添加特惠团
	 * yaoyingli 2015年12月24日
	 */
	public function add(){
		if(IS_POST){
			$_POST['start_time'] = strtotime($_POST['start_time']);
			$_POST['last_time'] = strtotime($_POST['last_time']);
			$result = $this->updateData($_POST, 'discount');
			$this->returnResult($result);
		}else{
			$this->display();
		}
	}
	/**
	 * 修改特惠团
	 * yaoyingli 2015年12月24日
	 */
	public function edit(){
		if(IS_POST){
			$_POST['start_time'] = strtotime($_POST['start_time']);
			$_POST['last_time'] = strtotime($_POST['last_time']);
			$result = $this->updateData($_POST, 'discount', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,name,num,present_price,original_price,sort,status,pic,start_time,last_time,content', 'discount', 'id=' . $_GET['id']);
			//dump($info);
//     		$info['start_time'] =  date("Y-m-d ",$info['start_time']);
//  			$info['last_time'] =  date("Y-m-d ",$info['last_time']);
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除特惠团
	 * yaoyingli 2015年12月24日
	 */
	public function del(){
		$result = $this->deleteData('id=' . $_GET['id'], 'discount');
		$this->returnResult($result);
	}
	/**
	 * 设置特惠团状态
	 * yaoyingli 2015年12月24日
	 */
	public function setStatus(){
		$status = empty($_GET['status']) ? 0 : I('get.status', 0, 'intval');
		$result = M("discount")->where('id='. $_GET['id'])->setField('status', $status);
		$this->returnResult($result);
	}
	/**
	 * 设置特惠团排序
	 * yaoyingli 2015年12月24日
	 */
	public function setSort(){
		$sort = empty($_GET['sort']) ? null : I('get.sort', null, 'intval');
		$result = M('discount')->where('id=' . $_GET['id'])->setField('sort', $sort);
		$this->returnResult($result);
	}
}