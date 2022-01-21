<?php 
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/ProductPage.php');

class ProductsController extends Zend_Controller_Action {

  public function __call($p_method, $p_args) {

    //call indexAction
    $this->indexAction();

  }

  /* Date: 07-08-2011
   * By: Hafeez
   * Implemented 301 Redirect for product page...*/
    
  public function indexAction() {  	  	  	
    
    //action is actually style number
    $va_params = $this->_getAllParams();
    $v_style_number = $va_params['action'];
    
    $v_style_number = strip_tags($v_style_number);
    $v_style_number = str_replace("'","",$v_style_number);
	$v_style_number = str_replace(" or","",$v_style_number);
	$v_style_number = str_replace(" OR","",$v_style_number);
	
  	//if($v_style_number=="gkp" || $v_style_number == "30043603"){
			// Ticket TICK:35021
		//	Header( "HTTP/1.1 301 Moved Permanently" );
		//	Header( "Location: " . "http://" . $_SERVER['SERVER_NAME'] . "/product/30046010/golfsmith-gripping-supply-kit"); 
	//}
    $i_ppage = new ProductPage($v_style_number);
    
    $i_ppage->get_seo_url($v_new_url);    
    
    $v_query_string=strip_tags(urldecode($_SERVER['QUERY_STRING']));

	$v_arr_query_string = explode("&",$v_query_string);

    foreach ($v_arr_query_string as $key=> $val){

    	//extract array
        $v_query_key = strstr($val, '=', true);
        $v_query_val = strstr($val, '=');
        $v_query_val = substr($v_query_val, 1);

        if (($v_query_key == 'tcode') || ($v_query_key == 'scode') 
        || ($v_query_key == 'cm_mmc') || ($v_query_key == 'isRedirect') || ($v_query_key == 'cm_lm'))
        {
            $v_new_query_string = $v_new_query_string . '&' . $v_query_key . '=' . $v_query_val;
        }
     }

     //remove the first ampersand
     $v_new_query_string = substr($v_new_query_string, 1);

	if (strlen($v_new_query_string) > 1)
	{
   		$v_new_url = $v_new_url . '?'.  $v_new_query_string;                       
	}
    
    header("HTTP/1.1 301 Moved Permanently");
    header( 'Location: http://'.$_SERVER["HTTP_HOST"] . $v_new_url);
    
  }
  
  public function validateqbdAction() {
   
  	$i_request = $this->getRequest();
    $v_qbd_ordered_qty = strip_tags($i_request->getParam('qbd_ordered_qty'));    
    $v_qbd_style = strip_tags($i_request->getParam('qbd_style'));
    $v_qbd_long_desc = strip_tags($i_request->getParam('qbd_long_desc'));
    $v_qbd_price = strip_tags($i_request->getParam('qbd_price'));
    
    $i_site_init = new SiteInit('product');
    $i_site_init->loadInit($v_connect_mssql);
    
    $objProduct = new ProductPage($v_qbd_style);
    $v_QBD_info = $objProduct->get_QBD_info();

    if ($v_QBD_info['active'] != 'Y'){
    	echo 'Sorry, we do not have any more of the ' . $v_qbd_long_desc . ' at the $' . $v_qbd_price . ' Hot Deal price; the last of the available quantity has just been sold. If you still wish to buy this item you may do so at the regular selling price. Please click the "OK" button to continue.';
    }else {
    	echo 'Success';
    }
        
  }
  
  public function validateatpAction() {  	
  	$i_request = $this->getRequest();
  	$v_style = strip_tags($i_request->getParam('style'));
    $v_ordered_qty = strip_tags($i_request->getParam('ordered_qty'));
    $v_iid = strip_tags($i_request->getParam('inv_id'));
    $v_org_id = strip_tags($i_request->getParam('org_id'));
    
    global $connect_mssql_db;
    $connect_mssql_db = 1;
  	$i_site_init = new SiteInit('product');
    $i_site_init->loadInit($v_connect_mssql);
    
    $objProduct = new ProductPage($v_style);
    
    //$soap_atp = $objProduct->validateATP($v_iid, $v_org_id,$v_ordered_qty,$v_style);
    $soap_atp =  ProductPage::checkATPMSGProc($v_iid,$v_ordered_qty);
    $_SESSION["ATPPromisedDATEs"]["$v_iid"]=$soap_atp["promisedDate"];
	$v_atp = $soap_atp["totalQTY"];
    if (($v_atp >= 0) && ($v_atp < $v_ordered_qty)){
    	echo 'We\'re sorry, but the quantity you requested is not in stock. Please enter a lower quantity and try again';
    }else {
    	echo 'Success';
    }
    
  }
  
}
?>
