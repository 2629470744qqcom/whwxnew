<?php
namespace Common\Controller;
use Think\Controller;
use Think\Model;

/**
 * 项目基类
 * zhangxinhe 2015-12-25
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class BaseController extends Controller{
	protected function _initialize(){
		header("Content-type: text/html; charset=utf-8");
		define('JQ', 'http://apps.bdimg.com/libs/jquery/1.8.2/jquery.min.js');
		define('MAP', 'http://api.map.baidu.com/api?v=2.0&ak=7E10638907487f0b95529028323dc734');
		define('COM', '/Public/Common/');
		define('CSS', '/Public/' . MODULE_NAME . '/css/');
		define('JS', '/Public/' . MODULE_NAME . '/js/');
		define('IMG', '/Public/' . MODULE_NAME . '/images/');
	}
	/**
	 * 获取列表信息，支持分页
	 * @param string $field 需要查询的字段
	 * @param string $table 数据来源表
	 * @param string $where 查询条件
	 * @param string $order 排序规则
	 * @param string $page 是否分页
	 * @param number $listrows 分页大小
	 *        zhangxinhe 2015-12-25
	 */
	protected function getList($field, $table, $where, $order, $page = false, $listrows = 12){
		$table = stripos($table, ',') === false ? C('DB_PREFIX') . $table : $table;
		$model = new Model();
		if($page == true){
			$totalrows = $model->table($table)->where($where)->count();
			$page = new \Think\Page($totalrows, $listrows);
			$limit = $page->firstRow . ',' . $page->listRows;
			$this->assign('page', $page->show());
		}
		return $model->table($table)->field($field)->where($where)->order($order)->limit($limit)->select();
	}
	/**
	 * 获取记录信息
	 * @param string $field 需要查询的字段
	 * @param string $table 数据来源表
	 * @param string $where 查询条件
	 *        zhangxinhe 2015-12-25
	 */
	protected function getInfo($field, $table, $where){
		$table = stripos($table, ',') === false ? C('DB_PREFIX') . $table : $table;
		return M()->table($table)->field($field)->where($where)->find();
	}
	/**
	 * 插入和更新数据，更新时可能返回0，请用恒等判断
	 * @param array $data 数据源
	 * @param string $table 操作的数据表
	 * @param number $action 操作类型，1添加（默认），2更新
	 * @param string $where 更新条件
	 *        zhangxinhe 2015-12-25
	 */
	protected function updateData($data, $table, $action = 1, $where = ''){
		$model = M($table);
		if($model->create($data, $action)){
			if($action == 1){
				return $model->add();
			}else{
				if(empty($where)){
					return $model->save();
				}else{
					return $model->where($where)->save();
				}
			}
		}
		return false;
	}
	/**
	 * 删除数据，返回值可能为0，请用恒等判断
	 * @param string $where 删除条件
	 * @param string $table 删除数据表
	 *        zhangxinhe 2015-12-25
	 */
	protected function deleteData($where, $table){
		return M($table)->where($where)->delete();
	}
	/**
	 * 返回处理结果
	 * @param boolean $result 处理结果
	 * @param array $info 提示消息
	 * @param string $url 跳转链接
	 * @param array $params 附带参数
	 *        zhangxinhe Dec 25, 2015
	 */
	protected function returnResult($result, $info = null, $url = null, $params = array()){
		$info = $info ? $info : array('操作成功', '操作失败');
		list($success, $error) = $info;
		if($result === false){
			$data = array('info' => $error, 'status' => 0);
		}else{
			$data = array('info' => $success, 'status' => 1);
		}
		if($url !== false){
			$data['url'] = $url ? $url : U(CONTROLLER_NAME . '/index');
		}
		$this->ajaxReturn(array_merge($data, $params));
	}

	/**
	 * 积分变化
	 * @param unknown $oid 业主id
	 * @param unknown $point 积分
	 * @param unknown $name 标题名
	 * @param integer $act 事件 （1：注册，2：缴费，3：报修，4：购物送，5：发帖，6：回帖，7：购物花积分, 8: 缴费抵用, 9:管理员删除帖子/回复, 0:装修申请赠送, 10 :评价赠送）
	 * @param integer $act 事件id
	 * @param string $type 类型 （1：添加，0，减少）
	 *        huying Jan 21, 2016
	 */
	protected function changePoint($oid, $point, $name, $act, $act_id, $type = 1){
		$result = $this->updateData(array('oid' => $oid, 'point' => $point, 'name' => $name, 'type' => $type == 1 ? 1 : 0, 'act' => $act, 'act_id' => $act_id, 'times' => time()), 'point');
		if($result !== false){
			if($type == 1){
				$result = M('owner')->where('id=' . $oid)->setInc('point', $point);
			}else{
				$result = M('owner')->where('id=' . $oid)->setDec('point', $point);
			}
			return true;
		}
		return false;
	}
}
?>