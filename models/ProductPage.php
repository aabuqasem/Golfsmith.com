<?php 
/****************************************************************************
*                                                                           *
*  Program Name :  ProductPage.php                                          *
*  Type         :  MVC Model Class                                          *
*  File Type    :  PHP                                                      *
*  Location     :  /include/Models                                          *
*  Created By   :  Jana                                                     *
*  Created Date :  10/12/2010                                               *
*               :  Copyright 2010  Golfsmith International                  *
*---------------------------------------------------------------------------*
* Note:-
* Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)                                                                    *
* Thus, we shouldn't need any other includes here                                                                          *
*                                                
* ------------------------------------------------------------------------- * 
* 
* History:                                                                  *
* --------                                                                  *
* Date       By                  Comments                                   * 
* ---------- ---------------     --------------------                       *
* 10/12/2010 Jana		          Initial Version                           *
* 05/25/2011 Hafeez Ullah		  Replaced few tables, attributes           *
*                                 and few places changed the logic          *
*                                 according to the R12 requirnment..        *
*                                                                           *
*                                                                           *
****************************************************************************/
include_once('models/Endeca.php');
require_once('Zend/View.php');
include_once('models/MyJoys.php');
include_once('functions/QBD_functions.php');
require_once("Slider.php");
require_once('ATPExtendedSoap.php');
include_once('models/Scene7.php');

class ProductPage {
 
  private $v_style_number;
  private $v_old_style_number;
  private $v_old_or_new_style;
  private $i_site;
  private $_QBD_info;
 

  public function __construct($p_style_number) {
    $this->v_style_number = $p_style_number;    
  }

  //display a page
  public function displayPage() {

  	$i_site_init = $this->i_site;
  	
    $screen_title = "PPAGE" ;
    $trck_flag    = 'N' ;

    //check for any customer/source code pricing
    //session_start();    

    //always modified

    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    $i_site_init = new SiteInit('product');
    $i_site_init->loadInit($v_connect_mssql);
       
    /* Change# R12-001 */
    /* from url we are reciving both old and new style numbers. against old style we are getting new 
     * style from style info table. still for Bazzarvoice case we are giving preference to old style 
     * if old style is not available then we are sending new style number in bazzarvoice case...*/
    
    $this->get_valid_style($this->v_style_number,$p_new_style,$p_old_style);
    $this->v_style_number=$p_new_style;
    $this->v_old_style_number=$p_old_style;    
    if(!empty($p_old_style)){
      $this->v_old_or_new_style=$p_old_style;
    }else{
      $this->v_old_or_new_style=$p_new_style;
    }    
       
    //this will get QBD related info if applicable
    $this->_QBD_info = $this->get_QBD_info();
    
    if ( !empty( $_SESSION['s_cust_price_list']) || !empty($_SESSION['s_source_code']) ||  $this->_QBD_info['active'] == 'Y' || !empty($_REQUEST['scode'])) {
      global $mssql_db;
      get_mssql_connection();      
    }
    
    global $web_db ;

    include_once(FPATH . 'ppage_functions');

    $_GET['stynum'] = ( $_GET['stynum'] ? $_GET['stynum'] : $this->v_style_number);

    if (!$_POST['stynum']) {
      $this->v_style_number = strtoupper($_GET['stynum']) ;
      $p_xref         = strtoupper($_GET['xref']) ;
    }

    $this->v_style_number = trim($this->v_style_number) ;
    $remove_char = array("\\", "'");
    $this->v_style_number = str_replace($remove_char, "", $this->v_style_number) ;

    ob_start();
    $valid_style = $this->validate_style($v_forecast);
    $style_display = ob_get_contents();
    ob_end_clean();

    $i_site_init->loadMain();

    if ($valid_style) {
      echo $style_display;
    }

    if ($valid_style != 1) {
      $msg = $i_site_init->get_timestamp() ;
      $msg = $msg . '  ' . $this->v_style_number ;
      include(FPATH . 'invalid_style');
    } // invalid style

    //CI Implementation
    // remarketing var
    $g_data["pageType"]="product";
    $g_data["prodid"]=$this->v_style_number;
    $g_data["totalvalue"]="";
    //
    $i_site_init->loadFooter($g_data);

  }
  
 /*
  * function for Shipping: Today, if ordered before 1PM CST on product pages
 */

  public function getShippingCutOff(){
           global $mssql_db ;
           
           $v_web_categories = "GSI_SHIPPING_CUTOFF";
           $v_web_profile = "WEB";
           $v_stmt = mssql_init("r12pricing..gsi_profile_value");

           gsi_mssql_bind($v_stmt, "@p_profile_scope", $v_web_profile, 'varchar', 50);
           gsi_mssql_bind($v_stmt, "@p_profile_name", $v_web_categories, 'varchar', 50);
           gsi_mssql_bind($v_stmt, "@o_profile_value", $v_ship_time, 'varchar', 50, true);

           $v_result = mssql_execute($v_stmt);

           $data = $v_ship_time;
           if($data<12){
                $data = ($data)."AM";
           }else $data = ($data-12)."PM";

          return $data;
  }  
  
  /*
   * Author Chantrea (10/18/2012) for Ajax Call get New SKU Number post to GA Tracking 
   */
  public static function getSKUNumber($inv_item_id){
      global $mssql_db ;         
      $v_sp = mssql_init("direct.dbo.gsi_get_new_sku");
      gsi_mssql_bind ($v_sp, "@inventory_item_id", $inv_item_id, "int", 20, false);
      gsi_mssql_bind($v_sp,"@skunumber",$skunumber,"varchar",50,true);   
  
      $v_result = mssql_execute($v_sp);
      if (!$v_result){
         display_mssql_error("gsi_get_new_SKU" . $msg, "call from ProductPage.php");
       }
      mssql_free_statement($v_sp);
      mssql_free_result($v_result);
      return $skunumber;
  }
  
  /*
   * Author Hosam Mahmoud(1/18/2015) for Ajax Call get New ATP  
   */
	public static function checkATP($inv_item_id){
  		global $mssql_db ; 
		$return = ATPExtendedSoap::checkATP($inv_item_id,1);
      	return $return;
  	}

  	/*
  	 * Author Hosam Mahmoud(1/18/2015) for Ajax Call get New ATP
  	 */
  	public static function checkATPProc($inv_item_id){
  	  		global $mssql_db ;
  	  		$atp_arr = array(array("iiv"=>$inv_item_id,"qty"=>"1"));
  	  		$return = ATPExtendedSoap::checkATPProc($atp_arr);
  	  		return $return;
  	}
  	/*
   * Author Hosam Mahmoud(1/18/2015) for Ajax Call get New ATP  
   */
	public static function checkATPMSG($inv_item_id,$qtp){
  		global $mssql_db ; 
		$return = ATPExtendedSoap::checkATP($inv_item_id,$qtp);
      	return $return;
  	}
  	
  	/*
  	 * Author Hosam Mahmoud(1/18/2015) for Ajax Call get New ATP
  	 */
  	public static function checkATPMSGProc($inv_item_id,$qtp){
  	  		global $mssql_db ;
  	  		$atp_arr = array(array("iiv"=>$inv_item_id,"qty"=>$qtp));
  	  		$return = ATPExtendedSoap::checkATPProc($atp_arr);
  	  		return $return;
  	}
  	
  /*R12-001*/
  /* This function will return us the active new and old style  */
  
  function get_valid_style($p_style,&$p_new_style,&$p_old_style){
   	global $web_db;
  	$v_avi_rec=0;
      $sql="select sia.style_number
                   ,sia.old_style
            from gsi_style_info_all sia
                 , gsi_cmn_style_data csd
            where (sia.old_style='$p_style' or sia.style_number='$p_style')
            and sia.style_number=csd.style_number";
      
  	  $result = mysqli_query($web_db, $sql);
  	  $v_avi_rec=mysqli_num_rows($result);
  	  $myrow = mysqli_fetch_array($result, MYSQLI_ASSOC);
  	  if($v_avi_rec>0){
  	    $p_new_style=$myrow["style_number"];
  	    $p_old_style=$myrow["old_style"];
  	  }  	
  }
  
