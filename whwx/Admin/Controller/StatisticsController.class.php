<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

/**
 * 报修统计
 * huying Feb 15, 2016
 * 版权所有：安徽鼎龙网络传媒有限公司
 */
class StatisticsController extends AdminController{
	public $where, $date;

	protected function _initialize(){
		parent::_initialize();
		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);
		$this->where = 'aid=' . I('get.aid', 0, 'intval');
		if($_GET['start_time'] && $_GET['end_time']){
			$start_time = strtotime($_GET['start_time']);
			$end_time = strtotime($_GET['end_time']);
			for($i = $start_time; $i <= $end_time; $i += 24 * 3600){
				$this->date[] = date('Ymd', $i);
			}
			$this->where .= ' and times<=' . str_replace('-', '', I('get.end_time')) . ' and times>=' . str_replace('-', '', I('get.start_time'));
		}else{
			$day = I('get.day', 7, 'intval');
			if($day == 6 || $day == 12){
				for($i = 1; $i <= $day; $i++){
					$this->date[] = date('Ym', strtotime('-' . $i . ' month'));
				}
				$this->where .= ' and times<' . date('Ym') . ' and times>=' . date('Ym', strtotime('-6 month'));
			}else{
				for($i = 1; $i <= $day; $i++){
					$this->date[] = date('Ymd', time() - $i * 24 * 3600);
				}
				$this->where .= ' and times<' . date('Ymd') . ' and times>=' . date('Ymd', time() - $day * 24 * 3600);
			}
		}
	}

	public function index(){
		exit();
	}

	/**
	 * 报修统计
	 * zhangxinhe 2016年5月11日
	 */
	public function repair(){
		$dataNum = M('count_repair')->field('creat_num,complate_num,handle_num,times')->where($this->where)->order('times desc')->select();
		$dataScore = M('count_score')->field('num,score,times')->where($this->where . ' and type=1')->order('times desc')->select();
		foreach($dataNum as $v){$dataNum2[] = array('y' => $v['times'], 'a' => $v['creat_num'], 'b' => $v['handle_num'], 'c' => $v['complate_num']);}
		$dayNum = $this->date;
		foreach($this->date as $k => $v){foreach($dataNum2 as $value){if($value['y'] == $v){unset($dayNum[$k]);}}}
		foreach($dayNum as $v){$dataNum2[] = array('y' => $v, 'a' => '0', 'b' => '0', 'c' => '0');}
		$this->showResult($dataNum2, $dataScore);
	}

	/**
	 * 缴费统计
	 * zhangxinhe 2016年5月11日
	 */
	public function payment(){
		$dataNum = M('count_payment')->field('money,times,pay_cate,pay_type')->where($this->where)->order('times desc')->select();
		$cateKey = array('times', 'porperty', 'energy', 'water', 'carport', 'arrear_money', 'car_manger');
		$typeKey = array('times', 'weipay', 'offline', 'point');
		$times = 0;
		foreach($dataNum as $k => $v){
			if($times != $v['times']){
				$times = $v['times'];
				$dataNumCate[$k]['times'] = $dataNumType[$k]['times'] = $v['times'];
				foreach($dataNum as $value){
					if($value['times'] == $v['times'] && $value['pay_cate']){$dataNumCate[$k][$value['pay_cate']] = $value['money'];}
					if($value['times'] == $v['times'] && $value['pay_type']){$dataNumType[$k][$typeKey[$value['pay_type']]] = $value['money'];}
				}
				foreach($cateKey as $keys){if(!$dataNumCate[$k][$keys]){$dataNumCate[$k][$keys] = '0';}}
				foreach($typeKey as $keys){if(!$dataNumType[$k][$keys]){$dataNumType[$k][$keys] = '0';}}
			}
		}
		$dayCate = $this->date;
		foreach($this->date as $k => $v){foreach($dataNumCate as $value){if($value['times'] == $v){unset($dayCate[$k]);}}}
		foreach($dayCate as $v){$dataNumCate[] = array('times' => $v, 'arrear_money' => '0', 'car_manger' => '0', 'carport' => '0', 'energy' => '0', 'porperty' => '0', 'water' => '0');}
		$dayType = $this->date;
		foreach($this->date as $k => $v){foreach($dataNumType as $value){if($value['times'] == $v){unset($dayType[$k]);}}}
		foreach($dayType as $v){$dataNumType[] = array('times' => $v, 'weipay' => '0', 'offline' => '0', 'point' => '0');}
		foreach($dataNumCate as $k => $v){ksort($v); $dataNumCate2[] = $v;}
		foreach($dataNumType as $k => $v){ksort($v); $dataNumType2[] = $v;}
		$data = array('dataNum' => $dataNumCate2, 'dataScore' => $dataNumType2);
		if(IS_AJAX){
			$this->ajaxReturn($data);
		}else{
			$this->assign('data', $data);
			$this->display();
		}
	}

	/**
	 * 特惠团统计
	 * zhangxinhe 2016年5月11日
	 */
	public function group(){
		$dataNum = M('count')->field('num,times')->where($this->where . ' and type=2')->order('times desc')->select();
		$dataScore = M('count_score')->field('num,score,times')->where($this->where . ' and type=3')->order('times desc')->select();
		foreach($dataNum as $v){$dataNum2[] = array('y' => $v['times'], 'a' => $v['num']);}
		$date = $this->date;
		foreach($this->date as $k => $v){foreach($dataNum2 as $value){if($value['y'] == $v){unset($date[$k]);}}}
		foreach($date as $v){$dataNum3[] = array('y' => $v, 'a' => '0');}
		$this->showResult($dataNum3, $dataScore);
	}

	/**
	 * 预约服务统计
	 * zhangxinhe 2016年5月11日
	 */
	public function booking(){
		$dataNum = M('count')->field('num,times')->where($this->where . ' and type=1')->order('times desc')->select();
		$dataScore = M('count_score')->field('num,score,times')->where($this->where . ' and type=4')->order('times desc')->select();
		foreach($dataNum as $v){$dataNum2[] = array('y' => $v['times'], 'a' => $v['num']);}
		$date = $this->date;
		foreach($this->date as $k => $v){foreach($dataNum2 as $value){if($value['y'] == $v){unset($date[$k]);}}}
		foreach($date as $v){$dataNum3[] = array('y' => $v, 'a' => '0');}
		$this->showResult($dataNum3, $dataScore);
	}

	/**
	 * 投诉建议统计
	 * zhangxinhe 2016年5月11日
	 */
	public function complaint(){
		$dataNum = M('count')->field('num,times')->where($this->where . ' and type=3')->order('times desc')->select();
		$dataScore = M('count_score')->field('num,score,times')->where($this->where . ' and type=5')->order('times desc')->select();
		foreach($dataNum as $v){$dataNum2[] = array('y' => $v['times'], 'a' => $v['num']);}
		$date = $this->date;
		foreach($this->date as $k => $v){foreach($dataNum2 as $value){if($value['y'] == $v){unset($date[$k]);}}}
		foreach($date as $v){$dataNum2[] = array('y' => $v, 'a' => '0');}
		$this->showResult($dataNum2, $dataScore);
	}

	/**
	 * 数据处理
	 * @param array $dataNum
	 * @param array $dataScore zhangxinhe 2016年5月11日
	 */
	protected function showResult($dataNum, $dataScore){
		$times = 0;
		$key = array('y', 'a', 'b', 'c', 'd', 'e');
		foreach($dataScore as $k => $v){
			if($times != $v['times']){
				$times = $v['times'];
				$dataScore2[$k][$key[0]] = $v['times'];
				foreach($dataScore as $value){if($value['times'] == $v['times']){$dataScore2[$k][$key[$value['score']]] = $value['num'];}}
				foreach($key as $keys){if(!$dataScore2[$k][$keys]){$dataScore2[$k][$keys] = '0';}}
			}
		}
		$date = $this->date;
		foreach($this->date as $k => $v){foreach($dataScore2 as $value){if($value['y'] == $v){unset($date[$k]);}}}
		foreach($date as $v){$dataScore2[] = array('y' => $v, 'a' => '0', 'b' => '0', 'c' => '0', 'd' => '0', 'e' => '0');}
		foreach($dataScore2 as $v){ksort($v); $dataScore3[] = $v;}
		$data = array('dataNum' => $dataNum, 'dataScore' => $dataScore3);
		if(IS_AJAX){
			$this->ajaxReturn($data);
		}else{
			$this->assign('data', $data);
			$this->display();
		}
	}
}