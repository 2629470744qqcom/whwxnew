<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 商家分类
 * huying Dec 23, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class MerchantTypeController extends AdminController{

	/**
	 * 分类列表
	 * huying Dec 28, 2015
	 */
	public function index(){
		$list = $this->getList('id,name,sort,status', 'merchant_type', '1 = 1', 'sort asc', true);
		$this->assign('list', $list);
		$this->display();
	}

	/**
	 * 添加/修改分类
	 * huying Dec 28, 2015
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'merchant_type');
			$this->returnResult($result, null, U('MerchantType/index'));
		}
	}

	/**
	 * 查询详细信息
	 * huying Dec 28, 2015
	 */
	public function detail(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'merchant_type', 2);
			$this->returnResult($result, null, U('MerchantType/index'));
		}else{
			$data = $this->getInfo('id,pic,name,desc', 'merchant_type', 'id=' . I('get.id', 0, 'intval'));
			$this->ajaxReturn($data);
		}
	}

	/**
	 * 删除分类
	 * huying Dec 28, 2015
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'merchant_type');
		$this->returnResult($result);
	}
}