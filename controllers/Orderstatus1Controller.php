<?php

/**
 * Orderstatus1Controller
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once('models/SiteInit.php');
require_once('Zend/View.php');
require_once('models/SoapClientHandle.php');

class Orderstatus1Controller extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
		private $i_site_init;

		
	public function indexAction() {
		$v_port = $v_port = $_SERVER['SERVER_PORT'];
	    if($v_port == '443') {
	      $this->_redirect('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
	      return;
	    }
		// TODO Auto-generated Orderstatus1Controller::indexAction() default action
		$this->i_site_init = new SiteInit();
		$this->z_view = new Zend_view(array('scriptPath' => VIEW_PATH));
		$this->i_site_init->loadInit();
		$this->i_site_init->loadMain();
		//echo $this->z_view->data = "TEST";
		echo $this->z_view->render("orderstatus.phtml");
		$this->i_site_init->loadFooter();
	}
	public function ajaxAction(){
		$v_order_number = $_REQUEST['orderNo'];
		$v_postal_code = $_REQUEST['postalCode'];
	    $request = array("orderNumber" => $v_order_number, "orderDetailLevel" => "FULL", "postalCode" => $v_postal_code);	    
        //$request = array("customerId" => "$v_customer_id", "orderDetailLevel" => "HEADER", "daysBack" => "100");
        $obj = new SoapClientHandle("https://dev6.golfsmith.com/roService.wsdl?wsdl");
        $result = $obj->doRequest($request, "roRequest");        
        $results = array();        
        foreach ($result->orders->order as $order){
        	if (!empty($order->OriginalSystemReference)){
        		$results["resultStatus"]= "ok";
            	$results["orderNumber"] = $order->OriginalSystemReference;
            	$results["PostalCode"] = $order->Customer->ShipAddress->PostalCode;           	
            	$results["DatePlaced"] = $order->DateOrdered;
				$results["DateShiped"] = $order->ShipDate;
          		$results["Status"] = $order->Status;
          		$results["TrackingNumber"] = $order->TrackingNumber;
          		$results["ShipMethodCode"] = $order->ShipMethodCode;
          		$results["OrderTotal"] = $order->OrderTotal;        	          									
       		
          		header('Content-type: application/json');
  		    	echo (json_encode($results));
	        	exit();
          }      		      		
        }		
	}
}