  public function validate_style ($p_forecast) {

    global $mssql_db ;
    global $web_db;
    global $record_sequence ;
    global $override_header_contents ;
    global $items;
    global $p_group;
    global $p_group_number;
    global $p_group_segment2;
    global $p_group_segment3;
    global $p_group_segment4;
    global $p_group_segment5;

    $p_valid_style = 0 ;
 
    $sql="select
       cat.category_id as item_category_id
      ,cat.segment2	as	parent_category_name
      ,cat.description as item_category_desc
      ,cat.subclass
      ,brn.description as manufacturer_desc
      ,brn.brand_image as manufacturer_image_file
      ,gcs.segment2
      ,gcs.segment3
      ,gcs.segment4
      ,gcs.segment5
      ,gcs.segment6
      ,gcs.segment7
      ,gcs.segment8
      ,gcs.segment9
      ,gcs.segment10
      ,gcs.segment2_values_desc segment2_values
      ,gcs.segment3_values_desc segment3_values
      ,gcs.segment4_values_desc segment4_values
      ,gcs.segment5_values_desc segment5_values
      ,gcs.segment6_values_desc segment6_values
      ,gcs.segment7_values_desc segment7_values
      ,gcs.segment8_values_desc segment8_values
      ,gcs.segment9_values_desc segment9_values
      ,gcs.segment10_values_desc segment10_values
      ,gcs.segment2_values segment2_val
      ,gcs.segment3_values segment3_val
      ,gcs.segment4_values segment4_val
      ,gcs.segment5_values segment5_val
      ,gcs.segment6_values segment6_val
      ,gcs.segment7_values segment7_val
      ,gcs.segment8_values segment8_val
      ,gcs.segment9_values segment9_val
      ,gcs.segment10_values segment10_val
      ,gcs.skus_str
      ,gcs.item_id_str
      ,gcs.price_str
      ,gcs.act_price_str
      ,gcs.orig_price_str
      ,gcs.price_svg_str
      ,gcs.atp_str
      ,gcs.attr_values_cnt_str
      ,gcs.style_number record_sequence
      ,1 valid_style
	from  gsi_cmn_style_data gcs
	     ,gsi_style_info_all gsi
	     ,gsi_categories cat
	     ,gsi_brands brn
	where gcs.style_number='".mysqli_real_escape_string($web_db,$this->v_style_number)."'
	and gcs.style_number=gsi.style_number
	and gsi.brand_id=brn.brand_id
	and gsi.web_category_id=cat.category_id
	and cat.category_set_name='GSI WEB CATALOG'";
    
    $result = mysqli_query($web_db, $sql);
    display_mysqli_error($sql) ;
    $myrow = mysqli_fetch_array($result) ;
    
    $item_category_desc      = $myrow["item_category_desc"] ;
    $parent_category_name	 = $myrow["parent_category_name"];    
    $item_category_id        = $myrow["item_category_id"] ;
    $subclass                = $myrow["subclass"] ;
    $manufacturer_desc       = $myrow["manufacturer_desc"] ;
    $manufacturer_image_file = $myrow["manufacturer_image_file"] ;    
    $segment2                = $myrow["segment2"] ;
    $segment3                = $myrow["segment3"] ;
    $segment4                = $myrow["segment4"] ;
    $segment5                = $myrow["segment5"] ;
    $segment6                = $myrow["segment6"] ;
    $segment7                = $myrow["segment7"] ;
    $segment8                = $myrow["segment8"] ;
    $segment9                = $myrow["segment9"] ;
    $segment10               = $myrow["segment10"] ;
   	$segment2_values         = $myrow["segment2_values"] ;
    $segment3_values         = $myrow["segment3_values"] ;
    $segment4_values         = $myrow["segment4_values"] ;
    $segment5_values         = $myrow["segment5_values"] ;
    $segment6_values         = $myrow["segment6_values"] ;
    $segment7_values         = $myrow["segment7_values"] ;
    $segment8_values         = $myrow["segment8_values"] ;
    $segment9_values         = $myrow["segment9_values"] ;
    $segment10_values        = $myrow["segment10_values"] ;
   	$segment2_val         = $myrow["segment2_val"] ;
    $segment3_val            = $myrow["segment3_val"] ;
    $segment4_val            = $myrow["segment4_val"] ;
    $segment5_val            = $myrow["segment5_val"] ;
    $segment6_val            = $myrow["segment6_val"] ;
    $segment7_val            = $myrow["segment7_val"] ;
    $segment8_val            = $myrow["segment8_val"] ;
    $segment9_val            = $myrow["segment9_val"] ;
    $segment10_val           = $myrow["segment10_val"] ;
    $skus_str                = $myrow["skus_str"] ;
    $item_id_str             = $myrow["item_id_str"] ;
    $price_str               = $myrow["price_str"] ;
    $act_price_str           = $myrow["act_price_str"] ;
    $orig_price_str          = $myrow["orig_price_str"] ;
    $price_svg_str           = $myrow["price_svg_str"] ;
    $atp_str                 = $myrow["atp_str"] ;    
    $attr_values_cnt_str     = $myrow["attr_values_cnt_str"] ;
    $record_sequence         = $myrow["record_sequence"] ;
    $p_valid_style           = $myrow["valid_style"] ;    
    mysqli_free_result($result);
 
    /*R12-001*/
    /*Replace old category id with new category id
     * check for logo item*/
    //$item_category_id 
    if ($parent_category_name=="LOGO APPAREL") {   /* Logo Apparel (old id:1696534)*/
      $v_logo_item = "Y";
      $v_logo_apparel = "Y";
      $v_logo_other = "N";
    } elseif ($parent_category_name == "LOGO BALLS" || $parent_category_name=="LOGO ACCESSORIES") {
      $v_logo_item = "Y";
      $v_logo_apparel = "N";
      $v_logo_other = "Y" ;
    } else {
      $v_logo_item = "N" ;
      $v_logo_apparel = "N";
      $v_logo_other = "N";
    }

    //if ($parent_category_name == "TENNIS RACQUETS" || $parent_category_name == "CONSUMABLES") { //unstrung tennis rocket
    if ($subclass == "PERFORMANCE RACQUETS")
    {
      $v_disp_help = 'Y';
    }

    /* Copying original variables into temporary variable. */
   	$item_category_desc_temp = $item_category_desc;
   	$manufacturer_desc_temp = $manufacturer_desc;
   	
   	/* Appending \' for all single quotes to avoid error in javascript. */
   	$item_category_desc_temp = str_replace("'", "\'",$item_category_desc_temp);
   	$manufacturer_desc_temp = str_replace("'", "\'",$manufacturer_desc_temp);
   	
    $dropship_str = "";
    if($this->find_dropship_yesno($this->v_style_number) == 'Y') {
     $dropship_str = $this->get_dropship_array($item_id_str);
    }

    $v_show_atp = 'N';
    
    if ($p_valid_style == 1) {
      // check if style belongs to a Group
/*      $sql = "select gcg.group_name
                     , gcs.segment2
                     , gcs.segment3
                     , gcs.segment4
                     , gcs.segment5 
              from gsi_cmn_product_groups gcg
                   , gsi_cmn_product_group_styles gcs 
              where gcg.record_status = 'VALID' 
              and gcg.group_id = gcs.group_id 
              and gcs.style_number = '$this->v_style_number'" ;
      
      $result = mysqli_query($web_db, $sql);
      display_mysqli_error($sql) ;
      $myrow = mysqli_fetch_array($result) ;
      $v_group_name     = $myrow["group_name"] ;
      $p_group_segment2 = $myrow["segment2"] ;
      $p_group_segment3 = $myrow["segment3"] ;
      $p_group_segment4 = $myrow["segment4"] ;
      $p_group_segment5 = $myrow["segment5"] ;*/
    	
	   $v_group_name     = '' ;
       $p_group_segment2 = '' ;
       $p_group_segment3 = '' ;
       $p_group_segment4 = '' ;
       $p_group_segment5 = '' ;
    	

      $sql = "select ifnull(bran.description,'') as brand_desc
      		   ,ifnull(gci.description,'') as  style_desc
		       ,gci.large_image_file image_file
		       ,gci.long_copy
		       ,gci.contract_rqmts
		       ,gci.disclaimers
		       ,ifnull(gci.min_order_qty, 1) min_order_qty
		       ,gci.used_item_flag
		       , 'In Stock' as atp_message
			from  gsi_brands bran
			     ,gsi_style_info_all gci
			where gci.style_number='$record_sequence'
			and bran.brand_id=gci.brand_id";
      
      $result = mysqli_query($web_db, $sql);
      display_mysqli_error($sql) ;
      $myrow = mysqli_fetch_array($result) ;
      extract( $myrow);

      $brand_name		 = $myrow['brand_desc'];
      $style_desc		 = $myrow['style_desc'];      
      $product_image     = $myrow["image_file"];
      $product_long_copy = $myrow["long_copy"];     
      $contract_rqmts    = $myrow["contract_rqmts"];
      $disclaimers       = $myrow["disclaimers"];      
      $min_order_qty     = $myrow["min_order_qty"];
      $preowned_style	 = $myrow["used_item_flag"];
      $atp_message       = $myrow["atp_message"]; 
           
      $product_title     = $brand_name .' '. $style_desc;
      $product_title_disp= $product_title;
      
      if($min_order_qty==0){  /*if by default minimum qty is 0 then we are forcing it to 1*/
      	$min_order_qty=1;
      } 
                  
      mysqli_free_result($result);
       
      /*R12-001*/
      /* Now in R12 we are showing upto 10 segments so i added 6 to 10 segments*/
      $v_segment_count = 0;

      if (!empty($segment2)) {
        $v_segment_count++;
        $attr_types_str = '"' . $segment2 . '"' ;
        $attr_names_str = '"' . ucfirst( strToLower( $segment2)) . '"' ;
      }

      if (!empty($segment3)) {
        $v_segment_count++;
        $attr_types_str .= ',"' . $segment3 . '"';
        $attr_names_str .= ',"' . ucfirst( strToLower( $segment3)) . '"';
      }
      if (!empty($segment4)) {
        $v_segment_count++;
        $attr_types_str .= ',"' . $segment4 . '"';
        $attr_names_str .= ',"' . ucfirst( strToLower( $segment4)) . '"';
      }
      if (!empty($segment5)) {
        $v_segment_count++;
        $attr_types_str .= ',"' . $segment5 . '"';
        $attr_names_str .= ',"' . ucfirst( strToLower( $segment5)) . '"';
      }      
      
      if (!empty($segment6)) {
        $v_segment_count++;
        $attr_types_str .= ',"' . $segment6 . '"';
        $attr_names_str .= ',"' . ucfirst( strToLower( $segment6)) . '"';
      }
      
      if (!empty($segment7)) {
        $v_segment_count++;
        $attr_types_str .= ',"' . $segment7 . '"';
        $attr_names_str .= ',"' . ucfirst( strToLower( $segment7)) . '"';
      }
      
      if (!empty($segment8)) {
        $v_segment_count++;
        $attr_types_str .= ',"' . $segment8 . '"';
        $attr_names_str .= ',"' . ucfirst( strToLower( $segment8)) . '"';
      }
      
      if (!empty($segment9)) {
        $v_segment_count++;
        $attr_types_str .= ',"' . $segment9 . '"';
        $attr_names_str .= ',"' . ucfirst( strToLower( $segment9)) . '"';
      }
      
      if (!empty($segment10)) {
        $v_segment_count++;
        $attr_types_str .= ',"' . $segment10 . '"';
        $attr_names_str .= ',"' . ucfirst( strToLower( $segment10)) . '"';
      }
      

      //tracking for coremetrics (older version pre 11/28/2005 - Serna)
      $cm_category    = post_or_get('cseq') ;
      if(!empty($cm_category)) {
        $cm_category = ereg_replace('~', '', $cm_category);
      } else {
        //  default setting
        $cm_category = "EXPRESSORDER";
      }
      $cm_lcode    = post_or_get('lcode') ;
      if (!empty($cm_lcode))
        $cm_category = $cm_lcode ;

      $attr_types_str = str_replace(" ", "_", $attr_types_str);
      $v_cust_price_list_id  = $_SESSION['s_cust_price_list'];
      $v_source_code         = $_SESSION['s_source_code'] ;
      $std_price_str         = $price_str ;
      
      /*R12-001*/
      /*Replaced old QBD price list id (2408) with new id*/
      
      if ($this->_QBD_info['active'] == 'Y') {
      	$v_cust_price_list_id = WEB_PRICE_LIST_ID;	      	
      }
        
    /* Change# R12-001*/
    /* check for any customer/source code pricing or QBD price
     * added 4 new input parameters($item_id_str,$price_str,$price_svg_str,$orig_price_str)
     *  into below sql price procedure  
     */
        
      if( !empty($v_cust_price_list_id) ) {
      //if( !empty($v_cust_price_list_id) || !empty($v_source_code) ) {
      	
        $v_sp = mssql_init("gsi_cmn_item_reprice_style");
        $v_webprofile = C_WEB_PROFILE;
        gsi_mssql_bind ($v_sp, "@p_style_number", $this->v_style_number, "varchar", 80, false);
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
          display_mssql_error("gsi_cmn_item_reprice_style" . $msg, "call from ProductPage.php");
        }
        mssql_free_statement($v_sp);
        mssql_free_result ($v_result);

      } else {
        // add quote to orig price
        $orig_price_str = "\"" . str_replace(",", "\",\"", $orig_price_str) . "\"" ;
      }

       $this->override_header();

      if ($std_price_str != $price_str)
        $price_break_flag = "N";
        $inventory_item_id   = $this->get_first_item_id ($item_id_str) ;

      /************Coremeterics serna 11/28/2005 ********************/
      $cm_category_new=get_record_parent_1($this->v_style_number);
      $screen_name = $_SESSION['s_screen_name'] ;
      if (empty($screen_name))
        $screen_name = 'ps' ;
      $cm_category_new = $screen_name . $cm_category_new ;

      if($item_not_available==2) {
        $product_id=$_POST['stynum'];
        $product_name=$_POST['sdes'];
        $qty=$_POST['qty'];
        $script_name=$_SERVER['SCRIPT_NAME'];
        $query_string=$_SERVER['QUERY_STRING'];

        $product_title=str_replace('"','',$product_title);
        $product_title_disp = str_replace('"','&quot;',$product_title_disp);
        $product_name=str_replace('"','',$product_name);
      } else {

        $product_title=str_replace('"','',$product_title);
        $product_title_disp = str_replace('"','&quot;',$product_title_disp);
        $product_name=str_replace('"','',$product_name);

        ?>
        <!-- Used for Looking at Products -->
        <SCRIPT language="javascript1.1" type="text/javascript" >
                cmCreateProductviewTag("<?=$this->v_style_number?>", "<?=$product_title?>","<?=$cm_category_new?>");
        </SCRIPT>
        <? 
      }

      /*************End  Coremeterics serna 11/28/2005 ****************/
      $sno = 1;

      ob_start();
      if (!empty($v_group_name)){
        $this->define_product_array($sno);
        // the next validate_group function is absolute no need for it any more 
        //$p_valid_style = validate_group($v_group_name, $sno);
        if ($p_valid_style == 1) {
          $p_group_number = $this->v_style_number;
        }
        $sno++;
      }
      $p_group = "N";
      $this->define_product_array($sno);

      if($this->has_scene7()){
        $avl_images_str = $this->get_avl_images();
        $this->setproduct_property($sno, 'avl_images', "$avl_images_str", true);
        $imageZoomViewer = $this->get_scene7_zoom_viewer();
      }

      $v_atp_formatted = preg_replace('/([0-9]{1,2})-([A-Z]{3})-([0-9]{4})/', '$2 $1, $3', $atp_str);
      $va_abbreviated_months = array('JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC');
      $va_full_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
      $v_atp_formatted = str_replace($va_abbreviated_months, $va_full_months, $v_atp_formatted);

      $this->setproduct_property($sno, 'sku_prices', "$price_str", true);
      $this->setproduct_property($sno, 'sku_prices2', "$orig_price_str",true);
      $this->setproduct_property($sno, 'act_sku_prices', "$act_price_str",true);
      $this->setproduct_property($sno, 'price_svg', "$price_svg_str",true);
      $this->setproduct_property($sno, 'item_ids', "$item_id_str",true);
      $this->setproduct_property($sno, 'attr_names', "$attr_names_str",true);
      $this->setproduct_property($sno, 'attr_types', "$attr_types_str",true);
      //$this->setproduct_property($sno, 'sku_atp', "$atp_str",true);
      $this->setproduct_property($sno, 'sku_atp', "$v_atp_formatted",true);
      $this->setproduct_property($sno, 'sku_giftwrap', "$giftwrap_str",true);
      $this->setproduct_property($sno, 'sku_numbers', "$skus_str",true);
      $this->setproduct_property($sno, 'atp_message', "$atp_message",false);
      $this->setproduct_property($sno, 'no_price_message', "<span class=\"save\">Price will be displayed in shopping cart</span>",false);
      $this->setproduct_property($sno, 'min_order_qty', "$min_order_qty",false);
      $this->setproduct_property($sno, 'max_order_qty', "9999",false);
      $this->setproduct_property($sno, 'lcode', "$cm_category",false);
      $this->setproduct_property($sno, 'dropship', "$dropship_str",true);
      $this->setproduct_property($sno, 'group_price_str', "",false);
      $this->setproduct_property($sno, 'group_item', $p_group,false);

     
      
      
      //changes for MyJoys shoes
      $v_myjoys_obj = new MyJoys();
      $v_myjoys_obj->v_style = $this->v_style_number; 
  	  $v_is_myjoys = $v_myjoys_obj->is_myjoys();
      $va_myjoys = $v_is_myjoys;
  	  if (is_array($v_is_myjoys) && !empty($v_is_myjoys))
      	$v_is_myjoys = true;
      else
      	$v_is_myjoys = false;
      if (!$v_is_myjoys) {

        $this->setproduct_property($sno, 'attr_values_count', "$attr_values_cnt_str", true);
        $this->setproduct_property($sno, 'group_segment2', $p_group_segment2,false);
        $this->setproduct_property($sno, 'group_segment3', $p_group_segment3,false);
        $this->setproduct_property($sno, 'group_segment4', $p_group_segment4,false);
        $this->setproduct_property($sno, 'group_segment5', $p_group_segment5,false);
        $this->setproduct_property($sno, 'stynum', $this->v_style_number,false);
        if (!empty($p_group_segment2))
          $items[$sno]['group_segment2']        = $p_group_segment2;
        if (!empty($p_group_segment3))
          $items[$sno]['group_segment3']        = $p_group_segment3;
        if (!empty($p_group_segment4))
          $items[$sno]['group_segment4']        = $p_group_segment4;
        if (!empty($p_group_segment5))
          $items[$sno]['group_segment5']        = $p_group_segment5;

        /*R12-001*/
        /*Added new segments from 6 to 10*/
          
        if (!empty($segment2_values)) {
          $this->setproduct_property($sno, 'seg2_list', $segment2_values, true);
          $this->setproduct_property($sno, 'seg2_val', $segment2_val, true);
          $items[$sno]['segment2']        = $segment2;
          $items[$sno]['segment2_values'] = $segment2_values;
          $_SESSION['segment2_values']=$segment2_val;
        }
        if (!empty($segment3_values)) {
          $this->setproduct_property($sno, 'seg3_list', $segment3_values, true);
          $this->setproduct_property($sno, 'seg3_val', $segment3_val, true);
          $items[$sno]['segment3']        = $segment3;
          $items[$sno]['segment3_values'] = $segment3_values;
          $_SESSION['segment3_values']=$segment3_val;
        }
        if (!empty($segment4_values)) {
          $this->setproduct_property($sno, 'seg4_list', $segment4_values, true);
          $this->setproduct_property($sno, 'seg4_val', $segment4_val, true);
          $items[$sno]['segment4']        = $segment4;
          $items[$sno]['segment4_values'] = $segment4_values;
          $_SESSION['segment4_values']=$segment4_val;
        }
        if (!empty($segment5_values)) {
          $this->setproduct_property($sno, 'seg5_list', $segment5_values, true);
          $this->setproduct_property($sno, 'seg5_val', $segment5_val, true);
          $items[$sno]['segment5']        = $segment5;
          $items[$sno]['segment5_values'] = $segment5_values;
          $_SESSION['segment5_values']=$segment5_val;
        }       
        
        if (!empty($segment6_values)) {
          $this->setproduct_property($sno, 'seg6_list', $segment6_values, true);
          $this->setproduct_property($sno, 'seg6_val', $segment6_val, true);
          $items[$sno]['segment6']        = $segment6;
          $items[$sno]['segment6_values'] = $segment6_values;
          $_SESSION['segment6_values']=$segment6_val;
        }
        
        if (!empty($segment7_values)) {
          $this->setproduct_property($sno, 'seg7_list', $segment7_values, true);
          $this->setproduct_property($sno, 'seg7_val', $segment7_val, true);
          $items[$sno]['segment7']        = $segment7;
          $items[$sno]['segment7_values'] = $segment7_values;
          $_SESSION['segment7_values']=$segment7_val;
        }
        
        if (!empty($segment8_values)) {
          $this->setproduct_property($sno, 'seg8_list', $segment8_values, true);
          $this->setproduct_property($sno, 'seg8_val', $segment8_val, true);
          $items[$sno]['segment8']        = $segment8;
          $items[$sno]['segment8_values'] = $segment8_values;
          $_SESSION['segment8_values']=$segment8_val;
        }
        
        if (!empty($segment9_values)) {
          $this->setproduct_property($sno, 'seg9_list', $segment9_values, true);
          $this->setproduct_property($sno, 'seg9_val', $segment9_val, true);
          $items[$sno]['segment9']        = $segment9;
          $items[$sno]['segment9_values'] = $segment9_values;
          $_SESSION['segment9_values']=$segment9_val;
        }
        
        if (!empty($segment10_values)) {
          $this->setproduct_property($sno, 'seg10_list', $segment10_values, true);
          $this->setproduct_property($sno, 'seg10_val', $segment10_val, true);
          $items[$sno]['segment10']        = $segment10;
          $items[$sno]['segment10_values'] = $segment10_values;
          $_SESSION['segment10_values']=$segment10_val;
        }       

      } else {
        $this->setproduct_property($sno, 'attr_values_count', "", true);
      }


      $items[$sno]['group'] = 'N';
      $init_product = ob_get_contents();
      ob_end_clean();
    } else {
      $sno = 1;
      ob_start();
      $this->define_product_array($sno);
      // the next validate_group function is absolute no need for it any more
      //$p_valid_style = validate_group($this->v_style_number, $sno);
      if ($p_valid_style == 1) {
        $p_group_number = $this->v_style_number;
      }
      $init_product = ob_get_contents();
      ob_end_clean();

    }


