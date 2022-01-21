<?php
include_once('gsi_log.inc');
include_once('gsi_helper.inc');
include_once('gsi_paths.inc');
 
class SiteInit {
 
  private $v_page_type_id;

  public function __construct($p_page_type_id) {
    $this->v_page_type_id = $p_page_type_id;
  }
 
  //this code is a prelim step
  public function loadInit() {

    define('LOG_ORACLE_TIMES', FALSE);
    define('SHOW_GOOGLE_CHECKOUT', TRUE);
    define('SHOW_PAYPAL_CHECKOUT', TRUE);
    $_SESSION['s_enable_chat'] = TRUE;


    $v_http_via = $_SERVER['HTTP_VIA'];
    if (strpos($v_http_via , 'gsicorp.com')){
      $v_http_via = "http://" . GSI_PROXY_IP_ADDRESS;
    }


    //akamai implementation
    if(isset($_SERVER['XFF']))
    {
    	$remote_address = $_SERVER['XFF'];
    }else if (isset($_SERVER['HTTP_TRUE_CLIENT_IP']))
	{
		$remote_address = $_SERVER['HTTP_TRUE_CLIENT_IP'];
	
	}else {
    	$remote_address = ( ( strPos( $v_http_via, GSI_PROXY_IP_ADDRESS)&& ( $_SERVER['HTTP_X_FORWARDED_FOR']))
                      ? $_SERVER['HTTP_X_FORWARDED_FOR']
                      : ( !empty($_SERVER['HTTP_CLIENT_IP']) ?  $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'] ) );
	}

    if ( $_SERVER['HTTP_HOST'] == 'golfsmith.com') {
      header( 'Location: http://www.golfsmith.com' . $_SERVER[ 'REQUEST_URI'], true, 301);
    }

    /**
    //set profile scope based on Canada or US
    if(substr_count($_SERVER['HTTP_HOST'],"golfsmithcanada") > 0  || // golfsmithcanada.com
       substr_count($_SERVER['HTTP_HOST'],"golfsmith.ca")    > 0  ||    //golfsmith.ca
       substr_count($_SERVER['REQUEST_URI'],"/ca/")         >= 1)   // canadian web site.
    {
      define('C_WEB_PROFILE', 'WEB_CAN');
      $v_forecast = 'GSI_CAN';
    } else {
    **/
      define('C_WEB_PROFILE', 'WEB');
      $v_forecast = 'GSI_WEB';
    //}

    include('gsi_db.inc') ;

    global $v_for_cache ;
    if (empty($v_for_cache))
      $v_for_cache = 'N';

    if ($v_for_cache == 'N') {
      include('gsi_session.inc');
    }

    include('gsi_browser.inc');
    include('gsi_promos.inc');

    //need to set forecast in session
    $_SESSION['s_forecast'] = $v_forecast;

    //set remote address here
    $_SESSION['s_remote_address'] = $remote_address;
    
    //Setting Session var for Chat
    $_SESSION['s_enable_chat'] = TRUE;
    
    //Setting Session var for Invodo Videos
    //$_SESSION['s_enable_invodo'] = TRUE;

    if ( ((subStr($remote_address, 0, 8) == '192.168.') || (subStr($remote_address, 0, 7) == '10.125.')) && (empty($_SESSION['s_store_number']))) {
      include(FPATH . 'employee_credit.inc');
      $_SESSION['s_store_number'] = Employee_Credit::get_store_number();
    }

    $platform = browser_get_platform();
    if($platform == 'Mac'){
      $b_size = 10;
      $b_weight = 300;
    } else {
      $b_size = 9;
      $b_weight = 700;
    }
    $b_bgclr      = (($_SESSION['s_screen_name'])=='ps') ? 'FFD911':'555555';
    $b_clr        = (($_SESSION['s_screen_name'])=='ps') ? '222222':'FFFFFF';

    define('B_STYLE'," style=\"background-color:#".$b_bgclr.";color:#".$b_clr.";font-size:".$b_size."pt;font-weight:".$b_weight.";\"");

  }

  //get tracking data and load header/navigation
  public function loadMain() {

    //Setting Session var for GCID
    if (isset($_GET['GCID'])) {
      $_SESSION['GCID']= $_GET['GCID'] ;
    }

    $tracking_code = post_or_get('tcode');
    $tracking_code = $this->clean_request_ampersands($tracking_code);

    $lcode = post_or_get('lcode');
    $lcode = $this->clean_request_ampersands($lcode);
    
    /* Site wide Banner Session Code Start*/
    $v_xcode=$_REQUEST['xcode'];
        
    if(!empty($v_xcode)){
      $_SESSION['xcode']=$this->clean_request_ampersands($v_xcode);
    }
    
    /* Site wide Banner Session Code End*/
    
    if (!empty($_SESSION['s_partner_code']))
      $tracking_code = $_SESSION['s_partner_code'];

    //deal with cookies
    if (!empty($tracking_code)) {
      $partner_code = strtoupper(substr($tracking_code,0,2)) ;

      switch($partner_code) {
        case 'PF':
        case 'MO':
        case 'GW':
        case 'GO':
        case 'MS':
        case 'US':
        case 'ON': $v_cookie_length = 2592000; //30 days
                   break;
        case 'CJ': $v_empty_tcode_only = TRUE;
                   $v_cookie_length = 86400; //24 hours
                   break;
        default:   if(strtoupper($lcode) == 'CI') {
                     $v_cookie_length = 2592000; //30 days
                   }
                   break;

      }

      //cleanup cookie
      //Do not overwrite partner code to CJ if current partner code is less than 24 hours
       if(post_or_get("tcode") == 'gcom' )
      {
          //Is it already set
          $v_curr_tcode = post_or_get("tcode");
          setcookie("tcode", "gcom", time()+10368000, "/", ".golfsmith.com",0,1);     // expire in $v_cookie_length seconds
      }
      else if (!empty($v_cookie_length)) {
      	
      	  $v_curr_tcode = $_COOKIE['tcode'];
	      $v_tcode_date = $_COOKIE['tcdate'];
	      $v_tcode_life = strtotime("now") - $v_tcode_date;
      	
	      if (($partner_code == 'CJ') && (!empty($v_curr_tcode)) && (!empty($v_tcode_date)) && ($v_curr_tcode != 'CJ') && ($v_tcode_life < 3600))	      
	      {
	    	//do not update tcode cookie
	      } else {
	    	
	    	setcookie("tcode", "", time()-3600);                                                        // remove cookie
	        setcookie("tcode", $tracking_code, time()+$v_cookie_length, "/", ".golfsmith.com",0,1);     // expire in $v_cookie_length seconds
    	    setcookie("prtcode", "", time()-3600);                                                      // remove cookie
        	setcookie("prtcode", $partner_code, time()+$v_cookie_length, "/", ".golfsmith.com",0,1);    // expire in $v_cookie_length seconds
        	setcookie("tcdate", "", time()-3600);                                     				    // remove cookie
      		setcookie("tcdate", strtotime("now"), time()+$v_cookie_length, "/", ".golfsmith.com",0,1);  // expire in $v_cookie_length seconds
	    	
	      }	      	  

      }else if($v_empty_tcode_only !== TRUE) {
        setcookie("tcode", "", time()-3600);                                              // remove cookie
        setcookie("tcode", $tracking_code, time()+86400, "/", ".golfsmith.com",0,1);      // expire in one day
        setcookie("prtcode", "X", time()+1, "/", ".golfsmith.com",0,1);                   // remove cookie
        setcookie("tcdate", "", time()-3600);                                     		  // remove cookie
     	setcookie("tcdate", strtotime("now"), time()+86400, "/", ".golfsmith.com",0,1);   // expire in a day
      }
      
    }//not empty tracking code

    if ( !empty( $_REQUEST['scode']) ) {
      $v_source_code = post_or_get('scode');
      $v_source_code = $this->clean_request_ampersands($v_source_code);
    }

    if ( isset($v_source_code)) {
      // This will clobber s_tcode and s_source_code if this is a valid source code.
      CompoundPromotionCode::redeem($v_source_code);

      $_SESSION['s_do_reprice']  = 'Y' ;
      $_SESSION['s_do_totals']   = 'Y' ;
    }

    // if not logged in, check the cookie and login.
    $v_customer_number = $_SESSION['s_customer_number'];
    if (empty($v_customer_number)) {
      // check if there is a cookie
      $user_name = $_COOKIE['username'];
      $password  = $_COOKIE['password'];
      if (!empty($user_name)) {
        include_once(FPATH . 'fns_sc_user');
        $v_status = login($user_name, $password);
      } // no cookie
    } // not logged in

    //call page rendering function
    $this->loadHeader();

  }

  
function getStoreMeta($v_store_num)
  {
        include_once 'models/Store.php';
        $storeObj = new Store($v_store_num);
        $v_store_meta = '<title>Golfsmith ' . $storeObj->getStoreName() . ' ' . $storeObj->getStoreState() . ' Store</title>
' .
                                        '<meta property="og:title" content="Golfsmith ' . $storeObj->getStoreName() . ' ' . $storeObj->getStoreState() . ' Store" />
' .
                                        '<meta property="og:description" content="Phone: ' . $storeObj->getStorePhone() . ', Address: ' . $storeObj->getStoreAddress2() .  ', ' . $storeObj->getStoreCity() .  ', ' . $storeObj->getStoreState() .  ', ' . $storeObj->getStoreZip() . '" />
' .
                                        '<meta property="og:type" content="website" />
' .
                                        '<meta property="og:url" content="http://www.golfsmith.com' . $storeObj->getStoreSEOUrl() . '?cm_mmc=social-_-facebook-_-like-_-' . str_replace(' ','-',strtolower($storeObj->getStoreName())) . '&utm_source=facebook&utm_medium=social&utm_content=' . str_replace(' ','-',strtolower($storeObj->getStoreName())) . '&utm_campaign=like" />
' .
                                        '<meta property="og:image" content="http://www.golfsmith.com/_site_images/_retail_pages/storefronts/' . $storeObj->getStoreNum() . '.jpg" />
' .
                                        '<meta property="og:site_name" content="Golfsmith" />
' .
                                        '<meta property="fb:admins" content="1000066968" />
' .
                                        '<meta property="fb:app_id" content="147414978665215" />
';
return $v_store_meta;

  }
  function loadHeader() {

    global $s_screen_name;
    global $override_header_file, $override_header_contents;
	//echo "TEST";
  if((substr_count($_SERVER['HTTP_HOST'],"golfsmithcanada")) > 0 || (substr_count($_SERVER['HTTP_HOST'],"golfsmith.ca")) > 0 || substr_count($_SERVER['REQUEST_URI'],"/ca/") == 1) { // canadian web site.
        $user_home = 'ca' ;
        return;
    }
    if (empty($user_home))
       $user_home = 'ps' ;
    if ($_SERVER['REQUEST_URI'] == '/') {
        $_SESSION['s_screen_name'] = $user_home ;
        $s_screen_name             = $user_home ;
    }
    
    require_once('Zend/View.php');
    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));

    $v_header_path = STATIC_HTML_PATH . '_meta_overrides/';

    //set Bazaarvoice environment
    $v_server_name = $_SERVER['SERVER_NAME'];
    if ( (substr($v_server_name, 0, 6) == "r12dev" || substr($v_server_name, 0, 3) == "dev" || substr($v_server_name, 0, 8) == "redesign" || substr($v_server_name, 0, 7) == "www-dev") && substr($v_server_name, -13) == "golfsmith.com") {
      $z_view->bazaarvoice_environment = 'bvstaging/';
    } else {
      $z_view->bazaarvoice_environment = '';
    }

    //look for a meta override
    if($override_header_file && file_exists($v_header_path . $override_header_file)) {
      $override_header_contents = join( '', file($v_header_path . $override_header_file));
    }
        
    $v_req_url=strtolower($_SERVER['REQUEST_URI']);
    
    $v_find_url="checkout/cart";
    $v_cart_url_pos = strpos($v_req_url,$v_find_url);
    
    
    $v_find_url="stores/";
    $v_stores_url_pos = strpos($v_req_url,$v_find_url);
    
    $v_find_url="product/";
    $v_product_url_pos = strpos($v_req_url,$v_find_url);
    
    $parts = explode("/", $_SERVER['REQUEST_URI']);
   
    if($v_cart_url_pos!=false){
    	$v_header_path = STATIC_HTML_PATH . '_meta_overrides/php/';
      if(file_exists($v_header_path . 'cart.html')) {
        $override_header_contents = join( '', file($v_header_path . 'cart.html'));
      }
    }else if ( ($v_stores_url_pos != false) && ($parts[1] == 'stores') && (!empty($parts[2])) && ($parts[2] != 'search') ) {
		
    	$v_store_num = str_replace(".php","",$parts[2]);

        $override_header_contents = $this->getStoreMeta($v_store_num);          
    }else if ($v_product_url_pos != true) {

    	$v_header_path = STATIC_HTML_PATH . '_meta_overrides/php/';
 
        $v_file_name =str_replace(".php","",$parts[1]). '.html';
        
        if(file_exists($v_header_path . $v_file_name)) {
                $override_header_contents = join( '', file($v_header_path . $v_file_name));
        }
   }
    
    if (!empty($override_header_contents)) {
      $z_view->header_contents = $override_header_contents;

    } else {
// Change some thing from 5 to 4 and '/'.$s_screen_name.'/?' to '/'.$s_screen_name.'/'  substr($_SERVER['REQUEST_URI'],0,4)=='/'.$s_screen_name.'/'
// add new criteria for Ticket #39156
    	if($_SERVER['REQUEST_URI'] == '/'|| $_SERVER['REQUEST_URI']=='/'.$s_screen_name.'/' || substr($_SERVER['REQUEST_URI'],0,4)=='/'.$s_screen_name.'/' || preg_match('/^\/\?.+/',$_SERVER['REQUEST_URI']) == 1){
          $v_header_path = STATIC_HTML_PATH . '_meta_overrides/home/';

          if(file_exists($v_header_path . $s_screen_name . '.html')) {
            $v_header_path .= $s_screen_name . '.html';
          } else {
            $v_header_path .= 'ps.html';
          }
        }else{
          $v_header_path = STATIC_HTML_PATH . '_meta_overrides/';
          if(file_exists($v_header_path . $s_screen_name . '_default_meta.html')) {
            $v_header_path .= $s_screen_name . '_default_meta.html';
          }else {
            $v_header_path .= 'ps_default_meta.html';
          }
     	
        }
    	
      $z_view->header_contents = join('', file($v_header_path));
    }


    $v_occurrence = substr_count($_SERVER['SCRIPT_NAME'], "/home.php");
    if ($this->v_page_type_id == 'home') {
      $z_view->is_homepage = TRUE;
    }
    if($z_view->is_homepage === TRUE && ($_SESSION['s_screen_name'] == 'ps' || empty($_SESSION['s_screen_name']))) {
      $z_view->is_default_homepage = TRUE;
    }

    //Determine production or dev
    $v_prod_count = substr_count($_SERVER['HTTP_HOST'], "www");
    if($v_prod_count > 0 ) {
      $z_view->is_production = TRUE;
    }

    //adjust this later - these will depend on page type
    $z_view->show_flash = TRUE;
    $z_view->body_id = $this->v_page_type_id;

    if(file_exists(STATIC_HTML_PATH . '_site_headers/' . $s_screen_name . '_header.html')) {
      $z_view->site_header_path = STATIC_HTML_PATH . '_site_headers/' . $s_screen_name . '_header.html';
    } else {
      $z_view->site_header_path = STATIC_HTML_PATH . '_site_headers/ps_header.html';
    }

    if(file_exists(STATIC_HTML_PATH . '_site_navigation/' . $s_screen_name . '_navigation.html')) {
      $z_view->site_nav_path = STATIC_HTML_PATH . '_site_navigation/' . $s_screen_name . '_navigation.html';
    } else {
      $z_view->site_nav_path = STATIC_HTML_PATH . '_site_navigation/ps_navigation.html';
    }

    $z_view->sitewide_promo_path = STATIC_HTML_PATH . '_sitewide_promos/sitewide_promo.html';

    $z_view->script_name = $_SERVER['SCRIPT_NAME'];

    echo $z_view->render("page_head.phtml");

  }

