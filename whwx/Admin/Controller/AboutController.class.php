<?php
namespace Admin\Controller;
use Common\Controller\AdminController;
/**
 * 关于伟星
 * huying Dec 29, 2015
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class AboutController extends AdminController{
	/**
	 * 关于伟星
	 * huying Jan 23, 2016
	 */
	public function index(){
		if(IS_POST){
			$file = CONF_PATH . 'about.php';
			if(is_writable($file)){
				$_POST['about_desc'] = str_replace("'", "‘", $_POST['about_desc']);
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