<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 分类管理
 * zhangxinhe Jan 5, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class TourClassifyController extends AdminController{

	/**
	 * 分类列表
	 * zhangxinhe Jan 5, 2016
	 */
	public function index(){
		$list = $this->getList('*', 'tour_classify', null, 'sort desc,id desc', true);
		$this->assign('list', $list);
		$this->display();
	}

	/**
	 * 添加分类
	 * zhangxinhe Jan 5, 2016
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'tour_classify');
			$this->returnResult($result);
		}else{
			$this->display();
		}
	}

	/**
	 * 修改分类
	 * zhangxinhe Jan 5, 2016
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'tour_classify', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('*', 'tour_classify', 'id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$this->display('add');
		}
	}

	/**
	 * 删除分类
	 * zhangxinhe Jan 5, 2016
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'tour_classify');
		$this->returnResult($result);
	}
}