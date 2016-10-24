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
	protected function getList($field, $table, $where, $order, $page = false, $listrows = 12, $showSql = false){
		$table = stripos($table, ',') === false ? C('DB_PREFIX') . $table : $table;
		$model = new Model();
		if($page == true){
			$totalrows = $model->table($table)->where($where)->count();
			$page = new \Think\Page($totalrows, $listrows);
			$limit = $page->firstRow . ',' . $page->listRows;
			$this->assign('page', $page->show());
		}

		if ($showSql) {
			return $model->table($table)->field($field)->where($where)->order($order)->limit($limit)->fetchSql(true)->select();
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

			if ("Admin" == MODULE_NAME and !$url and array_key_exists('HTTP_REFERER', $_SERVER) and $_SERVER['HTTP_REFERER']) {
				unset($data['url']);
			}
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


	/**
	 * @param  $line_id        	tour_orders 的 id
	 * @param  $merchant_id    	旅行社id
	 * @param  $line_name      	线路名称
	 * @param  $contact_name   	联系人的名称
	 * @param  $contact_phone  	联系人的电话
	 * @param  $dates     		本次旅游的时间
	 * @param  $num       		本次旅游的总人数
	 * @return [type]
	 */
	protected function sendTourMerchantWXTemplate ($line_id, $merchant_id, $line_name, $contact_name, $contact_phone, $dates, $num) {
		$openid = M()->table('whwx_tour_merchant m,whwx_wxfans f')->where('m.fid>0 and m.fid=f.id and m.id=' . $merchant_id)->getField('openid');

		if($openid){
			$msgData = array(
							'first' => array(
								'value' => '有新的旅游订单，快去看看吧！', 
								'color' => '#ff0000'
							), 
							'tradeDateTime' => array(
								'value' => date('Y-m-d H:i'), 
								'color' => '#173177'
							), 
							'orderType' => array(
								'value' => $line_name, 
								'color' => '#173177'
							),
							'customerInfo' => array(
								'value' => $contact_name . ' ' . $contact_phone, 
								'color' => '#173177'
							), 
							'orderItemName' => array(
								'value' => '游玩信息'
							), 
							'orderItemData' => array(
								'value' => $dates . '出发 共' . $num . '人', 
								'color' => '#173177'
							), 
							'remark' => array(
								'value' => '点击查看更多信息', 
								'color' => '#173177'
							)
						);

			// 获取业主所在小区的维修工的信息
			$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
			$wechatAuth->sendTemplateMsg($merchant_id, C('tour_template'), U('Wap/TourMerchant/ordersInfo?id='.$line_id), $msgData);
		}
	}
}
?>