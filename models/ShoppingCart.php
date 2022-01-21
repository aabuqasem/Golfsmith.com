<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here


require_once('Zend/View.php');
require_once('CheckoutPage.php');
require_once('Order.php');
require_once('AbandonedCart.php');
include_once('functions/QBD_functions.php');
include_once("Slider.php");
require_once('ATPExtendedSoap.php');
require_once('StoreFinder.php');
require_once('ISPPage.php');
require_once('ProductPage.php');
require_once('MasterPassCheckout/MasterPassController.php');
require_once('MasterPassCheckout/MasterPassData.php');


class ShoppingCart extends CheckoutPage {

  //need to set the parameter in the parent constructor (shopping cart only)
  public function __construct() {
    parent::__construct(TRUE);
  }
 
 /*
   * Autho Chantrea 02/29/2012
   * Display Customer who viewed this item also bought.
   */
  public function displayCustomerReView($p_show)
  {
  	$view = new Zend_view(array('scriptPath'=> VIEW_PATH));
  	$view->show_div = $p_show;  	

  	$this->getCartItems($va_items, $va_cm_tags, $v_google_checkout_allowed, $v_paypal_checkout_allowed);

	 
	$va_also_purchased = array();
       // Initializing $v_sequence counter to loop until end of record
    foreach ($va_items as $item){
    	$arr = array();   
   		$arr = $this->generateAlsoPurchasedStyleNumber($item['style_number']);
   		foreach($arr as $aritem){
   			$va_also_purchased[] = $aritem;
   		}	
    }

	$obj_slider = new Slider($va_also_purchased,6,"customer");
	$obj_slider->displaySlider();
  	
  }
  
  
function generateAlsoPurchasedStyleNumber($p_style_number) {
  	global $web_db;    
    
    $v_sql = "select cross_style_number 
              from gsi_cmetric_upsales 
              where style_number ='$p_style_number' 
              and slot_number <7";

    $v_result = mysqli_query($web_db, $v_sql);
    display_mysqli_error($v_sql);
    $va_myrow = array();
    while($row = mysqli_fetch_array($v_result))
    {
    	array_push($va_myrow,$row['cross_style_number']);
    } 
    
	 mysqli_free_result($v_result);
	 
	return $va_myrow;
   }
  
  //display the cart page
  public function displayPage() {

	$this->updateOrderTrackingIp();
	$this->i_site_init->loadMain();	
	echo $this->displayHeader('cart');
	echo $this->displayItemListing(TRUE);
	echo $this->displayCartFooter('', '', '', TRUE);
	
	echo $this->displayCartCheckoutBottom(TRUE);
	
	echo $this->displayFooter('cart');

	echo $this->displayCustomerReView(TRUE);
	
	// remarketing var
	global $remarketing_sub_total,$remarketing;
	//var_dump($remarketing);
	//var_dump($remarketing_sub_total) ;
	$g_data["pageType"]="cart";
    $g_data["prodid"]= $this->googleRemarktingProducIdGen($remarketing);
    $g_data["totalvalue"]=$remarketing_sub_total;
	//
	$this->i_site_init->loadFooter($g_data);
			
  }
  
function googleRemarktingProducIdGen($products){
	if (count ($products) == 1){
		return $products[0];
	} else {
		$return = "[";
		foreach ($products as $product){
			$return .= "'".$product."',";
		}
		$return = rtrim ($return,",");
		$return .= "]";
		return $return; 
	}
}

  public function displayCartCheckoutBottom($p_show_div = TRUE) {
    $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

    $z_view->show_div = $p_show_div;

    //check for HTTPS
    $v_port = $_SERVER[SERVER_PORT];
    if($v_port != '443') {
      $z_view->using_https = FALSE;
    } else {
      $z_view->using_https = TRUE;
    }
    
    
    //by Luis for ISP Project
    $i_order = new Order($_SESSION['s_order_number']);
    $z_view->is_isp = $i_order->isISP();
    $z_view->isp_only  = $i_order->isISPOnly();
    
    if(empty($_SESSION['s_customer_id'])){
        $z_view->is_logged_in  = "N";
    }else{
        $z_view->is_logged_in  = "Y";
    }
    
    $z_view->merchantID = CheckoutByAmazon_Service_MerchantValues::getInstance()->getMerchantId();
    $z_view->displayMasterPass = $_SESSION["displayMasterPass"];
    
    if($_SESSION['mp_long_access'] && FALSE) // MP Express Checkout
    {
        $z_view->expressMasterPass = $this->postPreCheckoutData();
    }
    
    return $z_view->render("cart_checkout_bottom.phtml");
  }

