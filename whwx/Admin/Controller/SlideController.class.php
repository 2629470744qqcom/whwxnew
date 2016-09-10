<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 幻灯片
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class SlideController extends AdminController {
	/**
	 * 幻灯片
	 * huying Dec 28, 2015
	 */
	public function index(){
		$where = '1=1';
		$where .= I('get.title')&&I('get.title') != '' ? ' and title like "%'.I('get.title').'%"' : '';
		// 		小区筛选
		$where .= I('get.aid') && I('get.aid') > 0 ? ' and aids like "%,'.I('get.aid', 'intval').',%"' : '';
		$where .= I('get.status', -1) > -1 ? ' and status ='.I('get.status') : '';
		$list = $this->getList('id,title,sort,status,url', 'slide', $where, 'id desc',true);
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}
	/**
	 * 添加幻灯片
	 * huying Dec 28, 2015
	 */
	public function add(){
		if(IS_POST){
			$_POST['aids'] = ','.implode(',', $_POST['aid']).',';
			$result = $this->updateData($_POST, 'slide');
			$this->returnResult($result);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}
	/**
	 * 修改幻灯片
	 * huying Dec 28, 2015
	 */
	public function edit(){
		if(IS_POST){
			$_POST['aids'] = ','.implode(',', $_POST['aid']).',';
			$result = $this->updateData($_POST, 'slide', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,aids,title,pic,sort,status,url,desc', 'slide', 'id='.I('get.id', 0, 'intval'));
			$info['aids'] = explode(',', $info['aids']);
			$this->assign('info', $info);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display('add');
		}
	}
	/**
	 * 删除幻灯片
	 * huying Dec 28, 2015
	 */
	public function del(){
		$result = $this->deleteData ('id='.I('get.id', 0, 'intval'), 'slide' );
		$this->returnResult ( $result );
	}
}