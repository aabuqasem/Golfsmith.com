<?

include_once('Payment.php');

class CreditCardPayment extends Payment {

  public function __construct() {
    parent::__construct();
  }
// adding new parameter for CC type come from the User interface
	public function add_payment($p_amount, $p_cc_num, $p_expiry, $p_is_golfsmith_cc, $p_save_cc_info = 'N',$p_pin_number,$p_cc_type) {
	
		//validate card number
    	if(!empty($p_cc_num)) {
    		if($p_is_golfsmith_cc != 1 ) {
		        //-1 if failed, 0 otherwise
    		    // here validate against the number format not the auth of the CC
        		$v_validation = $this->validate_card_number($p_cc_num, $v_card_type_id, $v_days_auth_valid, $p_card_type_desc);
        		// I overwrite the $p_card_type_desc by the value come from the user 
        		// we will stop use any internal check point in case we change it we just remove the next line 
        		$p_card_type_desc = strtoupper($p_cc_type);
        		// to pickup the right CC type I force to logic ides as next to refer to the same cc ides in the r12pricing.dbo.gsi_cc_issuer_ranges table
        		$creditCardTypes = array (
        			'VISA' => '1',
        			'MASTERCARD'=> '101',
        			'AMEX'=> '201',
        			'DISCOVER'=>'401'
        		);
        		$v_card_type_id = $creditCardTypes[$p_card_type_desc];
    		} else {
        		$v_validation = $this->validate_ge_number($p_cc_num);
      		}
    	} else {  //empty card number should always fail validation
      		$v_validation = -1;
    	}
    	//check validation here; invalid is -1
    	if($v_validation != -1) {
	      
            $v_payment_method = 'CRCARD';

            if ( $p_is_golfsmith_cc == 1 )
            {
				// I added here a new paramter for the $p_deferment (it sent through $p_expiry but now we need to send it to the encrypted service)
				// I will get the value before it is overwritten 
				$p_deferment = $p_expiry ;
            	$v_payment_method = 'GECARD';
            	//this is the real pin for GECARDS
                //see credit_switch_utilization_fraud_check_3B.docx for explanation
                $p_pin_number = '100';
                $p_card_type_desc = "GENERAL_ELECTRIC";
                //this is the real expiry date for GE cards
                //see credit_switch_utilization_fraud_check_3B.docx for explanation
                $p_expiry = "1249" ;
            }
			// check if the Token in the row or not
			if ($v_payment_method == 'CRCARD' ){
				global $mssql_db;
				// for PCI we save the @p_pin_number as null. We stell need $p_pin_number for encrypt_credit_card function
				$temp_pin_number = NULL;
				
				$lastFourDigits = substr($p_cc_num,-4,4);
				$testExpiry = str_replace("-","",$p_expiry);
	    		$this->v_stmt = mssql_init("gsi_validate_order_payment");
				
    			gsi_mssql_bind($this->v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    			gsi_mssql_bind($this->v_stmt, "@p_last4_card_number", $lastFourDigits, 'varchar', 50);
    			gsi_mssql_bind($this->v_stmt, "@p_expiry", $testExpiry, 'varchar', 50);
    			gsi_mssql_bind($this->v_stmt, "@p_pin_number", $temp_pin_number, 'varchar', 50);
    			gsi_mssql_bind($this->v_stmt, "@p_return_status", $c_return_status, 'varchar', 200, true);

    			$v_result = mssql_execute($this->v_stmt);

    			if ($c_return_status == 1){
    				return ;
    			}
			}
    		//
			$_results = $this->encrypt_credit_card($p_cc_num,$p_card_type_desc, $p_expiry, $p_pin_number,$v_token, $v_mask,$p_deferment);
			//fraud check moved to Payment.php
			if ($_results == "success")
			{
	   
			    if ( $p_is_golfsmith_cc == 1 )
                   {
                 // send the defergment as promocode
                      parent::add_payment($p_amount, $v_payment_method, $p_cc_num, '', '', $p_deferment);
    	              
                   }
                   else 
                   {
                     //this is for a regular CC
                      parent::add_payment($p_amount, $v_payment_method, $v_mask, $v_token, '', $p_expiry, $v_card_type_id, $v_days_auth_valid, '', $p_save_cc_info,$p_pin_number); 
                   }

	  		 }
	  		 else
	  		 {
	  			$v_return_status = $_results;
	  	     }

		}
		else
		{
			$v_return_status = 'Invalid Card Number';
		}
		
    	return $v_return_status;
	}

  public function checkSavedCard($p_customer_id, &$p_token_id, &$p_masked_cc, &$p_cc_type, &$p_expiry, &$p_days_auth_valid) {
    global $mssql_db;

    $this->v_stmt = mssql_init("gsi_cmn_payments_get_cc_info");

    gsi_mssql_bind($this->v_stmt, "@p_customer_id", $p_customer_id, 'gsi_id_type');
    gsi_mssql_bind($this->v_stmt, "@p_token_id", $p_token_id, 'varchar', 20, true);
    gsi_mssql_bind($this->v_stmt, "@p_masked_cc", $p_masked_cc, 'varchar', 20, true);
    gsi_mssql_bind($this->v_stmt, "@p_cc_type", $p_cc_type, 'gsi_id_type', -1, true);
    gsi_mssql_bind($this->v_stmt, "@p_expiry", $p_expiry, 'varchar', 10, true);
    gsi_mssql_bind($this->v_stmt, "@p_days_auth_valid", $p_days_auth_valid, 'gsi_id_type', -1, true);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_get_cc_info", "called from checkSavedCard() in CreditCardPayment.php");
    }

    mssql_free_statement($this->v_stmt);
    mssql_free_result($v_result);

  }

