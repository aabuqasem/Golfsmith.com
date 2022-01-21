<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here

include_once( FPATH . 'ene_bridge.inc');
include_once('models/Slider.php');
require_once('models/SiteInit.php');


class DisplayPage {
 
  private $v_page_name;
  private $v_type;
  private $i_site_init;

  public function __construct($p_type, $p_page_name) {
      
    $this->v_type = $p_type;
    $v_parameter_position = strpos($p_page_name, '?');
    //if we have GET params, we only want the page name to be what comes before the "?"
    if($v_parameter_position !== FALSE) {
      $this->v_page_name = strip_tags(substr($p_page_name, 0, $v_parameter_position));
    } else {
      $this->v_page_name = strip_tags($p_page_name);
    }

    
  }

  //display a page
  public function displayPage() {
    global $override_header_contents;
    global $web_db;
    
    if($this->v_type == 'category' || $this->v_type == 'brand') {
      $v_body_id = $this->v_type;
    } else {
      $v_body_id = '';
    }

    
    $i_site_init = new SiteInit($v_body_id);
    $i_site_init->loadInit();

    $v_path = STATIC_HTML_PATH;
    $v_old_path = SPATH;
	$showCatSmartTag = False;
    switch($this->v_type) {
      case 'category': $v_path .= '_category_pages/';
      				$showCatSmartTag = TRUE;
                      break;
      case 'brand':   $v_path .= '_brand_stores/';
                      break;
      case 'popup':   $v_path .= '_popups/';
                      break;
      default:        $v_path .= '_landing_pages/';
                      break;
    }

    $v_header = strip_tags($_GET['hdr']) ;

    if (empty($v_header)){
      $v_header = 'Y' ;
    }

    //meta tags html files
    
    $v_html_file = str_replace('/','_',$this->v_page_name) . '.html';
    
    if ($this->v_type == 'category')
    {
      $file_name = STATIC_HTML_PATH . "_meta_overrides/category/" . $v_html_file;
      
      if(!file_exists($file_name)) {
         $file_name = STATIC_HTML_PATH . "_meta_overrides/" .$_SESSION['s_screen_name'] . '_default_meta.html';
      }     
    }
    else if ($this->v_type == 'brand')  
    {     
       $file_name = STATIC_HTML_PATH . "_meta_overrides/brand/" . $v_html_file; 
      if(!file_exists($file_name)) {
         $file_name = STATIC_HTML_PATH . "_meta_overrides/" .$_SESSION['s_screen_name'] . '_default_meta.html';
       }
    }
    else //default 
    {     
        $file_name = STATIC_HTML_PATH . "_meta_overrides/display/" . $v_html_file;
       if(!file_exists($file_name)) {
         $file_name = STATIC_HTML_PATH . "_meta_overrides/" .$_SESSION['s_screen_name'] . '_default_meta.html';
       }
    }
    
    
    $override_header_contents = file_get_contents($file_name);
    
    if($this->v_page_name == '401_error') { 
      header('HTTP/1.0 404 not found'); 
    }

    if ($v_header == 'Y') {
      $i_site_init->loadMain();
      $_SESSION['s_last_search'] = null;
      /********************************Core Metrics **************************/
      $page=strtoupper($this->v_page_name);
      $s_screen_name = $_SESSION['s_screen_name'];
      $s_screen_name_upper=strtoupper($s_screen_name);
      ?>
      <script type="text/javascript" >
      cmCreatePageviewTag("<?=$s_screen_name_upper?> DISPLAY PAGE: <?=htmlentities($page, ENT_QUOTES, 'UTF-8');?>","DISPLAY PAGE","","");
      </script>
      <?php

      if($this->v_page_name=="401_error"){
      ?>
        <script type="text/javascript">
        cmCreateErrorTag("404 ERROR","ERROR","","");
        googleTracking('Mulligan','Invalid URL','display/401_error',0);
        </script>
      <?php
      }
     /********************************End Core Metrics **************************/

    } else {
      $tracking_code = post_or_get('tcode') ;
      if (!empty($tracking_code)) {
        $partner_code = strtoupper(substr($tracking_code,0,2)) ;
        if ($partner_code == 'CJ' || $partner_code == 'PF') {
          setcookie("tcode","",time()-3600,'/',".golfsmith.com",0,1);     // remove cookie
          setcookie("tcode",$tracking_code,time()+2592000, ".golfsmith.com",".golfsmith.com",0,1); // expire in 30 dayes
          setcookie("prtcode","",time()-3600,'/',".golfsmith.com",0,1);     // remove cookie
          setcookie("prtcode",$partner_code,time()+2592000, ".golfsmith.com",".golfsmith.com",0,1); // expire in 30 days
        } else {
          setcookie("tcode","",time()-3600,'/',".golfsmith.com",0,1);     // remove cookie
          setcookie("tcode",$tracking_code,time()+86400, ".golfsmith.com",".golfsmith.com",0,1); // expire in one day
          setcookie("prtcode","X",time()+1, "/",".golfsmith.com",0,1);     // remove cookie
        }
      }

      $v_source_code = post_or_get('scode');
      if (!empty($v_source_code)) {
        $_SESSION['s_source_code'] = $v_source_code ;
        $_SESSION['s_do_reprice']  = 'Y' ;
        $_SESSION['s_do_totals']   = 'Y' ;
      }
    } // no header

    //need to fix this -- this won't work anymore
    if ( ! function_exists( 'has_products')) {
      function has_products() {
        return true;
      }
    }
    if(file_exists($v_path . $this->v_page_name . '.html'))
    {
        // Get the site name
        $site_name = $_SESSION['s_screen_name'];
        
        // Get page name
        $pageName = $this->v_page_name;
        if(strpos($this->v_page_name, '/'))
        {
            $pageName = substr($this->v_page_name,strpos($this->v_page_name, '/')+1);
        }
        
        // Get the landing page file name
        $stmt = $web_db->prepare("SELECT file_name 
                FROM webonly.gsi_landing_templates
                WHERE site_name= ? AND page_name = ?
                AND NOW() BETWEEN start_date and IFNULL(end_date, NOW())");
        $stmt->bind_param('ss',strtoupper($site_name), $pageName);
        $stmt->execute();
        $stmt->bind_result($filename);
        $stmt->fetch();
        $stmt->close();
        
        // Load up the landing page or show an error page 
        if(ISSET($filename))
        {
            include_once(STATIC_HTML_PATH . "/_landing_pages/$site_name/$filename");
        }
        else
        {
            include($v_path . $this->v_page_name . '.html');
        }
    }
    else
    {
      include($v_old_path . $this->v_page_name . '.html');
    }

    if($this->v_page_type == 'category' || $this->v_page_type == 'brand') {
      // remarketing vars 
      $g_data["pageType"]="category";
    }
    else
    {
      // remarketing vars 
      $g_data["pageType"]="other";
    }
/*
 * Add Customer Recent Review 
 * Author Chantrea 03/02/2012
 */
	if($this->v_type!="popup"){
  $obj_slider = new Slider($_SESSION ['recently_viewed_item_list']['order'],6,"review");
  $obj_slider->displaySlider();
	}
	if ($showCatSmartTag){
  		echo '<script type="text/javascript"> 
  			var _smtr = _smtr || window._smtr || []; 
			_smtr.push(["pageView", { "pageType": "category",
    		"catId": "'.ucfirst(str_replace('-',' ',$this->v_page_name)).'",
    		"catName": "'.ucfirst(str_replace('-',' ',$this->v_page_name)).'"}]);
    		</script>';
	}
    if ($v_header == 'Y') {
    	// remarketing vars
      	$g_data["prodid"]="";
      	$g_data["totalvalue"]="";
      	//
      $i_site_init->loadFooter($g_data);
    }
  }
  
/*  public function GetBrandList($p_cat_id)
  {   
      $ene_request['N'] =  $p_cat_id;
      $v_response = ene_bridge($ene_request);
      $v_brand_search = ene_get_value( $v_response, 'brand_search');
      
      return $v_brand_search;
  }*/
  
 public function GetBrandList($p_cat_id)
{ 
                $search_base_url = ENDECA_LB_URL.'/xml/pages/category-drop-down/_/N-'.$p_cat_id;  
  
                $results_xml = file_get_contents($search_base_url);

               $xml=simplexml_load_string($results_xml);
               $xml->registerXPathNamespace('ns', 'http://endeca.com/schema/xavia/2010');

                $refinement = $xml->xpath('//ns:Item[@class="com.endeca.infront.cartridge.model.Refinement"]');

                //echo '<pre>';
                //print_r($refinement);
                //echo '</pre>';

                $json_string = json_encode($refinement);
    
                $result_array = json_decode($json_string, TRUE);

                //echo '<pre>';
                //var_dump($result_array);
                //echo '</pre>';

                $sortDropdown = '<ene_delimter name="brand_search">
                                                                                                <select name="Brand" onchange="javascript: doRefineBrand(this)" id="brand">
                                                                                                                <option value="-1">Select Brand</option>';

                for($i=0;$i<count($result_array);$i++)
                {
                                //Navigation State - [1]["String"]
                                //Label - [5]["String"]
                                //Count - [3]["Integer"]
                                $sortDropdown .= '<option value="'.$result_array[$i]["Property"][1]["String"].'">'.$result_array[$i]["Property"][5]["String"].' - ('.$result_array[$i]["Property"][3]["Integer"].')</option>';
                }

                $sortDropdown .="</select></ene_delimeter>";

                //echo '<pre>';
                //print($sortDropdown);
                //echo '</pre>';

                return $sortDropdown;
}//end function
  
 
}
?>
