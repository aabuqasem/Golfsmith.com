<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here
require_once('Zend/View.php');
require_once('CheckoutPage.php');
require_once('CheckoutLogin.php');
require_once('Address.php');
require_once('Order.php');
require_once('EmployeeCredit.php');
require_once('ShoppingCart.php');
require_once('CheckoutReview.php');
require_once('PayPalCheckout.php');
require_once('PayPalApiCall.php');
include_once('QBD_functions.php');

class CheckoutSubmit extends CheckoutPage {

  //no constructor needed -- we're using the CheckoutPage constructor
 
  //display the page
  public function displayPage() {

    //we don't need to display this page independently -- it will always be after a successful order

  }

  public function displaySubmitConfirm($p_show_div = TRUE, $p_require_wire_transfer, $p_has_isp_sts, $p_request_web_account) {

    //check for submitted order here?

    $i_order = new Order($this->v_order_number);
    $i_address = new Address($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_bill_phone_id']);

    $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

    $z_view->show_div = $p_show_div;

    $z_view->customer_number = $_SESSION['s_customer_number'];
    $z_view->email = $_SESSION['s_user_name'];

    if(substr($this->v_order_number, 0, 1) == 'G') {
      $z_view->order_number = substr($this->v_order_number, 1);
    } else {
      $z_view->order_number = $this->v_order_number;
    }

    //empty out order number session variables here -- note that the customer will stay logged in
    $_SESSION['s_order_number'] = '';
    $_SESSION['s_us_order_number'] = '';
    $_SESSION['s_ca_order_number'] = '';
    $_SESSION['CartTotal'] = '$0.00';
    $_SESSION['s_source_code'] = '';
    $_SESSION['s_ship_method'] = '';
	$_SESSION['smethod'] = '';
	$_SESSION['s_paypal_token'] = '';
	$_SESSION['s_paypal_payerid'] = '';
    $_SESSION['s_paypal_order_id'] = '';
    $_SESSION['s_paypal_token_created'] = '';
    $_SESSION['s_using_paypal'] = '';
	

    $z_view->require_wire_transfer = $p_require_wire_transfer;

    $z_view->has_isp_sts = $p_has_isp_sts;

    $z_view->request_web_account = $p_request_web_account;


    $this->generatePartnerData($v_order_subtotal, $v_ping_total, $va_cm_data, $va_ga_data, $va_sales_channel_code);
    $i_order->retrieveSubmittedOrderTotals($v_sub_total, $v_tax_total, $v_ship_total);  
    
    $z_view->ga_data = $va_ga_data;
    $z_view->cm_data = $va_cm_data;
    $z_view->order_subtotal = format_currency($v_order_subtotal);
    $z_view->ship_total = $v_ship_total;
    $z_view->ga_tax_total = $v_tax_total;
    $z_view->ga_order_total = $v_sub_total + $v_tax_total + $v_ship_total; 
    $z_view->site_init = $this->i_site_init;
	
    $v_contains_only_ping = FALSE;
    if($v_ping_total > 0) {
      $v_non_ping_total = $v_order_subtotal - $v_ping_total;
      if($v_non_ping_total <= 0) {
        $v_contains_only_ping = TRUE;
      }
    } else {
      $v_non_ping_total = $v_order_subtotal;
    }

    $z_view->non_ping_total = format_currency($v_non_ping_total);
    $z_view->contains_only_ping = $v_contains_only_ping;

    $z_view->partner_code = $_COOKIE['prtcode'];

    $i_address->getAddressFields($v_is_international, $va_address);

    $z_view->bill_city = $va_address['city'];
    $z_view->bill_state = $va_address['state'];
    $z_view->bill_postal_code = $va_address['postal_code'];
	$z_view->sales_channel_code = $va_sales_channel_code;
	
    //check for existing password - if none, allow password creation

    return $z_view->render('checkout_submit_confirm.phtml');

  }

  //generates data for confirm page for CoreMetrics and Channel Intelligence
  public function generatePartnerData(&$p_order_subtotal, &$p_ping_total, &$pa_cm_data, &$ga_newarr, &$p_sales_channel_code) {

    global $mssql_db;
    global $web_db;

    $pa_ga_data = array();
    $pa_cm_data = array();

    $p_order_subtotal = 0;

    $v_sql = "select
                v.original_system_line_reference
              , v.inventory_item_id
              , v.ordered_quantity
              , v.selling_price
              , v.style_number
              , v.style_attr1
              , v.style_attr2
              , v.style_attr3
              , v.style_attr4
              , v.sku_desc
              , v.sku_display
              , h.sales_channel_code
              , b.description + ' ' + s.description style_name
              , c.description style_category
              , iia.sku
              from  orders.dbo.gsi_cmn_order_lines_v v
              , orders.dbo.gsi_headers_interface_all h
              , r12pricing..gsi_item_info_all iia
              , r12pricing..gsi_style_info_all s
              , r12pricing..gsi_brands b
              , r12pricing..gsi_categories c  
              where v.order_number = '$this->v_order_number'
                and v.order_number = h.original_system_reference 
                and v.inventory_item_id = iia.inventory_item_id
                and iia.segment1 = v.style_number
                and v.style_number = s.style_number
				and s.brand_id = b.brand_id
                and NOT v.style_number='GIFT BOX'
                and c.category_set_name = 'GSI WEB CATALOG'
                and c.category_id = s.web_category_id
              order by ship_set_number, line_number";

    $v_result = mssql_query($v_sql);
    
    if(!$v_result) {
      display_mssql_error("generatePartnerData", "called from generatePartnerData in CheckoutSubmit.php");
    }

    while($va_row = mssql_fetch_array($v_result)) {
	  
      $p_sales_channel_code = $va_row['sales_channel_code'];
	  
      $v_sku = $va_row['style_number'] . '-' . $va_row['style_attr1'] . '-' . $va_row['style_attr2'] . '-' . $va_row['style_attr3'] . '-' . $va_row['style_attr4'];

      //check for ping
      // I removed the next query. I don't see any usage of itanywhere except some pixel link and it throw an error  
/*       $v_sql2 = "select manufacturer_desc from gsi_cmn_style_data where style_number='$style'";

      $v_result2 = mysqli_query($web_db, $v_sql2);
      display_mysqli_error($v_sql2);

      if($va_row2 = mysqli_fetch_array($v_result2)) {
        $v_manufacturer_desc = $va_row2['manufacturer_desc'];
        if(strtoupper($v_manufacturer_desc) == 'PING') {
          $p_ping_total = floatval($v_ping_total + floatval($va_row['ordered_quantity'] * $va_row['selling_price']));
        }
      } */

      if($va_row['selling_price'] > 0) {
        $p_order_subtotal = floatval($p_order_subtotal + floatval($va_row['ordered_quantity'] * $va_row['selling_price'])); 
      }

      $pa_cm_data[] = array('style_number' => $va_row['style_number'], 'sku_desc' => str_replace('"', '' ,$va_row['sku_desc']), 'ordered_quantity' => $va_row['ordered_quantity'], 'selling_price' => $va_row['selling_price']);
      
      $pa_ga_data[] = array('sku' => $va_row['sku'], 'style_number' => $va_row['style_number'], 'style_name' => str_replace('\'', '' ,$va_row['style_number'] . ' - ' . $va_row['style_name']), 'style_category' => str_replace('\'', '' ,$va_row['style_category']), 'ordered_quantity' => $va_row['ordered_quantity'], 'selling_price' => $va_row['selling_price']);
        }
        //group google analytics by sku
        foreach ($pa_ga_data as $ga_key => $ga_row) {
    		$va_ga_sku[$ga_key]  = $ga_row['sku'];
		}

		// Sort the data by sku
		array_multisort($va_ga_sku, SORT_ASC, $pa_ga_data);

		$ga_newarr = array();
		$ga_reverse_map = array();
		$ga_new_idx = -1;
		foreach($pa_ga_data as $ga_idx => $ga_entry) {
		
		    if (!isset($ga_reverse_map[$ga_entry['sku']])) {
		       $ga_reverse_map[$ga_entry['sku']] = $ga_new_idx;
		       $ga_new_idx += 1;
		
		    }
		
		    $ga_newarr[$ga_new_idx]['sku'] = $ga_entry['sku'];
		    $ga_newarr[$ga_new_idx]['style_name'] = $ga_entry['style_name'];
		    $ga_newarr[$ga_new_idx]['style_category'] = $ga_entry['style_category'];
		    $ga_newarr[$ga_new_idx]['selling_price'] = $ga_entry['selling_price'];
		    $ga_newarr[$ga_new_idx]['ordered_quantity'] += $ga_entry['ordered_quantity'];
		
		}
        

  }

  public function updateAccount($p_password, $p_password_confirm) {

    $i_user_registration = new UserRegistration();

    $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

    $v_customer_id = $_SESSION['s_customer_id'];
    $v_email = $_SESSION['s_user_name'];

    if(empty($p_password)) {
      $v_return_status = 'Error: Please enter a password to create an account.';
    } else if($p_password != $p_password_confirm) {
      $v_return_status = 'Error: Password must match the value entered in the "Password Confirm" field.';
    } else {
      $v_return_status = $i_user_registration->updateAccount($v_customer_id, $v_email, crypt($p_password, 'GS'), $p_password);
      if(!empty($v_return_status)) {
        $v_return_status = 'Error: ' . $v_return_status;
      }
    }

    if(empty($v_return_status)) {
      $_SESSION['s_is_guest_checkout'] = 'N';
      $v_return_status = $z_view->render('checkout_submit_create_account.phtml');
    }

    return $v_return_status;

  }

  public function processOrder(&$p_paypal_error_message, &$p_err_msg = null) {
    // the next for check the time gap between place orders
  	// to prevent repeation of placing orders 2 seconds is the bext 
  	if ($_SESSION["ProcessPaymentStart"] != "" && (time() - $_SESSION["ProcessPaymentStart"] ) <3) {
  		exit();
  	}else{
  		$_SESSION["ProcessPaymentStart"] = time();
  	}
  	
    $i_order = new Order($this->v_order_number);
    
    $v_has_qbd = $i_order->hasQBDStyle();

    if ($v_has_qbd) {
    	$_SESSION['s_do_reprice'] = 'Y' ;
  		$_SESSION['s_do_totals'] = 'Y';	
    }
    
    $i_order->repriceOrder();
    $v_return = $i_order->getPaymentInfo($v_card_type, $v_card_number, $v_expiry, $v_amt_paid);

    //adjust gift card payments
    $i_gc_payment = new GiftCardPayment();
    $i_gc_payment->adjustGiftCardPayments();
    $i_cc_payment = new CreditCardPayment();
    $i_payment = new Payment();

    $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_return_status);

    $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total;
    $v_net_total = round($v_order_total,2) - round($v_gc_total,2);
    
    $v_isp_dummy_pmt_status = $i_order->findISPDummyPayment();

    if($v_order_total == 0 && $v_isp_dummy_pmt_status == 'NOT PAID') {
      $v_net_total = 1;
    }

    if($_SESSION['site'] == 'CA' && $_SESSION['smethod'] == 'I') {
      $v_net_total = 1;
    }

    $v_require_payment = FALSE;

    if($v_net_total > 0) {

      $v_payment_method = $i_order->getPaymentMethod($_SESSION['s_ship_address_id'], $v_net_total);

      //check if user is actually paying by paypal
      if($_SESSION['s_using_paypal'] == 'Y' && !empty($_SESSION['s_paypal_token']) && !empty($_SESSION['s_paypal_payerid'])) {
        $v_payment_method = 'PAYPAL';
      }

      if($v_payment_method == 'WIRETR') {

        //remove all gift cards
        $i_payment->void_payments('Y');

        //DB proc already voids existing wire transfers and credit card payments
        $i_payment->add_payment($v_net_total, '', '', '', '');

      } else if($v_payment_method == 'PAYPAL') {
        if(!empty($v_card_type)) {
          //void non-GC non-PayPal payments
          $i_payment->void_payments('N');
        }
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
              $i_cc_payment->addPaymentFromSavedCard($v_net_total, $v_token_id, $v_masked_cc, $v_cc_type, $v_expiry, $v_days_auth_valid);
            } else {
              $v_require_payment = TRUE;
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

    
    //if($v_require_payment !== TRUE && $v_net_total > 0) {
    if($v_require_payment !== TRUE ) {
      //if using PayPal, we need to send the order call
      if($_SESSION['s_using_paypal'] == 'Y' && !empty($_SESSION['s_paypal_token']) && !empty($_SESSION['s_paypal_payerid']) && ($v_net_total > 0)) {

        $i_paypal_checkout = new PayPalCheckout();
        $i_paypal_checkout->setErrorHandler();
        $i_api_call = new PayPalApiCall();
        $v_status = $i_payment->add_paypal_payment($v_net_total);

        //if we already have a paypal order ID, skip the DoExpressCheckoutPayment step; it's already been done
        if(!empty($_SESSION['s_paypal_order_id'])) {
          $v_paypal_order = $_SESSION['s_paypal_order_id'];
          $v_success = TRUE;
        } else {
          $i_api_call->generate_createorder_xml($this->v_order_number, $_SESSION['s_paypal_token'], $_SESSION['s_paypal_payerid']);

          $v_paypal_order = $i_api_call->process_createorder($v_success, $paypal_error_code);

        }

        //restore the original (non-paypal) error handler
        $i_paypal_checkout->restoreErrorHandler();

        if($v_success !== TRUE) {

          //void the payment, since it's not valid
          //(don't delete it; for this case we want to store the error code that PayPal returned)
          $i_payment->void_paypal_payments($paypal_error_code);

          //clear everything but the token (which we might need) out of the "PayPal" section of $_SESSION
          $_SESSION['s_using_paypal'] = '';
          $_SESSION['s_paypal_payerid'] = '';
 
          $v_require_payment = TRUE;

          $v_paypal_return_status = 'PayPal authorization failed.  Please return to the shopping cart and choose a new payment method.';

        } else {  //order succeeded

          //store order ID in session, because we won't get another chance to establish an order
          $_SESSION['s_paypal_order_id'] = $v_paypal_order;

          //now we need to send the DoAuthorization request
          $i_api_call->generate_authorization_xml($_SESSION['s_order_number'], $v_paypal_order);

          $v_paypal_authorization = $i_api_call->process_authorization($v_success, $paypal_error_code);

          if($v_success === TRUE) {
            $i_payment->remove_paypal_payments();
            $i_payment->add_paypal_payment($v_net_total, $v_paypal_authorization);

            // the usual order processing
            $v_return_status = $i_order->processOrder();

            //store the order ID over the token value
            $i_order->updateHeaderPurchaseOrderNum($v_paypal_order);
          } else {
            $i_payment->void_paypal_payments($paypal_error_code);
            $_SESSION['s_using_paypal'] = '';
            $_SESSION['s_paypal_payerid'] = '';

            $v_paypal_return_status = 'Paypal authorization failed.  Please return to the shopping cart and choose a new payment method.';
          }
        }
      } else {  //not a PayPal order, so proceed as usual
        $v_return_status = $i_order->processOrder();
      }

      
      if (!empty($v_return_status)) {      	
        $v_require_payment = TRUE;
        $p_err_msg = $v_return_status;
      } else if(!empty($v_paypal_return_status)) {

        $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
        $z_view->paypal_error_message = $v_paypal_return_status;
        $p_paypal_error_message = $z_view->render('paypal_submit_error.phtml');
        $v_require_payment = TRUE;

      } //else leave payment requirement as-is      
      
    }
    
    //this will be a generic error message in case it is unset         	
    if ($v_require_payment === TRUE)
    {
      	if ( (empty($p_err_msg)) || !(isset($p_err_msg)) || (strlen(trim($p_err_msg)) <= 0 ) ) {
      		$p_err_msg = 'Error in processing order.  Please try again.';
      	} 
    }

    return $v_require_payment;

  }


}
?>
