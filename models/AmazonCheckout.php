<?php 
require_once('Zend/View.php');
require_once('CheckoutPage.php');
require_once('Order.php');
require_once('Payment.php');
require_once('amazon_config.php');

class AmazonCheckout extends CheckoutPage {

  public function displayPage() {
  	
    $this->i_site_init->loadMain();

    $v_contract_id = post_or_get('asession'); // retrieve the amazon session id.
	
    // set the sales channel to amazon
    $i_order = new Order($this->v_order_number);
	$i_order->updateHeaderSalesChannel('AMAZONCBA');
	
	// set the voided in golf_payments to Y
	$i_payment = new Payment();
	$i_payment->void_payments('N');
	$i_payment->void_payments('Y');
	

	$z_view = new Zend_view(array('scriptPath' => VIEW_PATH));
	$z_view->contract_id = $v_contract_id;
	
	$z_view->merchantID = CheckoutByAmazon_Service_MerchantValues::getInstance()->getMerchantId();
	
	// display all portions of the page
	echo $z_view->render("checkout_amazon_payment_form.phtml") . $z_view->render("checkout_amazon_payment.phtml");
	echo $this->displayShipSection(TRUE);
    echo $this->displayCharges(TRUE);	
	echo $z_view->render('amazon_checkout_review_submit.phtml') . $z_view->render("amazon_checkout_review_formclose.phtml");
	
	// remarketing vars 
	$g_data["pageType"]="purchase";
    $g_data["prodid"]="";
    $g_data["totalvalue"]="";
	
	// display the footer
    $this->i_site_init->loadFooter($g_data);
 
  }

  public function displayShipSection($p_show_div = TRUE) {

//    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only show content if already logged in
    //if(!empty($v_customer_number)) {

      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

      $z_view->show_div = $p_show_div;

      $i_order = new Order($this->v_order_number);
      $z_view->has_sts = $i_order->isSTS();
      
      $this->getShipItems($va_items);

      $z_view->ship_items = $va_items;
      $z_view->item_count = count($va_items);

      $z_view->ship_method_code = $_SESSION['s_ship_method'];

      return $z_view->render("amazon_checkout_review_ship.phtml") . $z_view->render("amazon_checkout_review.phtml");

//    }

  }

