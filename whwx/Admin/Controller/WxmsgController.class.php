<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 微信消息管理
 * zhangxinhe Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WxmsgController extends AdminController{

	/**
	 * 消息列表
	 * zhangxinhe Dec 28, 2015
	 */
	public function index(){
		$where = 'm.type<>2 and m.fid=f.id';
		$where .= $_GET['id'] ? ' and m.id>' . $_GET['id'] : '';
		$where .= $_GET['nickname'] ? ' and f.nickname like "%' . $_GET['nickname'] . '%"' : '';
		$where .= $_GET['content'] ? ' and m.content like "%' . $_GET['content'] . '%"' : '';
		$where .= $_GET['type'] == 2 ? ' and m.star=1' : '';
		$where .= $_GET['type'] > -1 && $_GET['type'] < 2 ? ' and m.type=' . $_GET['type'] : '';
		$list = $this->getList('f.nickname,f.type ftype,f.oid,m.id,m.fid,m.type,m.status,m.times,m.star,m.content', 'whwx_wxfans f,whwx_wxmsg m', $where, 'm.times desc,id desc', true);
		if(IS_AJAX){
			$this->ajaxReturn(count($list));
		}else{
			foreach($list as $k => $v){
				if($v['ftype'] == 1 and $v['oid'] > 0){
					$list[$k]['ownerInfo'] = M()->table('whwx_owner o,whwx_area a')->field('a.name aname,o.name,o.phone,o.addr')->where('o.aid=a.id and o.id=' . $v['oid'])->find();
				}
			}
			$this->assign('list', $list);
			$this->display();
		}
	}

	/**
	 * 显示消息内容和回复内容
	 * zhangxinhe Dec 28, 2015
	 */
	public function showMsgInfo(){
		if(IS_POST){
			M('wxmsg')->where('id=' . I('post.mid', 0, 'intval'))->setField('status', 1);
			$_POST['type'] = 2;
			$_POST['times'] = time();
			$result = $this->updateData($_POST, 'wxmsg');
			if($result){
				$openid = M('wxfans')->where(array('id' => I('post.fid', 0, 'intval')))->getField('openid');
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				$wechatAuth->sendMsg($openid, $_POST['content']);
			}
			$this->returnResult($result);
		}else{
			$list = M()->table('whwx_wxfans f,whwx_wxmsg m')->field('f.nickname,m.content,m.times,m.type')->where('m.fid=f.id and m.fid=' . $_GET['fid'])->order('m.times desc')->limit(30)->select();
			foreach($list as $k => $v){
				$list[$k]['times'] = date('Y-m-d H:i', $v['times']);
				$list[$k]['nickname'] = $v['type'] == 2 ? C('site_name') : $v['nickname'];
			}
			$this->ajaxReturn(array_reverse($list));
		}
	}

	/**
	 * 设置星标消息（收藏）
	 * zhangxinhe Dec 28, 2015
	 */
	public function setStar(){
		$star = I('get.star', 0, 'intval');
		$star = $star == 1 ? 0 : 1;
		$result = M('wxmsg')->where(array('id' => I('get.id', 0, 'intval')))->setField('star', $star);
		$this->returnResult($result);
	}
}