<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 通知公告
 * yaoyongli 2016年1月4日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class NoticeController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}
	/**
	 * 列表
	 * huying Jan 13, 2016
	 */
	public function index(){
		$list = $this->getList("id,oid,desc,times,type,title,typeid,status", "owner_notice", 'status < 2 and oid='.session('fansInfo.oid'), "times desc", true);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 删除
	 * huying Jan 13, 2016
	 */
	public function del(){
		$result = M('owner_notice')->where('id in ('.$_POST['ids'].')')->setField('status', 2);
		$this->returnResult($result);
	}
	/**
	 * 系统发布的通知
	 * huying Jan 22, 2016
	 */
	public function notice(){
		if(IS_POST){
			$result = M('owner_notice')->where('type = 1 and typeid='.I('post.id', 0, 'intval').' and oid='.session('fansInfo.oid'))->setField('status', 0);
			if($result > 0 && I('post.status') == 1){
				M('notice')->where('id='.I('post.id', 0, 'intval'))->setInc('look');
			}
		}else{
			$info = $this->getInfo('id,status,title,desc,abstract,times', 'notice', 'id='.I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$this->display();
		}
	}
}