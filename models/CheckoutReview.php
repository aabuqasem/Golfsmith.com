<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here
require_once('Zend/View.php');
require_once('CheckoutPage.php');
require_once('CheckoutLogin.php');
require_once('Address.php');
require_once('Order.php');
require_once('EmployeeCredit.php');
include_once('functions/QBD_functions.php');

class CheckoutReview extends CheckoutPage {

  //no constructor needed -- we're using the CheckoutPage constructor
 
  //display the page
  public function displayPage() {

    $this->i_site_init->loadMain();

    $i_order = new Order($this->v_order_number);
    $v_is_isp_only = $i_order->isISPOnly();

    //ISP layout is slightly different from non-ISP
    echo $this->displayHeaderIsp(TRUE);
    if($i_order->isISP() === TRUE) {
      echo $this->displayIspSection(TRUE);
    }

    echo $this->displayShipSection(TRUE);

    echo $this->displayShippingAndPayment(TRUE);
    echo $this->displayCharges(TRUE);
    echo $this->displaySubmit(TRUE);
    echo $this->displayFooter(TRUE);
	// remarketing vars
	$g_data["pageType"]="other";
    $g_data["prodid"]="";
    $g_data["totalvalue"]="";
    $this->i_site_init->loadFooter($g_data);
  }

  public function displayReviewContent($p_ship_method) {

    $i_order = new Order($this->v_order_number);
    $v_is_isp_only = $i_order->isISPOnly();

    //ISP layout is slightly different from non-ISP
    echo $this->displayHeaderIsp(FALSE);

    if($i_order->isISP() === TRUE) {
      echo $this->displayIspSection(TRUE);
    }

    echo $this->displayShipSection(TRUE);

    echo $this->displayShippingAndPayment(TRUE);
    echo $this->displayCharges(TRUE, $p_ship_method);
    echo $this->displaySubmit(TRUE);
    echo $this->displayFooter(FALSE);

  }

  private function displayHeaderIsp($p_show_div = TRUE) {
    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
    $z_view->show_div = $p_show_div;
    $z_view->checkout_header_path = HTML_PATH . '_shopping_cart/headers/header_review.html';
    return $z_view->render("checkout_review_header.phtml");
  }

  private function displayCartItemsDiv() {

    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
    return $z_view->render("checkout_review_no_ship_items.phtml");

  }

  public function displayReview($p_show_div = TRUE) {

    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only show login page if not already logged in
    if(empty($v_customer_number)) {

      $i_login = new CheckoutLogin();
      return $i_login->displayLogin($p_show_div);

    } else {

      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

      $z_view->show_div = $p_show_div;

      $this->getShipItems($va_items);
      $z_view->ship_items = $va_items;
      $z_view->item_count = count($va_items);

      return $z_view->render("checkout_review.phtml");

    }
  }

  public function displayIspSection($p_show_div = TRUE) {

    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only show content if already logged in
    if(!empty($v_customer_number)) {

      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

      $z_view->show_div = $p_show_div;

      $this->getIspItems($va_items);
      $z_view->store_items = $va_items;

      return $z_view->render("checkout_review_isp.phtml");

    }

  }

  public function displayShipSection($p_show_div = TRUE) {

    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only show content if already logged in
    if(!empty($v_customer_number)) {

      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

      $z_view->show_div = $p_show_div;

      $i_order = new Order($this->v_order_number);
      $z_view->has_sts = $i_order->isSTS();
      $z_view->is_isp_only = $i_order->isISPOnly();

      if($z_view->is_isp_only !== TRUE) {
        $this->getShipItems($va_items);

        foreach($va_items as $v_isp_warehouse_id => $va_data) {
          if($v_isp_warehouse_id == 'web') {
            $v_ship_address_id = $_SESSION['s_ship_address_id'];

            $i_address = new Address($_SESSION['s_customer_id'], $v_ship_address_id, $_SESSION['s_ship_contact_id'], $_SESSION['s_ship_phone_id']);
            $i_address->getAddressFields($v_ship_is_international, $va_address);

          } else {
            $this->getStoreInfo($v_isp_warehouse_id, $va_address);
          }

          $va_addresses[$v_isp_warehouse_id] = $va_address;

        }

        $z_view->ship_items = $va_items;
        $z_view->item_count = count($va_items);
        $z_view->ship_addresses = $va_addresses;
      }

      $z_view->ship_method_code = $_SESSION['s_ship_method'];
      return $z_view->render("checkout_review_ship.phtml") . $z_view->render("checkout_review.phtml");

    }

  }

