<?php 
require_once('Zend/Controller/Action.php');
require_once('models/SiteInit.php');
require_once('models/DisplayPage.php');

class DisplayController extends Zend_Controller_Action {

  public function __call($p_method, $p_args) {

    //call indexAction
    $this->indexAction();

  }

  public function http_redirect($location){
  		header( "HTTP/1.1 301 Moved Permanently" );
		header( "Location: /".$location);
		die();
  }
  
  public function indexAction() {

    //our parameter isn't named in the URL, so we have to get it manually
    $v_file = $this->getRequest()->getRequestUri();
   
    if(strpos($v_file, '/index/') !== FALSE) {

      $v_file = strstr($v_file, '/index/');
      $v_file = str_replace('/index/', '', $v_file);

      //if the last character is '/', remove it
      $v_last_character = substr($v_file, strlen($v_file) - 1, 1);
    
      if($v_last_character == '/') {
        $v_file = substr($v_file, 0, strlen($v_file) - 1);
      }

    } else {

      //the action is actually the file name in this case
      $va_params = $this->_getAllParams();
      $v_file = $va_params['action'];

      //if no file name given in path, try the GET params
      if(empty($v_file) || $v_file == 'index') {  //default
        $v_file = $va_params['page'];
      }

    }

    $i_display = new DisplayPage('default', $v_file);

    $i_display->displayPage();

  }

  public function categoryAction() {

    //our parameter isn't named in the URL, so we have to get it manually
    $v_file = $this->getRequest()->getRequestUri();
    $v_file = strstr($v_file, '/category/');
    $v_file = str_replace('/category/', '', $v_file);

    //if the last character is '/', remove it
    $v_last_character = substr($v_file, strlen($v_file) - 1, 1);

    if($v_last_character == '/') {
      $v_file = substr($v_file, 0, strlen($v_file) - 1);
    }
    
    /*
    $i_display = new DisplayPage('category', $v_file);

    $i_display->displayPage();
	*/
    
    //301 redirect implementation - START
    global $web_db;
    $i_site_init = new SiteInit('');
    $i_site_init->loadInit();
    
    //get query string
    $v_pos = strpos($v_file, '?');
    $v_new_query_string = '';
    
    if ($v_pos !== false) {
    	
    	$v_query_string = strstr($v_file, '?');
    	$v_file = strstr($v_file, '?', true);
    	$v_query_string = substr($v_query_string, 1); 
    	$v_arr_query_string = explode("&",$v_query_string);
    
	   	foreach ($v_arr_query_string as $key=> $val){
	   		
	   		//extract array
	   		$v_query_key = strstr($val, '=', true);
	   		$v_query_val = strstr($val, '=');
	   		$v_query_val = substr($v_query_val, 1);
	   		
	   		if (($v_query_key == 'tcode') || ($v_query_key == 'scode') || ($v_query_key == 'cm_mmc')
	   			|| ($v_query_key == 'isRedirect') || ($v_query_key == 'cm_lm'))
	   		{
	   			$v_new_query_string = $v_new_query_string . '&' . $v_query_key . '=' . $v_query_val;
	   		}
	 	}	    
	 	
	 	//remove the first ampersand
	 	$v_new_query_string = substr($v_new_query_string, 1);
	 	
	    if (strlen($v_new_query_string) > 0)
	 	{
	 		$v_new_query_string = '?' . $v_new_query_string;
	 	}
    	
    }  
        
    $strSQL = "SELECT map_term FROM gsi_remap_keywords WHERE original_term = TRIM(LOWER('$v_file')) and map_type = 'CAT'";
    $objResult = mysqli_query($web_db, $strSQL);
    if($row = mysqli_fetch_assoc($objResult)) {
  		$new_term = $row['map_term'];
    } else {
    	$new_term = $v_file;
    }
    Header( "HTTP/1.1 301 Moved Permanently" );
	Header( 'Location: http://' . $_SERVER["HTTP_HOST"] . "/category/" . $new_term . $v_new_query_string);
	
	die();
	//301 redirect implementation - END

  }


  public function brandAction() {

    //our parameter isn't named in the URL, so we have to get it manually
    $v_file = $this->getRequest()->getRequestUri();
    $v_file = strstr($v_file, '/brand/');
    $v_file = str_replace('/brand/', '', $v_file);

    //if the last character is '/', remove it
    $v_last_character = substr($v_file, strlen($v_file) - 1, 1);
    
    if($v_last_character == '/') {
      $v_file = substr($v_file, 0, strlen($v_file) - 1);
    }
/*
    $i_display = new DisplayPage('brand', $v_file);

    $i_display->displayPage();
*/
    //get query string
    $v_pos = strpos($v_file, '?');
    $v_new_query_string = '';
    
    if ($v_pos !== false) {
    	
    	$v_query_string = strstr($v_file, '?');
    	$v_file = strstr($v_file, '?', true);
    	$v_query_string = substr($v_query_string, 1); 
    	$v_arr_query_string = explode("&",$v_query_string);
    
	   	foreach ($v_arr_query_string as $key=> $val){
	   		
	   		//extract array
	   		$v_query_key = strstr($val, '=', true);
	   		$v_query_val = strstr($val, '=');
	   		$v_query_val = substr($v_query_val, 1);
	   		
	   		if (($v_query_key == 'tcode') || ($v_query_key == 'scode') || ($v_query_key == 'cm_mmc')
	   			|| ($v_query_key == 'isRedirect') || ($v_query_key == 'cm_lm'))
	   		{
	   			$v_new_query_string = $v_new_query_string . '&' . $v_query_key . '=' . $v_query_val;
	   		}
	 	}	    
	 	
	 	//remove the first ampersand
	 	$v_new_query_string = substr($v_new_query_string, 1);
	 	
	    if (strlen($v_new_query_string) > 0)
	 	{
	 		$v_new_query_string = '?' . $v_new_query_string;
	 	}
    	
    }
 	
    //301 redirect implementation - START
    Header( "HTTP/1.1 301 Moved Permanently" );
	Header( 'Location: http://'.$_SERVER["HTTP_HOST"] . "/brand/" . $v_file . $v_new_query_string);
	die();
	//301 redirect implementation - END
  }

  public function popupAction() {
    //our parameter isn't named in the URL, so we have to get it manually
    $v_file = $this->getRequest()->getRequestUri();
    $v_file = strstr($v_file, '/popup/');
    $v_file = str_replace('/popup/', '', $v_file);

    //if the last character is '/', remove it
    $v_last_character = substr($v_file, strlen($v_file) - 1, 1);

    if($v_last_character == '/') {
      $v_file = substr($v_file, 0, strlen($v_file) - 1);
    }

    $i_display = new DisplayPage('popup', $v_file);

    $i_display->displayPage();
  }

}
?>
