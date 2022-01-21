<?php 
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/ShoppingCart.php');
require_once('models/CheckoutPage.php');
require_once('models/CheckoutLogin.php');
require_once('models/CheckoutAddressEntry.php');
require_once('models/CheckoutPayment.php');
require_once('models/CheckoutReview.php');
require_once('models/CheckoutSubmit.php');
require_once('models/GoogleCheckout.php');
require_once('models/PayPalCheckout.php');
require_once('models/AmazonCheckout.php');
require_once('models/ATPExtendedSoap.php');
require_once('models/StoreFinder.php');
require_once('models/Order.php');
require_once('models/UserRegistration.php');
require_once('models/MasterPassPromo.php');
require_once('Zend/View.php');

class CheckoutController extends Zend_Controller_Action {

  public function indexAction() {
    $v_port = $v_port = $_SERVER['SERVER_PORT'];
    if($v_port != '443') {
      $this->_redirect('https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
      return;
    }
    $this->cartAction();
  }

  // Cart-related actions **********

  public function cartAction() {
    $v_port = $v_port = $_SERVER['SERVER_PORT'];
    if($v_port != '443') {
      $this->_redirect('https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
      return;
    }
    $i_request = $this->getRequest();
	$i_cart = new ShoppingCart();
	$order = new Order($_SESSION["s_order_number"]);
	
	// Clear the master pass info
	if($i_request->getParam('mpstatus') != 'success') {
		$order->clearMPSessionInfo();
	}
	
    if ($i_request->getParam('email')) {	    
	    $v_email = strip_tags($i_request->getParam('email'));
	    $v_offset = strip_tags($i_request->getParam('offset'));
	 
	    $i_cart->restoreCart($v_email, $v_offset);
    }
      $i_cart->displayPage();
  }

  public function cartitemsAction() {
    $i_cart = new ShoppingCart();
    echo $i_cart->displayItemListing(FALSE);
  }
  
  public function validateorderqtysAction() {
    $i_cart = new ShoppingCart();
    echo $i_cart->validateOrderQtys();
  }
  
  public function validateorderqbdAction() {
    $i_cart = new ShoppingCart();
    echo $i_cart->validateQBD();
  }

  //used with Ajax update line quantity functionality
  	public function updateatpAction() {
		$i_request = $this->getRequest();
		$v_line_reference = strip_tags($i_request->getParam('line'));
		$v_quantity = $i_request->getParam('quantity');
		$ii_inv = $i_request->getParam('inv_id');
		$i_cart = new ShoppingCart();
		$getTtlOrderPerItem = $i_cart->getTtlOrderPerItem();
		
		$atp_arr = array(array("iiv"=>$ii_inv,"qty"=>$v_quantity));
		$soap_atp = ATPExtendedSoap::checkATPProc($atp_arr);
		if(!empty($v_line_reference) && is_numeric($v_line_reference) && is_numeric($v_quantity)) {
			//$i_cart->updateATP($v_line_reference, $v_quantity);
      		//show item listing without containing div, because the div is already defined in the document
      		$soap_atp["promisedDate"] = date ("d-M-y",$soap_atp["promisedDate"]) ;
   			$soap_atp["OrderedQTY"]=$getTtlOrderPerItem[$ii_inv];
      		//$soap_atp["totalQTY"] = 3 ;
			echo json_encode($soap_atp);
    	}
	}
  //used with Ajax update line quantity functionality
  public function updateAction() {

    $i_request = $this->getRequest();
    $v_line_reference = strip_tags($i_request->getParam('line'));
    $v_quantity = $i_request->getParam('quantity');

    $i_cart = new ShoppingCart();

    if(!empty($v_line_reference) && is_numeric($v_line_reference) && is_numeric($v_quantity)) {
      $i_cart->updateLine($v_line_reference, $v_quantity);
      //show item listing without containing div, because the div is already defined in the document
      echo $i_cart->displayItemListing(FALSE);
    }
  }

  public function addpureingAction() {

    $i_request = $this->getRequest();
    $v_line_reference = strip_tags($i_request->getParam('line'));
    $v_quantity = $i_request->getParam('quantity');
    $v_style = $i_request->getParam('stynum');

    $i_cart = new ShoppingCart();

    if(!empty($v_line_reference) && is_numeric($v_line_reference) && is_numeric($v_quantity)) {
      $i_cart->addPureing($v_style,$v_quantity,$v_line_reference);
      //show item listing without containing div, because the div is already defined in the document
      echo $i_cart->displayItemListing(FALSE);
    }
  }
  
  public function cartfooterAction() {

    $i_request = $this->getRequest();
    $v_ship_method = strip_tags($i_request->getParam('method_select'));
    $v_postal_code = strip_tags($i_request->getParam('postal_code'));
    $v_source_code = strip_tags($i_request->getParam('source_code'));

    $i_cart = new ShoppingCart();
    echo $i_cart->displayCartFooter($v_ship_method, $v_postal_code, $v_source_code, FALSE);

  }

  public function cartcheckoutAction() {
    $i_cart = new ShoppingCart();
    echo $i_cart->displayCartCheckoutBottom(FALSE);
  }

  public function getispformAction() {

    $i_request = $this->getRequest();
    $v_line_number = strip_tags($i_request->getParam('line_number'));

    $i_cart = new ShoppingCart();
    echo $i_cart->getISPForm($v_line_number);

  }

  public function findispstoreAction() {

    $i_request = $this->getRequest();
    $v_line_number = strip_tags($i_request->getParam('line_number'));
    $v_inventory_item_id = strip_tags($i_request->getParam('inventory_item_id'));
    $v_quantity = strip_tags($i_request->getParam('quantity'));

    $i_cart = new ShoppingCart();
    echo $i_cart->findISPStore($v_line_number, $v_inventory_item_id, $v_quantity);

  }

  public function changeispwarehouseAction() {

    $i_request = $this->getRequest();
    $v_line_number = strip_tags($i_request->getParam('line_number'));
    $v_organization_id = strip_tags($i_request->getParam('organization_id'));
    $v_line_type = strip_tags($i_request->getParam('line_type'));

    $i_cart = new ShoppingCart();
    $i_cart->changeLineISPWarehouse($v_line_number, $v_organization_id, $v_line_type);
    echo $i_cart->displayItemListing(FALSE);

  }

  public function emptycartAction() {

    $i_cart = new ShoppingCart();
    $i_cart->emptyCart();
    echo $i_cart->displayItemListing(FALSE);

  }

  //returns string indicating if customer is logged in
  public function checkloggedinAction() {
    $i_cart = new ShoppingCart();
    echo $i_cart->checkLoggedIn();
  }

  // Login-related actions **********

  public function loginAction() {
    $i_login = new CheckoutLogin();
    $i_login->displayPage();
  }

  public function logincheckAction() {
    $i_login = new CheckoutLogin();
    $i_login->handleLogin();
  }

  //returns content of login page (minus header, footer, etc.)
  public function logincontentAction() {
    $i_login = new CheckoutLogin();
    echo $i_login->displayLogin(FALSE);
  }


  // Address Entry actions **********

  public function addressentryAction() {
    $i_address_entry = new CheckoutAddressEntry();
    $i_address_entry->displayPage();
  }

  public function addressentrycontentAction() {
    $i_address_entry = new CheckoutAddressEntry();
    echo $i_address_entry->displayAddressEntry(FALSE);
  }

  public function fedexcheckAction() {
    $i_address_entry = new CheckoutAddressEntry();
    echo $i_address_entry->fedExCheck();
  }

  public function verifycustomerAction() {
    //customer creation for guest checkout - either returns error div HTML or empty string for success
    $i_address_entry = new CheckoutAddressEntry();
    echo $i_address_entry->registerCustomer();
  }


  // Payment actions **********

  public function paymentAction() {
    $i_payment = new CheckoutPayment();
    $i_payment->displayPage();
  }

  public function paymentcontentAction() {
    $i_payment = new CheckoutPayment();
    echo $i_payment->displayPayment(FALSE);

  }

  public function addgiftcardpaymentAction() {

    $i_request = $this->getRequest();
    $v_gc_num = strip_tags($i_request->getParam('giftCard_number_input'));
    $v_gc_pin = strip_tags($i_request->getParam('giftCard_pin_input'));

    $i_payment = new CheckoutPayment();
    echo $i_payment->addGiftCardPayment($v_gc_num, $v_gc_pin);
  }

  public function removegiftcardpaymentAction() {
    $i_request = $this->getRequest();
    $v_gc_num = strip_tags($i_request->getParam('giftCard_number'));

    $i_payment = new CheckoutPayment();
    echo $i_payment->removeGiftCardPayment($v_gc_num);
  }

  public function getgiftcardsAction() {
    $i_payment = new CheckoutPayment();
    echo $i_payment->displayGiftCardList();
  }

  public function applypromocodeAction() {
    $i_request = $this->getRequest();
    $v_promo_code = strip_tags($i_request->getParam('cashCard_number'));

    $i_payment = new CheckoutPayment();
    echo $i_payment->applyPromoCode($v_promo_code);
  }
  
  public function applycartpromocodeAction() {
    $i_request = $this->getRequest();
    $v_promo_code = strip_tags($i_request->getParam('source_code'));
    
    $mpPromo = new MasterPassPromo();
    $i_cart = new ShoppingCart();
    $promo = $mpPromo->isMPPromo($v_promo_code);
    
    if(!$promo)
    {
        $result = $i_cart->updateCartPromoCode($v_promo_code);
    }
    else
    {
        $result = $promo;
    }
    
    echo json_encode($result);
  }

  public function updatepaymentshippingAction() {
    $i_request = $this->getRequest();
    $v_ship_method = strip_tags($i_request->getParam('method_select'));

    $i_payment = new CheckoutPayment();
    echo $i_payment->updateShipping($v_ship_method);
    
  }

  public function getdefermentoptionsAction() {
    $i_payment = new CheckoutPayment();
    echo $i_payment->getDefermentOptions();
  }
  
  public function getdefermentdisclosureAction(){
  	$i_payment = new CheckoutPayment();
    echo $i_payment->getdefermentdisclosure();
  }
  
  public function addcreditcardpaymentAction() {

    $i_request = $this->getRequest();
    $v_payment_method = strip_tags($i_request->getParam('ccType'));
    $v_cc_num = strip_tags($i_request->getParam('cc_number_input'));
    $v_expiry_month = strip_tags($i_request->getParam('cc_MM_input'));
    $v_expiry_year = strip_tags($i_request->getParam('cc_YY_input'));
    $v_deferment = strip_tags($i_request->getParam('terms_select'));

    $i_payment = new CheckoutPayment();
    $v_status = $i_payment->addCreditCardPayment($v_payment_method, $v_cc_num, $v_expiry_month, $v_expiry_year, $v_deferment);

    echo $v_status;
  }

  public function addwiretransferpaymentAction() {
    $i_payment = new CheckoutPayment();
    $v_status = $i_payment->addWireTransferPayment();

    echo $v_status;
  }

  public function displayaddressbookAction() {

    $i_request = $this->getRequest();

    $v_address_type = strip_tags($i_request->getParam('address_type'));
    $v_containing_page = strip_tags($i_request->getParam('containing_page'));
    $v_require_canadian_address = strip_tags($i_request->getParam('require_canadian_address'));

    if($v_require_canadian_address == 'true') {
      $v_require_canadian_address = TRUE;
    } else {
      $v_require_canadian_address = FALSE;
    }

    $i_payment = new CheckoutPayment();
    echo $i_payment->displayAddressBook(TRUE, $v_address_type, $v_containing_page, $v_require_canadian_address);

  }

  public function displayaddressbookformAction() {

    $i_request = $this->getRequest();

    $v_address_id = strip_tags($i_request->getParam('address_id'));
    $v_contact_id = strip_tags($i_request->getParam('contact_id'));
    $v_phone_id = strip_tags($i_request->getParam('phone_id'));
    $v_address_type = strip_tags($i_request->getParam('address_type'));
    $v_containing_page = strip_tags($i_request->getParam('containing_page'));

    $i_payment = new CheckoutPayment();
    echo $i_payment->displayAddressBookForm(TRUE, $v_address_id, $v_contact_id, $v_phone_id, $v_address_type, $v_containing_page);

  }

  public function setaddressasprimaryAction() {

    $i_request = $this->getRequest();

    $v_address_id = strip_tags($i_request->getParam('address_id'));
    $v_address_type = strip_tags($i_request->getParam('address_type'));
    $v_containing_page = strip_tags($i_request->getParam('containing_page'));

    $i_payment = new CheckoutPayment();
    echo $i_payment->setAddressAsPrimary($v_address_id, $v_address_type, $v_containing_page);

  }

  public function updateaddressAction() {

    $i_request = $this->getRequest();

    $i_payment = new CheckoutPayment();
    echo $i_payment->updateAddress($i_request); 

  }

  public function setsessionaddressinfoAction() {

    $i_request = $this->getRequest();
    $v_address_type = strip_tags($i_request->getParam('address_type'));
    $v_address_id = strip_tags($i_request->getParam('address_id'));
    $v_contact_id = strip_tags($i_request->getParam('contact_id'));
    $v_phone_id = strip_tags($i_request->getParam('phone_id'));
    $v_containing_page = strip_tags($i_request->getParam('containing_page'));

    $i_payment = new CheckoutPayment();
    $i_payment->setSessionAddressInfo($v_address_id, $v_contact_id, $v_phone_id, $v_address_type);

    if($v_containing_page == 'payment') {
      echo $i_payment->displayPayment(FALSE);
    } else { //review page
      //JavaScript will handle the needed content refreshes in this case
      echo '';
    }
  }

  public function deleteaddressAction() {

    $i_request = $this->getRequest();
    $v_address_id = strip_tags($i_request->getParam('address_id'));
    $v_address_type = strip_tags($i_request->getParam('address_type'));
    $v_containing_page = strip_tags($i_request->getParam('containing_page'));

    $i_payment = new CheckoutPayment();
    $i_payment->deleteAddress($v_address_id);
    echo $i_payment->displayAddressBook(TRUE, $v_address_type, $v_containing_page);

  }

  public function checkpaymentsAction() {

    $i_request = $this->getRequest();
    $i_payment = new CheckoutPayment();
    echo $i_payment->checkPayments($i_request);

  }

  public function haspaymentsAction() {

    $i_payment = new CheckoutPayment();
    echo $i_payment->hasPayments();

  }

  // Review actions **********

  public function reviewAction() {
    $i_review = new CheckoutReview();
    echo $i_review->displayPage();
  }

  public function reviewcontentAction() {

    $i_request = $this->getRequest();
    $v_ship_method = strip_tags($i_request->getParam('method_select'));

    $i_review = new CheckoutReview();
    echo $i_review->displayReviewContent($v_ship_method);

  }

  public function displayreviewshipitemsonlyAction() {

    $i_review = new CheckoutReview();
    echo $i_review->displayShipSectionOnly(FALSE);

  }

  public function displayreviewchargesAction() {

    $i_request = $this->getRequest();
    $v_ship_method = strip_tags($i_request->getParam('method_select'));

    $i_review = new CheckoutReview();
    echo $i_review->displayCharges(FALSE, $v_ship_method);

  }

  public function displaypaymentchargesAction() {    

    $i_payment = new CheckoutPayment();
    echo $i_payment->updatePaymentCharges();

  }
  
  public function displaypaymentshippingchargesAction() {    

    $i_payment = new CheckoutPayment();
    echo $i_payment->updateShippingCharges();

  }
  
  public function displayreviewshippaymentAction() {

    $i_review = new CheckoutReview();
    echo $i_review->displayShippingAndPayment(FALSE);

  }

  public function updatesalesassociateinfoAction() {

    $i_request = $this->getRequest();

    $v_salesrep_number = strip_tags($i_request->getParam('salesrep_number'));

    $i_review = new CheckoutReview();
    $i_review->updateSalesAssociateInfo($v_salesrep_number, $v_salesrep_name);
    echo $i_review->displaySubmit(FALSE, $v_salesrep_number, $v_salesrep_name);

  }

  public function isisponlyAction() {

    $i_review = new CheckoutReview();
    echo $i_review->isISPOnly();

  }

  public function updatereviewshippingAction() {

    $i_request = $this->getRequest();
    $v_ship_method = strip_tags($i_request->getParam('method_select'));

    $i_review = new CheckoutReview();
    echo $i_review->updateReviewShipping($v_ship_method);

  }

  public function updateamazonreviewshippingAction() {

    $i_request = $this->getRequest();
    $v_ship_method = strip_tags($i_request->getParam('method_select'));

    $i_amazon = new AmazonCheckout();
    echo $i_amazon->updateAmazonReviewShipping($v_ship_method);

  }
  
  // Order Submit actions **********

  public function processorderAction() {
    $i_submit = new CheckoutSubmit();
    $i_order = new Order($_SESSION['s_order_number']);
    $i_review = new CheckoutReview();
    $i_request = $this->getRequest();

    $v_is_gift_order = strip_tags($i_request->getParam('is_gift_order'));
    $temp_CVV = strip_tags($i_request->getParam('cvv'));

    if (!empty($temp_CVV)){
        $_SESSION["p_pin_number"] = $temp_CVV;
    }
    
    $i_order->updateHeaderGiftOrder($v_is_gift_order);
    
    //check for qty validations
    $v_qty_err = $i_order->validateOrderQtys();
    //check for restrictions before doing anything else
    //check country restrictions
	  if($i_review->isRestricted()) {
    		echo "restricted"; 
      }else if (!empty($v_qty_err)) {      		
      		echo 'qtyerr: ' . $v_qty_err;       	
      } 
      else {
      	
         	$i_user_registration = new UserRegistration();
		    $v_user_name = $_SESSION['s_user_name'];
		
		    //check needed information before processing the order -- easier to get this info while it's still in direct
		    $v_return = $i_order->getPaymentInfo($v_card_type, $v_card_number, $v_expiry, $v_amt_paid);
		
		    if(empty($v_card_type) && $v_amt_paid > 0 && $_SESSION['s_using_paypal'] != 'Y') {
		      $v_require_wire_transfer = TRUE;
		    } else {
		      $v_require_wire_transfer = FALSE;
		    }
		
		    $v_has_isp_sts = $i_order->isISPSTS();
		
		    $v_has_web_account = $i_user_registration->hasWebAccount($v_user_name);
		
		    if($v_has_web_account === TRUE) {
		      $v_request_web_account = FALSE;
		    } else {
		      $v_request_web_account = TRUE;
		    }
				
		    //$has_external_items = $i_order->checkForPrebook();
		    
		    $v_require_payment = $i_submit->processOrder($v_paypal_error_message, $v_err_msg);

		    if(!empty($v_paypal_error_message)) {
		      echo 'paypal: ' . $v_paypal_error_message;
		    } else if($v_require_payment === TRUE) {
		      $i_payment = new CheckoutPayment();
		      echo 'payment: errStart' . $v_err_msg . 'errEnd' . $i_payment->displayPayment(FALSE);
		    } else {
		      echo 'confirm:' . $i_submit->displaySubmitConfirm(FALSE, $v_require_wire_transfer, $v_has_isp_sts, $v_request_web_account, $cpi, $has_external_items);
		    }      	
      }
  }

  public function processamazonorderAction() {

    $i_request = $this->getRequest();
    $v_asession = strip_tags($i_request->getParam('asession'));

    $i_amazon = new AmazonCheckout();
    $i_amazon->processAmazonOrder($v_asession);
                        //echo 'confirm:' . $i_submit->displaySubmitConfirm(FALSE, $v_require_wire_transfer, $v_has_isp_sts, $v_request_web_account);
  }
  
  public function updateaccountAction() {

    $i_request = $this->getRequest();

    $v_password = strip_tags($i_request->getParam('createAccount_password_input'));
    $v_password_confirm = strip_tags($i_request->getParam('createAccount_passwordConfirm_input'));

    $i_submit = new CheckoutSubmit();
    echo $i_submit->updateAccount($v_password, $v_password_confirm);

  }

  // Google Checkout actions **********

  public function getgooglecheckouturlAction() {

    $i_google_checkout = new GoogleCheckout();
    echo $i_google_checkout->getCheckoutUrl();

  }

  
  // PayPal Checkout actions **********

  public function getpaypalcheckouturlAction() {

    $i_paypal_checkout = new PayPalCheckout();
    echo $i_paypal_checkout->getCheckoutUrl();

  }

  public function paypallandingAction() {

    $i_paypal_checkout = new PayPalCheckout();
    echo $i_paypal_checkout->getCheckoutDetails();

  }
 
  public function paypalcancelAction() {

    $i_paypal_checkout = new PayPalCheckout();
    $i_paypal_checkout->cancelPayPalCheckout();

    $this->cartAction();

  }
 
  // Amazon Checkout action
  public function amazoncheckoutAction()
  {
        //error_reporting(E_ALL);
        $i_amazon_checkout = new AmazonCheckout();
        $i_amazon_checkout->displayPage();
  }
  
  public function refreshamazonreviewchargesAction()
  {
  	$i_amazon_checkout = new AmazonCheckout();
  	$postal_code = post_or_get('postal_code');
  	$purchaseContractId = post_or_get('asession');
  	$p_ship_method = post_or_get('method_select');
  	if(!$postal_code)
  	{
  		$postal_code = $i_amazon_checkout->getPostalCode($purchaseContractId);
  	}
    $i_amazon_checkout->refreshAmazonReviewCharges($p_ship_method,$postal_code,$purchaseContractId);
  }
  public function amazongetpostalcodeAction()
  {
        $i_amazon_checkout = new AmazonCheckout();
        $purchaseContractId = post_or_get('asession');
        $v_postal_code = $i_amazon_checkout->getPostalCode($purchaseContractId);
        echo $v_postal_code;
  }

  public function amazongetcountrycodeAction()
  {
        $i_amazon_checkout = new AmazonCheckout();
        $purchaseContractId = post_or_get('asession');
        $v_country_code = $i_amazon_checkout->getCountryCode($purchaseContractId);
        echo $v_country_code;
  }
  // Cart restore actions **********
  
  public function restorecartAction() {
  	
    $i_request = $this->getRequest();
    $v_email = strip_tags($i_request->getParam('email'));
    $v_offset = strip_tags($i_request->getParam('offset'));

    $i_cart = new ShoppingCart();
    $i_cart->restoreCart($v_email, $v_offset);    

    $this->cartAction();
  }
  
  
  public function ispcheckoutAction(){
      
      $i_request = $this->getRequest();
      $v_email = strip_tags($i_request->getParam('email'));
      
      
      
      global $connect_mssql_db;
      $connect_mssql_db = 1;
      
      $i_site_init = new SiteInit();
      $i_site_init->loadInit($connect_mssql_db);

      $i_site_init->loadMain();
      
      /*
      echo "<pre>";
      print_r($_SESSION);
      echo "</pre>";
      */

      
      if(!empty($_SESSION['s_customer_id'])){
          $first_name = $_SESSION['s_first_name'];
          $last_name = $_SESSION['s_last_name'];
          $email = $_SESSION['s_user_name'];
          $phone_number = $_SESSION['phone_number'];
      }else{
          if(!empty($v_email)){
              $email = $v_email;
          }else{
              header("Location: /checkout/");
          }
         
      }
      
      
      $v_subtotal = 0;
      $v_items = $_SESSION['SmartMarketerItems'];

      $i_storeFinder = new StoreFinder();
      if(!empty($_SESSION['SmartMarketerItems'])){
          
           for($i=0; $i < count($v_items); $i++){
                $v_org_id = $v_items[$i]['store_org_id'];
                
                $v_subtotal += trim($v_items[$i]['total'], '$');
                $result = $i_storeFinder->getClosesStoresByOrgId($v_org_id);
                $row = mysqli_fetch_assoc($result);
                $storeinfo = array();
                $storeInfo['location_code'] = $row['location_code'];
                $storeInfo['address'] = $row['address_line_2'];
                $storeInfo['city'] = $row['town_or_city'];
                $storeInfo['state'] = $row['state'];
                $storeInfo['zipcode'] = substr($row['postal_code'], 0, 5);
                $storeInfo['sun_start'] = $row['sun_start'];
                $storeInfo['mon_start'] = $row['mon_start'];
                $storeInfo['tue_start'] = $row['tue_start'];
                $storeInfo['wed_start'] = $row['wed_start'];
                $storeInfo['thu_start'] = $row['thu_start'];
                $storeInfo['fri_start'] = $row['fri_start'];
                $storeInfo['sat_start'] = $row['sat_start'];
                $storeInfo['sun_end'] = $row['sun_end'];
                $storeInfo['mon_end'] = $row['mon_end'];
                $storeInfo['tue_end'] = $row['tue_end'];
                $storeInfo['wed_end'] = $row['wed_end'];
                $storeInfo['thu_end'] = $row['thu_end'];
                $storeInfo['fri_end'] = $row['fri_end'];
                $storeInfo['sat_end'] = $row['sat_end'];
                
                $v_items[$i]['store_info'] = $storeInfo;
          }
      }
      
      $i_checkoutPage = new CheckoutPage();
      $p_isp_total_str = $i_checkoutPage->get_isp_totals_string($_SESSION['s_order_number']);
      $response = $this->_extract_isp_totals($p_isp_total_str);
      

      $v_sales_tax =  $response['isp_tax'];
      $v_total = $v_sales_tax + $v_subtotal; //"{CALC FROM TABLE}";
      
      
      //include view
      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));
      
      $z_view->first_name = $first_name;
      $z_view->last_name = $last_name;
      $z_view->email = $email;
      $z_view->phone_number = $phone_number;
      $z_view->items = $v_items;
      $z_view->sales_tax = $v_sales_tax;
      $z_view->Total_in_store = $v_total;
      $z_view->subtotal = $v_subtotal;
     

      echo $z_view->render("checkout_isp.phtml");
      
      $i_site_init->loadFooter();
  }
  