  public function displayShipSectionOnly($p_show_div = TRUE) {

    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only show content if already logged in
    if(!empty($v_customer_number)) {

      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

      $z_view->show_div = $p_show_div;

      $i_order = new Order($this->v_order_number);
      $z_view->has_sts = $i_order->isSTS();

      $this->getShipItems($va_items);

      foreach($va_items as $v_isp_warehouse_id => $va_data) {
        if($v_isp_warehouse_id == 'web') {
          $v_ship_address_id = $_SESSION['s_ship_address_id'];

          $i_address = new Address($_SESSION['s_customer_id'], $v_ship_address_id, $_SESSION['s_ship_contact_id'], $_SESSION['s_ship_phone_id']);
          $i_address->getAddressFields($v_ship_is_international, $va_address);

        } else {
          $this->getStoreInfo($v_isp_warehouse_id, $va_address);
        }

        $va_addresses[$v_isp_warehouse_id] = $va_address;

      }

      $z_view->ship_items = $va_items;
      $z_view->item_count = count($va_items);
      $z_view->ship_addresses = $va_addresses;

      return $z_view->render("checkout_review.phtml");

    }

  }

  public function displayShippingAndPayment($p_show_div = TRUE) {
    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only display this section if the customer is logged in
    if(!empty($v_customer_number)) {

      $i_order = new Order($this->v_order_number);

      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

      $z_view->show_div = $p_show_div;

      $v_return = $i_order->getPaymentInfo($v_card_type, $v_card_number, $v_expiry, $v_amt_paid);

      //adjust gift card payments
      $i_gc_payment = new GiftCardPayment();
      $i_cc_payment = new CreditCardPayment();
      $i_payment = new Payment();

      //update the whole order just in case
      //link addresses, update order with ship method, etc.
      $v_update_return_status = $i_order->updateOrder($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_ship_address_id'], $_SESSION['s_ship_contact_id'], $_SESSION['s_ship_method']);

      $i_gc_payment->adjustGiftCardPayments();

      $_SESSION['s_do_totals'] = 'Y';

      $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_return_status);

      $i_gc_payment->adjustGiftCardPayments();

      //calc totals again to get accurate GC info
      $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_return_status);
      
      $v_tmp_sub = $v_sub_total + $v_ship_total + $v_tax_total;
      $v_net_total   = round(($v_tmp_sub - $v_gc_total), 2) ;
      
      $v_isp_dummy_pmt_status = $i_order->findISPDummyPayment();

      if($v_net_total == 0 && $v_isp_dummy_pmt_status == 'NOT PAID') {
        $v_net_total = 1;
      }

      if($_SESSION['site'] == 'CA' && $_SESSION['smethod'] == 'I' && $v_net_total == 0) {
        $v_net_total = 1;
      }

      $z_view->require_payment = FALSE;

      if($v_net_total > 0) {
        $v_payment_method = $i_order->getPaymentMethod($_SESSION['s_ship_address_id'], $v_net_total);

        if($v_payment_method == 'WIRETR') {

          //remove all gift cards
          $i_payment->void_payments('Y');

          //DB proc already voids existing wire transfers and credit card payments
          $i_payment->add_payment($v_net_total, '', '', '', '');

        } else {

          //remove all wire transfer payments
          if(empty($v_card_type) && $v_amt_paid > 0) {  //we have a wire transfer to remove
            //void the existing wire transfer
            $i_payment->voidWireTransferPayments();
          }

          if(!empty($v_card_type)) {
            $i_cc_payment->updateCreditCardAmount($v_net_total);
          } else {

            if($_SESSION['s_using_paypal'] != 'Y' || empty($_SESSION['s_paypal_token']) || empty($_SESSION['s_paypal_payerid'])) {
              //check for a saved card
              $i_cc_payment->checkSavedCard($_SESSION['s_customer_id'], $v_token_id, $v_masked_cc, $v_cc_type, $v_expiry, $v_days_auth_valid);

              if(!empty($v_token_id) && $_SESSION['s_using_paypal'] != 'Y') {  //if saved card, we can automatically add the payment
                $v_status = $i_cc_payment->addPaymentFromSavedCard($v_net_total, $v_token_id, $v_masked_cc, $v_cc_type, $v_expiry, $v_days_auth_valid);
              } else {
                $z_view->require_payment = TRUE;
                //need to go back to the payment page
              }
            }
          }

        }
      } else {
      	
      	if($v_net_total == 0 && $v_isp_dummy_pmt_status != 'PAID') {
	        //void all non-gc payments
    	    $i_payment->void_payments('N');
      	}
      }

      $v_ship_address_id = $_SESSION['s_ship_address_id'];

      $i_address = new Address($_SESSION['s_customer_id'], $v_ship_address_id, $_SESSION['s_ship_contact_id'], $_SESSION['s_ship_phone_id']);
      $i_address->getAddressFields($v_ship_is_international, $va_ship_address);
      $i_address->setAll($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_bill_phone_id']);
      $i_address->getAddressFields($v_bill_is_international, $va_bill_address);

      $z_view->ship_is_international = $v_ship_is_international;
      $z_view->ship_address = $va_ship_address;
      $z_view->bill_is_international = $v_bill_is_international;
      $z_view->bill_address = $va_bill_address;


      //get payment info again after adjustments
      $v_return = $i_order->getPaymentInfo($v_card_type, $v_card_number, $v_expiry, $v_amt_paid);

      if(!empty($v_card_type)) {
        $z_view->card_type = $v_card_type;
      } else if($_SESSION['s_using_paypal'] == 'Y' && !empty($_SESSION['s_paypal_token']) && !empty($_SESSION['s_paypal_payerid']) && $v_net_total > 0) {
       $z_view->card_type = 'PayPal';
       $v_amt_paid = $v_net_total;
      } else if($v_amt_paid > 0) { //must be a wire transfer
        $z_view->card_type = 'Wire Transfer';
      } 
      $z_view->card_amount = $v_amt_paid;
      $z_view->card_number = str_replace(' ', '-', $v_card_number);
      $z_view->expiry = $v_expiry;

      $i_order = new Order($this->v_order_number);

      $v_status = $i_order->getPreviousGiftcards($va_giftcards);

      $z_view->previous_giftcards = $va_giftcards;
      $z_view->isISPSTSOnly = $i_order->isISPSTSOnly();

      return $z_view->render("checkout_review_ship_payment.phtml");
    }
  }

