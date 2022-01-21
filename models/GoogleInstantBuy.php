<?php 
require_once('models/jwt.php');
require_once('models/SiteInit.php');
require_once('models/UserRegistration.php');

 
class GoogleInstantBuy {

  /**
   * encodes MWR array or object into an encrypted JWT string
   */
  public static function encodeMWR($json) {
    return JWT::encode($json, IB_MERCHANT_SECRET);
  }
  
  /**
   * returns json object of info encrypted in JWT string
   */
  public static function decodeJwt($jwt) {
    return JWT::decode ( $jwt, IB_MERCHANT_SECRET, false );
  }
  
  
  public static function getShippingAddressFromMaskedWalletResponse($jwt) {
    $decoded = GoogleInstantBuy::decodeJwt($jwt);
    return $decoded["response"]["ship"]["shippingAddress"];
  }
  
  /**
   * returns a full wallet array object based on all the information in the current order 
   */
  public static function getOrderFullWalletArray($googleTransactionId) {
    // retrieve line items for the current order to populate the jwt
    $i_cart = new ShoppingCart ();
		$lineItems = $i_cart->getLineItems();
		$walletLineItems = array();
		for ($i = 0; $i< count($lineItems); $i++) {
		  $walletLineItems[] = array("description" => $lineItems[$i]["description"], 
		          "quantity" => $lineItems[$i]["quantity"],
		          "unitPrice" => ltrim ($lineItems[$i]["selling_price"], "$ "),    // convert to num and back?
    		      "totalPrice" => ltrim ($lineItems[$i]["total"], "$ "),
    		      "currencyCode" => "USD",
    		      "isDigital" => false
		  );
		}
		// add shipping and tax as line items
    $i_cart->getTaxShippingAndTotal(&$tax, &$shipping, &$total1, &$total2,&$v_sub_total);
    $total = floatval($v_sub_total) + floatval($tax) + floatval($shipping);
		$walletLineItems[] = array (
  		      "description" => "Sales tax",
  		      "totalPrice" => $tax,
  		      "currencyCode" => "USD",
  		      "role" => "TAX");
    $walletLineItems[] =  array (
  		      "description" => "Shipping",
  		      "totalPrice" => $shipping,
  		      "currencyCode"=> "USD",
  		      "role"=> "SHIPPING");
		
    
    $now = (int)date('U');
    //Json representation for items purchased
		$fullWalletMWR = array(
      'iat' => $now,
      'exp' => $now + IB_JWT_EXP,
      'typ' => 'google/wallet/online/full/v2/request',
      'aud' => 'Google',
      'iss' => IB_MERCHANT_ID,
      'request'=> array(
        'clientId' =>  IB_CLIENT_ID,
        'merchantName'=> IB_MERCHANT_NAME,
        'origin'=> IB_ORIGIN,
  			"googleTransactionId" => $googleTransactionId,
  		  "cart" => array (
  		    "currencyCode" => "USD",    // hardcoded as only dollar order make it through
  		    "totalPrice" => $total,
  		    "lineItems" => $walletLineItems
  		  )
  		)
		);
		return $fullWalletMWR;
  }    
  

  /**
   * Generate and return the transaction notification for post-order sending
   * Returns the encrypted notification jwt 
   * 
   * @param $googleTransactionId: The id for the current transaction
   * @param $status:  						Either SUCCESS or FAILURE
   * @param $reason:  						Reason for failure (optional)
   * @param $detailedReason:  		Failure detailed reason (optional)
   */
  public static function getTransactionStatusNotification($googleTransactionId, $status, $reason = null, $detailedReason = null) {
    $now = (int)date('U');
    $notification = array( 
      'iat' => $now,
      'exp' => $now + IB_JWT_EXP,
      'typ' => 'google/wallet/online/transactionstatus/v2',
      'aud' => 'Google',
      'iss' => IB_MERCHANT_ID,
      'request'=> array(
  			"googleTransactionId" => $googleTransactionId,
  	  )
  	);
    
  	if ($status == "SUCCESS") {
  	  $notification["request"]["status"] = "SUCCESS";
    } else {
      // if the reason sent is not in the accepted list, make it "OTHER" and place our reason in the detailedReason field 
  	  $errorReasons = array("DECLINED", "AVS_DECLINE", "FRAUD_DECLINE", "BAD_CVC", "BAD_CARD", "OTHER");
  	  if (!in_array(reason, errorReasons)) {
  		  $detailedReason = reason + " - " + detailedReason; 
  		  $reason = "OTHER";
  	  }
  	  $notification["request"]["status"] = "FAILURE";
 	    if ($reason) {
 	      $notification["request"]["reason"] = $reason;
 	    }
   	  if ($detailedReason) {
     	  $notification["request"]["detailedReason"] = $detailedReason;
 	    }
  	}
    
//  	error_log(date('d.m.Y h:i:s') . "  ---  transnotif: $googleTransactionId $status  $reason  $detailedReason    \n", 3, "/home/dev5/www/logs/phperror.log");
    $jwt = GoogleInstantBuy::encodeMWR($notification);
    return $jwt;
  }
  
  
  