 private function getShipItems(&$pa_item_list) {
  	
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
              , v.qbd_id
              , gia.sku
              , ghi.sales_channel_code 
              from  direct.dbo.gsi_headers_interface_all ghi, direct.dbo.gsi_cmn_order_lines_v v
              left join    direct.dbo.gsi_item_web_atp atp
              on atp.inventory_item_id = v.inventory_item_id
              and atp.organization_id  = '$v_out_warehouse_id',r12pricing.dbo.gsi_item_info_all gia 
              where order_number = '$this->v_order_number'
                and isnull(v.isp_line_type, 0) < 2
                and NOT style_number='GIFT BOX'
                and v.inventory_item_id = gia.inventory_item_id 
                and ghi.original_system_reference = v.order_number
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
      $v_sku			   = $v_row['sku'];
      $v_sales_channel_code = $v_row['sales_channel_code'];

      unset($v_min_order_qty);
      unset($v_category_id);
      unset($v_note_text);

      //don't show certain 0-cost items
      if ((trim($v_segment1) == '30047701' || trim($v_segment1) == 'PUREPAT') && $v_selling_price == 0) {
        continue;
      }
      if (trim($v_segment1) == '30047701' || trim($v_segment1) == 'PURE' || trim($v_segment1) == 'PUREPAT') {
        $va_item['show_quantity'] = FALSE;
      } else {
        $va_item['show_quantity'] = TRUE;
      }
      $va_item['original_line_reference'] = $v_orig_line_ref;
      $va_item['line_number'] = $v_line_number;
      $va_item['promise_date'] = $v_promise_date;
      $va_item['quantity'] = $v_ordered_quantity;
      $va_item['is_qbd'] = 'N';
      $va_item['selling_price'] = format_currency($v_selling_price);
      $v_extended_price = $va_item['quantity'] * $v_selling_price;
      $va_item['total'] = format_currency($v_extended_price);
      $this->getSkuInfo($v_segment1, $v_sku_desc, $v_min_order_qty, $v_category_id, $v_manuf_desc);
      $va_item['is_oem'] = FALSE;
      $va_item['style_number'] = $v_segment1;
      $va_item['source_type_code'] = $v_source_type_code;
      $va_item['description'] = $v_sku_desc;
	  $va_item['manuf_desc'] = $v_manuf_desc;
	  $va_item['sku'] = $v_sku;
	  $va_item['sales_channel_code'] = $v_sales_channel_code;
	  
      $pa_item_list['web'][] = $va_item;

      $pa_cm_tags[] = 'cmCreateShopAction5Tag("' . $va_item['style_number'] . '", "' . str_replace('"', '' ,$va_item['description']) . '", "' . $va_item['quantity'] . '", "' . $v_selling_price . '");';
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

  public function displayCharges($p_show_div = TRUE, $p_ship_method) {

    $v_customer_number = $_SESSION['s_customer_number'] ;

    //only display this section if the customer is logged in
      $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

      $z_view->show_div = $p_show_div;

      $i_order = new Order($this->v_order_number);
      
     if(!$_SESSION['s_ship_method']) {
        $p_ship_method = 'G';
        $z_view->ship_method_code = 'G';
      }
      else
      {
      	$p_ship_method = $_SESSION['s_ship_method'];
      	$z_view->ship_method_code = $p_ship_method;
      }
      
      if(!empty($p_ship_method)) {

        $_SESSION['s_ship_method'] = $p_ship_method;
        $_SESSION['smethod'] = $p_ship_method;

        //update the whole order just in case
        //link addresses, update order with ship method, etc.
        $v_update_return_status = $i_order->updateOrder($_SESSION['s_customer_id'], $_SESSION['s_bill_address_id'], $_SESSION['s_bill_contact_id'], $_SESSION['s_ship_address_id'], $_SESSION['s_ship_contact_id'], $p_ship_method);

        $_SESSION['s_do_totals'] = 'Y';

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
      return $z_view->render("amazon_checkout_review_charges.phtml");
  }
  
  public function getShippingOptions($p_has_ship_restrictions, &$pa_shipping_options) {
    $i_order = new Order($this->v_order_number);
    $i_order->getShippingOptions($p_has_ship_restrictions, $pa_shipping_options);
  }

  public function getAddress($purchaseContractId)
  {
  	//Create an instance of CreaateOrder class
	$lib = new CheckoutByAmazon_Service_CBAPurchaseContract();

	//Enter the purchase contract ID here
	$addressList = $lib->getAddress($purchaseContractId);
	return $addressList[0];
	
  }

  public function getPostalCode($purchaseContractId)
  {
  	//Create an instance of CreaateOrder class
	$lib = new CheckoutByAmazon_Service_CBAPurchaseContract();

	//Enter the purchase contract ID here
	$addressList = $lib->getAddress($purchaseContractId);
	$addressList = $addressList[0];
	return $addressList->getPostalCode();
	
  }
  public function getCountryCode($purchaseContractId)
  {
  	//Create an instance of CreaateOrder class
	$lib = new CheckoutByAmazon_Service_CBAPurchaseContract();

	//Enter the purchase contract ID here
	$addressList = $lib->getAddress($purchaseContractId);
	$addressList = $addressList[0];
	if($addressList->getCountryCode() != 'US')
   	{
   		echo 'NUS';
   	}	
	else
	{
		return $addressList->getCountryCode();
	}
  }
  
  public function refreshAmazonReviewCharges($p_ship_method, $postal_code ,$purchaseContractId, $p_show_div = FALSE)
  {
  	$p_ship_method = ($p_ship_method)?$p_ship_method:'G';
  	
	$z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
	$z_view->show_div = $p_show_div;
	
/*	$address = $this->getAddress($purchaseContractId);
   	if($address->getCountryCode() != 'US')
   	{
   		echo false;
   	}	
	else
	{*/
	   	//$v_postal_code = $address->getPostalCode();
	   	$v_postal_code = $postal_code;

	    $i_order = new Order($this->v_order_number);
	
    	$z_view->has_ship_restrictions = FALSE;
      
    	$this->getShippingOptions($z_view->has_ship_restrictions, $va_shipping_options);
    	$z_view->promotion_savings = format_currency($i_order->getTotalPromotionSavings());
		$z_view->shipping_options = $va_shipping_options;
    
    	$i_order->getEstimatedShipping($v_postal_code, $p_ship_method, $va_shipping_options);
    	$this->getMerchandiseTotal($v_num_items,$v_merchandise_total);
    
    	$z_view->sub_total = format_currency($v_merchandise_total);
    	$z_view->ship_method_code = $p_ship_method;

    	$z_view->ship_total = format_currency($va_shipping_options[$z_view->ship_method_code]['shipping']);
    	$z_view->tax_total = format_currency($va_shipping_options[$z_view->ship_method_code]['tax']);
    	$z_view->order_total = format_currency($v_merchandise_total + $va_shipping_options[$z_view->ship_method_code]['shipping'] + $va_shipping_options[$z_view->ship_method_code]['tax']);
		$z_view->asession = $purchaseContractId;
		
    	echo $z_view->render("amazon_checkout_review_charges.phtml");
	//}
  }
  
  public function updateAmazonReviewShipping($p_ship_method) {
  
    $i_order = new Order($this->v_order_number);

    if(!empty($p_ship_method)) {

      $_SESSION['s_ship_method'] = $p_ship_method;
      $_SESSION['smethod'] = $p_ship_method;

      // update the shipping charges area 
      $v_update_return_status = $i_order->updateAmazonOrderShipping($p_ship_method);

    }

  }

public function processAmazonOrder($purchaseContractId)
{
  	$i_order = new Order($this->v_order_number);

  	$v_qty_err = $i_order->validateOrderQtys();
  	
  	if (!empty($v_qty_err)) {      		
      		echo 'qtyerr: ' . $v_qty_err;       	
    } 
    else {  	
  		$this->getShipItems($va_items); // retrieve the items list
		$v_sales_channel_code = $va_items['sales_channel_code'];

  		$address = $this->getAddress($purchaseContractId); // retrieve the address selected by the user
  		
  		// Make sure we only process US orders
  		if($address->getCountryCode() != 'US')
  		{
  		    echo "Shipping Error: We don't allow AmazonCheckout for orders outside of the US.";
  		    return;
  		}
  		
  		$_SESSION['s_user_name'] = $address->getName();
   		$ship_method_code = $_SESSION['s_ship_method'];
   		  				
  		$i_order->getEstimatedShipping($address->getPostalCode(), $ship_method_code, $va_shipping_options); //get shipping charges
   		$this->getMerchandiseTotal($v_num_items,$v_merchandise_total); // get merchandise total

   		$sub_total = format_currency($v_merchandise_total);

   		
   		// set all the totals
   		$ship_total = $va_shipping_options[$ship_method_code]['shipping'];
   		$tax_total = $va_shipping_options[$ship_method_code]['tax'];

		//Create an Object of CreateOrder
		$lib = new CheckoutByAmazon_Service_CBAPurchaseContract();

		//Object to pass the list of items to setItems function for setting the items to be purchased
		$itemList = new CheckoutByAmazon_Service_Model_ItemList();

		// set shipping per item
		$shipping_per_item = $ship_total/$v_num_items;
		
		// calculate the shipping tax
        $v_stmt = mssql_init("gsi_calculate_ship_tax_amount_r12");

        gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 30);
        gsi_mssql_bind($v_stmt, "@p_postal_code", $address->getPostalCode(), 'varchar', 10);
        gsi_mssql_bind($v_stmt, "@p_shipping_amount", $ship_total, 'money', 20);
        gsi_mssql_bind($v_stmt, "@p_ship_tax_amount", $v_ship_tax, 'money', 20, true);
        gsi_mssql_bind($v_stmt, "@o_return_status", $v_return_status, 'varchar', 100, true);

        $v_result = mssql_execute($v_stmt);

        $v_ship_tax_per_item =  $v_ship_tax/$v_num_items;
        $count = 0;
        $line_shipping = 0;
        $v_ship_tax_total = 0;
                		
		//Call create Item or create Physical item to create each item.
		//Input : createItem(MerchantItemID,Title,UnitPriceAmount)
		foreach($va_items as $v_warehouse_id => $va_ship_item_section) {
			foreach($va_ship_item_section as $v_index => $va_data) {
				
				$v_shipping = round($shipping_per_item * $va_data['quantity'],2);
            	$v_ship_tax_per_line = round($v_ship_tax_per_item * $va_data['quantity'],2);
            	$line_shipping += $v_shipping;
            	$v_ship_tax_total += $v_ship_tax_per_line;
            	$count++;

            	if($count == count($va_ship_item_section))
            	{
            		if($line_shipping > $ship_total)
                	{
                		$v_shipping = $ship_total - ($line_shipping - $v_shipping);
                    	$v_ship_tax_per_line = $v_ship_tax - ($v_ship_tax_total - $v_ship_tax_per_line);
                	}
            	}
				
				// calculate the item tax and shipping tax 
				$v_stmt = mssql_init("gsi_calculate_per_item_tax_amount_r12");
				
				gsi_mssql_bind($v_stmt, "@p_original_system_line_reference", $va_data['original_line_reference'],'varchar',15);
                gsi_mssql_bind($v_stmt, "@p_original_system_reference", $this->v_order_number, 'varchar', 15);
                gsi_mssql_bind($v_stmt, "@p_postal_code", $address->getPostalCode(), 'varchar', 10);
                gsi_mssql_bind($v_stmt, "@p_item_tax_amount", $v_item_tax, 'money', 20, true);
                gsi_mssql_bind($v_stmt, "@o_return_status", $v_return_status, 'varchar', 100, true);

                $v_result = mssql_execute($v_stmt);

                if(!$v_result) {
                	display_mssql_error("gsi_nbt_ship_get_three_ship_charges_and_tax", "called from getEstimatedShipping() in CheckoutPage.php");
                }
                mssql_free_statement($v_stmt);
                mssql_free_statement($v_result);

                // shipping method array
                $shipping_method = array('G' => "Standard",'N' => "OneDay",'2' => "TwoDay");
                                
				//Create a new PurchaseItem
				$itemObject = new CheckoutByAmazon_Service_Model_PurchaseItem();
				$itemObject->createPhysicalItem($va_data['line_number'],$va_data['manuf_desc'].' '.$va_data['description'],str_replace('$','',$va_data['selling_price']),$shipping_method[$ship_method_code]);
				$itemObject->setQuantity($va_data['quantity']);
				$itemObject->setSKU($va_data['sku']);
				$itemObject->setItemTax($v_item_tax+$v_ship_tax_per_line);
				$itemObject->setItemShippingCharges($v_shipping);
				
				//Add all the item objects to ItemList.
				$itemList->addItem($itemObject);
				$v_sales_channel_code = $va_data['sales_channel_code'];
			}
		}

try{    
	    $setItemsStatus = $lib->setItems($purchaseContractId,$itemList);

    	if($setItemsStatus == 1)
		{
       		//Complete the Amazon transaction by using the below call. Pass the 
	        //purchaseContractID to complete the order
       		$orderIdList = $lib->completeOrder($purchaseContractId);

	        //Displays the orders generated
       		if(!is_null($orderIdList))
       		{
				$i_order->updateHeaderPurchaseOrderNum($orderIdList[0]);
				
				// ship_set atp_dates
      			$v_stmt = mssql_init("gsi_cmn_order_ext_ship_set_atp_dates");

      			gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 30);
      			gsi_mssql_bind($sql, "@p_profile_scope", $v_profile_scope, 'varchar', 30);

      			$v_result = mssql_execute($v_stmt);

      			if(!$v_result){
        			display_mssql_error("gsi_cmn_order_ext_ship_set_atp_dates", "called from processOrder() in AmazonCheckout.php");
      			}

      			mssql_free_statement($v_stmt);
      			mssql_free_result($v_result);
      				
				// Update tracking code and ip address
        		$v_tracking_code = $_COOKIE['tcode'] ;
        		//$v_ip_address    = ( !empty($_SERVER['HTTP_CLIENT_IP']) ?  $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'] ) ;
        		if(isset($_SERVER['XFF']))
        		{
        			$v_ip_address    = $_SERVER['XFF'];
        		}else if (isset($_SERVER['HTTP_TRUE_CLIENT_IP']))
 				{
 					$v_ip_address    = $_SERVER['HTTP_TRUE_CLIENT_IP'];
 				}
      			else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
 				{
 					$v_ip_address    = $_SERVER['HTTP_X_FORWARDED_FOR'];
 				}
 		 		else {
        				$v_ip_address    = ( !empty($_SERVER['HTTP_CLIENT_IP']) ?  $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'] ) ;
 				}

 				// update order attributes
        		$v_stmt = mssql_init("gsi_cmn_order_ext_update_order_attributes");

		        gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 25);
			    gsi_mssql_bind($sql, "@p_tracking_code", $v_tracking_code, 'varchar', 25);
        		gsi_mssql_bind($sql, "@p_ip_address", $v_ip_address, 'varchar', 25);

        		$v_result = mssql_execute($v_stmt);

        		if (!$v_result){
          			display_mssql_error("gsi_cmn_order_ext_update_order_attributes", "called from processOrder() in AmazonCheckout.php");
        		}

        		mssql_free_statement($v_stmt);
        		mssql_free_result($v_result);
        		
        		$ATPFlag = $i_order->ATPdecrement();
        		// update promo code, and reduce the available quantity
        		$v_stmt = mssql_init("gsi_cmn_order_amazon_post_process");

		        gsi_mssql_bind($v_stmt, "@p_orig_sys_ref", $this->v_order_number, 'varchar', 25);
		        gsi_mssql_bind($v_stmt, "@p_catp_service_flag", $ATPFlag, 'varchar', 25);

        		$v_result = mssql_execute($v_stmt);

        		if (!$v_result){
          			display_mssql_error("gsi_cmn_order_amazon_post_process", "called from processOrder() in AmazonCheckout.php");
        		}

        		mssql_free_statement($v_stmt);
        		mssql_free_result($v_result);
        		
           		echo $this->displaySubmitConfirm(FALSE,$orderIdList[0],$sub_total,$tax_total,$ship_total, $v_sales_channel_code);
       		}
   		}
}

catch (CheckoutByAmazon_Service_RequestException $rex)
{
	$error = "Caught Request Exception: " . $rex->getMessage();
	$error .= "Response Status Code: " . $rex->getStatusCode();
	$error .= "Error Code: " . $rex->getErrorCode();
	$error .= "Error Type: " . $rex->getErrorType();
	$error .= "Request ID: " . $rex->getRequestId() . "\n";
	$error .= "XML: " . $rex->getXML() . "\n";
    error_log($error,3, LOG_PATH . 'amazon_process_err.dat');
}   		
	}
}

public function displaySubmitConfirm($p_show_div = TRUE,$amazon_order,$v_sub_total,$v_tax_total,$v_ship_total,$v_sales_channel_code) {

    $i_order = new Order($this->v_order_number);

    $z_view = new Zend_view(array('scriptPath' => VIEW_PATH));

    $z_view->show_div = $p_show_div;
    $z_view->name = $_SESSION['s_user_name'];
	$z_view->amazon_order_number = $amazon_order;
	
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
    $_SESSION['s_user_name'] = '';
	

    $this->generatePartnerData($v_order_subtotal, $v_ping_total, $va_cm_data, $va_ga_data);
    
    $z_view->ga_data = $va_ga_data;
    $z_view->cm_data = $va_cm_data;
    $z_view->order_subtotal = format_currency($v_sub_total);
    $z_view->ship_total = $v_ship_total;
    $z_view->ga_tax_total = $v_tax_total;
    $z_view->ga_order_total = $v_sub_total + $v_tax_total + $v_ship_total; 
    $z_view->site_init = $this->i_site_init;
    $z_view->sales_channel_code = $v_sales_channel_code;

    $v_contains_only_ping = FALSE;
    if($v_ping_total > 0) {
      $v_non_ping_total = $v_sub_total - $v_ping_total;
      if($v_non_ping_total <= 0) {
        $v_contains_only_ping = TRUE;
      }
    } else {
      $v_non_ping_total = $v_sub_total;
    }

    $z_view->non_ping_total = format_currency($v_non_ping_total);
    $z_view->contains_only_ping = $v_contains_only_ping;
    $z_view->partner_code = $_COOKIE['prtcode'];
    return $z_view->render('checkout_submit_confirm.phtml');
}

