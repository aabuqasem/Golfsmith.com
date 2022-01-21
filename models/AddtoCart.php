<?php

/****************************************************************************
*                                                                           *
*  Program Name :  AddtoCart.php                                           *
*  Type         :  MVC Model Class                                          *
*  File Type    :  PHP                                                      *
*  Location     :  /include/Models                                          *
*  Created By   :  Hafeez Ullah Arain                                       *
*  Created Date :  10/12/2010                                               *
*               :  Copyright 2010  Golfsmith International                  *
*---------------------------------------------------------------------------*
*                                                                           * 
* History:                                                                  *
* --------                                                                  *
* Date       By                  Comments                                   * 
* ---------- ---------------     --------------------                       *
* 10/12/2010 Hafeez Ullah         Initial Version                           *
* 05/25/2011 Hafeez Ullah		  changed the tables, attribute and also
*                                 added five more segments...               *
*                                                                           *
*                                                                           *
****************************************************************************/

global $connect_mssql_db;
$connect_mssql_db = 1;

require_once('Zend/View.php');
include('gsi_common.inc');
include_once('functions/QBD_functions.php');
include_once('ProductPage.php');
include_once(FPATH . "xsell");
include_once(FPATH . "fns_sc_order");
include_once(FPATH . 'ppage_functions'); 
include_once(FPATH .'QBD.php');

class AddtoCart {
	
  function get_scene7_image($p_style) {
    global $web_db;
    
    // Modifed the script to match with QBD and simplified without too many calls to DB
        $v_image_url = '';    
		$sql="select old_style from webdata.gsi_style_info_all where style_number='".$p_style."'";
		$result = mysqli_query($web_db,$sql);
		$old_style = mysqli_fetch_row($result);
		$old_style_small = substr($old_style[0],0,-2);
		$sql="SELECT full_name
        	FROM webonly.gsi_scene7_data g
            WHERE (style_number = upper('$old_style[0]') OR style_number = upper('$old_style_small') OR style_number = upper('$p_style'))
            AND image_type='im' ORDER BY pos_num";
            //ORDER BY SUBSTR(g.full_name, -5,5) DESC LIMIT 1";
		$result = mysqli_query($web_db,$sql);
		$scene7_image = mysqli_fetch_row($result);
		if($scene7_image[0])
		{
			$v_image_url = SCENE7_URL . $scene7_image[0] . '?$srp$';
		}	
		else 
		{	
        	if(file_exists(IMAGE_PATH.$p_style.'_sm.jpg') === TRUE)
			{
				$v_image_url = 'http://golfsmith.com/images/'.$p_style.'.jpg" height="200 ' ;
			}
			else
			{
				if(file_exists(IMAGE_PATH.strtolower($old_style[0]).'.jpg') === TRUE)
				{
					$v_image_url = 'http://golfsmith.com/images/'.strtolower($old_style[0]).'.jpg" height="200 ' ;
				}
				else
				{
					$v_image_url = 'http://golfsmith.com/images/'.strtolower(substr($old_style[0],0,-2)).'.jpg" height="200 ' ;
				}
			}
		}
		return $v_image_url;
		    
/*    $v_image_url = '';
    
    $sql ="select count(*)
		   from gsi_style_info_all
		   where style_number='$p_style'
		     and used_item_flag = 'Y'";
    $result=mysqli_query($web_db,$sql);
        
    if (mysqli_num_rows($result) > 1) {
      $sql="select parent_style_number
			from gsi_preowned_styles
			where style_number='$p_style' limit 1";
      
      $result=mysqli_query($web_db,$sql);
      while($va_row = mysqli_fetch_row($result)){
            $v_style = strtoupper($va_row[0]);
      }
    }else {
      $v_style =$p_style;
    }
    
    $sql="SELECT full_name
          FROM webonly.gsi_scene7_data
          WHERE style_number = '$v_style'
          AND image_type='im'
          ORDER BY pos_num DESC";
    $result=mysqli_query($web_db,$sql);
    display_mysqli_error($sql) ;

    if (mysqli_num_rows($result) > 0)    {
      while($va_row = mysqli_fetch_row($result)){
        $v_image_url = SCENE7_URL . $va_row[0] . "?hei=215&wid=200&op_sharpen=1";
      }
    } else {
      $sql="select ifnull(large_image_file
       		,lower(concat('/images/', ifnull(old_style,style_number), '.jpg'))) as style_url
			from gsi_style_info_all
			where (style_number='$v_style' or old_style='$v_style')";
      $result=mysqli_query($web_db,$sql);
      display_mysqli_error($sql) ;

      if (mysqli_num_rows($result) > 0)    {
        while($va_row = mysqli_fetch_row($result)){
          $v_image_url ='http://www.golfsmith.com/' .$va_row[0].'"'. ' height="200"';
        }
      } 
    }
   return $v_image_url;*/
  }
  
