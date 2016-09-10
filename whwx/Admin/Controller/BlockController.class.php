<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class BlockController extends AdminController{

	public function index(){
		$where = 'b.aid = a.id and b.aid in(0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and b.id=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.aid', 0, 'intval') > 0 ? ' and  b.aid=' . I('get.aid', 0, 'intval') : '';
		$where .= I('get.name', '', 'strval') == '' ? '' : ' and b.name like "%' . I('get.name', '', 'strval') . '%"';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and b.status=' . I('get.status', -1, 'intval');
		$list = $this->getList('b.id,b.aid,b.name,b.status,b.sort,b.units,a.name as title', 'whwx_block b,whwx_area a', $where, 'sort desc,id asc', true);
		$this->assign('list', $list);
		$areaList = $this->getAreaList('id,name');
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 添加楼栋
	 * yaoyingli 2015年12月29日
	 */
	public function add(){
		if(IS_POST){
			$_POST['units'] = ','.implode(',', $_POST['units']).',';
			$result = $this->updateData($_POST, 'block');
			$this->returnResult($result);
		}else{
			$unitList = $this->getList('id,name', 'unit', 'status=1');
			$this->assign('unitList', $unitList);
			$areaList = $this->getAreaList('id,name as title');
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}

	/**
	 * 修改楼栋
	 * yaoyingli 2015年12月29日
	 */
	public function edit(){
		if(IS_POST){
			$_POST['units'] = ','.implode(',', $_POST['units']).',';
			$result = $this->updateData($_POST, 'block', 2);
			$this->returnResult($result);
		}else{
			$unitList = $this->getList('id,name', 'unit');
			$this->assign('unitList', $unitList);
			$areaList = $this->getAreaList('id,name as title');
			$this->assign('areaList', $areaList);
			$info = $this->getInfo('id,aid,name,status,units', 'block', 'id=' . $_GET['id']);
			$info['units'] = '#units' . implode(',#units', explode(',', $info['units']));
			// dump($info);
			$this->assign('info', $info);
			$this->display('add');
		}
	}

	/**
	 * 删除楼栋
	 * yaoyingli 2015年12月29日
	 */
	public function del(){
		$result = $this->deleteData('id=' . $_GET['id'], 'block');
		$this->returnResult($result);
	}

	/**
	 * 根据小区id找楼栋号
	 * huying Dec 28, 2015
	 */
	public function getBlockByAid(){
		$list = $this->getList('id,name', 'block', 'status = 1 and aid=' . I('get.aid', 0, 'intval'), 'sort desc,id asc');
		$this->ajaxReturn($list);
	}
}