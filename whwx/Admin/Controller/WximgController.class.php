<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 微信图文回复
 * zhangxinhe Dec 29, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WximgController extends AdminController{
	/**
	 * 图文列表
	 * zhangxinhe Dec 29, 2015
	 */
	public function index(){
		$list = $this->getList('id,keyword,title,link,times,ids', 'wximg', null, 'id desc',true);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 添加图文回复
	 * zhangxinhe Dec 29, 2015
	 */
	public function add(){
		if(IS_POST){
			$this->checkKeyword($_POST['keyword']);
			$_POST['times'] = time();
			$_POST['ids'] = implode(',', $_POST['ids']);
			$result = $this->updateData($_POST, 'wximg');
			if($result){
				$this->setKeyword('Img', $result, 1, $_POST['keyword']);
				$this->returnResult(true);
			}
			$this->returnResult(false);
		}else{
			$list = $this->getList('id,title', 'wximg', null, 'id desc');
			$this->assign('list', $list);
			$this->display();
		}
	}
	/**
	 * 修改图文回复
	 * zhangxinhe Dec 29, 2015
	 */
	public function edit(){
		if(IS_POST){
			$this->checkKeyword($_POST['keyword'], $_POST['id']);
			$_POST['ids'] = implode(',', $_POST['ids']);
			$result = $this->updateData($_POST, 'wximg', 2);
			if($result){
				$this->setKeyword('Img', $_POST['id'], 2, $_POST['keyword']);
				$this->returnResult(true);
			}
			$this->returnResult(false);
		}else{
			$id = I('get.id', 0, 'intval');
			$list = $this->getList('id,title', 'wximg', 'id<>' . $id, 'id desc');
			$this->assign('list', $list);
			$info = $this->getInfo('id,keyword,title,pic,desc,link,ids', 'wximg', 'id=' . $id);
			$info['ids'] = '#imgs' . implode(',#imgs', explode(',', $info['ids']));
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除图文回复
	 * zhangxinhe Dec 29, 2015
	 */
	public function del(){
		$id = I('get.id', 0, 'intval');
		$result = $this->deleteData('id=' . $id, 'wximg');
		if($result){
			$this->setKeyword('Img', $id, '3');
			$this->returnResult(true);
		}
		$this->returnResult(false);
	}
}