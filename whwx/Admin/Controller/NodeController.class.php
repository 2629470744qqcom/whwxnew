<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 节点管理
 * huying Dec 29, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class NodeController extends AdminController{
	/**
	 * 节点列表
	 * huying Dec 29, 2015
	 */
	public function index(){
		$list = $this->getList('id,pid,title,name,type,sort,status', 'node', null, 'sort desc,id asc');
		foreach($list as $key => $value){
			if($value['pid'] == 0){
				$arr[] = $value;
				foreach($list as $k => $v){
					if($v['pid'] == $value['id']){
						$arr[] = $v;
						foreach($list as $k1 => $v1){
							if($v1['pid'] == $v['id']){
								$arr[] = $v1;
							}
						}
					}
				}
			}
		}
		$this->assign('list', $arr);
		$this->display();
	}
	/**
	 * 添加节点
	 * huying Dec 29, 2015
	 */
	public function add(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'node');
			$this->returnResult($result);
		}else{
			$list = $this->getList('id,title,pid,type', 'node', 'status=1 and type<2', 'sort desc,id asc');
			foreach($list as $key => $value){
				if($value['pid'] == 0){
					$arr[] = $value;
					foreach($list as $k => $v){
						if($v['pid'] == $value['id']){
							$arr[] = $v;
						}
					}
				}
			}
			$this->assign('nodeList', $arr);
			$this->display();
		}
	}
	/**
	 * 修改节点
	 * huying Dec 29, 2015
	 */
	public function edit(){
		if(IS_POST){
			$result = $this->updateData($_POST, 'node', 2, 'id='.I('post.id', 0, 'intval'));
			$this->returnResult($result);
		}else{
			$info = $this->getInfo('id,pid,title,type,sort,name,status,note', 'node', 'id=' . I('get.id', 0, 'intval'));
			$this->assign('info', $info);
			$list = $this->getList('id,title,pid,type', 'node', 'status=1 and type<2', 'sort desc,id asc');
			foreach($list as $key => $value){
				if($value['pid'] == 0){
					$arr[] = $value;
					foreach($list as $k => $v){
						if($v['pid'] == $value['id']){
							$arr[] = $v;
						}
					}
				}
			}
			$this->assign('nodeList', $arr);
			$this->display('add');
		}
	}
	/**
	 * 删除节点
	 * huying Dec 29, 2015
	 */
	public function del(){
		$result = $this->deleteData('id=' . I('get.id', 0, 'intval'), 'node');
		$this->returnResult($result);
	}
}