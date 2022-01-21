<?php

/**
 * TradeinController
 * 
 * @author Ahmad Abuqasem
 * @version 
 */

require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('Zend/View.php');
require_once('models/TradeIn.php');

class TradeinController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	private $i_site_init;
		    
	public function init()
    {	
    	$this->i_site_init = new SiteInit();
    	$this->i_site_init->loadInit();
    }
	public function indexAction() {
		$v_port = $v_port = $_SERVER['SERVER_PORT'];
	    if($v_port == '443') {
	    	$this->_redirect('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
	    	return;
	    }
		$this->z_view = new Zend_view(array('scriptPath' => VIEW_PATH));
		$this->i_site_init->loadMain();
		echo $this->z_view->render("trade_in.phtml");
		$this->i_site_init->loadFooter();		
	}

	public function getstateslistAction(){
        $obj = new Tradein();
        echo $obj->getStatesList(); 
	}
	
	public function getstoreslistAction(){
		$v_state = $_REQUEST['state'];
        $obj = new Tradein();
        echo $obj->getStoresList($v_state); 
	}
	
	public function getstorepromoAction(){
		$v_store_id = $_REQUEST['store_id'];
        $obj = new Tradein();
        echo $obj->getStorePromo($v_store_id); 
	}
	
	public function getbrandslistAction(){		
        $obj = new Tradein();
        echo $obj->getBrandsList(); 
	}	

		public function getclubtypelistAction(){	
		$v_brandname = $_REQUEST['brandname'];	
        $obj = new Tradein();
        echo $obj->getClubsTypeList($v_brandname); 
	}	
	
		public function getclubmodellistAction(){	
		$v_clubtype = $_REQUEST['clubtype'];
		$v_brandname = $_REQUEST['brandname'];	
        $obj = new Tradein();
        echo $obj->getClubsmodelList($v_clubtype,$v_brandname); 
	}	
	
		public function getestimatevalueAction(){	
		$p_organization_id = $_REQUEST['organization_id'];
		$v_style = $_REQUEST['style'];
		$v_platform = $_REQUEST['platform'];	
        $obj = new Tradein();
        echo $obj->getEstimateValue($p_organization_id,$v_style,$v_platform); 
	}	
}
