<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 业主管理
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class OwnerController extends AdminController{
	/**
	 * 业主列表
	 * huying Dec 28, 2015
	 */
	public function index(){
		$where = 'o.aid = a.id and o.bid = b.id and o.uid = u.id and o.rid = r.id and a.id in('.session('ruleInfo.aids').')';
		$where .= I('get.aid') > 0 ? ' and o.aid='.I('get.aid', 0, 'intval') : '';
		$where .= I('get.bid') > 0 ? ' and o.bid='.I('get.bid', 0, 'intval') : '';
		$where .= I('get.uid') > 0 ? ' and o.uid='.I('get.uid', 0, 'intval') : '';
		$where .= I('get.name') != '' ? ' and o.name like "%'.I('get.name').'%"' : '';
		$where .= I('get.phone') != '' ? ' and o.phone like "%'.I('get.phone').'%"' : '';
		$where .= I('get.start_time') ? ' and o.reg_time>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and o.reg_time<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		if(I('get.status') != -1){
			$where .= I('get.status') > -1 && !is_null($_GET['status']) ? ' and o.status ='.I('get.status') : ' and o.status < 2';
		}
		$list = $this->getList('o.id,o.fid,o.name,o.point,o.phone,o.reg_time,o.status,o.pid,a.name as area, b.name as block, u.name as unit, r.name as room', 'whwx_owner as o, whwx_area as a, whwx_block as b, whwx_unit as u, whwx_room as r', $where, 'o.id desc', true);
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}
	/**
	 * 添加业主
	 * huying Dec 28, 2015
	 */
	public function add(){
		if(IS_POST){
			$_POST['addr'] = $_POST['block_name'].'-'.$_POST['unit_name'].'-'.$_POST['room_name'];
			$result = $this->updateData($_POST, 'owner');
			if($result !== false){
				M('room')->where('id='.$_POST['rid'])->setField('oid', $result);
				if($_POST['point'] > 0){
					$this->updateData(array('oid' => $result, 'point' => C('reg_point'), 'name' => '注册送积分', 'type' => 1, 'act' => 0, 'act_id' => 1, 'times' => time()), 'point');
				}
			}
			$this->returnResult($result);
		}else{
// 			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id desc');
// 			$this->assign('areaList', $areaList);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}
	/**
	 * 修改业主
	 * huying Dec 28, 2015
	 */
	public function edit(){
		if(IS_POST){
			$_POST['addr'] = $_POST['block_name'].'-'.$_POST['unit_name'].'-'.$_POST['room_name'];
			$result = $this->updateData($_POST, 'owner', 2);
			if($result !== false){
				M('room')->where('oid = '.I('post.id', 0, 'intval'))->setField('oid', 0);
				M('room')->where('id = '.I('post.rid', 0, 'intval'))->setField('oid', I('post.id', 0, 'intval'));
				$point = $_POST['point'] - $_POST['old_point'];
				if($point != 0){
					$this->updateData(array('oid' => $_POST['id'], 'point' => abs($point), 'name' => '管理员处理', 'type' => $point > 0 ? 1 : 0, 'act' => 0, 'act_id' => 1, 'times' => time()), 'point');
				}
			}
			$this->returnResult($result);
		}else{
// 			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id desc');
// 			$this->assign('areaList', $areaList);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$info = $this->getInfo('id,name,phone,aid,bid,uid,rid,status,desc,point', 'owner', 'id='.I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除业主
	 * huying Dec 28, 2015
	 */
	public function del(){
		$id = I('get.id', 0, 'intval');
		$result = M('owner')->where('id=' . $id)->setField('status', 2);
		if($result !== false){
			M('room')->where('oid=' . $id)->setField('oid', '0');
			M('wxfans')->where('type=1 and oid=' . $id)->save(array('oid' => 0, 'type' => 0));
		}
		$this->returnResult($result);
	}
	/**
	 * 业主信息
	 * huying Jan 13, 2016
	 */
	public function detail(){
		$where = 'o.aid = a.id and o.bid = b.id and o.uid = u.id and o.rid = r.id and o.id='.I('post.id', 0, 'intval');
		$info = $this->getInfo('o.id,o.name,o.phone,a.name as area, b.name as block, u.name as unit, r.name as room, r.owner, r.phone as ownerphone', 'whwx_owner as o, whwx_area as a, whwx_block as b, whwx_unit as u, whwx_room as r', $where);
		if(empty($info)){
			$this->ajaxReturn(array('status' => -1, 'info' => '获取信息失败'));	
		}
		$this->ajaxReturn(array('status' => 1, 'info' => $info));
	}
	/**
	 * 审核
	 * huying Jan 13, 2016
	 */
	public function check(){
		/* zxh 20160409
		$_POST['status'] = I('post.type', 0, 'intval') == 1 ? 1 : 2;
		$owner = $this->getInfo('r.oid,o.fid', 'whwx_owner as o ,whwx_room as r', 'o.rid = r.id and o.id='.I('post.id', 0, 'intval'));
		if($_POST['status'] == 1){
			if($owner['oid'] > 0){
				$this->ajaxReturn(array('status' => -1, 'info' => '该房间已经有业主了'));
			}
		}
		$result = $this->updateData($_POST, 'owner', 2);
		*/
		$result = M('owner')->where(array('id' => I('post.id', 0, 'intval')))->setField('status', (I('post.type', 0, 'intval') == 1 ? 1 : 2));
		if($result !== false){
			$owner = $this->getInfo('rid,fid', 'owner', 'id='.I('post.id', 0, 'intval'));
			$openid = M('wxfans')->where('id='.$owner['fid'].' and status = 1')->getField('openid');
			if($openid){
				$tplMsgData = array('first' => array('value' => '你的申请已审核！', 'color' => '#ff0000'), 'keyword1' => array('value' => '业主入驻', 'color' => '#173177'), 
						'keyword2' => array('value' => I('post.type', 0, 'intval') == 1 ? '通过' : '不通过', 'color' => '#173177'), 
						'keyword3' => array('value' => I('post.type', 0, 'intval') == 1 ? '无' : '信息不正确', 'color' => '#173177'), 
						'remark' => array('value' => '感谢你使用星管家智慧社区系统。', 'color' => '#173177'));
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				$result3 = $wechatAuth->sendTemplateMsg($openid, C('check_template'), '/Wap/Index/index', $tplMsgData);
			}
			if(I('post.type', 0, 'intval') == 1){
				M('room')->where('id='.$owner['rid'])->setField('oid', I('post.id', 0, 'intval'));
				$this->updateData(array('id' => $owner['fid'], 'type' => 1, 'oid' => I('post.id', 0, 'intval')), 'wxfans', 2);
			}
			$this->ajaxReturn(array('status' => 1, 'info' => '操作成功'));
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '操作失败'));
	}
	/**
	 * 获取叶业主积分变化详情
	 * huying Mar 3, 2016
	 */
	public function getPoint(){
		$info = $this->getInfo('id,name,phone,pic,point', 'owner', 'id='.$_GET['oid']);
		$this->assign('info', $info);
		$where = 'oid='.$_GET['oid'];
		$where .= I('get.start_time') ? ' and times >= '.strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and times <= '.strtotime(I('get.end_time')) : '';
		$where .= I('get.type', -1, 'intval') > -1 ? ' and type = '.I('get.type', -1, 'intval') : '';
		$data = $this->getList('id,point,times,name,act,type', 'point', $where, 'times desc', true);
		$this->assign('data', $data);
		$this->display();
	}
	/**
	 * 业主数据导出
	 * huying Mar 3, 2016
	 */
	public function export(){
		$where = 'o.aid = a.id and o.bid = b.id and o.uid = u.id and o.rid = r.id and a.id in('.session('ruleInfo.aids').')';
		$where .= I('get.aid') > 0 ? ' and o.aid='.I('get.aid', 0, 'intval') : '';
		$where .= I('get.bid') > 0 ? ' and o.bid='.I('get.bid', 0, 'intval') : '';
		$where .= I('get.uid') > 0 ? ' and o.uid='.I('get.uid', 0, 'intval') : '';
		$where .= I('get.name') != '' ? ' and o.name like "%'.I('get.name').'%"' : '';
		$where .= I('get.phone') != '' ? ' and o.phone like "%'.I('get.phone').'%"' : '';
		$where .= I('get.start_time') ? ' and o.reg_time>' . strtotime(I('get.start_time')) : '';
		$where .= I('get.end_time') ? ' and o.reg_time<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
		if(I('get.status') != -1){
			$where .= I('get.status') > -1 && !is_null($_GET['status']) ? ' and o.status ='.I('get.status') : ' and o.status < 2';
		}
		$list = $this->getList('o.name,o.phone,a.name as area,b.name as block,u.name as unit,r.name as room,o.point,o.reg_time,o.status', 'whwx_owner as o, whwx_area as a, whwx_block as b, whwx_unit as u, whwx_room as r', $where, 'o.id desc');
		foreach($list as $k => $v){
			$list[$k]['reg_time'] = date('Y-m-d H:i:s', $v['reg_time']);
			switch($v['status']){
				case 1: $list[$k]['status'] = '已审核'; break;
				case 2: $list[$k]['status'] = '已删除'; break;
				default: $list[$k]['status'] = '未审核';
			}
		}
		$title = array('业主', '手机', '小区', '楼栋', '单元', '房号', '积分', '注册时间', '状态');
		array_unshift($list, $title);
		\Common\Api\PHPExcelApi::exportExcel($list, '业主筛选结果导出', true);
	}
}