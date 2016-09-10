<?php
namespace Home\Controller;
use Common\Controller\BaseController;
/**
 * 文件上传管理
 * zhangxinhe 2015-12-25
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class UploadController extends BaseController{
	public function index() {
		exit;
	}
	/**
	 * 文件上传获取Token
	 * zhangxinhe 2015-12-25
	 */
	public function getToken(){
		$type = I('get.type', 0, 'intval');
		header("Content-type:text/html;charset=utf-8");
		$data =  array("scope" => C('QINIU_BUCKET'), "deadline" => time() + 3600);
		$data1 = array_merge($data, array('returnUrl' => C('site_url').U('Home/Upload/uploadReturn'), 'returnBody' => '{"url":"'.C('QINIU_HOST').'$(key)", "size":$(fsize), "name":"$(fname)"}'));
		$token1 = $this->token($data1, C('QINIU_AK'), C('QINIU_SK'));
		$data2 = array_merge($data, array('callbackUrl' => C('site_url').U('Home/Upload/uploadCallback'), 'callbackBody' => 'url='.C('QINIU_HOST').'$(key)&size=$(fsize)&name=$(fname)'));
		$token2 = $this->token($data2, C('QINIU_AK'), C('QINIU_SK'));
		if($type == 3){
			$this->ajaxReturn(array('token1' => $token1, 'token2' => $token2));
		}else if($type == 2){
			$this->ajaxReturn($token2);
		}else{
			$this->ajaxReturn($token1);
		}
	}
	/**
	 * 单文件上传返回信息
	 * zhangxinhe Dec 25, 2015
	 */
	public function uploadReturn(){
		$str = json_decode(base64_decode(str_replace(array('-', '_'), array('+', '/'), $_GET['upload_ret'])), true);
		exit('{"error":0, "url": "'.$str['url'].'"}');
	}
	/**
	 * 上传成功回调函数
	 * zhangxinhe Dec 25, 2015
	 */
	public function uploadCallback(){
		exit('{"error":0, "url": "'.$_POST['url'].'"}');
	}
	/**
	 * 上传文件到本地服务器
	 * zhangxinhe Dec 25, 2015
	 */
	public function upload(){
		$path = '/whwx/Runtime/Temp/';
		if (!file_exists($_SERVER["DOCUMENT_ROOT"].$path)){
			mkdir($_SERVER["DOCUMENT_ROOT"].$path, 0777, true);
			chmod($_SERVER["DOCUMENT_ROOT"].$path, 0777);
		}
		$fileName = $path.date('YmdHis').strrchr($_FILES['file']['name'], '.');
		move_uploaded_file($_FILES['file']['tmp_name'], $_SERVER["DOCUMENT_ROOT"].$fileName);
		exit('{"error":0, "url": "'.$fileName.'"}');
	}
	//Url_Base64_Encode
	private function token($data, $accessKey, $secretKey){
		$data = str_replace(array('+', '/'), array('-', '_'), base64_encode(json_encode($data)));
		$sign = hash_hmac('sha1', $data, $secretKey, true);
		return $accessKey.':'.str_replace(array('+', '/'), array('-', '_'), base64_encode($sign)).':'.$data ;
	}
	/**
	 * 七牛抓取微信图片
	 * huying Jan 20, 2016
	 */
	public function downPic(){
		$pics = explode(',', $_POST['pics']);
		// 		$path = '/whwx/Runtime/Temp/';
		foreach ($pics as $k => $v){
			if(!empty($v)){
				$wechatAuth = \Common\Api\CommonApi::wechatAuthInfo();
				$access_token = F('access_token');
				$url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token='.$access_token['access_token'].'&media_id='.$v;
				$pic = qiniuFetch($url);
				$list[] = $pic;
			}
		}
		$this->ajaxReturn($list);
	}
}
?>