<?php 

//Assumes the controller includes SiteInit.php (which includes gsi_helper.inc)
//Thus, we shouldn't need any other includes here

class PageRedirect {
 
  private $v_redirect_name;

  public function __construct($p_redirect_name) {
    $this->v_redirect_name = $p_redirect_name;
  }

  //handle a redirect
  public function handleRedirect() {

    global $web_db;

    $v_tcode = post_or_get('tcode');

    $HTTP_HOST = $_SERVER['HTTP_HOST'];

    if(substr_count("$HTTP_HOST",'dev') >= 1){
      $v_prefix='r12dev';
    } else if (substr_count("$HTTP_HOST",'test') >= 1) {
      $v_prefix='test';
    } else {
      $v_prefix='www';
    }

    //we're now going to get redirect straight from Zend Framework and our member variable, rather than trying to parse the path
    //file remaps are moved to the database
    if ( !empty($this->v_redirect_name) ) {
      $v_sql = "SELECT map_term 
                FROM gsi_remap_keywords 
                WHERE TRIM(LOWER(original_term)) = TRIM(LOWER('" . $this->v_redirect_name . "')) 
                  AND map_type = 'page'";

      $v_result = mysqli_query($GLOBALS['web_db'], $v_sql);

      display_mysqli_error ($v_result, $v_sql);

      while ($v_row = mysqli_fetch_assoc($v_result)) {
        $v_url = 'http://' . $v_prefix . '.golfsmith.com' . $v_row['map_term'];
      }

    }

    $v_url   = strtolower($v_url) ;

    $v_start_pos = strpos($v_url,'?');

    if ($v_start_pos > 0) {
      $v_sep = '&' ;
    } else {
      $v_sep = '?' ;
    }

    if (empty($v_url)) {
      if (strtolower($_SESSION['site'])=='ca'){
        header("Location: " . '/ca/display/401_error_ca');
      }else{
       header("Location: " . '/display/401_error');
      }
    } else if ($v_tcode) {
      header("Location: " . $v_url . $v_sep . "tcode=". $v_tcode);
    } else {
      header("Location: " . $v_url );
    }

  }
 
}
?>
