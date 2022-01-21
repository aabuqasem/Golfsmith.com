<?php 

/**
 * THis extends soap_client so the seveal arrays passed in, 
 * maniuplated for elements with the same name which can not be represented in a multi dimensional
 * associative array
 * 
 *
 * PHP version 5
 * @author     Original Author Hosam Mahmoud hosam.mahmoud@golfsmith.com and hosamsad@Hotmail.com 
 *
 */
//include 'gsi_db.inc';
require_once('gsi_env.inc');

class ATPDecrementExtendedSoap extends SoapClient {

	public $mutliArrayRequest =array();
	private $templateSOAPVar = "<ns1:DecrementReqInput>
            <ns1:Inventory_Item_Id>%s</ns1:Inventory_Item_Id>
            <ns1:Organization_Id>25</ns1:Organization_Id>
            <ns1:Quantity>%s</ns1:Quantity>
            <!--Optional:-->
            <ns1:Sales_Channel_Code>GS.COM</ns1:Sales_Channel_Code>
         </ns1:DecrementReqInput>
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
			$tempReplace = sprintf($this->templateSOAPVar, $value["INVENTORY_ITEM_ID"], $value["QUANTITY"]);
			$tempVar .= $tempReplace; 
		}
		$this->mutliArrayRequest = "<ns1:DecrementReq>".$tempVar."</ns1:DecrementReq>"; 
	} 
	
	function __doRequest($request, $location, $action, $version) {
      	//doRequest
      	$request = str_replace("<ns1:DecrementReq/>",$this->mutliArrayRequest,$request);

      	return parent::__doRequest($request, $location, $action, $version);
	}
}
class ATPExtendedSoap extends SoapClient {