    if (!empty($p_valid_style)){
      if ($p_group == 'Y') {
        $display_links = $items[1]['display_links'];
        $product_long_copy = $items[1]['product_long_copy'];
        $manufacturer_image_file = $items[1]['manufacturer_image_file'];
        $manufacturer_url = $items[1]['manufacturer_url'];
        $product_image = $items[1]['product_image'] ;
        $product_title = $items[1]['product_title'] ;
      }
      global $item_not_available ;
      
      if($item_not_available==2){
        include(FPATH . 'add_item_view');
      } else {


        $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));

        include(FPATH . 'ppage');

        //if no segments, we already know our inventory item id
        if((empty($segment2)) || $v_is_myjoys ) {
          $z_view->inventory_item_id = $inventory_item_id;
        }

        $z_view->action = (!empty($_SESSION['s_screen_name']) ? '/' . $_SESSION['s_screen_name'] : '');
        $z_view->show_quantity = TRUE;
        $z_view->myjoys = $va_myjoys;
        
        
        if($subclass == "PERFORMANCE RACQUETS") {
          $z_view->action .= '/tstringing.php';
          $z_view->button = 'continue_tstringing';
        } else if($v_logo_apparel == 'Y') {
          $z_view->action .= '/order_details.php';
          $z_view->button = 'continue_logo';
        } else if($v_is_myjoys) {
          $z_view->action .= '/myjoys.php?cseq=EXPRESSORDER&sku=' . $this->v_style_number;
          $z_view->button = 'continue_myjoys';
          $z_view->show_quantity = FALSE;
          $z_view->continue_note = 'Click "Continue" to customize your MyJoys.';
        } else {
          $z_view->action .= '/add_to_cart.php?cseq=EXPRESSORDER';
          $z_view->button = 'addtocart';
        }


        $z_view->item_category_id = $item_category_id;
        $z_view->logo_item = $v_logo_item;
        $z_view->logo_apparel = $v_logo_apparel;
        $z_view->logo_other = $v_logo_other;
        //$this->is_excluded_item($v_source_code, $v_is_excluded);
        $z_view->source_code = $v_source_code;
        //$z_view->is_excluded = $v_is_excluded;

        if ($p_group == 'Y') {
          $this->get_promo_messages($p_group_number, $v_shipping_message, $v_9090_message, $v_wf_message) ;
        } else {
          $this->get_promo_messages('', $v_shipping_message, $v_9090_message, $v_wf_message) ;
        }

        //keeping the code form ppage for now
        if(!empty($header)) {
          $z_view->header_info = $header;
        }
        
        //we need to know if item is preowned; if it is, remove Q&A section
        $z_view->is_preowned = 'N';
        if ($preowned_style == 'Y') {
          $z_view->is_preowned = 'Y';
        } 

        $z_view->shipping_message = $v_shipping_message;
        $z_view->v_9090_message = $v_9090_message;
        $z_view->wf_message = $v_wf_message;

        $this->define_sku_options($v_disp_help, $v_logo_item, $v_logo_apparel,$v_segment_count, $va_sku_options); 

        $z_view->init_product = $init_product;

        $z_view->sku_options = $va_sku_options;

        $this->generatePersonalizationCheckboxes($pa_checkbox_data);
        
        if($v_logo_apparel == 'N') {
          $this->generatePersonalizationData($v_service_type, $va_personalization_data, $v_per_line_count);
          $z_view->per_line_count = $v_per_line_count;
        }
        if($v_is_myjoys) {        	
          $this->generateMyJoysTeams($myjoys_team);
          $z_view->myjoys_team_options = $myjoys_team;        	
        }

        $z_view->personalization_item = $v_service_type;

        $z_view->personalization_checkbox_data = $pa_checkbox_data;//$va_personalization_checkboxes;        
        $z_view->personalization_data = $va_personalization_data;

        $z_view->personalize_url = $this->findAvailablePersonalization();
        
        $z_view->isPersonalizedstyle = $this->isPersonalizedstyle();

        if(!empty($z_view->personalize_url)) {
          
          $v_seo_obj       = new seo($z_view->personalize_url);
		  $p_new_style_url = $v_seo_obj->get_seo_url();
        	
          $z_view->personalize_url = $p_new_style_url;
        }


        //protocol is used for chat, but it might be useful for other things later
        $v_port = $_SERVER[SERVER_PORT];

        if($v_port == '443') {
          $z_view->url_protocol = 'https';
        } else {
          $z_view->url_protocol = 'http';
        }

        //browser version - we only care whether it's IE6 for now
        $z_view->is_ie6 = FALSE;
        if (ereg( 'MSIE ([0-9].[0-9]{1,2})', $_SERVER[HTTP_USER_AGENT], $va_log_version)) {
          $v_browser_ver = $va_log_version[1];
          if($v_browser_ver == '6.0') {
            $z_view->is_ie6 = TRUE;
          }
        }

/**
        if($_SESSION['s_enable_chat'] === TRUE) {
          $z_view->chat_dept_id = '24194';
          $z_view->chat_default_screen_name = 'PR';
        } else {
          $z_view->chat_dept_id = '';
        }
**/
        $z_view->isp_available = 'N';
        $z_view->isp_available = $this->find_isp_yesno();

        $z_view->atp_message = $atp_message;

        $this->getSpecialOffers($va_special_offers);

        $z_view->special_offers = $va_special_offers;
 
        if($z_view->logo_apparel == 'Y' || $z_view->logo_other == 'Y') {
          $this->getPricingArray($va_pricing_array, $v_low_quantity);
          $z_view->logo_pricing = $va_pricing_array;
        } elseif($price_break_flag != 'N' && strpos($price_str,"N") === FALSE) {
          $this->getPriceBreaks($va_pricebreak_array, $v_low_quantity);
          $z_view->price_breaks = $va_pricebreak_array;
        }
        if(empty($v_low_quantity)) {
          $v_low_quantity = 1;
        }
        $z_view->default_quantity = $v_low_quantity;

        $z_view->scene7_base_url = SCENE7_URL;
        $scene7 = new Scene7($this->v_style_number);
        $v_main_image = $scene7->getMainImage("P");
        
        if(!$v_main_image)
        {
            $this->get_main_image($product_image, $v_main_image);
        }
        $z_view->cross_sell_1 = $cross_sell_1;
        $z_view->cross_sell_2 = $cross_sell_2;
        $z_view->cross_sell_3 = $cross_sell_3;
		
        /* before this upsale feed shows only upto 6 style / items 
         * now it will show upto 10 items and we are also removing repeated style numbers  
         * */
        $va_also_purchased = array();       
        $v_sequence=1; // Initializing $v_sequence counter to loop until end of record
        do{
	        $this->generateAlsoPurchased($this->v_style_number, $v_sequence, $va_also_purchased_tmp);
		    if(is_array($va_also_purchased_tmp)) {
		    	$va_also_purchased[] = $va_also_purchased_tmp;
		    }
		    $v_sequence++;
        }while($v_sequence<11); /* Since, we need 2 rows consisting of 5 records in each row. */ 
        
        /* Eliminate redundant array values from the array.
         * 
         */ 
		foreach ($va_also_purchased as $ak_k=>$av_ap)
			$new_ap[$ak_k] = serialize($av_ap); // Serializing
		$va_uniq_ap = array_unique($new_ap);
		
		foreach($va_uniq_ap as $ak_k=>$av_ser)
			$va_t[$ak_k] = unserialize($av_ser); // Unserializing
		
		/* **************************************************************** */
		$va_also_purchased=$va_t; // transferring the legit array values to va_also_purchased
       	
		unset($va_t); // Unsetting the $va_t
				
        $z_view->also_purchased = $va_also_purchased;
        $z_view->has_scene7 = $this->has_scene7();
        $z_view->avl_images_str = $avl_images_str;
        $z_view->imageZoomViewer = $imageZoomViewer;
        $z_view->main_image = $v_main_image;
        $z_view->render_set = $this->get_render();
        $z_view->spin_set = $this->get_scene7_spin_viewer();
        $z_view->video_set = $this->get_video();
        $z_view->style_number = $this->v_style_number;
        $z_view->old_or_new_style = $this->v_old_or_new_style;
        $z_view->product_title = $product_title_disp;
        $z_view->show_specs_tab = $this->has_specifications();
        $z_view->record_sequence=$record_sequence;
		$z_view->category = $this->getcategoryname();
		$z_view->brandname = $manufacturer_desc;
        $v_server_name = $_SERVER['SERVER_NAME'];      
        $z_view->server_name = $v_server_name;
        
        //by Luis
        $v_zipcode = $_SESSION['s_zipcode'];
        if( strlen($v_zipcode) > 5 ){
            $v_zipcode = substr($v_zipcode, 0, 5);
        }
        $z_view->zipcode = $v_zipcode;
        
        //bazaarvoice implementation
		
		if (!empty($_SESSION['s_user_name']))
		{
		 	//encode using BV's schema
		  	$v_s_user_nm = $_SESSION['s_user_name'];
		  	$v_date = date('Y-m-d');
		  	$bv_token = "date=$v_date&userid=$v_s_user_nm&emailaddress=$v_s_user_nm";  	
		    $v_bv_user_id = md5("Qdk821!w" . $bv_token) . bin2hex($bv_token);		
		
		}else {
		  	$v_bv_user_id = '';
		}
		
		
		if($v_port == '443') {
	      $v_url_protocol = 'https';
	    } else {
	      $v_url_protocol = 'http';
	    }
	
