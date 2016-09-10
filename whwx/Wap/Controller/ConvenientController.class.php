<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 便民服务
 * huying Mar 10, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ConvenientController extends WapController{
	/**
	 * 首页
	 * yaoyongli 2016年1月7日
	 */
	public function index(){
		$aid = session('fansInfo.aid') > 0 ? session('fansInfo.aid') : 0 ;
		$info = M('area')->field('desc,name')->where('id='.$aid)->find();
		$this->assign('info', $info);
		$this->display();
	}
}