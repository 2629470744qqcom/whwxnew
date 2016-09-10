<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 维修员管理
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class RepairmanController extends AdminController{

	/**
	 * 列表
	 * huying Dec 29, 2015
	 */
	public function index(){
		$where = 'concat(",",aid,",") regexp concat(",(",replace("' . session('ruleInfo.aids') . '",",","|"),"),")';
		$where .= I('get.aid') > 0 ? ' and find_in_set("' . I('get.aid', 0, 'intval') . '",aid)' : '';
		$where .= I('get.name') ? ' and name like "%' . I('get.name') . '%"' : '';
		$where .= I('get.phone') ? ' and phone like "%' . I('get.phone') . '%"' : '';
		if(I('get.status') == ''){
			$where .= ' and status > -1';
		}elseif(I('get.status') > -2){
			$where .= I('get.status') > -2 ? ' and status =' . I('get.status') : ' and status = 2';
		}
		$list = $this->getList('id,name,phone,aid,status,fid', 'repairman', $where, 'id desc', true);
		foreach($list as $key => $value){
			$areaInfo = M('area')->where('id in (' . $value['aid'] . ')')->getField('name', true);
			$list[$key]['area'] = implode(',', $areaInfo);
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
			$_POST['aid'] = implode(',', $_POST['aid']);
			$result = $this->updateData($_POST, 'repairman');
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
			$_POST['aid'] = implode(',', $_POST['aid']);
			$result = $this->updateData($_POST, 'repairman', 2);
			$this->returnResult($result);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$info = $this->getInfo('id,name,aid,pic,desc,status,phone', 'repairman', 'id=' . I('get.id', 0, 'intval'));
			$info['aid'] = explode(',', $info['aid']);
			$this->assign('info', $info);
			$this->display('add');
		}
	}

	/**
	 * 删除
	 * huying Dec 29, 2015
	 */
	public function del(){
		$result = M('repairman')->where('id=' . I('get.id', 0, 'intval'))->setField('status', -1);
		if($result !== false){
			$this->updateData(array('type' => 0, 'oid' => 0), 'wxfans', 2, 'type = 3 and oid=' . I('get.id', 0, 'intval'));
		}
		$this->returnResult($result);
	}

	/**
	 * 审核维修员
	 * huying Jan 19, 2016
	 */
	public function vette(){
		if($_POST['status'] == null){
			$this->ajaxReturn(array('status' => -1, 'info' => '请选择审核状态'));
		}
		$result = $this->updateData($_POST, 'repairman', 2);
		if($result !== false){
			$fid = M('repairman')->where('id=' . I('post.id', 0, 'intval'))->getField('fid');
			if($fid > 0){
				$openid = M('wxfans')->where('id='.$fid.' and status = 1')->getField('openid');
				if($openid){
					$tplMsgData = array('first' => array('value' => '你的申请已审核！', 'color' => '#ff0000'), 'keyword1' => array('value' => '维修员入驻', 'color' => '#173177'),
							'keyword2' => array('value' => $_POST['status'] == 1 ? '通过' : '不通过', 'color' => '#173177'),
							'keyword3' => array('value' => $_POST['status'] == 1 ? '无' : '信息不正确', 'color' => '#173177'),
							'remark' => array('value' => '感谢你使用星管家智慧社区系统。', 'color' => '#173177'));
					$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
					$result3 = $wechatAuth->sendTemplateMsg($openid, C('check_template'), '/Wap/Index/index', $tplMsgData);
				}
				$this->updateData(array('id' => $fid, 'type' => 3, 'oid' => I('post.id', 0, 'intval')), 'wxfans', 2);
			}
		}
		$this->returnResult($result);
	}
}