	    $v_server_name = $_SERVER['SERVER_NAME'];
	   
	    $z_view->bv_server_name = $v_url_protocol . "://" . $v_server_name; 
	
	    $z_view->bv_user_id = $v_bv_user_id;
	    
        

        $v_custom_style = $this->get_associated_custom_style();
        if(!empty($v_custom_style)) {
          $z_view->custom_url = '/';
          if(!empty($_SESSION['s_screen_name'])) {
            $z_view->custom_url .= $_SESSION['s_screen_name'] . '/';
          }
          $z_view->custom_url .= 'oem_ocf.php?from_ppage=1&base_style=' . $v_custom_style;
          $tempDataCustomStyleNumber = explode("||",$v_custom_style);
          $z_view->custom_style_no = $tempDataCustomStyleNumber[0];
          $z_view->custom_style_ii_id = $tempDataCustomStyleNumber[1];
          $z_view->custom_style_only = $tempDataCustomStyleNumber[2];
        }

        if(!empty($z_view->render_set)) {
          $this->get_thumbs($va_thumb_list);
          $z_view->thumb_list = $va_thumb_list;
        }

        $z_view->breadcrumbs = $this->get_breadcrumbs($item_category_id, $z_view->product_title);
        $this->getBrowseLinkData($v_browse_link_data);
        $z_view->browse_link_data = $v_browse_link_data;
                
		$z_view->QBD_info = $this->_QBD_info;
		$z_view->v_style_number = $this->v_style_number;
		/*
		 * Add your Recent View by using flexslider 
		 * Author Chantrea 03/02/2012
		 */
	  $obj_slider = new Slider($_SESSION ['recently_viewed_item_list']['order'],6,"review");
  
	  $z_view->objectrecentview = $obj_slider;
	  
	  
	  
	  /*
	  echo "<pre>";
	  echo var_dump($z_view);
	  echo "</pre>";
	  die();
	  */
	  
	
	/*
	 * End of Recent View
	 */
	    //get shipping cutoff
	    $z_view->show_shipping_time = $this->getShippingCutOff();
	    
	    // Get smart SEO html
	    $z_view->smartSEO = $this->getSEOHtml($this->v_style_number);
	    
        echo $z_view->render("product.phtml");
      }
    }


    // Set the pageTitle equal to description - vendor name
    global $pageTitle, $metaKeywords, $metaDescription;
    $pageTitle = $product_title . ' - ' . ucfirst( strToLower( $manufacturer_desc));
    $metaKeywords = $pageTitle;
    $metaDescription= $pageTitle;

    $_SESSION['product_title'] = $product_title;

    return $p_valid_style ;
  } // end of function validate_style
  

