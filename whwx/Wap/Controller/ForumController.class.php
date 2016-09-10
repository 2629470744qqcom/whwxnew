<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 讨论论坛
 * 手机端登录页面
 * yaoyongli 2016年1月4日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ForumController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}else{
			if(session('fansInfo.type') != 1 && ACTION_NAME != 'zan'){
				$this->ajaxReturn(array('status' => -1, 'info' => '你没有权限'));
			}
		}
	}
	/**
	 * 首页
	 * huying 2016年1月4日
	 */
	public function index(){
		//获取板块
		$plateList = $this->getList('id,name', 'forum_plate', 'status = 1', 'sort desc');
		$this->assign('plateList', $plateList);
		$where = 'o.id = f.fid and f.cate_id = p.id and f.status=1';
		$where .= I('get.pid', 0, 'intval') > 0 ? ' and f.cate_id = '.I('get.pid', 0, 'intval') : '' ;
		$order = I('get.pid', 0, 'intval') > 0 ? 'f.top desc,f.hot desc,f.sort desc,f.times desc' : 'f.sort desc,f.times desc';
		$list = $this->getList('f.id,f.title,f.pics,f.times,f.status,f.hot,f.top,f.posts,f.views,f.zan,o.id as oid,o.nickname as name,o.pic,p.id as pid,p.name as plate', 'whwx_forum as f,whwx_forum_plate as p, whwx_owner as o', $where, $order, true);
		foreach ($list as $k => $v){
			if(!empty($v['pics'])){
				$list[$k]['pics'] = explode(',', $v['pics']);
			}
		}
		$this->assign('list', $list);
		$title = I('get.pid', 0, 'intval') > 0 ? $list[0]['plate'].'板块' : '伟星论坛最新贴' ;
		$shareJs = $this->getShareJs($title, C('share_pic'), U('Forum/index?pid='.I('get.pid', 0, 'intval')), $title);
		$this->assign('shareJs', $shareJs);
		$this->display();
	}
	/**
	 * 我的回复&我的帖子
	 * huying Jan 7, 2016
	 */
	public function card(){
		if(I('get.type', 0, 'intval') == 1){
			$postList = $this->getList('f.id,f.title,p.times,p.desc,p.status,f.status as forumstatus', 'whwx_forum as f,whwx_forum_post as p', 'f.id = p.tid and p.fid='.session('fansInfo.oid'), 'p.times desc', true);
			$this->assign('postList', $postList);
		}else{
			$threadList = $this->getList('f.id,f.title,f.times,f.status,p.id as pid,p.name as plate', 'whwx_forum as f,whwx_forum_plate as p', 'f.cate_id = p.id and f.fid='.session('fansInfo.oid'), 'f.times desc', true);
			$this->assign('threadList', $threadList);
		}
		$this->display();
	}
	/**
	 * 帖子详情
	 * huying Jan 7, 2016
	 */
	public function details(){
		$info = $this->getInfo('f.id,f.title,f.desc,f.pics,f.times,f.zan,f.hot,f.top,o.nickname as name,o.id as oid,o.pic,p.id as pid,p.name as plate', 'whwx_forum as f,whwx_forum_plate as p, whwx_owner as o', 'o.id = f.fid and f.cate_id = p.id and f.id = '.I('get.id', 0, 'intval'));
		if(!empty($info['pics'])){
			$info['pics'] = explode(',', $info['pics']);
		}
		$shareJs = $this->getShareJs($info['title'], empty($info['pics'][0]) ? C('share_pic') :$info['pics'][0] , U('Forum/details?id='.I('get.id', 0, 'intval')), $info['desc']);
		$this->assign('shareJs', $shareJs);
		$this->assign('info', $info);
		$postList = $this->getList('p.desc,p.times,o.nickname as name, o.id as oid,o.pic', 'whwx_forum_post as p, whwx_owner as o', 'p.status = 1 and o.id = p.fid and tid='.I('get.id', 0, 'intval'), 'times asc', true, 10);
		$this->assign('postList', $postList);
		$this->display();
	}
	/**
	 * 发布帖子
	 * huying Jan 7, 2016
	 */
	public function release(){
		if(IS_POST){
			$_POST['desc'] = preg_replace('/\[em_(\d+)\]/', '<img src="/Public/Wap/face/$1.gif" class="face" border="0" />', $_POST['desc']);
			$_POST['fid'] = session('fansInfo.oid');
			$_POST['times'] = time();
			$_POST['pics'] = implode(',', $_POST['pic']);
			$result = $this->updateData($_POST, 'forum');
			if($result !== false){
				if(C('post') > 0){//送积分
					//获取该业主的今天发帖获得的积分
					$total = M('point')->where('oid='.session('fansInfo.oid').' and type =1 and act = 5 and times >'.strtotime(date('Ymd')))->getField('sum(point)');
					if(C('max_post') > $total || C('max_post') == 0){
						$point = (C('max_post') == 0 || (C('max_post')-$total > C('post'))) ? C('post') : (C('max_post')-$total);
						$this->changePoint(session('fansInfo.oid'), $point, '发布帖子', 5, $result);
					}
				}
			}
			$this->returnResult($result, null, U('Forum/details?id='.$result));
		}else{
			$plateList = $this->getList('id,name', 'forum_plate', 'status = 1', 'sort desc');
			$this->assign('plateList', $plateList);
			$this->assign('pics', explode(',', $_GET['pics']));
			$this->display();
		}
	}
	/**
	 * 随手拍
	 * huying Jan 19, 2016
	 */
	public function random(){
		$plateList = $this->getList('id,name', 'forum_plate', 'status = 1', 'sort desc');
		$this->assign('plateList', $plateList);
		$this->assign('pics', explode(',', $_GET['pics']));
		$this->display();
	}
	/**
	 * 发表回复
	 * huying Jan 7, 2016
	 */
	public function addPost(){
		if(IS_POST){
			$_POST['fid'] = session('fansInfo.oid');
			$_POST['times'] = time();
			$result = $this->updateData($_POST, 'forum_post');
			if($result !== false){
				if(C('reply') > 0){//送积分
					//获取该业主的今天发帖获得的积分
					$total = M('point')->where('oid='.session('fansInfo.oid').' and type =1 and act = 6 and times >'.strtotime(date('Ymd')))->getField('sum(point)');
					if(C('max_reply') > $total || C('max_reply') == 0){
						$point = (C('max_reply') == 0 || (C('max_reply')-$total > C('reply'))) ? C('reply') : (C('max_reply')-$total);
						$this->changePoint(session('fansInfo.oid'), $point, '发表回复', 6, $result);
					}
				}
				M('forum')->where('id = '.I('post.tid'))->setInc('posts');
			}
			$this->ajaxReturn($result);
		}
	}
	/**
	 * 七牛抓取微信图片
	 * huying Jan 20, 2016
	 */
	public function downPic(){
		$pics = explode(',', $_POST['pics']);
// 		$path = '/whwx/Runtime/Temp/';
		foreach ($pics as $k => $v){
			if(!empty($v)){
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				$access_token = F('access_token');
				$url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token='.$access_token['access_token'].'&media_id='.$v;
				$pic = qiniuFetch($url);
				$list[] = $pic;
			}
		}
		$this->ajaxReturn($list);
	}
	/**
	 * 添加浏览量
	 * huying Mar 12, 2016
	 */
	public function setView(){
		if(!cookie('forum'.I('get.id', 0, 'intval'))){
			M('forum')->where('id='.I('get.id', 0, 'intval'))->setInc('views');
			cookie('forum'.I('get.id', 0, 'intval'), I('get.id', 0, 'intval'), 12*60*60);
		}
	}

	public function zan(){
		$id = I('get.id', 0, 'intval');
		if(!cookie('forum_zan_'.$id)){
			$result = M('forum')->where('id='.$id)->setInc('zan');
			cookie('forum_zan_'.$id, $id, strtotime(date('Y-m-d', strtotime('+1 day'))) - time());
			$this->returnResult($result);
		}
		$this->returnResult(false);
	}
}