  public function displayCartFooter($p_ship_method, $p_postal_code, $p_source_code, $p_show_div = TRUE) {
    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));

    $z_view->show_div = $p_show_div;

    $i_order = new Order($this->v_order_number);
    //$z_view->has_external_items = $i_order->checkForPrebook();

    if(empty($p_ship_method)) {

      if(!empty($_SESSION['smethod']) && $_SESSION['smethod'] != 'undefined') {
        $z_view->shipping_method = $_SESSION['smethod'];
      } else if(!empty($_SESSION['s_ship_method']) && $_SESSION['s_ship_method'] != 'undefined') {
        $z_view->shipping_method = $_SESSION['s_ship_method'];
      } else {
        $z_view->shipping_method = 'G';
      }
    } else {
      $_SESSION['smethod'] = $p_ship_method;
      $_SESSION['s_ship_method'] = $p_ship_method;
      $z_view->shipping_method = $p_ship_method;
    }

    $_SESSION['smethod'] = $z_view->shipping_method;
    $_SESSION['s_ship_method'] = $z_view->shipping_method;

    $i_order->updateOrder($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_ship_address_id'], $_SESSION['s_ship_contact_id'], $z_view->shipping_method);

    if(!empty($p_source_code)) {
      $_SESSION['s_source_code'] = $p_source_code;
    }

    //by this point, source code should already be in the session
    if(!empty($_SESSION['s_source_code'])) {

      $z_view->promotion_code = $_SESSION['s_source_code'];

      $z_view->promotion_valid = $i_order->updatePromoCode();

      if($z_view->promotion_valid === TRUE) {
        $z_view->promotion_code = $_SESSION['s_source_code'];
        $z_view->promotion_name = $this->getPromoName($z_view->promotion_code);
        $z_view->promotion_savings = format_currency($i_order->getTotalPromotionSavings());
      }
    }

    $this->getMerchandiseTotal($v_num_items, $v_merchandise_total);

    //hopefully, we already have a postal code in the session at this point, via an Ajax operation
    $v_postal_code = $p_postal_code;
    if(empty($p_postal_code)) {
      if(!empty($_SESSION['s_zipcode'])) {
        $v_postal_code = $_SESSION['s_zipcode'];
      } else {
        if($_SESSION['site'] == 'US') {
          $v_postal_code = '78728';
        } else {
          $v_postal_code = 'L5T 2J4';
        }
      }
    }
    
    $v_isUSZip = true;
    $v_zip_tmp = str_replace('-', '', $v_postal_code);
    if(preg_match('/[^0-9]/', $v_zip_tmp)) {
      $v_isUSZip = false;
    } 
    
    if (($_SESSION['site'] != 'US') && ($v_isUSZip)) {
    	$v_postal_code = 'L5T 2J4';
    }

    $_SESSION['s_zipcode'] = $v_postal_code;
    $_SESSION['visitor']['zip'] = $_SESSION['s_zipcode']; // used for stores/events search

    if(!empty($v_postal_code)) {
      if($_SESSION['site'] == 'US') {
        $this->updateIspLineAddresses();
        $this->calculateIspTaxZip(strtoupper($v_postal_code));
        $v_isp_totals_string = $i_order->getISPTotalsString();
        $i_order->extractISPTotals($v_isp_totals_string, $v_isp_total, $v_isp_sub_total, $v_isp_tax);
      }

      $z_view->merchandise_total = $v_merchandise_total + $v_isp_sub_total;
//Shipping Dropdown --Comment out below line
      $i_order->getEstimatedShipping($v_postal_code, '', $va_shipping_options);

      /*
      //recompute only when shipping options are not set or if postal code changed
      if ( (empty($_SESSION['s_ship_opt'])) || ($_SESSION['s_ship_opt_postal'] != $v_postal_code))
      {
      	$i_order->getEstimatedShipping($v_postal_code, '', $va_shipping_options);
      	$_SESSION['s_ship_opt'] = $va_shipping_options;
      	$_SESSION['s_ship_opt_postal'] = $v_postal_code;  
      } else {
      	$va_shipping_options = $_SESSION['s_ship_opt'];
      }
      */
      
      $z_view->shipping_total = $va_shipping_options[$z_view->shipping_method]['shipping'];

      $z_view->tax_total = $va_shipping_options[$z_view->shipping_method]['tax'] + $v_isp_tax;
      
      //$z_view->order_total = $z_view->merchandise_total + $z_view->shipping_total + $z_view->tax_total;
      $z_view->order_total = $z_view->merchandise_total + $z_view->shipping_total;


      $z_view->shipping_options = $va_shipping_options;
    }

    if($i_order->isIspStsOnly()) {
      $z_view->show_shipping = FALSE;
    } else {
       // $z_view->show_shipping = TRUE;
     //Shipping Dropdown comment above line and use below
      $z_view->order_total = $z_view->merchandise_total;
      $z_view->show_shipping = FALSE;
    }

    $z_view->has_dropship = $i_order->hasDropship();

    $z_view->postal_code = $v_postal_code;
    
    if ($z_view->order_total > 0)
    {
    	$z_view->show_cart_footer = TRUE;
    }else {
    	$z_view->show_cart_footer = FALSE;
    }
    
    $_SESSION['CartTotal'] = format_currency($z_view->order_total); 
    echo '<SCRIPT TYPE="text/javascript">document.getElementById(\'CartTotal\').innerHTML = \'<strong>' . $_SESSION['CartTotal'] . '</strong>\';</SCRIPT>';

    return $z_view->render("cart_footer.phtml");
  }

  
  public function updateCartPromoCode($p_source_code) {

    $i_order = new Order($this->v_order_number);

    if(!empty($p_source_code)) {
      $_SESSION['s_source_code'] = $p_source_code;
    }
    
    //by this point, source code should already be in the session
    if(!empty($_SESSION['s_source_code'])) {
      $v_promotion_valid = $i_order->updatePromoCode();
    }
    
    $v_ret = 'invalid';
    
    if ($v_promotion_valid === TRUE)
    {
    	$v_ret = 'valid';
    }

    return $v_ret;
  }
  
  
  //display the item listing for the cart
  public function displayItemListing($p_show_containing_div = TRUE) {
  	
  	$_SESSION['s_do_reprice'] = 'Y' ;
  	$_SESSION['s_do_totals'] = 'Y';
  	$i_order = new Order($this->v_order_number);
  	$i_gc_payment = new GiftCardPayment();  	
    $i_ppage = new ProductPage();
      	
  	$show_shipping_time = $i_ppage->getShippingCutOff();
  	$i_order->loadFreegoods();
  	$i_order->updateFreeShipping();
    $i_order->repriceOrder();
	
    $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_return_status);
    
    //the google remarketing var to get the total value
    global $remarketing_sub_total;
    $remarketing_sub_total = $v_sub_total;
    
    $i_gc_payment->adjustGiftCardPayments();

  	$z_view = new Zend_View(array('scriptPath' => VIEW_PATH));

  	if ($_SESSION['site'] != 'US')
  	{
  		$z_view->empty_cart_path = HTML_PATH . '_shopping_cart/empty_cart_ca.html';
  	} else {
    	$z_view->empty_cart_path = HTML_PATH . '_shopping_cart/empty_cart.html';
  	}

    $this->getCartItems($va_items, $va_cm_tags, $v_google_checkout_allowed, $v_paypal_checkout_allowed, $v_amazon_checkout_allowed);

    $z_view->show_containing_div = $p_show_containing_div;
    $z_view->items = $va_items;
    $z_view->cm_tags = $va_cm_tags;
    $z_view->google_checkout_allowed = $v_google_checkout_allowed;
    $z_view->paypal_checkout_allowed = $v_paypal_checkout_allowed;
    $z_view->amazon_checkout_allowed = $v_amazon_checkout_allowed;
    $z_view->show_shipping_time = $show_shipping_time;
    
    if(count($va_items) > 0)
    {
        $_SESSION["displayMasterPass"] = true;
    }
    else
    {
        $_SESSION["displayMasterPass"] = false;
    }
    
    $z_view->displayMasterPass = $_SESSION["displayMasterPass"];

    return $z_view->render("cart_items.phtml");
  }
  
  function getTtlOrderPerItem(){
  	global $mssql_db;
  	$sql = "select
                v.original_system_line_reference
              , v.inventory_item_id
              , v.selling_price
              , v.ordered_quantity
              from  direct.dbo.gsi_cmn_order_lines_v v
              where order_number = '$this->v_order_number'
              order by ship_set_number, line_number";
    $results = mssql_query($sql);

    if (!$results){
      display_mssql_error($sql, "cart item query from shopping cart");
    }
    while($v_row = mssql_fetch_array($results)) {
    	// the new ATP array
        if ($v_row["inventory_item_id"] == "332375"){
    		// as SPEC ORDER FEE MENS EQUIPMENT AND SUPPLIES.
    		continue;
    	}
    	$ItemKey = $v_row["inventory_item_id"];
    	$tempTotalOrdQtyArr[$ItemKey] += $v_row["ordered_quantity"];
    }
    return $tempTotalOrdQtyArr;
  	
  }
  //get an array of items in the cart
  private function getCartItems(&$pa_item_list, &$pa_cm_tags, &$p_google_checkout_allowed, &$p_paypal_checkout_allowed, &$p_amazon_checkout_allowed) {
    global $mssql_db;

    $v_warehouse_id = $this->getWarehouseId();

    $v_organization_id = 25;
    if ($_SESSION['site'] != 'US') {
    	$v_organization_id = 173;
    }
    
    $pa_item_list = array();
    $pa_cm_tags = array();
	$atp_sql = "select
                v.original_system_line_reference
              , v.inventory_item_id
              , v.selling_price
              , v.ordered_quantity
              from  direct.dbo.gsi_cmn_order_lines_v v
              where order_number = '$this->v_order_number'
              order by ship_set_number, line_number";
    $atp_result = mssql_query($atp_sql);

    if (!$atp_result){
      display_mssql_error($atp_sql, "cart item query from shopping cart");
    }
    while($v_row = mssql_fetch_array($atp_result)) {
    	// the new ATP array
        if ($v_row["inventory_item_id"] == "332375"){
    		// as SPEC ORDER FEE MENS EQUIPMENT AND SUPPLIES.
    		continue;
    	}
    	$tempArr[] = array('INVENTORY_ITEM_ID'=>$v_row["inventory_item_id"],'QUANTITY'=>$v_row["ordered_quantity"]);
    	$ItemKey = $v_row["inventory_item_id"];
    	$tempTotalOrdQtyArr[$ItemKey] += $v_row["ordered_quantity"];
    	$tempQtyArr[$ItemKey] = $v_row["ordered_quantity"];
    }
    
    foreach ($tempTotalOrdQtyArr as  $key => $value){
        $atp_arr[]= array("iiv"=>$key,"qty"=>$value);
    }






    //$atp_arr = array(array("iiv"=>$inv_item_id,"qty"=>$qtp));
   $ATPresults = ATPExtendedSoap::checkATPProc($atp_arr,"Shoppingcart");


 	//$SOAPURL =ATPSOAPSERVICE_URL; 
 	/*
 	$SOAPURL =dirname(__FILE__) . '/GetATPQuantity.wsdl';
    try{
		$v_sclient = new ATPExtendedSoap($SOAPURL,array("soap_version"  => SOAP_1_1, 'trace' => true));
		$v_sclient->setArrayRequest($tempArr);
			//$credentials = array("UsernameToken" => array("Username" => ORDER_STATUS_USER, "Password" => ORDER_STATUS_PASSWORD ));
			//$security =  new SoapVar($credentials, SOAP_ENC_OBJECT, "Security", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
			//	create header object
			//$header = new SOAPHeader($security->enc_ns, $security->enc_stype, $security, true);
			//$v_sclient->__setSoapHeaders( $header );
			
   			$results = $v_sclient->process( "" );
	
  			if (is_array($results->ATPOutput)){

				foreach ($results->ATPOutput as $value) {
					// $va_item['promise_date'] override this value for the display 
					// $va_item['atp_qty'] this is ATP QTY but there are no usage of it in the old code
					$value = (object) array("ATPOutput"=>$value);
					$ATPkey = $value->ATPOutput->INVENTORY_ITEM_ID; 
					$ATPresults[$ATPkey]=ATPExtendedSoap::ATPPromisedDatesMSG($value,$tempTotalOrdQtyArr[$ItemKey]);
				}
			}else{
				$ATPkey = $results->ATPOutput->INVENTORY_ITEM_ID;
				$ATPresults[$ATPkey]=ATPExtendedSoap::ATPPromisedDatesMSG($results,$tempTotalOrdQtyArr[$ItemKey]);
				
			}

		}catch (SoapFault $e ){
			// in any case the SOAP service has some issues log the error and keep the old table date and Quantity
			$ATPresults = array ();
			//var_dump($e);
			mail("webalert@golfsmith.com", "ATP Decrement Alert", var_export($e,TRUE));
			write_to_log("ATP Error ". $e->getMessage());
    	}
*/
    $v_sql = "select
                v.original_system_line_reference
              , v.inventory_item_id
              , v.ordered_quantity
              , v.selling_price
              , convert(varchar,datepart(dd,v.promise_date)) + '-'  + upper(convert(varchar(3),datename(month,v.promise_date)))  + '-'+ substring(convert(varchar,datepart(yy,promise_date)),3,2)   as promise_date
              , convert(varchar,datepart(dd,v.promise_date)) + '-'  + upper(convert(varchar(3),datename(month,v.promise_date)))  + '-'+ substring(convert(varchar,datepart(yy,promise_date)),3,2)   as promise_date1
              , v.sku_display
              , v.style_number
              , v.style_attr1
              , v.style_attr2
              , v.style_attr3
              , v.style_attr4
              , v.sku_desc
              , v.lcode
              , v.pricing_attribute3
              , v.pricing_attribute4
              , v.line_number
              , case v.warehouse_id when 25 then null else v.warehouse_id end as isp_warehouse_id
              , isnull(v.isp_line_type,0) isp_line_type
              , v.list_price
              , v.saved
              , v.extended_save
              , v.source_type_code
              , v.is_freegoods
              , ISNULL(ATP.ATP_QTY,0) atp_qty
              , v.ship_set_number
              , direct.dbo.gsi_get_style_pured(v.order_number,v.ship_set_number, v.original_system_line_reference) pured_style
              , v.pricing_attribute8
              , direct.dbo.gsi_get_ship_set_count(v.order_number,v.ship_set_number) ship_set_count
              , v.qbd_id
              , gia.global_item_flag
              ,gia.sku
              , gia.customization_type
              from  r12pricing.dbo.gsi_item_info_all gia, direct.dbo.gsi_cmn_order_lines_v v
              left join    direct.dbo.gsi_item_web_atp atp
              on atp.inventory_item_id = v.inventory_item_id
              and atp.organization_id  = $v_organization_id
              where order_number = '$this->v_order_number'
                and NOT v.style_number='GIFT BOX' and v.inventory_item_id = gia.inventory_item_id 
              order by ship_set_number, line_number";

    $v_result = mssql_query($v_sql);

    if (!$v_result){
      display_mssql_error($v_sql, "cart item query from shopping cart");
    }

    //don't display Google Checkout or PayPal Checkout on Canada site
    if($_SESSION['site'] != 'US') {
      $p_google_checkout_allowed = FALSE;
      $p_paypal_checkout_allowed = FALSE;
      $p_amazon_checkout_allowed = FALSE;
    } else {
      $p_google_checkout_allowed = TRUE;
      $p_paypal_checkout_allowed = TRUE;
      $p_amazon_checkout_allowed = TRUE;
    } 
    // remarketing counter
	$remakeringCounter = 0;
	$tempPriorityVal = 1;
    while($v_row = mssql_fetch_array($v_result)) {

      $va_item = array();

      $v_orig_line_ref     = $v_row['original_system_line_reference'];
      $v_inventory_item_id = $v_row['inventory_item_id'];
      $v_sku 			   = $v_row['sku'];      
      $v_ordered_quantity  = $v_row['ordered_quantity'];
      $v_selling_price     = $v_row['selling_price'];
      $v_promise_date      = $v_row['promise_date'];
      $v_promise_date1     = $v_row['promise_date1'];
      $va_item['sku_display'] = $v_row['sku_display'];
      $v_segment1          = $v_row['style_number'];
      $v_segment2          = $v_row['style_attr1'];
      $v_segment3          = $v_row['style_attr2'];
      $v_segment4          = $v_row['style_attr3'];
      $v_segment5          = $v_row['style_attr4'];
      $v_sku_desc          = $v_row['sku_desc'];
      $v_lcode             = $v_row['lcode'];
      $v_prc_attr3         = $v_row['pricing_attribute3'];
      $v_prc_attr4         = $v_row['pricing_attribute4'];
      $v_line_number       = $v_row['line_number'];
      $v_isp_warehouse_id  = $v_row['isp_warehouse_id'];
      $v_isp_line_type     = $v_row['isp_line_type'];
      $v_list_price        = $v_row['list_price'];
      $v_saved             = $v_row['saved'];
      $v_extended_save     = $v_row['extended_save'];
      $v_source_type_code  = $v_row['source_type_code'];
      $v_is_freegoods      = $v_row['is_freegoods'];
      $v_atp_qty           = $v_row['atp_qty'];
      $v_ship_set_number   = $v_row['ship_set_number'];
      $v_pured_style       = $v_row['pured_style'];
      $v_applied_promo     = $v_row['pricing_attribute8'];
      $v_ship_set_count    = $v_row['ship_set_count'];
      $v_qbd_id 	       = intval($v_row['qbd_id']);
	  $v_global_item_flag  = $v_row['global_item_flag'];
	  $v_customization_type = $v_row['customization_type'];
	  // Promising Date from the new service
//	  var_dump($ATPresults);
//	  var_dump(isset($ATPresults[$v_inventory_item_id]["promisedDate"]), $ATPresults[$v_inventory_item_id]["ResponseStatus"]=="VALID");
//	  var_dump($v_promise_date,$v_promise_date1);
	  if (isset($ATPresults[$v_inventory_item_id]["promisedDate"]) && $ATPresults[$v_inventory_item_id]["ResponseStatus"]=="VALID"){
	  	$v_promise_date = date("d-M-y",$ATPresults[$v_inventory_item_id]["promisedDate"]);
	  	$v_promise_date1 = date("d-M-y",$ATPresults[$v_inventory_item_id]["promisedDate"]);
	  	$_SESSION["ATPPromisedDATEs"][$v_inventory_item_id]=$ATPresults[$v_inventory_item_id]["promisedDate"];
 		$va_item["ATPmsg"]= $ATPresults[$v_inventory_item_id]["msg"];
 		$va_item["embargo_date"] = $ATPresults[$v_inventory_item_id]["embargo_date"];
	  }
	  
	  $productPage = new ProductPage($v_segment1);
	  $va_item["isPersonalizedstyle"] = $productPage->isPersonalizedstyle();
	  
	  
	  // remarkting vars
	  global $remarketing ;
	  $remarketing[$remakeringCounter++]=$va_item['sku_display'];

      $v_show_pureing = FALSE;
      unset($v_min_order_qty);
      unset($v_category_id);
      unset($v_isp_store_name);
      unset($v_oem_yn);
      unset($v_note_text);

      //don't show certain 0-cost items
      if ((trim($v_segment1) == '30047701' || trim($v_segment1) == '30045871') && $v_selling_price == 0) {
        continue;
      }

      if (trim($v_segment1) == '30047701' || trim($v_segment1) == 'PURE' || trim($v_segment1) == '30045871') {
        $va_item['show_quantity'] = FALSE;
      } else {
        $va_item['show_quantity'] = TRUE;
      }

      $va_item['inventory_item_id'] = $v_inventory_item_id;
      $va_item['sku'] = $v_sku;      
      $va_item['original_line_reference'] = $v_orig_line_ref;
      $va_item['line_number'] = $v_line_number;
      $va_item['pured_style'] = $v_pured_style;
      $va_item['extended_save'] = $v_extended_save;

      $va_item['quantity'] = $v_ordered_quantity;
      
      $va_item['atp_qty'] = $v_atp_qty;
      
      if($v_customization_type == "CUSTOM")
      {
          $va_item['image'] = $this->getProductImage($this->getCustomClubParentStyle($v_segment1));
      }
      else
      {
          $va_item['image'] = $this->getProductImage($v_segment1);
      }
      
      $va_item['ship_set_count'] = $v_ship_set_count;

      $va_item['is_qbd'] = 'N';
      //if QBD style, get QBD info 
      if ($v_qbd_id > 0) {
      	$va_item['is_qbd'] = 'Y';
      	$v_arr_QBD = array();
      	$v_arr_QBD = get_QBD_info($v_qbd_id);	
      	$va_item['qbd_price'] = format_currency($v_arr_QBD['qbd_price']);
      	$va_item['qbd_curr_price'] = format_currency($v_arr_QBD['curr_price']);
      	$va_item['qbd_original_price'] = format_currency($v_arr_QBD['original_price']);
      	$va_item['qbd_savings'] = $v_arr_QBD['qbd_savings']; 
      	
      	//we do not want google or paypal checkout if style have QBD items
      	$p_google_checkout_allowed = FALSE;
        $p_paypal_checkout_allowed = FALSE;
        $p_amazon_checkout_allowed = FALSE;
      }
      
      /* Do not show amazon checkout button for Global source items */
      if($v_global_item_flag == 'Y')
      {
      	$p_amazon_checkout_allowed = FALSE;
      }
      
      /* Do not show amazon checkout button for Handicap Service items */
      // ticket # 37773 kbox      
      if (($v_segment1 == '30048538') || ($v_segment1 == '30035243'))
      {
      	$p_amazon_checkout_allowed = FALSE;
      }
            
      // We do not want amazon or Google checkout for Gift Card and E-card 
      // KBOX#47564
      if (($v_segment1 == 'ECARD') || ($v_segment1 == 'GIFTCARD'))
      {
      	$p_amazon_checkout_allowed = FALSE;
      	$p_google_checkout_allowed = FALSE;
      }
      
      $va_item['selling_price'] = format_currency($v_selling_price);
      
      $this->getSkuInfo($v_segment1, $v_sku_desc, $v_min_order_qty, $v_category_id, $v_manuf_desc);

      //check availability
      if ($v_isp_line_type == 2) {
        $va_item['item_available'] = $this->checkItemAvailability($v_inventory_item_id, $va_item['quantity'], $v_isp_warehouse_id);
        //need to figure out new handling for this
      }
 
      //check country restrictions
      $va_item['ships_to_country'] = TRUE;
      if(!empty($_SESSION['s_customer_id'])) {
        if($this->checkCountryRestrictedItem($v_orig_line_ref) == 1 || $this->checkSourcedItemShippedInt($v_orig_line_ref) == 1) {
          $va_item['ships_to_country'] = FALSE;
        }
      }

      $v_logo_fee = $this->getLogoFeeFlag($v_inventory_item_id);
      $v_check    = $this->checkMinOrderQty($v_category_id);

      if($v_logo_fee === FALSE && $v_check === TRUE) {
        $v_logo_item = TRUE;
      } else {
        $v_logo_item = FALSE;
      }

      $va_item['show_update_delete'] = TRUE;
      if ($v_ship_set_number > 0 && $v_category_id == '2539247') {
        $va_item['show_update_delete'] = FALSE;
      } else if($v_sku_desc == 'Special Order Processing Fee' || $v_logo_fee === TRUE || $v_prc_attr4 == 'NEW') {
        $va_item['show_update_delete'] = FALSE;
      }

      if($v_inventory_item_id != 332375) {
        $this->getSkuFlags($v_inventory_item_id, $v_store_pickup_flag, $v_austin_to_store_flag);
      }

      if($v_inventory_item_id == '332375') { //don't display DSC01 lines
        continue;
      }

      if ($v_isp_line_type != 0) {
        $p_google_checkout_allowed = FALSE;
        $p_paypal_checkout_allowed = FALSE;
        $p_amazon_checkout_allowed = FALSE;
      }
      
      //if we're showing a quantity that isn't read-only and isn't OEM, check for ship set and accumulate related items
      if($va_item['show_quantity'] === TRUE && $va_item['show_update_delete'] === TRUE && $va_item['is_oem'] === FALSE && !empty($v_ship_set_number)) {
        //find everything else in the ship set and store a comma separated list
      }

      if($_SESSION['site'] == 'US' && $va_item['show_update_delete'] == TRUE && ($v_store_pickup_flag === TRUE || $v_austin_to_store_flag === TRUE)) {
        $va_item['show_isp'] = TRUE;
      } else {
        $va_item['show_isp'] = FALSE;
      }
      
      //disable ISP for giftcards and ecards
      if (($v_segment1 == 'GIFTCARD') || ($v_segment1 == 'ECARD'))
      {
      	$va_item['show_isp'] = FALSE;
      }

      $va_item['is_oem'] = FALSE;
      if ($v_source_type_code == 'EXTERNAL') {
        $this->getPersonalizedText($v_orig_line_ref, $va_item['note_text'], $va_item['is_oem']);

        if(!empty($va_item['note_text'])) {
          $va_note_lines = explode("\n", $va_item['note_text']);
          $va_item['note_text'] = $va_note_lines;
        }

        $p_paypal_checkout_allowed = FALSE;
        $p_amazon_checkout_allowed = FALSE;
      }

      $this->getMarkdownSavings($v_segment1, $v_orig_price, $v_act_price, $v_savings_story, $v_inventory_item_id);

      $v_extended_price = $va_item['quantity'] * $v_selling_price;
      $v_selling_price_dsp = format_currency($v_selling_price);
      $v_extended_price_dsp = format_currency($v_extended_price);
      if(!empty($v_savings_story)) {
        $v_actual_price = $va_item['quantity'] * $v_selling_price;
        $v_sub_total += $va_item['quantity'] * $v_selling_price;
      } else {
        $v_sub_total += $v_extended_price;
      }

      if ($v_isp_line_type == 0) {
        $v_ship_sub_total += $v_extended_price;
      }

      if($v_list_price > $v_selling_price) { //promo savings
        $va_item['original_price'] = format_currency($v_list_price);
        $va_item['actual_price'] = $v_selling_price_dsp;
        $va_item['savings'] = format_currency($v_saved);
      } else if(($v_orig_price > $v_act_price) && (!empty($v_savings_story)) && ($v_act_price == $v_selling_price)) {
        $va_item['original_price'] = format_currency($v_orig_price);
        $va_item['actual_price'] = format_currency($v_act_price);
        $va_item['savings'] = $v_savings_story;
      } else {
        $va_item['actual_price'] = $v_selling_price_dsp;
        
		// added this part of the code to get the savings story for the priced too low to show items. -- start      
        $sql = "SELECT original_price FROM webdata.gsi_style_info_all WHERE style_number = '".$v_segment1."'";
        $result = mysqli_query($web_db,  $sql);
        $row = mysqli_fetch_assoc($result);
        $v_original_price = $row['original_price'];
		
        if($v_original_price > $v_selling_price)
        {
                $v_dollar_svg = round(($v_original_price - $v_selling_price),2);
                $v_percent_svg = floor(($v_dollar_svg/$v_original_price)*100);
                if($v_dollar_svg > $v_percent_svg)
                {
                        $va_item['savings'] = '$'.number_format($v_dollar_svg,2,'.','');
                }
                else
                {
                        $va_item['savings'] = $v_percent_svg.'%';
                }
                $va_item['original_price'] = format_currency($v_original_price);
        }        
        // --- end
      }

      if(!empty($v_savings_story)) {
        $va_item['total'] = format_currency(floatval($va_item['quantity'] * $v_selling_price));
      } else {
        $va_item['total'] = $v_extended_price_dsp;
      }

      $va_item['style_number'] = $v_segment1;

      if($v_isp_line_type == 1) {
        $v_promise_date1 = $this->getPromiseDate($v_isp_warehouse_id, $v_promise_date1);
        $v_promise_date1 = strtoupper(date('d-M-y', strtotime($v_promise_date1)));
        $v_promise_date = $v_promise_date1;
      }
      else if($v_isp_line_type == 2) {      	
        $v_promise_date1 = strtoupper(date('d-M-y'));
        $v_promise_date = $v_promise_date1;            
      } else {
        //need to check lead time; if more than one week, no PayPal Checkout
        $v_promise_date_stamp = strtotime($v_promise_date);
        $v_current_date_stamp = time();
        $v_ship_time = $v_promise_date_stamp - $v_current_date_stamp;
        if($v_ship_time > 604800) { //1 week => 7*24*60*60 = 604800 sec
          $p_paypal_checkout_allowed = FALSE;
          $p_amazon_checkout_allowed = FALSE;
        }
      }

      if(empty($v_promise_date)) {
        $v_promise_date = 'Not Available';
      }
   
      $va_item['promise_date'] = $v_promise_date;
      $va_item['promise_date_display'] = $v_promise_date;
      $va_item['isp_line_type'] = $v_isp_line_type;
      $va_item['source_type_code'] = $v_source_type_code;
      $va_item["ATPmsgISP"] = $this->atp_isp_msg($v_promise_date, $va_item["ATPmsg"]);
      
      if(!empty($v_isp_warehouse_id)) {
        $this->getStoreInfo($v_isp_warehouse_id, $va_item['store_name'], $v_store_number);
        $va_item['store_url'] = (!empty($_SESSION['s_screen_name']) ? '/' . $_SESSION['s_screen_name'] : '') . '/stores/' . $v_store_number;
        $va_item['store_org_id'] = $v_isp_warehouse_id;
        
        //Get store address
        $addressInfo = $this->getStoreAddress($v_isp_warehouse_id);
        $va_item['address_line_1'] = $addressInfo['address_line_1'];
        $va_item['address_line_2'] = $addressInfo['address_line_2'];
        $va_item['town_or_city'] = $addressInfo['town_or_city'];
        $va_item['county'] = $addressInfo['county'];
        $va_item['state'] = $addressInfo['state'];
        $va_item['postal_code'] = $addressInfo['postal_code'];
        
        /*
        //get qty on hand
        $args = array();
        $args['promise_date'] =  $v_promise_date; //date('m-d-Y', $v_promise_date);
        $args['qty'] = $v_ordered_quantity;
        $args['org_id'] = $v_isp_warehouse_id;
        $args['item_id'] = $v_inventory_item_id;
        
        $i_isppage = new Isppage();
        $result = $i_isppage->getDateAndQty($args);
        
        $va_item['qty_onhand'] = $result['qty_onhand'];
        $va_item['return_date'] = $result['return_date'];
        */
        
      }

      $va_item['description'] = $v_sku_desc;
      $va_item['manuf_desc'] = $v_manuf_desc;

      //no product page links for OEM clubs
      if($va_item['is_oem'] === FALSE && $v_logo_fee === FALSE) {
        if ($_SESSION['site'] != 'CA') {
          $v_seo_obj = new seo($v_segment1);
          $va_item['url'] = $v_seo_obj->get_seo_url();  
          
        } else {
          $va_item['url'] = "/ca/products/" . $v_segment1;
        }
      }      
      
      //find out if we need to show the PUREing option
      if(trim($v_segment1) != 'PURE' && trim($v_segment1) != '30045871') {
        $va_item['show_pureing'] = $this->showPureingUpsell($v_segment1, $v_line_number);        
      }
      if($v_isp_line_type != 0) {
        $va_item['show_pureing'] = FALSE;
      }

      $va_item['promo_exclusion'] = $this->checkStyleExclusion($this->v_order_number,$v_orig_line_ref);
      
      if (isset($v_applied_promo)) {
      	$va_item['promo_applied'] = 'Y';
      } else {
      	$va_item['promo_applied'] = 'N';
      }
      
      //Change SKU display and Segment Display 
      	$v_sku_disp = $va_item['sku_display'];
      	$v_pos = strpos(trim($va_item['sku_display']), ' ');
      	
      	if ($v_pos > 0)
      	{
      		$v_seg_disp = substr($v_sku_disp,$v_pos,strlen($v_sku_disp));   		
      	} else {
      		$v_seg_disp = '';
      	}
      	
      	$va_item['sku_display'] = $va_item['style_number'];      	
      	$va_item['segment_display'] = $v_seg_disp;
      	

      $pa_item_list[] = $va_item;
      $pa_cm_tags[] = 'cmCreateShopAction5Tag("' . $va_item['style_number'] . '", "' . str_replace('"', '' ,$va_item['description']) . '", "' . $va_item['quantity'] . '", "' . $v_selling_price . '");';
    }

  }
  
  private function getCustomClubParentStyle($style_number)
  {
      global $web_db;
      
      $sql = "select style_number
                from gsi_style_services
                where service_style_number = '$style_number';";
      
      $result = mysqli_query($web_db, $sql);
      
      if(!$result)
      {
          display_mysqli_error($sql);
      }
      
      while($row = mysqli_fetch_assoc($result))
      {
          $style_number = $row["style_number"];
      }
      
      mysqli_free_result($v_result);
      
      return $style_number;
  }
  
  public function getATPForCartItems($inventory_ids){
  		$SOAPURL =ATPSOAPSERVICE_URL;
		global $mssql_db;
  		$soap_arr = array ("ATPReqInput"=>array ('SALES_CHANNEL_CODE'=>'GS.COM',
  							'INVENTORY_ITEM_ID'=>$p_iid,
  							'ORGANIZATION_ID'=>$p_org_id,
  							'QUANTITY'=>$p_ordered_qty
  							));
    	try
		{
			$v_sclient = new SoapClient($SOAPURL,array("soap_version"  => SOAP_1_1, 'trace' => true));
   			$result = $v_sclient->process( $soap_arr );
  
    		// get the SOAP results and updat the
   			$ATP_check_result = $result->ATPOutput;;
		}catch (SoapFault $e )
		{
			//If there an error we will call the Tables diectly
			$v_sp = mssql_init("direct.dbo.gsi_get_atp_qty");
			gsi_mssql_bind ($v_sp, "@inventory_item_id", $inv_item_id, "int", 20, false);
			gsi_mssql_bind ($v_sp, "@qty", $qty, "int", 20, false);
			gsi_mssql_bind($v_sp,"@skunumber",$skunumber,"varchar",50,true);        
			$v_result = mssql_execute($v_sp);
			if (!$v_result){
				display_mssql_error("gsi_get_new_SKU" . $msg, "call from ProductPage.php");
			}
			mssql_free_statement($v_sp);
			mssql_free_result($v_result);
			
			// in any case the result status will be ERROR
			$this->result =  "ERROR";
			write_to_log("ATP Error ". $e->getMessage()." the call:".var_export($e,TRUE));
			$ATP_check_result = "ERROR";
    	}

      return $ATP_check_result;
  	
  }

  public function getISPForm($p_line_number) {

    global $web_db;

    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));

    $z_view->line_number = $p_line_number;
    $z_view->state_options_path = PHP_INC_PATH . 'state_bill_dropdown.html';
     $z_view->state_options_ca_path = PHP_INC_PATH . 'state_bill_dropdown_ca.html';;

    return $z_view->render('isp_selectors.phtml');

  }

  public function findISPStore($p_line_number, $p_inventory_item_id, $p_quantity) {
    global $web_db;

    $v_quantity = $p_quantity;
    if(empty($v_quantity)) {
      $v_quantity = 1;
    }

    $isp_zipcode = substr( preg_replace( '/[^0-9]/', '', $_POST['store_postal_code'] ), 0, 5 );
    $isp_city = strip_tags($_POST['isp_city']);
    $state = strip_tags($_POST['isp_state']);
    $isp_store = strip_tags($_POST['isp_store']);
    if (! isset( $isp_zipcode ) || (empty( $isp_zipcode ) && (empty( $isp_city ))))
      $isp_zipcode = $_SESSION['isp_zipcode'];
    else
      $_SESSION['isp_zipcode'] = $isp_zipcode;
    if (empty( $isp_zipcode ) && (empty( $isp_city ) || (empty( $state ))))
      $v_error_message = 'Please enter required values';
    if (! empty( $isp_store ))
      $_SESSION['isp_store'] = $isp_store;

    if (empty( $v_error_message )) {

      if (empty( $isp_zipcode )) {

        $temp_zipcode = 0;
        $sql = "
         select min(substring(p.from_postal_code,1,5)) zip_code
         from (ar_location_values v left join
         (ar_location_values parent left join
         ar_location_values gparent on parent.parent_segment_id = gparent.location_segment_id)
         on v.parent_segment_id = parent.location_segment_id )
         ,    ar_location_rates  p
         where p.location_segment_id    = v.location_segment_id
           and now() between p.START_DATE     and p.END_DATE
           and gparent.location_segment_qualifier = 'STATE'
           and gparent.location_segment_value = upper('$state')
           and v.location_segment_qualifier       = 'CITY'
           and parent.location_segment_qualifier  = 'COUNTY'
           and v.location_segment_value = upper('$isp_city') ";

        $result = mysqli_query($web_db, $sql);
        display_mysqli_error($sql);
        while ( $row = mysqli_fetch_assoc( $result ) ) {
          $temp_zipcode = $row['zip_code'];
        }
        $_SESSION['isp_zipcode'] = $temp_zipcode;
        $isp_zipcode = $temp_zipcode;
        mysqli_free_result( $result );
      } else {
        $temp_zipcode = $isp_zipcode;
      } // if(empty($isp_zipcode))

      while ( true ) {
        $found_zipcode = 'N';
        $sql = "select 'Y' from gsi_zipcodes where postal_code = $temp_zipcode";
        $result = mysqli_query($GLOBALS['web_db'], $sql);
        display_mysqli_error( $sql );
        while ( $row = mysqli_fetch_assoc( $result ) ) {
          $found_zipcode = 'Y';
        }
        if (($found_zipcode == 'Y') || ($temp_zipcode < 0) || empty( $temp_zipcode ))
          break;
        $temp_zipcode ++;
      } // while (true)

      //check flags to determine ISP versus STS
      if(!empty($p_inventory_item_id)) {
        $v_sql = "select store_pickup_flag, austin_to_store_flag
                from gsi_item_info_all
                where inventory_item_id = $p_inventory_item_id";

        $v_result = mysqli_query($GLOBALS['web_db'], $v_sql);
        display_mysqli_error($v_sql);
        while($va_row = mysqli_fetch_assoc($v_result)) {
          $v_store_pickup_flag    = $va_row['store_pickup_flag'];
          $v_austin_to_store_flag = $va_row['austin_to_store_flag'];
        }
        mysqli_free_result($v_result);
      }

      $v_pure_yes = $this->lineHasPureing($p_line_number);
      
      $v_isTennisStringing = $this->isTennisStringing($p_line_number);

      $sql = "select distinct
        store_info.organization_id
       ,store_info.organization_code
       ,case ifnull(LENGTH(info.store_name),0) when 0 then gsie.display_store_name else info.store_name end as location_code
       ,store_info.address_line_2
       ,store_info.address_line_3
       ,store_info.town_or_city
       ,store_info.state
       ,store_info.postal_code
       ,info.store_name
       ,ifnull(round(60 * 1.515 * degrees(acos(
        (sin( radians( origin.latitude)) * sin( radians(store.latitude)) )
        +(cos( radians(origin.latitude)) * cos( radians(store.latitude)) )
        * cos( radians(origin.longitude - store.longitude) )
        )),2),0) as distance
      from
        gsi_zipcodes origin
       ,gsi_zipcodes store
       ,gsi_store_info_ext gsie
       ,gsi_web_store_groups
       ,gsi_store_info store_info
       left join webonly.gsi_store_web_info info on info.store_id = store_info.organization_id  
      where gsie.store_pickup_flag = 'Y' 
        and gsie.organization_id = store_info.organization_id

        and substring(store_info.postal_code, 1, 5) = store.postal_code
        and gsi_web_store_groups.record_name = store_info.organization_code
        and substring(origin.postal_code, 1, 5) = '$temp_zipcode'
        and (
            60 * 1.515 * degrees(acos(
            ( sin( radians( origin.latitude)) * sin( radians(store.latitude)) )
            +
            ( cos( radians(origin.latitude)) * cos( radians(store.latitude)) )
            * cos( radians(origin.longitude - store.longitude) )
            )) < 101
            or
            60 * 1.515 * degrees(acos(
            ( sin( radians( origin.latitude)) * sin( radians(store.latitude)) )
            +
            ( cos( radians(origin.latitude)) * cos( radians(store.latitude)) )
            * cos( radians(origin.longitude - store.longitude) )
            )) is null
            )
      
      order by distance limit 3";
	
      $v_result = mysqli_query($web_db, $sql);

      display_mysqli_error($sql);

      $va_stores = array();

      while($va_row = mysqli_fetch_assoc($v_result)) {

        $v_isp = FALSE;

        if($v_store_pickup_flag == 'Y' && $v_pure_yes !== TRUE && $v_isTennisStringing == 'N') {
          $v_available = $this->checkItemAvailability($p_inventory_item_id, $v_quantity, $va_row['organization_id']);
          if($v_available === TRUE) {
            $v_isp = TRUE;
          }
        }
        if($v_isp === TRUE || $v_austin_to_store_flag == 'Y') {
          $va_store = array();

          if($v_isp !== TRUE && $v_austin_to_store_flag == 'Y') { //STS
            $va_store['line_type'] = 1;
          } else { //ISP
            $va_store['line_type'] = 2;
          }

          $va_store['organization_id'] = $va_row['organization_id'];
          $va_store['address_line_2'] = $va_row['address_line_2'];
          $va_store['address_line_3'] = $va_row['address_line_3'];
          $va_store['distance'] = $va_row['distance'];
          $va_store['city'] = $va_row['town_or_city'];
          $va_store['state'] = $va_row['state'];
          $va_store['store_name'] = $va_row['store_name'];
          $va_store['location_code'] = $va_row['location_code'];
          $va_store['postal_code'] = substr($va_row['postal_code'],0,5);
          $va_store['availability_date'] = $this->getISPDate($p_inventory_item_id, $va_store['organization_id'], $v_quantity);

          $v_today = strtoupper(date('j-M-y'));

          if($va_store['availability_date'] == $v_today) {
            $va_store['available_today'] = TRUE;
          }

          $va_store['map_location'] = 'http://www.mapquest.com/maps/map.adp?country=US&countryid=250&addtohistory=&address=';
          if(!empty($va_store['address_line_2'])) {
            $va_store['map_location'] .= $va_store['address_line_2'];
          }
          if(!empty($va_store['address_line_3'])) {
            $va_store['map_location'] .= ',+' . $va_store['address_line_3'];
          }

          $va_store['map_location'] .= '&city=' . $va_store['city'] . '&state=' . $va_store['state'] . '&zipcode=' . $va_store['postal_code'] . '&submit=Get+Map';

          $va_store['map_location'] = str_replace(' ', '+', $va_store['map_location']);

          //we need to call availability for the given item

          $va_stores[] = $va_store;
        }

      } //while ($row = mysqli_fetch_assoc ($result))
      mysqli_free_result( $result );

      $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
      $z_view->stores = $va_stores;
      $z_view->line_number = $p_line_number;
      $z_view->inventory_item_id = $p_inventory_item_id;
      echo $z_view->render('cart_isp.phtml');

    } //if (empty($v_error_message))

  }

  public function changeLineISPWarehouse($p_line_number, $p_organization_id, $p_line_type) {
    $i_order = new Order($this->v_order_number);
    $i_order->updateISPWarehouse($p_line_number, $p_organization_id, $p_line_type);
  }

  public function validateOrderQtys() {
    $i_order = new Order($this->v_order_number);
    return $i_order->validateOrderQtys();
  }
  
  private function lineHasPureing($p_line_number) {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_cmn_check_pure_yes");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_line_num", $p_line_number, 'int');
    gsi_mssql_bind($v_stmt, "@p_pure_yes", $v_pure_yes, 'varchar', 1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_check_pure_yes", "called from ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result ($v_result);

    if($v_pure_yes == 'Y') {
      $v_pure_yes = TRUE;
    } else {
      $v_pure_yes = FALSE;
    }

    return $v_pure_yes;

  }

  private function getISPDate($p_inventory_item_id, $p_organization_id, $p_quantity) {
    $p_promise_date = date('Y-m-d');
    global $mssql_db;

    $v_stmt = mssql_init("gsi_isp_pickup_dates");

    gsi_mssql_bind($v_stmt, "@p_item_id", $p_inventory_item_id, 'varchar', 20);
    gsi_mssql_bind($v_stmt, "@p_org_id", $p_organization_id, 'bigint');
    gsi_mssql_bind($v_stmt, "@p_qty", $p_quantity, 'bigint');
    gsi_mssql_bind($v_stmt, "@p_promise_date", $p_promise_date, 'varchar',16);
    gsi_mssql_bind($v_stmt, "@p_return_date", $v_availability_date, 'varchar', 16, true);
    gsi_mssql_bind($v_stmt, "@p_qty_onhand", $v_qty_onhand, 'bigint', true);

    $v_result = mssql_execute($v_stmt); 

    if(!$v_result) {
      display_mssql_error("gsi_isp_pickup_dates", "called from getISPDate() in ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    return $v_availability_date;

  }

  private function checkStyleExclusion($p_order_number, $p_order_line) {

    global $mssql_db;

    //check for promo exclusions
    $v_source_code = $_SESSION['s_source_code'];

    if(!empty($v_source_code)) {
      $v_stmt = mssql_init("direct.dbo.gsi_check_style_exclusion_r12");
      gsi_mssql_bind($v_stmt, "@p_order_number", $p_order_number , "varchar", 50);
      gsi_mssql_bind($v_stmt, "@p_order_line"  , $p_order_line , "varchar", 50);
      gsi_mssql_bind($v_stmt, "@p_source_code" , $v_source_code , "varchar", 30);
      
      gsi_mssql_bind($v_stmt, "@p_exclusion_type" , $v_out_exclusion_type , "bigint", 50 ,true );

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("direct..gsi_check_style_exclusion from sc_view");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result ($v_result);

      return $v_out_exclusion_type;
    }

    return 0;
 
  }

  private function getSkuInfo($p_segment1, &$p_sku_desc2, &$p_min_order_qty, &$p_category_id, &$p_manuf_desc) {
    global $web_db;

    $v_sql = "select s.description
                   , b.description manuf_desc
                   , ifnull(s.min_order_qty, 1) min_order_qty
                   , s.web_category_id category_id
              from gsi_style_info_all s
                 , gsi_brands b
             where s.brand_id = b.brand_id
               and (s.old_style = '$p_segment1' or s.style_number = '$p_segment1')";    
    

    $v_result = mysqli_query($web_db, $v_sql);
    display_mysqli_error($v_sql);
    if ($va_row = mysqli_fetch_array($v_result)) {
      $p_manuf_desc = $va_row["manuf_desc"];
      $p_sku_desc2 = $va_row["description"];      
      $p_min_order_qty = $va_row["min_order_qty"];
      $p_category_id = $va_row["category_id"];
    }
    mysqli_free_result($v_result);

  }
  
  
  private function isTennisStringing($p_line_number) {

  	global $mssql_db;

  	$v_stringing = 'N';
    $v_stmt = mssql_init("gsi_cmn_check_tennis_stringing");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_line_num", $p_line_number, 'int');
    gsi_mssql_bind($v_stmt, "@p_stringing", $v_stringing, 'varchar', 1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_check_tennis_stringing", "called from ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result ($v_result);
    
    return $v_stringing;   
  }
  

  private function getProductImage($p_style_number) {

    global $web_db;
    
    
    // Get the scene7 image if there is one
    $v_image_url = '';
    $sql="select old_style from webdata.gsi_style_info_all where style_number='".$p_style_number."'";
    $result = mysqli_query($web_db, $sql);
    $old_style = mysqli_fetch_row($result);
    $old_style_small = substr($old_style[0],0,-2);
    $sql="SELECT full_name
    	FROM webonly.gsi_scene7_data g
    	WHERE (style_number = upper('$old_style[0]') OR style_number = upper('$old_style_small') OR style_number = upper('$p_style_number'))
    	AND image_type='im' ORDER BY pos_num";
    //ORDER BY SUBSTR(g.full_name, -5,5) DESC LIMIT 1";
    $result = mysqli_query($web_db, $sql);
    $scene7_image = mysqli_fetch_row($result);
    if($scene7_image[0])
    {
        $v_image_url = SCENE7_URL . $scene7_image[0] . '?$sm$';
    }
    
    if(empty($v_image_url)){
        //check if style is custom club
        $v_sql = "select style_number from gsi_style_services
        where service_style_number = '$p_style_number'
        and service_type = 'CUSTOM'";
        $v_result = mysqli_query( $web_db, $v_sql);
        display_mysqli_error($v_sql);
        if($va_row = mysqli_fetch_array($v_result, MYSQLI_ASSOC)) {
            $p_style_number = $va_row["style_number"];
        }
        
        //now get the image
        $v_sql = "select s.small_image_file
        , s.old_style
        from gsi_style_info_all s
        where s.style_number = '$p_style_number'";
        $v_result = mysqli_query($web_db, $v_sql);
        display_mysqli_error($v_sql);
        if ($va_row = mysqli_fetch_array($v_result, MYSQLI_ASSOC))
            $v_image     = $va_row["small_image_file"];
	    $v_old_style = $va_row["old_style"];
        
        
        mysqli_free_result($v_result);
        
        if ($v_image == '') {
            $v_image = 'images/' . strtolower($p_style_number) . '_sm.jpg';
            if (!is_file($v_image)) {
                $v_image = 'images/' . strtolower($v_old_style) . '_sm.jpg';
                if (!is_file($v_image)) {
                    $v_image = '/images/comingsoon_sm.jpg';
        
                } else {
                    $v_image = '/' . $v_image;
                }
            } else {
                $v_image = '/' . $v_image;
            }
        } else if(substr($v_image, 0, 1) != '/') {
            $v_image = '/' . $v_image;
        }
        
        $v_image_url = $v_image;
    }
    

    return $v_image_url;


  }

  private function getPromiseDate($p_isp_warehouse_id, $p_promise_date) {
    global $web_db;

    $v_use_promise_date = date('Y-m-d', strtotime($p_promise_date));

    $v_sql = "select ucase(date_format(date_add('$v_use_promise_date', interval (delivery_time+1) day), '%d-%b-%y')) sts_promise_date 
              from gsi_store_info_ext 
              where organization_id = $p_isp_warehouse_id";

    $v_result = mysqli_query($web_db, $v_sql);

    display_mysqli_error($v_sql);

    if($va_row = mysqli_fetch_array($v_result)) {
      $v_promise_date = $va_row['sts_promise_date'];
    }

    mysqli_free_result($v_result);

    return $v_promise_date;
  }

  private function getStoreInfo($p_isp_warehouse_id, &$p_isp_store_name, &$p_isp_store_number) {
    global $web_db;

    if(!empty($p_isp_warehouse_id)) {
      $v_sql = "select infos.store_name, concat('', gsi_store_info_ext.display_store_name) as location_code
                     , organization_code
                from gsi_store_info_ext,webonly.gsi_store_web_info infos
                where organization_id = $p_isp_warehouse_id and infos.store_id = $p_isp_warehouse_id";

      $v_result = mysqli_query($web_db, $v_sql);

      display_mysqli_error($v_sql);

      if($va_row = mysqli_fetch_array($v_result)) {
		if($va_row['store_name']!="" && $va_row['store_name']!=null && $va_row['store_name']!=" "){
			$p_isp_store_name = $va_row['store_name'];
		}else
      	$p_isp_store_name = $va_row['location_code'];
        $p_isp_store_number = $va_row['organization_code'];
       
      }

      mysqli_free_result($v_result);
    }

  }
  
  private function getStoreAddress($p_isp_warehouse_id) {
      $i_storeFinder = new StoreFinder();
      $a_closesStores= $i_storeFinder->getClosesStoresByOrgId($p_isp_warehouse_id);
  
      $row = mysqli_fetch_assoc($a_closesStores);
      $results = array();
      $results['address_line_1'] = $row['address_line_1'];
      $results['address_line_2'] = $row['address_line_2'];
      $results['town_or_city'] = $row['town_or_city'];
      $results['county'] = $row['county'];
      $results['state'] = $row['state'];
      $results['postal_code'] = $row['postal_code'];
  
      return $results;
  }

  private function getMarkdownSavings($p_style_number, &$p_orig_price, &$p_act_price, &$p_savings, $p_item_id) {

    global $web_db;

    //clear out savings before beginning, to remove values from previous items
    $p_savings = '';

    $v_sql = "select price_str, orig_price_str, price_svg_str, item_id_str 
              from " . GSI_CMN_STYLE_DATA . "
              where style_number = '$p_style_number'";

    $v_result = mysqli_query($web_db, $v_sql);
    display_mysqli_error($v_sql);

    if($va_row = mysqli_fetch_array($v_result)) {

      $v_actual_price_str = $va_row['price_str'];

      //deal with quotes if they're in the actual price string
      if(preg_match('/^".*"$/U', $v_actual_price_str) > 0) {
        $v_actual_price_str = substr($v_actual_price_str, 1, (strlen($v_actual_price_str) - 2));
        $va_actual_price = explode('","', $v_actual_price_str);
      } else {
        $va_actual_price = explode(',', $v_actual_price_str);
      }

      $v_orig_price_str = $va_row['orig_price_str'];

      //deal with quotes (original price)
      if(preg_match('/^".*"$/U', $v_orig_price_str) !== 0) {
        $v_orig_price_str = substr($v_orig_price_str, 1, (strlen($v_orig_price_str) - 2));
        $va_orig_price = explode('","', $v_orig_price_str);
      } else {
        $va_orig_price = explode(',', $v_orig_price_str);
      }

      $v_savings_str = $va_row['price_svg_str'];

      //deal with quotes (savings)
      if(preg_match('/^".*"$/U', $v_savings_str) !== 0) {
        $v_savings_str = substr($v_savings_str, 1, (strlen($v_savings_str) - 2));
        $va_savings = explode('","', $v_savings_str);
      } else {
        $va_savings = explode(',', $v_savings_str);
      }

      $v_item_id_str = $va_row['item_id_str'];
      $va_item_id = explode(',', $v_item_id_str);

    }
 
    for($i = 0; $i < count($va_item_id); $i++) {

      if($va_item_id[$i] == $p_item_id) {
        if($va_actual_price[$i] != 'N') {

          if(substr($va_actual_price[$i], 0, 1) == '$') {
            $v_actual_price_val = substr($va_actual_price[$i], 1);
          } else {
            $v_actual_price_val = $va_actual_price[$i];
          }

          if($v_actual_price_val > 0) {
            $p_orig_price = $va_orig_price[$i];
            $p_savings = $va_savings[$i];
            $p_act_price = $v_actual_price_val;
          }

        }
      }

    }

  }

  private function showPureingUpsell($p_style_number, $p_line_number) {
    global $web_db;
    global $mssql_db;
    $v_ret = FALSE;    
    
   /* Change: R12-001
    * Date: 06-08-2011
    * Made By: Hafeez
    * Before we were getting service_item_id from gsi_cmn_style_services table but now 
    * in R12 change we don't have that column so we are getting inventory_item_id as 
    * puring service_item_id from gsi_item_info_all table only if pure_flag value should be 'Y'
    * and segment1 value is "PURE" 
    * */
    
  $v_pure_yes_no='N';
      
  $v_pure_sql="select pure_flag
               from gsi_item_info_all
               where segment1='$p_style_number'";
  $v_pure_result = mysqli_query($web_db, $v_pure_sql);
  display_mysqli_error($v_pure_sql);
  
  if($v_pure_row = mysqli_fetch_array($v_pure_result)) {
    $v_pure_yes_no= $v_pure_row['pure_flag'];
  }
    
  if($v_pure_yes_no==='Y'){
    $v_sql = "select inventory_item_id
              from gsi_item_info_all
              where segment1='PURE'";
    $v_result = mysqli_query($web_db, $v_sql);
    display_mysqli_error($v_sql);

    if($va_row = mysqli_fetch_array($v_result)) {
      $v_pure_iid = $va_row['inventory_item_id'];
    }

      $v_sql2 = mssql_init("gsi_cmn_order_ext_check_for_service_line");
      gsi_mssql_bind($v_sql2, "@p_order_number", $_SESSION['s_order_number'], 'varchar', 30);
      gsi_mssql_bind($v_sql2, "@p_line_number", $p_line_number, 'int');
      gsi_mssql_bind($v_sql2, "@p_service_item_id", $v_pure_iid, 'varchar', 50);
      gsi_mssql_bind($v_sql2, "@p_has_service", $v_has_service, 'char', 1, true);

      $v_result2 = mssql_execute($v_sql2);

      if(!$v_result2) {
        display_mssql_error("gsi_cmn_order_ext_check_for_service_line", "called from showPureingUpsell in ShoppingCart.php");
      }
      mssql_free_statement($v_sql2);
      mssql_free_result($v_result2);

      if($v_has_service == 'Y') { //already has PUREing
        $v_ret = false;
      } else { //doesn't have PUREing but is eligible
        $v_ret =  true;
      }
  }
    return $v_ret;
  }
   
  private function getPersonalizedText($p_orig_line_ref, &$p_note_text, &$p_is_oem) {
    global $mssql_db;

    $v_sql = "select ' ' + note_text as short_text 
                   , case note_title when 'Oem Info' then 'Y' else 'N' end as oem_yn 
              from direct..gsi_order_notes
              where entity_name = 'SO_LINES' 
                and order_source_id = '9' 
                and original_system_reference = '$this->v_order_number' 
                and original_system_line_reference = '$p_orig_line_ref' 
                and note_title in ('Oem Info', 'Personalize Text')";

    $v_result = mssql_query($v_sql);

    if(!$v_result) {
      display_mssql_error($v_sql, "called from getPersonalizedText() in ShoppingCart.php");
    }

    while($va_row = mssql_fetch_array($v_result)) {
      $p_note_text = $va_row['short_text'];
      if($va_row['oem_yn'] == 'Y') {
        $p_is_oem = TRUE;
      } else {
        $p_is_oem = FALSE;
      }
    }

    mssql_free_statement($v_sql);
    mssql_free_result($v_result);
  }

  private function getLogoFeeFlag($p_inventory_item_id) {

    global $mssql_db;

    $v_sql = "select count(*) as logo_count 
              from r12pricing.dbo.gsi_cmn_logo_process_fees 
              where process_fee_id = $p_inventory_item_id";

    $v_result = mssql_query($v_sql);

    if(!$v_result) {
      display_mssql_error($v_sql, "called from getLogoFeeFlag() in ShoppingCart.php");
    }

    if($va_row = mssql_fetch_array($v_result)) {
      $v_logo_count = $va_row['logo_count'];
    }

    mssql_free_result($v_result);

    if($v_logo_count > 0) {
      $v_logo_fee = TRUE;
    } else {
      $v_logo_fee = FALSE;
    }

    return $v_logo_fee;

  }

  private function checkMinOrderQty($p_category_id) {

    global $mssql_db;

    $v_check = FALSE;

    $v_web_categories = "GSI_CHECK_MIN_ORDER_QTY_CATEGORIES";
    $v_web_profile = C_WEB_PROFILE;
    $v_stmt = mssql_init("r12pricing..gsi_profile_value");

    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_web_profile, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_profile_name", $v_web_categories, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@o_profile_value", $v_web_logo_cat, 'varchar', 50, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("r12pricing..gsi_profile_value", "called from checkMinOrderQty() in ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    if(!empty($v_web_logo_cat)) {
      $va_logo_cat = explode(',', $v_web_logo_cat);

      foreach($va_logo_cat as $v_logo_cat) {
        if($v_logo_cat == $p_category_id) {
          $v_check = TRUE;
          break;
        }
      }
    }

    return $v_check;

  }

  private function getSkuFlags($p_inventory_item_id, &$p_store_pickup_flag, &$p_austin_to_store_flag) {

    global $web_db;

    $v_store_pickup_flag = FALSE;
    $v_austin_to_store_flag = FALSE;  
    
    $v_sql = "select store_pickup_flag, austin_to_store_flag
			  from gsi_item_info_all
              where inventory_item_id = $p_inventory_item_id";

    $v_result = mysqli_query($web_db, $v_sql);

    display_mysqli_error($v_sql);

    if($va_row = mysqli_fetch_array($v_result)) {
      $v_store_pickup_flag = $va_row['store_pickup_flag'];
      $v_austin_to_store_flag = $va_row['austin_to_store_flag'];
    }

    mysqli_free_result($v_result);

    if($v_store_pickup_flag == 'Y') {
      $p_store_pickup_flag = TRUE;
    } else {
      $p_store_pickup_flag = FALSE;
    }

    if($v_austin_to_store_flag == 'Y') {
      $p_austin_to_store_flag = TRUE;
    } else {
      $p_austin_to_store_flag = FALSE;
    }

  }

  private function checkItemAvailability($p_item_id_str, $p_item_qty_str, $p_org_id) {

    global $mssql_db;

    $v_stmt = mssql_init("reports.dbo.gsi_isp_availability");
    
    gsi_mssql_bind($v_stmt, "@p_item_id_str", $p_item_id_str, 'varchar', 5000);
    gsi_mssql_bind($v_stmt, "@p_quantity_str", $p_item_qty_str, 'varchar', 5000);
    gsi_mssql_bind($v_stmt, "@p_org_id", $p_org_id, 'bigint', 50);
    gsi_mssql_bind($v_stmt, "@p_availability_str", $v_available, 'varchar', 5000, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("report.dbo.gsi_isp_availability", "called from checkItemAvailability() in ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    if($v_available == 'Y') {
      $v_available = TRUE;
    } else {
      $v_available = FALSE;
    }

    return $v_available;

  }

  public function updateATP($p_line_reference, $p_quantity) {

    global $mssql_db;

    //find inventory_item_id for the line
    $v_sql = "select v.inventory_item_id
              from  direct.dbo.gsi_cmn_order_lines_v v
              where order_number = '$this->v_order_number'
                and original_system_line_reference = '$p_line_reference'";

    $v_result = mssql_query($v_sql);

    if(!$v_result) {
      display_mssql_error($v_sql, "called from deleteLine() in ShoppingCart.php");
    }

    if($va_row = mssql_fetch_array($v_result)) {
      $v_inventory_item_id = $va_row['inventory_item_id'];
    }
    mssql_free_result($v_result);
    
	//ATPExtendedSoap::checkATP($v_inventory_item_id,$p_quantity);


  }
  
  public function updateLine($p_line_reference, $p_quantity) {

    global $mssql_db;

    //find style number for the line
    $v_sql = "select v.style_number
              from  direct.dbo.gsi_cmn_order_lines_v v
              where order_number = '$this->v_order_number'
                and original_system_line_reference = '$p_line_reference'";

    $v_result = mssql_query($v_sql);

    if(!$v_result) {
      display_mssql_error($v_sql, "called from deleteLine() in ShoppingCart.php");
    }

    if($va_row = mssql_fetch_array($v_result)) {
      $v_style_number = $va_row['style_number'];
    }

    if(!empty($v_style_number)) {
      $this->updateItem($p_line_reference, $p_quantity, $v_style_number);
    }

  }

  private function updateItem($p_line_reference, $p_quantity, $p_style_number) {

    if($p_quantity < 1) { //if there's somehow a negative quantity, it becomes 0
      ////using "N'0'" instead of just '0' in DB calls prevents the 0 from being interpreted as null
      $p_quantity = "N'0'";
    }

    $this->updateTlabor($p_line_reference, $p_quantity);
    $this->updateLineQuantity($p_line_reference, $p_quantity);
    $this->handleLogoProcessFee($p_style_number);

  }

  private function  updateTlabor($p_line_reference, $p_quantity) {
    global $mssql_db;

    $v_sql = "select count(*) as count 
              from direct.dbo.gsi_cmn_order_lines_v
              where order_number = '$this->v_order_number'
                and style_number = '30047701'";

    $v_result = mssql_query($v_sql);

    if(!$v_result) {
      display_mssql_error($v_sql, "called from updateTlabor() in ShoppingCart.php");
    }

    while($va_row = mssql_fetch_array($v_result)) {
      $v_count = $va_row['count'];
    }

    mssql_free_result($v_result);

    if($v_count > 0) {
      $v_stmt = mssql_init("direct.dbo.gsi_cmn_order_ext_update_tlabor_set_qty");

      gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@p_line_id", $p_line_reference, 'int');
      gsi_mssql_bind($v_stmt, "@p_new_qty", $p_quantity, 'int');

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_order_ext_update_tlabor_set_qty", "called from updateTlabor() in ShoppingCart.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);
    }
  }

  private function updateLineQuantity($p_line_reference, $p_quantity) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    $v_stmt = mssql_init("direct..gsi_cmn_order_update_line_qty");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_line_id", $p_line_reference, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_new_qty", $p_quantity, 'int');
    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_update_line_qty", "called from updateLineQuantity in ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  private function handleLogoProcessFee($p_style_number) {

    global $mssql_db;

    $v_profile_scope = C_WEB_PROFILE;

    $v_stmt = mssql_init("gsi_cmn_order_ext_handle_logo_process_fee");

    gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_style_number", $p_style_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_status", $v_return_status, 'varchar', 50);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_ext_handle_logo_process_fee", "called from handleLogoProcessFee() in ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  public function updateIspWarehouse($p_line_number, $p_store, $p_line_type) {

    global $mssql_db;

    // update warehouse_id in the  line
    $_SESSION['s_do_reprice']   = 'Y' ;
    $_SESSION['s_do_totals']    = 'Y' ;

    $v_stmt = mssql_init("direct.dbo.gsi_cmn_order_ext_update_isp_warehouse");

    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_line_number", $p_line_number, 'int');
    gsi_mssql_bind($v_stmt, "@p_warehouse_id", $p_store, 'int');
    gsi_mssql_bind($v_stmt, "@p_isp_line_type", $p_line_type, 'int');
      
    $v_result = mssql_execute($v_stmt);
 
    if(!$v_result){
      display_mssql_error("gsi_cmn_order_ext_update_isp_warehouse", "called from updateIspWarehouse() ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

  }

  public function emptyCart() {

    $i_order = new Order($this->v_order_number);
    $i_order->emptyCart();

  }

  private function updateOrderTrackingIp() {
    global $mssql_db;

    //update tracking code and IP address
    $v_tracking_code = $_COOKIE['tcode'];
    //$v_ip_address = (!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR']);
    if ( (isset($_SERVER['HTTP_VIA']))  && (strrpos($_SERVER['HTTP_VIA'], "moovweb") > 0))
    {
        $v_ip_address = substr($_SERVER['HTTP_X_FORWARDED_FOR'],0,strripos($_SERVER['HTTP_X_FORWARDED_FOR'],',') );
    }
    else if (isset($_SERVER['HTTP_TRUE_CLIENT_IP']))
 	{
 		$v_ip_address    = $_SERVER['HTTP_TRUE_CLIENT_IP'];
 	}
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
 	{
 		$v_ip_address    = $_SERVER['HTTP_X_FORWARDED_FOR'];
 	}
 	else {
        $v_ip_address    = ( !empty($_SERVER['HTTP_CLIENT_IP']) ?  $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'] ) ;
 	}
    
 		
    $v_return_status = '';

    $v_stmt = mssql_init("gsi_cmn_order_ext_update_order_attributes");

    gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_tracking_code", $v_tracking_code, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_ip_address", $v_ip_address, 'varchar', 15);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 100, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_ext_update_order_attributes", "called from updateOrderTrackingIp() in ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);
 
  }

  public function addPureing($p_style_number, $p_quantity, $p_line_number) {

    global $mssql_db;

    $v_per_item = 'PURE';
    $v_service_line_number = '';
    $v_profile_scope = C_WEB_PROFILE;
   
    $v_stmt = mssql_init("gsi_cmn_order_customization_add_service");
	
	
    gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_style_number", $p_style_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_quantity", $p_quantity, 'int');
    gsi_mssql_bind($v_stmt, "@p_item_line_num", $p_line_number, 'int');
    gsi_mssql_bind($v_stmt, "@p_service_type", $v_per_item, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_service_line_num", $v_service_line_num, 'int', -1, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_order_customization_add_service", "called from addPureing() in ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    if(!empty($v_service_line_num)) { 
      //add a note

      $v_note = "ITEM $p_style_number";

      //find sku values for note
      $v_sql = "select style_attr1, style_attr2, style_attr3, style_attr4
                from direct.dbo.gsi_cmn_order_lines_v 
                where order_number = '$this->v_order_number' 
                  and line_number = $p_line_number";

      $v_result = mssql_query($v_sql);

      if(!$v_result) {
        display_mssql_error($v_sql, "called from addPureing() in ShoppingCart.php");
      }

      while($va_row = mssql_fetch_array($v_result)) {
        $v_segment2 = $va_row['style_attr1'];
        $v_segment3 = $va_row['style_attr2'];
        $v_segment4 = $va_row['style_attr3'];
        $v_segment5 = $va_row['style_attr4'];
          
      }

      mssql_free_result($v_result);

      if(!empty($v_segment2)) {
        $v_note .= " $v_segment2 $v_segment3 $v_segment4 $v_segment5 PURE ";
      } else {
        $v_note .= " PURE ";
      }

      $v_stmt = mssql_init("gsi_cmn_order_customization_add_note");

      gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_line_number", $p_line_num, 'int');
      gsi_mssql_bind($v_stmt, "@p_note_text", $v_note, 'varchar', 1000);
      gsi_mssql_bind($v_stmt, "@p_service", $v_per_item, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
		   
      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("gsi_cmn_order_customization_add_note", "called from () in ShoppingCart.php");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result($v_result);
    }

  }

  private function getPromoName($p_source_code) {

    global $mssql_db;

    $v_stmt = mssql_init("gsi_promo_get_promo_name");

    gsi_mssql_bind($v_stmt, "@p_source_code", $p_source_code, 'varchar', 30);
    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($v_stmt, "@p_promo_name", $v_promo_name, 'varchar', 100, true);
    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 250, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_promo_get_promo_name", "called from getPromoName() in ShoppingCart.php");
    }

    mssql_free_statement($v_stmt);
    mssql_free_result($v_result);

    return $v_promo_name;

  }
  
  public static function restoreUserCart($user_id){
      global $mssql_db;
      
      $v_stmt = mssql_init("direct.dbo.gsi_cmn_restore_cart_logged_in");
      gsi_mssql_bind($v_stmt, "@p_user_id", $user_id, 'int');
      gsi_mssql_bind($v_stmt, "@p_order_number", $order_number, 'varchar', 50, true);
      gsi_mssql_bind($v_stmt, "@p_order_prices", $order_prices, 'varchar', 150, true);
      $v_result = mssql_execute($v_stmt);
      mssql_free_statement($v_stmt);
      
      if (!$v_result) {
          display_mssql_error("gsi_cmn_restore_cart_logged_in", "called from restoreUserCart() in ShoppingCart.php");
      }
      
      if(!empty($order_number)) {
          $_SESSION['s_order_number'] = $order_number;
          $_SESSION['s_us_order_number'] = $order_number;
          
		  if(!empty($order_prices))
          {
              $pattern = '/([0-9\.]+)\|([0-9\.]+)\|([0-9\.]+)\|([0-9\.]+)\|([0-9\.]+)/';
              preg_match($pattern, $order_prices, $matches);
              $taxes = $matches[4];
              $total = $matches[5] - $taxes;
              $_SESSION['CartTotal'] = format_currency($total);
          }
      }
  }

  public function restoreCart($p_email, $p_offset)
  {
    $i_abandoned_cart = new AbandonedCart();
    $v_order_info = $i_abandoned_cart->findAbandonedCart($p_email, $p_offset);
    
    if(!empty($v_order_info['order_number'])) {
     $_SESSION['s_order_number'] = $v_order_info['order_number'];
     $this->v_order_number = $v_order_info['order_number'];
	 
     if ($v_order_info['order_type'] == 'CANADA')
     {
     	$_SESSION['s_ca_order_number'] = $v_order_info['order_number'];
     } else {
     	$_SESSION['s_us_order_number'] = $v_order_info['order_number'];
     }
    }
  }
  
  public function validateQBD()
  {
  	
  	$v_return_status = 'Success';
  	
  	if ($_SESSION['site'] != 'CA') {
	  	//this will get QBD related info if applicable
	    $v_arr_QBD = get_active_QBD();
	
	 	if (!empty($v_arr_QBD['style'])) {
		
		  	//We need to pass the STYLE, remaining qty, qbd limit and order number to the procedure
		  	global $mssql_db;
		  	
		    $v_profile_scope = C_WEB_PROFILE;
		
		    $v_stmt = mssql_init("gsi_cmn_order_validate_qbd");
		
		    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
		    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
		    gsi_mssql_bind($v_stmt, "@p_style", $v_arr_QBD['style'], 'varchar', 50);
		    gsi_mssql_bind($v_stmt, "@p_qbd_limit", $v_arr_QBD['qbd_limit'], 'int');
		    gsi_mssql_bind($v_stmt, "@p_qbd_left", $v_arr_QBD['qbd_qty_left'], 'int');
		    gsi_mssql_bind($v_stmt, "@p_qbd_id", $v_arr_QBD['qbd_id'], 'varchar', 50);
		    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 500, true);
		
		    $v_result = mssql_execute($v_stmt);
		
		    if(!$v_result) {
		      display_mssql_error("gsi_cmn_order_validate_qbd", "called from validateQBD() in ShoppingCart.php");
		    }
		
		    mssql_free_statement($v_stmt);
	  	}else {
	  		//no active qbd; then clear qbd_id in the so lines

	  		global $mssql_db;
		  	
		    $v_profile_scope = C_WEB_PROFILE;
		
		    $v_stmt = mssql_init("gsi_qbd_remove_qbd_items");
		
		    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
		    gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_profile_scope, 'varchar', 30);
		    gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 500, true);	    
		
		    $v_result = mssql_execute($v_stmt);
		
		    if(!$v_result) {
		      display_mssql_error("gsi_qbd_remove_qbd_items", "called from validateQBD() in ShoppingCart.php");
		    }
		
		    mssql_free_statement($v_stmt);
	  		
	  	}
  	}

    return $v_return_status;  
    
  	
  }