  /*
   * Get a full wallet jwt (encrypted) using the passed in trasnaction id.
   * This will fetch line items from the shopping cart and calculate totals with shipping and tax
   * 
   */
  public static function getFullWallet($googleTransactionId) {    
    // retrieve line items for the current order to populate the jwt
    $i_cart = new ShoppingCart();
		$lineItems = $i_cart->getLineItems();
		$walletLineItems = array();
		for ($i = 0; $i< count($lineItems); $i++) {
		  $walletLineItems[] = array("description" => $lineItems[$i]["description"], 
		          "quantity" => $lineItems[$i]["quantity"],
		          "unitPrice" => ltrim ($lineItems[$i]["selling_price"], "$ "),    // convert to num and back?
    		      "totalPrice" => ltrim ($lineItems[$i]["total"], "$ "),
    		      "currencyCode" => "USD",
    		      "isDigital" => false
		  );
		}
		// add shipping and tax as line items
    $i_cart->getTaxShippingAndTotal(&$tax, &$shipping, &$total1, &$total2,&$v_sub_total);
    $total = floatval($v_sub_total) + floatval($tax) + floatval($shipping);
		$walletLineItems[] = array (
  		      "description" => "Sales tax",
  		      "totalPrice" => $tax,
  		      "currencyCode" => "USD",
  		      "role" => "TAX");
    $walletLineItems[] =  array (
  		      "description" => "Shipping",
  		      "totalPrice" => $shipping,
  		      "currencyCode"=> "USD",
  		      "role"=> "SHIPPING");
		
    
    $now = (int)date('U');
    //Json representation for items purchased
		$fullWalletMWR = array(
      'iat' => $now,
      'exp' => $now + IB_JWT_EXP,
      'typ' => 'google/wallet/online/full/v2/request',
      'aud' => 'Google',
      'iss' => IB_MERCHANT_ID,
      'request'=> array(
        'clientId' =>  IB_CLIENT_ID,
        'merchantName'=> IB_MERCHANT_NAME,
        'origin'=> IB_ORIGIN,
  			"googleTransactionId" => $googleTransactionId,
  		  "cart" => array (
  		    "currencyCode" => "USD",
  		    "totalPrice" => $total,
  		    "lineItems" => $walletLineItems
  		  )
  		)
		);
		
//		print_r($fullWalletMWR);
//		echo $total1;  echo "****";
//		echo $total2;echo "--";
//		echo $v_sub_total;
//		var_dump($lineItems);
//		exit;
//		$fullWalletMWR
		
//		$jwt = GoogleInstantBuy::encode_jwt($fullWalletMWR);
		return $fullWalletMWR;
  }
  
  public static function getMaskedwallet($estimatedTotal, $currency, $googleTransactionId = null) {
    $now = (int)date('U');
    $mwr = array(
      'iat' => $now,
      'exp' => $now + IB_JWT_EXP,
      'typ' => 'google/wallet/online/masked/v2/request',
      'aud' => 'Google',
      'iss' => IB_MERCHANT_ID,
      'request'=> array(
        'clientId' =>  IB_CLIENT_ID,
        'merchantName'=> IB_MERCHANT_NAME,
        'origin'=> IB_ORIGIN,
         'pay'=> array (
           'estimatedTotalPrice'=> $estimatedTotal,
           'currencyCode'=> $currency,
          ),
          'ship'=> new stdClass(),
      ),
    ); 
    
    if ($googleTransactionId) {
      $mwr['request']['googleTransactionId'] = $googleTransactionId;
    }
    try {
      $jwt = GoogleInstantBuy::encodeMWR($mwr);
    } catch (Exception $e){
      echo($e);
    }
      return $jwt;
  }

