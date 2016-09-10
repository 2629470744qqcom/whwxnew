<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 红黑榜
 * huying Jan 18, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class BlackController extends WapController{
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
	 * 列表
	 * huying Jan 18, 2016
	 */
	public function index(){
		//获取红黑榜
		$where = 'status = 1';
		$where .= I('get.type', 0, 'intval') == 1 ? ' and type = 1' : ' and type != 1';
		$blackList = $this->getList('id,title,pic,type,day,zan', 'black_list', $where, 'sort desc',true);
		foreach ($blackList as $k => $v){
			$blackList[$k]['is_zan'] = M('black_zan')->where('fid='.session('fansInfo.id').' and bid='.$v['id'])->getField('id');
		}
		$this->assign('blackList', $blackList);
		$this->display();
	}
	/**
	 * 详细
	 * huying Jan 19, 2016
	 */
	public function cont(){
		$info = $this->getInfo('id,title,pic,type,day,zan,desc', 'black_list', 'id='.I('get.id', 0, 'intval'));
		$info['is_zan'] = M('black_zan')->where('fid='.session('fansInfo.id').' and bid='.$info['id'])->getField('id');
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 点赞
	 * huying Jan 19, 2016
	 */
	public function zan(){
		$id = M('black_zan')->where('bid='.I('post.bid', 0, 'intval').' and fid='.session('fansInfo.id'))->getField('id');
		if($id > 0){
			$this->ajaxReturn(array('status' => -1, 'info' => '你已经点过赞了'));
		}else{
			$result = $this->updateData(array('bid' => I('post.bid', 0, 'intval'), 'fid' => session('fansInfo.id'), 'times' => time()), 'black_zan');
			if($result !== false){
				M('black_list')->where('id='.I('post.bid', 0, 'intval'))->setInc('zan');
				$this->ajaxReturn(array('status' => 1, 'info' => '点赞成功'));
			}
		}
		$this->ajaxReturn(array('status' => -1, 'info' => '点赞失败，请稍后再试'));
	}
}