  public function updateReviewShipping($p_ship_method) {
  
    $i_order = new Order($this->v_order_number);

    if(!empty($p_ship_method)) {

      $_SESSION['s_ship_method'] = $p_ship_method;
      $_SESSION['smethod'] = $p_ship_method;

      //update the whole order just in case
      //link addresses, update order with ship method, etc.
      $v_update_return_status = $i_order->updateOrder($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_ship_address_id'], $_SESSION['s_ship_contact_id'], $p_ship_method);

      $_SESSION['s_do_totals'] = 'Y';

    }

    $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_return_status);
  
  }

  public function displayCharges($p_show_div = TRUE, $p_ship_method) {

    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only display this section if the customer is logged in
    if(!empty($v_customer_number)) {

      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

      $z_view->show_div = $p_show_div;

      $i_order = new Order($this->v_order_number);
      $z_view->is_isp_only = $i_order->isISPOnly();
      //$z_view->has_external_items = $i_order->checkForPrebook();        
      
      $i_order->getDropshipRestrictions($v_dropship_restrictions);

      $z_view->has_ship_restrictions = FALSE;
      
      $va_dropship_restrictions = explode(',', str_replace('"', '', $v_dropship_restrictions));

      if($va_dropship_restrictions[0] || !$_SESSION['s_ship_method']) {
        $p_ship_method = 'G';
        $z_view->ship_method_code = 'G';
        $z_view->has_ship_restrictions = TRUE;
      }
      
      if(!empty($p_ship_method)) {

        $_SESSION['s_ship_method'] = $p_ship_method;
        $_SESSION['smethod'] = $p_ship_method;

        //update the whole order just in case
        //link addresses, update order with ship method, etc.
        $v_update_return_status = $i_order->updateOrder($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_ship_address_id'], $_SESSION['s_ship_contact_id'], $p_ship_method);

        $_SESSION['s_do_totals'] = 'Y';

      }
      
      if($_SESSION['site'] == 'US') {
        $this->updateIspLineAddresses();
      }
      
      $z_view->ship_method_code = $_SESSION['s_ship_method'];

      $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_return_status);

      $z_view->sub_total = format_currency($v_sub_total);
      $z_view->ship_total = format_currency($v_ship_total);
      $z_view->tax_total = format_currency($v_tax_total);
      if(!empty($v_gc_total)) {
        $z_view->gc_total = format_currency($v_gc_total);
      } else {
        $z_view->gc_total = '';
      }
      $z_view->order_total = format_currency($v_sub_total + $v_ship_total + $v_tax_total - $v_gc_total);
    
      $v_isp_totals_string = $i_order->getISPTotalsString();
      $i_order->extractISPTotals($v_isp_totals_string, $v_isp_total, $v_isp_sub_total, $v_isp_tax);
  
      $v_cart_total = $v_isp_total + ($v_sub_total + $v_ship_total + $v_tax_total - $v_gc_total);
      $_SESSION['CartTotal'] = format_currency($v_cart_total);       
      echo '<SCRIPT TYPE="text/javascript">document.getElementById(\'CartTotal\').innerHTML = \'<strong>' . $_SESSION['CartTotal'] . '</strong>\';</SCRIPT>';

      //check country restrictions
      $z_view->intl_restrictions = FALSE;
      if(!empty($_SESSION['s_customer_id'])) {
        if($this->checkCountryRestrictedItem() == 1 || $this->checkSourcedItemShippedInt() == 1) {
          $z_view->intl_restrictions = TRUE;
        }
      }

      $this->getShippingOptions($z_view->has_ship_restrictions, $va_shipping_options);
      $z_view->shipping_options = $va_shipping_options;
      $z_view->promotion_savings = format_currency($i_order->getTotalPromotionSavings());
      $z_view->isIsp = $i_order->isISP();
            
      return $z_view->render("checkout_review_charges.phtml");
    }
  }

