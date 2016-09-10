<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 装修申请
 * yaoyongli 2016年1月4日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ApplyController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}
	/**
	 * 首页
	 * yaoyongli 2016年1月7日
	 */
	public function index(){
		//获取业主的信息
		$ownerInfo = $this->getInfo('o.id,o.name,o.pid,o.phone,r.size,r.id rid,o.addr,a.decorate,a.name as area', 'whwx_owner as o, whwx_room as r, whwx_area as a', 'o.aid = a.id and o.rid = r.id and o.id='.session('fansInfo.oid'));
		if($ownerInfo){
			//判断有没有申请过
			$decorate = $this->getInfo('id,status,times,money,id_pic,company_pic,design_pic', 'decorate', 'rid=' . $ownerInfo['rid']);
			$this->assign('decorate', $decorate);
			$ownerInfo['decorate'] = $decorate['money'] > 0 ? $decorate['money'] : round($ownerInfo['size'] * $ownerInfo['decorate']);
			$this->assign('owner', $ownerInfo);
			$this->display();
		}
	}
	/**
	 * 申请
	 * huying Jan 18, 2016
	 */
	public function apply(){
		if(empty($_POST['id_pic'])){
			$this->ajaxReturn(array('status' => -1, 'info' => '请上传身份证照片'));
		}
		if($_POST['id'] > 0){
			$_POST['status'] = 0;
			$result = $this->updateData($_POST, 'decorate', 2);
			$result2 = M('payment')->where('type = 4 and typeid = '.I('post.id', 0, 'intval').' and oid='.session('fansInfo.oid'))->getField('id');
		}else{
			$ownerInfo = $this->getInfo('o.id,o.name,o.pid,o.phone,r.size,r.id as rid,o.addr,a.decorate,a.name as area', 'whwx_owner as o, whwx_room as r, whwx_area as a', 'o.aid = a.id and o.rid = r.id and o.id='.session('fansInfo.oid'));
			$_POST['money'] = round($ownerInfo['size'] * $ownerInfo['decorate']);
			$_POST['rid'] = $ownerInfo['rid'];
			$_POST['times'] = time();
			$_POST['name'] = $ownerInfo['name'];
			$_POST['phone'] = $ownerInfo['phone'];
			$_POST['aid'] = session('fansInfo.aid');
			$result = $this->updateData($_POST, 'decorate');
			if($result > 0){
				$data = array('oid' => session('fansInfo.oid'), 'aid' => session('fansInfo.aid'), 'status' => 1, 'money' => $_POST['money'], 'creat_time' => time(), 'type' => 4, 'typeid' => $result, 'remark' => '装修垃圾清理费');
				$result2 = $this->updateData($data, 'payment');
			}
		}
		if($result !== false && $result2 > 0){
			$this->ajaxReturn(array('status' => 1, 'info' => '申请成功', 'pid' => $result2));
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '申请失败'));
	}
}