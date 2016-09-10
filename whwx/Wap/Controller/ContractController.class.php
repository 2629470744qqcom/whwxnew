<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 合同
 * 姚永俐 2016.3.5
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ContractController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}
	/**
	 * 合同
 	 * 姚永俐 2016.3.5
	 */
	public function index(){
		$this->display();
	}
}