<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 商家列表
 * huying Dec 23, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class MerchantController extends AdminController{

	/**
	 * 商家列表
	 * huying Dec 28, 2015
	 */
	public function index(){
		$where = 'm.aid = a.id and m.type_id = t.id and m.aid in(0,' . session('ruleInfo.aids') . ')';
		$where .= I('get.type_id') && I('get.type_id') > 0 ? ' and m.type_id=' . I('get.type_id', 0, 'intval') : '';
		$where .= I('get.aid') && I('get.aid') > 0 ? ' and m.aid=' . I('get.aid', 0, 'intval') : '';
		$where .= I('get.name') && I('get.name') != '' ? ' and m.name like "%' . I('get.name') . '%"' : '';
		$where .= (I('get.status') !== false) && (I('get.status', -1) > -1) ? ' and m.status =' . I('get.status') : '';
		$list = $this->getList('m.id,m.name,m.type_id,t.name as type,m.sort,m.status,a.name as area', 'whwx_merchant as m, whwx_merchant_type as t,whwx_area as a', $where, 'm.id desc', true);
		$this->assign('list', $list);
		$typeList = $this->getList('id,name', 'merchant_type', 'status = 1', 'id desc');
		$this->assign('typeList', $typeList);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 添加商家
	 * huying Dec 28, 2015
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'merchant');
			$this->returnResult($result);
		}else{
			$typeList = $this->getList('id,name', 'merchant_type', 'status = 1', 'id desc');
			$this->assign('typeList', $typeList);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}

	/**
	 * 修改商家
	 * huying Dec 28, 2015
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'merchant', 2);
			$this->returnResult($result);
		}else{
			$typeList = $this->getList('id,name', 'merchant_type', 'status = 1', 'id desc');
			$this->assign('typeList', $typeList);
			$info = $this->getInfo('id,aid,address,tel,name,mapx,mapy,type_id,pic,desc,sort,status', 'merchant', 'id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display('add');
		}
	}

	/**
	 * 删除商家
	 * huying Dec 28, 2015
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'merchant');
		$this->returnResult($result);
	}
}