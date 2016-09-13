<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
use Common;

/**
 * 报修功能
 * huying Dec 25, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class RepairController extends AdminController{

	/**
	 * 报修列表
	 * huying Dec 25, 2015
	 */
	public function index(){
		$where = 'r.aid = a.id and a.id in(0,' . session('ruleInfo.aids') . ')';
		if (I('get.type') == 1){
			$where .= ' and r.del<>1 and r.status=1';			
		}else{
			$where .= I('get.id', 0, 'intval') > 0 ? ' and r.id = ' . I('get.id', 0, 'intval') : '';
			$where .= I('get.aid', 0, 'intval') > 0 ? ' and r.aid=' . I('get.aid', 0, 'intval') : '';
			$where .= I('get.phone') ? ' and r.phone like "%' . I('get.phone') . '%"' : '';
			$where .= I('get.name') ? ' and r.name="' . I('get.name') . '"' : '';
			$where .= I('get.owner') ? ' and r.owner like "%' . I('get.owner') . '%"' : '';
			$where .= I('get.owner') ? ' and r.owner like "%' . I('get.owner') . '%"' : '';
			$where .= I('get.cate', 0, 'intval') > 0 ? ' and r.cate=' . I('get.cate', 0, 'intval') : '';
			$where .= I('get.cate_id', 0, 'intval') > 0 ? ' and r.cate=' . I('get.cate', 1, 'intval') . ' and r.cate_id =' . I('get.cate_id', 0, 'intval') : '';
			$where .= I('get.start_time') ? ' and r.creat_time>' . strtotime(I('get.start_time')) : '';
			$where .= I('get.end_time') ? ' and r.creat_time<' . (strtotime(I('get.end_time')) + 24 * 3600) : '';
			$status = I('get.status', -1);
			if($status == 0){
				$where .= ' and r.del = 1';
			}else{
				switch($status){
					case 0: 
					case 1: 
					case 2:
					case 10:
					case 9: $where .= ' and r.del<>1 and r.status=' . $status; break; 
					case 3:
					case 4: $where .= ' and r.del<>1 and r.status in (3,4)'; break;
					case 5:
					case 6: $where .= ' and r.del<>1 and r.status in (5,6)'; break;
					case 7:
					case 8: $where .= ' and r.del<>1 and r.status in (7,8)'; break;
					default: $where .= ' and r.del<>1 and r.status > 0';
				}
			}
		}
		$list = $this->getList('r.id,r.name,r.cate,r.del,r.cate_id,r.creat_time,r.status,r.oid,r.owner,r.phone,r.type,a.name as area', 'whwx_repair as r, whwx_area as a', $where, 'r.creat_time desc', true);
		foreach($list as $k => $v){
			if($v['cate_id'] > 0){
				$table = $v['cate'] == 1 ? 'repairman' : 'service';
				$list[$k]['catename'] = M($table)->where('id=' . $v['cate_id'])->getField('name');
			}
		}
		$this->assign('list', $list);
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->display();
	}

	/**
	 * 报修详情
	 * huying Dec 25, 2015
	 */
	public function detail(){
		if(IS_POST){
			if(empty($_POST['content'])){
				$this->ajaxReturn(array('status' => -1, '请输入反馈内容'));
			}
			$result = Common\Api\CommonApi::addFollow(session('aid'), session('ainfo.name'), session('ainfo.tel'), 1, I('post.id', 0, 'intval'), $_POST['content']);
			$this->ajaxReturn(array('status' => $result));
		}else{
			$info = $this->getInfo('id,name,creat_time,status,oid,owner,phone,cate,cate_id,address,pics,desc,price,repairman_pic,feedback', 'repair', 'id=' . I('get.id', 0, 'intval'));
			if($info['status'] > 4 && $info['cate_id'] > 0){
				$table = $info['cate'] == 1 ? 'repairman' : 'service';
				$info['cate_name'] = M($table)->where('id=' . $info['cate_id'])->getField('name');
			}
			if($info['status'] == 9){
				$info['comment'] = $this->getInfo('times,desc,score', 'comment', 'type < 3 and rid = ' . I('get.id', 0, 'intval'));
			}
			if(!empty($info['pics'])){
				$info['pics'] = explode(',', $info['pics']);
			}
			if(!empty($info['repairman_pic'])){
				$info['repairman_pic'] = explode(',', $info['repairman_pic']);
			}
			if($info['price'] > 0 && $info['status'] > 6){
				$info['pay'] = $this->getInfo('money,real_money,status,pay_time,pay_type', 'payment', 'type = 2 and typeid = ' . I('get.id', 0, 'intval'));
			}
			$this->assign('info', $info);
			// 订单跟踪
			$list = $this->getList('id,oid,oname,otel,content,times', 'follow', 'type = 1 and typeid=' . I('get.id', 0, 'intval'), 'times asc');
			$this->assign('list', $list);
			$this->display();
		}
	}

	/**
	 * 删除
	 * huying Dec 25, 2015
	 */
	public function del(){
		M('warn')->where('type=1 and typeid=' . I('get.id', 0, 'intval'))->setField('status', 0);
		$result = M('repair')->where('id=' . I('get.id', 0, 'intval'))->setField('del', 1);
		$this->returnResult($result);
	}

	/**
	 * 获取维修工
	 * huying Dec 30, 2015
	 */
	public function getRepairMan(){
		$info = $this->getInfo('id,oid,aid,cate', 'repair', 'id=' . I('post.id', 0, 'intval'));
		if($info['cate'] == 2){
			// $bid　= M('owner')->where('id='.$info['oid'])->getField('bid');
			// $list = M('service')->field('id,name')->where('status = 1 and find_in_set('.$bid　.', bids)')->select();
			// $list = $this->getList('id,name', 'repairman', 'status = 1 and aid='.$info['aid'], 'id desc');
		}else{
			//$list = $this->getList('id,name', 'repairman', 'status = 1 and aid=' . $info['aid'], 'id desc');
			$list = $this->getList('id,name', 'repairman', 'status = 1 and find_in_set('.$info['aid'].',aid)', 'id desc');
		}
		$this->ajaxReturn(array('type' => $info['cate'], 'list' => $list));
	}

	/**
	 * 分配
	 * huying Dec 30, 2015
	 */
	public function allot(){
		$type = I('post.type', 0, 'intval');
		$info = $this->getInfo('r.id,r.oid,r.aid,r.name,r.owner,r.phone,r.desc,r.address', 'repair as r', 'r.id=' . I('post.id', 0, 'intval'));
		if($type == -1){ // 专属客服
			if(!empty($info)){
				// 获取业主所在区域的负责客服
				$bid　 = M('owner')->where('id=' . $info['oid'])->getField('bid');
				$serviceInfo = $this->getInfo('id,name', 'service', 'status = 1 and aid=' . $info['aid'] . ' and FIND_IN_SET(' . $bid　 . ', bids)');
				if($serviceInfo){
					$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'cate' => 2, 'cate_id' => $serviceInfo['id'], 'status' => 5, 'update_time' => time()), 'repair', 2);
					$data = array('type' => 1, 'typeid' => I('post.id', 0, 'intval'), 'content' => '分配当前报修记录给客服专员：' . $serviceInfo['name']);
				}else{
					$this->ajaxReturn(array('status' => -1, 'info' => '该业主所在楼栋还没有客服专员'));
				}
			}
		}elseif($type == -2){
			// 添加到预警表中（自由抢单）
			$tplMsgData = array('first' => array('value' => '有新的报修订单，快去抢单吧！', 'color' => '#ff0000'), 'keyword1' => array('value' => $info['owner'], 'color' => '#173177'), 'keyword2' => array('value' => $info['phone'], 'color' => '#173177'), 
				'keyword3' => array('value' => $info['address'], 'color' => '#173177'), 'keyword4' => array('value' => $info['name'], 'color' => '#173177'), 'remark' => array('value' => $info['desc'], 'color' => '#173177'));
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			$manList = $this->getList('r.id,f.openid', 'whwx_repairman as r, whwx_wxfans as f', 'f.type = 3 and f.oid = r.id and r.aid = ' . $info['aid'], 'r.id desc');
			foreach($manList as $k => $v){
				$result3 = $wechatAuth->sendTemplateMsg($v['openid'], C('repair_template'), '/Wap/Repairman/order?id=' . $info['id'], $tplMsgData);
			}
			$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'status' => 4, 'update_time' => time()), 'repair', 2);
			$data = array('type' => 1, 'typeid' => I('post.id', 0, 'intval'), 'content' => '分配当前报修记录为自由抢单');
		}elseif($type > 0){ // 维修工
			$tplMsgData = array('first' => array('value' => '管理员分配给你一个报修订单！', 'color' => '#ff0000'), 'keyword1' => array('value' => $info['owner'], 'color' => '#173177'), 'keyword2' => array('value' => $info['phone'], 'color' => '#173177'), 
				'keyword3' => array('value' => $info['address'], 'color' => '#173177'), 'keyword4' => array('value' => $info['name'], 'color' => '#173177'), 'remark' => array('value' => $info['desc'], 'color' => '#173177'));
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			$manInfo = $this->getInfo('r.name,f.openid', 'whwx_repairman as r, whwx_wxfans as f', 'f.type = 3 and f.oid = r.id and r.id = ' . $type);
			if($manInfo){
				$wechatAuth->sendTemplateMsg($manInfo['openid'], C('repair_template'), '/Wap/Repairman/my_order?id=' . $info['id'], $tplMsgData);
			}
			$result = $this->updateData(array('id' => I('post.id', 0, 'intval'), 'cate' => 1, 'cate_id' => $type, 'status' => 5, 'update_time' => time()), 'repair', 2);
			$data = array('type' => 1, 'typeid' => I('post.id', 0, 'intval'), 'content' => '分配当前报修记录给维修员：' . $manInfo['name']);
		}
		if($result !== false){
			$result = Common\Api\CommonApi::addFollow(session('aid'), session('ainfo.name'), session('ainfo.tel'), $data['type'], $data['typeid'], $data['content']);
			M('warn')->where('type = 1 and typeid=' . I('post.id', 0, 'intval') . ' and status = 1')->setField('status', 0);
			$this->ajaxReturn(array('status' => 1, 'info' => '分配成功'));
		}else{
			$this->ajaxReturn(array('status' => -1, 'info' => '分配错误，请说稍后重试'));
		}
	}

	/**
	 * 报修数据导出
	 * zhangxinhe Mar 4, 2016
	 */
	public function export(){
		$where = 'r.aid=a.id';
		$where .= I('post.aid', 0, 'intval') > 0 ? ' and r.aid=' . I('post.aid', 0, 'intval') : '';
		//$where .= I('post.status', -1) > -1 ? ' and r.status = ' . I('post.status') : ' and r.status > 0';
		$status = I('post.status', -1);
		if($status == 0){
			$where .= ' and r.del = 1';
		}else{
			switch($status){
				case 0: 
				case 1: 
				case 2:
				case 10:
				case 9: $where .= ' and r.del<>1 and r.status=' . $status; break; 
				case 3:
				case 4: $where .= ' and r.del<>1 and r.status in (3,4)'; break;
				case 5:
				case 6: $where .= ' and r.del<>1 and r.status in (5,6)'; break;
				case 7:
				case 8: $where .= ' and r.del<>1 and r.status in (7,8)'; break;
				default: $where .= ' and r.del<>1 and r.status > 0';
			}
		}
		$start_time = strtotime($_POST['start_time']);
		$end_time = strtotime($_POST['end_time']) + 24 * 3600;
		$where .= I('post.cate', 0, 'intval') > 0 ? ' and r.cate=' . I('post.cate', 0, 'intval') : '';
		$where .= I('post.cate_id', 0, 'intval') > 0 ? ' and r.cate_id =' . I('post.cate_id', 0, 'intval') : '';
		$where .= ' and creat_time > ' . $start_time . ' and creat_time < ' . $end_time;
		$list = M()->table('whwx_repair as r,whwx_area a')->field('r.id,r.owner,r.phone,r.name,r.creat_time,r.desc,r.address,r.status,r.cate,r.cate_id,r.price,a.name area')->where($where)->limit(0, 10000)->select();
		foreach($list as $k => $v){
			$list[$k]['creat_time'] = date('Y-m-d H:i:s', $v['creat_time']);
			switch($v['status']){
				case 0 :
					$list[$k]['status'] = '已删除';
					break;
				case 1 :
					$list[$k]['status'] = '预警中';
					break;
				case 2 :
					$list[$k]['status'] = '纠错中';
					break;
				case 3 :
					$list[$k]['status'] = '已提交';
					break;
				case 4 :
					$list[$k]['status'] = '已提交';
					break;
				case 5 :
					$list[$k]['status'] = '维修中';
					break;
				case 6 :
					$list[$k]['status'] = '维修中';
					break;
				case 7 :
					$list[$k]['status'] = '维修完成';
					break;
				case 8 :
					$list[$k]['status'] = '维修完成';
					break;
				case 9 :
					$list[$k]['status'] = '评价完成';
					break;
				case 10 :
					$list[$k]['status'] = '维修完成';
					break;
			}
			if($v['cate_id'] > 0){
				$table = $v['cate'] == 1 ? 'repairman' : 'service';
				$list[$k]['cate'] = M($table)->where('id=' . $v['cate_id'])->getField('name');
			}else{
				$list[$k]['cate'] = '未分配';
			}
			unset($list[$k]['cate_id']);
			if($v['status'] == 9){
				$commentInfo = $this->getInfo('score,desc', 'comment', 'type < 3 and rid=' . $v['id']);
				$list[$k]['comment_score'] = $commentInfo['score'];
				$list[$k]['comment_desc'] = $commentInfo['desc'];
			}
		}
		$title = array('ID', '业主姓名', '业主手机', '报修类型', '报修时间', '报修内容', '报修地址', '报修状态', '负责人', '维修费用', '所属小区', '评分', '评论');
		array_unshift($list, $title);
		$file = \Common\Api\PHPExcelApi::exportExcel($list, $_POST['name'], false);
		$this->ajaxReturn(array('info' => '导出成功', 'status' => 1, 'url' => $file));
	}

	/**
	 * 关闭纠错状态下的报修
	 * huying Mar 5, 2016
	 */
	public function close(){
		$repair = $this->getInfo('id,status', 'repair', 'id=' . I('post.id', 0, 'intval'));
		if($repair['status'] == 2){
			$result = M('repair')->where('id=' . I('post.id', 0, 'intval'))->setField('status', 10);
			if($result !== false){
				$result2 = Common\Api\CommonApi::addFollow(session('aid'), session('ainfo.name'), session('ainfo.tel'), 1, I('post.id', 0, 'intval'), '关闭该报修');
			}
			$this->returnResult($result);
		}
	}

	/**
	 * 后台添加报修
	 * huying Mar 8, 2016
	 */
	public function add(){
		if(IS_POST){
			$data = array('name' => '后台报修', 'address' => $_POST['address'], 'type' => 4, 'desc' => $_POST['desc'], 'pics' => implode(',', $_POST['pic']), 'status' => 3, 'creat_time' => time(), 'cate' => 1, 'aid' => I('post.aid', 0, 'intval'), 'owner' => $_POST['name'], 'phone' => $_POST['phone']);
			$result = $this->updateData($data, 'repair');

            \Think\Log::write('后台报修日志信息，报修信息结果是：'.serialize($data).'更新数据结果是：'.serialize($result), 'WARN');

			if($result !== false){
				$area = M('area')->where(array('id' => I('post.aid', 0, 'intval')))->getField('name');
				// 发送报修通知抢单
				$info = array('first' => array('value' => '有新的报修订单，快去抢单吧！', 'color' => '#ff0000'), 'keyword1' => array('value' => $_POST['name'], 'color' => '#173177'), 'keyword2' => array('value' => $_POST['phone'], 'color' => '#173177'), 
					'keyword3' => array('value' => $area . ' ' . $_POST['address'], 'color' => '#173177'), 'keyword4' => array('value' => '后台报修', 'color' => '#173177'), '$desc' => array('value' => $_POST['desc'], 'color' => '#173177'));
				// 获取业主所在小区的维修工的信息
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				$manList = $this->getList('r.id,f.openid,r.name', 'whwx_repairman as r, whwx_wxfans as f', 'f.type = 3 and f.oid = r.id and find_in_set(' . $_POST['aid'] . ',r.aid)', 'r.id desc');

                \Think\Log::write('后台报修日志信息，维修员结果是：'.serialize($manList), 'WARN');

                foreach($manList as $k => $v){
					$result3 = $wechatAuth->sendTemplateMsg($v['openid'], C('repair_template'), '/Wap/Repairman/order?id=' . $result, $info);

					\Think\Log::write('后台报修日志信息，返回结果是：*****************'.serialize($result3).'.详细信息如下****：'.serialize($v).'. info****: '.serialize($info), 'WARN');

                    if ('ok' != $result3['errmsg']) {
                        \Think\Log::write('&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&错误信息:'.serialize($result3), 'WARN');
                    }
                }
			}
			$this->returnResult($result);
		}else{
			$areaList = $this->getAreaList();
			$this->assign('areaList', $areaList);
			$this->display();
		}
	}

	/**
	 * 后台完成报修
	 * huying Mar 8, 2016
	 */
	public function feedback(){
		if(IS_POST){
			$result = $this->updateData(array('id' => I('post.rid', 0, 'intval'), 'feedback' => $_POST['feedback'], 'status' => 9), 'repair', 2);
			$this->returnResult($result);
		}
	}

	/**
	 * 根据类型获取维修工
	 * huying Mar 14, 2016
	 */
	public function getCateName(){
		if(I('get.cate')){
			$table = I('get.cate') == 1 ? 'repairman' : 'service';
			if(I('get.aid', 0, 'intval') > 0){
				$list = $this->getList('id,name', $table, 'status = 1 and aid =' . I('get.aid', 0, 'intval') . ' and aid in(0,' . session('ruleInfo.aids') . ')', 'id desc');
			}else{
				$list = $this->getList('id,name', $table, 'status = 1 and aid in(0,' . session('ruleInfo.aids') . ')', 'id desc');
			}
			$this->ajaxReturn(array('status' => 1, 'list' => $list));
		}
	}
}