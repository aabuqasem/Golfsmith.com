<?php 
//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here
require_once('Zend/View.php');
require_once('CheckoutPage.php');
require_once('CheckoutLogin.php');
require_once('Address.php');
require_once('Order.php');
require_once( FPATH.'countries.inc');
require_once('UserRegistration.php');

class CheckoutPayment extends CheckoutPage {

  //no constructor needed -- we're using the CheckoutPage constructor
 
  //display the cart page
  public function displayPage() {

    $this->i_site_init->loadMain();

    echo $this->displayHeader('payment', 2);
    echo $this->displayPayment(TRUE);
    echo $this->displayFooter();
    // remarketing vars
	$g_data["pageType"]="other";
    $g_data["prodid"]="";
    $g_data["totalvalue"]="";
    $this->i_site_init->loadFooter($g_data);
  }

  public function displayPayment($p_show_div = TRUE) {

    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only show login page if not already logged in
    if(empty($v_customer_number)) {

      $i_login = new CheckoutLogin();
      return $i_login->displayLogin($p_show_div);

    } else {
     
    
      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

      $z_view->show_div = $p_show_div;

      $v_ship_address_id = $_SESSION['s_ship_address_id'];

      $i_address = new Address($_SESSION['s_customer_id'], $v_ship_address_id, $_SESSION['s_ship_contact_id'], $_SESSION['s_ship_phone_id']);
      $i_address->getAddressFields($v_ship_is_international, $va_ship_address);
      $i_address->setAll($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_bill_phone_id']);
      $i_address->getAddressFields($v_bill_is_international, $va_bill_address);

      $z_view->require_canadian_shipping = FALSE;
      $z_view->require_canadian_billing = FALSE;
      $z_view->run_fedex_check = FALSE;
      if($_SESSION['site'] == 'CA' && $va_ship_address['country'] != 'CA') {
        $z_view->require_canadian_shipping = TRUE;
      } else if($_SESSION['site'] == 'CA' && $va_bill_address['country'] != 'CA') {
        $z_view->require_canadian_billing = TRUE;
      } else if($va_ship_address['country'] == 'US' && $_SESSION['skip_default_fedex_check'] !== TRUE) {
        $z_view->run_fedex_check = TRUE;
      }

      //check for dropship restrictions here
      $i_order = new Order($this->v_order_number);
      $i_order->getDropshipRestrictions($v_dropship_restrictions);

      $va_dropship_restrictions = explode(',', str_replace('"', '', $v_dropship_restrictions));

      if($va_dropship_restrictions[0] || !$_SESSION['s_ship_method']) {
        $_SESSION['s_ship_method'] = 'G';
        $z_view->ship_method_code = 'G';
        $z_view->has_ship_restrictions = TRUE;
      } else {
        $z_view->ship_method_code = $_SESSION['s_ship_method'];
        $z_view->has_ship_restrictions = FALSE;
      }
      
      //link addresses, update order with ship method, etc.
      $v_update_return_status = $i_order->updateOrder($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_ship_address_id'], $_SESSION['s_ship_contact_id'], $z_view->ship_method_code);
      
      
      $_SESSION['s_do_reprice']   = 'Y' ;
  	  $i_order = new Order($this->v_order_number);    
      $i_order->repriceOrder();
      
      if($_SESSION['site'] == 'US') {
        $this->updateIspLineAddresses();
      }
      
      $z_view->ship_is_international = $v_ship_is_international;
      $z_view->ship_address = $va_ship_address;
      $z_view->bill_is_international = $v_bill_is_international;
      $z_view->bill_address = $va_bill_address;

      //check country restrictions
      $z_view->intl_restrictions = FALSE;
      if(!empty($_SESSION['s_customer_id'])) {
        if($this->checkCountryRestrictedItem() == 1 || $this->checkSourcedItemShippedInt() == 1) {
          $z_view->intl_restrictions = TRUE;
        }
      }

      //get order total here
      $_SESSION['s_do_totals'] = 'Y';
      $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_return_status);
      $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total ;
      //$z_view->promotion_savings_total = $i_order->getTotalPromotionSavings();
      $v_net_total   = round(($v_order_total - $v_gc_total), 2);
    
      //$v_isp_totals_string = $i_order->getISPTotalsString();
      //$i_order->extractISPTotals($v_isp_totals_string, $v_isp_total, $v_isp_sub_total, $v_isp_tax);
      
      
      //$z_view->merchandise_total = $v_sub_total + $v_isp_sub_total + $z_view->promotion_savings_total;
	  //$z_view->giftcard_total = $v_gc_total;	  
	  //$z_view->shipping_total = $v_ship_total;
	  //$z_view->sales_tax_total = $v_tax_total + $v_isp_tax;
	  //$z_view->amount_due_total = $z_view->merchandise_total + $z_view->shipping_total + $z_view->sales_tax_total - $z_view->giftcard_total;
	  
	 // $_SESSION['CartTotal'] = format_currency($z_view->amount_due_total);           
	  
      if ($v_net_total == '0'){
        $v_isp_dummy_pmt_status = $i_order->findISPDummyPayment();

        if ($v_isp_dummy_pmt_status == 'NOT PAID') {
          $v_net_total = 1;
        }
        if ($_SESSION['site'] == 'CA' && $_SESSION[s_ship_method] == 'I') {
          $v_net_total = 1;
        }
      }

      //need to get full order total for the top of the page here, too

      if($v_gc_total > 0 && $v_net_total != 0) {
        $i_order->adjustGiftCardPayments();
      }
      
      //get order total here
      $_SESSION['s_do_totals'] = 'Y';
      $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_return_status);
      $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total ;
      $z_view->promotion_savings_total = $i_order->getTotalPromotionSavings();
      $v_net_total   = round(($v_order_total - $v_gc_total), 2);
    
      $v_isp_totals_string = $i_order->getISPTotalsString();
      $i_order->extractISPTotals($v_isp_totals_string, $v_isp_total, $v_isp_sub_total, $v_isp_tax);
      
      
      $z_view->merchandise_total = $v_sub_total + $v_isp_sub_total + $z_view->promotion_savings_total;
	  $z_view->giftcard_total = $v_gc_total;
	  $z_view->shipping_total = $v_ship_total;
	  $z_view->sales_tax_total = $v_tax_total + $v_isp_tax;

	  $z_view->amount_due_total = $z_view->merchandise_total + $z_view->shipping_total + $z_view->sales_tax_total - $z_view->giftcard_total - $z_view->promotion_savings_total;
	   
	  $_SESSION['CartTotal'] = format_currency($z_view->amount_due_total);           
      

      //find out whether wire transfer is required here
      $v_payment_method = $i_order->getPaymentMethod($v_ship_address_id, $v_net_total);

      if($v_payment_method == 'WIRETR') {
        $z_view->require_wire_transfer = TRUE;
      } else {
        $z_view->require_wire_transfer = FALSE;
      }

      if($_SESSION['s_using_paypal'] == 'Y' && !empty($_SESSION['s_paypal_token']) && !empty($_SESSION['s_paypal_payerid'])) {
        $z_view->show_paypal = TRUE;
      } else {
        $z_view->show_paypal = FALSE;
      }

      $this->getShippingOptions($z_view->has_ship_restrictions, $va_shipping_options);
      $z_view->shipping_options = $va_shipping_options;
	  $z_view->atp_date = $this->getATP_DATE($this->v_order_number);
	  
	  $z_view->isISPSTSOnly = $i_order->isISPSTSOnly();
	  
      return $z_view->render("checkout_payment.phtml");

    }
  }