public function getSEOHtml($sku){
    $seoData = new stdClass();
      
      //http://seo-stg.bazaarvoice.com/{seo-key}/{deployment-zone}-{locale}/{content-type}/{subject-type}/{page-number}/{product-id}.htm
      // Add SEO data to the page if we're being hit by a search engine
    if (preg_match('/(msnbot|googlebot|teoma|bingbot|yandexbot|yahoo)/i', $_SERVER['HTTP_USER_AGENT'])){
          $url = "http://seo" . BV_ENVIRONMENT . ".bazaarvoice.com/" . BV_SEO_KEY . "/";
          
          // If we don't have the url to get for SEO information create it
          $bvrrp = post_or_get('bvrrp');

            if (!$bvrrp){
                $url .= "Main_Site-en_US/%%SUBJECT_TYPE%%/product/1/$sku.htm";

                  // Get SEO data for reviews and questions
                $subjectTypes = array("reviews", "questions");
                foreach ($subjectTypes as $subject){
                    $html = file_get_contents(str_replace('%%SUBJECT_TYPE%%', $subject, $url));
                          
                    // Replace place holder with page URL and save in object
                    $html = str_replace('{INSERT_PAGE_URI}', $_SERVER['REQUEST_URI'] . "?", $html);
                          
                    $seoData->{$subject} = $html;
                }
              
            }else{
                
                $url = $url . $bvrrp;

                $html = file_get_contents($url);
                
                $uri = $url=strtok($_SERVER["REQUEST_URI"],'?');

                //$html .= '<a class="bvseo-paginationLink" href="{INSERT_PAGE_URI}bvrrp=Main_Site-en_US/reviews/product/3/30039565.htm">This is a link test</a>';

                // Replace place holder with page URL and save in object
                $html = str_replace('{INSERT_PAGE_URI}', $uri . "?", $html);
                
                
                if (preg_match('/reviews/', $bvrrp)){
                     $seoData->reviews = $html;
                }else{
                    $seoData->questions = $html;
                }

            }

      }

    return $seoData;
  }

  public function setproduct_property($p_sno, $p_property_name, $p_property_value, $p_isarray){
    if($p_isarray === true) { ?>
      product_list[<?=$p_sno ?>][<?= "'" . $p_property_name . "'"?>] = new Array().concat(<?= $p_property_value?>);
    <? 
    } else { ?>
      product_list[<?=$p_sno ?>][<?= "'" . $p_property_name . "'"?>] = <?= "'" . str_replace('"', '', $p_property_value) . "'" ?>;
    <? 
    }

  }

  /*Change # R12_001 */
  /*Replaced sku flag table with item info table*/
  public function find_dropship_yesno() {
    global $web_db;

    $v_msg = 'N' ;
      
    $v_sql = "select count(*) n_cnt
			  from gsi_item_info_all
			  where segment1 = '$this->v_style_number'
			  and dropship_flag = 'Y'";

    $v_result = mysqli_query($web_db, $v_sql) ;
    $v_error = display_mysqli_error($v_sql);
    if (empty($v_error)){
      $va_row = mysqli_fetch_array($v_result);
      if($va_row['n_cnt'] > 0) {
        $v_msg = 'Y' ;
      }
    }

    return($v_msg);
  } // end of function find_dropship_yesno
  
  /* Change# R12-001 */
  /* Replaced the sku flag table with item info */
  public function get_dropship_array($item_id_str) {
    global $web_db;
    $v_dropship_flag = "";
    $item_ids = explode(",", $item_id_str);

    $idx = 0 ;
    while ($idx < count($item_ids)) {
      if ($item_ids[$idx] > 0) {
         $v_flag = 'N'; 		
                          
         $sql = "select dropship_flag,item_price 
         		 from gsi_item_info_all
                 where inventory_item_id = " . $item_ids[$idx] ."";
         
         $result = mysqli_query($web_db, $sql);
         display_mysqli_error($sql) ;
         if($row = mysqli_fetch_array($result))
           $v_flag = $row["dropship_flag"] ;
           $v_price = $row["item_price"] ;
         if (empty($v_flag))
           $v_flag = 'N';
         mysqli_free_result($result) ;

         if(($v_flag == 'Y') && ($v_price > 75)) {
           $v_flag = 'Y';
         } else {
           $v_flag = 'N';
         }

      }
      $v_dropship_flag[$idx] = '"'.$v_flag.'"';
      $idx++;
    }
    $dropship_str = implode(',', $v_dropship_flag);

    return ($dropship_str);
  } // end of function get_dropship_array 

  // Function Get Category Name for Google Tracking By Chantrea
  public function getcategoryname()
  {
  		global $web_db;    
  	     $sql = "select cat.DESCRIPTION as cat_description 
  	     from webdata.gsi_categories cat,
  	     webdata.gsi_style_info_all infoall 
  	     where infoall.STYLE_NUMBER = '$this->v_style_number' 
  	     and cat.CATEGORY_ID = infoall.WEB_CATEGORY_ID";
  	     $result = mysqli_query($web_db, $sql);
  	     while($row=mysqli_fetch_array($result))
  	     {
  	     	$catename = $row['cat_description'];
  	     }
  	     return $catename;
  }
  
  /*Change# R12_001*/
  public function override_header() {
    global $override_header_contents, $web_db;

    $by_style_filename = STATIC_HTML_PATH . '_meta_overrides/product/' . strToLower( $this->v_style_number) . '.html';    
    if(! file_exists( $by_style_filename)) {          
    	if("product"==strtolower(substr($_SERVER['REQUEST_URI'],1,7)) || "product"==strtolower(substr($_SERVER['REQUEST_URI'],1,7))){     	
        $by_common_filename = STATIC_HTML_PATH . '_meta_overrides/product/_product_default.html';
      }
    }
    
    $override_header_file = '';
    if ( file_exists( $by_style_filename))
      $override_header_file = $by_style_filename;
    else if ( file_exists( $by_common_filename))
      $override_header_file = $by_common_filename;
    if ($override_header_file) {       
     $sql = "select  sia.style_number as record_name
      			, brnd.description as manuf_desc
      			, sia.description
      			, cat.description as item_category_desc
			from  gsi_style_info_all sia
     			, gsi_brands brnd
     			, gsi_categories cat
			where sia.style_number='$this->v_style_number'
			    and sia.brand_id=brnd.brand_id
				and sia.web_category_id = cat.category_id
				and cat.category_set_name='GSI WEB CATALOG'";
      $result = mysqli_query($web_db, $sql);
      while( $row = mysqli_fetch_assoc( $result)) {
        extract( $row);
      }
      ob_start();
      include( $override_header_file);
      $override_header_contents = ob_get_contents();
      ob_end_clean();
      
    }
  }// end of the override_header function
  

  public function get_first_item_id ($item_str) {
    $item_ids = explode(",", $item_str);
    $idx = 0 ;

    while (! empty($item_ids[$idx])) {
      if ($item_ids[$idx] > 0) {
        return($item_ids[$idx]);
      }
      $idx++;
    }
  }

  public function define_product_array($sno) {
  ?>
    product_list[<?=$sno?>] = new Array();
    product_list[<?=$sno?>]['start_idx'] = 0;
    product_list[<?=$sno?>]['end_idx'] = 0;
    product_list[<?=$sno?>]['attr_increment'] = 0;
  <?
  }

  public function has_scene7(){
    global $web_db;

    $sql="SELECT change_seg_number
                 , style_number 
          from " . MS_WEBONLYDB_NAME.".gsi_scene7_data 
          where ( style_number ='$this->v_style_number' or style_number ='$this->v_old_style_number')  
          order by image_type,change_seg_number desc";

    $results=mysqli_query($web_db,$sql);
    mysqli_error($web_db);
    $row=mysqli_fetch_row($results);

    if($row) {

     //return $row[0];
     $_SESSION['change_seg_number']= $row[0];
     return true;
    } else {
     return false;
    }
  }

  public function get_render() {
    global $web_db;

    $sql="SELECT full_name 
          from " . MS_WEBONLYDB_NAME.".gsi_scene7_data 
          where ( style_number ='$this->v_style_number' or style_number ='$this->v_old_style_number')
          and image_type='rn' ";

    $results=mysqli_query($web_db,$sql);
    mysqli_error($web_db);

    while($row=mysqli_fetch_row($results)) {
      $return = $row[0] ;
      return $return;
    }
  }

  public function get_scene7_spin_viewer() {
    global $web_db;

    $v_sql = "SELECT full_name, image_type 
              from " . MS_WEBONLYDB_NAME.".gsi_scene7_data 
              where ( style_number ='$this->v_style_number' or style_number ='$this->v_old_style_number')
              and (image_type='sp' or image_type='3d')";
    $v_result = mysqli_query($web_db, $v_sql);
    mysqli_error($web_db);
	$v_return = "";
    while($row = mysqli_fetch_row($v_result)) {
      $v_return = $row[0];
      if ($row[1]== "3d"){
      	$v_return = $row[0]; 
      }
    }

    if(!empty($v_return)){
        $v_return = SCENE7_URL_SPIN_VIEWER . $v_return;
  }

    return $v_return;
  }

  public function get_video() {
    global $web_db;

    $v_sql = "SELECT full_name 
              from " . MS_WEBONLYDB_NAME . ".gsi_scene7_data 
              where ( style_number ='$this->v_style_number' or style_number ='$this->v_old_style_number')
              and image_type='vd'";

    $v_result = mysqli_query($web_db, $v_sql);
    mysqli_error($web_db);

    while($row = mysqli_fetch_row($v_result)) {
      $v_return = $row[0];
      return $v_return;
    }
  }

  public function get_scene7_zoom_viewer(){
      global $web_db;
      $v_style_numbers = "'" . $this->v_style_number . "'";
      if (!empty($this->v_old_style_number)){
          $v_style_numbers = $v_style_numbers . ",'" . $this->v_old_style_number . "'";
      }
      
      $sql="SELECT full_name
          from " . MS_WEBONLYDB_NAME.".gsi_scene7_data
          where style_number in (" . $v_style_numbers . ")" .
                " and image_type='rn'";
      
      $results=mysqli_query($web_db, $sql);
      mysqli_error();
      $image_name = null;
      while($row=mysqli_fetch_row($results)){
          $image_name = SCENE7_URL_ZOOM_VIEWER . $row[0];
      }
      
      return $image_name;
  }

  public function get_avl_images(){

    global $web_db;
    $v_style_numbers = "'" . $this->v_style_number . "'";
    if (!empty($this->v_old_style_number)){
       $v_style_numbers = $v_style_numbers . ",'" . $this->v_old_style_number . "'";
    }

    $sql="SELECT full_name
                , change_seg_number
                , change_seg_value
          from " . MS_WEBONLYDB_NAME.".gsi_scene7_data
          where style_number in (" . $v_style_numbers . ")" .
          " and image_type='im' order by pos_num";

    $results=mysqli_query($web_db,$sql);
    mysqli_error($web_db);
  	
    // KBOX# 37667
    $index=0; //index used to relate back to drop down 
    $js_image_name_array="";
    $js_image_name_str="";
	while($row=mysqli_fetch_row($results)){    	
    	if ($js_image_name_array == "" and $js_image_name_str == "" ) {
    		$js_image_name_str=" \"$row[0]\",";
    	}
      	$index++;
      	$row[2]=str_replace("~","/",$row[2]); //deal with mask
      	if($this->is_instock($row[1], $row[2])){
        	//set JS Array of all available images
        	$js_image_name_array = $js_image_name_array. " \"$row[0]\",";
		} 	//end is in stock
	}
	if ($js_image_name_array == "") {
		$js_image_name_array = $js_image_name_str;
	}
    $js_image_name_str=substr($js_image_name_array,0, -1);
    return $js_image_name_str;
  }

  public function is_instock($change_segment_num, $chang_seg_value){ //check to see if a sku is in stock
    global $web_db;
    //need to initiate valid sku list
    $valid_sku_array=get_sku_str($this->v_style_number);
    $valid_sku_array=str_replace('"',"",$valid_sku_array);
    $valid_sku_array=explode(",",$valid_sku_array);  //getting individual SKUs

    foreach ($valid_sku_array as $key => $value){
       $segment_array=explode("-",$value);  //split sku into segments
        $index=$change_segment_num-1;
        $segment=$segment_array[$index];
        $segment=str_replace("&","",$segment); //deal with ampersand   
         if(($segment==$chang_seg_value)){   //check segment values
            return true;
         }
    }


  } //end function is_instock

  /*  Change# R12-001
   *  getting the image file according to new or old style number.
   *  if old style have image then we show old style image otherwise if new style
   *  has image then we show that image if both dosenot have image then we show comming soon image...
   */
  public function get_valid_image(){
    $v_image_file_path = IMAGE_PATH . strtolower($this->v_old_style_number) . ".jpg";
    $v_new_image_file_path = IMAGE_PATH . strtolower($this->v_style_number) . ".jpg";
    if (is_file($v_new_image_file_path)) {
      $v_static_image = "images/" . strtolower($this->v_style_number) . ".jpg" ;
    }elseif(is_file($v_image_file_path)) {    	
    	$v_static_image = "images/" . strtolower($this->v_old_style_number) . ".jpg" ;
    } else {
      $v_static_image = "images/comingsoon.jpg" ;
    }
    return $v_static_image;
  }

  public function get_main_image($p_product_image, &$p_main_image) { //get the main image file name from table.
    global $web_db;

    if(!empty($p_product_image)) {
      if(strpos($p_product_image, '/') === 0) {
        $v_static_image = substr($p_product_image, 1);
      } else {
        $v_static_image = $p_product_image;
      }     
    } else {
    	
    	$v_static_image=$this->get_valid_image(); //Change# R12_001    	
    }

    $v_sql="SELECT full_name, change_seg_number, change_seg_value
            FROM " . MS_WEBONLYDB_NAME.".gsi_scene7_data
            WHERE (style_number = '$this->v_style_number' or style_number = '$this->v_old_style_number') 
              and image_type='im'
            ORDER BY pos_num";

    $v_result = mysqli_query($web_db, $v_sql);

    mysqli_error($web_db);

    if (mysqli_num_rows($v_result) > 0) {

      while($va_row = mysqli_fetch_row($v_result)){
        $va_row[2] = str_replace("~", "/", $va_row[2]); //deal with mask

        if($this->is_instock($va_row[1], $va_row[2])) {
          $p_main_image = SCENE7_URL . $va_row[0] . "?hei=405&wid=375&op_sharpen=1";
          return true;
        }
      }
    }

    //if we've exited the loop, no Scene7 images found (either no skus in stock had an image, or 
    // there were no images to begin with
    if (file_exists($v_static_image)) {
      $p_main_image = '/' . $v_static_image;
      return true;
    } else {
      return false;
    }

  } //end get_mail Image Function

  public function get_thumbs(&$p_thumb_list) {
    global $web_db;

    $v_sql = "SELECT full_name, change_seg_number, change_seg_value 
              FROM " . MS_WEBONLYDB_NAME.".gsi_scene7_data 
              WHERE ( style_number ='$this->v_style_number' or style_number ='$this->v_old_style_number')
                and image_type='sw' 
              ORDER BY pos_num";

    $v_result = mysqli_query($web_db, $v_sql);
    mysqli_error($web_db);

    while($va_row = mysqli_fetch_row($v_result)) { //get thumbnails for the style

      $session_index = "segment" . $va_row[1] ."_values";
      $va_drop_values = explode(",", str_replace('"', "", $_SESSION[$session_index]));
      foreach ($va_drop_values as $v_key => $v_drop_value) { //need to cycle through all colors

        $va_row[2] = str_replace("~", "/", $va_row[2]); //deal with mask

        if($va_row[2] == $v_drop_value) { //has thumb
          if($this->is_instock($va_row[1], $va_row[2])) { //is instock
            $v_change_seg_number = $va_row[1];
            $v_drop_name = $this->get_drop_name($v_change_seg_number);
            $v_sm_img = $va_row[0];
            $v_target = str_replace("_sw_", "_im_", $v_sm_img);

            $v_sm_img_passed = str_replace("~", "/", $v_sm_img); //deal with mask

            $p_thumb_list[] = array('change_seg_number' => $v_change_seg_number,
                                    'drop_name'         => $v_drop_name,
                                    'small_image'       => $v_sm_img,
                                    'target'            => $v_target,
                                    'small_image_passed'=> $v_sm_img_passed);                                    
          } //end is in stock
        } //has pic

      } //for each

    } //end while

  } //end get thumbs function


  public function get_drop_name($p_change_seg_number){
    global $web_db;
    $v_segment="segment$p_change_seg_number";

    $v_sql = "select $v_segment from gsi_cmn_style_data where style_number = '$this->v_style_number'";

    $v_result = mysqli_query($web_db, $v_sql);

    mysqli_error($web_db);

    while($va_row = mysqli_fetch_row($v_result)) {
        $v_return = $va_row[0] . "1";
        return $v_return;
    }

  }

  public function get_breadcrumbs($p_item_category_id, $p_product_title) {

    if( $_SESSION['site'] == 'US'){
      if(!empty($_SESSION['s_last_search']['referer']) && substr($_SESSION['s_last_search'][referer],-9) != 'index.php'){

        $GLOBALS['endeca_referer'] = $_SESSION['s_last_search']['referer'];

        if (! $s_screen_name)
          $s_screen_name = $_SESSION['s_screen_name'];

        if (! $s_screen_name)
          $s_screen_name = $_SESSION['s_screen_name'] = 'ps';

        $i_endeca = new Endeca();

        $response = $i_endeca->bridge($_SESSION['s_last_search']);

        $v_breadcrumb_text = $i_endeca->getValue($response, 'bread_crumb');
        $v_breadcrumb_text = $i_endeca->removeDelimiter('bread_crumb', $v_breadcrumb_text);

        $v_bread_crumb = '<li><a href="/' . $s_screen_name . '/">' . str_replace(' ', '&nbsp;', ucwords( $GLOBALS['s_screen_title']))
                 . '</a></li>'
                 . $v_breadcrumb_text;

        //now format for new way
        $v_bread_crumb = preg_replace('/ &gt; /', '<li>&nbsp;&raquo; </li><li>', $v_bread_crumb, 1);
        $v_bread_crumb = str_replace(' &gt; ', '</li><li> &raquo; </li><li>', $v_bread_crumb);
        $v_bread_crumb = str_replace(' class="searchnav_sm"', '', $v_bread_crumb);
        $v_bread_crumb .= '</li>';

      } else {
        //get this piece working
        $v_bread_crumb = $this->show_breadcrumb_trail( $p_item_category_id);
      }

      if(!empty($p_product_title)) {
        $v_bread_crumb .= '<li> &raquo; </li>';
        $v_bread_crumb .= '<li>' . str_replace(array ("Assorted","ASSORTED","assorted"),array ("","",""), $p_product_title) . '</li>';
      }
      $v_bread_crumb = '<ul class="breadCrumbs">' . $v_bread_crumb . '</ul>';
      
      unset($_SESSION['s_last_search']['referer']);
      return $v_bread_crumb;
    }
  }


  public function breadcrumb_list( $p_category_id ) {
	
  	global $s_screen_title;
  	
  	$screen_name = $_SESSION['s_screen_name'];
  	
  	$this->get_category_info($p_category_id, $v_cat_desc, $v_parent_cat);

  	$breadcrumb_list[] = "/$screen_name/search?N=$p_category_id" . '|' . $v_cat_desc;                                      
                                          
  	while (isset($v_parent_cat)) {
  		
  		$this->get_category_info($v_parent_cat, $v_cat_desc, $v_p_cat);
  	    
		$breadcrumb_list[] = "/$screen_name/search?N=$v_parent_cat" . '|' . $v_cat_desc;                                      
                                      
		$v_parent_cat = $v_p_cat;                                      
      
    }
  	
    $breadcrumb_list[] = "/$screen_name/" . '|' . ucfirst( $s_screen_title);

    krsort($breadcrumb_list);
    
    return $breadcrumb_list;

  }

  public function show_breadcrumb_trail( $p_category) {

  	$breadcrumb_list = $this->breadcrumb_list( $p_category);

    $v_arr_cnt = count($breadcrumb_list);

    $v_cnt = 0;
    
    $trail = '';
    
    foreach ($breadcrumb_list as $key => $value) {
  
      $v_cnt++;
      $v_info = explode("|", $value);

      $url = $v_info[0];
      $description = $v_info[1];
      $trail .= (!empty($trail) ? '<li>&nbsp;&raquo; </li>' : '');

      if ($v_cnt == $v_arr_cnt - 1)
      {
        $v_prev_cat_url = $url;
      }

      if ($v_cnt == $v_arr_cnt)
      {
        $trail .= $description . ' '  . '<li><a href="'.$v_prev_cat_url.'">'.' (remove)'. '</a></li>';
      } else {
        $trail .= '<li><a href="'.$url.'">'.$description. '</a></li>';
      }
    }
    return $trail;
  }


  function get_category_info($p_cat_id, &$p_cat_desc, &$p_parent_cat) {
  	
  	global $web_db;
    $v_style_list = '';

          
	$v_sql = "select description, parent_category_id
			  from gsi_categories
			  where category_set_name = 'GSI WEB CATALOG'
				and category_id  = $p_cat_id";
	
    $v_result = mysqli_query($web_db, $v_sql);
    display_mysqli_error($v_sql);

    while($va_row = mysqli_fetch_assoc($v_result)) {
    	
    	$p_parent_cat = $va_row['parent_category_id'];
    	$p_cat_desc   = $va_row['description'];
     
    }
    mysqli_free_result($v_result);
    return $v_style_list;
    
  }
  
  
  //get custom styles for clubs
public function get_associated_custom_style() {
    global $web_db;
    $v_style_list = '';

    //remove the limit 1 clause when we've added handling for multiple
    //custom styles mapped to the same original style
    //exclude Lynx (Brand_id of 109120) for now       
          
                $v_sql = "select service_style_number ,inventory_item_id, ss.service_type,ss.style_number
                                                  from   gsi_style_services ss
                                                                ,gsi_style_info_all sia
                                                                ,gsi_item_info_all iia
                                                  where ss.style_number='$this->v_style_number'
                                                  and ss.service_style_number=sia.style_number
                                                  and ss.style_type='CUSTOM'
                                                  and ss.service_type='CUSTOM'
                                                  and iia.SEGMENT1=sia.STYLE_NUMBER
                                                  and ss.template_id is null
                                                  and sia.brand_id!=109120";

    $v_result = mysqli_query($web_db, $v_sql);
    display_mysqli_error($v_sql);

    while($va_row = mysqli_fetch_assoc($v_result)) {
      if(!empty($v_style_list)) {
        $v_style_list .= ',';
      }
      $activeCustomStyle = $va_row['service_style_number'];
      $v_style_list .= $va_row['service_style_number']."||".$va_row['inventory_item_id'];
      if ($va_row['service_style_number'] == $va_row['style_number'] && $va_row['service_type']=="CUSTOM"){
          $v_style_list = $v_style_list."||"."TRUE";
      }else{
          $v_style_list = $v_style_list."||"."FALSE";
      }
    }
    mysqli_free_result($v_result);
    
    //now check if custom club is still available based on the date - per Lois requirement
    $v_active = $this->is_custom_club_active($activeCustomStyle);
    
    if ($v_active == 'N')
    {
                $v_style_list = ''; 
    }

    return $v_style_list;
  }
  
  
  