	public $mutliArrayRequest =array();
	private $templateSOAPVar = "<ns1:ATPReqInput>
				<ns1:SALES_CHANNEL_CODE>GS.COM</ns1:SALES_CHANNEL_CODE>
				<ns1:INVENTORY_ITEM_ID>%s</ns1:INVENTORY_ITEM_ID>
				<ns1:ORGANIZATION_ID>25</ns1:ORGANIZATION_ID>
				<ns1:QUANTITY>%s</ns1:QUANTITY>
			</ns1:ATPReqInput>";
	
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
			$tempReplace = sprintf($this->templateSOAPVar, $value["INVENTORY_ITEM_ID"], $value["QUANTITY"]);
			$tempVar .= $tempReplace; 
		}
		$this->mutliArrayRequest = "<ns1:ATPRequest>".$tempVar."</ns1:ATPRequest>"; 
	} 
	
	function __doRequest($request, $location, $action, $version) {
      	//doRequest
      	$request = str_replace("<ns1:ATPRequest/>",$this->mutliArrayRequest,$request);
      	return parent::__doRequest($request, $location, $action, $version);
	}
	
	// the next function to get the ATP from Procedure 
	public static function loadDB(){
	    global $webpubsql_db,$webpubsql_db;
	    $webpubsql_db = mssql_connect(WEBPUB_SQL_SERVER, WEBPUB_SQL_USER, WEBPUB_SQL_PASSWORD);
	    mssql_min_message_severity(15);
	    if ($webpubsql_db){
	        $webpub_mssql_db = mssql_select_db(WEBPUB_SQL_DB, $webpubsql_db) ;
	        if (! $webpub_mssql_db){
	            $strError = mssql_get_last_message();
	            write_to_log($usrip . ": WEBPUB SQL Server DB select failed : " . $strError);
	        }
	    } else {
	        $strError = mssql_get_last_message();
	        write_to_log($usrip . ": WEBPUB SQL Server DB not available : " . $strError);
	    }
	}
	
	
	public static function checkATPProc($atp_arr,$type = "SingleATP"){
    
	    $webpubsql_db = mssql_connect(WEBPUB_SQL_SERVER, WEBPUB_SQL_USER, WEBPUB_SQL_PASSWORD);
	    mssql_min_message_severity(15);
	    if ($webpubsql_db){
	        $webpub_mssql_db = mssql_select_db(WEBPUB_SQL_DB, $webpubsql_db) ;
	        if (! $webpub_mssql_db){
	            $strError = mssql_get_last_message();
	            write_to_log($usrip . ": WEBPUB SQL Server DB select failed : " . $strError);
	        }
	    } else {
	        $strError = mssql_get_last_message();
	        write_to_log($usrip . ": WEBPUB SQL Server DB not available : " . $strError);
	    }
	    
       // global $connect_webpub_mssql_db,$connect_mssql_db,$webpub_mssql_db,$webpubsql_db;
	    $itemsDTS = "";
        if (count($atp_arr) == 1){
            $itemsDTS = $atp_arr[0]["iiv"].","."25";
        }else{
            foreach ($atp_arr as  $key => $value){
                $itemsDTS .= $value["iiv"].",25,";
            }
        }
        $itemsDTS = trim($itemsDTS,",");
        $v_stmt = mssql_init("gsi_ATP_Check",$webpubsql_db);
        gsi_mssql_bind($v_stmt, "@CSVList", $itemsDTS, 'varchar', 50);
        gsi_mssql_bind($v_stmt, "@p_return_status", $v_process_status, 'varchar', 500, true);

        $v_ATP_result = mssql_execute($v_stmt) ;
        if (!$v_ATP_result){
            display_mssql_error("gsicatp.dbo.gsi_ATP_Check " . $v_return_status , "call from ATPExtendedSoap.php");
        }
        if (mssql_num_rows( $v_ATP_result ) > 0) {
            $returnFlag = "N";
            while ($v_row = mssql_fetch_assoc($v_ATP_result)) {
                $key = $v_row["INVENTORY_ITEM_ID"];
                $ATPresults[$key]["status"] = "VALID";
                $ATPresults[$key]["CATP_ITEM_INFO_ID"] = $v_row["CATP_ITEM_INFO_ID"];
                $ATPresults[$key]["STYLE_ID"] = $v_row["STYLE_ID"];
                $ATPresults[$key]["INVENTORY_ITEM_ID"] = $v_row["INVENTORY_ITEM_ID"];
                $ATPresults[$key]["ORGANIZATION_ID"] = $v_row["ORGANIZATION_ID"];
                $ATPresults[$key]["WEB_EFFECTIVE_DATE"] = $v_row["WEB_EFFECTIVE_DATE"];
                $ATPresults[$key]["GT_WEB_EFFECTIVE_DATE"] = $v_row["GT_WEB_EFFECTIVE_DATE"];
                $ATPresults[$key]["AVAILABILITY_DATE"] = $v_row["AVAILABILITY_DATE"];
                $ATPresults[$key]["EMBARGO_DATE"] = $v_row["EMBARGO_DATE"];
                $ATPresults[$key]["DROPSHIP_FLAG"] = $v_row["DROPSHIP_FLAG"];
                $ATPresults[$key]["PREPROCESSING_LT"] = $v_row["PREPROCESSING_LT"];
                $ATPresults[$key]["PROCESSING_LT"] = $v_row["PROCESSING_LT"];
                $ATPresults[$key]["POSTPROCESSING_LT"] = $v_row["POSTPROCESSING_LT"];
                $ATPresults[$key]["VENDOR_NAME"] = $v_row["VENDOR_NAME"];
                $ATPresults[$key]["VENDOR_SITE"] = $v_row["VENDOR_SITE"];
                $ATPresults[$key]["VENDOR_ADDRESS1"] = $v_row["VENDOR_ADDRESS1"];
                $ATPresults[$key]["VENDOR_ADDRESS2"] = $v_row["VENDOR_ADDRESS2"];
                $ATPresults[$key]["VENDOR_CITY"] = $v_row["VENDOR_CITY"];
                $ATPresults[$key]["VENDOR_STATE"] = $v_row["VENDOR_STATE"];
                $ATPresults[$key]["VENDOR_COUNTRY_CODE"] = $v_row["VENDOR_COUNTRY_CODE"];
                $ATPresults[$key]["VENDOR_ZIPCODE"] = $v_row["VENDOR_ZIPCODE"];
                $ATPresults[$key]["Total_Quantity"] = $v_row["Total_Quantity"];
                $ATPresults[$key]["lines"][] = array("ATP_STATUS"=>$v_row["ATP_STATUS"],
                                            "PROMISED_DATE"=>$v_row["PROMISED_DATE"],
                                            "CATP_QTY"=>$v_row["CATP_QTY"],
                                            "CATP_TYPE"=>$v_row["CATP_TYPE"],
                                            "DECREMENT_PRIORITY"=>$v_row["DECREMENT_PRIORITY"]);

            } // end while
        }else{
            $ATPresults["status"] = "NOT_FOUND";
        } 	
    	// I have to use the next one to close the current connection
        //var_dump(__LINE__,$ATPresults);
        if ($type == "SingleATP"){
            $ATPresults = $ATPresults[$key];
            if ( $ATPresults["status"] == "VALID"){
                $return["status"]	= "VALID";
                $ATPResultSet		= ATPExtendedSoap::ATPPromisedDatesMSGProc($ATPresults,$qty);
                if ($ATPResultSet["status"]=="INVALID"){
                    $return["status"]	= "INVALID";
                }
                $return["MSG"]		= $ATPResultSet["msg"];
                $return["OrderedQTY"]	= $qty;
                $return["totalQTY"]	=	$ATPresults["Total_Quantity"];
                $return["promisedDate"] = $ATPResultSet["promisedDate"];
                $return["invID"] = $inv_item_id;
                $return["embargo_date"] = $ATPresults["EMBARGO_DATE"];
            }else{
                
                $return["status"] = "INVALID";
            }
        }else if($type == "Shoppingcart") {
            foreach ($atp_arr as $key=>$value){
                $TempATPArr[$value["iiv"]] = $value["qty"];
            }
            if (is_array($ATPresults)){
                foreach ($ATPresults as $key=>$value) {
                    $ATPkey = $value["INVENTORY_ITEM_ID"];
                    $ATPresults[$ATPkey]=ATPExtendedSoap::ATPPromisedDatesMSGProc($value,$TempATPArr[$ATPkey]);
                }
            }
            $return = $ATPresults;
        }
        mssql_close($webpubsql_db);
        if (!$mssql_db) {
            $sql_db = mssql_connect(SQL_SERVER, SQL_USER, SQL_PASSWORD);
            mssql_min_message_severity(15);
            if ($sql_db){
                $mssql_db = mssql_select_db(SQL_DB, $sql_db) ;
                if (! $mssql_db){
                    $strError = mssql_get_last_message();
                    write_to_log($usrip . ": SQL Server DB select failed : " . $strError);
                }
            } else {
                $strError = mssql_get_last_message();
                write_to_log($usrip . ": SQL Server DB not available : " . $strError);
            }
        }
        return $return;
    }
    
    
	public static function checkATP($inv_item_id,$qty){
		global $mssql_db ; 
		//$SOAPURL =ATPSOAPSERVICE_URL;
		$SOAPURL = dirname(__FILE__) . '/GetATPQuantity.wsdl';
		//$SOAPURL = "http://devwlssoa1.gsicorp.com:8001/soa-infra/services/Common/GetATPQuantity/getatpqtybpelcomponent_client_ep?WSDL";
		//$SOAPURL = "http://devwlssoa2.gsicorp.com:8001/soa-infra/services/Common/GetATPQuantity/getatpqtybpelcomponent_client_ep?WSDL";
		// temp SOAP URL have to remoe the next line 
		//$SOAPURL = "http://devsoacluster.gsicorp.com:7777/soa-infra/services/default/GetATPQuantity/getatpqtybpelcomponent_client_ep?WSDL";

		$options = array(
                  "soap_version" => SOAP_1_1,
                  "exceptions"   => true,
                  "trace"        => 1,
                  "cache_wsdl"   => WSDL_CACHE_NONE,
                  "style"        => SOAP_DOCUMENT,
                  "use"          => SOAP_LITERAL
                  );
		
		$soap_arr = array ("ATPReqInput"=>array ('SALES_CHANNEL_CODE'=>'GS.COM',
  							'INVENTORY_ITEM_ID'=>$inv_item_id,
  							'ORGANIZATION_ID'=>25,
  							'QUANTITY'=>$qty
  							));
    	try{
			$v_sclient = new SoapClient($SOAPURL,$options);
	   		$results = $v_sclient->process( $soap_arr );
	   		$result = $results->ATPOutput->MESSAGE_STATUS;
   			// get the SOAP results and updat the
	    	if ( $result == "VALID"){
    			$return["status"]	= "VALID"; 
	   			$ATPResultSet		= ATPExtendedSoap::ATPPromisedDatesMSG($results,$qty);
	   			if ($ATPResultSet["status"]=="INVALID"){
	   				$return["status"]	= "INVALID"; 
	   			} 
    			$return["MSG"]		= $ATPResultSet["msg"];		
    			$return["OrderedQTY"]	= $qty;
    			$return["totalQTY"]	=	$results->ATPOutput->TOTAL_QUANTITY;
    			$return["promisedDate"] = $ATPResultSet["promisedDate"];
    			$return["invID"] = $inv_item_id;
    		}else{
    			$return["status"] = "INVALID";
    		}
		}catch (SoapFault $e ){
			$p_warehouse_id = 25;
			$p_clear = "Y";
			//If there an error we will call the Tables diectly
			$v_sp = mssql_init("direct.dbo.gsi_cmn_atp_global_atp");
			gsi_mssql_bind ($v_sp,"@p_item_id", $inv_item_id, "bigint", 20, false);
			gsi_mssql_bind ($v_sp,"@p_warehouse_id", $p_warehouse_id, "int", 20, false);
			gsi_mssql_bind ($v_sp,"@p_clear", $p_clear, "varchar", 20, false);
			gsi_mssql_bind($v_sp,"@o_total_qty",$total_qty,"varchar",50,true);        
			gsi_mssql_bind($v_sp,"@o_promise_date",$promise_date,"varchar",50,true);

			$v_result = mssql_execute($v_sp);
			if (!$v_result){
			    echo "2";
				display_mssql_error2("direct.dbo.gsi_cmn_atp_global_atp" . $msg, "call from ATPExtendedSoap.php");
			}
			mssql_free_statement($v_sp);
			mssql_free_result($v_result);
			// prepare the rsponse 
			$return["status"]	= "OLDCODE";
			$return["totalQTY"]	= $total_qty;
			$return["promise_date"]	= $promise_date;
			// we will just send an email in the decrement issues
			mail("webalert@golfsmith.com", "ATP Alert", var_export($e,TRUE));
			write_to_log("ATP Error ". $e->getMessage());
    	}
    	return $return;
	}
	
	public static function ATPPromisedDatesMSGProc($SOAPResponseObj, $orderedQTY){
	    // get all the dates of the Object
	    //var_dump($SOAPResponseObj);
	    $WebEffectiveDate = strtotime($SOAPResponseObj["WEB_EFFECTIVE_DATE"]);
	    $AvailabilityDate = strtotime ($SOAPResponseObj["AVAILABILITY_DATE"]);
	    $EmbargoDate = strtotime ($SOAPResponseObj["EMBARGO_DATE"]);
	    $DropShipFlag = $SOAPResponseObj["DROPSHIP_FLAG"];
	    $PreProcessingLeadTime = $SOAPResponseObj["PREPROCESSING_LT"];
	    $ProcessingLeadTime = $SOAPResponseObj["PROCESSING_LT"];
	    $PostProcessingLeadTime = $SOAPResponseObj["POSTPROCESSING_LT"];
	    $MessageStatus = $SOAPResponseObj["status"];
	    

	    if ($MessageStatus != "VALID"){
	        //in case the MESSAGE_STATUS is not valid break it here
	        $return["status"] = "INVALID";
	        return $return;
	    }
	    $totalTempAvalCount = 0;
	
	    $TOTAL_QUANTITY = $SOAPResponseObj["Total_Quantity"];
	    if (count($SOAPResponseObj["lines"]) == 1){
	        $promisedDate = strtotime($SOAPResponseObj["lines"]["0"]["PROMISED_DATE"]);
	        $totalTempAvalCount = $SOAPResponseObj["lines"]["0"]["CATP_QTY"];
	        $statusArray[] = array("status"=>$SOAPResponseObj["lines"]["0"]["ATP_STATUS"], "PromisedDate"=>$promisedDate) ;
	    }else{
	        // I loop through the obecjt to get the newer date
	        $tempPromisdeDate = time();

	        foreach ($SOAPResponseObj["lines"] as &$value) {
	            // in case we the ordered quintyt is fullfilled stop the loop
	            // I have to get here the order quantity here to stop the loop as well
	            if ( strtotime($value["PROMISED_DATE"]) >= $tempPromisdeDate ){
	                $promisedDate = strtotime($value["PROMISED_DATE"]);
	            }else{
	                $promisedDate = $tempPromisdeDate;
	            }
	            $totalTempAvalCount += $value["CATP_QTY"];
	            if ($orderedQTY <= $totalTempAvalCount ){
	                $statusArray []= array ("status"=>$value["ATP_STATUS"],"PromisedDate"=>$promisedDate);
	                break;
	            }else{
	                $statusArray []= array ("status"=>$value["ATP_STATUS"],"PromisedDate"=>$promisedDate);
	            }
	
	        }
	    }
	
	    // in case the $AvailabilityDate in the future
	    if ($WebEffectiveDate > time()){
	        //in case the MESSAGE_STATUS is not valid break it here
	        $return["status"] = "INVALID";
	        return $return;
	    }
	    // in case we don't have enugh in the stock
	    if ($totalTempAvalCount < $orderedQTY ){
	        $return["msg"] = "The quantity you order is more than we have in stock";
	        $return["promisedDate"] = NULL;
	        	
	    }
	    $return["promisedDate"] = $promisedDate ;
	    $return["inventoryID"] = $SOAPResponseObj["INVENTORY_ITEM_ID"] ;
	    $return["totalQTY"] = $TOTAL_QUANTITY;
	    $return["ResponseStatus"] = $SOAPResponseObj["status"];
	    
	    
	    $return["embargo_date"] = $EmbargoDate;
	    
	    

	    // The Messages logic
	    if ($EmbargoDate >= $promisedDate ){
	        $return["msg"] = "Currently on order. Expected ship date ".date("d-M-Y",$EmbargoDate)." .";
	        $return["promisedDate"] = $EmbargoDate;
	    }else{
	        // extrenal item
	        if ($DropShipFlag == "Y"){

	            $return["shipStatus"]="DropShip";
	            $totalLeadTime = $PreProcessingLeadTime + $ProcessingLeadTime + $PostProcessingLeadTime;
	            
	            //Check availability  date
	            $totalLeadAndToday = strtotime('+' . $totalLeadTime . ' days', time());

    	            
	           if( $totalLeadAndToday < $AvailabilityDate ){
				    $return["msg"] = "Item ships directly from the manufacturer; Product expected to ship " . date("d-M-Y", $AvailabilityDate);
				}else{
				   
                    
    				if ($totalLeadTime <= 5){
    					$return["msg"] = "Item ships directly from the manufacturer; Product normally ships in 3-5 business days.";
    					$return["priority"] = 1;
    					$return["promisedDate"] = time() + 5*24*60*60 ;
    				}elseif ($totalLeadTime <= 12){
    					$return["msg"] = "Item ships directly from the manufacturer; Product normally ships in 1-2 weeks.";
    					$return["priority"] = 2;
    					$return["promisedDate"] = time() + 14*24*60*60;
    				}elseif ($totalLeadTime <= 18){
    					$return["msg"] = "Item ships directly from the manufacturer; Product normally ships in 2-3 weeks.";
    					$return["priority"] = 3;
    					$return["promisedDate"] = time() + 21*24*60*60 ;
    				}elseif ($totalLeadTime <= 29){
            		    $return["msg"] = "Currently on order.<br />Please allow 3-4 weeks for delivery.";
            		    $return["priority"] = 4;
            		}else{
    					$return["msg"] = "Item ships directly from the manufacturer; Product expected to ship ".date("d-M-Y",$promisedDate)." .";
    				}
    				//1
				}//$AvailabilityDate
	            
	        }else{// internal
	            foreach ($statusArray as $value) {
	                $return["shipStatus"]=$value["status"];
	                if ($value["status"] == "INSTOCK"){
	                            if ((date("d-M-Y",$value["PromisedDate"])) > date("d-M-Y"))
	                            {
	                                  $return["msg"] = "Next business day.";
	                                  $return["promisedDate"] = time() + 1*24*60*60;
	                            } else {
	                                  $return["msg"] = "Today, if ordered before 1PM CST";
	                                  $return["promisedDate"] = time() ;
	                            }            
	                    $return["priority"] = 1;
	                }else if ($value["status"] == "RECEIVING"){
	                    $return["msg"] = "Available to ship within 3-4 business days.";
	                    $return["priority"] = 2;
	                    $return["promisedDate"] = time() + 4*24*60*60;
	                }else if ($value["status"] == "SERVICE"){
	                    $return["msg"] = "<!--br /-->";
	                    $return["priority"] = 4;
	                    $return["promisedDate"] = time() + 5*24*60*60;
	                }else if ($value["status"] == "RESERVED"){
	                    $return["msg"] = "Product normally ships in 3-5 business days.";
	                    $return["priority"] = 5;
	                    $return["promisedDate"] = time() + 5*24*60*60;
	                }else if ($value["status"] == "BACKORDERED" || $value["status"] == "INTRANSIT" || $value["status"] == "REPLENISH"){
	                    //$daysOut = (# of days)AvlbleToPromise.PROMISED_DATE - today
	                    $daysOut = floor(($value["PromisedDate"] - time())/(86400));
	                    if ($daysOut >= 0 && $daysOut <= 9){ //This includes if the PROMISED_DATE is today
	                        $return["msg"] = "Currently on order.<br />Please allow 1-2 weeks for delivery.";
	                        $return["priority"] = 4;
	                        $return["promisedDate"] = time() + 14*24*60*60;
	                    }else if ($daysOut >=10 && $daysOut  <= 19 || $daysOut < 0){// ;This includes being past the PROMISED_DATE
	                        $return["msg"] = "Currently on order.<br />Please allow 2-3 weeks for delivery.";
	                        $return["priority"] = 5;
	                        $return["promisedDate"] = time() + 21*24*60*60;
	                    }else if ($daysOut >= 20  && $daysOut <= 29){
	                        $return["msg"] = "Currently on order.<br />Please allow 3-4 weeks for delivery.";
	                        $return["priority"] = 6;
	                        $return["promisedDate"] = time() + 28*24*60*60;
	                    }else if ($daysOut >= 30){
	                        $return["msg"] = "Currently on order.<br />Expected ship date ".date("d-M-Y",$promisedDate)." .";
	                        $return["promisedDate"] = $promisedDate;
	                    }
	                }
	            }
	        }
	    }
	    return $return;
	}
	

	public static function ATPPromisedDatesMSG($SOAPResponseObj, $orderedQTY){
		// get all the dates of the Object
		$WebEffectiveDate = strtotime($SOAPResponseObj->ATPOutput->WEB_EFFECTIVE_DATE);
		$AvailabilityDate = strtotime ($SOAPResponseObj->ATPOutput->AVAILABILITY_DATE);
		$EmbargoDate = strtotime ($SOAPResponseObj->ATPOutput->EMBARGO_DATE);
		$DropShipFlag = $SOAPResponseObj->ATPOutput->DROPSHIP_FLAG;
		$PreProcessingLeadTime = $SOAPResponseObj->ATPOutput->PREPROCESSING_LT;
		$ProcessingLeadTime = $SOAPResponseObj->ATPOutput->PROCESSING_LT;
		$PostProcessingLeadTime = $SOAPResponseObj->ATPOutput->POSTPROCESSING_LT;
		$MessageStatus = $SOAPResponseObj->ATPOutput->MESSAGE_STATUS;
		if ($MessageStatus != "VALID"){
			//in case the MESSAGE_STATUS is not valid break it here
			$return["status"] = "INVALID";
			return $return;
		}
		$totalTempAvalCount = 0;

		$TOTAL_QUANTITY = $SOAPResponseObj->ATPOutput->TOTAL_QUANTITY;
		if (!is_array($SOAPResponseObj->ATPOutput->AvlbleToPromise->ATPAvlbleTyp)){
			$promisedDate = strtotime($SOAPResponseObj->ATPOutput->AvlbleToPromise->ATPAvlbleTyp->PROMISED_DATE);
			$totalTempAvalCount = $SOAPResponseObj->ATPOutput->AvlbleToPromise->ATPAvlbleTyp->QUANTITY;
			$statusArray[] = array("status"=>$SOAPResponseObj->ATPOutput->AvlbleToPromise->ATPAvlbleTyp->ATP_STATUS, "PromisedDate"=>$promisedDate) ;
		}else{
			// I loop through the obecjt to get the newer date 
			$tempPromisdeDate = time();
			foreach ($SOAPResponseObj->ATPOutput->AvlbleToPromise->ATPAvlbleTyp as $value) {
				// in case we the ordered quintyt is fullfilled stop the loop 
				// I have to get here the order quantity here to stop the loop as well
				
				if ( strtotime($value->PROMISED_DATE) >= $tempPromisdeDate ){
					$promisedDate = strtotime($value->PROMISED_DATE); 
				}else{
					$promisedDate = $tempPromisdeDate;
				}
				$totalTempAvalCount += $value->QUANTITY;
				if ($orderedQTY <= $totalTempAvalCount ){
					$statusArray []= array ("status"=>$value->ATP_STATUS,"PromisedDate"=>$promisedDate);
					break;
				}else{
					$statusArray []= array ("status"=>$value->ATP_STATUS,"PromisedDate"=>$promisedDate);
				}

			}
		}
		
		// in case the $AvailabilityDate in the future 
		if ($WebEffectiveDate > time()){
			//in case the MESSAGE_STATUS is not valid break it here
			$return["status"] = "INVALID";
			return $return;
		}
		// in case we don't have enugh in the stock 
		if ($totalTempAvalCount < $orderedQTY ){
			$return["msg"] = "The quantity you order is more than we have in stock";
			$return["promisedDate"] = NULL;
			
		}
		$return["promisedDate"] = $promisedDate ;
		$return["inventoryID"] = $SOAPResponseObj->ATPOutput->INVENTORY_ITEM_ID ;
		$return["totalQTY"] = $TOTAL_QUANTITY;
		$return["ResponseStatus"] = $SOAPResponseObj->ATPOutput->MESSAGE_STATUS;
		// The Messages logic 
		if ($EmbargoDate >= $promisedDate){
			$return["msg"] = "Currently on order. Expected ship date ".date("d-M-Y",$EmbargoDate)." .";
			$return["promisedDate"] = $EmbargoDate;
		}else{
			// extrenal item 
			if ($DropShipFlag == "Y"){

				$return["shipStatus"]="DropShip";
				$totalLeadTime = $PreProcessingLeadTime + $ProcessingLeadTime + $PostProcessingLeadTime;

				//Check availability  date
				$totalLeadAndToday = strtotime('+' . $totalLeadTime . ' days', time());
				
				if($totalLeadAndToday < $AvailabilityDate){
				    $return["msg"] = "Item ships directly from the manufacturer; Product expected to ship " . date("d-M-Y", $AvailabilityDate);
				}else{
				   
                    
    				if ($totalLeadTime <= 5){
    					$return["msg"] = "Item ships directly from the manufacturer; Product normally ships in 3-5 business days.";
    					$return["priority"] = 1;
    					$return["promisedDate"] = time() + 5*24*60*60 ;
    				}elseif ($totalLeadTime <= 12){
    					$return["msg"] = "Item ships directly from the manufacturer; Product normally ships in 1-2 weeks.";
    					$return["priority"] = 2;
    					$return["promisedDate"] = time() + 14*24*60*60;
    				}elseif ($totalLeadTime <= 18){
    					$return["msg"] = "Item ships directly from the manufacturer; Product normally ships in 2-3 weeks.";
    					$return["priority"] = 3;
    					$return["promisedDate"] = time() + 21*24*60*60 ;
    				}elseif ($totalLeadTime <= 29){
            		    $return["msg"] = "Currently on order.<br />Please allow 3-4 weeks for delivery.";
            		    $return["priority"] = 4;
            		}elseif($totalLeadAndToday < $AvailabilityDate){
    				     $return["msg"] =  date("d-M-Y", $AvailabilityDate);
    				}else{
    					$return["msg"] = "Item ships directly from the manufacturer; Product expected to ship ".date("d-M-Y",$promisedDate)." .";
    				}
    				//1
				}//$AvailabilityDate
				 

			}else{// internal
				foreach ($statusArray as $value) {
					$return["shipStatus"]=$value["status"];
					if ($value["status"] == "INSTOCK"){
	                        if (date("G") < 12 ){
	                            $return["msg"] = "Today, if ordered before 1PM CST";
	                        }else{
	                            $return["msg"] = "Today, if ordered before 1PM CST";
	                            //$return["msg"] = "Next business day.";
	                        }
	                   
						$return["priority"] = 1;
						$return["promisedDate"] = time() + 1*24*60*60;
					}else if ($value["status"] == "RECEIVING"){
						$return["msg"] = "Available to ship within 3-4 business days.";
						$return["priority"] = 2;
						$return["promisedDate"] = time() + 4*24*60*60;
					}else if ($value["status"] == "SERVICE"){
						$return["msg"] = "<!--br /-->";
						$return["priority"] = 4;
						$return["promisedDate"] = time() + 5*24*60*60;
					}else if ($value["status"] == "RESERVED"){
						$return["msg"] = "Product normally ships in 3-5 business days.";
						$return["priority"] = 5;
						$return["promisedDate"] = time() + 5*24*60*60;
					}else if ($value["status"] == "BACKORDERED" || $value["status"] == "INTRANSIT" || $value["status"] == "REPLENISH"){
						//$daysOut = (# of days)AvlbleToPromise.PROMISED_DATE - today
						$daysOut = floor(($value["PromisedDate"] - time())/(86400));
						if ($daysOut >= 0 && $daysOut <= 9){ //This includes if the PROMISED_DATE is today
							$return["msg"] = "Currently on order.<br />Please allow 1-2 weeks for delivery.";
							$return["priority"] = 4;
							$return["promisedDate"] = time() + 14*24*60*60;
						}else if ($daysOut >=10 && $daysOut  <= 19 || $daysOut < 0){// ;This includes being past the PROMISED_DATE
							$return["msg"] = "Currently on order.<br />Please allow 2-3 weeks for delivery.";
							$return["priority"] = 5;
							$return["promisedDate"] = time() + 21*24*60*60;
						}else if ($daysOut >= 20  && $daysOut <= 29){
							$return["msg"] = "Currently on order.<br />Please allow 3-4 weeks for delivery.";
							$return["priority"] = 6;
							$return["promisedDate"] = time() + 28*24*60*60;
						}else if ($daysOut >= 30){
							$return["msg"] = "Currently on order.<br />Expected ship date ".date("d-M-Y",$promisedDate)." .";
						    $return["promisedDate"] = $promisedDate;
						}
					}
				}
			}
		}
    	return $return;
	}	
	
}
function display_mssql_error2($p_sql, $p_desc) {
  global $log_filename;
  $msg = trim(mssql_get_last_message());
  if (empty($msg) || strpos($msg, 'Changed database context') !== FALSE ){
    return;
  }
  $server = getenv('HOSTNAME');
  if($server == 'devnew' || $server == 'austispwdb01') {
      echo "MSSQL DB ERROR : $p_desc : $msg : $p_sql";
  }

  //fill in log writing later
  if (!empty($p_sql)){
       $usrip = (!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'] );
       $sessionid = session_id();

       //jmic - 04/01/2008
       //Remove unwanted session variables.
       $a_new_session_ms = strip_session_string();
         

       //$session_string = addslashes(serialize($_SESSION));
       $session_string = addslashes(serialize($a_new_session_ms));        

       $request_string = addslashes(serialize($_REQUEST));
       if (empty($log_filename)){
          $log_filename = get_log_filename('gsi_mssql_error.log');
          write_to_log("MSSQL DB Error : $p_desc : $usrip : $sessionid : $msg : SQL : $p_sql");
          write_to_log("Session : $usrip : $sessionid : $msg: String: $session_string");
          write_to_log("Request : $usrip : $sessionid : $msg: String: $request_string");
          $log_filename = '';
       }
       else{
          write_to_log("MSSQL DB Error : $p_desc : $usrip : $sessionid : $msg: SQL : $p_sql");
          write_to_log("Session : $usrip : $sessionid : $msg : String: $session_string");
          write_to_log("Request : $usrip : $sessionid : $msg : String: $request_string");
       }
  }
  

  return $msg;	
}
?>