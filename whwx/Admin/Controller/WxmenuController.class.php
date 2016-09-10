<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 自定义菜单设置
 * zhangxinhe Dec 29, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WxmenuController extends AdminController{

	/**
	 * 自定义菜单列表
	 * zhangxinhe Dec 29, 2015
	 */
	public function index(){
		$list = $this->getList('id,pid,name,value,sort', 'wxmenu', null, 'sort desc');
		foreach($list as $key => $value){
			if($value['pid'] == 0){
				$arr[] = $list[$key];
				foreach($list as $k => $v){
					if($v['pid'] == $value['id']){
						$arr[] = $list[$k];
					}
				}
			}
		}
		$this->assign('list', $arr);
		$this->display();
	}

	/**
	 * 添加自定义菜单
	 * zhangxinhe Dec 29, 2015
	 */
	public function add(){
		if(IS_POST){
			$_POST['name'] = trim($_POST['name']);
			$_POST['value'] = trim($_POST['value']);
			$result = $this->updateData($_POST, 'wxmenu');
			$this->returnResult($result);
		}else{
			$list = $this->getList('id,name', 'wxmenu', 'pid=0', 'sort desc');
			$this->ajaxReturn($list);
		}
	}

	/**
	 * 修改自定义菜单
	 * zhangxinhe Dec 29, 2015
	 */
	public function edit(){
		if(IS_POST){
			$_POST['name'] = trim($_POST['name']);
			$_POST['value'] = trim($_POST['value']);
			$result = $this->updateData($_POST, 'wxmenu', 2);
			$this->returnResult($result);
		}else{
			$list = $this->getList('id,name', 'wxmenu', 'pid=0', 'sort desc');
			$info = $this->getInfo('id,pid,name,value,sort', 'wxmenu', 'id=' . I('get.id', 0, 'intval'));
			$info['menu'] = $list;
			$this->ajaxReturn($info);
		}
	}

	/**
	 * 删除自定义菜单
	 * zhangxinhe Dec 29, 2015
	 */
	public function del(){
		$id = I('get.id', 0, 'intval');
		$this->deleteData('pid=' . $id, 'wxmenu');
		$result = $this->deleteData('id=' . $id, 'wxmenu');
		$this->returnResult($result);
	}

	/**
	 * 生成自定义菜单
	 * zhangxinhe Dec 29, 2015
	 */
	public function createMenu(){
		$list = $this->getList('id,pid,name,value', 'wxmenu', null, 'sort desc,id asc');
		if($list){
			foreach($list as $key => $value){
				if($value['pid'] == 0){
					$subMean = array();
					foreach($list as $k => $v){
						if($v['pid'] == $value['id']){
							if(strpos($v['value'], 'http://') === false && strpos($v['value'], 'https://') === false){
								$type = 'click';
								$key = 'key';
							}else{
								$type = 'view';
								$key = 'url';
							}
							$subMean[] = array('type' => $type, 'name' => $v['name'], $key => $v['value']);
						}
					}
					if(count($subMean) > 0){
						$mean[] = array('name' => $value['name'], 'sub_button' => $subMean);
					}else{
						if(strpos($value['value'], 'http://') === false && strpos($value['value'], 'https://') === false){
							$type = 'click';
							$key = 'key';
						}else{
							$type = 'view';
							$key = 'url';
						}
						$mean[] = array('type' => $type, 'name' => $value['name'], $key => $value['value']);
					}
				}
			}
			$wechatAuthInfo = \Common\Api\CommonApi::wechatAuthInfo();
			$result = $wechatAuthInfo->menuCreate($mean);
			if($result['errcode'] == 0){
				$this->returnResult(true, array('创建成功！', null));
			}else{
				$this->returnResult(false, array(null, $result['errmsg']));
			}
		}else{
			$this->ajaxReturn(array('info' => '请先添加菜单！', 'status' => 0));
		}
	}

	/**
	 * 同步自定义菜单
	 * zhangxinhe Dec 29, 2015
	 */
	public function syncMenu(){
		$wechatAuthInfo = \Common\Api\CommonApi::wechatAuthInfo();
		$meanList = $wechatAuthInfo->menuGet();
		if($meanList['errcode'] == '46003'){
			$this->returnResult(true, null, '未设置自定义菜单');
		}else{
			M('wxmenu')->where('id>0')->delete();
			foreach($meanList['menu']['button'] as $key => $value){
				if(!empty($value['type'])){
					$values = $value['type'] == 'click' ? $value['key'] : $value['url'];
					M('wxmenu')->add(array('name' => $value['name'], 'value' => $values));
				}else{
					$pid = M('wxmenu')->add(array('name' => $value['name']));
					foreach($value['sub_button'] as $k => $v){
						$values = $v['type'] == 'click' ? $v['key'] : $v['url'];
						M('wxmenu')->add(array('pid' => $pid, 'name' => $v['name'], 'value' => $values));
					}
				}
			}
			$this->returnResult(true);
		}
	}

	/**
	 * 删除自定义菜单
	 * zhangxinhe Dec 29, 2015
	 */
	public function cancelMenu(){
		$wechatAuthInfo = \Common\Api\CommonApi::wechatAuthInfo();
		$result = $wechatAuthInfo->menuDelete();
		if($result['errcode'] == 0){
			$this->returnResult(true, array('删除成功！', null));
		}else{
			$this->returnResult(false, array(null, $result['errmsg']));
		}
	}
}