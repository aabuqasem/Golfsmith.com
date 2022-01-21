<?php
require_once('Zend/View.php');
class RecentView{
	
	private $numberitems = 6; 
	private $arrstart = array(1); // store number of start items
	private $arrend = array(); // store number of end items
	
	
	public function displayRecentlyView($object)
	{
		 $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
		 $z_view->obj = $object;
	 	 $z_view->customer_recent_view = "customer_recent_review.phtml";
		 echo $z_view->render($z_view->customer_recent_view);
	}
	
	public function generatedArrayList($countlist)
	{
		$k = 1;
		$ke = 0;
		for($i=0;$i<$countlist;$i++)
		{
			$k = $k + $this->numberitems;
			$ke = $ke + $this->numberitems;
			$this->arrstart[] = $k;
			$this->arrend = $ke;
		}
	}
	

	
	
	public function searchNumberInArray($number,$array=array())
	{
		for($i=0;$i<sizeof($array);$i++)
		{
			if($array[$i]==$number)
				return true;
		}
		return false;
	}
	
	public function getNumberitems() {
		return $this->numberitems;
	}

	public function setNumberitems($numberitems) {
		$this->numberitems = $numberitems;
	}
	public function getArrstart() {
		return $this->arrstart;
	}

	public function getArrend() {
		return $this->arrend;
	}

	public function setArrstart($arrstart) {
		$this->arrstart = $arrstart;
	}

	public function setArrend($arrend) {
		$this->arrend = $arrend;
	}
}