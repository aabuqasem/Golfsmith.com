<?php

/**
 * OrderstatusController
 * 
 * @author
 * @version 
 */

require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('Zend/View.php');
require_once('models/OrderStatusPage.php');

class OrderstatusController extends Zend_Controller_Action {
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
		echo $this->z_view->render("order_status.phtml");
		// remarkting vars
		$g_data["pageType"]="other";
      	$g_data["prodid"]="";
      	$g_data["totalvalue"]="";
		
		$this->i_site_init->loadFooter($g_data);
	}

	public function orderAction(){
		$v_order_number = $_REQUEST['orderNo'];
		$v_postal_code = $_REQUEST['postalCode'];
		$v_order_number=trim($v_order_number);
		$v_postal_code=trim($v_postal_code);	           
        $obj = new OrderStatusPage(ORDER_STATUS_WSDL_URL);
        $obj->setOrderNumber($v_order_number); 
        $obj->setOrderDetailLevel("HEADER");
        $obj->setPostalCode($v_postal_code);
        $obj->setloggedIn(False);       
		echo $obj->getOrderHeader();
	}
	
	
	public function headersAction(){
		$v_customer_id = $_REQUEST['customerId'];
		$v_show_all_orders = $_REQUEST['showAllOrders'];	    	          
        if ($v_show_all_orders)
        	$v_days_back = ORDER_STATUS_DAYS_BACK_ALL;
        else 
        	$v_days_back = ORDER_STATUS_DAYS_BACK_RECENT;       
		$obj = new OrderStatusPage(ORDER_STATUS_WSDL_URL);
        $obj->setCustomerId($v_customer_id); 
        $obj->setOrderDetailLevel("HEADER");
        $obj->setDaysBack($v_days_back);
        $obj->setloggedIn(True);               
		echo $obj->getOrdersHeaders();
	}
			
	public function showdetailsAction(){		
		$v_order_number = $_REQUEST['orderNo'];    	          
        $obj = new OrderStatusPage(ORDER_STATUS_WSDL_URL);
        $obj->setOrderNumber($v_order_number); 
        $obj->setOrderDetailLevel("FULL");                
		echo $obj->getOrderDetails();
	}

}