  /*
   * Adding item into shopping cart and also rendring the .phtml file to showing the Added item view
   * 
   */
  
  function addtocart_product($p_style,$p_description,$p_qty,$p_itemid,$p_record_sequence,$p_per_item,$p_segment2,$p_segment3,$p_segment4,$p_segment5,$p_segment6,$p_segment7,$p_segment8,$p_segment9,$p_segment10,$p_sku,$p_service_item,$p_lcode,$p_click_seq,$p_forecast,$p_xref){
   	global $web_db;
   	
  	$z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
  	  
    $v_xref=$p_xref;
    $v_quantity=$p_qty;
    $v_lcode= $p_lcode;
    $v_forecast=$p_forecast;
    $v_segment2=$p_segment2;
    $v_segment3=$p_segment3;
    $v_segment4=$p_segment4;
    $v_segment5=$p_segment5;
    $v_segment6=$p_segment6;
    $v_segment7=$p_segment7;
    $v_segment8=$p_segment8;
    $v_segment9=$p_segment9;
    $v_segment10=$p_segment10;
    $v_per_item=$p_per_item;
	$v_style_number= mysqli_real_escape_string($web_db,$p_style);
    $v_click_seq=$p_click_seq;
    $v_sku_desc=$p_description;
    $v_inventory_item_id=$p_itemid;
    $v_service_item=$p_service_item;
    $v_screen_name=$_SESSION['s_screen_name'];
        
    $v_prod_page = new ProductPage();
    if($v_per_item=='undefined'){ // setting null value other it will save this recored into notes table.
      $v_per_item='';
    }
      
    include_once(FPATH . 'add_to_cart_ext'); // Adding item to shopping cart
    $valid_style = validate_style($v_style_number, $v_forecast);
   
    //Properly formating the sku for showing on the addtocart popup
    
    if(!empty($p_sku)){
      $v_gsi_sku = str_replace($v_style_number,'',$p_sku);
	  $v_gsi_sku = str_replace('-NA','',$v_gsi_sku);
	  $v_gsi_sku = str_replace('-N/A','',$v_gsi_sku);
      $v_gsi_sku = "&nbsp;|". str_replace('-','&nbsp;',$v_gsi_sku);
    }
   
    $v_cust_price_list_id  = $_SESSION['s_cust_price_list'];
    $v_source_code         = $_SESSION['s_source_code'] ;
   
    $v_QBD_price_flag='N';
    if (isActiveQBD($v_inventory_item_id)) { // verifying either currunt item is a QBD or not
    	$v_cust_price_list_id = WEB_PRICE_LIST_ID; /*Replaced old QBD price id (2408) with new pricing id*/ 
    	$v_QBD_price_flag='Y';
    }
  	
    $sql = "select item_id_str,
		     	   price_str,
		  	 	   price_svg_str,
		     	   orig_price_str
		  	from gsi_cmn_style_data  
		  	where style_number = '$v_style_number'";
		 
		    $result = mysqli_query($web_db, $sql);
		    display_mysqli_error($sql);
		    $myrow = mysqli_fetch_array($result);
		    
		    $item_id_str    = $myrow["item_id_str"];
		    $price_str      = $myrow["price_str"];
		    $price_svg_str 	= $myrow["price_svg_str"];
		    $orig_price_str	= $myrow["orig_price_str"];		    
		    mysqli_free_result($result);
		    
    // check for any customer/source code pricing
    if( !empty($v_cust_price_list_id) || !empty($v_source_code) ) {
        $v_sp = mssql_init("gsi_cmn_item_reprice_style");
        $v_webprofile = C_WEB_PROFILE;

        gsi_mssql_bind ($v_sp, "@p_style_number", $v_style_number, "varchar", 80, false);
        gsi_mssql_bind ($v_sp, "@p_price_list", $v_cust_price_list_id, "gsi_id_type", 50, false);
        gsi_mssql_bind ($v_sp, "@p_source_code", $v_source_code, "varchar", 150, false);
        gsi_mssql_bind ($v_sp, "@p_profile_scope", $v_webprofile, "varchar", 50, false);        
        gsi_mssql_bind ($v_sp, "@p_item_id_str_in", $item_id_str, "varchar", 8000, false);
		gsi_mssql_bind ($v_sp, "@p_price_str_in", $price_str, "varchar", 8000, false);
		gsi_mssql_bind ($v_sp, "@p_price_svg_in", $price_svg_str, "varchar", 8000, false);
		gsi_mssql_bind ($v_sp, "@p_orig_price_str_in", $orig_price_str, "varchar", 8000, false);
        gsi_mssql_bind ($v_sp, "@p_price_str", $o_price_str, "varchar", 8000, true);
        gsi_mssql_bind ($v_sp, "@p_price_svg", $o_price_svg_str, "varchar", 8000, true);
        gsi_mssql_bind ($v_sp, "@p_orig_price_str", $o_orig_price_str, "varchar", 8000, true);
        gsi_mssql_bind ($v_sp, "@p_act_price_str", $o_act_price_str, "varchar", 8000, true);
        //gsi_mssql_bind ($v_sp, "@o_return_status", $return, "varchar", 200, true);

        $v_result = mssql_execute($v_sp);
        
        /* Reassigning output variables to normal variable */        
        $price_str=$o_price_str;
        $price_svg_str=$o_price_svg_str;
        $orig_price_str=$o_orig_price_str;
        $act_price_str=$o_act_price_str;

        if (!$v_result){
          display_mssql_error("gsi_cmn_item_reprice_style" . $msg, "call from AddtoCart.php");
        }
        mssql_free_statement($v_sp);
        mssql_free_result ($v_result);
   
    } else {
	  $sql = "select 
	  				 price_str,
		     		 item_id_str		  	 
		  	  from gsi_cmn_style_data
		  	  where style_number = '$v_style_number'";
		 
		    $result = mysqli_query($web_db, $sql);
		    display_mysqli_error($sql);
		    $myrow = mysqli_fetch_array($result);
		    $price_str = $myrow["price_str"];
		    $item_id_str = $myrow["item_id_str"];		    
		    mysqli_free_result($result);
	}
	
    if(empty($item_id_str)){
      $sql="select item_id_str
		  	from gsi_cmn_style_data  
		  	where style_number = '$v_style_number'";
		    $result = mysqli_query($web_db, $sql) ;
		    display_mysqli_error($sql) ;
		    $myrow = mysqli_fetch_array($result) ;
		    $item_id_str = $myrow["item_id_str"] ;
		    mysqli_free_result($result) ;
	}
	
	
	//Getting manuf_desc and original price
	$sql2= " select lower(bra.brand_name) as manuf_desc, sia.description,sia.original_price
			 from gsi_style_info_all sia,
     		 gsi_cmn_style_data csd,
     		 gsi_brands         bra
			 where csd.style_number = '$v_style_number'
			 and sia.style_number = csd.style_number
			 and sia.brand_id = bra.brand_id";
	
           $results2 = mysqli_query($web_db, $sql2);
           display_mysqli_error($sql2);
      if ($myrow2 = mysqli_fetch_array($results2)){
          $v_manuf_desc=ucwords($myrow2["manuf_desc"]);
          $v_sku_desc =$myrow2["description"];
          $v_original_price=$myrow2["original_price"];
          mysqli_free_result($results2);
      } 
           
    // if currunt item is a QBD then repricing it  
    if($v_QBD_price_flag=='Y'){
      $arr_price_str=explode(",",$price_str);
      foreach ($arr_price_str as $value) {
        $v_QBDPrice=str_replace('"',"",$value);
    	  if($v_QBDPrice<>"0.00"){
    	    $price=$v_QBDPrice;
    	    break;
    	  }
      }
    }else{
    	$price = get_actual_price($price_str,$item_id_str, $v_inventory_item_id);
    }
    
    $v_donotshow_total='';
    $v_dollar_saving='';
    $v_peritem_saving='';
    $v_tot_saving='';
    
    // Generating saving story
    if ( (ltrim($price,'$') < ltrim($v_original_price,'$')) && $price!='0.0'){
          $v_dollar_saving=ltrim($v_original_price,'$')-ltrim($price,'$');
    }   

    if ($price == 'N' || $price_str == '"N"')
    {
      $price = "<a href=/$v_screen_name/checkout/cart><span>View Cart to See Price</span></a>";
      $v_donotshow_total='N';
    }else{
      if(!empty($v_dollar_saving)){
      $v_peritem_saving = format_currency($v_dollar_saving);
      $v_tot_saving = format_currency(($v_dollar_saving*$v_quantity));
      }	
    }
    //Generating subtotal      
    if($v_donotshow_total!='N'){
    	$v_dprice = ltrim($price,'$');
    	$v_sub_tot =format_currency(($v_quantity * $v_dprice));
	}
    
	// Getting currunt product image
	$v_product_image=$this->get_scene7_image($v_style_number);	    
      
	
        		
        $va_also_purchased = array();       
        $v_sequence=1; // Initializing $v_sequence counter to loop until end of record
        
        do{
	       $v_prod_page->generateAlsoPurchased($v_style_number, $v_sequence, $va_also_purchased_tmp);	        
	     
		   if(is_array($va_also_purchased_tmp)) {
		      $va_also_purchased[] = $va_also_purchased_tmp;
		   }
		    $v_sequence++;		   
        }while($v_sequence<11);  
        
        /* Eliminate redundant array values from the array.  */
         
		foreach ($va_also_purchased as $ak_k=>$av_ap)
		  $new_ap[$ak_k] = serialize($av_ap); // Serializing
		  $va_uniq_ap = array_unique($new_ap);
		
		foreach($va_uniq_ap as $ak_k=>$av_ser)
			$va_t[$ak_k] = unserialize($av_ser); // Unserializing		
		
		$va_also_purchased=$va_t;
  		unset($va_t); // Unsetting the $va_t
				
    $z_view->va_also_purchased=$va_also_purchased;    
    $z_view->image_file=$v_product_image;	
    $z_view->style_number=$v_style_number;
    $z_view->manuf_desc=$v_manuf_desc;    
    $z_view->description=$v_sku_desc;
    $z_view->qty=$v_quantity;
    $z_view->item_id=$v_inventory_item_id;
    $z_view->price=$price;
    $z_view->sub_total=$v_sub_tot;
    $z_view->peritem_saving=$v_peritem_saving;
    $z_view->all_saving=$v_tot_saving;
    $z_view->screen_name=$v_screen_name;
    $z_view->sku=$v_gsi_sku;
    return $z_view->render('addtocart.phtml'); // rendering addtocart html view
  }
  
  
  /*
   * Added by Luis Vazquez
   * Modified: 09/10/2015
   */
  function addtocart_product2($param){
  
      global $web_db;
  
      $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
       
      $v_xref = $param['xref'];
      $v_quantity = $param['qty'];
      $v_lcode =  $param['lcode'];
      $v_forecast = $param['fcst'];
      $v_segment2 = $param['segment2'];
      $v_segment3 = $param['segment3'];
      $v_segment4 = $param['segment4'];
      $v_segment5 = $param['segment5'];
      $v_segment6 = $param['segment6'];
      $v_segment7 = $param['segment7'];
      $v_segment8 = $param['segment8'];
      $v_segment9 = $param['segment9'];
      $v_segment10 = $param['segment10'];
      $v_per_item = $param['per_item'];
      $v_style_number = $param['style'];
      $v_click_seq = $param['cseq'];
      $v_sku_desc = $param['description'];
      $v_inventory_item_id = $param['item_id'];
      $v_service_item = $param['service_item'];
      $v_ship_opt = $param['ship_opt'];
      $v_screen_name = $_SESSION['s_screen_name'];
      
      $newQty = 0;
  
      //This weren't assigned with $v_
      $p_record_sequence = $v_record_sequence = $param['record_sequence'];
      $p_sku = $v_sku = $param['sku'];
  
      $v_item_isp_qty = $param['item_isp_qty'];
      $v_item_isp_organization_id = $param['organization_id'];
      $v_isp_line_type = $param['line_type'];
  
  
  
      $v_prod_page = new ProductPage();
      if($v_per_item=='undefined'){ // setting null value other it will save this recored into notes table.
          $v_per_item='';
      }
  
      include_once(FPATH . 'add_to_cart_ext'); // Adding item to shopping cart
      $v_quantity = $newQty;
      $valid_style = validate_style($v_style_number, $v_forecast);
  
      //Properly formating the sku for showing on the addtocart popup
  
      if(!empty($p_sku)){
          $v_gsi_sku = str_replace($v_style_number,'',$p_sku);
          $v_gsi_sku = str_replace('-NA','',$v_gsi_sku);
          $v_gsi_sku = str_replace('-N/A','',$v_gsi_sku);
          $v_gsi_sku = "&nbsp;|". str_replace('-','&nbsp;',$v_gsi_sku);
      }
       
      $v_cust_price_list_id  = $_SESSION['s_cust_price_list'];
      $v_source_code         = $_SESSION['s_source_code'] ;
  
      $v_QBD_price_flag='N';
      if (isActiveQBD($v_inventory_item_id)) { // verifying either currunt item is a QBD or not
          $v_cust_price_list_id = WEB_PRICE_LIST_ID; /*Replaced old QBD price id (2408) with new pricing id*/
          $v_QBD_price_flag='Y';
      }
  
      $sql = "select item_id_str,
      price_str,
      price_svg_str,
      orig_price_str
      from gsi_cmn_style_data
      where style_number = '$v_style_number'";
  
      $result = mysqli_query($web_db, $sql);
      display_mysqli_error($sql);
      $myrow = mysqli_fetch_array($result);
  
      $item_id_str    = $myrow["item_id_str"];
      $price_str      = $myrow["price_str"];
      $price_svg_str 	= $myrow["price_svg_str"];
      $orig_price_str	= $myrow["orig_price_str"];
      mysqli_free_result($result);
  
      // check for any customer/source code pricing
      if( !empty($v_cust_price_list_id) || !empty($v_source_code) ) {
          $v_sp = mssql_init("gsi_cmn_item_reprice_style");
          $v_webprofile = C_WEB_PROFILE;
  
          gsi_mssql_bind ($v_sp, "@p_style_number", $v_style_number, "varchar", 80, false);
          gsi_mssql_bind ($v_sp, "@p_price_list", $v_cust_price_list_id, "gsi_id_type", 50, false);
          gsi_mssql_bind ($v_sp, "@p_source_code", $v_source_code, "varchar", 150, false);
          gsi_mssql_bind ($v_sp, "@p_profile_scope", $v_webprofile, "varchar", 50, false);
          gsi_mssql_bind ($v_sp, "@p_item_id_str_in", $item_id_str, "varchar", 8000, false);
          gsi_mssql_bind ($v_sp, "@p_price_str_in", $price_str, "varchar", 8000, false);
          gsi_mssql_bind ($v_sp, "@p_price_svg_in", $price_svg_str, "varchar", 8000, false);
          gsi_mssql_bind ($v_sp, "@p_orig_price_str_in", $orig_price_str, "varchar", 8000, false);
          gsi_mssql_bind ($v_sp, "@p_price_str", $o_price_str, "varchar", 8000, true);
          gsi_mssql_bind ($v_sp, "@p_price_svg", $o_price_svg_str, "varchar", 8000, true);
          gsi_mssql_bind ($v_sp, "@p_orig_price_str", $o_orig_price_str, "varchar", 8000, true);
          gsi_mssql_bind ($v_sp, "@p_act_price_str", $o_act_price_str, "varchar", 8000, true);
          //gsi_mssql_bind ($v_sp, "@o_return_status", $return, "varchar", 200, true);
  
          $v_result = mssql_execute($v_sp);
  
          /* Reassigning output variables to normal variable */
          $price_str=$o_price_str;
          $price_svg_str=$o_price_svg_str;
          $orig_price_str=$o_orig_price_str;
          $act_price_str=$o_act_price_str;
  
          if (!$v_result){
              display_mssql_error("gsi_cmn_item_reprice_style" . $msg, "call from AddtoCart.php");
          }
          mssql_free_statement($v_sp);
          mssql_free_result ($v_result);
           
      } else {
          $sql = "select
          price_str,
          item_id_str
          from gsi_cmn_style_data
          where style_number = '$v_style_number'";
           
          $result = mysqli_query($web_db, $sql);
          display_mysqli_error($sql);
          $myrow =  mysqli_fetch_array($result);
          $price_str = $myrow["price_str"];
          $item_id_str = $myrow["item_id_str"];
          mysqli_free_result($result);
      }
  
      if(empty($item_id_str)){
          $sql="select item_id_str
          from gsi_cmn_style_data
          where style_number = '$v_style_number'";
          $result = mysqli_query($web_db, $sql) ;
          display_mysqli_error($sql) ;
          $myrow = mysqli_fetch_array($result) ;
          $item_id_str = $myrow["item_id_str"] ;
          mysqli_free_result($result) ;
      }
  
  
      //Getting manuf_desc and original price
      $sql2= " select lower(bra.brand_name) as manuf_desc, sia.description,sia.original_price
      from gsi_style_info_all sia,
      gsi_cmn_style_data csd,
      gsi_brands         bra
      where csd.style_number = '$v_style_number'
      and sia.style_number = csd.style_number
      and sia.brand_id = bra.brand_id";
  
      $results2 = mysqli_query($web_db, $sql2);
      display_mysqli_error($sql2);
      if ($myrow2 = mysqli_fetch_array($results2)){
          $v_manuf_desc=ucwords($myrow2["manuf_desc"]);
          $v_sku_desc =$myrow2["description"];
          $v_original_price=$myrow2["original_price"];
          mysqli_free_result($results2);
      }
  
      // if currunt item is a QBD then repricing it
      if($v_QBD_price_flag=='Y'){
          $arr_price_str=explode(",",$price_str);
          foreach ($arr_price_str as $value) {
              $v_QBDPrice=str_replace('"',"",$value);
              if($v_QBDPrice<>"0.00"){
                  $price=$v_QBDPrice;
                  break;
              }
          }
      }else{
          $price = get_actual_price($price_str,$item_id_str, $v_inventory_item_id);
      }
  
      $v_donotshow_total='';
      $v_dollar_saving='';
      $v_peritem_saving='';
      $v_tot_saving='';
  
      // Generating saving story
      if ( (ltrim($price,'$') < ltrim($v_original_price,'$')) && $price!='0.0'){
          $v_dollar_saving=ltrim($v_original_price,'$')-ltrim($price,'$');
      }
  
      if ($price == 'N' || $price_str == '"N"')
      {
          $price = "<a href=/$v_screen_name/checkout/cart><span>View Cart to See Price</span></a>";
          $v_donotshow_total='N';
      }else{
          if(!empty($v_dollar_saving)){
              $v_peritem_saving = format_currency($v_dollar_saving);
              $v_tot_saving = format_currency(($v_dollar_saving*$v_quantity));
          }
      }
      //Generating subtotal
      if($v_donotshow_total!='N'){
          $v_dprice = ltrim($price,'$');
          $v_sub_tot =format_currency(($v_quantity * $v_dprice));
      }
  
      // Getting currunt product image
      $v_product_image=$this->get_scene7_image($v_style_number);
  
  
  
      $va_also_purchased = array();
      $v_sequence=1; // Initializing $v_sequence counter to loop until end of record
  
      do{
          $v_prod_page->generateAlsoPurchased($v_style_number, $v_sequence, $va_also_purchased_tmp);
  
          if(is_array($va_also_purchased_tmp)) {
              $va_also_purchased[] = $va_also_purchased_tmp;
          }
          $v_sequence++;
      }while($v_sequence<11);
  
      /* Eliminate redundant array values from the array.  */
  
      foreach ($va_also_purchased as $ak_k=>$av_ap)
          $new_ap[$ak_k] = serialize($av_ap); // Serializing
      $va_uniq_ap = array_unique($new_ap);
  
      foreach($va_uniq_ap as $ak_k=>$av_ser)
          $va_t[$ak_k] = unserialize($av_ser); // Unserializing
  
      $va_also_purchased=$va_t;
      unset($va_t); // Unsetting the $va_t
  
      $z_view->va_also_purchased=$va_also_purchased;
      $z_view->image_file=$v_product_image;
      $z_view->style_number=$v_style_number;
      $z_view->manuf_desc=$v_manuf_desc;
      $z_view->description=$v_sku_desc;
      $z_view->qty=$v_quantity;
      $z_view->item_id=$v_inventory_item_id;
      $z_view->price=$price;
      $z_view->sub_total=$v_sub_tot;
      $z_view->peritem_saving=$v_peritem_saving;
      $z_view->all_saving=$v_tot_saving;
      $z_view->screen_name=$v_screen_name;
      $z_view->sku=$v_gsi_sku;
      $z_view->ship_opt=$v_ship_opt;
      
      return $z_view->render('addtocart.phtml'); // rendering addtocart html view
  }//addtocart_product2 ENDS
  
  
  
 }
?>