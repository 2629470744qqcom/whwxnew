<?php
namespace Common\Api;
/**
 * 粉丝管理相关接口
 * zhangxinhe 2015-12-25
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class FansApi{

	/**
	 * 获取粉丝ID
	 * @param string $openid zhangxinhe 2015-12-25
	 */
	public static function getFansId($openid){
		$fid = M('wxfans')->where(array('openid' => $openid, 'status' => 1))->getField('id');
		return $fid > 0 ? $fid : false;
	}

	/**
	 * 添加粉丝
	 * @param array $data 粉丝数据
	 *        zhangxinhe 2015-12-25
	 */
	public static function addFans($openid){
		$fid = M('wxfans')->where(array('openid' => $openid))->getField('id');
		if($fid > 0){
			M('wxfans')->where(array('id' => $fid))->setField('status', 1);
			return $fid;
		}
		$data = array('openid' => $openid, 'active_time' => time());
		$wechatAuthInfo = \Common\Api\CommonApi::wechatAuthInfo();
		$userInfo = $wechatAuthInfo->userInfo($openid);
		if(!$userInfo['errcode']){
			$data = array_merge($userInfo, $data);
		}
		return M('wxfans')->add($data);
	}

	/**
	 * 更新粉丝活跃时间
	 * @param intger $id zhangxinhe 2015-12-25
	 */
	public static function updateFansActiveTime($id){
		return M('wxfans')->where('id=' . $id)->setField('active_time', time());
	}
}