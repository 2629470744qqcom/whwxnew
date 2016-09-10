<?php
namespace Wap\Controller;
use Common\Controller\WapController;

/**
 *个人中心
 * yaoyongli 2016年1月4日
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class PersonController extends WapController{
	protected function _initialize() {
		parent::_initialize ();
		//分享
		if(!IS_AJAX){
			$shareJs = $this->getShareJs(C('share_title'), C('share_pic'), U('Public/index'), C('share_desc'));
			$this->assign('shareJs', $shareJs);
		}
	}
	/**
	 * 首页
	 * yaoyongli 2016年1月7日
	 */
	public function index(){
		$pid = M('owner')->where('id='.session('fansInfo.oid'))->getField('pid');
		$this->assign('pid', $pid);
		$this->display();
	}
	/**
	 * 个人信息
	 * huying Jan 13, 2016
	 */
	public function all(){
		$info = $this->getInfo('o.id,o.name,o.phone,o.pic,o.address,o.nickname,o.addr,a.name as area', 'whwx_owner as o, whwx_area as a', 'o.aid = a.id and o.id='.session('fansInfo.oid'));
		$info['addr'] = array_reverse(explode('-', $info['addr']));
		$this->assign('info', $info);
		$this->display();
	}
	/**
	 * 修改电话
	 * huying Jan 13, 2016
	 */
	public function tel(){
		if(IS_POST){
			if(!$_POST['phone'] || !$_POST['verify'] || !preg_match('/^1[3-8]\d{9}$/', $_POST['phone'])){
				$this->ajaxReturn(array('info' => '请输入正确的信息', 'status' => 0));
			}else{
				if(cookie('verify_code_' . $_POST['phone']) === sha1(date('Ym') . $_POST['phone'] . $_POST['verify'])){
					$oid = M('owner')->where('status=1 and phone=' . $_POST['phone'])->getField('id');
					if($oid > 0){
						$this->ajaxReturn(array('status' => 0, 'info' => '此手机号已被绑定'));
					}
					//修改业主的手机号码
					$result = M('owner')->where('id='.session('fansInfo.oid'))->setField('phone', $_POST['phone']);
					if($result !== false){
						$this->ajaxReturn(array('info' => '修改成功', 'status' => 1));
					}
				}else{
					$this->ajaxReturn(array('info' => '手机验证码错误', 'status' => 0));
				}
			}
			$this->ajaxReturn(array('info' => '修改失败', 'status' => 0));
		}else{
			$this->display();
		}
	}
	/**
	 * 修改地址
	 * huying Jan 13, 2016
	 */
	public function address(){
		if(IS_POST){
			if(!$_POST['address']){
				$this->ajaxReturn(array('info' => '请输入收货地址', 'status' => 0));
			}else{
				$result = M('owner')->where('id='.session('fansInfo.oid'))->setField('address', $_POST['address']);
				if($result !== false){
					$this->ajaxReturn(array('info' => '修改成功', 'status' => 1));
				}
			}
			$this->ajaxReturn(array('info' => '修改失败', 'status' => 0));
		}else{
			$address = M('owner')->where('id='.session('fansInfo.oid'))->getField('address');
			$this->assign('address', $address);
			$this->display();
		}
	}
	/**
	 * 修改昵称
	 * huying Jan 13, 2016
	 */
	public function nickname(){
		if(IS_POST){
			if(!$_POST['nickname']){
				$this->ajaxReturn(array('info' => '请输入昵称', 'status' => 0));
			}else{
				$resul= M('owner')->where('id='.session('fansInfo.oid'))->setField('nickname', $_POST['nickname']);
				$this->returnResult($result);
			}
		}else{
			$nickname = M('owner')->where('id='.session('fansInfo.oid'))->getField('nickname');
			$this->assign('nickname', $nickname);
			$this->display();
		}
	}
	/**
	 * 更换头像
	 * huying Jan 13, 2016
	 */
	public function head(){
		if(IS_POST){
			if(!$_POST['pic']){
				$this->ajaxReturn(array('info' => '请输入上传图片', 'status' => 0));
			}else{
				$result = M('owner')->where('id='.session('fansInfo.oid'))->setField('pic', $_POST['pic']);
				if($result !== false){
					session('fansInfo.pic', $_POST['pic']);
					$this->ajaxReturn(array('info' => '修改成功', 'status' => 1));
				}
			}
			$this->ajaxReturn(array('info' => '修改失败', 'status' => 0));
		}else{
			$pic = M('owner')->where('id='.session('fansInfo.oid'))->getField('pic');
			$this->assign('pic', $pic);
			$this->display();
		}
	}
	/**
	 * 技术支持
	 * huying Jan 13, 2016
	 */
	public function tech(){
		$this->display();
	}
}