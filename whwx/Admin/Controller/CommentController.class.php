<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 评论管理
 * huying Dec 31, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class CommentController extends AdminController{
	/**
	 * 显示评论列表
	 * huying Dec 31, 2015
	 */
	public function index(){
		$type = I('get.type', 1, 'intval');
		$where = 'r.aid = a.id and c.rid = r.id and c.type = '.$type.' and a.id in(0,'.session('ruleInfo.aids').')';
		$where .= I('get.status', -1) > -1 ? ' and c.status = '.I('get.status') : '';
		$where .= I('get.aid', 0, 'intval') > 0 ? ' and r.aid=' . I('get.aid', 0, 'intval') : '';
		$where .= I('get.phone') && I('get.phone') != '' ? ' and r.phone like "%' . I('get.phone') . '%"' : '';
		$where .= I('get.name') && I('get.name') != '' ? ' and r.owner like "%' . I('get.name') . '%"' : '';
		$where .= I('get.cate', 0, 'intval') > 0 ? ' and r.cate=' . I('get.cate', 0, 'intval') : '';
		$where .= I('get.cate_id', 0, 'intval') > 0 ? ' and r.cate_id ='.I('get.cate_id', 0, 'intval') : '';
		
		$list = $this->getList('c.id,c.rid,r.owner,r.type,r.phone,c.score,c.desc,c.times,c.status,r.cate,r.cate_id', 'whwx_repair as r, whwx_comment as c , whwx_area as a ', $where, 'times desc',true);
		foreach($list as $k => $v){
			if($v['cate_id'] > 0){
				$table = $v['cate'] == 1 ? 'repairman' : 'service';
				$list[$k]['catename'] = M($table)->where('id=' . $v['cate_id'])->getField('name');
			}
		}
		$this->assign('list',$list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}
}