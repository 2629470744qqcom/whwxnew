<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 手机端首页
 * zhangxinhe Dec 31, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class IndexController extends WapController{
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
	 * zhangxinhe Dec 31, 2015
	 */
	public function index(){
		//获取幻灯片
		$where = 'status = 1';
		$where .= session('fansInfo.type') == 1 ? ' and aids like "%,'.session('fansInfo.aid').',%"' : '';
		$slideList = $this->getList('id,pic,url', 'slide', $where, 'sort desc',true, 6);
		$this->assign('slideList', $slideList);
		if(session('fansInfo.type') == 1){
			//获取是否有未读消息
			$notice = $this->getList('id,title,desc,type,typeid', 'owner_notice', ' status = 1 and oid='.session('fansInfo.oid'), 'times desc');
			$this->assign('notice', $notice);
		}
		//获取板块
		
		$plateList = $this->getList('id,type,pic,link,name,look', 'plate', 'status = 1', 'type asc,sort desc');
		$this->assign('plateList', $plateList);
		if(session('fansInfo.oid')){
			$count['notice'] = M('owner_notice')->where('status=1 and oid='.session('fansInfo.oid'))->count();
			$this->assign('count', $count);
		}
		$this->display();
	}
	/**
	 * 星管家
	 * huying Jan 18, 2016
	 */
	public function butler(){
		//获取业主所在小区的星管家
		$owner = $this->getInfo('o.bid,o.aid,a.phone as aphone', 'whwx_owner as o, whwx_area as a', 'o.aid = a.id and o.id = '.session('fansInfo.oid'));
		$owner['service'] = $this->getInfo('name,phone,pic,desc', 'service', 'status=1 and bids like "%,'.$owner['bid'].',%"');
		$this->assign('owner', $owner);
		$this->display();
	}
}