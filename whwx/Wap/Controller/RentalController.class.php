<?php
namespace Wap\Controller;
use Common\Controller\WapController;

/**
 * 租售服务
 * yaoyongli 2016年1月8日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class RentalController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}else{
			if(session('fansInfo.type') != 1 && ACTION_NAME != 'want'){
				$this->ajaxReturn(array('status' => -1, 'info' => '你没有权限'));
			}
		}
	}
	/**
	 * 首页,也是出租列表页
	 * yaoyongli 2016年1月8日
	 */
	public function index(){

		$where='(s.type=1 or s.type=3 or s.type=5) and s.aid=a.id and s.status=1';
		$where .= I('get.type', '0', 'intval') == 0 ? '' : ' and s.type=' . I('get.type', 0, 'intval');
		$where .= I('get.area', 0, 'intval') > 0 ? ' and a.id='.I('get.area', 0, 'intval') : '';
		$order='s.times desc';
		if($_GET['sort']==1){
			$order='s.price+0 asc,s.times desc';
		}else if($_GET['sort'] == 2){
			$order='s.price+0 desc,s.times desc';
		}else{
			$order='s.times desc';
		}
		$list=$this->getList('s.id as id,s.title,s.status,s.pics,s.type,s.size,s.price,s.aid,s.times,a.name as area, a.id as areaid', 'whwx_rental_service s,whwx_area a',$where, $order,true);
		foreach($list as $k => $v){
			if(!empty($v['pics'])){
				$list[$k]['pics'] = explode(',', $v['pics']);
			}
		}
		$this->assign('list',$list);
		$areaList = $this->getList('id,name', 'area', 'status = 1', 'id asc');
		$this->assign('areaList', $areaList);
		$shareJs = $this->getShareJs('出租出售列表业', C('share_pic'), U('Rental/index'), '出租出售列表业');
		$this->assign('shareJs', $shareJs);
		$this->display();
	}
	/**
	 * 租售服务的出售
	 * yaoyongli 2016年1月8日
	 */
	public function sell(){
		if(IS_POST){
			$_POST['times'] = time();
			$_POST['type'] = $_GET['type'];
			$_POST['pics'] = implode(',', $_POST['pic']);
			$_POST['oid'] = session('fansInfo.oid');
			$_POST['status']=1;
			$style = I('post.id', 0, intval) > 0 ? 2 : 1;
			$result = $this->updateData($_POST, 'rental_service', $style);
			$this->returnResult($result);
		}else{
			if(I('get.id',0,intval) >　0){
			$info=$this->getInfo('id,type,size,desc,price,room,hall,toilet,floor_several,floor_all,aid,address,pics,title,house', 'rental_service', 'id='.I('get.id',0,intval));
			if(!empty($info['pics'])){
				$info['pics'] = explode(',', $info['pics']);
			}
			$info['count'] = count($info['pics']);
			$this->assign('info',$info);
			$infoman=$this->getInfo('id,name,phone', 'owner', 'id='.session('fansInfo.oid'));
			$this->assign('infoman',$infoman);
			}
			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id asc');
			$this->assign('areaList', $areaList);
			$this->display();
		}
		// $this->display();
	}
	/**
	 * 租售服务的出租
	 * yaoyongli 2016年1月8日
	 */
	public function lease(){
		if(IS_POST){
			$_POST['times']=time();
			$_POST['oid'] = session('fansInfo.oid');
			$_POST['type']=$_GET['type'];
			$_POST['pics']=impload(',',$_POST['pic']);
			$_POST['status']=1;
			$style = I('post.id', 0, intval) > 0 ? 2 : 1;
			$result = $this->updateData($_POST, 'rental_service', $style);
			$this->returnResult($result);
		}else{
			if(I('get.id',0,intval) >　0){
				$info=$this->getInfo('id,type,size,desc,price,room,hall,toilet,floor_several,floor_all,aid,address,pics,title,house', 'rental_service', 'id='.I('get.id',0,intval));
				if(!empty($info['pics'])){
					$info['pics'] = explode(',', $info['pics']);
				}
				$info['count'] = count($info['pics']);
				$this->assign('info',$info);
			}
			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id asc');
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}
	/**
	 * 删除租售服务
	 * yaoyingli 2015年12月25日
	 */
	public function del(){
// 		$result = $this->deleteData('id=' . $_GET['id'], 'rental_service');
        $POST['status']=0;
        $result =$this->updateData($POST, 'rental_service',2,'id=' . $_GET['id']);
		$this->returnResult($result);
	}
	/**
	 * 租售服务的用户提交信息页面
	 * yaoyongli 2016年1月19日
	 */
	public function want(){
	if(IS_POST){
		$_POST['submit_time']=time();
		$_POST['status']=0;
		$_POST["type"]=5;
// 		存在反馈表的类型
        $info=$this->getInfo('id,phone', 'rental_service_intention', 'sid='.$_POST["sid"]);
        if($_POST["phone"]<>$info['phone']){
			$result=$this->updateData($_POST, 'rental_service_intention');
			$this->returnResult($result);
		   if($result !== false){
				$this->ajaxReturn(array('status' => 0, 'info' => '操作成功'));
			}else{
				$this->ajaxReturn(array('status' => -1, 'info' => '操作失败'));
			}
		}else{
			$this->ajaxReturn(array('status' => -1, 'info' => '已提交意向'));
		};
	}
		$this->display();
	}
	/**
	 * 租售服务的我的页面
	 * yaoyongli 2016年1月8日
	 */
	public function mine(){
		
		$list=$this->getList('id,type,title,times,status', 'rental_service', 'status =1 and oid='.session('fansInfo.oid'),'times desc',true);
		$this->assign('list',$list);
		$this->display();
	}
	/**
	 * 租售服务的列表详情页
	 * yaoyongli 2016年1月19日
	 */
   public function cont(){
   	
   	$info=$this->getInfo('s.oid,s.id as id,s.title,s.status,s.pics,s.size,s.price,s.aid,s.times,s.room,s.type,s.hall,s.toilet,s.size,s.floor_several,s.address,s.desc,s.floor_all,a.name as area, a.id as areaid,a.phone', 'whwx_rental_service s,whwx_area a', 's.aid =a.id and s.id='.I('get.id', 0, 'intval'));
	if(!empty($info['pics'])){
		$info['pics'] = explode(',', $info['pics']);
	}
   	$this->assign('info',$info);
   	$this->display();
   }
   
   /**
    * 出售列表页
    * yaoyongli 2016年1月19日
    */
   public function sell_index(){
        $where='(s.type=2 or s.type=4) and s.aid=a.id and s.status=1';
        $where .= I('get.type', '0', 'intval') == 0 ? '' : ' and s.type=' . I('get.type', 0, 'intval');
		$where .= I('get.area', 0, 'intval') > 0 ? ' and a.id='.I('get.area', 0, 'intval') : '';
		$order='s.times desc';
         if($_GET['sort']==1){
			$order='s.price+0 asc,s.times desc';
		}else{
			$order='s.price+0 desc,s.times desc';
		}
		$list=$this->getList('s.id as id,s.title,s.status,s.pics,s.type,s.size,s.price,s.aid,s.times,a.name as area, a.id as areaid', 'whwx_rental_service s,whwx_area a',$where, $order,true);
		foreach($list as $k => $v){
			if(!empty($v['pics'])){
				$list[$k]['pics'] = explode(',', $v['pics']);
			}
		}
		$this->assign('list',$list);
		$areaList = $this->getList('id,name', 'area', 'status = 1', 'id asc');
		$this->assign('areaList', $areaList);
		$this->display();
}
}