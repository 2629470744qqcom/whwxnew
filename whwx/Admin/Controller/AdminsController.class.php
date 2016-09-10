<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 员工管理
 * huying Dec 29, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class AdminsController extends AdminController{
	/**
	 * 员工列表
	 * huying Dec 29, 2015
	 */
	public function index(){
		$where = 'a.gid = g.id';
		$where .= I('get.gid') &&I('get.gid') > 0 ? ' and a.gid='.I('get.gid', 0, 'intval') : '';
		$where .= I('get.name')&&I('get.name') != '' ? ' and a.name like "%'.I('get.name').'%"' : '';
		$where .= I('get.tel')&&I('get.tel') != '' ? ' and a.tel like "%'.I('get.tel').'%"' : '';
		$where .= I('get.status', -1) > -1 ? ' and a.status ='.I('get.status') : '';
		$list = $this->getList('a.id,a.name,a.status,a.tel,a.gid,g.name as groupname,a.last_login_time,a.last_login_ip', 'whwx_group as g, whwx_admins as a', $where, 'id desc',true);
		$this->assign('list', $list);
		$groupList = $this->getList('id,name', 'group', 'status = 1', 'id desc');
		$this->assign('groupList', $groupList);
		$this->display();
	}
	/**
	 * 员工列表
	 * huying Dec 29, 2015
	 */
	public function add(){
		if(IS_POST){
			if($_POST['pwd'] != $_POST['pwd2']){
				$this->returnResult(false, array('操作成功', '两次密码不一致'));
			}
			$_POST['pwd'] = md5($_POST['name'] . '_WhwX_' . $_POST['pwd']);
			$result = $this->updateData($_POST, 'admins');
			$this->returnResult($result);
		}else{
			$groupList = $this->getList('id,name', 'group', 'status = 1', 'id desc');
			$this->assign('groupList', $groupList);
			$this->display();
		}
	}
	/**
	 * 员工列表
	 * huying Dec 29, 2015
	 */
	public function edit(){
		if(IS_POST){
			if(!empty($_POST['pwd'])){
				if($_POST['pwd'] != $_POST['pwd2']){
					$this->returnResult(false, array('操作成功', '两次密码不一致'));
				}
				$_POST['pwd'] = md5($_POST['name'] . '_WhwX_' . $_POST['pwd']);
			}else{
				unset($_POST['pwd']);
			}
			$result = $this->updateData($_POST, 'admins', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,gid,name,tel,remark,status,last_login_time,last_login_ip', 'admins', 'id='.I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$groupList = $this->getList('id,name', 'group', 'status = 1', 'id desc');
			$this->assign('groupList', $groupList);
			$this->display('add');
		}
	}
	/**
	 * 员工列表
	 * huying Dec 29, 2015
	 */
	public function del(){
		$result = $this->deleteData ('id='.I('get.id', 0, 'intval'), 'admins' );
    	$this->returnResult ( $result );
	}
}