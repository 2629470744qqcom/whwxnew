<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 投票活动
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class VoteActivityController extends AdminController {
    /**
     * 投票活动列表
     * huying Dec 28, 2015
     */
    public function index()
    {
        $where = '1 = 1';
        $where .= I('get.aid') > 0 ? ' and find_in_set("' . I('get.aid', 0, 'intval') . '",aid)' : '';
        $where .= I('get.name')&&I('get.name') != '' ? ' and name like "%'.I('get.name').'%"' : '';
        $where .= I('get.status', -1) > -1 ? ' and status ='.I('get.status') : '';
        $where .= I('get.start_time') ? ' and start_time>' . strtotime(I('get.start_time')) : '';
        $where .= I('get.end_time') ? ' and end_time<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
        $list = $this->getList('id,name,start_time,end_time,view_num,sort,status,aid,desc,lid', 'vote_activity', $where, 'id desc',true);
        //遍历活动小区
        foreach($list as $key => $value)
        {
            $areaInfo = M('area')->where('id in (' . $value['aid'] . ')')->getField('name', true);
            $list[$key]['area'] = implode(',', $areaInfo);
        }
        //获取活动对象
        foreach($list as $key => $value)
        {
            $areaInfo = M('level')->where('id in (' . $value['lid'] . ')')->getField('name', true);
            $list[$key]['level'] = implode(',', $areaInfo);
        }
        $this->assign('list', $list);
        $areaList = $this->getAreaList();
        $this->assign('areaList', $areaList);
        $levelList=$this->getLevelList();
        $this->assign('levelList', $levelList);
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