  public function ispcheckoutconfirmAction(){
      global $connect_mssql_db;
      $connect_mssql_db = 1;
      
      $i_site_init = new SiteInit();
      $i_site_init->loadInit($connect_mssql_db);
      /*
      
      $i_site_init->loadMain();
      */
      
      //Check if all of the orders are pick up in store
      $order_number = $_SESSION['s_order_number'];
      $v_order = new Order($order_number);
      
      $i_request = $this->getRequest();
      $v_email = trim(strip_tags($i_request->getParam('email')));
      $v_fname = trim(strip_tags($i_request->getParam('pu-fname')));
      $v_lname = trim(strip_tags($i_request->getParam('pu-lname')));
      $v_phone = trim(strip_tags($i_request->getParam('pu-phone')));
      
      // Store pickup info
      $v_order->saveISPInfo($order_number, $v_phone, $v_email, "$v_fname $v_lname");
      
      if( $v_order->isISPOnly() ){
          $v_signup = strip_tags($i_request->getParam('pu-signup')); 
          
          $_SESSION['s_user_name'] = $v_email;
          $_SESSION['s_first_name'] = $v_fname;
          $_SESSION['s_last_name'] = $v_lname;
          
          $v_items = $_SESSION['SmartMarketerItems'];
    
          $i_storeFinder = new StoreFinder();
          if(!empty($_SESSION['SmartMarketerItems'])){
              
               for($i=0; $i < count($v_items); $i++){
                    $v_org_id = $v_items[$i]['store_org_id'];
                    
                    $v_subtotal += trim($v_items[$i]['total'], '$');
                    $result = $i_storeFinder->getClosesStoresByOrgId($v_org_id);
                    $row = mysqli_fetch_assoc($result);
                    $storeinfo = array();
                    $storeInfo['location_code'] = $row['location_code'];
                    $storeInfo['address'] = $row['address_line_2'];
                    $storeInfo['city'] = $row['town_or_city'];
                    $storeInfo['state'] = $row['state'];
                    $storeInfo['zipcode'] = substr($row['postal_code'], 0, 5);
                    $storeInfo['sun_start'] = $row['sun_start'];
                    $storeInfo['mon_start'] = $row['mon_start'];
                    $storeInfo['tue_start'] = $row['tue_start'];
                    $storeInfo['wed_start'] = $row['wed_start'];
                    $storeInfo['thu_start'] = $row['thu_start'];
                    $storeInfo['fri_start'] = $row['fri_start'];
                    $storeInfo['sat_start'] = $row['sat_start'];
                    $storeInfo['sun_end'] = $row['sun_end'];
                    $storeInfo['mon_end'] = $row['mon_end'];
                    $storeInfo['tue_end'] = $row['tue_end'];
                    $storeInfo['wed_end'] = $row['wed_end'];
                    $storeInfo['thu_end'] = $row['thu_end'];
                    $storeInfo['fri_end'] = $row['fri_end'];
                    $storeInfo['sat_end'] = $row['sat_end'];
                    
                    $v_items[$i]['store_info'] = $storeInfo;
              }
          }
          
          $v_order_number = $_SESSION['s_order_number'];
          
          $i_checkoutPage = new CheckoutPage();
          $p_isp_total_str = $i_checkoutPage->get_isp_totals_string($_SESSION['s_order_number']);
          $response = $this->_extract_isp_totals($p_isp_total_str);
    
          $v_sales_tax =  $response['isp_tax'];
          $v_total = $v_sales_tax + $v_subtotal; //"{CALC FROM TABLE}";
    
      
          //include view
          $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));
      
