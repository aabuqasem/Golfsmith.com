<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This is the class for Accertify Fraud Check. Relies on ExtendedSoap.php
 
 * 
 *
 * PHP version 5


 * @author     Original Author harry forbess <harry.forbess@golfsmith.com> <hforbess@gmail.com>

 *
 */
 require_once("ExtendedSoap.php");
 class Accertify
 {
	private $order_number;
	private $order_type;
	private $order_date;
	private $total_order_amount;
	private $transaction_currency;
	private $shipping_charges;
	private $sales_tax;
	private $sales_rep;
	private $source_code;
	private $oracle_customer_number;
	private $gs_customer_number;
	private $customer_membership_date;
	private $customer_first_name;
	private $customer_last_name;
	private $billing_first_name;
	private $billing_last_name;
	private $billing_address1;
	private $billing_address2;
	private $billing_city;
	private $billing_state;
	private $billing_postal_code;
	private $billing_country;
	private $billing_phone_number;
	private $billing_email_address;
	private $shipping_first_name;
	private $shipping_last_name;
	private $shipping_address1;
	private $shipping_address2;
	private $shipping_city;
	private $shipping_state;
	private $shipping_postal_code;
	private $shipping_country;
	private $shipping_phone_number;
	private $shipping_email_address;
	private $shipping_method;
	private $shipping_deadline;
	private $trans_armor_token;
	private $masked_card_number;
	private $card_type_description;
	private $expiry;
	private $pin;
	private $note_giftmessage;
	private $internet_purchase_registered_member;
	private $internet_purchase_guest;
	private $phone_purchase;
	private $ip_address;
	private $sql;
	private $soap_arr;
	private $paymentStatus;
	public $result;
	
	public function __set( $field, $value )
	{
		$this->field = $value;
	}
	
	public function __get( $field )
	{
		return $this->field;
	}
	
	// Here is ths Accerify Constructor 
	// The Paramters
	// $p_card_type_desc  the CC type
	// $p_expiry the CC expire date
	// $p_pin_number CC pin number 
	// $v_token the transarmorToken of the CC 
	// $v_mask the transarmorToken of the CC 
	// $authorization_number Checkout Authorization Number  
	// $approvalStatus the process order stauts 
	public function __construct( $p_card_type_desc, $p_expiry, $p_pin_number, $v_token, $v_mask, $authorization_number ,$approvalStatus )
	{
		global $mssql_db; 
		$this->order_number = $_SESSION['s_order_number'];
		$this->trans_armor_token = $v_token;
		$this->masked_card_number = $v_mask;
		$this->card_type_description = $p_card_type_desc;
		$this->expiry = $p_expiry;
		$this->pin = $p_pin_number;
		$this->ip_address = $_SERVER['REMOTE_ADDR'];
       //took out a bunch of fields that were not being used.
       //THis is for general information, price,tax, etc. 
		$this->sql = "SELECT 
                     hia.original_system_reference					
                    ,hia.order_type									
                    ,hia.date_ordered								
                    ,hia.attribute8									
                    ,hia.currency_code
                    ,'' as shipping_charges
                    ,'' as sales_tax
                    ,'' as sales_rep
                    ,hia.attribute15
                    ,'' as oracle_customer_number --leave blank
                    ,hia.customer_id
                    ,inv.CREATION_DATE as customer_membership_date
                    ,SUBSTRING(hia.customer_name, 1, CHARINDEX(' ', hia.customer_name) - 1) AS first_name
                    ,SUBSTRING(hia.customer_name, CHARINDEX(' ', hia.customer_name) + 1, 8000) AS last_name
                    ,hia.ship_method_code
                    ,'shipping_deadline'
                    ,'giftmessage'
                    ,'N' --Always no for phone_purchase

                    FROM 
                    direct..gsi_headers_interface_all hia
                    ,direct..gsi_lines_interface_all lia
                    ,customer..gsi_ra_customers inv
                    ,customer..gsi_ra_addresses_all invadd
                    ,customer..gsi_ra_customers ship
                    ,customer..gsi_ra_addresses_all shipadd
                    WHERE 1=1
                    AND hia.original_system_reference = lia.original_system_reference
                    AND hia.customer_id = inv.customer_id
                    AND hia.invoice_address_id = invadd.address_id
                    AND hia.ship_address_id = shipadd.address_id
                    AND hia.ship_to_customer_id = ship.customer_id
                    AND hia.original_system_reference = '$this->order_number'";


		$sql_db = mssql_connect(SQL_SERVER,SQL_USER,SQL_PASSWORD);
		//$mssql_db = mssql_select_db(SQL_DB, $sql_db);
    
		$rs = mssql_query ( $this->sql,$sql_db );
		$query_result = mssql_fetch_assoc( $rs );

		$this->order_type = $query_result['order_type'];
		// attribute8 has all the totals in pipe delimited field
		$total = explode( "|", $query_result["attribute8"] );

 		$this->total_order_amount = $total[4];
 		$this->sales_tax = $total[3];
 		$this->shipping_charges = $total[2];
 		$this->shipping_method = $query_result['ship_method_code'];
 		$this->gs_customer_number = $query_result['customer_id'];

		$date_arr = date_parse_from_format( "M d Y h:iA", $query_result["customer_membership_date"] );

 		$year = $date_arr["year"];
 		$month = str_pad( $date_arr["month"], 2, '0', STR_PAD_LEFT);
 		$day = str_pad( $date_arr["day"] , 2, '0', STR_PAD_LEFT);
 		$formatted_date = $year. "-" . $month . "-". $day;
 		$this->customer_membership_date = $formatted_date;
 		$this->customer_first_name = $query_result['first_name'];
 		$this->customer_last_name = $query_result['last_name'];
		//get the order lines
 		$sql = "SELECT lia.inventory_item_id,
					gid.*,
					lia.* 
				FROM direct..gsi_lines_interface_all lia,
				r12pricing..gsi_item_detail gid
				WHERE 1=1
				AND lia.inventory_item_id = gid.inventory_item_id
				AND lia.original_system_reference =  '$this->order_number'";

		$mssql_db = mssql_select_db(SQL_DB, $sql_db);
		$arr = array();
		$rs = mssql_query ( $sql,$sql_db );
		while ( $row =  mssql_fetch_assoc( $rs ))
		{
			$arr[] = $row;
		}
		//took out some debugging.
  		$order_lines = array();
  
		foreach( $arr as $line )
		{
			$sku = $line["SKU"];
			$item_price = $line["ITEM_PRICE"];
			$item_quantity = $line["ORDERED_QUANTITY"]; 
			$item_description = $line['DESCRIPTION'];
			$brand_name = $line['BRAND'];
			$item_category_class = $line['CATEGORY_NAME'];
			$web_category = $line['WEB_CATEGORY_ID'];

			$order_lines[] = array
			(
				"ItemSku" => "$sku",
				"ItemPrice" => "$item_price",
				"ItemQuantity" => "$item_quantity",
				"ItemTax" => "0.00",//need tax calc. probably won't need this later.
				"ItemDescription" => "$item_description",
				"BrandName" => "$brand_name",
				"ItemCategoryClass" => "$item_category_class",
				"ItemCategorySubClass" => "",
				"WebCategory" => "$web_category",
				"SalesRep" => "",
			);
  		}   
		//code cleaned up significantly
		// this retrieves all the payment lines
 
		$sql = "SELECT *
				FROM direct..golf_payments 
				WHERE order_number = '$this->order_number'
				AND isnull(voided, 'N') = 'N'
				AND payment_method_id != 'GFTCRT'";
				/*
				 * I removed the next condition from the quesry to get the GECard as well 
				AND payment_method_id = 'CRCARD'
				*/

		$rs = mssql_query ( $sql,$sql_db ) or die( "error"); 
		if ( mssql_num_rows( $rs ) < 1 )
		{
			write_to_log("Accertify Error there are no golf_payment rows to this order in direct DB: ". $this->order_number);
		}
		$payment_lines = array();
		$arr = array();
		while( $line = mssql_fetch_assoc( $rs ) )
		{
			$payment_type_code = $line['CREDIT_CARD_TYPE_ID'];
			$payment_amount = $line['PMT_AMOUNT'];
			$payment_date = date("Y-m-d",strtotime($line['CREATION_DATE']));
			$expiry = $line['EXPIRY'];
			$masked_card_number = $line['CARD_NUMBER'];

			if ($this->card_type_description == "GENERAL_ELECTRIC"){
				$expiry = "1249";
			}
			$payment_lines[] = array
				(
					"PaymentTypeCode" => "CREDIT",
                  	"PaymentStatus" => "$approvalStatus",
					"TransArmorToken" => $this->trans_armor_token,
					"PaymentAmount" => "$payment_amount",
					"PaymentDate" => "$payment_date",
					"CardType" => "$this->card_type_description",
					"CardExpirationDate" => "$expiry",
				); 
		}   

		//checks to see if gift cards are being used. 
		$sql = "SELECT *
			FROM direct..golf_payments 
			WHERE order_number = '$this->order_number' 
			AND payment_method_id = 'GFTCRT'
			AND isnull(voided, 'N') = 'N'";
    
		$rs = mssql_query( $sql ) or die( "sql error line ". __LINE__ ." File:".__FILE__);
		while( $obj = mssql_fetch_object( $rs ))
		{
			$payment_lines[] = array
			(  
				"PaymentTypeCode" => "GIFT",
				"PaymentAmount" => $obj->PMT_AMOUNT,
				"PaymentStatus" => "$approvalStatus",
				"TransArmorToken" => $obj->VENDOR_GIFT_CERTIFICATE_ID,
				"PaymentDate" => date("Y-m-d",strtotime($obj->CREATION_DATE)),  	
			);   	
    	}
		//shipping and billing addresses
		$v_ship_address_id = $_SESSION['s_ship_address_id'];

		$i_address = new Address($_SESSION['s_customer_id'], $v_ship_address_id, $_SESSION['s_ship_contact_id'], $_SESSION['s_ship_phone_id']);
		$i_address->getAddressFields($v_ship_is_international, $va_ship_address);
		$i_address->setAll($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_bill_phone_id']);
		$i_address->getAddressFields($v_bill_is_international, $va_bill_address);

		$contacts = array();
    	$key_use = "Billing";
		$contacts[$key_use] = array
			(
				"$key_use"."FirstName" => $va_bill_address["first_name"],
				"$key_use"."LastName" =>  $va_bill_address["last_name"],
				"$key_use"."PhoneNumber" => $va_bill_address["phone"],
				"$key_use"."EmailAddress" => $_SESSION["s_user_name"], 
				"$key_use"."AddressLine1" => $va_bill_address["line1"],
				"$key_use"."AddressLine2" => $va_bill_address["line2"],
				"$key_use"."City" =>  $va_bill_address["city"],
				"$key_use"."State" =>  $va_bill_address["state"],
				"$key_use"."PostalCode" =>  $va_bill_address["postal_code"],
				"$key_use"."Country" =>  $va_bill_address["country"]
			);

		$key_use = "Shipping";
		$contacts[$key_use] = array
			(
				"$key_use"."FirstName" => $va_ship_address["first_name"],
				"$key_use"."LastName" =>  $va_ship_address["last_name"],
				"$key_use"."PhoneNumber" => $va_ship_address["phone"],
				"$key_use"."EmailAddress" => $_SESSION["s_user_name"], 
				"$key_use"."AddressLine1" => $va_ship_address["line1"],
				"$key_use"."AddressLine2" => $va_ship_address["line2"],
				"$key_use"."City" =>  $va_ship_address["city"],
				"$key_use"."State" => $va_ship_address["state"],
				"$key_use"."PostalCode" =>  $va_ship_address["postal_code"],
				"$key_use"."Country" =>  $va_ship_address["country"]
			);
		/*		
		foreach ( $site_uses as $key_use => $value )
		{
			$address_id = $_SESSION["s_".$value."_address_id"];
			$sql = "SELECT phone_id,
                       area_code,
                       phone_number
                     FROM customer..gsi_ra_phones 
                     WHERE address_id = " . $address_id . "
                     and status = 'A'";

			$res = mssql_query($sql) or die( "sql error on ". __LINE__ . " " . __FILE__ );
			$phone = mssql_fetch_assoc( $res );
			$phone_number =  $phone['area_code']. $phone['phone_number'];

       
 			$contact_id = $_SESSION["s_".$value."_contact_id"];
			$sql = "SELECT  *
                  FROM customer..gsi_ra_contacts  rc
                  INNER JOIN customer..gsi_ra_addresses_all ra ON rc.address_id = ra.address_id
                  WHERE rc.contact_id   = $contact_id";


			$res = mssql_query($sql) or die( "sql error on ". __LINE__ . " " . __FILE__ );
			$contact = mssql_fetch_object( $res );

			$contacts[$key_use] = array
				(
					"$key_use"."FirstName" => "$contact->FIRST_NAME",
					"$key_use"."LastName" =>  "$contact->LAST_NAME",
					"$key_use"."PhoneNumber" => "$phone_number",
					"$key_use"."EmailAddress" => "$contact->EMAIL_ADDRESS", 
					"$key_use"."AddressLine1" => "$contact->ADDRESS1",
					"$key_use"."AddressLine2" => "$contact->ADDRESS2",
					"$key_use"."City" =>  "$contact->CITY",
					"$key_use"."State" =>  "$contact->STATE",
					"$key_use"."PostalCode" =>  "$contact->POSTAL_CODE",
					"$key_use"."Country" =>  "$contact->COUNTRY"
				);
    	}*/
   		//Build an array representing the xml. it requires a mulitdimensional array. We can't repeat keys so we add them in the 
		//ExtendedSoap with simple_xml_elements.
		$today = date("c"); 
		$IPAddress = $this->getClientIP();
		if ((empty($v_customer_number)) || (!isset( $_COOKIE['CloseEvent']))){
			$RegisteredUser="N";
		}else{
			$RegisteredUser="Y";
		}
		$soap_arr = array
		(
			"request" => array
			(
				"AccertifyTransaction" => array
				(
					"OrderTransaction" => array
					(
						"OrderNumber" => "$this->order_number",
						"OrderDate" => "$today",
						"OrderTransactionType" => "REGULAR",
						"OrderCurrency"=> "USD",
						"ShippingMethod" => "$this->shipping_method",
						"ShippingFee" => "$this->shipping_charges",
						"ShippingDeadline" => '2016-10-10',
						"GolfsmithCustomerId" => "$this->gs_customer_number",
						"TotalOrderAmount" => "$this->total_order_amount",
						"TotalTax" => $this->sales_tax,
						"RegisteredUser" => "Y",

						"OrderLines"  => array
						(
                                                                                               
						),
						"SalesRep" => "WEB",
						
						"PaymentLines" => array
						(

						),
						"CustomerMembershipDate" => "$this->customer_membership_date",
						"CustomerFirstName" => "$this->customer_first_name",
						"CustomerLastName"=> "$this->customer_last_name",
					),
				"OriginatingSystem" => "GOLFSMITH_WEB",
				"IPAddress" => "$IPAddress",	
				)
			)
		);

		$soap_arr["request"]["AccertifyTransaction"]["OrderTransaction"]["OrderLines"]= $order_lines;
    	$soap_arr["request"]["AccertifyTransaction"]["OrderTransaction"]["PaymentLines"]= $payment_lines;
    	$soap_arr["request"]["AccertifyTransaction"]["OrderTransaction"]=array_merge($soap_arr["request"]["AccertifyTransaction"]["OrderTransaction"], $contacts["Billing"],$contacts["Shipping"]);
		$this->soap_arr = $soap_arr;
   	}
   	// here will make the accetify call 
   	
   	public function accertify($p_order_number){
		global $mssql_db;
		
		// The SOAP call 
		try
		{
			$v_sclient = new SoapClient(ACCERTIFY_SERVICES_URL,array("soap_version"  => SOAP_1_1, 'trace' => true));
			$credentials = array("UsernameToken" => array("Username" => WEBSERVICE_USERNAME, "Password" => WEBSERVICE_PASSWD ));
			$security =  new SoapVar($credentials, SOAP_ENC_OBJECT, "Security", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
			//	create header object
			$header = new SOAPHeader($security->enc_ns, $security->enc_stype, $security, true);
			$v_sclient->__setSoapHeaders( $header );
			// in case the total amount is zero we will pass this call and send ACCEPT 

			if ($this->soap_arr["request"]["AccertifyTransaction"]["OrderTransaction"]["TotalOrderAmount"] > 0){ 
    			$result = $v_sclient->DoTransaction( $this->soap_arr );
  
	    		// get the SOAP results and updat the
    			$this->result =  $result->DoTransactionResult->AccertifyTransactionResult->FraudCheckStatus;
    			$fraud_check_result = $this->result;
			}else{
				$fraud_check_result = 'ACCEPT';
			}
			// save the accertify results in autho_code in golf_payment
			$accertify_stmt= mssql_init("direct.dbo.gsi_update_payment_accertify");

			gsi_mssql_bind($accertify_stmt, "@p_order_number", $p_order_number, 'varchar',50);
			gsi_mssql_bind($accertify_stmt, "@p_accertify", $fraud_check_result, 'varchar', 25);
			gsi_mssql_bind($accertify_stmt, "@o_return_status", $accertify_V_result, 'varchar', 200, true);
			$accertify_result = mssql_execute($accertify_stmt);

			if($accertify_V_result == "failed")
  			{
				//display_mssql_error("direct.dbo.gsi_update_payment_accertify", "called from Accertify() in Accertify.php"."The paramter:".var_export($this->soap_arr,TRUE));
                display_mssql_error("direct.dbo.gsi_update_payment_accertify", "called from Accertify() in Accertify.php");
			}
	 
    		$status_array=array('ACCEPT','REJECT','REVIEW','ERROR');

    		if ($this->result == "ERROR" || !in_array($this->result,$status_array)){
    			//write_to_log("Accertify Error ". var_export($v_sclient->__getLastRequest(),TRUE)." The results:".var_export($this->result,TRUE)." The paramters:".var_export($this->soap_arr,TRUE)." The order#:".var_export($this->order_number,TRUE));
                write_to_log("Accertify Error ". $v_sclient->__getLastRequest()." The results:".$this->result." Order#:".$p_order_number );
    		} 
    	}
		catch (SoapFault $e )
		{
			// in any case the result status will be ERROR
		    $fraud_check_result = "ERROR";
		    $accertify_stmt= mssql_init("direct.dbo.gsi_update_payment_accertify");
		    
		    gsi_mssql_bind($accertify_stmt, "@p_order_number", $p_order_number, 'varchar',50);
		    gsi_mssql_bind($accertify_stmt, "@p_accertify", $fraud_check_result, 'varchar', 25);
		    gsi_mssql_bind($accertify_stmt, "@o_return_status", $accertify_V_result, 'varchar', 200, true);
		    $accertify_result = mssql_execute($accertify_stmt);
		    
		    if($accertify_V_result == "failed")
		    {
		        //display_mssql_error("direct.dbo.gsi_update_payment_accertify", "called from Accertify() in Accertify.php"."The paramter:".var_export($this->soap_arr,TRUE));
		        display_mssql_error("direct.dbo.gsi_update_payment_accertify", "called from Accertify() in Accertify.php; Order#:".$p_order_number);
		    }
		    
			mail("webalert@golfsmith.com", "Accertify Alert", var_export($e,TRUE)."\n".$v_sclient->__getLastRequest(), $header);
			$this->result =  "ERROR";
			write_to_log("Accertify Error call:". $v_sclient->__getLastRequest()." Error Msg:". $e->getMessage()." Order#:".$p_order_number);
    	}
    	
   	}

 	public function getClientIP() {

    	if (isset($_SERVER)) {
	        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
	        	$ips = explode (",",trim($_SERVER["HTTP_X_FORWARDED_FOR"]));
	        	if (is_array($ips))
	        		return trim($ips[0]); 
	        	else 
	        		return $_SERVER["HTTP_X_FORWARDED_FOR"];
	        }
    	        

        	if (isset($_SERVER["HTTP_CLIENT_IP"]))
            	return $_SERVER["HTTP_CLIENT_IP"];

        	return $_SERVER["REMOTE_ADDR"];
    	}

    	if (getenv('HTTP_X_FORWARDED_FOR'))
    		$ips = explode (",",trim(getenv('HTTP_X_FORWARDED_FOR')));
	        	if (is_array($ips))
	        		return trim($ips[0]); 
	        	else 
		        	return getenv('HTTP_X_FORWARDED_FOR');

    	if (getenv('HTTP_CLIENT_IP'))
        	return getenv('HTTP_CLIENT_IP');

    	return getenv('REMOTE_ADDR');
	}
 }
 
 ?>