  public function isRestricted() {
  	  //check country restrictions
  	  $v_restricted = FALSE;     
      if(!empty($_SESSION['s_customer_id'])) {
        if($this->checkCountryRestrictedItem() == 1 || $this->checkSourcedItemShippedInt() == 1) {
          $v_restricted = TRUE;
        }
      }
      return $v_restricted;
  }
  
  
  public function displaySubmit($p_show_div = TRUE, $p_salesrep_number, $p_salesrep_name) {

    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only display this section if the customer is logged in
    if(!empty($v_customer_number)) {

      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

      $z_view->show_div = $p_show_div;

      $i_order = new Order($this->v_order_number);
      $z_view->is_isp_only = $i_order->isISPOnly();

      //may need to change this if statement
      if (substr($_SESSION['s_remote_address'], 0, 4) == '192.' || substr($_SERVER['HTTP_X_FORWARDED_FOR'], 0, 4) == '192.' || substr($_SERVER['HTTP_TRUE_CLIENT_IP'], 0, 4) == '192.') {

        $z_view->show_badge_number_div = TRUE;
        $z_view->store_number = $_SESSION['s_store_number'];
        $z_view->salesrep_number = $p_salesrep_number;
        $z_view->salesrep_name = $p_salesrep_name;

        //need to assemble store list
        if(empty($z_view->store_number)) {
          $this->getOrganizationList($va_stores);
          $z_view->stores = $va_stores;
        }

      }

      return $z_view->render("checkout_review_submit.phtml");
    }

  }

  public function isISPOnly() {

    $i_order = new Order($this->v_order_number);
    $v_is_isp_only = $i_order->isISPOnly();

    if($v_is_isp_only === TRUE) {
      $v_is_isp_only = 'true';
    } else {
      $v_is_isp_only = 'false';
    }

    return $v_is_isp_only;

  }

  public function updateSalesAssociateInfo($p_salesrep_number, &$p_salesrep_name) {

    if(substr($_SESSION['s_remote_address'], 0, 4) == '192.' || substr($_SERVER['HTTP_X_FORWARDED_FOR'], 0, 4) == '192.') {

      if(!empty($p_salesrep_number)) {
        $i_employee_credit = new EmployeeCredit();
        $v_validation_message_list =& $i_employee_credit->validation_message_list;
        $p_salesrep_name = $i_employee_credit->salesrep_name;
      }

    }

  }

  public function getShippingOptions($p_has_ship_restrictions, &$pa_shipping_options) {
    $i_order = new Order($this->v_order_number);
    $i_order->getShippingOptions($p_has_ship_restrictions, $pa_shipping_options);
  }

  private function getOrganizationList(&$va_stores) {

    global $mssql_db;

    $va_stores = array();

    $v_sql = "select organization_code, display_store_name
              from reports..gsi_store_info_ext
              order by organization_code asc";

    $v_result = mssql_query($v_sql);

    if(!$v_result){
      display_mssql_error($v_sql, "called from getOrganizationList() in CheckoutReview.php");
    }

    while($va_row = mssql_fetch_array($v_result)) {

      $v_org_code = $va_row['organization_code'];
      $v_org_display = $va_row['display_store_name'];

      $va_store = array();
      $va_store['organization_code'] = $v_org_code;
      $va_store['organization_display'] = $v_org_code . ' - ' . $v_org_display;
      $va_stores[] = $va_store;

    }

  }

