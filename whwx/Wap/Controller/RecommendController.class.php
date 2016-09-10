<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 星房推荐
 * huying Mar 10, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class RecommendController extends WapController{
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
		$where = 'status = 1';
		$where .= session('fansInfo.aid') > 1 ? ' and aid = 0 or aid='.session('fansInfo.aid') : '';
		$list = $this->getList('id,name,aid,pic', 'recommend', $where, 'sort desc',true);
		foreach ($list as $k => $v){
			$list[$k]['area'] = $v['aid'] > 0 ? M('area')->where('id='.$v['aid'])->getField('name') : '全部小区';
		}
		$this->assign('list', $list);
		$this->display();
	}
	/**
	 * 详细情况
	 * huying Mar 10, 2016
	 */
	public function detail(){
		$info = $this->getInfo('id,name,desc,pic,tel', 'recommend', 'id='.I('get.id', 0, 'intval'));
		$this->assign('info', $info);
		$this->display();
	}
}