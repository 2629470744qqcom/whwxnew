<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 商家管理
 * zhangxinhe Jan 5, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class TourMerchantController extends AdminController{

	/**
	 * 商家列表
	 * zhangxinhe Jan 5, 2016
	 */
	public function index(){
		$list = $this->getList('*', 'tour_merchant', null, 'id desc', true);
		$this->assign('list', $list);
		$this->display();
	}

	/**
	 * 添加商家
	 * zhangxinhe Jan 5, 2016
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'tour_merchant');
			$this->returnResult($result);
		}else{
			$this->display();
		}
	}

	/**
	 * 修改商家
	 * zhangxinhe Jan 5, 2016
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'tour_merchant', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('*', 'tour_merchant', 'id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$this->display('add');
		}
	}

	/**
	 * 删除商家
	 * zhangxinhe Jan 5, 2016
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'tour_merchant');
		$this->returnResult($result);
	}

	/**
	 * 重置管理员
	 * zhangxinhe Jan 5, 2016
	 */
	public function reset(){
		$result = M('tour_merchant')->where('id=' . I('get.id', 0, 'intval'))->setField('fid', 0);
		if($result){
			M('wxfans')->where(array('type' => 5, 'oid' => I('get.id', 0, 'intval')))->save(array('type' => 0, 'oid' => 0));
		}
		$this->returnResult($result);
	}
}