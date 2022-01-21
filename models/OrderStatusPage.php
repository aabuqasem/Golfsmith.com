<?php
require_once('Zend/View.php');

class OrderStatusPage{

	public  $orderNumber;
	public  $customerId;
	public  $orderDetailLevel;
	public  $postalCode;
	public  $daysBack;
	public  $loggedIn;		
    private $wsdl;
    private $client;

    public function __construct($wsdl)
    {
     	
            $this->wsdl = $wsdl;
            $options = array(
            		'location' => ORDER_STATUS_ENDPOINT_LOCATION,
            		'soap_version' => SOAP_1_1,
            		'exceptions'   => true,
            		'trace'        => 1,
            		'cache_wsdl'   => WSDL_CACHE_NONE,
            		'style'        => SOAP_DOCUMENT,
            		'use'          => SOAP_LITERAL
            		);
      
    try { 
    	$this->client = @new SoapClient($this->wsdl, $options);                        
    } catch (SoapFault $E) {
      	print $E->faultstring;
      }
	$username = ORDER_STATUS_USER;
	$password = ORDER_STATUS_PASSWORD;

	//set security credentials
	$this->credentials = array("UsernameToken" => array("Username" => $username, "Password" => $password));
	$security = new SoapVar($this->credentials, SOAP_ENC_OBJECT, "Security", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");	                                                                          
	//create header object
	$this->header = new SOAPHeader($security->enc_ns, $security->enc_stype, $security, true);
	//set the soap header
    $this->client->__setSoapHeaders($this->header);
    }
      
    public function doRequest($arr,$tagRequest){
    $request = $arr;
    try {
     $result = $this->client->__soapCall("process", array($tagRequest => $request), null, null);
    } catch (SoapFault $e) {
    	//print $e->faultstring;
        mail("webalertsapps@golfsmith.com", "Order Status Web Service Error", $e,
        "From: GSI - Webadmin <dont.reply@golfsmith.com>\n");
        $result = "error";            
      }
            return $result;
     }
      
   
	// set Order Number 
	public function setOrderNumber($orderNumber)
	{
		$this->orderNumber = $orderNumber;
	}
	
	// set Customer Id
	public function setCustomerId($customerId)
	{
		$this->customerId = $customerId;
	}

	// set Order Detail Level (FULL/HEADER)
	public function setOrderDetailLevel($orderDetailLevel)
	{
		$this->orderDetailLevel = $orderDetailLevel;
	}

	// set postal Code
	public function setPostalCode($postalCode)
	{
		$this->postalCode = $postalCode;
	}

	// set days Back (30 days to show recent orders/90 days to show all orders)
	public function setDaysBack($daysBack)
	{
		$this->daysBack = $daysBack;
	}
	
	// set Customer Logged in Status
	public function setloggedIn($loggedIn)
	{
		$this->loggedIn = $loggedIn;
	}
	
	public function getNullToEmpty($value)
	{
		if ($value == null) $value="";
		return $value;	
	}
	
	// function to get the Order's Header only when the customer is not logged in
	public function getOrderHeader() {   		
		$request = array("orderNumber" => $this->orderNumber, "orderDetailLevel" => $this->orderDetailLevel, "postalCode" => $this->postalCode);				
        $result = $this->doRequest($request, "roRequest");        
        $results = array();
		$results["headers"] = array();
		if ($result != "error") {        
        $headercounter = 0;       
        if (sizeof($result->orders->Order->header) > 0) {
        	foreach ($result->orders->Order as $order){
          		if (!empty($order->externalOrderNumber)){        
        			$results["resultStatus"]= "ok";
        			$results["headers"][$headercounter]["orderNumber"]= $order->externalOrderNumber;
        			$v_date = date_create($order->OrderedDate);				    												            	         	
            		$results["headers"][$headercounter]["DatePlaced"] = date_format($v_date, 'm-d-Y');
            		if($order->shipDate=="N/A") {
						$results["headers"][$headercounter]["DateShiped"] = "N/A";
            		} else {
					if($order->shipDate=="MULTIPLE") {
						$results["headers"][$headercounter]["DateShiped"] = "MULTIPLE";
					} else {
							$v_date = date_create($order->shipDate);				    			
							$results["headers"][$headercounter]["DateShiped"] =  date_format($v_date, 'm-d-Y');
					}
					}				 				            	                   	            	
            		$results["headers"][$headercounter]["Status"] = $order->Status;
          			$results["headers"][$headercounter]["TrackingNumber"] = $order->waybill;
          			$results["headers"][$headercounter]["OrderTotal"] = $order->OrderTotal;   
          			$headercounter++; 				        	
          		}    		      		
         	} 
        } else 	{ 
        		$results["resultStatus"]= "empty"; 
		}
		} else {
				$results["resultStatus"]= "WebServiceError";				
		}        
			   	header('Content-type: application/json');
  		    	return (json_encode($results));
	        	exit();			 
	}
	
	// function to get the Orders's Header when the customer is logged in
	public function getOrdersHeaders()
	{   
		$request = array("customerId" => $this->customerId, "orderDetailLevel" => $this->orderDetailLevel, "daysBack" => $this->daysBack);
        $result = $this->doRequest($request, "roRequest");          
        $results = array();  		        
		$results["headers"] = array();
		if ($result != "error") {        
        	$headercounter = 0;     
        	if (sizeof($result->orders->Order) > 0) {             
				// when it has one line this will convert the object to array
        		if (!is_array($result->orders->Order)) {
        		$result->orders->Order = array($result->orders->Order);
        		}			
        		foreach ($result->orders->Order as $order){
        			foreach ($order as $header){
						if (!empty($header->externalOrderNumber)){
        					$results["resultStatus"]= "ok";
        					$results["headers"][$headercounter]["orderNumber"]= $header->externalOrderNumber;
        					$v_date = date_create($header->OrderedDate);				    												            	         	
            				$results["headers"][$headercounter]["DatePlaced"] = date_format($v_date, 'm-d-Y');				
            				if($header->shipDate=="N/A") {
								$results["headers"][$headercounter]["DateShiped"] = "N/A";						
            				} else {
								if($header->shipDate=="MULTIPLE") {
									$results["headers"][$headercounter]["DateShiped"] = "MULTIPLE";
								} else {
									$v_date = date_create($header->shipDate);				    			
									$results["headers"][$headercounter]["DateShiped"] =  date_format($v_date, 'm-d-Y');
								}
							}				 				            	       
          					$results["headers"][$headercounter]["Status"] = $header->Status;
          					$results["headers"][$headercounter]["TrackingNumber"] = $header->waybill;
          					$results["headers"][$headercounter]["OrderTotal"] = $header->OrderTotal;   
          					$headercounter++; 				        	
          				}         
        			}    		      		
        		}
        	} else { 
        		$results["resultStatus"]= "empty"; 
          	}
		} else {
				$results["resultStatus"]= "WebServiceError";
		}        	
		header('Content-type: application/json');
  		return (json_encode($results));
	    exit();			         
	}
	
	public function getTrackingURL($ship_method,$Tracking_Number){	                
		$shipped_via_ups = ( substr(strtoupper($ship_method),0,3) == 'UPS');
		$shipped_via_fedex = ( substr(strtoupper($ship_method),0,5) == 'FEDEX');
		$v_waybill_list = explode( "|", $Tracking_Number);
		$waybill_count = 0;
		$TrackingURL = "";
		$show_waybill_count = (sizeof( $v_waybill_list) > 1);
		foreach ($v_waybill_list as $v_waybill) {
			++$waybill_count;
			//Tracking #=( $show_waybill_count ? $waybill_count : ''):&nbsp;
			if ($shipped_via_fedex) { 
				$TrackingURL = "http://www.fedex.com/Tracking?=&tracknumbers=".$v_waybill."&track.x=43&track.y=11";					
			}	else if ($shipped_via_ups) { 
					$TrackingURL = "http://wwwapps.ups.com/etracking/tracking.cgi?tracknums_displayed=5&TypeOfInquiryNumber=T&HTMLVersion=4.0&InquiryNumber1=".$v_waybill."&track.x=43&track.y=11>=".$v_waybill;					
				} else { 
						$TrackingURL=$v_waybill;					 
				}				
		}
		return $TrackingURL;
		exit();
	}
		
	// function to get the Orders's details when the user clicks on the Order No.	
	public function getOrderDetails()
	{	
		$request = array("orderNumber" => $this->orderNumber, "orderDetailLevel" => $this->orderDetailLevel);	            
        $result = $this->doRequest($request, "roRequest");       	
        $results = array(); 	
        if (sizeof($result->orders->Order->header) > 0) {
        	foreach ($result->orders->Order as $order){  
            	$header = $order;      		
        		if (!empty($header->externalOrderNumber)){        
        			$results["resultStatus"]= "ok";
            		$results["orderNumber"] = $header->externalOrderNumber;
          			$results["ShipMethodCode"] = $header->ShippingMethodCode;
					$results["FullName"] = $header->CustomerAccount->Party->PersonFirstName." ".$header->CustomerAccount->Party->PersonLastName;    	          									
					$results["Address1"] = $header->CustomerAccount->ShipToAddress->Address1;
					$results["Address2"] = $header->CustomerAccount->ShipToAddress->Address2;
					$results["Address2"] = $this->getNullToEmpty($results["Address2"]); 
					$results["CityStateZip"] = $header->CustomerAccount->ShipToAddress->City.", ".$header->CustomerAccount->ShipToAddress->State." ".$header->CustomerAccount->ShipToAddress->PostalCode;				
					$results["Phone"] = $header->CustomerAccount->Party->PrimaryPhoneAreaCode."-".$header->CustomerAccount->Party->PrimaryPhoneNumber;											
					$results["TrackingURL"] = $this->getTrackingURL($header->ShippingMethodCode,$header->waybill);
					$results["details"] = array();
					$itemcounter = 0;
					// when it has one line this will convert the object to array
                	if (!is_array($result->orders->Order->lines->line)) {
                    	$result->orders->Order->lines->line = array($result->orders->Order->lines->line);
                	}				
					foreach ($result->orders->Order->lines->line as $line){ 								
						if (!empty($line->styleNumber)){						       
							$results["details"][$itemcounter]["StyleNumber"] = $line->styleNumber;
							$results["details"][$itemcounter]["StyleDescription"] = $line->StyleDescription;
			    			$v_date = date_create($line->PromiseDate);				    											
							$results["details"][$itemcounter]["PromiseDate"] = date_format($v_date, 'm-d-Y');
		        			$results["details"][$itemcounter]["OrderedQuantity"] = $line->OrderedQuantity;
		        			$results["details"][$itemcounter]["UnitSellingPrice"] = $line->UnitSellingPrice;
							if($line->ShipDate=="N/A"){
								$results["details"][$itemcounter]["ShipDate"] = "N/A";
							} else {
								$v_date = date_create($line->ShipDate);				    			
								$results["details"][$itemcounter]["ShipDate"] = date_format($v_date, 'F d Y g:ia');
							}				 				            	            	            			        
		        			$results["details"][$itemcounter]["TrackingNumber"] = $line->Waybill;
		        			$results["details"][$itemcounter]["TrackingURL"] = $this->getTrackingURL($results["ShipMethodCode"],$line->Waybill);
		        			$results["details"][$itemcounter]["Status"] = ucfirst( strToLower($line->Status));
		        			$results["details"][$itemcounter]["DeliveryId"] = $line->DeliveryId;
		        			$results["details"][$itemcounter]["DeliveryId"] = $this->getNullToEmpty($results["details"][$itemcounter]["DeliveryId"]);		        
							$v_style_number = $line->styleNumber;
		        			$v_seo_obj = new seo($v_style_number);
							$v_url = $v_seo_obj->get_seo_url();
							$results["details"][$itemcounter]["ItemURL"] = $v_url;
		        			$itemcounter++;		               			
						}			
				}
          			header('Content-type: application/json');          		
  		    		echo (json_encode($results));
	        		exit();
          		}      		      		
        	}
		}	
	}	
}
?>
