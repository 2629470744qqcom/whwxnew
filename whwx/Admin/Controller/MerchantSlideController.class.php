<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 幻灯片
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class MerchantSlideController extends AdminController {
	/**
	 * 幻灯片
	 * huying Dec 28, 2015
	 */
	public function index(){
		$where = 's.aid in(0,'.session('ruleInfo.aids').')';
		$where .= I('get.title')&&I('get.title') != '' ? ' and s.title like "%'.I('get.title').'%"' : '';
		$where .= (I('get.status') !== false) && (I('get.status', -1) > -1 ) ? ' and s.status ='.I('get.status') : '';
		//$where .= I('get.aid') &&I('get.aid') > 0 ? ' and s.aid='.I('get.aid', 0, 'intval') : '';
		if(I('get.aid', -1, 'intval') <> -1){
			if(I('get.aid', -1, 'intval') == 0){
				$where .=' and  s.aid =0';
			}else{
				$where .=' and s.aid='.I('get.aid', 0, 'intval');
			}
		}
		$list = $this->getList('s.id,s.title,s.sort,s.status,s.times,s.url,s.aid', 'merchant_slide as s', $where, 's.id desc',true);
		foreach ($list as $k => $v){
			$list[$k]['name'] = $v['aid'] > 0 ? M('area')->where('id='.$v['aid'])->getField('name') : '全部小区';
		}
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}
	/**
	 * 添加幻灯片
	 * huying Dec 28, 2015
	 */
	public function add(){
		if(IS_POST){
			$_POST['times'] = time();
			$result = $this->updateData($_POST, 'merchant_slide');
			$this->returnResult($result);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}
	/**
	 * 修改幻灯片
	 * huying Dec 28, 2015
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'merchant_slide', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,aid,title,pic,sort,status,url,desc', 'merchant_slide', 'id='.I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display('add');
		}
	}
	/**
	 * 删除幻灯片
	 * huying Dec 28, 2015
	 */
	public function del(){
		$result = $this->deleteData ('id='.I('get.id', 0, 'intval'), 'merchant_slide' );
		$this->returnResult ( $result );
	}
}