  private function getShipItems(&$pa_item_list) {
  	
  	if ($_SESSION['site'] != 'CA') {
  		$this->updateQBDInfo();
  	}
    global $mssql_db;

    $v_warehouse_id = $this->getWarehouseId();

    $pa_item_list = array();
    $pa_cm_tags = array();
    
    // get the right dates :)
    $atp_sql = "select
                v.original_system_line_reference
              , v.inventory_item_id
              , v.selling_price
              , v.ordered_quantity
              from  direct.dbo.gsi_cmn_order_lines_v v
              where order_number = '$this->v_order_number'
              order by ship_set_number, line_number";
    $atp_result = mssql_query($atp_sql);

    if (!$v_result){
      display_mssql_error($v_sql, "cart item query from shopping cart");
    }
    while($v_row = mssql_fetch_array($atp_result)) {
    	// the new ATP array
    	$tempArr[] = array('INVENTORY_ITEM_ID'=>$v_row["inventory_item_id"],'QUANTITY'=>$v_row["ordered_quantity"]);
    	$ItemKey = $v_row["inventory_item_id"];
    	$tempTotalOrdQtyArr[$ItemKey] += $v_row["ordered_quantity"];
    	$tempQtyArr[$ItemKey] = $v_row["ordered_quantity"];
    }
    
    foreach ($tempTotalOrdQtyArr as  $key => $value){
        $atp_arr[]= array("iiv"=>$key,"qty"=>$value);
    }
    
    $ATPresults= ATPExtendedSoap::checkATPProc($atp_arr,"Shoppingcart");
    
    /*
 	//$SOAPURL =ATPSOAPSERVICE_URL;  
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
              , v.qbd_id
              from  direct.dbo.gsi_cmn_order_lines_v v
              left join    direct.dbo.gsi_item_web_atp atp
              on atp.inventory_item_id = v.inventory_item_id
              and atp.organization_id  = '$v_out_warehouse_id'
              where order_number = '$this->v_order_number'
                and isnull(v.isp_line_type, 0) < 2
                and NOT style_number='GIFT BOX'
              order by isp_warehouse_id desc, ship_set_number, line_number";

    $v_result = mssql_query($v_sql);

    if (!$v_result){
      display_mssql_error($v_sql, "cart item query from shopping cart");
    }

    while($v_row = mssql_fetch_array($v_result)) {

      $va_item = array();

      $v_orig_line_ref     = $v_row['original_system_line_reference'];
      $v_inventory_item_id = $v_row['inventory_item_id'];
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
      $v_qbd_id            = $v_row['qbd_id'];

      unset($v_min_order_qty);
      unset($v_category_id);
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

      $va_item['original_line_reference'] = $v_orig_line_ref;
      $va_item['line_number'] = $v_line_number;

      $va_item['quantity'] = $v_ordered_quantity;
            
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
      }
      
      $va_item['selling_price'] = format_currency($v_selling_price);

      $this->getSkuInfo($v_segment1, $v_sku_desc, $v_min_order_qty, $v_category_id, $v_manuf_desc);

      if($v_inventory_item_id == '332375') { //don't display DSC01 lines
        continue;
      } else {
        $this->getSkuFlags($v_inventory_item_id, $v_store_pickup_flag, $v_austin_to_store_flag);
      }

      $va_item['is_oem'] = FALSE;
      if ($v_source_type_code == 'EXTERNAL') {
        $this->getPersonalizedText($v_orig_line_ref, $va_item['note_text'], $va_item['is_oem']);

        if(!empty($va_item['note_text'])) {
          $va_note_lines = explode("\n", $va_item['note_text']);
          $va_item['note_text'] = $va_note_lines;
        }
      }

      $this->getMarkdownSavings($v_segment1, $v_orig_price, $v_act_price, $v_savings_story, $v_inventory_item_id);

      $v_extended_price = $va_item['quantity'] * $v_selling_price;
      $v_selling_price_dsp = format_currency($v_selling_price);
      $v_extended_price_dsp = format_currency($v_extended_price);
      if(!empty($v_savings_story)) {
        $v_actual_price = $va_item['quantity'] * $v_selling_price;
      }

      if($v_list_price > $v_selling_price) { //promo savings
        $va_item['original_price'] = format_currency($v_list_price);
        $va_item['actual_price'] = $v_selling_price_dsp;
        $va_item['savings'] = format_currency($v_saved);
      } else if($v_orig_price > $v_actual_price && !empty($v_savings_story)) {
        $va_item['original_price'] = format_currency($v_orig_price);
        $va_item['actual_price'] = format_currency($v_act_price);
        $va_item['savings'] = $v_savings_story;
      } else {
        $va_item['actual_price'] = $v_selling_price_dsp;
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

      if(empty($v_promise_date)) {
        $v_promise_date = 'Not Available';
      }

      $va_item['promise_date'] = $v_promise_date;
      $va_item['promise_date_display'] = $v_promise_date;
      $va_item['source_type_code'] = $v_source_type_code;

	  if (isset($ATPresults[$v_inventory_item_id]["promisedDate"]) && $ATPresults[$v_inventory_item_id]["ResponseStatus"]=="VALID"){
	  	$va_item['promise_date'] = date("d-M-y",$ATPresults[$v_inventory_item_id]["promisedDate"]);
	  	$_SESSION["ATPPromisedDATEs"][$v_inventory_item_id]=$ATPresults[$v_inventory_item_id]["promisedDate"];
	  	$va_item["ATPmsg"]= $ATPresults[$v_inventory_item_id]["msg"];
	  }
      $va_item['description'] = $v_sku_desc;
	  $va_item['manuf_desc'] = $v_manuf_desc;
	  
      if(!empty($v_isp_warehouse_id)) {
        $pa_item_list[$v_isp_warehouse_id][] = $va_item;
      } else {
        $pa_item_list['web'][] = $va_item;
      }

      $pa_cm_tags[] = 'cmCreateShopAction5Tag("' . $va_item['style_number'] . '", "' . str_replace('"', '' ,$va_item['description']) . '", "' . $va_item['quantity'] . '", "' . $v_selling_price . '");';
    }
  }

  //get an array of items in the cart
  private function getIspItems(&$pa_store_list, &$pa_cm_tags) {
    global $mssql_db;

    $v_warehouse_id = $this->getWarehouseId();

    $pa_item_list = array();
    $pa_cm_tags = array();

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
              from  direct.dbo.gsi_cmn_order_lines_v v
              left join    direct.dbo.gsi_item_web_atp atp
              on atp.inventory_item_id = v.inventory_item_id
              and atp.organization_id  = '$v_out_warehouse_id'
              where order_number = '$this->v_order_number'
                and isnull(v.isp_line_type, 0) > 1
                and NOT style_number='GIFT BOX'
              order by ship_set_number, line_number";

    $v_result = mssql_query($v_sql);

    if (!$v_result){
      display_mssql_error($v_sql, "cart item query from shopping cart");
    }

    while($v_row = mssql_fetch_array($v_result)) {

      $va_item = array();

      $v_orig_line_ref     = $v_row['original_system_line_reference'];
      $v_inventory_item_id = $v_row['inventory_item_id'];
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

      $va_item['original_line_reference'] = $v_orig_line_ref;
      $va_item['line_number'] = $v_line_number;

      $va_item['quantity'] = $v_ordered_quantity;

      $this->getSkuInfo($v_segment1, $v_sku_desc, $v_min_order_qty, $v_category_id, $v_manuf_desc);

      //check availability
      if ($v_isp_line_type == 2) {
        $va_item['item_available'] = $this->checkItemAvailability($v_inventory_item_id, $va_item['quantity'], $v_isp_warehouse_id);
        //need to figure out new handling for this
      }

      if($v_inventory_item_id != 332375) {
        $this->getSkuFlags($v_inventory_item_id, $v_store_pickup_flag, $v_austin_to_store_flag);
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
      } else if($v_orig_price > $v_actual_price && !empty($v_savings_story)) {
        $va_item['original_price'] = format_currency($v_orig_price);
        $va_item['actual_price'] = format_currency($v_act_price);
        $va_item['savings'] = $v_savings_story;
      } else {
        $va_item['actual_price'] = $v_selling_price_dsp;
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

      if(empty($v_promise_date)) {
        $v_promise_date = 'Not Available';
      }

      $va_item['promise_date'] = $v_promise_date;
      $va_item['promise_date_display'] = $v_promise_date;
      $va_item['isp_line_type'] = $v_isp_line_type;

      if(!empty($v_isp_warehouse_id)) {
        $this->getStoreInfo($v_isp_warehouse_id, $pa_store_list[$v_isp_warehouse_id]['store_info']);
      }

      $va_item['description'] = $v_sku_desc;
	  $va_item['manuf_desc'] = $v_manuf_desc;
	  
      $pa_store_list[$v_isp_warehouse_id]['items'][] = $va_item;
      $pa_cm_tags[] = 'cmCreateShopAction5Tag("' . $va_item['style_number'] . '", "' . str_replace('"', '' ,$va_item['description']) . '", "' . $va_item['quantity'] . '", "' . $v_selling_price . '");';
    }

    $i_order = new Order($this->v_order_number);

    $v_isp_warehouse_id = '';

    foreach($pa_store_list as $v_isp_warehouse_id => $va_data) {
      if(!empty($v_isp_warehouse_id) && $v_isp_warehouse_id != 'web') {

        $this->getStoreInfo($v_isp_warehouse_id, $pa_store_list[$v_isp_warehouse_id]['store_info']);

        $i_order->calcISPWarehouseTotals($v_isp_warehouse_id, $pa_store_list[$v_isp_warehouse_id]['totals']['subtotal'], $pa_store_list[$v_isp_warehouse_id]['totals']['tax']);

        $pa_store_list[$v_isp_warehouse_id]['totals']['warehouse_total'] = format_currency((floatval($pa_store_list[$v_isp_warehouse_id]['totals']['subtotal']) + floatval($pa_store_list[$v_isp_warehouse_id]['totals']['tax'])));
        $pa_store_list[$v_isp_warehouse_id]['totals']['subtotal'] = format_currency($pa_store_list[$v_isp_warehouse_id]['totals']['subtotal']);
        $pa_store_list[$v_isp_warehouse_id]['totals']['tax'] = format_currency($pa_store_list[$v_isp_warehouse_id]['totals']['tax']);

      }
    }


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

  private function checkStyleExclusion($p_style_number) {

    global $mssql_db;

    //check for promo exclusions
    $v_source_code = $_SESSION['s_source_code'];

    if(!empty($v_source_code)) {
      $v_stmt = mssql_init("direct.dbo.gsi_check_style_exclusion");
      gsi_mssql_bind($v_stmt, "@p_source_code" , $v_source_code , "varchar", 30);
      gsi_mssql_bind($v_stmt, "@p_style_num"  , $p_style_number , "varchar", 50);
      gsi_mssql_bind($v_stmt, "@p_is_excluded" , $v_out_is_excluded , "bigint", 50 ,true );

      $v_result = mssql_execute($v_stmt);

      if(!$v_result) {
        display_mssql_error("direct..gsi_check_style_exclusion from sc_view");
      }

      mssql_free_statement($v_stmt);
      mssql_free_result ($v_result);

      if ($v_out_is_excluded == 1) {
        return true;
      }
    }

    return false;

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

  private function getStoreInfo($p_isp_warehouse_id, &$pa_store_info) {

    global $web_db;

    if(!empty($p_isp_warehouse_id)) {

      $v_sql = "select c.organization_code, concat('', e.display_store_name) as store_name
                     , c.address_line_1, c.address_line_2, c.address_line_3
                     , c.town_or_city, c.state, c.postal_code, c.country
                     , c.telephone_number_1
                from gsi_store_info as c, gsi_store_info_ext as e
                where c.organization_id = '$p_isp_warehouse_id'
                  and e.organization_id = '$p_isp_warehouse_id'";

      $v_result = mysqli_query ($web_db, $v_sql);

      while($va_row = mysqli_fetch_assoc($v_result)) {
        $pa_store_info['store_number'] = $va_row['organization_code'];
        $pa_store_info['store_name'] = $va_row['store_name'];
        $pa_store_info['address1'] = $va_row['address_line_1'];
        $pa_store_info['address2'] = $va_row['address_line_2'];
        $pa_store_info['address3'] = $va_row['address_line_3'];
        $pa_store_info['city'] = $va_row['town_or_city'];
        $pa_store_info['state'] = $va_row['state'];
        $pa_store_info['postal_code'] = $va_row['postal_code'];
        $pa_store_info['country'] = $va_row['country'];
        $pa_store_info['phone'] = $va_row['telephone_number_1'];
        $pa_store_info['directions_url'] = '';
      }

      // hardcoding this since it doesn't seem to be in the database
      if ($p_isp_warehouse_id == '173') {
        $pa_store_info = array(
          'store_number' => '888'
        , 'store_name'  => 'Toronto'
        , 'address1'      => ''
        , 'address2'      => '6150 Kennedy Rd Unit 9'
        , 'address3'      => ''
        , 'city'        => 'Mississauga'
        , 'state'               => 'ON'
        , 'postal_code'         => 'L5T 2J4'
        , 'country'             => 'CA'
        , 'phone'  => '905.696.8341'
        );
      }

      $pa_store_info['map_request'] = '';

      if(!empty($pa_store_info['address2'])) {
        $pa_store_info['map_request'] .= $pa_store_info['address2'];
      }
      if(!empty($va_store['address3'])) {
        $pa_store_info['map_request'] .= ',+' . $pa_store_info['address3'];
      }
      $pa_store_info['map_request'] .= ',+' . $pa_store_info['city'] . ',+' . $pa_store_info['state'] . ',+' . $pa_store_info['postal_code'];

      $pa_store_info['map_request'] = str_replace(' ', '+', $pa_store_info['map_request']);

      mysqli_free_result($v_result);

      $v_sql = "select day, open, close, note
                from gsi_web_store_hours hours
                   , gsi_web_store_groups member
                where ((start_date IS NULL and end_date IS NULL)
                   or (start_date IS NULL and end_date > NOW())
                   or (start_date <= NOW() and end_date IS NULL)
                   or (NOW() between start_date and end_date))
                   and approved_date <= NOW()
                   and member.record_parent = hours.record_name
                   and member.record_name = '" . $pa_store_info['store_number'] . "'
                order by start_date, hours_id";

      $v_result = mysqli_query($web_db, $v_sql);
      while ($va_row = mysqli_fetch_assoc($v_result)) {
        if(!empty($va_row['day']) || $va_row['day'] === '0') {
          $va_hour_list[$va_row['day']] = $va_row;
        }
      }

      $v_sql = "select distinct day, open, close, note
                from gsi_web_store_hours hours
                where ((start_date IS NULL and end_date IS NULL)
                   or (start_date IS NULL and end_date > NOW())
                   or (start_date < NOW() and end_date IS NULL)
                   or (NOW() between start_date and end_date)
                   or (NOW() = end_date))
                   and hours.record_name = '" . $pa_store_info['store_number'] . "'
                   and approved_date <= NOW()
                order by start_date, hours_id";

      $v_result = mysqli_query($web_db, $v_sql);
      while ($va_row = mysqli_fetch_assoc($v_result)) {
        if(!empty($va_row['day']) || $va_row['day'] === '0') {
          $va_hour_list[$row['day']] = $va_row;
        }
      }

      $va_day_list = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');

      $v_index = 0;
      foreach($va_hour_list as $v_day => $va_data) {
        if(!empty($va_data['open']) && !empty($va_data['close']) && !empty($va_day_list[$v_day])) {
          $va_open = explode(':', $va_data['open']);
          $va_close = explode(':', $va_data['close']);

          if($va_open[0] > 12) {
            $v_open_str = intval($va_open[0] - 12) . 'pm';
          } else if($va_open[0] == 12) {
            $v_open_str = intval($va_open[0]) . 'pm';
          } else if(!empty($va_open[0])) {
            $v_open_str = intval($va_open[0]) . 'am';
          } else {
            $v_open_str = '';
          }

          if($va_close[0] > 12) {
            $v_close_str = intval($va_close[0] - 12) . 'pm';
          } else if($va_close[0] == 12) {
            $v_close_str = inval($va_close[0]) . 'pm';
          } else if(!empty($va_close[0])) {
            $v_close_str = intval($va_close[0]) . 'am';
          } else {
            $v_close_str = '';
          }

          if($v_day > 0) {
            if($v_open_str == $va_store_hours[$v_index]['open'] && $v_close_str == $va_store_hours[$v_index]['close']) {
              $va_store_hours[$v_index]['last_day'] = $va_day_list[$v_day];
            } else {
              $v_index++;
              $va_store_hours[] = array('first_day' => $va_day_list[$v_day], 'last_day' => '', 'open' => $v_open_str, 'close' => $v_close_str);
            }
          } else {
            $va_store_hours[] = array('first_day' => $va_day_list[$v_day], 'last_day' => '', 'open' => $v_open_str, 'close' => $v_close_str);
          }
        }
      }

      $pa_store_info['hours'] = $va_store_hours;


    }

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
  
  private function updateQBDInfo() {
  	
  	$v_arr_QBD = get_active_QBD();
  	
  	if (!empty($v_arr_QBD['style'])) {
  		
  		global $mssql_db;

	    $v_stmt = mssql_init("direct.dbo.gsi_qbd_update_qbd_id_by_style");
	
	    gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 30);
	    gsi_mssql_bind($v_stmt, "@p_style_number", $v_arr_QBD['style'], 'varchar', 50);
	    gsi_mssql_bind($v_stmt, "@p_qbd_id", $v_arr_QBD['qbd_id'], 'varchar', 50);	    
	    	
	    $v_result = mssql_execute($v_stmt);
	
	    if(!$v_result) {
	      display_mssql_error("direct.dbo.gsi_qbd_update_qbd_id_by_style", "called from updateQBDInfo in CheckoutReview.php");
	    }
	
	    mssql_free_statement($v_stmt);
	    mssql_free_result($v_result);
  		
  	}
  	
  }

}
?>
