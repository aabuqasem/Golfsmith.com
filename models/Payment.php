<?
/** 
 * vim: set filetype=php :
 * $Id: payment_processor,v 1.0 2009/03/02 14:09:47 websrc Exp $
 * Copyright Golfsmith International 2009
 */

/****************************************************************************
*                                                                           *
*  Program Name :  Payment.class                                            *
*          Type :  Class                                                    *
*     File Type :  PHP                                                      *
*      Location :  /www/include/functions                                   *
*    Created By :  Angela Roles                                             *
*  Created Date :  03/02/2009                                               *
*               :  Copyright 2009  Golfsmith International                  *
*---------------------------------------------------------------------------*
*   Called From :                                                           *
*                                                                           *
*      Includes : gsi_common.inc                                            * 
*                                                                           * 
*                                                                           * 
*                                                                           *
* History:                                                                  *
* --------                                                                  *
* Date       By                  Comments                                   * 
* ---------- ---------------     --------------------                       *
* 03/02/2001 Angela Roles        Initial Version                            *
* 03/26/2014  Harry Forbess      New credit switch and Accertify Fraud 
* detection                                                            *
****************************************************************************/
require_once( "Accertify.php");
$connect_mssql_db = 1;

class CreditCardPaymentSoap extends SoapClient {

    public $mutliArrayRequest =array();
    private $templateSOAPVar = "
        <ns1:TenderInfo>
            <ns1:cardType>%s</ns1:cardType>
            <ns1:tenderKey>%s</ns1:tenderKey>
            <ns1:cardExpirationDate>%s</ns1:cardExpirationDate>
            <ns1:csc>%s</ns1:csc>
            <ns1:transactionAmount>%s</ns1:transactionAmount>
            <ns1:promoCode>%s</ns1:promoCode>
            <ns1:pinNumber>%s</ns1:pinNumber>
            <ns1:externalId>%s</ns1:externalId>
        </ns1:TenderInfo>
	";

    public function __construct($wsdl, $options = null) {
        parent::__construct($wsdl, $options);
    }

    public function setArrayRequest($requestAyyar){
        // to convert and set the array as string
        $tempVar = "";
        if (!is_array($requestAyyar)){
            return "This is not an array";
        }
        foreach ($requestAyyar as $value) {
            $tempReplace = sprintf($this->templateSOAPVar,  $value["cardType"], 
                                                            $value["tenderKey"], 
                                                            $value["cardExpirationDate"], 
                                                            $value["csc"], 
                                                            $value["transactionAmount"], 
                                                            $value["promoCode"], 
                                                            $value["pinNumber"], 
                                                            $value["externalId"]);
            $tempVar .= $tempReplace;
        }
        $this->mutliArrayRequest = "<ns1:payments>".$tempVar."</ns1:payments>";
    }

    function __doRequest($request, $location, $action, $version, $one_way = NULL) {
        //doRequest
        $request = str_replace("<ns1:payments/>",$this->mutliArrayRequest,$request);
        $ret = parent::__doRequest($request, $location, $action, $version, $one_way = NULL);
        $this->__last_request = $request;
        return $ret;
    }
}

class Payment {

  var $v_order_number;
  var $v_profile_scope;
  var $v_stmt;

  public function __construct() {
    $this->v_order_number = $_SESSION['s_order_number'];
    $this->v_profile_scope = C_WEB_PROFILE;
  }

  public function log_trans_error($p_err_msg) {

    global $mssql_db;

    $v_stmt = mssql_init("reports..gsi_transaction_timeout_log_ins");
 
    $v_err_type = 'CMN SRVC'; 
 
    gsi_mssql_bind($v_stmt, '@p_transaction_type', $v_err_type, 'varchar', 10);
    gsi_mssql_bind($v_stmt, '@p_message', $p_err_msg, 'varchar', 500);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("reports..gsi_transaction_timeout_log_ins", " called from void_insert_trans_err_log in Payment class");
    }

