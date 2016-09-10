<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 星房推荐
 * huying Mar 10, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class RecommendController extends AdminController{
	/**
	 * 星房推荐列表
	 * huying Jan 23, 2016
	 */
	public function index(){
		$where = ' 1 = 1';
		$where .= I('get.status', -1, 'intval') == -1 ? '' : ' and status=' . I('get.status', -1, 'intval');
		// 		小区筛选
		if(I('get.aid', -1, 'intval') <> -1){
			if(I('get.aid', -1, 'intval') == 0){
				$where .=' and  aid =0';
			}else{
				$where .=' and aid='.I('get.aid', 0, 'intval');
			}
		}
		$list = $this->getList('id,name,aid,tel,sort,status', 'recommend', $where, 'id desc', true);
		foreach ($list as $k => $v){
			$list[$k]['area'] = $v['aid'] > 0 ? M('area')->where('id='.$v['aid'])->getField('name') : '全部小区';
		}
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}
	/**
	 * 添加
	 * huying Mar 10, 2016
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'recommend');
			$this->returnResult($result);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}
	/**
	 * 修改
	 * huying Mar 10, 2016
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'recommend', 2);
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,name,pic,desc,aid,tel,sort,status', 'recommend', 'id='.I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display('add');
		}
	}
	/**
	 * 删除
	 * huying Mar 10, 2016
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'recommend');
		$this->returnResult($result);
	}
}