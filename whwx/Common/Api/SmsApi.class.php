<?php
namespace Common\Api;
class SmsApi{
	const apiUrl1 = 'http://115.28.50.135:8888/sms.aspx?action=send&userid=1672&account=民生装修&password=123456&sendTime=&extno=&';

	/**
	 * 发送短信，每条60字内
	 * @param string $tel 接收号码
	 * @param string $content 发送内容
	 *        zhangxinhe 2015-12-25
	 */
	static function sendSms($tel, $content){
		$url = self::apiUrl1 . 'mobile=' . $tel . '&content=' . $content;
		$result = simplexml_load_string(file_get_contents($url));
		if($result->returnstatus == 'Success'){ // 发送成功
			M('record_sms')->add(array('tel' => $tel, 'content' => $content, 'times' => time()));
			return true;
		}else{
			self::sendEmail(C('site_email'), '芜湖伟星物业短信发送失败，URL地址：' . $url, '短信平台发送失败');
			return false;
		}
	}

	/**
	 * 邮件发送
	 * @param string $user 收件人邮箱
	 * @param string $content 邮件内容
	 * @param string $subject 邮件主题
	 *        zhangxinhe 2015-12-25
	 */
	static function sendEmail($user, $content, $subject){
		vendor('PHPMailer.class#phpmailer');
		$mail = new \PHPMailer();
		$mail->IsSMTP();
		$mail->SMTPAuth = true;
		$mail->CharSet = "UTF-8";
		$mail->Host = 'smtp.mxhichina.com';
		$mail->Port = '25';
		$mail->Username = 'service@weilt.net';
		$mail->Password = 'ahdl3880882';
		$mail->From = 'service@weilt.net';
		$mail->Subject = $subject;
		$mail->FromName = "微老头客户服务部";
		$mail->AddAddress($user, "尊敬的微老头用户");
		$mail->IsHTML(true);
		$mail->Body = $content;
		return $mail->Send();
	}
}