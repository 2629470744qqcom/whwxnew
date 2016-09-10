<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 部门管理
 * huying Dec 29, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class GroupController extends AdminController{
	/**
	 * 部门列表
	 * huying Dec 29, 2015
	 */
	public function index(){
		$where = '1 = 1';
		$where .= I('get.name') && I('get.name') != '' ? ' and g.name like "%' . I('get.name') . '%"' : '';
		$where .= I('get.status', -1) > -1 ? ' and g.status =' . I('get.status') : '';
		$list = $this->getList('g.id,g.name,g.aids,g.status', 'group as g', $where, 'id desc',true);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 添加列表
	 * huying Dec 29, 2015
	 */
	public function add(){
		if(IS_POST){
			$_POST['aids'] = implode(',', $_POST['aid']);
			$result = $this->updateData($_POST, 'group');
			$this->returnResult($result, null, U('Group/rule', array('id' => $result)));
		}else{
			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id desc');
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}
	/**
	 * 修改部门
	 * huying Dec 29, 2015
	 */
	public function edit(){
		if(IS_POST){
			$_POST['aids'] = implode(',', $_POST['aid']);
			$result = $this->updateData($_POST, 'group', 2);
			$this->returnResult($result, null, U('Group/rule', array('id' => $_POST['id'])));
		}else{
			$info = $this->getInfo('id,aids,name,remark,status', 'group', 'id=' . I('get.id', 0, 'intval'));
			$info['aids'] = explode(',', $info['aids']);
			$this->assign('info', $info);
			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id desc');
			$this->assign('areaList', $areaList);
			$this->display('add');
		}
	}
	/**
	 * 权限分配
	 * huying Dec 29, 2015
	 */
	public function rule(){
		if(IS_POST){
			$_POST['rules'] = implode(',', $_POST['rule']);
			$result = $this->updateData($_POST, 'group', 2);
			$this->returnResult($result);
		}else{
			$list = $this->getList('id,pid,title,name,type,sort,status', 'node', 'status=1', 'sort desc,id asc');
			foreach($list as $key => $value){
				if($value['pid'] == 0){
					$arr[] = $value;
					foreach($list as $k => $v){
						if($v['pid'] == $value['id']){
							$arr[] = $v;
							foreach($list as $k1 => $v1){
								if($v1['pid'] == $v['id']){
									$arr[] = $v1;
								}
							}
						}
					}
				}
			}
			$this->assign('list', $arr);
			$info = $this->getInfo('id,rules', 'group', 'id=' . I('get.id', 0, 'intval'));
			$info['rules'] = explode(',', $info['rules']);
			$this->assign('info', $info);
			$this->display();
		}
	}
	/**
	 * 删除部门
	 * huying Dec 29, 2015
	 */
	public function del(){
		$id = I('get.id', 0, 'intval');
		if($id == 1){
			$this->returnResult(false, array('', '默认部门不可删除'));
		}
		$num = M('admins')->where(array('gid' => $id))->count();
		if ($num > 0){
			$this->returnResult(false, array('', '当前部门内有员工存在，不可删除'));
		}
		$result = $this->deleteData('id=' . $id, 'group');
		$this->returnResult($result);
	}
}