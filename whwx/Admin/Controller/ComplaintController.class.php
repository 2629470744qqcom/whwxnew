<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 投诉建议
 * huying Dec 28, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class ComplaintController extends AdminController{

	/**
	 * 列表
	 * huying Dec 26, 2015
	 */
	public function index(){
		$where = "o.id = c.oid and c.aid = a.id and a.id in(0," . session('ruleInfo.aids') . ")";
		if($_GET['type'] == 3){
			$ids = M('warn')->where(array('type' => 3, 'status' => 1))->getField('typeid', true);
			$ids && $where .= ' and c.id in (' . implode(',', $ids) . ')';
		}else{
			$where .= I('get.status', -1) > -1 ? ' and c.status =' . I('get.status') : ' and c.status > -1';
			$where .= I('get.aid', 0, 'intval') > 0 ? ' and c.aid=' . I('get.aid', 0, 'intval') : '';
			$where .= I('get.sid', 0, 'intval') > 0 ? ' and c.sid=' . I('get.sid', 0, 'intval') : '';
			$where .= I('get.id', 0, 'intval') > 0 ? ' and c.id=' . I('get.id', 0, 'intval') : '';
		}
		$list = $this->getList('c.id,c.desc,c.status,c.times,c.sid,c.deal_time,c.oid,c.name oname,c.tel phone,o.fid,o.addr,a.name as area', 'whwx_complaint as c,whwx_owner as o,whwx_area as a', $where, 'c.times desc', true, 12);
		foreach($list as $k => $v){
			$list[$k]['name'] = $v['sid'] > 0 ? M('service')->where('id=' . $v['sid'])->getField('name') : '未分配';
		}
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$serviceList = $this->getList('id,name', 'service', 'status = 1', 'id desc');
		$this->assign('serviceList', $serviceList);
		$this->assign('list', $list);
		$this->display();
	}

	/**
	 * 添加投诉建议
	 * Sandny 2016年4月21日
	 */
	public function addComplaint(){
		if(IS_POST){
			$_POST['aid'] = I('post.aid', 0, 'intval');
			$_POST['pics'] = implode(',', I('post.pic'));
			$_POST['desc'] = '楼栋：' . I('post.block') . ' 描述：' . I('post.desc');
			$_POST['times'] = time();
			$service = $this->getInfo('s.id,f.openid', 'whwx_service as s, whwx_wxfans as f', 's.aid = ' . $_POST['aid'] . ' and s.bids like "%,' . I('post.bid') . ',%" and f.type = 4 and s.id = f.oid and s.status = 1');
			$_POST['sid'] = $service['id'];
			$result = $this->updateData($_POST, 'complaint');
			if($result){
				if($service){
					$info = array('first' => array('value' => '有新的投诉建议，快去处理吧！', 'color' => '#ff0000'), 'keyword1' => array('value' => I('post.name'), 'color' => '#173177'), 'keyword2' => array('value' => I('post.tel'), 'color' => '#173177'), 
						'keyword3' => array('value' => date('Y-m-d H:i', time()), 'color' => '#173177'), 'keyword4' => array('value' => $_POST['desc'], 'color' => '#173177'), 'remark' => array('value' => '点击查看详情', 'color' => '#173177'));
					$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
					$result3 = $wechatAuth->sendTemplateMsg($service['openid'], C('advise_template'), U('Wap/Service/complaint?id=' . $result), $info);
				}
			}
			$this->returnResult($result);
		}else{
			$areaList = $this->getList('id,name', 'area', 'status = 1', 'id desc');
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}

	/**
	 * 处理
	 * huying Dec 28, 2015
	 */
	public function edit(){
		if(IS_POST){
			if(empty($_POST['feedback'])){
				$this->returnResult(false, '请提交处理信息');
			}
			$_POST['status'] = 1;
			$_POST['deal_time'] = time();
			$result = $this->updateData($_POST, 'complaint', 2);
			if($result !== false){
				M('warn')->where('type = 3 and typeid=' . I('post.id', 0, 'intval') . ' and status = 1')->setField('status', 0);
				if($_POST['fid'] > 0){
					$openid = M('wxfans')->where('id=' . $_POST['fid'])->getField('openid');
					if($openid){
						$data = array('first' => array('value' => '您的投诉建议已处理完成！', 'color' => '#ff0000'), 'keyword1' => array('value' => $_POST['name'], 'color' => '#173177'), 'keyword2' => array('value' => $_POST['phone'], 'color' => '#173177'), 
							'keyword3' => array('value' => date('Y-m-d H:i', time()), 'color' => '#173177'), 'keyword4' => array('value' => '已受理', 'color' => '#173177'), 'remark' => array('value' => '点击查看详情', 'color' => '#173177'));
						$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
						$result3 = $wechatAuth->sendTemplateMsg($openid, C('advise_return_template'), U('Wap/Complaint/detail?id=' . $_POST['id']), $data);
					}
				}
			}
			$this->returnResult($result, null, U('Complaint/index'));
		}
	}

	/**
	 * 查看/反馈
	 * huying Dec 28, 2015
	 */
	public function detail(){
		if(IS_POST){
			if(empty($_POST['content'])){
				$this->ajaxReturn(array('status' => -1, '请输入反馈内容'));
			}
			$result = \Common\Api\CommonApi::addFollow(session('aid'), session('ainfo.name'), session('ainfo.tel'), 5, I('post.id', 0, 'intval'), $_POST['content']);
			$this->ajaxReturn(array('status' => $result));
		}else{
			$info = $this->getInfo('id,oid,desc,pics,status,times,feedback,feedback_pic,pics,deal_time,name as owner,tel phone', 'complaint', 'id=' . I('get.id', 0, 'intval'));
			if($info['oid'] > 0){
				$roomInfo = $this->getInfo('o.addr,a.name', 'whwx_owner o,whwx_area a', 'o.aid=a.id and o.id=' . $info['oid']);
				$info = array_merge($info, $roomInfo);
			}
			if(!empty($info['pics'])){
				$info['pics'] = explode(',', $info['pics']);
			}
			if(!empty($info['feedback_pic'])){
				$info['feedback_pic'] = explode(',', $info['feedback_pic']);
			}
			if($info['status'] == 2){
				$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type = 5 and rid = ' . I('get.id', 0, 'intval'));
			}
			$this->assign('info', $info);
			// 管理员回复
			$list = $this->getList('id,oid,oname,otel,content,times', 'follow', 'type = 5 and typeid=' . I('get.id', 0, 'intval'), 'times asc');
			$this->assign('list', $list);
			
			$this->display();
		}
	}

	/**
	 * 导出数据
	 * zhangxinhe Mar 5, 2016
	 */
	public function export(){
		$where = "c.aid=a.id";
		$where .= I('post.status', -1) > -1 ? ' and c.status =' . I('post.status') : ' and c.status > -1';
		$where .= I('post.aid', 0, 'intval') > 0 ? ' and c.aid=' . I('post.aid', 0, 'intval') : '';
		$where .= I('post.start_time') ? ' and c.times>' . strtotime(I('post.start_time')) : '';
		$where .= I('post.end_time') ? ' and c.times<' . (strtotime(I('post.end_time')) + 24 * 3600) : '';
		$list = M()->table('whwx_complaint c,whwx_area a')->field('c.id,a.name aname,c.name,c.tel,c.times,c.status,c.desc,c.feedback,c.deal_time,c.oid')->where($where)->order('c.times desc')->limit(10000)->select();
		foreach($list as $k => $v){
			$list[$k]['times'] = date('Y-m-d H:i:s', $v['times']);
			$list[$k]['deal_time'] = $v['deal_time'] > 0 ? date('Y-m-d H:i:s', $v['deal_time']) : '未处理';
			switch($v['status']){
				case 0 :
					$list[$k]['status'] = '未处理';
					break;
				case 1 :
					$list[$k]['status'] = '已处理';
					break;
				case 2 :
					$list[$k]['status'] = '已完成';
					break;
			}
			if($v['oid'] > 0){
				$list[$k]['oid'] = M()->table('whwx_service s,whwx_owner o')->where('find_in_set(o.bid, s.bids) and o.id=' . $v['oid'])->getField('s.name');
			}
			if($v['status'] == 2){
				$commentInfo = $this->getInfo('score,desc', 'comment', 'type<5 and rid=' . $v['id']);
				$list[$k]['comment_score'] = $commentInfo['score'];
				$list[$k]['comment_desc'] = $commentInfo['desc'];
			}
		}
		$title = array('ID', '小区', '业主姓名', '业主手机', '投诉/建议时间', '状态', '投诉/建议内容', '回复内容', '回复时间', '负责人', '评分', '评论');
		array_unshift($list, $title);
		$file = \Common\Api\PHPExcelApi::exportExcel($list, $_POST['name'], false);
		$this->ajaxReturn(array('info' => '导出成功', 'status' => 1, 'url' => $file));
	}
}