  //generates data for confirm page for CoreMetrics and Channel Intelligence
  public function generatePartnerData(&$p_order_subtotal, &$p_ping_total, &$pa_cm_data, &$ga_newarr) {

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
              , b.description + ' ' + s.description style_name
              , c.description style_category
              , iia.sku
              from  direct.dbo.gsi_cmn_order_lines_v v
              , r12pricing..gsi_item_info_all iia
              , r12pricing..gsi_style_info_all s
              , r12pricing..gsi_brands b
              , r12pricing..gsi_categories c  
              where v.order_number = '$this->v_order_number'
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

      $v_sku = $va_row['style_number'] . '-' . $va_row['style_attr1'] . '-' . $va_row['style_attr2'] . '-' . $va_row['style_attr3'] . '-' . $va_row['style_attr4'];

      //check for ping
      $v_sql2 = "select manufacturer_desc from gsi_cmn_style_data where style_number='$style'";

      $v_result2 = mysqli_query($web_db, $v_sql2);
      display_mysqli_error($v_sql2);

      if($va_row2 = mysqli_fetch_array($v_result2)) {
        $v_manufacturer_desc = $va_row2['manufacturer_desc'];
        if(strtoupper($v_manufacturer_desc) == 'PING') {
          $p_ping_total = floatval($v_ping_total + floatval($va_row['ordered_quantity'] * $va_row['selling_price']));
        }
      }

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

}
?>