          $z_view->first_name = $v_fname;
          $z_view->last_name = $v_lname;
          $z_view->phone = $v_phone;
          $z_view->sales_tax = $v_sales_tax;
          $z_view->Total_in_store = $v_total;
          $z_view->subtotal = $v_subtotal;
          $z_view->order_number = str_replace("G", "", $v_order_number);
          $z_view->items = $v_items;
          
          
          
          //if the user is not logged in
          $data = array();
          $data['isp_guest_checkout'] = "N";
          if(empty($_SESSION['s_customer_id'])){
              //verify customer
              $info = array();
              $info['add1_ship_input']      =  $info['add1_bill_input'] =   $v_items[0]['store_info']['address'];
              $info['add2_ship_input']      =  $info['add2_bill_input'] =   "";
              $info['city_ship_input']      =  $info['city_bill_input'] =   $v_items[0]['store_info']['city'];
              $info['pcode_ship_input']     =  $info['pcode_bill_input'] =  $v_items[0]['store_info']['zipcode'];
              $info['country_ship_select']  =  $info['country_bill_select'] = "US";
              $info['state_ship_input']     =  $info['state_bill_input'] =  $v_items[0]['store_info']['state'];
              
              $info['fname_bill_input'] = $info['fname_ship_input'] = $v_fname;
              $info['lname_bill_input'] =  $info['lname_ship_input'] = $v_lname;
              $info['email_address_input'] =  $v_email;
              $info['phone_bill_input'] =  $v_phone;
              
              if($v_signup == "on"){
                  $info['GSEmailOptIn'] = "Y";
              }else{
                  $info['GSEmailOptIn'] = "N";
              }
    
              $i_userRegistration = new UserRegistration();
              $i_userRegistration->registerIspCustomer($info);
              $data['isp_guest_checkout'] = "Y";
              
          }else{
              $_SESSION['phone_number'] = $v_phone;
          }
            
          
          if($v_signup == "on"){
              $data['GSEmailOptIn'] = "Y";
          }else{
              $data['GSEmailOptIn'] = "N";
          }
          
          
          