function is_custom_club_active($p_custom_style) {

  global $mssql_db;
  
  get_mssql_connection();  

  $v_active = 'N';

  $v_stmt = mssql_init("gsi_is_active_custom_club");

  gsi_mssql_bind($v_stmt, "@p_style_number", $p_custom_style, 'varchar', 30);
  gsi_mssql_bind($v_stmt, "@p_active", $v_active, 'varchar', 1, true);
  gsi_mssql_bind($v_stmt, "@p_return_status", $v_return_status, 'varchar', 200, true);

  $v_result = mssql_execute($v_stmt);

  if(!$v_result) {
    display_mssql_error("gsi_is_active_custom_club", "called from is_custom_club_active() in ProductPage");
  }

  if(!empty($v_return_status)) {
    $v_active = 'N';
  }

  mssql_free_statement($v_stmt);

  return $v_active;
}

  public function define_sku_options($p_disp_help, $p_logo_item, $p_logo_apparel, $p_segment_count, &$p_sku_options) {

    global $items;

    $p_sku_options = array();

    for($v_idx=1 ; $v_idx <= count($items); $v_idx++){

      if ($items[$idx]['group'] == 'Y') {
        $v_disp_help = 'N';
      } else {
        $v_disp_help = $p_disp_help;
        $disp_number='Y';
      }

      if($p_logo_item == 'N') { //regular item 
        $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
        $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
        $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);
        $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
        $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
        $this->define_sku_option($items[$v_idx]['segment7'], $items[$v_idx]['segment7_values'], 7, $v_disp_help, $v_idx, $p_sku_options[]);
        $this->define_sku_option($items[$v_idx]['segment8'], $items[$v_idx]['segment8_values'], 8, $v_disp_help, $v_idx, $p_sku_options[]);
        $this->define_sku_option($items[$v_idx]['segment9'], $items[$v_idx]['segment9_values'], 9, $v_disp_help, $v_idx, $p_sku_options[]);
        $this->define_sku_option($items[$v_idx]['segment10'],$items[$v_idx]['segment10_values'],10, $v_disp_help, $v_idx, $p_sku_options[]);

      } else { //logo item

      	if ($p_logo_apparel == 'Y')
      	{
      		if($p_segment_count == 1) { 
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	        }        
	        if($p_segment_count == 2) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);	          
	        }
	        if($p_segment_count == 3) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);	          
	        }
	        if($p_segment_count == 4) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);	          
        	}
      	    if($p_segment_count == 5) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);	          
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	    if($p_segment_count == 6) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);	          
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	    if($p_segment_count == 7) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);	          
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment7'], $items[$v_idx]['segment7_values'], 7, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	    if($p_segment_count == 8) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);	          
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment7'], $items[$v_idx]['segment7_values'], 7, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment8'], $items[$v_idx]['segment8_values'], 8, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	    if($p_segment_count == 9) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);	          
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment7'], $items[$v_idx]['segment7_values'], 7, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment8'], $items[$v_idx]['segment8_values'], 8, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment9'], $items[$v_idx]['segment9_values'], 9, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	    if($p_segment_count == 10) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);	          
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment7'], $items[$v_idx]['segment7_values'], 7, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment8'], $items[$v_idx]['segment8_values'], 8, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment9'], $items[$v_idx]['segment9_values'], 9, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment10'], $items[$v_idx]['segment10_values'], 10, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	}else {
      	
	        if($p_segment_count == 1) { 
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	        }        
	        if($p_segment_count == 2) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	        }
	        if($p_segment_count == 3) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);
	        }
	        if($p_segment_count == 4) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	    if($p_segment_count == 5) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	    if($p_segment_count == 6) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment7'], $items[$v_idx]['segment6_values'], 7, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	    if($p_segment_count == 7) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment7'], $items[$v_idx]['segment7_values'], 7, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment8'], $items[$v_idx]['segment8_values'], 8, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	    if($p_segment_count == 8) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment7'], $items[$v_idx]['segment7_values'], 7, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment8'], $items[$v_idx]['segment8_values'], 8, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment9'], $items[$v_idx]['segment9_values'], 9, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	    if($p_segment_count == 9) {
	          $this->define_sku_option($items[$v_idx]['segment2'], $items[$v_idx]['segment2_values'], 2, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment3'], $items[$v_idx]['segment3_values'], 3, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment4'], $items[$v_idx]['segment4_values'], 4, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment5'], $items[$v_idx]['segment5_values'], 5, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment6'], $items[$v_idx]['segment6_values'], 6, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment7'], $items[$v_idx]['segment7_values'], 7, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment8'], $items[$v_idx]['segment8_values'], 8, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment9'], $items[$v_idx]['segment9_values'], 9, $v_disp_help, $v_idx, $p_sku_options[]);
	          $this->define_sku_option($items[$v_idx]['segment10'], $items[$v_idx]['segment10_values'], 10, $v_disp_help, $v_idx, $p_sku_options[]);
        	}
      	}
      }
    }

  }

  public function define_sku_option($p_seg_name, $p_seg_values, $p_seg_number, $p_disp_help, $p_sno, &$p_options) {

    global $web_db;

    $v_next_seg = $p_seg_number - 2 ;

    $v_select_name = str_replace(" ", "_", $p_seg_name);
    $v_select_name = $v_select_name . $p_sno;
    if(empty($p_disp_help) || !isset($p_disp_help)) {
      $p_disp_help = 'N';
    }

    if (!empty($p_seg_name)) {
      $p_seg_name = ucfirst(strtolower($p_seg_name));

      $v_change_seg_number= $_SESSION['change_seg_number'];

      if ($v_change_seg_number == $p_seg_number){
        $v_use_scene7 = TRUE;
      } else {
        $v_use_scene7 = FALSE;
      }
      $v_size_chart_name = get_size_chart($this->v_style_number);
      //if we're not on the right segment, don't show the size chart
      if($v_size_chart_name == '' || (strtoupper($p_seg_name) != 'SIZE' && strtoupper($p_seg_name) != 'WIDTH')) {
        $v_tennis_head = '';
        if($p_seg_name == 'Tennis head size')
          $v_tennis_head = "Y";
     
         if($p_disp_help == 'Y') {
           $v_help_text = 'Size Chart';
           if($v_tennis_head == 'Y') {
             $v_help_link = '/display_page.php?page_num=racquet_glossary&hdr=N#HeadSize';
           } else {
             $v_help_link = '/display_page.php?page_num=racquet_glossary&hdr=N#GripSize';
           }
         } else {
           $v_help_link = '';
           $v_help_text = '';
         }

      } else {
        $v_help_link = '/static_pages/htmlarch/size_charts/' . $v_size_chart_name;
        $v_help_text = 'Size Chart';
      }

      //HTML now gets printed elsewhere
      $v_visible = "Y";
      if (($p_seg_values == '"N/A"') || ($p_seg_values == '"NA"'))
      {
      	$v_visible = "N";
      }
      $p_options = array('select_name' => $v_select_name, 'sno' => $p_sno, 'next_seg' => $v_next_seg, 'seg_name' => $p_seg_name, 'change_seg_number' => $v_change_seg_number, 'use_scene7' => $v_use_scene7, 'help_link' => $v_help_link, 'help_text' => $v_help_text, 'visible' => $v_visible);

    } else {
      return false;
    }
  }
  
  /*R12-001*/
  /*Changed the table name prod_data_summary to style_info_all
   * also set the hardcode value of url for puring checkbox
   * */

  public function generatePersonalizationCheckboxes(&$pa_checkbox_data) {
    global $web_db;

    /* Change: R12-001
     * Made By: Hafeez
     * Before we were getting service_type from gsi_cmn_style_services table but now 
     * in R12 change we don't have puring service in gsi_cmn_style_services 
     * so now we have flag in gsi_item_info_all which is a pure_flag value="Y"*/
    
    $v_sql = "select distinct 'PURE' as service_type
			  from gsi_item_info_all iia,gsi_style_info_all sia
			  where iia.segment1=sia.style_number
				and sia.style_number = '$this->v_style_number'
				and pure_flag='Y'";    
    $v_result = mysqli_query($web_db, $v_sql);
    display_mysqli_error($v_sql) ;

    $pa_checkbox_data = array();

    $v_count = 0;

    while ($va_row = mysqli_fetch_array($v_result)) {

      $pa_checkbox_data[$v_count] = array();

      $pa_checkbox_data[$v_count]['service_type'] = $va_row['service_type'];

      $v_sql2 = "select replace(description, ' ', '&nbsp;') description, '/cm/display_page.php?page_num=pureing' as url
                 from gsi_style_info_all
                 where style_number='" . $pa_checkbox_data[$v_count]['service_type'] . "'";                 

      $v_result2 = mysqli_query($web_db, $v_sql2);
      display_mysqli_error($v_sql2);

      if ($va_row2 = mysqli_fetch_array($v_result2)){
        $pa_checkbox_data[$v_count]['service_desc'] = $va_row2["description"] ;
        $pa_checkbox_data[$v_count]['service_url'] = ( ! strPos( $va_row2['url'], '/') ? '' : '/') . $va_row2["url"] ;
        
      }
      mysqli_free_result($v_result2);
      $v_count++;
    }
    mysqli_free_result($v_result);    
  }

  public function isPersonalizedstyle() {
      global $web_db;

      $v_sql = "SELECT distinct 'Y' as isPersonalized
      FROM gsi_style_info_all sia
      WHERE sia.style_number = '$this->v_style_number'
      AND customization_type in('DEMO','SPECIAL ORDER','PERSONALIZED','CUSTOM','LOGO','FREE ITEM')";
      
     
      $v_result = mysqli_query($web_db, $v_sql);
      display_mysqli_error($v_sql) ;

      $va_row = mysqli_fetch_array($v_result);
      
      mysqli_free_result($v_result);
      
      return $va_row['isPersonalized'];
  }
  
  

  public function generatePersonalizationData(&$p_service_type, &$pa_personalization_data, &$p_per_line_count) {
    global $web_db;

    $pa_personalization_data = array();

    $p_service_type = '';
    $template_id = '';
    $sql = "select style_number, template_id, service_type
            from gsi_style_services
            where service_style_number = '$this->v_style_number'
              and service_type in ('PERSONALIZE_TEXT')
              and template_id is not null";
    $result = mysqli_query($web_db, $sql);
    display_mysqli_error($sql);
    if ($myrow = mysqli_fetch_array($result)) {
      $p_service_type = $myrow["service_type"];
      $org_style_number = $myrow["style_number"];
      $template_id = $myrow["template_id"];
    }
    mysqli_free_result($result);

    if (!empty($template_id)) {

      $sql2 = "select distinct l.user_action,l.display_label,a.attribute_name
               from gsi_per_attribute_lookup l
                  , gsi_per_template_attributes a
               where l.attribute_name = a.attribute_name
                 and a.template_id = $template_id
               order by ifnull(a.display_sequence,1),l.user_action,a.attribute_name" ;
      $result2 = mysqli_query($web_db, $sql2);
      display_mysqli_error($sql2);
      $line_cnt = 0;
      $type_cnt = 0;
      $choose_cnt = 0;
      while($myrow2 = mysqli_fetch_array($result2)){
        $user_action    = $myrow2["user_action"];
        $display_label  = $myrow2["display_label"];
        $attribute_name = $myrow2["attribute_name"];
        switch ($user_action) {
          case 'CHOOSE':
            $this->display_choose($template_id, $display_label, $attribute_name, $pa_personalization_data[]);
            $choose_cnt++;
            break;
          case 'TYPE':
            $this->display_text($template_id, $display_label, $attribute_name, $pa_personalization_data[]);
            $type_cnt++;
            if (substr($attribute_name,0,4) == 'LINE')
              $line_cnt++;
            break;
        } // end of switch
      } // end of while
      mysqli_free_result($result2);

      if (($choose_cnt > 0) || ($type_cnt > 0) ) { ?>
        <?
      } // if there are attributes displayed
      if ($line_cnt > 0) {
      ?>
        <!--<input type="hidden" name="per_line_cnt" value="<? echo $line_cnt ?>">-->
      <?
      } // if line_cnt > 0
    } // if not empty template id

    $p_per_line_count = $line_cnt;

  }

  
    public function generateMyJoysTeams(&$myjoys_team) {
    global $web_db;

    $myjoys_team = array();
    $myjoys_team['options'] = array();

    $sql="SELECT license_type
                , team_id
                , team_name 
          from " . MS_WEBONLYDB_NAME.".gsi_myjoys_licensed_teams           
          order by team_name";
    $result = mysqli_query($web_db, $sql);
    display_mysqli_error($sql);
   $idx = 0;
    while($myrow = mysqli_fetch_array($result)){ 
      //$idx = $myrow["team_id"];     
      $myjoys_team['options'][$idx]["license_type"] = $myrow["license_type"];
      $myjoys_team['options'][$idx]["team_id"] = $myrow["team_id"];
      $myjoys_team['options'][$idx]["team_name"] = $myrow["team_name"];
      $idx++;
    }
    mysqli_free_result($result);
  }
  
  
  public function display_text ($p_template_id, $p_display_label, $p_attribute_name, &$pa_personalization_data) {
    global $web_db;

    $pa_personalization_data = array();

    $tmp_sql = "select attribute_value 
                from gsi_per_template_attributes 
                where template_id = $p_template_id 
                  and attribute_name = '$p_attribute_name'" ;
    $tmp_result = mysqli_query($web_db, $tmp_sql);

    display_mysqli_error($tmp_sql);
    $tmp_myrow = mysqli_fetch_array($tmp_result) ;
    $max_length = $tmp_myrow["attribute_value"];
    mysqli_free_result($tmp_result);

    $pa_personalization_data['type'] = 'text';
    $pa_personalization_data['max_length'] = $max_length;
    $pa_personalization_data['field_name'] = "per_" . strtolower(str_replace(' ', '_', $p_attribute_name));
    $pa_personalization_data['label'] = $p_display_label;
  } // end of function display_text ;

  public function display_choose($p_template_id, $p_display_label, $p_attribute_name, &$pa_personalization_data) {
    global $web_db;

    $pa_personalization_data = array();
    $pa_personalization_data['type'] = 'select';
    $pa_personalization_data['options'] = array();

    $tmp_sql = "select a.attribute_value, v.display_label
                from gsi_per_template_attributes  a
                   , gsi_per_attribute_value_lookup v
                where a.template_id = $p_template_id
                  and a.attribute_name = '$p_attribute_name'
                  and v.attribute_name = a.attribute_name
                  and v.attribute_value = a.attribute_value
                order by a.val_display_sequence, a.attribute_value" ;
    $tmp_result = mysqli_query($web_db,$tmp_sql);
    display_mysqli_error($tmp_sql);
    $idx = 0;
    while($tmp_myrow = mysqli_fetch_array($tmp_result)){
      if($idx == 0) {
        $v_selected = TRUE;
      } else {
        $v_selected = FALSE;
      }
      $pa_personalization_data['options'][$idx] = array('label' => ucfirst(strtolower($tmp_myrow["display_label"])), 'value' => $tmp_myrow["attribute_value"], 'selected' => $v_selected);
      $idx++;
    }
    mysqli_free_result($tmp_result);

    $pa_personalization_data['field_name'] = "per_" . strtolower(str_replace(' ', '_', $p_attribute_name));
    $pa_personalization_data['label'] = $p_display_label;

  } // end of function display_choose ;
  
  /* Change# R12-001 */
  public function find_isp_yesno() {
    global $web_db;
    $v_msg = 'N' ;
   
    $sql = "select count(*) as n_cnt
			from gsi_item_info_all
			where segment1='$this->v_style_number'
			and (store_pickup_flag = 'N'
			or austin_to_store_flag = 'N')";    
    
    $result = mysqli_query($web_db, $sql) ;
    $error = display_mysqli_error($sql);
    if (empty($error)){
      $row = mysqli_fetch_array($result);
      if ($row['n_cnt'] == 0){
        $v_msg = 'Y' ;
      }
    }
    return($v_msg);
  } // end of function find_isp_yesno


  public function findAvailablePersonalization() {

    global $web_db;
    $v_personalize_style = '';

    $v_sql = "SELECT a.service_style_number 
               FROM gsi_style_services a, gsi_cmn_style_data b 
               WHERE b.style_number = a.service_style_number 
                 AND a.style_number <> a.service_style_number 
                 AND a.service_type IN ('PERSONALIZE_TEXT') 
                 AND a.style_number = '$this->v_style_number'";

    $v_result = mysqli_query($web_db, $v_sql);
    display_mysqli_error($v_sql);

    while ($va_row = mysqli_fetch_assoc($v_result)) {
      $v_personalize_style = $va_row['service_style_number'];
    }

    return $v_personalize_style;

  }

  public function getSpecialOffers(&$va_special_offers) {
    global $web_db;

    $va_special_offers = array();

    $v_sql = "select distinct gso.* 
              from webonly.gsi_special_offer gso 
                 , webonly.gsi_specialoffer_style gss
              where gss.style_number = '$this->v_style_number' 
                and gss.promo_id = gso.promo_id
                and now() between start_date and end_date 
              order by end_date desc";

    $v_result = mysqli_query($web_db, $v_sql);
    display_mysqli_error($v_sql);

    while($va_row = mysqli_fetch_assoc($v_result)) {
      $va_special_offers[] = $va_row;
    }

  }

  public function getPricingArray(&$pa_pricing_array, &$p_low_quantity) {
    global $web_db;

    $pa_pricing_array = array();

    if (!empty($this->v_style_number)) {
      $price_sql = " select low, high, price
                     from gsi_cmn_style_price_breaks
                     where style_number = '$this->v_style_number'
                     order by low ";

      $price_result = mysqli_query($web_db, $price_sql);
      display_mysqli_error($price_sql) ;
      $price_idx = 0;
      while ($price_myrow = mysqli_fetch_array($price_result)) {
        if(empty($p_low_quantity)) {
          $p_low_quantity = $price_myrow["low"];
        }
        $pa_pricing_array[$price_idx]["low"] = $price_myrow["low"];
        if($price_myrow["high"] <= 1000) {
          $pa_pricing_array[$price_idx]["high"] = $price_myrow["high"];
        }
        $pa_pricing_array[$price_idx]["price"] = $price_myrow["price"];
        $price_idx++;
      }
      mysqli_free_result($price_result);

      if (count($pa_pricing_array) == 0) {
        $price_str_sql = " select price_str
                           from " .GSI_CMN_STYLE_DATA. "
                           where style_number = '$this->v_style_number' ";
        $price_str_result = mysqli_query($web_db, $price_str_sql);
        display_mysqli_error($price_str_sql);
        $price_str_myrow = mysqli_fetch_array($price_str_result);
        $v_price_str = $price_str_myrow["price_str"];
        mysqli_free_result($price_str_result);

        if (!empty($v_price_str)) {
          $price_str_array = split(',',$v_price_str);
          $price_str_array_cnt = count($price_str_array);

          $idx2 = 0;
          for ($idx=0; $idx < $price_str_array_cnt; $idx++) {
            if ($price_str_array[$idx] != '"0.0"') {
              $new_price_str_array[$idx2] = trim($price_str_array[$idx],'"');
              $new_price_str_array[$idx2] = trim($new_price_str_array[$idx2],'$');
              $idx2++;
            }
          }

          $v_low_price = min($new_price_str_array);
          $v_high_price = max($new_price_str_array);

          $p_low_quantity = 1;

          if ($v_low_price == $v_high_price) {
            $pa_pricing_array[0]["low"] = "1+";
            $pa_pricing_array[0]["price"] = $v_low_price;
          } else {
            $pa_pricing_array[0]["low"] = "1+";
            $pa_pricing_array[0]["price"] = $v_low_price . " - " . $v_high_price;
          }
        }
      }

    }

  }
 
  
  //this will validate ordered quantity versus available quantity
  public function validateATP($p_iid, $p_org_id,$p_ordered_qty,$p_style) {  	
   // this proc will get the real atp number 
   // we will keep it and remove all the old code 
   /*
   global $mssql_db;
   $v_atp = 0;

    $v_stmt = mssql_init("direct.dbo.gsi_get_item_atp");

    gsi_mssql_bind($v_stmt, "@p_iid", $p_iid, "bigint", -1);
    gsi_mssql_bind($v_stmt, "@p_org_id", $p_org_id, "bigint", -1);
    gsi_mssql_bind($v_stmt, "@p_atp", $v_atp, "bigint", -1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("direct..gsi_get_item_atp called from ProductPage.php");
      $v_atp = -1;
    }

    mssql_free_statement($v_stmt);

    return $v_atp;
  	*/
		$SOAPURL =ATPSOAPSERVICE_URL;
		$SOAPURL = dirname(__FILE__) . '/GetATPQuantity.wsdl';
		//$SOAPURL = "http://devwlssoa1.gsicorp.com:8001/soa-infra/services/Common/GetATPQuantity/getatpqtybpelcomponent_client_ep?WSDL";
		// temp SOAP URL have to remoe the next line 
		//$SOAPURL = "http://devsoacluster.gsicorp.com:7777/soa-infra/services/default/GetATPQuantity/getatpqtybpelcomponent_client_ep?WSDL";
		//$qty = 1; // just one because we do check the product page
		$options = array(
                  "soap_version" => SOAP_1_1,
                  "exceptions"   => true,
                  "trace"        => 1,
                  "cache_wsdl"   => WSDL_CACHE_NONE,
                  "style"        => SOAP_DOCUMENT,
                  "use"          => SOAP_LITERAL
                  );
		global $mssql_db;
		$soap_arr = array ("ATPReqInput"=>array ('SALES_CHANNEL_CODE'=>'GS.COM',
  							'INVENTORY_ITEM_ID'=>$p_iid,
  							'ORGANIZATION_ID'=>$p_org_id,
  							'QUANTITY'=>$p_ordered_qty
  							));
    	try
		{
			$v_sclient = new SoapClient($SOAPURL,$options);
   			$result = $v_sclient->process( $soap_arr );
  
    		// get the SOAP results and updat the
   			$ATP_check_result = $result->ATPOutput;;
		}catch (SoapFault $e )
		{
			//If there an error we will call the Tables diectly
			$v_sp = mssql_init("direct.dbo.gsi_get_atp_qty");
			gsi_mssql_bind ($v_sp, "@inventory_item_id", $inv_item_id, "int", 20, false);
			gsi_mssql_bind ($v_sp, "@qty", $qty, "int", 20, false);
			gsi_mssql_bind($v_sp,"@skunumber",$skunumber,"varchar",50,true);        
			$v_result = mssql_execute($v_sp);
			if (!$v_result){
				display_mssql_error("gsi_get_new_SKU" . $msg, "call from ProductPage.php");
			}
			mssql_free_statement($v_sp);
			mssql_free_result($v_result);
			
			// in any case the result status will be ERROR
			$this->result =  "ERROR";
			write_to_log("ATP Error ". $e->getMessage()." the call:".var_export($e,TRUE));
			$ATP_check_result = "ERROR";
    	}

      return $ATP_check_result;
  }
  
  public function get_QBD_info() {
  	
  	$v_arr_QBD = array();

  	if ($_SESSION['site'] != 'CA') {
	  	$v_arr_QBD = get_active_QBD(); 	
	  	$v_arr_QBD2 = array();
	  	
	  	if (strtolower($v_arr_QBD['style']) == strtolower($this->v_style_number)) {
	  		
	  		$v_arr_QBD2 = $v_arr_QBD;
	  		
	  	} else {
	  		
	  		$v_arr_QBD2['active'] = 'N';
	  		
	  	}
  	} else {
  		
  		$v_arr_QBD2['active'] = 'N';
  		
  	}
  	return $v_arr_QBD2;
  	
  }

  public function getPriceBreaks(&$pa_pricing_array, &$p_low_quantity) {
    global $web_db;

    $sql = "select low
                   , high
                   , price 
            from gsi_cmn_style_price_breaks
            where style_number = '$this->v_style_number' 
            order by low " ;

    $result = mysqli_query($web_db, $sql);
    display_mysqli_error($sql) ;

    $v_idx = 0;

    while ($myrow = mysqli_fetch_array($result)) {

      if(empty($p_low_quantity)) {
        $p_low_quantity = $myrow["low"];
      }

      $min_qty = $myrow["low"] ;
      $max_qty = $myrow["high"] ;
      $price = $myrow["price"] ;

      $price_dsp = format_currency($price);

      if ($min_qty == 1) {
        $pa_pricing_array[$v_idx]['quantity'] = 'per unit';
        $pa_pricing_array[$v_idx]['price'] = $price_dsp;
      } else {
        $pa_pricing_array[$v_idx]['quantity'] = "$min_qty or more";
        $pa_pricing_array[$v_idx]['price'] = $price_dsp;
      }
      $v_idx++;
    }
    mysqli_free_result($result);
  }

  //promo messaging
  function get_promo_messages ($p_group_number, &$p_shipping_message, &$p_9090_message, &$p_wf_message) {
    global $web_db;
        
    if(!empty($this->v_style_number)) {     
      $sql2 = "select max(item_price) item_price 
               from gsi_item_info_all 
               where segment1 = '$this->v_style_number'";
      $result2 = mysqli_query($web_db, $sql2);
      $row2    = mysqli_fetch_array($result2);
      $v_item_price = $row2['item_price'];
    }        
    $p_9090_message = $this->get_message_by_column ('ninty_ninty_flag');
    if ($v_item_price > 100){ //Showing GE Card link on product page. 
      $p_wf_message = $this->get_message_by_column ('gsi_card_promotion_plan');
    }
  } // end of function

  function get_message_by_column ($p_column_name) {
    global $web_db;
    $sql = "select distinct $p_column_name as column_value
			from gsi_style_info_all sia, gsi_item_info_all iia
			where sia.style_number='$this->v_style_number'
			and sia.style_number=iia.segment1
			and iia.item_price<>0";    

    $result     = mysqli_query($web_db, $sql) ;
    $v_num_rows = mysqli_num_rows($result) ;
    $row        = mysqli_fetch_array($result) ;
    $v_msg = '' ;
    if ($v_num_rows == 1) {
      $v_column_value = $row['column_value'] ;
      $v_msg = $this->get_column_message($p_column_name, $v_column_value) ;
    }
    return($v_msg);
  } // end of function get_message_by_column

  function get_column_message ($p_column_name, $p_column_value) {
    global $web_db;
    $v_message = '';
    switch($p_column_name) {      
      case "ninty_ninty_flag":
        if ($p_column_value == 'Y') {
          $v_message = 'Performance Guarantee';
        }
        break;
      case "gsi_card_promotion_plan":
        if ($p_column_value <> 1) {
          $v_message = 'Special Financing';
        } // not regular terms
        break;
    } // end of switch
    return($v_message);
  } // end of function get_column_message

  
  public function getBrowseLinkData(&$pa_browse_data) {

    global $web_db;

    $pa_category_data = array();

    $v_show_manufacturer = FALSE;
    $v_show_category = FALSE;
    $v_show_combine = FALSE;

    if ($_SESSION['site'] != 'CA') {
      $v_sql = "select brand_id as manuf_id
                       ,web_category_id as category_id
		        from gsi_style_info_all
				where style_number='$this->v_style_number'";
    	
      $v_result = mysqli_query($web_db, $v_sql);
      display_mysqli_error($strSQL);

      while ($va_row = mysqli_fetch_assoc($v_result)) {
        $v_category_id = $va_row['category_id'];
        $v_manufacturer_id = $va_row['manuf_id'];
      }

      //check for products that fit both categories
      if ($v_category_id != '' && $v_manufacturer_id != '') {     
      	$v_sql = "SELECT 1
                  FROM gsi_style_info_all
                  WHERE brand_id =$v_manufacturer_id
                  AND web_category_id=$v_category_id";
      	
        $v_result = mysqli_query($web_db, $v_sql);
        display_mysqli_error($v_sql);

        if(mysqli_num_rows($v_result) > 1) {
          $v_show_manufacturer = TRUE;
          $v_show_category = TRUE;
          $v_show_combine = TRUE;
        }else { //still a chance we have to show one of them      
          $v_sql = "SELECT 1
 					FROM gsi_style_info_all
 					WHERE brand_id = $v_manufacturer_id";
          
          $v_result = mysqli_query($web_db, $v_sql);
          display_mysqli_error($v_sql);
          
          if(mysqli_num_rows($v_result) > 1) {
            $v_show_manufacturer = TRUE;
          }
                 
          $v_sql = "SELECT 1
					FROM gsi_style_info_all
					WHERE web_category_id =$v_category_id";
          $v_result = mysqli_query($web_db, $v_sql);
          display_mysqli_error($v_sql);

          if(mysqli_num_rows($v_result) > 1) {
            $v_show_category = TRUE;
          }

        }

        if($v_show_manufacturer === TRUE) {
          //find manufacturer name                 	
          $v_sql = "SELECT description
					FROM gsi_brands
					WHERE brand_id=$v_manufacturer_id";          
          $v_result = mysqli_query($web_db, $v_sql);
          display_mysqli_error($v_sql);

          $va_row = mysqli_fetch_assoc($v_result);
          $v_manufacturer_name = $va_row['description'];

        }

        if($v_show_category === TRUE) {        
          $v_sql = "SELECT description
					FROM gsi_categories
					WHERE category_id=$v_category_id
					AND category_set_name='GSI WEB CATALOG'";
          $v_result = mysqli_query($web_db, $v_sql);
          display_mysqli_error($v_sql);

          $va_row = mysqli_fetch_assoc($v_result);
          $v_category_name = $va_row['description'];
        }
      }

      if($v_show_combine === TRUE) {
        $pa_browse_data[] = array('label' => $v_manufacturer_name . ' ' . $v_category_name
                                , 'id_string' => strtolower(str_replace(" ","-",$v_manufacturer_name)) . '+' . strtolower(str_replace(" ","-",$v_category_name)));
      }

      if($v_show_manufacturer === TRUE) {
        $pa_browse_data[] = array('label' => $v_manufacturer_name . ' Gear'
                                , 'id_string' => strtolower(str_replace(" ","-",$v_manufacturer_name)));
      }

      if($v_show_category === TRUE) {
        $pa_browse_data[] = array('label' => $v_category_name
                                , 'id_string' => strtolower(str_replace(" ","-",$v_category_name)));
      }

    }
  }

  public function has_specifications() {
    global $web_db;

    $v_sql = "select count(sao.sku) as sku
				from gsi_style_info_all sia,
 	  			gsi_sku_attribute_options sao
				where sia.web_category_id not in(select category_id
			  from gsi_categories
    		  where category_name like '%PREOWNED%')
			  and sao.style_number='$this->v_style_number'
			  and sia.style_number=sao.style_number
			  and sao.search_criteria in('B','C')";
    
    $v_result = mysqli_query($web_db, $v_sql);    
    $v_sku = mysqli_fetch_array($v_result);
    display_mysqli_error($v_sql) ;
    
    $v_show_specs = false;      
    if($v_sku[0]>0){    	
      $v_show_specs = true;
    }

    mysqli_free_result($v_result);

    return $v_show_specs;
  }

  public function is_excluded_item(&$p_source_code, &$p_is_excluded) {

    global $mssql_db;

    //in case source code isn't in session...
    if(empty($_SESSION['s_source_code'])) {
      $p_source_code = strip_tags(post_or_get('scode'));
      if(!empty($p_source_code)) {
        $_SESSION['s_source_code'] = $v_source_code;
      }
    } else {
      $p_source_code = $_SESSION['s_source_code'];
    }

    $v_stmt = mssql_init("direct.dbo.gsi_check_style_exclusion");

    gsi_mssql_bind($v_stmt, "@p_source_code", $p_source_code, "varchar", 30);
    gsi_mssql_bind($v_stmt, "@p_style_num", $this->v_style_number, "varchar", 50);
    gsi_mssql_bind($v_stmt, "@p_is_excluded", $v_is_excluded, "bigint", -1, true);

    $v_result = mssql_execute($v_stmt);

    if(!$v_result) {
      display_mssql_error("direct..gsi_check_style_exclusion called from ProductPage.php");
    }

    mssql_free_statement($v_stmt);

    if($v_is_excluded == 1) {
      $p_is_excluded = TRUE;
    } else {
      $p_is_excluded = FALSE;
    }
  }
  
  function generateAlsoPurchased($p_style_number, $p_slot_number, &$pa_also_purchased) {
  	global $web_db;
    
    $v_screen_name = $_SESSION['s_screen_name'];
    
    /* Change# R12001 */    
    $v_sql = "select cross_style_number 
              from gsi_cmetric_upsales 
              where style_number ='$p_style_number' 
              and slot_number = $p_slot_number";

    $v_result = mysqli_query($web_db, $v_sql);
    display_mysqli_error($v_sql);
    $va_myrow = mysqli_fetch_array($v_result);
    $v_cross_style = $va_myrow["cross_style_number"];
    mysqli_free_result($v_result);
    
    if($this->v_style_number == $v_cross_style) {
      $p_slot_number = $p_slot_number + 1;
      
      /* Change# R12001 */
      $v_sql = "select cross_style_number 
                from gsi_cmetric_upsales  
                where style_number ='$p_style_number' 
                  and slot_number = $p_slot_number";

      $v_result = mysqli_query($web_db, $v_sql) ;
      display_mysqli_error($v_sql) ;
      $va_myrow = mysqli_fetch_array($v_result) ;
      $v_cross_style = $va_myrow["cross_style_number"] ;
      mysqli_free_result($v_result) ;
    }
    /************************************************************************************************/
  	if(!empty($v_cross_style)) {  	
  	  $v_sql = "select  brnd.brand_name as manuf_desc
      				, sia.description
      				, price_str
      				, price_svg_str      				
      				, item_id_str
      				, sia.original_price
      				, case when s7.full_name is not null then s7.full_name else sia.small_image_file end small_image_file
				from gsi_cmn_style_data gcs
     				, (gsi_style_info_all sia left join webonly.gsi_scene7_data s7 on sia.style_number = s7.style_number and s7.image_type = 'im' and s7.pos_num = 0)
     				, gsi_brands brnd
				where gcs.style_number='$v_cross_style'
				and sia.style_number=gcs.style_number
				and sia.brand_id=brnd.brand_id";

      $v_result = mysqli_query($web_db, $v_sql) ;
      display_mysqli_error($v_sql) ;
      if($va_myrow = mysqli_fetch_array($v_result)) {
        $v_description      = $va_myrow["description"] ;
        $v_manuf_desc       = $va_myrow["manuf_desc"] ;
        $v_small_image_file = $va_myrow["small_image_file"] ;        
        $v_price_str        = $va_myrow["price_str"] ;
        $v_price_svg_str    = $va_myrow["price_svg_str"];
        $v_item_id_str      = $va_myrow["item_id_str"] ;
        $v_original_price   = $va_myrow["original_price"] ;
        mysqli_free_result($v_result) ;
 		
        //$v_url              = '/' . $v_screen_name . '/product/'.$v_cross_style ;            
        $v_url_rep_text=array(" ",'"','.','/');

        // changed back to dash from underscore
        //$v_url = $v_url . '/' . str_replace($v_url_rep_text, '-',strtolower($v_manuf_desc)) . '-' . str_replace($v_url_rep_text, '-', strtolower($v_description)) . '?cm_vc=vc_us_pp';
		$v_seo_obj = new seo($v_cross_style);
		$v_url     = $v_seo_obj->get_seo_url();
		$v_url     = $v_url . '?cm_vc=vc_us_pp';
        
        
        $v_price = get_price($v_price_str);
        if ($price <> 'N') {              
        $v_pos = stripos($v_price,'-');
        $has_range = FALSE;
 	    if($v_pos===FALSE) {
			$v_min_price = ltrim($v_price,'$');
			$has_range = FALSE;
        } else {
       		$v_min_price = substr($v_price, 1, $v_pos-1);
      		$has_range = TRUE;
        }
        
      	if ($v_min_price >= ltrim($v_original_price,'$')) {         
        $v_original_price = '' ;
        }else{
        $v_price_svg = get_price_svg($v_price_svg_str); 

		$savings = null;
        $price_svg = get_price_svg($price_svg_str);
        if ($v_price_svg > 0) {
   		$now_price = (float) preg_replace( '/[^0-9\.]/', '', $v_price);
  		if (!empty($v_original_price)) 
  		{
  		    $v_original_price = ltrim($v_original_price,'$');
    		$dollar_savings     = floor($v_original_price - $now_price);
    		$percent_savings    = floor(($v_original_price - $now_price) * 100 / $v_original_price);
    		if ($percent_savings > $dollar_savings) {
      		if ($percent_savings > 0) {
      		    if($has_range)
        			 $savings = "Save up to " . $percent_savings . '%' ;
        	    else $savings = "Save " . $percent_savings . '%' ;        	    
        	 } 
    		} else {
      		if ($dollar_savings > 0) {
      		    if($has_range)
        			 $savings = 'Save up to $' . number_format( $dollar_savings, 2) ;
        		else $savings = 'Save $' . number_format( $dollar_savings, 2) ;
        	}
    		}
 	    	$v_price_svg = "";
        	$v_saving_story = $savings;    		
  		}
        }
          //$v_dollar_saving=floor(ltrim($v_original_price,'$')-ltrim($v_price,'$'));
          //$v_percentage_saving=floor(((ltrim($v_original_price,'$')-ltrim($v_price,'$'))*100)/ltrim($v_original_price,'$'));
        }
        } 
       
        // Add scene7 url if it's a scene7 image
        if(!empty($v_small_image_file) && !strPos($v_small_image_file, '/')){
            $v_image = SCENE7_URL . $v_small_image_file . '?$sm$';
        }else{
        //need to fix this
        $this->get_valid_style($v_cross_style,$p_new_style,$p_old_style);
        $v_image = get_image($v_small_image_file, $p_new_style,$p_old_style) ;

        //$v_url .= "&lcode=cross_sales";

        $this->get_valid_style($v_cross_style,$p_new_style,$p_old_style);
        $v_image = get_image($v_small_image_file, $p_new_style,$p_old_style) ;
        }

        //$v_url .= "&lcode=cross_sales";

        if($v_price <> 'N' && !empty($v_original_price)) {
          $v_has_savings = TRUE;
        } else {
          $v_has_savings = FALSE;
        }
        $pa_also_purchased = array('url' => $v_url, 'image' => $v_image, 'manufacturer' => $v_manuf_desc, 
                                   'description' => $v_description, 'price' => $v_price, 'has_savings' => $v_has_savings, 'original_price' => $v_original_price, 'saving_story' =>$v_saving_story);
      }
    }
  }
        
  
  public function get_seo_url(&$p_new_style_url){
  	$i_site_init = new SiteInit('product');
    $i_site_init->loadInit($v_connect_mssql);
    
    $v_style_num = $this->v_style_number;
    
    $v_seo_obj       = new seo($v_style_num);
	$p_new_style_url = $v_seo_obj->get_seo_url();
  
  }
	
