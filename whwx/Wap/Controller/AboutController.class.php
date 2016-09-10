<?php
namespace Wap\Controller;
use Common\Controller\WapController;

/**
 * 关于伟星
 * yaoyongli 2016年1月4日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class AboutController extends WapController{
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
		$shareJs = $this->getShareJs('关于伟星', C('share_pic'), U('About/index'), '伟星的详细介绍');
		$this->assign('shareJs', $shareJs);
		$this->display();
	}
	/**
	 * 幻灯片的详细信息
	 * huying Feb 24, 2016
	 */
	public function detail(){
		$type = I('get.type', 1 , 'intval');
		switch($type){
			case 1:
				$info = $this->getInfo('id,title,pic,desc', 'slide', 'id='.I('get.id', 0, 'intval'));
				break;
			case 2:
				$info = $this->getInfo('id,title,pic,desc', 'merchant_slide', 'id='.I('get.id', 0, 'intval'));
				break;
		}
		$this->assign('info', $info);
		$this->display();
	}
}