          $i_order = new Order($v_order_number);
          $i_order->processIspOrder($data);
    
          $response = array();
          $response['status'] = "success";
          $response['html'] = $z_view->render("checkout_submit_confirm_isp.phtml");
          
      }else{// if statment ends check if is only isp
          $response = array();
          $response['status'] = "fail";
      }
  
      die(json_encode($response));
      //$i_site_init->loadFooter();
  }//ispconfirm functin ends
  
  
  function _extract_isp_totals($p_isp_total_str){
      global $web_db;
  
      if ( empty($p_isp_total_str) ){
          return 1;
      }
      $isp_totals = explode("|", $p_isp_total_str);
  
      $idx = 0 ;
      $response = array();
      $response['isp_total'] = 0;
      $response['isp_sub_total'] = 0;
      $response['isp_tax'] = 0;
      
      while ($idx < count($isp_totals)) {
  
          if ($isp_totals[$idx] == "T") {
              $response['isp_total']     = $isp_totals[$idx+1];
              $response['isp_sub_total'] = $isp_totals[$idx+2];
              $response['isp_tax']       = $isp_totals[$idx+3];
          }
          $idx++;
      }
      return $response;
  }// end of function get extract_isp_totals
  
  public function getmptokenAction()
  {
      // Create object we'll send back to caller
      $mpData = new stdClass();
      
      // Get the MasterPass data
      $shoppingCart = new ShoppingCart();
      $data = $shoppingCart->getMasterPassData(TRUE);
      
      if($data)
      {
          // Populate what we'll send back
          $mpData->requestToken = $data->requestToken;
          $mpData->merchantCheckoutId = $data->checkoutIdentifier;
          $mpData->pairingRequestToken = $data->pairingToken;
          $mpData->error = "";
      }
      else
      {
          $mpData->error = "There was an issue communication with MasterPass. Please try again.";
      }
      
      echo json_encode($mpData);
  }
  
  // Get the pairing and request verifier tokens
  public function mppairAction()
  {
      // Get the MasterPass data
      $shoppingCart = new ShoppingCart();
      $masterPassData = $shoppingCart->getMasterPassData();
      
      // Make sure the pairing was a success for the same request
      if($_POST["mpstatus"] == "success" && $_POST["oauth_token"] == $masterPassData->requestToken && (!ISSET($_POST["pairing_token"]) || $_POST["pairing_token"] == $masterPassData->pairingToken))
      {
          try
          {
              // Get the checkout info
              $masterPassData->requestVerifier = $_POST["oauth_verifier"];
              $masterPassData->pairingVerifier = $_POST["pairing_verifier"];
              $masterPassData->checkoutResourceUrl = $_POST["checkout_resource_url"];
              $shoppingCart = new ShoppingCart();
              $masterPassData = $shoppingCart->getMasterPassCheckoutInfo($masterPassData);
              
              // Save the info for the order
              $checkoutPayments = new CheckoutPayment();
              $checkoutPayments->saveMPCheckoutInfo($masterPassData->checkoutData, $masterPassData->longAccessToken);
              
              $ccInfo = new stdClass();
              $ccInfo->AccountNumber = $masterPassData->checkoutData->Card->AccountNumber->__toString();
              $ccInfo->ExpMonth = sprintf('%02d', $masterPassData->checkoutData->Card->ExpiryMonth);
              $ccInfo->ExpYear = substr($masterPassData->checkoutData->Card->ExpiryYear, -2);
              switch(strtoupper($masterPassData->checkoutData->Card->BrandName))
              {
                  case "VISA":
                      $ccInfo->Type = "cc_visa";
                      break;
                  case "MASTERCARD":
                      $ccInfo->Type = "cc_mc";
                      break;
                  case "AMERICAN EXPRESS":
                      $ccInfo->Type = "cc_amex";
                      break;
                  case "DISCOVER":
                      $ccInfo->Type = "cc_disc";
                      break;
              }
              
              echo json_encode($ccInfo);
          }
          catch(Exception $e)
          {
              write_to_log("MasterPass Pair Error ". $e->getMessage()." the call:".var_export($e,TRUE));
              echo 'error';
          }
      }
      else
      {
          echo 'error';
      }
  }
}
?>
