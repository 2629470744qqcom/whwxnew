<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 首页板块管理
 * zhangxinhe Jan 5, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class PlateController extends AdminController{
	/**
	 * 板块列表
	 * zhangxinhe Jan 5, 2016
	 */
	public function index(){
		$list = $this->getList('id,name,type,pic,link,sort,status', 'plate', null, 'sort desc,id desc', true);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 添加板块
	 * zhangxinhe Jan 5, 2016
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'plate');
			$this->returnResult($result);
		}else{
			$this->display();
		}
	}
	/**
	 * 修改板块
	 * zhangxinhe Jan 5, 2016
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'plate', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,name,type,pic,link,sort,status,look', 'plate', 'id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除板块
	 * zhangxinhe Jan 5, 2016
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'plate');
		$this->returnResult($result);
	}
}