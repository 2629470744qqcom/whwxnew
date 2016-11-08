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
        $where = '1 = 1';
        $where .= I('get.act_id') > 0 ? ' and find_in_set("' . I('get.act_id', 0, 'intval') . '",act_id)' : '';
        $where .= I('get.name')&&I('get.name') != '' ? ' and name like "%'.I('get.name').'%"' : '';
        $where .= I('get.status', -1) > -1 ? ' and status ='.I('get.status') : '';
        $list = $this->getList('id,number,name,phone,view_num,job,status,zan,desc', 'vote_player', $where, 'id desc',true);
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
            $_POST['start_time'] = strtotime($_POST['start_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['add_time'] = time();
            $_POST['aid'] = implode(',', $_POST['aid']);
            $_POST['lid'] =implode(',',$_POST['lid']);
            $result = $this->updateData($_POST, 'vote_player');
            $this->returnResult($result);
        }else{
 			$activityList = $this->getActivityList();
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
            $_POST['start_time'] = strtotime($_POST['start_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['aid'] = implode(',', $_POST['aid']);
            $_POST['lid'] =implode(',',$_POST['lid']);
            $result = $this->updateData($_POST, 'vote_player', 2);
            $this->returnResult($result);
        }else{
            $info = $this->getInfo('id,start_time,end_time,name,pic,sort,status,desc,aid,lid', 'vote_player', 'id='.I('get.id', 0, 'intval'));
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
        $result = $this->deleteData ('id='.I('get.id', 0, 'intval'), 'vote_player' );
        $this->returnResult ( $result );
    }
}