<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 预警管理
 * yaoyongli 2016年2月17号
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WarnController extends AdminController{
	/**
	 * 预警列表
	 * yaoyongli 2016年2月17号
	 */
	public function index(){
		$where = 'aid in(0,'.session('ruleInfo.aids').')';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and id=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.name', '', 'strval') == '' ? '' : ' and name like "%' . I('get.name', '', 'strval') . '%"';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and status=' . I('get.status', -1, 'intval');
		$list = $this->getList('id,name,type,times,status,typeid', 'warn', $where,'times desc', true);
		$this->assign('list', $list);
		$this->display();
	}
}
?>