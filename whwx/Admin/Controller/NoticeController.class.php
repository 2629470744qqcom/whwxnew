<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 通知/公告
 * huying Dec 29, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class NoticeController extends AdminController{
	/**
	 * 列表
	 * huying Dec 29, 2015
	 */
	public function index(){
		$where = '1 = 1';
		//$where .= I('get.aid') &&I('get.aid') ==-1 ?  '':' and n.aid='.I('get.aid', 0, 'intval') ;
		if(I('get.aid', -1, 'intval') <> -1){
			if(I('get.aid', -1, 'intval')==0){
				$where .=' and  n.aid =0';
			}else{
				$where .=' and n.aid='.I('get.aid', 0, 'intval');
			}
		}
		$where .= I('get.title')&&I('get.title') != '' ? ' and n.title like "%'.I('get.title').'%"' : '';
		$where .= (I('get.type') !== false) && (I('get.type', -1) > -1 )? ' and n.status ='.I('get.type') : '';
		$list = $this->getList('n.id,n.aid,n.title,n.times,n.down_time,n.look,n.number,n.status,n.sort', 'notice as n', $where, 'id desc', true);
		foreach ($list as $k => $v){
			if($v['aid'] > 0){
				$list[$k]['aname'] = M('area')->where('id='.$v['aid'])->getField('name');
			}
		}
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}
	/**
	 * 添加
	 * huying Dec 29, 2015
	 */
	public function add(){
		if(IS_POST){
			$_POST['times'] = time();
			$result = $this->updateData($_POST, 'notice');
			$this->returnResult($result);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}
	/**
	 * 修改
	 * huying Dec 29, 2015
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'notice', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,aid,title,times,abstract,desc,sort', 'notice', 'id='.I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display('add');
		}
	}
	/**
	 * 删除
	 * huying Dec 29, 2015
	 */
	public function del(){
		$result = $this->deleteData ('id='.I('get.id', 0, 'intval'), 'notice' );
		if($result){
			M('owner_notice')->where(array('type' => 1, 'typeid' => I('get.id', 0, 'intval')))->delete();
		}
    	$this->returnResult ( $result );
	}
	/**
	 * 查看
	 * yaoyongli 2016/3/19
	 */
	public function desc(){
		$info =$this->getInfo('desc', 'notice', 'id='.$_POST["id"]);
		if(empty($info)){
			$this->ajaxReturn(array('status' => -1, 'info' => '获取信息失败'));	
		}
		$this->ajaxReturn(array('status' => 1, 'info' => $info));
	}
	/**
	 * 发布通知
	 * huying Jan 22, 2016
	 */
	public function release(){
		$info = $this->getInfo('id,aid,title', 'notice', 'id='.I('post.id', 0, 'intval'));
		if(!empty($info)){
			$where = $info['aid'] > 0 ? 'aid = '.$info['aid'].' and status = 1' : 'status = 1';
			$owner = $this->getList('id', 'owner', $where, 'id desc');
			if(empty($owner)){
				$this->ajaxReturn(array('status' => -1, 'info' => '没有接收人'));
			}
			foreach ($owner as $k => $v){
				$list[] = array('oid' => $v['id'], 'title' => '通知公告', 'desc' => $info['title'], 'times' => time(), 'type' => 1, 'typeid' => $info['id']);
				if($k % 1000 == 0){
					$result = M('owner_notice')->addAll($list);
					$list = array();
				}
			}
			$result = M('owner_notice')->addAll($list);
			if($result !== false){
				$this->updateData(array('id' => $info['id'], 'down_time' => time(), 'number' => $k + 1, 'status' => 1), 'notice', 2);
			}
			$this->returnResult($result);
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '没有接收人'));
	}
}