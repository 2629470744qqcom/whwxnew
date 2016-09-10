<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 粉丝管理
 * zhangxinhe Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WxfansController extends AdminController{
	/**
	 * 粉丝列表
	 * zhangxinhe Dec 28, 2015
	 */
	public function index(){
		$where = 'status=1';
		$where .= $_GET['nickname'] ? ' and nickname like "%' . $_GET['nickname'] . '%"' : '';
		$where .= $_GET['type'] > -1 ? ' and type=' . $_GET['type'] : '';
		$list = $this->getList('type,oid,openid,nickname,sex,province,city,country,active_time,subscribe_time,type', 'wxfans', $where, 'subscribe_time desc', true);
		foreach($list as $k => $v){
			if($v['type'] == 1 and $v['oid'] > 0){
				$list[$k]['ownerInfo'] = M()->table('whwx_owner o,whwx_area a')->field('a.name aname,o.name,o.phone,o.addr')->where('o.aid=a.id and o.id=' . $v['oid'])->find();
			}
		}
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 刷新粉丝信息
	 * zhangxinhe Jan 25, 2016
	 */
	public function refreshInfo(){
		$openid = I('get.id', null);
		if ($openid){
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			$fansInfo = $wechatAuth->userInfo($openid);
			$result = M('wxfans')->where(array('openid' => $openid))->save($fansInfo);
			$this->returnResult($result);
		}
		$this->returnResult(false);
	}
}