<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 租售服务列表和搜索功能
 * yaoyongli 2015年12月25日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class RentalServiceController extends AdminController{

	public function index(){
		$where = 's.oid=o.id and s.aid = a.id and s.aid in(0,'.session('ruleInfo.aids').')';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and s.id=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.aid') > 0 ? ' and s.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.title', '', 'strval') == '' ? '' : ' and s.title like "%' . I('get.title', '', 'strval') . '%"';
		$where .= I('get.type', '-1', 'intval') == -1 ? '' : ' and s.type=' . I('get.type', -1, 'intval');
		$where .= I('get.status', '-1', 'intval') == -1 ? '' : ' and s.status=' . I('get.status', -1, 'intval');
		$list = $this->getList('s.aid,s.oid,s.sort,s.status,s.pics,s.type,s.size,s.title,s.desc,s.times,s.price,o.name,o.id as oid,a.name as area,a.id as aid,s.id', 'whwx_rental_service s,whwx_owner o,whwx_area a', $where, 'times desc,sort asc', true);
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 添加租售服务
	 * yaoyingli 2015年12月25日
	 */
	public function add(){
		if(IS_POST){
			$_POST['pics'] = implode(',', $_POST['pic']);
			$_POST['times'] = time();
			$_POST["oid"] = 0;
			$result = $this->updateData($_POST, 'rental_service');
			$this->returnResult($result);
		}else{
			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id desc');
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}

	/**
	 * 修改租售服务
	 * yaoyingli 2015年12月25日
	 */
	public function edit(){
		if(IS_POST){
			$_POST['pics'] = implode(',', $_POST['pic']);
			$result = $this->updateData($_POST, 'rental_service', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('title,id,sort,status,pics,type,size,desc,times,price,aid,address,floor_all,house,floor_several,room,hall,toilet', 'rental_service', 'id=' . $_GET['id']);
			$info['pics'] = explode(',', $info['pics']);
			$this->assign('info', $info);
			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id desc');
			$this->assign('areaList', $areaList);
			$this->display('add');
		}
	}

	/**
	 * 删除租售服务
	 * yaoyingli 2015年12月25日
	 */
	public function del(){
		$result = $this->deleteData('id=' . $_GET['id'], 'rental_service');
		$this->returnResult($result);
	}

	/**
	 * 租售意向显示消息内容和回复内容
	 * yaoyingli 2016年2月20日
	 */
	public function Indexdetail(){
		if(IS_AJAX){
			$info = $this->getInfo('s.id as id,s.price,s.size,s.title,s.type as style,s.pics,s.times,s.oid,s.address,s.desc as content,s.room,s.house,s.hall,s.toilet,s.floor_several,s.floor_all,o.id,o.name as owner,o.phone as tel,a.name as area', 'whwx_rental_service s,whwx_owner o,whwx_area a', 's.aid=a.id and s.oid=o.id and s.id=' . I('get.id', 0, intval));
			if(!empty($info['pics'])){
				$info['pics'] = explode(',', $info['pics']);
			}
			$this->assign('info', $info);
			$this->display();
		}
	}

	/**
	 * 设置租售服务意向管理
	 * yaoyingli 2015年12月28日
	 */
	public function intention(){
		$where = 'i.sid=s.id and s.oid=o.id and o.aid=a.id';
		$where .= I('get.aid') > 0 ? ' and o.aid=' . I('get.aid', 'intval') : '';
		$where .= I('get.id', 0, 'intval') > 0 ? ' and i.sid=' . I('get.id', 0, 'intval') : '';
		$where .= I('get.name', '', 'strval') == '' ? '' : ' and i.name like "%' . I('get.name', '', 'strval') . '%"';
		$where .= I('get.type', '-1', 'intval') == -1 ? '' : ' and s.type=' . I('get.type', -1, 'intval');
		$where .= I('get.status', '-1', 'intval') == -1 ? '' : ' and i.status=' . I('get.status', -1, 'intval');
		$list = $this->getList('i.id,i.sid,i.submit_time,i.phone,i.name,i.status,i.desc,s.type,s.oid,o.name as title,o.phone as ophone,a.name area', 'whwx_rental_service_intention i,whwx_rental_service s,whwx_owner o,whwx_area a', $where, 'submit_time desc', true);
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 删除租售服务意向管理
	 * yaoyingli 2015年12月25日
	 */
	public function IntentionDel(){
		$result = $this->deleteData('id=' . $_GET['id'], 'rental_service_intention');
		$this->returnResult($result);
	}

	/**
	 * 设置租售服务意向管理的状态
	 * yaoyingli 2015年12月30日
	 */
	public function setStatus2(){
		$status = empty($_GET['status']) ? 0 : I('get.status', 0, 'intval');
		$result = M("rental_service_intention")->where('id=' . $_GET['id'])->setField('status', $status);
		if($_GET['status'] >= 2){
			$info = $this->getInfo('sid', 'rental_service_intention', 'id=' . $_GET['id']);
			$_POST["status"] = 0;
			$result = $this->updateData($_POST, 'rental_service', 2, 'id=' . $info["sid"]);
		}
		$this->returnResult($result);
	}

	/**
	 * 租售意向显示消息内容和回复内容
	 * yaoyingli 2015年12月30日
	 */
	public function detail(){
		if(IS_POST){
			if($_POST['status'] != 2){
				if(empty($_POST['content'])){
					$this->ajaxReturn(array('status' => -1, 'info' => '请输入反馈内容!'));
				}
				$result = \Common\Api\CommonApi::addFollow(session('aid'), session('ainfo.name'), session('ainfo.tel'), 3, I('post.id', 0, 'intval'), $_POST['content']);
				if($result > 0){
					$this->ajaxReturn(array('status' => $result, 'info' => '添加成功'));
				}else{
					$this->ajaxReturn(array('status' => -1, 'info' => '添加失败'));
				}
			}
		}else{

			$info = $this->getInfo('s.id,s.title,s.type as style,s.pics,s.times,s.oid,o.id,o.name as owner,o.phone as tel,i.id as id,i.name,i.phone,i.sid,i.submit_time,i.desc,i.status', 'whwx_rental_service_intention i,whwx_rental_service s,whwx_owner o', 'i.sid=s.id and s.oid=o.id and i.id=' . I('get.id', 0, intval));
			if(!empty($info['pics'])){
				$info['pics'] = explode(',', $info['pics']);
			}
			$this->assign('info', $info);
			// 订单跟踪
			$list = $this->getList('id,oid,oname,otel,content,times', 'follow', 'type = 3 and typeid=' . I('get.id', 0, 'intval'), 'times asc');
			$this->assign('list', $list);
			$this->display();
		}
	}
}