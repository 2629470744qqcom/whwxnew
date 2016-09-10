<?php
namespace Wap\Controller;
use Common\Controller\WapController;

/**
 * 身份选择和用户注册
 * zhangxinhe Dec 31, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class PublicController extends WapController{

	protected function _initialize(){
		parent::_initialize();
		if(session('fansInfo.type') > 0 && session('fansInfo.oid') > 0){
			$this->redirect('Wap/Index/index');
		}
		// 分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}

	/**
	 * 身份选择
	 * zhangxinhe Dec 31, 2015
	 */
	public function index(){
		session('fansInfo.type', 0);
		$this->display();
	}

	/**
	 * 申请注册
	 * zhangxinhe Dec 31, 2015
	 */
	public function regedit(){
		if(IS_POST){
			if(!session('fansInfo.id') || !$_POST['name'] || !$_POST['phone'] || !$_POST['verify'] || !preg_match('/^1[3-8]\d{9}$/', $_POST['phone'])){
				\Think\Log::write('Regedit_Post_Data_'.implode('|', $_POST).'_Session_Data_'.implode('|', $_SESSION), 'zxh');
				$this->ajaxReturn(array('info' => '参数有误，请稍后重试', 'status' => 0));
			}else{
				if(cookie('verify_code_' . $_POST['phone']) === sha1(date('Ym') . $_POST['phone'] . $_POST['verify'])){
					$_POST['fid'] = session('fansInfo.id');
					if($_POST['type'] == 3){ // 物业管家
						if($_POST['cate'] == 3 || $_POST['cate'] == 4){
							$table = $_POST['cate'] == 3 ? 'repairman' : 'service';
							$id = M($table)->where('phone=' . $_POST['phone'].' and status = 1')->getField('id');
							if($id > 0){
								$this->ajaxReturn(array('status' => 0, 'info' => '此手机号已被绑定'));
							}
							$_POST['status'] = 2;
							$result = $this->updateData($_POST, $table);
							if($result !== false){
								$this->ajaxReturn(array('status' => 1, 'info' => '申请成功，请等待审核通过'));
							}
						}
						$this->ajaxReturn(array('status' => 0, 'info' => '请选择你的管家身份'));
					}else{ // 业主
						$oid = M('owner')->where('status=1 and phone=' . $_POST['phone'])->getField('id');
						if($oid > 0){
							$this->ajaxReturn(array('status' => 0, 'info' => '此手机号已被绑定'));
						}
						if($_POST['type'] != 1){
							// 亲属
							$owner = $this->getInfo('o.id,o.phone', 'whwx_owner as o, whwx_room as r', 'o.status = 1 and o.id = r.oid and o.pid = 0 and r.id=' . I('post.rid', 0, 'intval'));
							if(empty($owner)){
								\Think\Log::write(M()->_sql(), 'zxh');
								$this->ajaxReturn(array('info' => '该业主还没有注册', 'status' => 0));
							}
							if($_POST['ownerphone'] != $owner['phone']){
								$this->ajaxReturn(array('info' => '业主手机号码不正确', 'status' => 0));
							}
							$_POST['pid'] = $owner['id'];
						}else{
							// 判断该业主是否存在临时表中
// 							$id = M('owner_temp')->where('name="' . $_POST['name'] . '" and phone="' . $_POST['phone'] . '" and oid=0')->getField('id');
							$room = M('room')->field('id,oid')->where('owner like "%'.$_POST['name'].'%" and phone like "%'.$_POST['phone'].'%" and id = '.$_POST['rid'])->find();
							if($room['oid'] > 0){
								$this->ajaxReturn(array('info' => '此房间已经有业主，请申请为亲友/租客', 'status' => 0));
							}
// 							$id = M('room')->where('owner like "%'.$_POST['name'].'%" and phone like "%'.$_POST['phone'].'%" and rid = '.$_POST['rid'].' and oid = 0')->getField('id');
						}
						$_POST['addr'] = $_POST['b_name'] . '-' . $_POST['u_name'] . '-' . $_POST['r_name'];
						$_POST['status'] = $room['id'] > 0 || $_POST['type'] != 1 ? 1 : 0;
						$_POST['reg_time'] = time();
						$result = $this->updateData($_POST, 'owner');
						if($result !== false){
							if(C('reg_point') > 0){
								$this->changePoint($result, C('reg_point'), '注册送积分', 0, 1);
							}
							if($room['id'] > 0){
								$this->updateData(array('id' => session('fansInfo.id'), 'type' => 1, 'oid' => $result), 'wxfans', 2);
								M('room')->where('id='.I('post.rid'))->setField('oid', $result);
								$this->ajaxReturn(array('status' => 2, 'info' => '注册成功'));
							}else{
								if($_POST['type'] == 2){ // 租客通知业主
									//通知消息
									$typeid = \Common\Api\CommonApi::addNotice($owner['id'], '你有新的申请', $_POST['name'] . '申请成为你的亲友/租客，请及时处理', 3, $result);
									//模板消息
									$info = array('first' => array('value' => '你有一条申请消息', 'color' => '#ff0000'), 
											'keyword1' => array('value' => '亲友/租客' , 'color' => '#173177'), 
											'keyword2' => array('value' => $_POST['name'], 'color' => '#173177'),
											'keyword3' => array('value' => $_POST['name'].'申请成为你的亲友/租客', 'color' => '#173177'),
											'keyword4' => array('value' => date('Y-m-d H:i:s'), 'color' => '#173177'),
											'remark' => array('value' => '点击查看详情', 'color' => '#173177'));
									$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
									$openid = M('wxfans')->where('type = 1 and oid='.$owner['id'].' and status = 1')->getField('openid');
									$result3 = $wechatAuth->sendTemplateMsg($openid, C('news_template'), U('Owner/cont?id=' . $result.'&typeid='.$typeid), $info);
									$this->updateData(array('id' => session('fansInfo.id'), 'type' => 1, 'oid' => $result), 'wxfans', 2);
									$this->ajaxReturn(array('status' => 2, 'info' => '申请成功'));
								}else{
// 									M('room')->where('id = ' . $_POST['rid'])->setField('oid', $result);
									$this->ajaxReturn(array('status' => 1, 'info' => '申请成功，请等待审核通过'));
								}
							}
						}
					}
					$this->ajaxReturn(array('info' => '注册失败，请重试', 'status' => 0));
				}else{
					$this->ajaxReturn(array('info' => '手机验证码错误', 'status' => 0));
				}
			}
		}else{
			session('fansInfo.type', 0);
			$areaList = $this->getList('id,name', 'area', 'status=1', 'sort desc,id asc');
			$this->assign('areaList', $areaList);
			$page = $_GET['type'] == 3 ? 'manger' : 'regedit';
			$this->display($page);
		}
	}

	/**
	 * 获取注册短信验证码
	 * zhangxinhe 2015-5-11
	 */
	public function getVerifyCode(){
		$phone = I('post.phone', 0, 'string');
		if(!session('fansInfo.id') || !preg_match('/^1[3-8]\d{9}$/', $phone)){
			\Think\Log::write('GetVerifyCode_Post_Data_'.implode('|', $_POST).'_Session_Data_'.implode('|', $_SESSION), 'zxh');
			$this->returnResult(false, array('', '参数有误，请稍后重试'));
		}
		if($_POST['type'] == 1){
			$oid = M('owner')->where('status=1 and phone=' . $_POST['phone'])->getField('id');
			if($oid > 0){
				$this->returnResult(false, array('', '此手机号已被绑定'));
			}
		}
		$code = mt_rand(100000, 999999);
		cookie('verify_code_' . $phone, sha1(date('Ym') . $phone . $code), 3000);
		$result = \Common\Api\SmsApi::sendSms($phone, '您好，你的手机验证码为' . $code . ',请勿转发或告诉他人，5分钟内有效。【星管家智慧社区】', false);
		if($result === false){
			$this->returnResult(false, array('', '获取验证码失败，请重试'));
		}
		$this->returnResult(true, array('', '发送成功'));
	}

	/**
	 * 获取小区、楼栋、单元和房间信息
	 * zhangxinhe Jan 6, 2016
	 */
	public function getAreaInfo(){
		if($_GET['d_id'] == 0 && $_GET['id'] > 0){
			$list = $this->getList('id,name', 'block', 'status=1 and aid=' . $_GET['id'], 'sort desc,id asc');
		}elseif($_GET['d_id'] == 1 && $_GET['id'] > 0){
			$uids = M('block')->where('id=' . $_GET['id'])->getField('units');
			$list = $this->getList('id,name', 'unit', 'status=1 and id in (' . trim($uids, ',') . ')', 'id asc');
		}else{
			$where = 'status=1 and uid=' . $_GET['id'] . ' and bid=' . $_GET['bid'];
			$where .= $_GET['type'] != 2 ? ' and oid = 0' : '';
			$list = $this->getList('id,name', 'room', $where, 'sort desc,id asc');
		}
		$this->ajaxReturn($list);
	}
}