<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class RoomController extends AdminController{

	public function index(){
		$where = 'r.aid = a.id and r.bid=b.id and r.uid= u.id and a.id in(0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.aid') > 0 ? ' and r.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.bid') > 0 ? ' and r.bid=' . I('get.bid', 'intval') : '';
		$where .= I('get.uid') > 0 ? ' and r.uid=' . I('get.uid', 'intval') : '';
		$where .= I('get.addr', '', 'strval') ? ' and r.addr like "' . I('get.addr', '', 'strval') . '%"' : '';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and r.status=' . I('get.status', -1, 'intval');
		$list = $this->getList('r.id,r.aid,r.bid,r.uid,r.addr,r.owner,r.phone,r.name,r.sort,r.status,r.size,a.name as area,b.name as block,u.name as unit', 'whwx_room r,whwx_area a,whwx_block b,whwx_unit u', $where, 'r.sort desc,r.id asc', true);
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 添加房号
	 * yaoyingli 2015年12月29日
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'room');
			$this->returnResult($result);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}

	/**
	 * 修改房号
	 * yaoyingli 2015年12月29日
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'room', 2);
			$result1 = M('block')->where('id=' . $_POST['bid'])->setField('rid', $_POST['id']);
			if($result1 > 0 && $_POST['old_bid'] != $_POST['rid']){
				M('block')->where('id=' . $_POST['rid_rid'])->setField('rid', 0);
			}
			$this->returnResult($result);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$info = $this->getInfo('id,owner,phone,addr,aid,bid,uid,name,size,status', 'room', 'id=' . $_GET['id']);
			$this->assign('info', $info);
			$this->display('add');
		}
	}

	/**
	 * 删除房号
	 * yaoyingli 2015年12月29日
	 */
	public function del(){
		$result = $this->deleteData('id=' . $_GET['id'], 'room');
		$this->returnResult($result);
	}

	/**
	 * 根据单元号获取房间名
	 * huying Dec 28, 2015
	 */
	public function getRoomByUid(){
		$list = $this->getList('id,name,oid', 'room', 'bid = ' . I('get.bid', 0, 'intval') . ' and uid = ' . I('get.uid', 0, 'intval'), 'sort desc,id asc');
		$this->ajaxReturn($list);
	}

	/**
	 * 房间数据导出
	 * huying Mar 3, 2016
	 */
	public function export(){
		$where = 'r.aid = a.id and r.bid=b.id and r.uid= u.id and a.id in(0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.aid') > 0 ? ' and r.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.bid') > 0 ? ' and r.bid=' . I('get.bid', 'intval') : '';
		$where .= I('get.uid') > 0 ? ' and r.uid=' . I('get.uid', 'intval') : '';
		$where .= I('get.addr', '', 'strval') ? ' and r.addr like "' . I('get.addr', '', 'strval') . '%"' : '';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and r.status=' . I('get.status', -1, 'intval');
		$list = $this->getList('a.name as area,b.name as block,u.name as unit,r.addr,r.size,r.owner,r.phone', 'whwx_room r,whwx_area a,whwx_block b,whwx_unit u', $where, 'r.sort desc,r.id asc');
		$title = array('小区', '楼栋', '单元', '房号', '面积', '业主', '手机');
		array_unshift($list, $title);
		\Common\Api\PHPExcelApi::exportExcel($list, '房间筛选结果导出', true);
	}
}