/**
* Checks whether the style number provided belongs to Price Breaks or not. 
*
*@access public
*@param String style_number
*@param Integer quantity
*@param Double price
*@return price 
*@return boolean
*
* @author Niraj Byanjankar 10-12-2011
* 
*/  
  public function checkPriceBreaks($p_style_number,$p_quantity,&$p_price){
	global $web_db;
    $price_sql = "select count(price) as cnt  from gsi_cmn_style_price_breaks where 
	  				style_number='$p_style_number'";
	   
	$price_result = mysqli_query($web_db, $price_sql);
	display_mysqli_error($price_sql);

	$price_myrow = mysqli_fetch_array($price_result); 
	$num_rows =$price_myrow["cnt"];
	mysqli_free_result($price_result);
			
	if($num_rows>1){
		$price_sql = "select price from gsi_cmn_style_price_breaks where 
					'$p_quantity' between low and high
					and style_number='$p_style_number'";
		   
	   	$price_result = mysqli_query($web_db, $price_sql);
	   	display_mysqli_error($price_sql);
		      	
	  	$price_myrow = mysqli_fetch_array($price_result);
		     	
	   	$p_price=$price_myrow["price"];
		     	
     	mysqli_free_result($price_result);
     	return true; 
   	}
   	else {
   		return false;
   	}
  }
}

?>
