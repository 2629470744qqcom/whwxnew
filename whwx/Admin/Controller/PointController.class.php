<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 积分设置
 * huying Dec 29, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class PointController extends AdminController{
	public function index(){
		if(IS_POST){
			$file = CONF_PATH . 'point.php';
			if(is_writable($file)){
				$result = file_put_contents($file, "<?php \nreturn " . stripslashes(var_export($_POST, true)) . ";", LOCK_EX);
				if($result > 0){
					@unlink(RUNTIME_FILE);
					$this->returnResult(true);
				}
			}
			$this->returnResult(false);
		}else{
			$this->display();
		}
	}
}