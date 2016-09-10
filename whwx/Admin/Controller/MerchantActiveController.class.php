<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 优惠活动
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class MerchantActiveController extends AdminController{
	/**
	 * 活动列表
	 * huying Dec 28, 2015
	 */
	public function index(){
		$where = 'a.aid in(0,'.session('ruleInfo.aids').')';
// 		$where .= I('get.aid') && I('get.aid') > 0 ? ' and a.aid=' . I('get.aid', 0, 'intval') : '';
		if(I('get.aid', -1, 'intval') <> -1){
			if(I('get.aid', -1, 'intval') == 0){
				$where .=' and  a.aid =0';
			}else{
				$where .=' and a.aid='.I('get.aid', 0, 'intval');
			}
		}
		$where .= I('get.title') && I('get.title') != '' ? ' and a.title like "%' . I('get.title') . '%"' : '';
		$where .= I('get.status', -1) > -1 ? ' and a.status =' . I('get.status') : '';
		$list = $this->getList('a.id,a.title,a.start_time,a.end_time,a.sort,a.status,a.aid', 'merchant_active as a', $where, 'a.id desc',true);
		foreach ($list as $k => $v){
			$list[$k]['name'] = $v['aid'] > 0 ? M('area')->where('id='.$v['aid'])->getField('name') : '全部小区';
		}
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}
	/**
	 * 添加优惠活动
	 * huying Dec 28, 2015
	 */
	public function add(){
		if(IS_POST){
			$_POST['start_time'] = strtotime($_POST['start_time']);
			$_POST['end_time'] = strtotime($_POST['end_time']);
			$result = $this->updateData($_POST, 'merchant_active');
			$this->returnResult($result);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$typeList = $this->getList('id,name', 'merchant_type', 'status = 1', 'id desc');
			$this->assign('typeList', $typeList);
			$this->display();
		}
	}
	/**
	 * 修改商家
	 * huying Dec 28, 2015
	 */
	public function edit(){
		if(IS_POST){
			$_POST['start_time'] = strtotime($_POST['start_time']);
			$_POST['end_time'] = strtotime($_POST['end_time']);
			$result = $this->updateData($_POST, 'merchant_active', 2);
			$this->returnResult($result);
		}else{
			$typeList = $this->getList('id,name', 'merchant_type', 'status = 1', 'id desc');
			$this->assign('typeList', $typeList);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$info = $this->getInfo('id,title,aid,pic,start_time,end_time,desc,sort,status', 'merchant_active', 'id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 删除商家
	 * huying Dec 28, 2015
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'merchant_active');
		$this->returnResult($result);
	}
}