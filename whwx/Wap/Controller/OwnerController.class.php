<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 业主管辖
 * huying Jan 22, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class OwnerController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}
	/**
	 * 亲友/租客
	 * huying Jan 22, 2016
	 */
	public function index(){
		$list = $this->getList('id,pic,status,name,phone,pic', 'owner', 'status != 2 and pid='.session('fansInfo.oid'));
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 亲友/租客详情
	 * huying Jan 22, 2016
	 */
	public function cont(){
		if(I('get.typeid', 0, 'intval') > 0){
			M('owner_notice')->where('id='.I('get.typeid', 0, 'intval'))->setField('status', 0);
		}
		$info = $this->getInfo('o.id,o.name,o.pic,o.phone,n.times,o.status', 'whwx_owner as o, whwx_owner_notice as n', 'o.status = 1 and n.type=3 and n.typeid = o.id and o.id='.I('get.id', 0, 'intval'));
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 删除
	 * huying Jan 22, 2016
	 */
	public function del(){
		$result = M('owner')->where('id in ('.$_POST['ids'].')')->setField('status', 2);
		$this->returnResult($result);
	}
	/**
	 * 审核业主
	 * huying Jan 22, 2016
	 */
	public function check(){
		$result = M('owner')->where('id = '.I('post.id', 0, 'intval'))->setField('status', I('post.status', 0, 'intval'));
		if($result !== false){
			$owner = $this->getInfo('rid,fid', 'owner', 'id='.I('post.id', 0, 'intval'));
			$this->updateData(array('id' => $owner['fid'], 'type' => 1, 'oid' => I('post.id', 0, 'intval')), 'wxfans', 2);
		}
		$this->returnResult($result);
	}
}