<?php
namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 微信接口
 * zhangxinhe 2014-12-27
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class WeixinController extends BaseController{
	protected $fid, $openid, $data;
	/**
	 * 获取基础数据并回复
	 * zhangxinhe 2015-12-25
	 */
	public function index(){
		$wechat = new \Common\Api\WechatApi($_GET['token']);
		$this->data = $wechat->request();
		$this->openid = $this->data['FromUserName'];
		$data = $this->getResponseContent($this->data);
		if($data){
			list($content, $type) = $data;
			$wechat->response($content, $type);
		}
		exit('success');
	}
	/**
	 * 分析数据并回复内容
	 * zhangxinhe 2015-12-25
	 */
	private function getResponseContent($data){
		// 粉丝信息处理
		$this->fid = \Common\Api\FansApi::getFansId($this->openid);
		if($this->fid == false){
			$this->fid = \Common\Api\FansApi::addFans($this->openid);
		}else{
			\Common\Api\FansApi::updateFansActiveTime($this->fid);
		}
		// 参数不完整时，错误提示
		if($this->fid == false){
			return array('哎呦，好像出了点问题，快去找技术人员帮你看看吧。', 'text');
		}
		// 处理消息内容
		if($data['MsgType'] == 'event'){
			switch($data['Event']){
				case 'subscribe' :
					return $this->subscribe();
					break;
				case 'unsubscribe' :
					return $this->unsubscribe();
					break;
				case 'CLICK' :
					return $this->click();
					break;
				case 'VIEW' :
					return $this->view();
					break;
				case 'LOCATION' :
					return $this->otherEvent();
					break;
				case 'SCAN' :
					return $this->otherEvent();
					break;
			}
		}else{
			switch($data['MsgType']){
				case 'text' :
					$wxMsgId = M('wxmsg')->add(array('fid' => $this->fid, 'times' => time(), 'content' => $this->data['Content']));
					return $this->getContent(null, $wxMsgId);
					break;
				case 'image' :
					return $this->reply(3);
					break;
				case 'voice' :
					return $this->reply(4);
					break;
				case 'video' :
					return $this->reply(5);
					break;
				case 'shortvideo' :
					return $this->reply(6);
					break;
				case 'location' :
					return $this->otherEvent();
					break;
				case 'link' :
					return $this->otherEvent();
					break;
			}
		}
	}
	/**
	 * 关注事件处理
	 * zhangxinhe 2015-12-25
	 */
	private function subscribe(){
		return $this->getContent(C('site_subscribe'));
	}
	/**
	 * 取消关注事件处理
	 * zhangxinhe 2015-12-25
	 */
	private function unsubscribe(){
		M('wxfans')->where('id=' . $this->fid)->setField('status', 0);
		return false;
	}
	/**
	 * 自定义菜单点击事件处理
	 * zhangxinhe 2015-12-25
	 */
	private function click(){
		return $this->getContent($this->data['EventKey']);
	}
	/**
	 * 自定义菜单浏览时间处理
	 * zhangxinhe 2015-12-25
	 */
	private function view(){
		return false;
	}
	/**
	 * 文件类型消息回复
	 * @param integer $type 消息类型 3图片 4语音 5视频 6小视频
	 * zhangxinhe Mar 8, 2016
	 */
	private function reply($type){
		$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
		$file = $wechatAuth->getFileToQiniu($this->data['MediaId']);
		M('wxmsg')->add(array('fid' => $this->fid, 'times' => time(), 'type' => $type, 'content' => $file));
		return $this->getContent(C('site_default'));
	}
	/**
	 * 其他类型事件处理
	 * zhangxinhe 2015-12-25
	 */
	private function otherEvent(){
		return $this->getContent(C('site_default'));
	}
	/**
	 * 根据关键字获取回复内容
	 * @param string $keyword 关键字
	 * @param string $wxMsgId 消息ID
	 *        zhangxinhe 2015-12-25
	 */
	private function getContent($keyword = '', $wxMsgId = 0){
		$keyword = $keyword ? $keyword : $this->data['Content'];
		switch($keyword){
			case 'wx' :
				return array('感谢使用星管家智慧社区，您的公众号已成功绑定，快去体验更多服务吧。', 'text');
				break;
			case '注册' :
				return array(array(array('欢迎使用“星管家”智慧社区，点击图文立即注册', '欢迎使用“星管家”智慧社区，点击图文立即注册', 'http://weixingwuye.com/Wap/Public/index?fid=' . $this->fid . '&openid=' . $this->openid, 'http://weixingwuye.com/Public/pic.png')), 'news');
				break;
			default :
				$array = explode('#',$keyword);
				if($array[0] == 'WXBD'){
					switch($array[1]){
						case 3: $table = 'repairman';break;
						case 4: $table = 'service';break;
						case 5: $table = 'tour_merchant';break;
						default :$table = 'owner';
					}
					$id = M($table)->where('phone = "'.$array[2].'" and id='.$array[3].' and status = 1')->getField('id');
					if($id > 0){
						$result = M('wxfans')->where('type='.$array[1].' and oid='.$id)->getField('id');
						if($result > 0 && $result != $this->fid){
							return array('感谢使用星管家智慧社区，此微信已经绑定过了。', 'text');
						}else{
							$this->updateData(array('id' => $this->fid, 'type' => $array[1], 'oid' => $id), 'wxfans', 2);
							M($table)->where('id='.$id)->setField('fid', $this->fid);
							return array('感谢使用星管家智慧社区，账号绑定成功', 'text');
						}
					}else{
						return array('感谢使用星管家智慧社区，账号绑定失败。', 'text');
					}
				}else{
					$keywordInfo = M('keyword')->field('id,pid,module')->where('keyword="' . $keyword . '"')->find();
					if($keywordInfo){
						switch($keywordInfo['module']){
							case 'Img' :
								return $this->newsMakeMsg($keywordInfo['pid']);
								break;
							case 'Text' :
								return $this->textMakeMsg($keywordInfo['pid']);
								break; 
							case 'Vote' :
								return $this->commonMakeMsg($keywordInfo['pid'], 'vote', 'title', 'title', 'pic', 'Wap/Vote/index');
								break;
							default :
								return $this->commonMakeMsg($keywordInfo['pid'], strtolower($keywordInfo['module']), 'title', 'desc', 'pic', 'Wap/' . $keywordInfo['module'] . '/index');
						}
					}else{
						$wxMsgId > 0 && M('wxmsg')->where('id=' . $wxMsgId)->setField('type', 0);
						// 获取默认回复
						if($keyword != C('site_default')){
							return $this->getContent(C('site_default'));
						}
					}
					return false;
				}
		}
	}
	/**
	 * 根据关键字信息组合数据
	 * @param integer $id 主键的值
	 * @param string $table 表名
	 * @param string $title 标题字段
	 * @param string $desc 描述字段
	 * @param string $pic 图片字段
	 * @param string $url 图文链接
	 * @param string $param 链接参数
	 *        zhangxinhe 2015-12-25
	 */
	private function commonMakeMsg($id, $table, $title, $desc, $pic, $url, $param = null){
		$params = array('fid' => $this->fid, 'openid' => $this->openid, 'id' => $id, 'froms' => 'keyword');
		is_array($param) && $params = array_merge($params, $param);
		$url = C('site_url') . U($url, $params);
		$result = M($table)->field(array($title, $desc, $pic))->where(array('id' => $id))->find();
		if($result){
			$data[] = array($result[$title], $result[$desc], $url, $result[$pic]);
			return array($data, 'news');
		}
		return false;
	}
	/**
	 * 返回图文消息数据（支持单多图文）
	 * @param intger $id 图文消息
	 *        zhangxinhe 2015-12-25
	 */
	private function newsMakeMsg($id){
		$imgInfo = M('wximg')->field('id,title,pic,desc,link,ids')->where('id=' . $id)->find();
		if($imgInfo){
			$data = array($imgInfo);
			if(!empty($imgInfo['ids'])){
				$imgList = M('wximg')->field('id,title,pic,desc,link')->where('id in (' . $imgInfo['ids'] . ')')->select();
				if($imgList){
					array_unshift($imgList, $imgInfo);
					$data = $imgList;
				}
			}
			foreach($data as $value){
				$url = $value['link'] . (strpos($value['link'], '?') === false ? '?' : '&');
				$url .= 'fid=' . $this->fid . '&openid=' . $this->openid;
				$return[] = array($value['title'], $value['desc'], $url, $value['pic']);
			}
			return array($return, 'news');
		}
		return false;
	}
	/**
	 * 返回文本消息数据
	 * @param intger $id 文本消息ID
	 *        zhangxinhe 2015-12-25
	 */
	private function textMakeMsg($id){
		$content = M('wxtext')->where(array('id' => $id))->getField('content');
		if($content){
			return array($content, 'text');
		}
		return false;
	}
}