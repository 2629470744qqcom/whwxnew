<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 投票粉丝
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class VoteVoterController extends AdminController {
    /**
     * 投票粉丝列表
     * huying Dec 28, 2015
     */
    public function index()
    {

        $where = 'v.act_id = a.id and a.id in('.session('ruleInfo.aids').')';
        $where .= 'and v.player_id = p.id and p.id in('.session('ruleInfo.aids').')';
        $where .= I('get.vote_time') ? ' and v.vote_time=' . strtotime(I('get.vote_time')) : '';
        $where .=I('get.act_id') && I('get.act_id')>0 ? 'and v.act_id='.I('get.act_id',0,'intval') : '' ;
        $where .=I('get.player_id') && I('get.player_id')>0 ? 'and v.player_id='.I('get.player_id',0,'intval') : '' ;
        $list = $this->getList('v.id,v.fans_id,v.player_id,v.vote_time,v.act_id,p.name as player,a.name as act,f.nickname as fans', 'whwx_vote_voter as v,whwx_vote_player as p,whwx_vote_activity as a,whwx_wxfans as f', $where, 'v.id desc',true);
        $this->assign('list', $list);
        $activityList = $this->getActivityList();
        $this->assign('activityList', $activityList);
        $playerList = $this->getPlayeryList();
        $this->assign('playerList', $playerList);
        $this->display();
    }
    /**
     * 添加投票粉丝
     * huying Dec 28, 2015
     */
    public function add()
    {
        if(IS_POST)
        {
            $_POST['time'] = time();
            $result = $this->updateData($_POST, 'vote_activity');
            $this->returnResult($result);
        }else{
 			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
            $levelList=$this->getLevelList();
            $this->assign('levelList', $levelList);
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
            $_POST['start_time'] = strtotime($_POST['start_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['aid'] = implode(',', $_POST['aid']);
            $_POST['lid'] =implode(',',$_POST['lid']);
            $result = $this->updateData($_POST, 'vote_activity', 2);
            $this->returnResult($result);
        }else{
            $info = $this->getInfo('id,start_time,end_time,name,pic,sort,status,desc,aid,lid', 'vote_activity', 'id='.I('get.id', 0, 'intval'));
            $info['aid'] = explode(',', $info['aid']);
            $info['lid'] = explode(',', $info['lid']);
            $this->assign('info', $info);
 			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
            $levelList=$this->getLevelList();
            $this->assign('levelList', $levelList);
            $this->display('add');
        }
    }
    /**
     * 删除投票活动
     * huying Dec 28, 2015
     */
    public function del()
    {
        $result = $this->deleteData ('id='.I('get.id', 0, 'intval'), 'vote_activity' );
        $this->returnResult ( $result );
    }
}