<?php 
require_once('Zend/Controller/Action.php');
require_once('controllers/RedirectController.php');
require_once('models/SiteInit.php');


class IndexController extends Zend_Controller_Action {

  public function indexAction() {
    global $web_db;
    //first we need to check the controller name and do a redirect or 401 error if it's nonempty or not 'index'

    $v_controller = $this->getRequest()->getParam('controller');

    if(!empty($v_controller) && substr($v_controller, 0, 5) != 'index') {
      //need to either handle redirect or show 401 page - depends on whether redirect exists
      RedirectController::indexAction($v_controller);
    } else { //main page

      $v_url = strip_tags(post_or_get('url'));
      if(empty($v_url)) {
        $v_url = strip_tags(post_or_get('URL'));
      }

      //if the URL parameter is present, we need a redirect
      if(!empty($v_url)) {
		$v_url = rawurldecode($v_url);
        // ensure that there is a full-fledged url
        if (strPos($v_url, 'http') !== 0) {
          $v_url = 'http://' . $v_url;
        }

        $v_url = strtolower($v_url);

        $v_start_pos = strpos($v_url, "golfsmith.com") ;
        if ($v_start_pos !== FALSE) {
          $this->_redirect($v_url);
          return;
        } //else just show the home page
      }

      //if we didn't have a URL parameter, load the appropriate main page
      $i_site_init = new SiteInit('home');
      $i_site_init->loadInit();

      $user_home = $_COOKIE['usrhm'] ;
      $v_forecast = 'GSI_WEB' ;

      if (empty($user_home))
        $user_home = 'ps' ;

      if ($_SERVER['REQUEST_URI'] == '/') {
        $_SESSION['s_screen_name'] = $user_home ;
        $s_screen_name             = $user_home ;
      }

      if ($_SESSION['site'] == 'US' && $_SERVER['REQUEST_URI'] == '/ca/') {

        $user_home = 'ps';
        header("Location: ".'http://'.$_SERVER['HTTP_HOST'].'/'.$user_home.'/') ;
        exit();
      }

      $trck_screen = $s_screen_name . ' Home' ;

      $override_header_file = $_SESSION['s_screen_name'] . '_homepage_meta.html';

      $i_site_init->loadMain();

      $_SESSION['s_forecast'] = $v_forecast ;
?>
		<!--
		Start of DoubleClick Floodlight Tag: Please do not remove
		Activity name of this tag: Golfsmith_Homepage
		URL of the webpage where the tag is expected to be placed: http://www.golfsmith.com/
		This tag must be placed between the <body> and </body> tags, as close as possible to the opening tag.
		Creation Date: 01/15/2014
		-->
		<script type="text/javascript">
		var axel = Math.random() + "";
		var a = axel * 10000000000000;
		var v_http = ('https:' == document.location.protocol ? 'https://' : 'http://');
		document.write('<iframe src="'+v_http+'4350451.fls.doubleclick.net/activityi;src=4350451;type=count722;cat=homep563;ord=' + a + '?" width="1" height="1" frameborder="0" style="display:none"></iframe>');
		</script>
		<noscript>
		<iframe src="https://4350451.fls.doubleclick.net/activityi;src=4350451;type=count722;cat=homep563;ord=1?" width="1" height="1" frameborder="0" style="display:none"></iframe>
		</noscript>
		
		<!-- End of DoubleClick Floodlight Tag: Please do not remove -->

<?php 
	  // get the template for today and show that here
      $sql = 'SELECT site_name,file_name FROM webonly.gsi_page_templates WHERE site_name="'.$_SESSION['s_screen_name'].'" 
      			AND "'.date('m-d-Y H:i').'" BETWEEN DATE_FORMAT(start_date,"%m-%d-%Y %H:%i") 
      			AND DATE_FORMAT(end_date, "%m-%d-%Y %H:%i")';

      $result = mysqli_query($web_db, $sql);
      $row = mysqli_fetch_array($result);
        
      if(isset($row['file_name']) && file_exists(STATIC_HTML_PATH . '_home_pages/' . strtolower($row['site_name']) . '/' . $row['file_name'])) {
      	
      		include(STATIC_HTML_PATH . '_home_pages/' . strtolower($row['site_name']) . '/'. $row['file_name']);
      		
      }else if(file_exists(STATIC_HTML_PATH . '_home_pages/' .   $_SESSION['s_screen_name'] . '/' . $_SESSION['s_screen_name'] . '_homepage.html')) {
      
      		include(STATIC_HTML_PATH . '_home_pages/' .   $_SESSION['s_screen_name'] . '/'. $_SESSION['s_screen_name'] . '_homepage.html');
      	
      }else {
      	
            include(HTML_PATH . '_home_pages/ps_homepage.html');
            
      }      

      
      //***********coremetrics ***********************
      include(FPATH .'cm_tags');
      $s_screen_name_upper=strtoupper($s_screen_name);
      cm_tech_properties($s_screen_name_upper);
      //**************end core metrics *******************

      $g_data["pageType"]="home";
      $g_data["prodid"]="";
      $g_data["totalvalue"]="";
      $i_site_init->loadFooter($g_data);
    }

  }

}
?>
