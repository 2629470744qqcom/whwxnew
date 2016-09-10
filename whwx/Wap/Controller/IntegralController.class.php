<?php
namespace Wap\Controller;
use Common\Controller\WapController;
/**
 * 积分
 * huying Jan 21, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class IntegralController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}
	/**
	 * 积分变化列表
	 * huying Jan 21, 2016
	 */
	public function index(){
		$info = $this->getInfo('id,name,phone,pic,point', 'owner', 'id='.session('fansInfo.oid'));
		$this->assign('info', $info);
		$list = $this->getList('id,point,times,name,act,type', 'point', 'oid='.session('fansInfo.oid'), 'times desc');
		foreach ($list as $k => $v){
			if($v['times'] >= strtotime(date('Y-m-01'))){//本月
				$data['this'][] = $v;
			}else if($v['times'] > (strtotime(date('Y').'-'.(date('m')-1).'-01'))){//上月
				$data['last'][] = $v;
			}else{//其余
				$data['other'][] = $v;
			}
		}
		$this->assign('data', $data);
		$this->display();
	}
	/**
	 * 积分详情
	 * huying Jan 21, 2016
	 */
	public function cont(){
		$info = $this->getInfo('id,point,times,name,act,type', 'point', 'id='.I('get.id', 0, 'intval'));
		$this->assign('info', $info);
		$this->display();
	}
}