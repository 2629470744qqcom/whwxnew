<?php
namespace Wap\Controller;
use Common\Controller\WapController;

/**
 *周边商家
 * yaoyongli 2016年1月4日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ShopController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Index/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}else{
			if(session('fansInfo.type') != 1){
				$this->ajaxReturn(array('status' => -1, 'info' => '你没有权限'));
			}
		}
	}
	/**
	 * 首页
	 * huying Jan 8, 2016
	 */
	public function index(){
		if(session('fansInfo.type') == 1){
			//获取业主所在小区
			$areaInfo = $this->getInfo('o.aid,a.name', 'whwx_owner as o,whwx_area as a', 'o.aid = a.id and o.id='.session('fansInfo.oid'));
			$this->assign('areaInfo', $areaInfo);
			$where = ' and (aid = 0 or aid='.$areaInfo["aid"].')';
		}
		//获取分类
		$typeList = $this->getList('id,name,pic', 'merchant_type', 'status = 1', 'sort desc', false, 8);
		$this->assign('typeList', $typeList);
		//获取活动
		$activeList = $this->getList('id,pic', 'merchant_active', 'status = 1' . $where, 'sort desc', false);
		$this->assign('activeList', $activeList);
		//获取轮播图片
		$slideList = $this->getList('id,url,pic', 'merchant_slide', 'status = 1' . $where, 'sort desc', false);
		$this->assign('slideList', $slideList);
		$this->display();
	}
	/**
	 * 商家详情
	 * huying Jan 8, 2016
	 */
	public function cont(){
		$info = $this->getInfo('id,mapx,mapy,tel,name,pic,desc,address', 'merchant', 'id='.I('get.id', 0, 'intval'));
		$this->assign('info', $info);
		$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
		$signPackage = $wechatAuth->getJsSignPackage();
		$addressJs = '<script>wx.config({debug:false, appId:"wx67e0ba62919fcc84", timestamp:'.$signPackage["timestamp"].', nonceStr:"'.$signPackage["nonceStr"].'", signature:"'.$signPackage["signature"].'", jsApiList:["openLocation"]});</script>';
		$this->assign('addressJs', $addressJs);
		$this->display();
	}
	/**
	 * 分类下的商家列表
	 * huying Jan 8, 2016
	 */
	public function lists(){
		//获取分类详情
		$typeInfo = $this->getInfo('name,pic', 'merchant_type', 'id='.I('get.id', 0, 'intval'));
		$this->assign('typeInfo', $typeInfo);
		//获取该分类下的商家
		if(session('fansInfo.type') == 1){
			$where = ' and (aid=0 or aid='.session('fansInfo.aid').')';
		}
		$list = $this->getList('id,name,pic', 'merchant', 'status = 1 and type_id='.I('get.id', 0, 'intval') . $where, 'sort desc',true);
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 活动详情
	 * huying Feb 24, 2016
	 */
	public function detail(){
		$info = $this->getInfo('id,title,pic,desc,start_time,end_time', 'merchant_active', 'id='.I('get.id', 0, 'intval'));
		$this->assign('info', $info);
		$shareJs = $this->getShareJs($info['title'], $info['pic'], U('Shop/detail?id='.I('get.id', 0, 'intval')), $info['desc']);
		$this->assign('shareJs', $shareJs);
		$this->display();
	}
}
