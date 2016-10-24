<?php
namespace Admin\Controller;
use Common\Controller\AdminController;

class TourOrdersAddController extends AdminController{
	public function index () {
		if (IS_POST) {
			$id = date('ymdHis') . mt_rand(100, 999);
			
			foreach($_POST['name'] as $k => $v){
				if (strpos($v, ' -- ') !== false) {
					$arr = explode(' -- ', $v);
					$v = $arr[1];
				}
				
				$userInfo[] = array('name' => $v, 'idcard' => $_POST['idcard'][$k]);
			}
				
			$data['id']             = $id;
			$data['aid']            = I('post.aid');
			$data['oid']            = I('post.oid');
			$data['mid']            = I('post.mid');
			$data['pid']            = I('post.tour_line_id');
			$data['times']          = time();
			$data['pnum']           = count(I('post.name'));
			$data['pprice']         = I('post.tour_line_price_input');
			$data['pname']          = I('post.tour_line_lname');
			$data['money']          = I('post.tour_line_price_input') * count(I('post.name'));
			$data['status']         = 1;
			$data['phone']          = I('post.phone');
			$data['dates']          = I('post.dates');			
			$data['user']           = json_encode($userInfo);
			$data['comment_status'] = 1;

			$status = M('tour_orders')->data($data)->add();
			$this->returnResult($status);
		}

		$tourLines = $this->getTourLines();
		$this->assign('tourLines', $tourLines);

		$areaList = $this->getAreaList();
		$this->assign('areaList', $areaList);

		$this->display();
	}

	public function getOwnerAjax () {
		$keyword = I('get.term', '', 'strval');

		if (!$keyword) {
			return 0;
		}

		$tmp_1 = M('owner')->alias('o')->join("left join whwx_area a on a.id = o.aid")->where('o.name = "'.$keyword.'" or o.phone = "%'.$keyword.'%"')->field("a.id as a_id, a.name as a_name, o.id as o_id, o.name as o_name, o.phone as o_phone")->select();
		$tmp_2 = M('owner')->alias('o')->join("left join whwx_area a on a.id = o.aid")->where('o.name like "%'.$keyword.'%" or o.phone like "%'.$keyword.'%"')->field("a.id as a_id, a.name as a_name, o.id as o_id, o.name as o_name, o.phone as o_phone")->select();

		$temp = array_merge($tmp_1, $tmp_2);

		$list = array();
		foreach ($temp as $value) {
			$tt['id'] = $value['o_id'];
			$tt['label'] = $value['a_name'] . ' -- ' . $value['o_name'] . ' -- ' . $value['o_phone'];
			$tt['value'] = $value['a_name'] . ' -- ' . $value['o_name'] . ' -- ' . $value['o_phone'];
			$tt['o_phone'] = $value['o_phone'];
			$tt['a_id'] = $value['a_id'];

			$flag = false;
			foreach ($list as $v) {
				if ($v['o_phone'] == $value['o_phone']) {
					$flag = true;
					// continue 2;    // is php5.3 support ?
				}
			}
			if ($flag) {
				continue;
			}

			$list[] = $tt;
		}

		$this->ajaxReturn($list);
	}



	private function getTourLines () {
		$list = M('tour_line')->alias('l')->join("left join whwx_tour_classify c on c.id = l.cid left join whwx_tour_merchant m on m.id = l.mid")->field("m.name as m_name, m.id as m_id, c.name as c_name, l.name as l_name, l.id as l_id, l.pics, l.dates, l.price")->where("l.status = 1")->select();

		foreach ($list as &$value) {
			$value['pics'] = array_shift(explode(',', $value['pics']));
		}

		return $list;
	}
}

?>