    mssql_free_statement($v_stmt);

  }

  	public function process_payments($p_order_number){
  		global $mssql_db;

	  	$v_stmt= mssql_init("customer.dbo.gsi_get_order_CC_Payment_info");
	
		gsi_mssql_bind($v_stmt, "@p_order_number", $this->v_order_number, 'varchar',50);
		
		gsi_mssql_bind($v_stmt, "@p_address1", $address_line_one, 'varchar', 240, true);
		gsi_mssql_bind($v_stmt, "@p_address2", $address_line_two, 'varchar', 240, true);
		gsi_mssql_bind($v_stmt, "@p_address3", $address_line_three, 'varchar', 240, true);
		gsi_mssql_bind($v_stmt, "@p_city", $city, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_state", $state, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_country", $country, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_zip_code", $zip_code, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_province", $province, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_full_name", $full_name, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_email_address", $e_mail_address, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_payment_amount", $total_amount, 'money', 20, true);
		gsi_mssql_bind($v_stmt, "@p_card_number", $token, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_vendor_gift_certificate_id ", $vendor_gift_certificate_id, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_card_type", $cc_type, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_card_type_id", $cc_type_id, 'varchar', 60, true);
		gsi_mssql_bind($v_stmt, "@p_card_expire_date", $expiry, 'varchar', 20, true);
		// I added this for the GE car promo number 
		gsi_mssql_bind($v_stmt, "@p_internal_tracking_number", $promoCode, 'varchar', 20, true);
		gsi_mssql_bind($v_stmt, "@p_return_status", $return_status, 'varchar', 200, true);
        $v_result = mssql_execute($v_stmt);

        if($v_result) {
			display_mssql_error("customer.dbo.gsi_get_order_CC_Payment_info", "called from function_name() in file_name.php");
		}
		mssql_free_statement($v_stmt);
     //this is a workaround so I dont have to change anything in the database. -- HF
    if( $cc_type == 'AMEX' )
    {
      $cc_type = 'American_Express';
    }
    //GE doesn't come across since its not a real CC
    if ( is_null( $cc_type ) )
    {
       
    	$cc_type = 'GENERAL_ELECTRIC';
    	//this is  the real but secret expiration date  for GEcards
    	//see credit_switch_utilization_fraud_check_3B.docx for explanation
       $p_expiry = '1249';
       $notCreditCard = true;
        
    }
    $i_order = new Order($this->v_order_number);
    
    // here for ISP stores 
    if ($total_amount == 0){
    	if ($i_order->isISPOnly()){
    		return "confirm";
    	}
    		
    }
    
    //this checks to see if it is external or is available in the future
    //this means it requires a 100% deposit.
    //$prebook = $i_order->checkForPrebook();
    $deposit_flag = "N";
    if ( $prebook )
    {
    	$deposit_flag = "Y";
    } 
        //Adding the new csc value to the logic 
        $CVV = $_SESSION["p_pin_number"];
		$today = date ("Y-m-d");
		$p_expiry = str_replace("-","",$p_expiry);
		$v_web_service = WEBSERVICES_URL;
		
		// the general information
		$a_params = array (
		    "orderNumber"=> $p_order_number,
		    "depositFlag" => "N",
		    "cardHoldersName" => $full_name,
		    "streetAddress"=> $address_line_one." ".$address_line_two." ".$address_line_three,
		    "postalCode" => $zip_code,
		    "city"=> $city,
		    "stateProvince" => $province,
		    "country"=> $country,
		    "clientIpAddress"=> $_SESSION["s_remote_address"],
		    "clientEmailAddress" => $e_mail_address,
		    "payments" => ""
		);

		//The payment lines
		 if ( $cc_type == "GENERAL_ELECTRIC" && !is_null($token)){
            // in case it is GE we have to send GE number
            $paymentsArr [] = array ( 
                "cardType" => strtoupper($cc_type),
                "cardExpirationDate" => $p_expiry,
                //"csc"=> $CVV, // no CVV with GE Card
                "tenderKey" => $token,
                "transactionAmount" => $total_amount,
                "promoCode" => $promoCode,
                "externalId" => ""
            );
		 }else if(!is_null($cc_type) && !$notCreditCard){
		    $token = $vendor_gift_certificate_id;
            $paymentsArr [] = array (
                "cardType" => strtoupper($cc_type),
                "tenderKey" => $token,
                "cardExpirationDate" => $expiry,
                "csc"=> $CVV,
                "transactionAmount" => $total_amount,
                "promoCode" => $promoCode,
                "externalId" => ""
            );
		 }
		// to get the payment_method_ids (Gift card and Credit Card)
		$MSsql = "select PAYMENT_ID, PAYMENT_METHOD_ID,PMT_AMOUNT, VENDOR_GIFT_CERTIFICATE_ID, INTERNAL_TRACKING_NUMBER from direct.dbo.golf_payments 
                              where order_number      =  '". $p_order_number ."' 
                              and   payment_method_id in ('CRCARD', 'GECARD', 'GFTCRT','PAYPAL','AMAZON','WIRETR')
                              and   isnull(voided, 'N')  = 'N' 
                     order by payment_method_id ";
		$MSQLResult = mssql_query ( $MSsql );
		while ( $row = mssql_fetch_array ( $MSQLResult ) ) {
			$paymentTypesUsed[]=$row['PAYMENT_METHOD_ID'];
			if ($row['PAYMENT_METHOD_ID'] == 'GFTCRT'){
			    $gv_return_status = 'GFTCRT';
			    $paymentsArr [] = array (
			        "cardType" => "VALUE_LINK",
			        "tenderKey" => $row['VENDOR_GIFT_CERTIFICATE_ID'],
//			        "cardExpirationDate" => "", GiftCard no Expiration Date 
//			        "csc"=> "",    GiftCard no CSC 
			        "transactionAmount" => $row['PMT_AMOUNT'],
			        "pinNumber" => $row['INTERNAL_TRACKING_NUMBER']
//			        "promoCode" => $promoCode //, GiftCard no PromoCode
//			        "externalId" => "" GiftCard no externalId
			    );
			}
		}

		// in case It is paypal checkout I'll return confim (beacuse It is already confirmed in many places)
		if ((in_array('PAYPAL',$paymentTypesUsed)) || (in_array('AMAZON',$paymentTypesUsed)) || (in_array('WIRETR',$paymentTypesUsed))){
		    //in case there GiftCard we will charge them before charge the paypal
		    if(in_array('GFTCRT',$paymentTypesUsed)){
		        try {
		            $v_sclient = new CreditCardPaymentSoap($v_web_service,array("soap_version"  => SOAP_1_1, 'trace' => true));
		            $v_sclient->setArrayRequest($paymentsArr);
		            $credentials = array("UsernameToken" => array("Username" => WEBSERVICE_USERNAME, "Password" => WEBSERVICE_PASSWD ));
		            $security =  new SoapVar($credentials, SOAP_ENC_OBJECT, "Security", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
		            //create header object
		            $header = new SOAPHeader($security->enc_ns, $security->enc_stype, $security, true);
		            $v_sclient->__setSoapHeaders( $header );
		        
		            $result = $v_sclient->DoGolfsmithWebCheckoutTransaction($a_params);
		            if ($result->approvalStatus != "APPROVED"){
		                return "";
		            }
		        } catch (SoapFault $f) {
		            // in case the are error will not call accertify and break the checkout with an error
		            // will not go further
		            mail("webalert@golfsmith.com", "Payment Alert", var_export($f,TRUE)."\n".$v_sclient->__getLastRequest(), $header);
		            $v_return_status = "We are unable to process your transaction at this time.  \n Please contact our customer service department at (800) 813-6897 ";
		            $this->log_trans_error("Transaction Error :" . $p_order_number . ":" . $f->getMessage());
		            return $v_return_status;
		        }
		    }
			return "confirm";
		}
		
		
		// in case there are 2 payment method GiftCard and Credit Card we hav eto set depositFlag to Y
		if ((in_array('CRCARD',$paymentTypesUsed) || in_array('GECARD',$paymentTypesUsed)) && in_array('GFTCRT',$paymentTypesUsed) ){
			$a_params["depositFlag"]="Y";
		}
		//this is for the actual transaction
		// we wall call the CC or GE first becasue if there are any problem in the CC will not withdraw the money from Giftcard
    	if (in_array('CRCARD',$paymentTypesUsed) or in_array('GECARD',$paymentTypesUsed) or in_array('GFTCRT',$paymentTypesUsed)){
			try {
				$v_sclient = new CreditCardPaymentSoap($v_web_service,array("soap_version"  => SOAP_1_1, 'trace' => true));
				$v_sclient->setArrayRequest($paymentsArr);
		        $credentials = array("UsernameToken" => array("Username" => WEBSERVICE_USERNAME, "Password" => WEBSERVICE_PASSWD ));
		        $security =  new SoapVar($credentials, SOAP_ENC_OBJECT, "Security", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
		        //create header object
		        $header = new SOAPHeader($security->enc_ns, $security->enc_stype, $security, true);
		        $v_sclient->__setSoapHeaders( $header );

		        $result = $v_sclient->DoGolfsmithWebCheckoutTransaction($a_params);

			} catch (SoapFault $f) {
				// in case the are error will not call accertify and break the checkout with an error
				// will not go further  
				mail("webalert@golfsmith.com", "Payment Alert", var_export($f,TRUE)."\n".$v_sclient->__getLastRequest(), $header);
				$v_return_status = "We are unable to process your transaction at this time.  \n Please contact our customer service department at (800) 813-6897 ";
      			$this->log_trans_error("Transaction Error :" . $p_order_number . ":" . $f->getMessage());
      			return $v_return_status;
    		}
    	}

		// in case there are any giftcards payment method in direct.dbo.the golf_payments
		// will call the old service 
/* 		if (in_array('GFTCRT',$paymentTypesUsed)){
  	 		try{
      			$gv_web_service = WEBSERVICES_GIFT_URL;
	      		$v_sclient = new SoapClient($gv_web_service, array( 'trace' => true));
    	  		if ($_SESSION['site'] == 'CA') {
        			$v_org_id = '173';
	      		} else {
	    	    	$v_org_id = '25';
    	  		}

				$ga_params = array('pOrderNumber' => $p_order_number, 'pWarehouseId' => $v_org_id);
				$v_res = $v_sclient->ProcessPayments($ga_params);
				$gv_return_status = $v_res->pReturnStatus;
			} catch (Exception $e) {
				$gv_return_status = "We are unable to process your transaction at this time. \n Please contact our customer service department at (800) 813-6897.\n";
	      		write_to_log("process_payments :" . $p_order_number . ":" . $e->getMessage());
    	  		$this->log_trans_error("process_payments :" . $p_order_number . ":" . $e->getMessage());
      			// breake the checkout process if there are any problem with GiftCard 
      			return $gv_return_status;
    		}
    		// if there any issue with the GiftCard results call I'll break it as well
	    	// the defult values are 
			// GFTCRT – gift card on order and processed successfully
			// ISP – gift card on order, but no action taken since in store pickup
			// NONE – no giftcard //covered
			// VOID – gift card on order, but FD had problem and giftcard redeem had to be voided
			// EXCEPTION – some other error in code
			//FALLTHROUGH – did not hit any case somehow! —treat as error
			// anything else – error
	    	if ($gv_return_status == 'GFTCRT' || $gv_return_status == 'ISP'){
	    		$gv_return_status = 'GFTCRT';
	    		// do nothing because everythin is ok 
	    	}else{
	    		//end the function beacuse there are a problem to procss the GiftCard
	    		// It is an error so I'll not call the accertify 
	    		return "";
	    	}

  		} */
    	// in case there are multi tender payment methods Gift Cards and Credit Cards. The decline from the service means they roll back the amount charged 
    	if ($result->approvalStatus != "APPROVED" && $a_params["depositFlag"]=="Y"){
    	    // 
    	    $MSsql = "UPDATE direct.dbo.golf_payments SET VOIDED = 'Y' WHERE order_number = '". $p_order_number ."' and payment_method_id = 'GFTCRT' ";
    	    $MSQLResult = mssql_query ( $MSsql );
    	    if (! $MSQLResult) {
    	        display_mssql_error ( $MSsql, "The Credit Card get declined and we remove all the payments tenders" );
    	    }
    	}
  		// in any case the GE card did not get approve status we will stop the checkout 
  		if ($result->approvalStatus != "APPROVED" && (in_array('GECARD',$paymentTypesUsed) || in_array('CRCARD',$paymentTypesUsed))) {
  			$accertify = new Accertify( $cc_type, $p_expiry, $p_pin_number,$token, $v_mask ,$result->authorizationNumber, "DECLINED" );
  			$accertify->accertify($p_order_number);
  			// reset the Pin number
  			$_SESSION["p_pin_number"]=NULL;
  			return "";
  		}

		if (($result->approvalStatus == "APPROVED" && (in_array('CRCARD',$paymentTypesUsed) || in_array('GECARD',$paymentTypesUsed))) || (in_array('GFTCRT',$paymentTypesUsed) && $gv_return_status == 'GFTCRT'))
		{
	  		// save the authorizationNumber in autho_code in golf_payment
			$auth_stmt= mssql_init("gsi_update_Payment_Auth_Number");
				
			gsi_mssql_bind($auth_stmt, "@p_order_number", $this->v_order_number, 'varchar',50);
			gsi_mssql_bind($auth_stmt, "@p_auth_number", $result->authorizationNumber, 'varchar', 25);
				
			$auth_V_result = mssql_execute($auth_stmt);
				
	  		if($auth_V_result){
				display_mssql_error("direct.dbo.gsi_update_Payment_Auth_Number", "called from process_payments() in payment.php");
			}
			$v_return_status = "confirm";
			// Accertify Code
			$accertify = new Accertify( $cc_type, $p_expiry, $p_pin_number,$token, $v_mask ,$result->authorizationNumber, "APPROVED" );
			// reset the Pin number 
			$_SESSION["p_pin_number"]=NULL;
		}else{
			// Accertify Code
			$accertify = new Accertify( $cc_type, $p_expiry, $p_pin_number,$token, $v_mask ,$result->authorizationNumber, "DECLINED" );
		}
		// Make the SOAP call 
		$accertify->accertify($p_order_number);
		
    	return $v_return_status;
  	}

  public function auth_payment($p_order_number, &$p_ret_status) {
  	$p_ret_status = $this->process_payments($p_order_number);

    if($p_ret_status !="confirm") {
      $p_ret_status = 'Failed to Process Payments: ' . $p_ret_status;

      return false;

    }
    return true;
  }

  public function add_payment($p_amount, $p_payment_method, $p_cc_num, $p_gc_num, $p_gc_pin, $p_expiry, $p_card_type_id = '', $p_days_auth_valid = '', $p_gc_note = '', $p_save_cc_info = null,$p_pin_number =null) {
  	global $mssql_db;

    $this->v_stmt = mssql_init("gsi_cmn_payments_update_payment2");

    //these are inputs to every call
    gsi_mssql_bind($this->v_stmt, "@p_profile_scope", $this->v_profile_scope, 'varchar', 50);
    gsi_mssql_bind($this->v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 20);
    gsi_mssql_bind($this->v_stmt, "@p_payment_amount", $p_amount, 'gsi_amount_type');
    gsi_mssql_bind($this->v_stmt, "@p_payment_method_id", $p_payment_method, 'varchar', 6);

    if(!empty($p_cc_num)) {
	 if ($p_payment_method=="CRCARD"){
	 	$number = $p_cc_num;
		$v_mask = preg_replace('/\d/i', "*", substr($number, 0, 4)) . str_repeat("*", strlen($number) - 8) . substr($number, -4); 
      	gsi_mssql_bind($this->v_stmt, "@p_card_number", $v_mask, 'varchar', 20);
	 }else{
	 	gsi_mssql_bind($this->v_stmt, "@p_card_number", $p_cc_num, 'varchar', 20);
	 }
      gsi_mssql_bind($this->v_stmt, "@p_expiry", $p_expiry, 'varchar', 10);
      // Remove the CVV number for PCI compliance
      $p_pin_number = NULL;
      gsi_mssql_bind($this->v_stmt, "@p_pin_number",$p_pin_number,'varchar',15);

      if(!empty($p_gc_num)) {
        gsi_mssql_bind($this->v_stmt, "@p_vendor_gift_certificate_id", $p_gc_num, 'varchar', 20);
      }
 
      if(!empty($p_card_type_id)) { //if we have card type, days auth valid applies as well
        gsi_mssql_bind($this->v_stmt, "@p_credit_card_type_id", $p_card_type_id, 'gsi_id_type');
        gsi_mssql_bind($this->v_stmt, "@p_days_auth_valid", $p_days_auth_valid, 'gsi_id_type');
      }

      gsi_mssql_bind($this->v_stmt, "@p_save_cc_info", $p_save_cc_info, 'char', 1); 

    } else if(!empty($p_gc_num)) {

      gsi_mssql_bind($this->v_stmt, "@p_gift_certificate_id", $p_gc_num, 'varchar', 50);
      gsi_mssql_bind($this->v_stmt, "@p_gift_certificate_pin", $p_gc_pin, 'varchar', 10);

      gsi_mssql_bind($this->v_stmt, "@p_notes", $p_gc_note, 'varchar', 40);

    } else { //else it's a wire transfer; no other bindings needed
      $p_payment_method = 'WIRETR';
    }

    gsi_mssql_bind($this->v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_update_payment($p_payment_method)", "called from add_payment() in class Payment");
    }

    mssql_free_statement($this->v_stmt);

    return $v_return_status;
  }

  public function add_paypal_payment($p_amount, $p_authorization_id = FALSE) {

    global $mssql_db;

    $this->v_stmt = mssql_init("gsi_cmn_payments_update_paypal_payment");

    mssql_bind($this->v_stmt, "@p_profile_scope", $this->v_profile_scope, SQLVARCHAR, false, false, 50);
    mssql_bind($this->v_stmt, "@p_order_number", $this->v_order_number, SQLVARCHAR, false, false, 20);
    mssql_bind($this->v_stmt, "@p_payment_amount", $p_amount, SQLFLT8, false, false);

    if(!empty($p_authorization_id)) {
      mssql_bind($this->v_stmt, "@p_auth_decline_message", $p_authorization_id, SQLVARCHAR, false, false, 50);
    }

    mssql_bind($this->v_stmt, "@p_return_status", $v_return_status, SQLVARCHAR, true, false, 200);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_update_paypal_payment", "called from add_paypal_payment() in payment_processor");
    }

    mssql_free_statement($this->v_stmt);

    return $v_return_status;

  }

  //if $p_gc == 'Y', only gift card payments will be voided
  //if $p_gc == 'N', everything except gift card payments will be voided
  public function void_payments($p_gc = 'N') {
    global $mssql_db;

    $this->v_stmt = mssql_init("gsi_cmn_payments_void_payments");

    gsi_mssql_bind($this->v_stmt, "@p_profile_scope", $this->v_profile_scope, 'varchar', 50);
    gsi_mssql_bind($this->v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($this->v_stmt, "@p_gc", $p_gc, 'varchar', 1);
  
    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_void_payments", "called from void_payments() in payment_processor");
    }

    mssql_free_statement($this->v_stmt);

  }

  public function voidWireTransferPayments() {

    global $mssql_db;

    $this->v_stmt = mssql_init("gsi_cmn_payments_void_wiretr_payments");

    gsi_mssql_bind($this->v_stmt, "@p_profile_scope", $this->v_profile_scope, 'varchar', 50);
    gsi_mssql_bind($this->v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_void_wiretr_payments", "called from voidWireTransferPayments() in Payment.php");
    }

    mssql_free_statement($this->v_stmt);
    mssql_free_result($v_result);

  }

  public function remove_paypal_payments() {
    global $mssql_db;

    $this->v_stmt = mssql_init("gsi_cmn_payments_remove_paypal_pmt");

    mssql_bind($this->v_stmt, "@p_order_number", $this->v_order_number, SQLVARCHAR, false, false, 50);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_remove_paypal_pmt", "called from remove_paypal_payments() in payment_processor");
    }

    mssql_free_statement($this->v_stmt);

  }

  public function void_paypal_payments($p_error_code) {

    global $mssql_db;

    $this->v_stmt = mssql_init("gsi_cmn_payments_void_paypal_pmt");

    gsi_mssql_bind($this->v_stmt, "@p_profile_scope", $this->v_profile_scope, 'varchar', 50);
    gsi_mssql_bind($this->v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($this->v_stmt, "@p_auth_decline_message", $p_error_code, 'varchar', 50);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_void_paypal_pmt", "called from void_paypal_payments() in payment_processor");
    }

    mssql_free_statement($this->v_stmt);

  }

}

?>
