<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 社区活动
 * huying Jan 23, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class CommunityController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}else{
			if(session('fansInfo.type') != 1){
				$this->ajaxReturn(array('status' => -1, 'info' => '你没有权限'));
			}
		}
	}
	/**
	 * 首页
	 * huying Jan 23, 2016
	 */
	public function index(){
		$where = 'status = 1';
		$where .= session('fansInfo.aid') > 0 ? ' and (aids = 0 or aids ='.session('fansInfo.aid') . ')' : '';
		$list = $this->getList('id,pic,title,times,address,start_time,end_time,sign_num', 'community', $where, 'sort desc, id desc',true);
		foreach ($list as $k => $v){
			$is_sign = M('community_sign')->where('status = 1 and aid = '.$v['id'].' and oid='.session('fansInfo.oid'))->getField('id');
			if($v['end_time'] < time()){
				$list[$k]['state'] = 3;//已结束
			}else if($v['start_time'] > time()){
				$list[$k]['state'] = 2;//未开始
			}else if($is_sign > 0){
				$list[$k]['state'] = 1;//已报名
			}else{
				$list[$k]['state'] = 4;//进行中
			}
		}
		$this->assign('list', $list);
		$shareJs = $this->getShareJs('伟星社区活动列表', C('share_pic'), U('Comminity/index'), '伟星社区活动列表');
		$this->assign('shareJs', $shareJs);
		$this->display();
	}
	/**
	 * 社区游戏
	 * huying Jan 23, 2016
	 */
	public function game(){
		$shareJs = $this->getShareJs('伟星社区社区', C('share_pic'), U('Comminity/game'), '伟星社区社区');
		$this->assign('shareJs', $shareJs);
		$this->display();
	}
	/**
	 * 活动详情
	 * huying Jan 23, 2016
	 */
	public function details(){
		$info = $this->getInfo('id,pic,title,times,address,desc,start_time,end_time,sign_num', 'community', 'id='.I('get.id', 0, 'intval'));
		$shareJs = $this->getShareJs($info['title'], $info['pic'], U('Comminity/detail?id='.I('get.id', 0, 'intval')), $info['desc']);
		$this->assign('shareJs', $shareJs);
		$is_sign = M('community_sign')->where('status = 1 and aid = '.$info['id'].' and oid='.session('fansInfo.oid'))->getField('id');
		if($info['end_time'] < time()){
			$info['state'] = 3;//已结束
		}else if($info['start_time'] > time()){
			$info['state'] = 2;//未开始
		}else if($is_sign > 0){
			$info['state'] = 1;//已报名
		}else{
			$info['state'] = 4;//进行中
		}
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 报名参加活动
	 * huying Jan 7, 2016
	 */
	public function sign(){
		$id = I('post.id', 0, 'intval');
		if($id > 0){
			$info = $this->getInfo('id,num,limit_type,sign_num,start_time,end_time', 'community', 'id='.$id);
			if($info['start_time'] > time()){
				$this->ajaxReturn(array('status' => -1, 'info' => '报名时间还没到'));
			}
			if($info['end_time'] < time()){
				$this->ajaxReturn(array('status' => -1, 'info' => '报名时间已经结束'));
			}
			if(($info['limit_type'] == 1 && $info['sign_num'] < $info['num']) || $info['limit_type'] == 0){
				$signInfo = $this->getInfo('id,status', 'community_sign', 'aid = '.$id.' and oid='.session('fansInfo.oid'));
				if($signInfo['id'] > 0){
					if($signInfo['status'] == 1){
						$this->ajaxReturn(array('status' => -1, 'info' => '你已经报过名了'));
					}else{
						$result = M('community_sign')->where('id='.$signInfo['id'])->setField('status', 1);
					}
				}else{
					$owner = $this->getInfo('name,phone', 'owner', 'id='.session('fansInfo.oid'));
					if($owner){
						$result = $this->updateData(array('aid' => $id, 'name' => $owner['name'], 'tel' => $owner['phone'], 'oid' => session('fansInfo.oid'), 'aids' => session('fansInfo.aid'), 'times' => time(), 'status' => 1), 'community_sign');
					}
				}
				if($result > 0){
					M('community')->where('id='.$id)->setInc('sign_num');
					$this->ajaxReturn(array('status' => 1, 'info' => '报名成功'));
				}
			}
			$this->ajaxReturn(array('status' => -1, 'info' => '报名已达到限制'));
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '参数错误'));
	}
	/**
	 * 取消报名
	 * huying Jan 7, 2016
	 */
	public function signCancel(){
		$id = I('post.id', 0, 'intval');
		if($id > 0){
			$signId = M('community_sign')->where('aid = '.$id.' and oid='.session('fansInfo.oid'))->getField('id');
			if($signId > 0){
				$result = M('community_sign')->where('id='.$signId)->setField('status', 0);
			}
			if($result !== false){
				M('community')->where('id='.$id)->setDec('sign_num');
				$this->ajaxReturn(array('status' => 1, 'info' => '取消成功'));
			}
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '参数错误'));
	}
}