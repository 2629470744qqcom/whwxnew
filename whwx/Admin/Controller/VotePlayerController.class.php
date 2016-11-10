<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 投票参赛选手
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class VotePlayerController extends AdminController {
    /**
     * 投票参赛选手列表
     * huying Dec 28, 2015
     */
    public function index()
    {
        $where = 'p.act_id = a.id and a.id in('.session('ruleInfo.aids').')';
        $where .= I('get.name')&&I('get.name') != '' ? ' and p.name like "%'.I('get.name').'%"' : '';
        $where .=I('get.number') && I('get.number') != '' ? 'and p.number ='.I('get.number') : '' ;
        $where .=I('get.act_id') && I('get.act_id')>0 ? 'and p.act_id='.I('get.act_id',0,'intval') : '' ;
        $where .= I('get.status', -1) > -1 ? ' and p.status ='.I('get.status') : '';
        if('desc' == I('get.sort')){
            $list = M('')->table('whwx_vote_player as p,whwx_vote_activity as a')->field('p.id,p.number,p.name,p.phone,p.view_num,p.job,p.status,p.zan,p.act_id,a.name as act')->where($where)->order('p.zan asc')->select();
        }else
        $list = $this->getList('p.id,p.number,p.name,p.phone,p.view_num,p.job,p.status,p.zan,p.act_id,a.name as act', 'whwx_vote_player as p,whwx_vote_activity as a', $where, 'p.id desc',true);
        $this->assign('list', $list);
        $activityList = $this->getActivityList();
        $this->assign('activityList', $activityList);
        $this->display();
    }

    /**
     * 添加投票活动
     * huying Dec 28, 2015
     */
    public function add()
    {
        if(IS_POST)
        {
            $result = $this->updateData($_POST, 'vote_player');
            $this->returnResult($result);
        }else{
 			$activityList = $this->getActivityList();
            //dump($activityList);
			$this->assign('activityList', $activityList);
            $this->display();
        }
    }

    /**
     * 修改投票活动
     * huying Dec 28, 2015
     */
    public function edit()
    {
        if(IS_POST)
        {
            $result = $this->updateData($_POST, 'vote_player', 2);
            $this->returnResult($result);
        }else{
            $info = $this->getInfo('id,name,pic,status,desc,phone,job,zan,view_num,number,act_id', 'vote_player', 'id='.I('get.id', 0, 'intval'));
            $this->assign('info', $info);
            $activityList = $this->getActivityList();
            $this->assign('activityList', $activityList);
            $this->display('add');
        }
    }

    /**
     * 删除投票活动
     * huying Dec 28, 2015
     */
    public function del()
    {
        $result = $this->deleteData ('id='.I('get.id', 0, 'intval'), 'vote_player' );
        $this->returnResult ( $result );
    }
}