  public function addPaymentFromSavedCard($p_amount, $p_token_id, $p_masked_cc, $p_cc_type, $p_expiry, $p_days_auth_valid) {

    $v_payment_method = 'CRCARD';

    //everything should already be encrypted and validated
    $v_return_status = parent::add_payment($p_amount, $v_payment_method, $p_masked_cc, $p_token_id, '', $p_expiry, $p_cc_type, $p_days_auth_valid);

    return $v_return_status;

  }

  public function updateCreditCardAmount($p_amount) {
    global $mssql_db;

    $this->v_stmt = mssql_init("gsi_cmn_payments_update_crcard_payment");

    gsi_mssql_bind($this->v_stmt, "@p_order_number", $this->v_order_number, 'varchar', 50);
    gsi_mssql_bind($this->v_stmt, "@p_payment_amount", $p_amount, 'gsi_amount_type');
    gsi_mssql_bind($this->v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_update_crcard_payment", "called by update_credit_card_amount() in payment_processor");
    }

    mssql_free_statement($this->v_stmt);
  }

  	// add the new paramter the defragment for promocode
	public function encrypt_credit_card($p_cc_num,$p_card_type_desc, $p_expiry, $p_pin_number,&$v_token, &$v_mask,$p_deferment) {
	    global $_SESSION;
		$name_on_CC = $_SESSION['s_first_name']." ".$_SESSION['s_last_name'];
		// I have here to remove all the dashes 
		$today = date ("Y-m-d");
		$p_expiry = str_replace("-","",$p_expiry);
		$v_web_service = WEBSERVICES_URL;
        //the $p_card_type_desc is done from a lookup in the db. I am doing a workaround here  so I dont have to change the database -HF/
        if ( $p_card_type_desc ==  'AMEX'  ){
			$p_card_type_desc = 'AMERICAN_EXPRESS' ;
		}
	//
		$a_params = array ("orderNumber"=> $_SESSION["s_order_number"],
					"cardType"=> strtoupper($p_card_type_desc),
					"cardNumber"=> $p_cc_num,
					"cardHoldersName"=> $name_on_CC,
					"cardExpirationDate"=> $p_expiry,
					"csc"=> $p_pin_number,
					"clientIpAddress"=> $_SESSION["s_remote_address"],
					"clientEmailAddress"=> $_SESSION['s_user_name'],
	     			"promoCode"=> $p_deferment	); // I need to send this to the CC service with GE card
	    try{
			// start to soap call to verfy the CC number with CSC
			$v_sclient = new SoapClient($v_web_service, array( 'trace' => true));
			$credentials = array("UsernameToken" => array("Username" => WEBSERVICE_USERNAME, "Password" => WEBSERVICE_PASSWD ));
			$security =  new SoapVar($credentials, SOAP_ENC_OBJECT, "Security", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
			//create header object
        	$header = new SOAPHeader($security->enc_ns, $security->enc_stype, $security, true); 
        	$v_sclient->__setSoapHeaders( $header );	    	
 	    	$result = $v_sclient->DoGolfsmithWebVerification($a_params);
        	//$this->log_trans_error($result->approvalStatus."verification" );
	    } catch (SoapFault $f) {
            $v_return_status = "We are unable to process your transaction at this time. \n Please contact our customer service department at (800) 813-6897 ";
            $v_orderNum = $_SESSION["s_order_number"];            
            //$last = $v_sclient->__getLastRequest();
            $this->log_trans_error("failed verfication  :" . $v_orderNum . ":" . $f->getMessage());
      		//The asked us to send differnt msg in case the CC is down 
			exit("Payment: ".$v_return_status);
    	}         
	  	// I commented the next line beacuse there are change in the production 
    	//if ($result->approvalStatus == "APPROVED" && ( $result->cscResult == "MATCH"  or $result->cscResult == 'NOT_PARTICIPATING') ){
	  	if ($result->approvalStatus == "APPROVED"){
	  		$v_mask = $result->transarmorToken; 
	  		$v_token = $result->transarmorToken;
	  		$v_return_status = "success";
	  		// When the CC is approved we save the pin number in the session till the checkout
	  		$_SESSION["p_pin_number"]=$p_pin_number;
  		}else{
  			$v_return_status = "Invalid Card Number";
  			$_SESSION["p_pin_number"] = NULL;
  		}
	 		

    	return $v_return_status;
	}
  
  public function validate_card_number($p_cc_num, &$p_card_type_id, &$p_days_auth_valid, &$p_card_type_desc) {
    //we may move some of this validation to PHP later; right now we just call the DB proc
    //v_credit_card_type_desc and p_credit_card_type code aren't needed by the update_payment code, so
    // they're currently just being discarded
    
  	/* I'll remove the next code beasue we decide to stop use this proc and just use luhan mod10 algorithems 
  	global $mssql_db;
	
    $this->v_stmt = mssql_init("golf_credit_card_validate_card_number");

    gsi_mssql_bind($this->v_stmt, "@p_credit_card_number", $p_cc_num, 'varchar', 20);

    gsi_mssql_bind($this->v_stmt, "@p_credit_card_type_desc", $p_card_type_desc, 'varchar', 20, true);
    gsi_mssql_bind($this->v_stmt, "@p_credit_card_type_code", $p_card_type_code, 'varchar', 2, true);
    gsi_mssql_bind($this->v_stmt, "@p_credit_card_type_id", $p_card_type_id, 'gsi_id_type', -1, true);
    gsi_mssql_bind($this->v_stmt, "@p_days_auth_valid", $p_days_auth_valid, 'gsi_id_type', -1, true);
    gsi_mssql_bind($this->v_stmt, "@p_return_status", $v_return_status, 'gsi_id_type', -1, true);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("golf_credit_card_validate_card_number", "called from validate_card_number() in class payment_processor");
    }

    mssql_free_statement($this->v_stmt);

    return $v_return_status;
    */
  	// We will use the next default value in case you want to change it it is up to the new design 
  	$p_card_type_id = 1;
  	$p_days_auth_valid= 21;
  	$p_card_type_desc = "Luhn_mod10";
  	return $this->luhn_check($p_cc_num);
  }

  public function validate_ge_number($p_cc_num) {
    global $mssql_db;

    $this->v_stmt = mssql_init("gsi_cmn_payments_validate_gs_card_number");

    gsi_mssql_bind($this->v_stmt, "@p_credit_card_number", $p_cc_num, 'varchar', 20);
    gsi_mssql_bind($this->v_stmt, "@p_return_status", $v_return_status, 'int', -1, true);

    $v_result = mssql_execute($this->v_stmt);

    if(!$v_result) {
      display_mssql_error("gsi_cmn_payments_validate_gs_card_number", "called from validate_ge_number() in class payment_processor");
    }

    mssql_free_statement($this->v_stmt);

    return $v_return_status;
  }
  
	public function get_ge_deferment_disclosure($p_ge_cc_num, $p_ge_promo_code, $p_ge_tot_amt_due, &$p_ofp_language, &$p_status) {

		$name_on_CC = $_SESSION['s_first_name']." ".$_SESSION['s_last_name'];
		$v_web_service = WEBSERVICES_URL;
		$v_sclient = new SoapClient($v_web_service, array( 'trace' => true));
		$credentials = array("UsernameToken" => array("Username" => WEBSERVICE_USERNAME, "Password" => WEBSERVICE_PASSWD ));
		$security =  new SoapVar($credentials, SOAP_ENC_OBJECT, "Security", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
		$header = new SOAPHeader($security->enc_ns, $security->enc_stype, $security, true); 
		$v_sclient->__setSoapHeaders( $header );
	        
		$a_params = array ("orderNumber"=> $_SESSION["s_order_number"],
						"cardType"=> "GENERAL_ELECTRIC",
						"cardNumber"=> $p_ge_cc_num,
						"cardHoldersName"=> $name_on_CC,
						"cardExpirationDate"=> "1249" ,
						"csc"=> "100",
						"clientIpAddress"=> $_SESSION["s_remote_address"],
						"clientEmailAddress"=> $_SESSION['s_email'],
		     			"promoCode"=> $p_ge_promo_code	);
		try{
			$result = $v_sclient->DoGolfsmithWebVerification($a_params);
			
	        //$this->log_trans_error($result->approvalStatus."verification" );
		} catch (SoapFault $f) {
			$v_return_status = "Error with credit card service";
			$v_orderNum = $_SESSION["s_order_number"];
			$last = $v_sclient->__getLastRequest();
			$this->log_trans_error("failed verfication  :" . $v_orderNum . ":" . $f->getMessage());
		}

		if ($result->approvalStatus == "APPROVED" ){
			$v_mask = $result->transarmorToken; 
			$v_token = $result->transarmorToken;
			$p_ofp_language = $result->promoDetail;
			$p_status = "SUCCESS";
			$_SESSION["p_pin_number"]=$p_pin_number;
		}else{
			$v_return_status = "Invalid Card Number";
			$_SESSION["p_pin_number"] = NULL;
		}

		return $v_return_status;
	}
	
	public function luhn_check($number) {
		// Strip any non-digits (useful for credit card numbers with spaces and hyphens)
		$number=preg_replace('/\D/', '', $number);
		// Set the string length and parity
		$number_length = strlen($number);
		$parity = $number_length % 2;
		// Loop through each digit and do the maths
		$total = 0;
		for ($i=0; $i<$number_length; $i++) {
			$digit=$number[$i];
			// Multiply alternate digits by two
			if ($i % 2 == $parity) {
				$digit*=2;
				// If the sum is two digits, add them together (in effect)
				if ($digit > 9) {
					$digit-=9;
				}
			}
			// Total up the digits
			$total+=$digit;
		}
		// If the total mod 10 equals 0, the number is valid
		return ($total % 10 == 0) ? 0 : -1;
	}
	
}

?>