// Load up a saved basket
  public function loadSavedCart($saved_basket_id)
  {
      global $mssql_db;
      $return_status = -1;
      
      $v_stmt = mssql_init("direct.dbo.gsi_cmn_order_load_saved_basket");
      
      gsi_mssql_bind($v_stmt, "@p_saved_basket_id", $saved_basket_id, 'varchar', 36);
      gsi_mssql_bind($v_stmt, "@p_customer_id", $_SESSION['s_customer_id'], 'bigint');
      gsi_mssql_bind($v_stmt, "@p_cust_price_list_id", $_SESSION['s_cust_price_list'], 'bigint');
      gsi_mssql_bind($v_stmt, "@p_sales_channel", $_SESSION['s_sales_channel'], 'varchar', 30);
      gsi_mssql_bind($v_stmt, "@o_p_order_id", $_SESSION['s_order_number'], 'varchar', 50, true);
      gsi_mssql_bind($v_stmt, "@o_return_status", $return_status, 'int', 0, true);
      
      $v_result = mssql_execute($v_stmt);
      
      if(!$v_result) {
          display_mssql_error("direct.dbo.gsi_cmn_order_load_saved_basket", "called from loadSavedCart(\$saved_basket_id) in ShoppingCart.php");
      }
      
      mssql_free_statement($v_stmt);
      
      if($return_status != 1)
      {
          return '<script type="text/javascript">alert("Error loading saved cart!");</script>';
      }
      $_SESSION['s_us_order_number'] = $_SESSION['s_order_number'];
      $this->v_order_number = $_SESSION['s_order_number'];
      return '';
  }
  
  public function createSavedCart($only_url = false)
  {
      global $mssql_db;
      
      $v_stmt = mssql_init("direct.dbo.gsi_cmn_order_create_saved_basket");
      gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
      gsi_mssql_bind($v_stmt, "@o_unique_id", $unique_id, 'varchar', 36, true);
      $v_result = mssql_execute($v_stmt);
      
      if(!$v_result) {
          display_mssql_error("direct.dbo.gsi_cmn_order_create_saved_basket", "called from createSavedCart() in ShoppingCart.php");
      }
      
      mssql_free_statement($v_stmt);
      
      if(empty($unique_id))
      {
          return false;
      }
      else
      {
          if($only_url)
          {
              return 'https://' . $_SERVER['SERVER_NAME'] . '/savedcart/loadcart?savedCart=' . $unique_id;
          }
          $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
          $z_view->url = 'https://' . $_SERVER['SERVER_NAME'] . '/savedcart/loadcart?savedCart=' . $unique_id;
          $z_view->cartId = $unique_id;
          return $z_view->render("saved_cart_results.phtml");
      }
  }
  
  
  private function atp_isp_msg($v_promise_date, $atpMsg){
    $v_promise_date = strtotime($v_promise_date);

    if( (strpos($atpMsg, "Today") > -1) or ( strpos($atpMsg, "Next business day") > -1 ) ){
        $ipsMSG = "Arriving on " . date( "d-M-Y", strtotime( "+3 days", $v_promise_date ) );
    }else{
        $ipsMSG = $atpMsg;
    }
    return $ipsMSG;
  }
  
  public function getMasterPassData($new = FALSE)
  {
      // Create data object
      $masterPassData = new MasterPassData();
      
      if($new)
      {
          try
          {
              // Create controller
              $controller = new MasterPassController($masterPassData);
          
              // Get the OAuth tokens for MasterPass
              $masterPassData = $controller->getRequestToken();
              //$masterPassData = $controller->getPairingToken(); // MP Express Checkout
          
              // Post cart items
              $this->getCartItems($va_items, $pa_cm_tags, $p_google_checkout_allowed, $p_paypal_checkout_allowed, $p_amazon_checkout_allowed);
              $controller->addShoppingCartItems($va_items);
              $masterPassData = $controller->postShoppingCart();
              
              // Save in session
              $_SESSION["mpRequestToken"] = $masterPassData->requestToken;
              //$_SESSION["mpPairingToken"] = $masterPassData->pairingToken;// MP Express Checkout
          }
          catch(Exception $e)
          {
              $masterPassData = false;
          }
      }
      else
      {
          $mpData = $_SESSION["masterPassData"];
          $masterPassData->requestToken = $_SESSION["mpRequestToken"];
          //$masterPassData->pairingToken = $_SESSION["mpPairingToken"];// MP Express Checkout
          $masterPassData->transactionId = $_SESSION["mpTransactionId"];
          $masterPassData->preCheckoutTransactionId = $_SESSION["mpPrecheckoutTransactionId"];
      }
      
      return $masterPassData;
  }
  
  public function getMasterPassCheckoutInfo($masterPassData)
  {
      // Create the controller
      $controller = new MasterPassController($masterPassData);
      
      // Get the checkout token
      $masterPassData = $controller->getAccessToken();
      
      // If we're pairing then get the long access token
      if($masterPassData->pairingVerifier)
      {
          $masterPassData = $controller->getLongAccessToken();
          $_SESSION['mp_long_access'] = $masterPassData->longAccessToken;
      }
      
      // Get the checkout data
      $masterPassData = $controller->getCheckoutData();
      
      // Save the transaction ID
      $_SESSION["mpTransactionId"] = $masterPassData->transactionId;
      
      return $masterPassData;
  }
  
  public function mpPostBack($status, $orderNumber)
  {
      try
      {
          // Get the MasterPass data
          $masterPassData = $this->getMasterPassData();
          
          // Create the controller
          $controller = new MasterPassController($masterPassData);
          
          // Get order info
          $v_stmt = mssql_init("orders.dbo.gsi_mp_order_info");
          
          gsi_mssql_bind($v_stmt, "@p_order_number", $orderNumber, 'varchar', 50);
          gsi_mssql_bind($v_stmt, "@o_auth_number", $authNumber, 'varchar', 6, true);
          gsi_mssql_bind($v_stmt, "@o_order_attribute8", $attribute8, 'varchar', 150, true);
          
          $v_result = mssql_execute($v_stmt);
          mssql_free_statement($v_stmt);
          
          if($v_result)
          {
              // Get the total
              $arr = explode('|', $attribute8);
              $total = $arr[count($arr)-1];
          
              // Multiply by 100 to get rid of decimals
              $total *= 100;
          
              // Postback the order data to MasterPass
              $masterPassData = $controller->postBackTransaction($status, $total, $authNumber);
          }
          else
          {
              display_mssql_error("orders.dbo.gsi_mp_order_info", "called from mpPostBack() in ShoppingCart.php");
          }
      }
      catch(Exception $e)
      {
          write_to_log("MasterPass PostBack Error ". $e->getMessage()." the call:".var_export($e,TRUE));
      }
  }
  
  private function postPreCheckoutData()
  {
      // Create data object
      $masterPassData = new MasterPassData();
      
      // Get the long access token
      $masterPassData->longAccessToken = $_SESSION["mp_long_access"];
      
      // Create the controller
      $controller = new MasterPassController($masterPassData);
      
      // Post precheckout data
      $masterPassData = $controller->postPreCheckoutData($masterPassData->longAccessToken);
      
      // Save the master pass data
      $_SESSION["mp_long_access"] = $masterPassData->longAccessToken;
      
      return "\"cardId\" : \"$masterPassData->preCheckoutCardId\",
      \"shippingId\" : \"$masterPassData->preCheckoutShippingAddressId\",
      \"precheckoutTransactionId\" : \"$masterPassData->preCheckoutTransactionId\",
      \"walletName\" : \"$masterPassData->walletName\",
      \"consumerWalletId\" : \"$masterPassData->consumerWalletId\",
      \"requestExpressCheckout\" : true,";
  }
}
?>
