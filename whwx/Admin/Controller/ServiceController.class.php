<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 客服专员
 * huying Dec 29, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ServiceController extends AdminController{
	/**
	 * 列表
	 * huying Dec 29, 2015
	 */
	public function index(){
		$where = 's.aid = a.id and a.id in('.session('ruleInfo.aids').')';
		$where .= I('get.aid') &&I('get.aid') > 0 ? ' and s.aid='.I('get.aid', 0, 'intval') : '';
		$where .= I('get.name')&&I('get.name') != '' ? ' and s.name like "%'.I('get.name').'%"' : '';
		$where .= I('get.phone')&&I('get.phone') != '' ? ' and s.phone like "%'.I('get.phone').'%"' : '';
		if(I('get.status') == ''){
			$where .= ' and s.status > -1';
		}elseif(I('get.status') > -2){
			$where .= I('get.status') > -2 ? ' and s.status ='.I('get.status') : ' and s.status >= 0';
		}
		$list = $this->getList('s.id,s.name,s.phone,s.aid,s.status,s.fid,a.name as area', 'whwx_service as s, whwx_area as a', $where, 's.id desc', true);
		/*
		foreach ($list as $k => $v){
			$list[$k]['fid'] = M('wxfans')->where('type=4 and oid='.$v['id'])->getField('id');
		}
		*/
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
			$_POST['bids'] = ','.implode(',', $_POST['bid']).',';
			$result = $this->updateData($_POST, 'service');
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
	 * 修改
	 * huying Dec 29, 2015
	 */
	public function edit(){
		if(IS_POST){
			$_POST['bids'] = ','.implode(',', $_POST['bid']).',';
			$result = $this->updateData($_POST, 'service', 2);
			$this->returnResult($result);
		}else{
// 			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id desc');
// 			$this->assign('areaList', $areaList);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$info = $this->getInfo('id,name,aid,pic,desc,status,bids,phone', 'service', 'id='.I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除
	 * huying Dec 29, 2015
	 */
	public function del(){
// 		$result = $this->deleteData ('id='.I('get.id', 0, 'intval'), 'service' );
		$result = M('service')->where('id='.I('get.id', 0, 'intval'))->setField('status', -1);
		if($result !== false){
			$this->updateData(array('type' => 0, 'oid' => 0), 'wxfans', 2, 'type = 4 and oid='.I('get.id', 0, 'intval'));
		}
    	$this->returnResult ( $result );
	}
	/**
	 * 根据小区id找已分配的楼栋号
	 * huying Dec 29, 2015
	 */
	public function getServiceBlockByAid(){
		$where = 'status = 1 and aid='.I('get.aid', 0, 'intval');
		$where .= $_GET['id'] && $_GET['id'] > 0 ? ' and id !='.I('get.id', 0, 'intval') : '';
		$list = $this->getList('bids', 'service', $where, 'id desc');
		$arr = ',';
		foreach ($list as $k => $v){
			$arr .= $v['bids'].',';
		}
		$this->ajaxReturn($arr);
	}
	/**
	 * 审核客服
	 * huying Jan 19, 2016
	 */
	public function vette(){
		if($_POST['status'] == null){
			$this->ajaxReturn(array('status' => -1, 'info' => '请选择审核状态'));
		}
		if(empty($_POST['bid']) && $_POST['status'] == 1){
			$this->ajaxReturn(array('status' => -1, 'info' => '请选择管理的楼栋'));
		}
		$_POST['bids'] = ','.implode(',', $_POST['bid']).',';
		$result = $this->updateData($_POST, 'service', 2);
		if($result !== false){
			$fid = M('service')->where('id='.I('post.id', 0, 'intval'))->getField('fid');
			if($fid > 0){
				$openid = M('wxfans')->where('id='.$fid.' and status = 1')->getField('openid');
				if($openid){
					$tplMsgData = array('first' => array('value' => '你的申请已审核！', 'color' => '#ff0000'), 'keyword1' => array('value' => '客服入驻', 'color' => '#173177'),
							'keyword2' => array('value' => $_POST['status'] == 1 ? '通过' : '不通过', 'color' => '#173177'),
							'keyword3' => array('value' => $_POST['status'] == 1 ? '无' : '信息不正确', 'color' => '#173177'),
							'remark' => array('value' => '感谢你使用星管家智慧社区系统。', 'color' => '#173177'));
					$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
					$result3 = $wechatAuth->sendTemplateMsg($openid, C('check_template'), '/Wap/Index/index', $tplMsgData);
				}
				$this->updateData(array('id' => $fid, 'type' => 4, 'oid' => I('post.id', 0, 'intval')), 'wxfans', 2);
			}
		}
		$this->returnResult($result);
	}
}