  /**
   * starting point to process an order.  
   * 	Registers the user with the shipping and billing address, and adds payment information (including CC data)
   * 
   * @param TODO
   */
  public static function addPaymentandShippingToOrder($decoded, $pan, $cvn, &$v_err_msg) {
    try {
      // put contact information in database
      GoogleInstantBuy::registerUser($decoded);
      
      // put payment information in database
      $exp_month = $decoded["response"]["pay"]["expirationMonth"];
      // make month conform to two digit requirement 
      if (intval($exp_month) < 10) {$exp_month = "0" . intval($exp_month);}
      $exp_year = $decoded["response"]["pay"]["expirationYear"];
      $i_payment = new CheckoutPayment();
      $ccreturn = $i_payment->addCreditCardPayment("mastercard", $pan, $exp_month, $exp_year, null, 'N',$cvn);     
//      $ccreturn = $i_payment->addCreditCardPayment("mastercard", "5454545454545454", "11","14", null, 'N',999);
      if (!$ccreturn) {
        return "1";
      }
      return $ccreturn;
    } catch (Exception $e) {
      return $e;
    }
  }
  
  /**
   * Change the shipping address in the Full Response JWT
   * 
   * @param $decodedJWT:  the decoded full wallet response object
   * @param $fedex_corrected_*:  the shipping address as we'd like it for the order
   */
  public static function changeShippingAddress($decodedJWT, $fedex_corrected_address1, $fedex_corrected_address2, $fedex_corrected_city, $fedex_corrected_state, $fedex_corrected_pcode) {
    $decodedJWT["response"]["ship"]["shippingAddress"]["address1"] = $fedex_corrected_address1;
    $decodedJWT["response"]["ship"]["shippingAddress"]["address2"] = $fedex_corrected_address2;
    $decodedJWT["response"]["ship"]["shippingAddress"]["city"]= $fedex_corrected_city;
    $decodedJWT["response"]["ship"]["shippingAddress"]["state"] = $fedex_corrected_state;
    $decodedJWT["response"]["ship"]["shippingAddress"]["postalCode"] = $fedex_corrected_pcode;
    return $decodedJWT;
  }
  
  
  /**
   * Adds the user information in the database that's in the decoded full wallet response
   * 
   *  $decodedJWT:  Full wallet response as it comes from google
   */
  public static function registerUser($decodedJWT) {
    if(!function_exists('gsi_mssql_bind')) {
      //load site init for database connections, helper functions, etc.
      global $connect_mssql_db;
      $connect_mssql_db = 1;
      $i_site_init = new SiteInit();
      $i_site_init->loadInit($v_connect_mssql);
    }
    
    $i_user_registration = new UserRegistration();
    $i_user_registration->verifyCustomer(
      $decodedJWT["response"]["email"],
      null,
      '',
      strtok($decodedJWT["response"]["pay"]["billingAddress"]["name"], " "),
      strtok(" "),
      $decodedJWT["response"]["pay"]["billingAddress"]["address1"] .  " " . $decodedJWT["response"]["pay"]["billingAddress"]["address2"],
      $decodedJWT["response"]["pay"]["billingAddress"]["address3"],
      $decodedJWT["response"]["pay"]["billingAddress"]["city"],
      $decodedJWT["response"]["pay"]["billingAddress"]["state"],
      $decodedJWT["response"]["pay"]["billingAddress"]["postalCode"],
      $decodedJWT["response"]["pay"]["billingAddress"]["countryCode"],
      strtok($decodedJWT["response"]["ship"]["shippingAddress"]["name"], " "),
      strtok(" "),
      $decodedJWT["response"]["ship"]["shippingAddress"]["address1"] .  " " . $decodedJWT["response"]["ship"]["shippingAddress"]["address2"],
      $decodedJWT["response"]["pay"]["shippingAddress"]["address3"],
      $decodedJWT["response"]["ship"]["shippingAddress"]["city"],
      $decodedJWT["response"]["ship"]["shippingAddress"]["state"],
      $decodedJWT["response"]["ship"]["shippingAddress"]["postalCode"],
      $decodedJWT["response"]["ship"]["shippingAddress"]["countryCode"],
      "",
      "",
      '',
      '',
      &$address_id,
      &$contact_id,
      &$customer_id,
  	  &$p_ship_contact_id, 
	    &$p_phone_id,
	    &$p_ship_phone_id,      
      &$return_status,
      &$ship_return_status,
      &$bill_return_status,
      &$ship_site_use_id,
      &$bill_site_use_id,
      "Y"
    );
  } 
  
}
 