  //most of this should probably be in a view, but we'll do that later
  public function loadFooter($googleReMarketing="") {
  	
    global $web_db; ;
    global $connect_apps_db, $apps_db ;
    global $homepage ;
    global $g_data;

    $screen_name = $_SESSION['s_screen_name'] ;

    $footer = '_footer.html';

    include(SPATH . 'gsi_popup');

    ?>
    <!-- FOOTER -->
    <?php
    if(file_exists(STATIC_HTML_PATH . '_site_footers/' . $screen_name . $footer)) {
      include(STATIC_HTML_PATH . '_site_footers/' . $screen_name . $footer) ;
    } else {
      include(STATIC_HTML_PATH . '_site_footers/ps' . $footer);
    }

    ?>
    <!-- END FOOTER -->
    
    <!--*********************ROSERVICE 12/09/2005************************** -->

    <?php
/*
    if(isset($_SESSION['GCID'])) {
      if(substr_count($_SERVER['REQUEST_URI'],"order_review.php") > 0 ) {
      } else { ?>
        <img border="0" width="1" height="1" src="https://track.roiservice.com/track/pixel.gif.aspx?roiid=937206107000016&sid=<?echo session_id();?>&desc=landingpage">
      <?php
      }
    }*/ ?>

    <!--*********************END ROSERVICE************************** -->

    </div>

    <!--[if IE]>
    </div>
    <![endif]-->
	<!-- Google Code for All visits --> 
	<script type="text/javascript"> 
		var google_tag_params = { 
			ecomm_prodid: "<?=$googleReMarketing["prodid"]?>", 
			ecomm_pagetype: "<?=$googleReMarketing["pageType"]?>", 
			ecomm_totalvalue: "<?=$googleReMarketing["totalvalue"]?>" 
		}; 
	</script> 
	<script type="text/javascript"> 
	/* <![CDATA[ */ 
	var google_conversion_id = <?php echo GOOGLE_CONVERSION_ID ?>;
	var google_conversion_label = "<?php echo GOOGLE_CONVERSION_LABEL?>";
	var google_custom_params = window.google_tag_params; 
	var google_remarketing_only = true; 
	/* ]]> */ 
	</script> 
	<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js"> 
	</script> 
	<noscript> 
	<div style="display:inline;"> 
	<img height="1" width="1" style="border-style:none;" alt="" 
src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/1069177894/?value=0&amp;label=0Sw6CI7CnQQQprjp_QM&amp;guid=ON&amp;script=0"/> 
	</div> 
	</noscript>

	<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js"> 
	</script> 
	<noscript> 
	<div style="display:inline;"> 
	<img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/1069177894/?value=0&amp;label=0Sw6CI7CnQQQprjp_QM&amp;guid=ON&amp;script=0"/> 
	</div> 
	</noscript>
	</body></html>

    <?php

    if ($connect_apps_db == 1) {
      OCILogoff($apps_db);
    }
    mysqli_close($web_db);
    }

  //utility functions
  function get_timestamp() {
    date_default_timezone_set('America/Chicago');
    $today = getdate() ;

    $date    = $today['mday'] ;
    $month   = $today['mon'] ;
    $year    = $today['year'] ;
    $hours   = $today['hours'] ;
    $minutes = $today['minutes'] ;
    $seconds = $today['seconds'] ;

    $str = sprintf("%04d%02d%02d%02d%02d%02d", $year, $month , $date , $hours , $minutes , $seconds);
    return($str);
  }

  //private functions
  private function clean_request_ampersands($p_request_value) {

    $v_return = $p_request_value;

    $v_amp_pos = strpos($v_return, '&');

    if($v_amp_pos !== FALSE) {
      //v_amp_pos is desired length, since index starts at 0
      $v_return = substr($v_return, 0, $v_amp_pos);
    }

    return $v_return;

  }
  
  //for now its commited untill webteam finalized
/* 
  public function load_ssl_image()
  {
  	?>
  		<div id="ssl_seal">  		
  		<script language="JavaScript" type="text/javascript"> SiteSeal("https://seal.networksolutions.com/images/evrecgreen.gif", "NETEV", "none");</script>
		</div>  		
  	<?
  }
  */
  
    }
    ?>
