<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 论坛管理
 * huying Dec 23, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ForumController extends AdminController{
	/**
	 * 帖子列表显示
	 * huying Dec 26, 2015
	 */
	public function index(){
		$where = 'f.cate_id = p.id and o.id=f.fid';
		$where .= I('get.cate_id') && I('get.cate_id') > 0 ? ' and f.cate_id=' . I('get.cate_id', 0, 'intval') : '';
		$where .= I('get.title') && I('get.title') != '' ? ' and f.title like "%' . I('get.title') . '%"' : '';
		$where .= I('get.status', -1) > -1 ? ' and f.status =' . I('get.status') : ' and f.status = 1';
		$where .= I('get.hot', -1) > -1 ? ' and f.hot =' . I('get.hot') : '';
		$where .= I('get.top', -1) > -1 ? ' and f.top =' . I('get.top') : '';
		$where .= I('get.aid', -1) > 0 ? ' and o.aid =' . I('get.aid') : '';
		$list = $this->getList('f.id,f.fid,f.title,f.times,f.status,f.hot,f.top,f.sort,f.posts,f.zan,f.cate_id', 'whwx_forum as f,whwx_forum_plate as p,whwx_owner o', $where, 'f.times desc', true);
		$this->assign('list', $list);
		$cateList = $this->getList('id,name', 'forum_plate', 'status = 1', 'id desc');
		$this->assign('cateList', $cateList);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}
	/**
	 * 删除
	 * huying Dec 26, 2015
	 */
	public function del(){
		$result = M('forum')->where('id=' . I('get.id', 0, 'intval'))->setField('status', 0);
		if($result){
			$info = $this->getInfo('id,fid', 'forum', 'id='.I('get.id', 0, 'intval'));
			$pointInfo = $this->getInfo('id,point', 'point', 'oid = '.$info['fid'].' and act = 5 and act_id='.I('get.id', 0, 'intval'));
			if(!empty($pointInfo)){
				$this->changePoint($info['fid'], $pointInfo['point'], '管理员删除帖子', 9, I('get.id', 0, 'intval'), 0);
			}
		}
		$this->returnResult($result);
	}
	/**
	 * 帖子详情
	 * huying Jan 14, 2016
	 */
	public function detail(){
		$info = $this->getInfo('f.id,f.title,f.desc,f.pics,f.times,o.name,o.addr,o.pic,o.phone,o.nickname,p.id as pid,p.name as plate,a.name aname', 'whwx_forum as f,whwx_forum_plate as p, whwx_owner as o,whwx_area a', 'a.id=o.aid and o.id = f.fid and f.cate_id = p.id and f.id = '.I('post.id', 0, 'intval'));
		if(!empty($info['pics'])){
			$info['pics'] = explode(',', $info['pics']);
		}
		$this->assign('info', $info);
		$postList = $this->getList('p.id,p.desc,p.times,o.name,o.phone,o.addr,o.nickname,a.name aname', 'whwx_forum_post as p, whwx_owner as o,whwx_area a', 'a.id=o.aid and p.status = 1 and o.id = p.fid and tid='.I('post.id', 0, 'intval'), 'times asc');
		$this->assign('postList', $postList);
		$this->display();
	}
	/**
	 * 删除回复
	 * huying Dec 26, 2015
	 */
	public function delPost(){
		$result = M('forum_post')->where('id=' . I('get.id', 0, 'intval'))->setField('status', 0);
		if($result){
			$info = $this->getInfo('id,tid,fid', 'forum_post', 'id='.I('get.id', 0, 'intval'));
			$pointInfo = $this->getInfo('id,point', 'point', 'oid = '.$info['fid'].' and act = 6 and act_id='.I('get.id', 0, 'intval'));
			if(!empty($pointInfo)){
				$this->changePoint($info['fid'], $pointInfo['point'], '管理员删除回复', 9, I('get.id', 0, 'intval'), 0);
			}
			M('forum')->where('id='.$info['tid'])->setDec('posts');	
		}
		$this->returnResult($result);
	}
	/**
	 * 发帖
	 * huying Mar 8, 2016
	 */
	public function add(){
		if(IS_POST){
			$_POST['fid'] = 0;
			$_POST['times'] = time();
			$_POST['pics'] = implode(',', $_POST['pic']);
			$result = $this->updateData($_POST, 'forum');
			$this->returnResult($result);
		}else{
			$list = $this->getList('id,name', 'forum_plate', 'status = 1', 'sort desc');
			$this->assign('plateList', $list);
			$this->display();
		}
	}
	/**
	 * 修改
	 * huying Mar 8, 2016
	 */
	public function edit(){
		if(IS_POST){
			$_POST['pics'] = implode(',', $_POST['pic']);
			$result = $this->updateData($_POST, 'forum', 2);
			$this->returnResult($result);
		}else{
			$list = $this->getList('id,name', 'forum_plate', 'status = 1', 'sort desc');
			$this->assign('plateList', $list);
			$info = $this->getInfo('id,title,desc,pics,cate_id', 'forum', 'id='.I('get.id', 0, 'intval'));
			if(!empty($info['pics'])){
				$info['pics'] = explode(',', $info['pics']);
			}
			$this->assign('info', $info);
			$this->display('add');
		}
	}
	/**
	 * 发表回复
	 * huying Mar 8, 2016
	 */
	public function post(){
		$_POST['fid'] = 0;
		$_POST['times'] = time();
		$result = $this->updateData($_POST, 'forum_post');
		if($result > 0){
			M('forum')->where('id='.I('post.tid', 0, 'intval'))->setInc('posts');
		}
		$this->returnResult($result);
	}
	/**
	 * 置顶热帖
	 * huying Mar 8, 2016
	 */
	public function setStatus(){
		$status = I('get.status', 0, 'intval') == 0 ? 1 : 0;
		$field = I('get.type');
		$result = M('forum')->where('id=' . I('get.id', 0, 'intval'))->setField($field, $status);
		$this->returnResult($result);
	}
}