<?php
namespace Common\Controller;
use Common\Controller\BaseController;

/**
 * 站长后台基类
 * zhangxinhe 2015-12-25
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class AdminController extends BaseController{

	protected function _initialize(){
		parent::_initialize();
		// 验证用户登录
		if(!session('aid')){
			session('[destroy]');
			$this->redirect('Admin/Public/index');
		}
		// 是否启用认证
		$authCheck = true;
		if($authCheck){
			// 无需认证模块
			$noCheck = array('Index/index', 'Index/main', 'Service/getServiceBlockByAid', 'Repair/getRepairMan', 'Payment/getBill', 'Block/getBlockByAid', 'Room/getRoomByUid', 'Unit/getUnitByBid');
			if(!$this->authCheck(CONTROLLER_NAME . '/' . ACTION_NAME, $noCheck)){
				if(IS_AJAX){
					$this->ajaxReturn(array('info' => '没有权限！', 'status' => '0'));
				}else{
					exit('没有权限');
				}
			}
		}
		// 获取左侧菜单
		$menu = session('left_menu');
		if(!$menu){
			$menuList = $this->getList('id,pid,title,name,type', 'node', 'status=1 and type<2 and id in (' . session('ruleInfo.rules') . ')', 'sort desc,id asc');
			foreach($menuList as $k => $v){
				if($v['pid'] == 0){
					$menu[$k] = $v;
					foreach($menuList as $key => $value){
						$value['pid'] == $v['id'] && $menu[$k]['sub'][] = $value;
					}
				}
			}
			session('left_menu', $menu);
		}
		$this->assign('menuList', $menu);
// 		$warnList = $this->getList('id,name,type,typeid','warn', 'status = 1', 'id desc',true, 6);
		$warnList = M('warn')->field('id,name,type,typeid')->where('status = 1 and aid in(0,'.session('ruleInfo.aids').')')->group('type')->select();
		$this->assign('warnList', $warnList);
	}

	/**
	 * 检查关键字是否存在
	 * @param string $keyword 关键字
	 * @param number $pid 项目ID
	 * @param string $module 模块名
	 *        zhangxinhe 2015-12-23
	 */
	public function checkKeyword($keyword, $pid = 0){
		$info = M('keyword')->field('id,pid')->where(array('keyword' => trim($keyword)))->find();
		if($info['id'] > 0 && ($pid == 0 || $info['pid'] != $pid)){
			$this->returnResult(false, array('', '关键字已存在！'));
		}
	}

	/**
	 * 设置关键字
	 * @param string $module 内容模块
	 * @param integer $pid 内容ID
	 * @param number $act 操作 1添加 2修改 3删除
	 * @param string $keyword 关键字
	 *        zhangxinhe 2015-1-29
	 */
	public function setKeyword($module, $pid, $act = 3, $keyword = ''){
		$keyword = trim($keyword);
		if($act == 1){
			return M('keyword')->add(array('module' => $module, 'pid' => $pid, 'keyword' => $keyword));
		}elseif($act == 2){
			return M('keyword')->where(array('module' => $module, 'pid' => $pid))->save(array('keyword' => $keyword));
		}else{
			return M('keyword')->where(array('module' => $module, 'pid' => $pid))->delete();
		}
	}

	/**
	 * 快捷状态设置
	 * zhangxinhe Dec 25, 2015
	 */
	public function setStatus(){
		$status = I('get.status', 0, 'intval');
		$result = M(CONTROLLER_NAME)->where('id=' . I('get.id', 0, 'intval'))->setField('status', ($status == 1 ? 0 : 1));
		$this->returnResult($result);
	}

	/**
	 * 快捷排序设置
	 * zhangxinhe Dec 25, 2015
	 */
	public function setSort(){
		$sort = I('get.sort', 100, 'intval');
		$result = M(CONTROLLER_NAME)->where('id=' . I('get.id', 0, 'intval'))->setField('sort', $sort);
		$this->returnResult($result);
	}

	/**
	 * 权限验证
	 * @param string $name 规则名称
	 * @param array $noCheck 无需验证模块
	 * @param number $type 验证类型 1 实时验证 2 登录验证
	 *        zhangxinhe 2015-12-25
	 */
	private function authCheck($name, $noCheck = array(), $type = 1){
		$aid = session('aid');
		if(empty($name) || $aid <= 0){
			return false;
		}elseif($aid == 1 || in_array($name, $noCheck)){
			return true;
		}elseif($type == 2 && session('WLT_AUTH_LIST')){
			return in_array($name, session('WLT_AUTH_LIST'));
		}else{
			$rules = M()->table('whwx_group g,whwx_admins a')->where('a.gid=g.id and a.id=' . $aid)->getField('g.rules');
			if($rules){
				if($type == 2){
					$rulesName = M('node')->where('status=1 and id in (' . $rules . ')')->getField('name', true);
					session('WLT_AUTH_LIST', $rulesName);
					return in_array($name, $rulesName);
				}else{
					$rulesArr = explode(',', $rules);
					$rulesId = M('node')->where('status=1 and name="' . $name . '"')->getField('id');
					return in_array($rulesId, $rulesArr);
				}
			}
			return false;
		}
	}

	/**
	 * 获取管理的小区
	 * huying Jan 7, 2016
	 */
	public function getAreaList($fields = null){
		$fields = $fields == null ? 'id,name' : $fields;
		return M('area')->field($fields)->where('status = 1 and id in (' . session('ruleInfo.aids') . ')')->select();
	}
}
?>