  /*
   * get Item ATP_DATE Author By Chantrea 
   */
  public function getATP_DATE($v_order){
        global $mssql_db;
        $sql = "select promise_date atp_date from direct.dbo.gsi_cmn_order_lines_v where order_number = '".$v_order."' and promise_date >= dateadd(d, 2, getdate())";
        $result = mssql_query($sql);
        $array = array();
        while($row=mssql_fetch_array($result)){
                $array[] = $row['atp_date'];
        }
        return $array;

  }
  public function updatePaymentCharges() {

  	$i_order = new Order($this->v_order_number);   
    $_SESSION['s_do_reprice']   = 'Y' ;  	    
    $i_order->repriceOrder();
    $_SESSION['s_do_totals'] = 'Y';
           
    $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));
    
  	//get order total here
      $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_return_status);
      $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total ;
      $z_view->promotion_savings_total = $i_order->getTotalPromotionSavings();
      $v_net_total   = round(($v_order_total - $v_gc_total), 2);
    
      $v_isp_totals_string = $i_order->getISPTotalsString();
      $i_order->extractISPTotals($v_isp_totals_string, $v_isp_total, $v_isp_sub_total, $v_isp_tax);
      
      
      $z_view->merchandise_total = $v_sub_total + $v_isp_sub_total + $z_view->promotion_savings_total;
	  $z_view->giftcard_total = $v_gc_total;	  
	  $z_view->shipping_total = $v_ship_total;
	  $z_view->sales_tax_total = $v_tax_total + $v_isp_tax;
	  $z_view->amount_due_total = $z_view->merchandise_total + $z_view->shipping_total + $z_view->sales_tax_total - $z_view->giftcard_total - $z_view->promotion_savings_total;
	  
	  $_SESSION['CartTotal'] = format_currency($z_view->amount_due_total); 
    
	
	return $z_view->render("checkout_payment_charges.phtml");
	  
  }
  
  
  public function updateShippingCharges() {

  	$i_order = new Order($this->v_order_number);   
	$z_view = new Zend_view(array('scriptPath' => VIEW_PATH));
    
  	$i_order->getDropshipRestrictions($v_dropship_restrictions);

    $va_dropship_restrictions = explode(',', str_replace('"', '', $v_dropship_restrictions));

    if($va_dropship_restrictions[0] || !$_SESSION['s_ship_method']) {
      $_SESSION['s_ship_method'] = 'G';
      $z_view->ship_method_code = 'G';
      $z_view->has_ship_restrictions = TRUE;
    } else {
      $z_view->ship_method_code = $_SESSION['s_ship_method'];
      $z_view->has_ship_restrictions = FALSE;
    }
    
    $this->getShippingOptions($z_view->has_ship_restrictions, $va_shipping_options);
    $z_view->shipping_options = $va_shipping_options;
	
	return $z_view->render("checkout_payment_shipping_charges.phtml");
	  
  }
  public function displayGiftCardList() {

    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));

    $i_order = new Order($this->v_order_number);

    $v_status = $i_order->getPreviousGiftcards($va_giftcards);

    $z_view->previous_giftcards = $va_giftcards;

    return $z_view->render("checkout_payment_giftcards.phtml");

  }

  public function displayAddressBook($p_show_div, $p_address_type, $p_containing_page, $p_require_canadian_address) {

    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));

    $z_view->show_div = $p_show_div;

    $z_view->address_type = $p_address_type;
    $z_view->containing_page = $p_containing_page;
    $z_view->require_canadian_address = $p_require_canadian_address;

    $i_address= new Address($_SESSION['s_customer_id']);
    $i_address->getAddressBookAddresses($p_address_type, $va_addresses);

    $z_view->addresses = $va_addresses;

    if($z_view->address_type == 'SHIP_TO') {
      $z_view->address_prefix = 'ship';
    } else {
      $z_view->address_prefix = 'bill';
    }

    return $z_view->render("address_book.phtml");

  }

  public function displayAddressBookForm($p_show_div, $p_address_id, $p_contact_id, $p_phone_id, $p_address_type, $p_containing_page) {

    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));

    $z_view->show_div = $p_show_div;
    $z_view->address_id = $p_address_id;

    if(!empty($p_address_id)) {
      $i_address = new Address($_SESSION['s_customer_id'], $p_address_id, $p_contact_id, $p_phone_id);
      $i_address->getAddressFields($v_is_international, $va_address);
      $z_view->address = $va_address;
    }

    $z_view->address_type = $p_address_type;
    $z_view->containing_page = $p_containing_page;

    $z_view->state_options_path = PHP_INC_PATH . 'state_bill_dropdown.html';
    $z_view->state_options_ca_path = PHP_INC_PATH . 'state_bill_dropdown_ca.html'; 
	$i_countries = new countries;   
    $z_view->intl_countries=$i_countries->fill_countries();
    $z_view->country_options_path = PHP_INC_PATH . 'country_bill_dropdown.html';

    return $z_view->render("checkout_payment_address_form.phtml");

  }

  public function setAddressAsPrimary($p_address_id, $p_address_type, $p_containing_page) {

    $i_address = new Address($_SESSION['s_customer_id'], $p_address_id, '', '');
    $i_address->setAddressAsPrimary($p_address_type);

    return $this->displayAddressBook(TRUE, $p_address_type, $p_containing_page);

  }

  //pass in the whole request, because there are a lot of parameters
  public function updateAddress(&$i_request) {

     //need to collect containing page here

     $i_address = new Address($_SESSION['s_customer_id']);

     $v_containing_page = strip_tags($i_request->getParam('containing_page'));
     $v_address_id = strip_tags($i_request->getParam('address_id'));
     $v_address_type = strip_tags($i_request->getParam('address_type'));
     $v_contact_id = strip_tags($i_request->getParam('contact_id'));
     $v_phone_id = strip_tags($i_request->getParam('phone_id'));
     $v_first_name = strip_tags($i_request->getParam('fname_ship_input'));
     $v_last_name = strip_tags($i_request->getParam('lname_ship_input'));
     $v_address1 = strip_tags($i_request->getParam('add1_ship_input'));
     $v_address2 = strip_tags($i_request->getParam('add2_ship_input'));
     $v_city = strip_tags($i_request->getParam('city_ship_input'));
     $v_state = strip_tags($i_request->getParam('state_ship_input'));
     $v_postal_code = strip_tags($i_request->getParam('pcode_ship_input'));
     $v_country = strip_tags($i_request->getParam('country_ship_select'));
     $v_phone = preg_replace('/[^0-9]/', '', strip_tags($i_request->getParam('phone_ship_input')));

     if($v_country == 'US' || $v_country == 'CA') {
       $v_area_code = substr($v_phone, 0, 3);
       $v_phone = substr($v_phone, 3);
     } else {
       $v_area_code = '';
     }

     if(!empty($v_address_id)) { //update

       $v_return = $i_address->updateAddress($v_first_name, $v_last_name, $v_address1, $v_address2, $v_city, $v_state, $v_postal_code, $v_country, $v_address_type, $v_area_code, $v_phone, '', $v_contact_id, $v_address_id, $v_phone_id);

     } else { //insert

       $v_return = $i_address->insertAddress($v_first_name, $v_last_name, $v_address1, $v_address2, $v_city, $v_state, $v_postal_code, $v_country, $v_address_type, $v_area_code, $v_phone, $v_contact_id, $v_address_id, $v_phone_id);

     }

     //set session vars so that they're correct for the next order update
     if(empty($v_return)) {
       if($v_address_type == 'SHIP_TO') {
         $_SESSION['s_ship_address_id'] = $v_address_id;
         $_SESSION['s_ship_contact_id'] = $v_contact_id;
         $_SESSION['s_ship_phone_id'] = $v_phone_id;
       } else {
         $_SESSION['s_bill_address_id'] = $v_address_id;
         $_SESSION['s_bill_contact_id'] = $v_contact_id;
         $_SESSION['s_bill_phone_id'] = $v_phone_id;
       }
     }

     return $v_return;

  }
  
  // Handle the information we get back from MasterPass for checkout
  public function saveMPCheckoutInfo($info, $mpLongAccess)
  {
      $userRegistration = new UserRegistration();
      
      // Split names
      $shipNames = explode(' ', preg_replace('/\s+/',' ',$info->ShippingAddress->RecipientName));
      $billNames = explode(' ', preg_replace('/\s+/',' ',$info->Card->CardHolderName));
      $shipFirstName = $shipNames[0];
      $shipLastName = count($shipNames) > 1 ? $shipNames[1] : $shipNames[0];
      $billFirstName = $billNames[0];
      $billLastName = count($billNames) > 1 ? $billNames[1] : $billNames[0];
      
      // Get the state
      $billStateArr = explode("-",  $info->Card->BillingAddress->CountrySubdivision);
      $billState = count($billStateArr) == 2 ? $billStateArr[1] : $billStateArr[0];
      
      // Get the state
      $shipStateArr = explode("-",  $info->ShippingAddress->CountrySubdivision);
      $shipState = count($shipStateArr) == 2 ? $shipStateArr[1] : $shipStateArr[0];
      
      // Determine length of area code in case of country code
      $offset = strpos($info->Contact->PhoneNumber, '-') ? strpos($info->Contact->PhoneNumber, '-') + 1 : 0;
      
      // Get the area code and phone number
      $areaCode = substr($info->Contact->PhoneNumber, 0, 3 + $offset);
      $phoneNumber = substr($info->Contact->PhoneNumber, 3 + $offset);
      
      $userRegistration->verifyCustomer((string) $info->Contact->EmailAddress, null, '', $shipFirstName, $shipLastName, html_entity_decode( htmlentities( $info->ShippingAddress->Line1, ENT_COMPAT, 'UTF-8')), (string) $info->ShippingAddress->Line2, 
                                            (string) $info->ShippingAddress->City, $shipState, (string) $info->ShippingAddress->PostalCode, (string) $info->ShippingAddress->Country, $billFirstName, $billLastName, 
                                            (string) $info->Card->BillingAddress->Line1, (string) $info->Card->BillingAddress->Line2, (string) $info->Card->BillingAddress->City, $billState, 
                                            (string) $info->Card->BillingAddress->PostalCode, (string) $info->Card->BillingAddress->Country, $areaCode, $phoneNumber, '', '', '', 
                                            $addressId, $contactId, $customerId, $shipContactId, $phoneId, $shipPhoneId, $returnStatus, $shipReturnStatus, $billReturnStatus, 
                                            $shipSiteUseId, $billSiteUseId, null, $mpLongAccess);
      
      // Set close event cookie
      setcookie("CloseEvent", "CloseEvent", 0, "/",$_SERVER['HTTP_HOST'],true,1);
      
      // Save the payment info
      //$this->addCreditCardPayment(strtoupper($info->Card->BrandName), $info->Card->AccountNumber->__toString(), $info->Card->ExpiryMonth->__toString(), $info->Card->ExpiryYear->__toString());
      
      // Set this as a master pass transaction
      $_SESSION["mpTransaction"] = TRUE;
      
      // Apply the MasterPass Promo if there is one
      if($_SESSION["mpPromoCode"])
      {
          $this->applyPromoCode($_SESSION["mpPromoCode"]);
      }
  }

  public function deleteAddress($p_address_id) {

    $i_address = new Address();
    $i_address->deleteAddress($p_address_id);

  }

  public function setSessionAddressInfo($p_address_id, $p_contact_id, $p_phone_id, $p_address_type) {

    if($p_address_type == 'SHIP_TO') {
      $v_address_type_name = 'ship';
    } else {
      $v_address_type_name = 'bill';
    }

    $_SESSION['s_' . $v_address_type_name . '_address_id'] = $p_address_id;
    $_SESSION['s_' . $v_address_type_name . '_contact_id'] = $p_contact_id;
    $_SESSION['s_' . $v_address_type_name . '_phone_id'] = $p_phone_id;

  }

  public function checkPayments(&$i_request) {

    //promo code first, then gift card, then payments
/*  remove this block of code.. user should hit apply button if they want to apply GC or Promo Code
    $v_promo_code = strip_tags($i_request->getParam('cashCard_number'));

    if(!empty($v_promo_code)) {
      $v_status = $this->applyPromoCode($v_promo_code);

      if($v_status == 'Your promotion code has been accepted.') {
        $v_status = '';
      }

      if(!empty($v_status)) {
        return 'promocode: ' . $v_status;
      }
    }

    $i_gc_payment = new GiftCardPayment();
    $i_gc_payment->adjustGiftCardPayments();

    $v_gc_num = strip_tags($i_request->getParam('giftCard_number_input'));
    $v_gc_pin = strip_tags($i_request->getParam('giftCard_pin_input'));

    if(!empty($v_gc_num)) {
      $v_status = $this->addGiftCardPayment($v_gc_num, $v_gc_pin);

      if($v_status == 'payment' || $v_status == 'review') {
        $v_status = '';
      }

      if(!empty($v_status)) {
        return 'giftcard: ' . $v_status;
      }
    }
*/
    //check totals to make sure we even need a payment
    $i_order = new Order($this->v_order_number);
    // If it's a master pass transaction and they're editing the payment (no hidden field mpTransaction posted) it's no longer a master pass transaction
    if($_SESSION["mpTransaction"] && $i_request->getParam("mpTransaction") != 1)
    {
        $i_order->clearMPSessionInfo();
    }
    $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_status);
    $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total ;
    $v_net_total   = round(($v_order_total - $v_gc_total), 2) ;

    $v_isp_dummy_pmt_status = $i_order->findISPDummyPayment();

    if($v_order_total == 0 && ($v_isp_dummy_pmt_status == 'NOT PAID' || $v_isp_dummy_pmt_status == 'PAID')) {
      $v_net_total = 1;
    }
    
    if($_SESSION['site'] == 'CA' && $_SESSION['smethod'] == 'I') {
      $v_net_total = 1;
    }
    
    if($v_net_total > 0) {

      $v_require_wire_transfer = strip_tags($i_request->getParam('require_wire_transfer'));

      if($v_require_wire_transfer == 'Y') {

        $v_status = $this->addWireTransferPayment();

      } else {

        $v_payment_method = strip_tags($i_request->getParam('ccType'));
        $v_cc_num = strip_tags($i_request->getParam('cc_number_input'));
        $v_expiry_month = strip_tags($i_request->getParam('cc_MM_input'));
        $v_expiry_year = strip_tags($i_request->getParam('cc_YY_input'));
        $v_save_cc_info = strip_tags($i_request->getParam('save_cc'));
        $v_deferment = strip_tags($i_request->getParam('terms_select'));
        $v_cc_cvv = strip_tags($i_request->getParam('cc_cvv'));
        
        if($_SESSION['s_using_paypal'] != 'Y' || empty($_SESSION['s_paypal_token']) || empty($_SESSION['s_paypal_payerid'])) {
          if($v_payment_method == 'undefined' && !empty($v_deferment) && empty($v_expiry_month) && empty($v_expiry_year)) {
            $v_payment_method = 'golfsmith_cc';
          }
//  I added here $v_net_total
          $v_status = $this->addCreditCardPayment($v_payment_method, $v_cc_num, $v_expiry_month, $v_expiry_year, $v_deferment, $v_save_cc_info,$v_cc_cvv, $v_net_total);
        }
      }
    }

    if(!empty($v_status)) {
      return 'payment: ' . $v_status;
    } else {
      return $v_status;
    }

  }

  public function hasPayments() {

    $i_order = new Order($this->v_order_number);

    $v_show_review = $i_order->hasPayments();

    //we're ultimately returning to JavaScript, so return a string just in case
    if($v_show_review === TRUE) {
      $v_show_review = 'true';
    } else {
      //$v_show_review = 'false';
    }

    return $v_show_review;

  }

  public function addGiftCardPayment($p_gc_num, $p_gc_pin) {

    $i_order = new Order($this->v_order_number);
    $i_gc_payment = new GiftCardPayment();

    $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_cc_total, $v_status);

    $v_order_total = $v_sub_total + $v_ship_total + $v_tax_total ;
    $v_net_total   = round(($v_order_total - $v_gc_total), 2) ;

    if($v_net_total > 0) {
      return $i_order->addGiftCardPayment($v_net_total, $p_gc_num, $p_gc_pin);
    }else {
    	return "Error: You cannot use giftcards for instore pickup orders.  Please enter a credit card to continue.";
    }
    

  }

  public function removeGiftCardPayment($p_gc_num) {
    $i_order = new Order($this->v_order_number);
    $i_order->removeGiftCardPayment($p_gc_num);
  }

  public function applyPromoCode($p_promo_code) {
    $i_order = new Order($this->v_order_number);    
    return $i_order->applyPromoCode($p_promo_code);
  }

  public function getShippingOptions($p_has_ship_restrictions, &$pa_shipping_options) {
    $i_order = new Order($this->v_order_number);
    $i_order->getShippingOptions($p_has_ship_restrictions, $pa_shipping_options);
  }

  public function updateShipping($p_ship_method) {
    $i_order = new Order($this->v_order_number);
    $i_gc_payment = new GiftCardPayment();

    $_SESSION['s_ship_method'] = $p_ship_method;
    $_SESSION['smethod'] = $p_ship_method;

    //update the whole order just in case
    //link addresses, update order with ship method, etc.
    $v_update_return_status = $i_order->updateOrder($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_ship_address_id'], $_SESSION['s_ship_contact_id'], $p_ship_method);

    $_SESSION['s_do_totals'] = 'Y';

    //we need to get the total here
    //get order total here
    $v_result = $i_order->calcTotals($v_sub_total, $v_ship_total, $v_tax_total, $v_gc_total, $v_gc_total, $v_cc_total, $v_return_status);

    //adjust any gift card payments now, so that they show up correctly in the display
    $i_gc_payment->adjustGiftCardPayments();

    $v_isp_totals_string = $i_order->getISPTotalsString();
    $i_order->extractISPTotals($v_isp_totals_string, $v_isp_total, $v_isp_sub_total, $v_isp_tax);

    $v_order_total = $v_ship_total + $v_isp_sub_total + $v_sub_total + $v_isp_tax + $v_tax_total;
    
    $_SESSION['CartTotal'] = format_currency($v_order_total);       
    echo '<SCRIPT TYPE="text/javascript">document.getElementById(\'CartTotal\').innerHTML = \'<strong>' . $_SESSION['CartTotal'] . '</strong>\';</SCRIPT>';    

    return format_currency($v_order_total);
  }

  public function getDefermentOptions() {

    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));

    $i_order = new Order($this->v_order_number);
    $i_order->getDefermentOptions($va_deferment_options);

    $z_view->deferment_options = $va_deferment_options;

    return $z_view->render('checkout_payment_deferments.phtml');

  }

  public function getdefermentdisclosure(){
  	global $web_db;
  	$z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
  	$v_plan_number=post_or_get('v_plan_no');
  	$v_ge_cc_number=post_or_get('v_ge_cc_num');  	
  	
    $pa_deferment_options = array();
    $v_sql = "select plan_number, plan_desc, min_purchase,disclosure,short_desc
              from gsi_ge_plans
              where NOW() between ifnull(start_date, date_sub(CURDATE(), INTERVAL 1 day)) 
              and ifnull(end_date, date_add(CURDATE(), INTERVAL 1 day))
              and plan_number=$v_plan_number
              order by display_sequence";
    $v_result = mysqli_query($web_db, $v_sql);
    while ($va_row = mysqli_fetch_assoc($v_result)) {
      
        $pa_deferment_options[] = $va_row;
      
    }
    
    $i_order = new Order($this->v_order_number);
    $v_ofp_language = $i_order->getDefermentDisclosure($v_ge_cc_number,$v_plan_number);
    
  	$z_view->deferment_disclosure = $pa_deferment_options;
  	
  	if (strlen($v_ofp_language) > 0)
  	{
  	   $z_view->ofp_language = $v_ofp_language;
  	
  	   return $z_view->render('checkout_payment_deferment_disclosure.phtml');
  	}else {
  	
  	   return 'ERROR:Invalid Card Number: Please verify your payment information and try again.';
  	}
  	
  }
  
  public function addCreditCardPayment($p_cc_type, $p_cc_num, $p_expiry_month, $p_expiry_year, $p_deferment, $p_save_cc_info = 'N',$p_pin_number,$v_net_total) {

    $i_order = new Order($this->v_order_number);

    if($p_cc_type == 'golfsmith_cc') {
      //don't allow saved cards for Golfsmith credit cards, because we need to allow selection of the deferment option
      // I'll send here $v_net_total paramter as well
      $v_status = $i_order->addCreditCardPayment($p_cc_num, '', '', $p_deferment,'','',$v_net_total);
    } else {
      // adding Credit Card type as last paramter to this function
      $v_status = $i_order->addCreditCardPayment($p_cc_num, $p_expiry_month, $p_expiry_year, '', $p_save_cc_info,$p_pin_number,$v_net_total,$p_cc_type);
    }
    
    if(empty($v_status) && $_SESSION["mpTransaction"])
    {
        global $mssql_db;
        
        // Add the M from attribute9 on the order
        $attr9 = "M";
        $v_stmt = mssql_init("gsi_update_payment_masterpass");
        
        gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, "varchar", 15);
        gsi_mssql_bind($v_stmt, "@p_attribute9", $attr9, "varchar", 20);
        
        mssql_execute($v_stmt);
        
        if(!$v_result) {
            display_mssql_error("gsi_update_payment_masterpass", "update to attribute9, called by addCreditCardPayment() in CheckoutPayment.php");
        }
        
        mssql_free_statement($v_stmt);
    }

    return $v_status;

  }

  public function addWireTransferPayment() {

    $i_order = new Order($this->v_order_number);

    $v_status = $i_order->addWireTransferPayment();

    return $v_status;

  }
}
?>
