<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 红黑榜
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class BlackListController extends AdminController {
	/**
	 * 红黑榜
	 * huying Dec 28, 2015
	 */
	public function index(){
		$where = '1 = 1';
		$where .= I('get.name')&&I('get.name') != '' ? ' and title like "%'.I('get.name').'%"' : '';
		$where .= I('get.status', -1) > -1 ? ' and status ='.I('get.status') : '';
		$where .= I('get.type', -1) > -1 ? ' and type ='.I('get.type') : '';
		$list = $this->getList('id,title,sort,status,type,zan', 'black_list', $where, 'id desc',true);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 添加红黑榜
	 * huying Dec 28, 2015
	 */
	public function add(){
		if(IS_POST){
			$_POST['day'] = strtotime($_POST['day']);
			$result = $this->updateData($_POST, 'black_list');
			$this->returnResult($result);
		}else{
// 			$areaList = $this->getAreaList();
// 			$this->assign('areaList', $areaList);
			$this->display();
		}
	}
	/**
	 * 修改红黑榜
	 * huying Dec 28, 2015
	 */
	public function edit(){
		if(IS_POST){
			$_POST['day'] = strtotime($_POST['day']);
			$result = $this->updateData($_POST, 'black_list', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,day,type,title,pic,sort,status,desc', 'black_list', 'id='.I('get.id', 0, 'intval'));
			$this->assign('info', $info);
// 			$areaList = $this->getAreaList();
// 			$this->assign('areaList', $areaList);
			$this->display('add');
		}
	}
	/**
	 * 删除红黑榜
	 * huying Dec 28, 2015
	 */
	public function del(){
		$result = $this->deleteData ('id='.I('get.id', 0, 'intval'), 'black_list' );
		$this->returnResult ( $result );
	}
}