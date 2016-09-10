<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 论坛管理
 * huying Dec 23, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ForumPlateController extends AdminController{

	/**
	 * 分类
	 * huying Dec 26, 2015
	 */
	public function index(){
		$list = $this->getList('id,name,sort,status', 'forum_plate', '1 = 1', 'id desc', true);
		$this->assign('list', $list);
		$this->display();
	}

	/**
	 * 添加分类
	 * huying Dec 26, 2015
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'forum_plate');
			$this->returnResult($result, null, U('ForumPlate/index'));
		}
	}

	/**
	 * 删除分类
	 * huying Dec 26, 2015
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'forum_plate');
		$this->returnResult($result);
	}

	/**
	 * 获取分类的详细信息
	 * huying Dec 26, 2015
	 */
	public function cateDetail(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'forum_plate', 2);
			$this->returnResult($result, null, U('ForumPlate/index?p=' . $_POST['p']));
		}else{
			$data = $this->getInfo('id,name,desc', 'forum_plate', 'id=' . I('get.id', 0, 'intval'));
			$this->ajaxReturn($data);
		}
	}
}