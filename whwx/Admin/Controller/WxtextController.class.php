<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 微信文本回复
 * zhangxinhe Dec 29, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WxtextController extends AdminController{
	/**
	 * 文本列表
	 * zhangxinhe Dec 29, 2015
	 */
	public function index(){
		$list = $this->getList('id,keyword,content,times', 'wxtext', null, 'times desc', true);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 添加文本回复
	 * zhangxinhe Dec 29, 2015
	 */
	public function add(){
		if(IS_POST){
			$this->checkKeyword($_POST['keyword']);
			$_POST['times'] = time();
			$result = $this->updateData($_POST, 'wxtext');
			if($result){
				$this->setKeyword('Text', $result, 1, $_POST['keyword']);
				$this->returnResult(true);
			}
			$this->returnResult(false);
		}
	}
	/**
	 * 修改文本回复
	 * zhangxinhe Dec 29, 2015
	 */
	public function edit(){
		if(IS_POST){
			$this->checkKeyword($_POST['keyword'], $_POST['id']);
			$result = $this->updateData($_POST, 'wxtext', 2);
			if($result){
				$this->setKeyword('Text', $_POST['id'], 2, $_POST['keyword']);
				$this->returnResult(true);
			}
			$this->returnResult(false);
		}else{
			$info = $this->getInfo('keyword,content', 'wxtext', 'id=' . I('get.id', 0, 'intval'));
			$this->ajaxReturn($info);
		}
	}
	/**
	 * 删除文本回复
	 * zhangxinhe Dec 29, 2015
	 */
	public function del(){
		$id = I('get.id', 0, 'intval');
		$result = $this->deleteData('id=' . $id, 'wxtext');
		if($result){
			$this->setKeyword('Text', $id, '3');
			$this->returnResult(true);
		}
		$this->returnResult(false);
	}
}