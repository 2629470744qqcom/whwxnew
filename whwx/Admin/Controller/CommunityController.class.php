<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 社区活动管理
 * huying Dec 23, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class CommunityController extends AdminController{

	/**
	 * 活动列表
	 * huying Dec 26, 2015
	 */
	public function index(){
		$where = 'aids in(0,'.session('ruleInfo.aids').')';
		//$where .= I('get.aid') && I('get.aid') > 0 ? ' and aids=' . I('get.aid', 'intval') : '';
// 		$where .= I('get.aid') &&I('get.aid') ==-1 ?  '' ;
// 		if(I('get.aid') &&I('get.aid') ==-1){
// 			$where .=" and  aids =''";
// 		};
		if(I('get.aid', -1, 'intval') <> -1){
			if(I('get.aid', -1, 'intval') == 0){
				$where .=' and  aids =0';
			}else{
				$where .=' and aids='.I('get.aid', 0, 'intval');
			}
		}
		$where .= I('get.cate') && I('get.cate') > 0 ? ' and cate=' . I('get.cate', 0, 'intval') : '';
		$where .= I('get.title') && I('get.title') != '' ? ' and title like "%' . I('get.title') . '%"' : '';
		$where .= (I('get.status') !== false) && (I('get.status', -1) > -1) ? ' and status =' . I('get.status') : '';
		//dump($where);
		$list = $this->getList('id,title,cate,status,sort,limit_type,aids,sign_num', 'community', $where, 'id desc', true);
		foreach ($list as $k => $v){
			$list[$k]['name'] = $v['aids'] > 0 ? M('area')->where('id='.$v['aids'])->getField('name') : '全部小区';
		}
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 添加
	 * huying Dec 26, 2015
	 */
	public function add(){
		if(IS_POST){
			$_POST['start_time'] = strtotime($_POST['start_time']);
			$_POST['end_time'] = strtotime($_POST['end_time']);
			$result = $this->updateData($_POST, 'community');
			$this->returnResult($result, null);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}

	/**
	 * 修改
	 * huying Dec 26, 2015
	 */
	public function edit(){
		if(IS_POST){
			$_POST['start_time'] = strtotime($_POST['start_time']);
			$_POST['end_time'] = strtotime($_POST['end_time']);
			$result = $this->updateData($_POST, 'community', 2);
			$this->returnResult($result, null);
		}else{
			$info = $this->getInfo('id,title,aids,desc,address,times,pic,limit_type,cate,num,start_time,end_time,sort,status', 'community', 'id=' . I('get.id', 0, 'intval'));
			$info['aids'] = explode(',', $info['aids']);
			$this->assign('info', $info);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display('add');
		}
	}

	/**
	 * 删除
	 * huying Dec 26, 2015
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'community');
		$this->returnResult($result);
	}

	/**
	 * 报名数据
	 * huying Dec 26, 2015
	 */
	public function sign(){
		$where = 'c.aid=' . I('get.aid', 0, 'intval') . ' and c.status = 1 and c.aids = a.id';
		$where .= I('get.aids') > 0 ? ' and c.aids=' . I('get.aids', 'intval') : '';
		$where .= I('get.name') != '' ? ' and c.name like "%' . I('get.name') . '%"' : '';
		$where .= I('get.tel') != '' ? ' and c.tel like "%' . I('get.tel') . '%"' : '';
		$list = $this->getList('c.id,c.aids,c.oid,c.remark,c.name,c.tel,c.times,c.status,a.name as area', 'whwx_community_sign as c,whwx_area as a', $where, 'times desc', true, 12);
		foreach($list as $k => $v){
			if(is_numeric($v['oid']) && $v['oid'] > 0){
				$list[$k]['oids'] = $v['oid'];
				$list[$k]['oid'] = M('owner')->where(array('id' => $v['oid']))->getField('addr');
			}else{
				$list[$k]['oids'] = $v['oid'];
			}
		}
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 添加
	 * huying Dec 26, 2015
	 */
	public function addSign(){
		$_POST['times'] = time();
		$result = $this->updateData($_POST, 'community_sign');
		M('community')->where(array('id' => $_POST['aid']))->setInc('sign_num');
		$this->returnResult($result, null, U('Community/sign', array('aid' => $_POST['aid'])));
	}

	public function editSign(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'community_sign', 2);
			$this->returnResult($result, null, U('Community/sign', array('aid' => $_POST['aid'])));
		}else{
			$info = $this->getInfo('id,name,tel,aids,remark,oid', 'community_sign', array('id' => I('get.id', 0, 'intval')));
			if(is_numeric($info['oid']) && $info['oid'] > 0){
				$info['oid'] = M('owner')->where(array('id' => $info['oid']))->getField('addr');
			}
			$this->ajaxReturn($info);
		}
	}

	/**
	 * 删除
	 * huying Dec 26, 2015
	 */
	public function delSign(){
		$aid = M('community_sign')->where(array('id' => I('get.id', 0, 'intval')))->getField('aid');
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'community_sign');
		M('community')->where(array('id' => $aid))->setDec('sign_num');
		$this->returnResult($result);
	}

	/**
	 * 导出数据
	 * huying Jan 28, 2016
	 */
	public function export(){
		$data = $this->getList('c.name,c.tel,from_unixtime(c.times) as times,a.name as area,c.oid,c.remark', 'whwx_community_sign as c,whwx_area as a', 'c.status = 1 and a.id = c.aids and c.aid=' . I('get.aid', 0, 'intval'), 'c.times desc');
		foreach($data as $k => $v){
			if(is_numeric($v['oid']) && $v['oid'] > 0){
				$data[$k]['oid'] = M('owner')->where(array('id' => $v['oid']))->getField('addr');
			}
		}
		if($data){
			$title = array(array('姓名', '手机', '报名时间', '所属小区', '房号', '备注'));
			$arr = array_merge($title, $data);
			$export = \Common\Api\PHPExcelApi::exportExcel($arr, '社区活动报名数据');
		}else{
			$this->error('没有数据');
		}
	}
}