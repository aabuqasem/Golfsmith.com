<?php
/*
 * 
 * @author Hosam Mahmoud
 * @version 0.1
 * @Store controller golfsmith.com/stores/ golfsmith.com/stores/*
 * 
 * 
 */ 
require_once('Zend/Controller/Action.php');
require_once('Zend/View.php');
require_once('models/SiteInit.php');
require_once('models/StoreFinder.php');
require_once('models/StoreFeature.php');
require_once('models/EventList.php');
require_once('models/EventType.php');
include_once('models/States.php');
include_once('models/Banner.php');
//include('gsi_common.inc');

class StoresController extends Zend_Controller_Action {
	
	private $i_site_init;
	private $store_search;
	private $s_employment_opportunity;
	private $s_store_pickup;
	private $events;
	private $event_types;
	
	private function startInit()
	{       
    	$this->i_site_init = new SiteInit();
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Pragma: no-cache");
        $this->i_site_init->loadInit();
        $this->z_view = new Zend_view(array('scriptPath' => VIEW_PATH));
	}

	public function __call($p_method, $p_args) {

    //call indexAction
    $this->indexAction();

  	}

  	public function indexAction() {
		$v_port = $v_port = $_SERVER['SERVER_PORT'];
	    if($v_port == '443') {
	      $this->_redirect('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
	      return;
	    }

	    //action is actually style number
		$this->startInit();

		$this->i_site_init->loadMain();
	    $va_params = $this->_getAllParams();
	    //var_dump($va_params);

	    if (isset($va_params["action"]) && $va_params["action"] != 0){
	    		$this->get_Store_Page($va_params["action"]);
	    }
		else 
		{
			//to get all the sotre
  			$store_finder = new StoreFinder();
	    	$all_stores = $store_finder->getStores();
	    	foreach ($all_stores as $store){
				$this->z_view->all_stores .= "<li><a href='".$store->getStoreSEOUrl()."'>".$store->getStoreName()."</a></li>";
	    	}
	    
			// get the features filters  
			$features = $this->getFeatures();
			foreach ($features as $key => $value){
				$features_div .= '<li><input id="'.$value['class'].'" type="checkbox" value="'.$key.'" checked="checked"/>
							<label class="'.$value['class'].'-attr" title="'.$value['feature'].
							'" for="'.$value['class'].'">'.$value['feature'].
							'</label></li>';
			}
		
			/* Store Finder Banner Start */
			$v_banner = new Banner(); 
			$v_banner->getStoreFinderBanner(); // call the function that will retrieve banner for Store Finder page
			$this->z_view->banner_title = $v_banner->getBannerTitle();
			$this->z_view->destination_url = $v_banner->getDestinationUrl();
			$this->z_view->image_ref = $v_banner->getImageRef();
			/* Store Finder Banner End */
		
			// asign the next variable with the divs
			$this->z_view->page_name = "Stores"; 
			$v_states_abb = new States();
			$this->z_view->all_states_abb = $v_states_abb->getAllStates();
			$this->z_view->store_search = $this->z_view->render("store_search.phtml");

			$this->z_view->store_features = $features_div;
		
			$this->z_view->store_finder_footer = $this->z_view->render("store_finder_footer.phtml");
			$this->z_view->usa_static_map = $this->z_view->render("usa_static_map.phtml");
			echo $this->z_view->render("storelocator_top.phtml");
		}
		$g_data["pageType"]="other";
      	$g_data["prodid"]="";
      	$g_data["totalvalue"]="";
		
		$this->i_site_init->loadFooter($g_data);
	
	}
	
	public function searchAction(){
		 $v_port = $v_port = $_SERVER['SERVER_PORT'];
	    if($v_port == '443') {
	      $this->_redirect('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
	      return;
	    }
	    //action is actually style number
		$this->startInit();
		$this->i_site_init->loadMain();
	    $va_params = $this->_getAllParams();
	    // to Do 
	    //I have to work in it to get the serach by zipcode 
	    if (isset($va_params["zip_code"])){
	    	$this->z_view->search_type = "zipcode";
	    	$this->z_view->searcg_zip_code_url=$va_params["zip_code"];
	    	
	    }else if (isset($va_params["city"]) && isset($va_params["state"])){
	    	$this->z_view->search_type = "citystate";
	    	$this->z_view->searcg_city_url=$va_params["city"];
	    	$this->z_view->searcg_state_url=strtoupper ($va_params["state"]);
	    	
	    }else if (isset($va_params["state"])){
	    	$this->z_view->search_type = "state";
	    	$this->z_view->searcg_state_url=strtoupper ($va_params["state"]);
	    	
	    }
	    $this->z_view->store_search_zip_url = $this->z_view->render("serach_by_zipcode_url.phtml");
		// get the features filters  
		$features = $this->getFeatures();
		foreach ($features as $key => $value){
		$features_div .= '<li><input id="'.$value['class'].'" type="checkbox" value="'.$key.'" checked="checked"/>
							<label class="'.$value['class'].'-attr" title="'.$value['feature'].
							'-filter" for="'.$value['class'].'">'.$value['feature'].
							'</label></li>';
		}
		
		/* Store Finder Banner Start */
			$v_banner = new Banner(); 
			$v_banner->getStoreFinderBanner(); // call the function that will retrieve banner for Store Finder page
			var_dump();
			$this->z_view->banner_title = $v_banner->getBannerTitle();
			$this->z_view->destination_url = $v_banner->getDestinationUrl();
			$this->z_view->image_ref = $v_banner->getImageRef();
		/* Store Finder Banner End */
			
		// asign the next variable with the divs
		$this->z_view->page_name = "Stores"; 
		$v_states_abb = new States();
		$this->z_view->all_states_abb = $v_states_abb->getAllStates();
		$this->z_view->store_search = $this->z_view->render("store_search.phtml");
		
		$this->z_view->store_features = $features_div;
		
		$this->z_view->store_finder_footer = $this->z_view->render("store_finder_footer.phtml");
		$this->z_view->s_store_pickup = $this->z_view->render("store_pickup.phtml");
		$this->z_view->usa_static_map = $this->z_view->render("usa_static_map.phtml");
	    
	    echo $this->z_view->render("storelocator_top.phtml");
	    $g_data["pageType"]="other";
      	$g_data["prodid"]="";
      	$g_data["totalvalue"]="";
	    
		$this->i_site_init->loadFooter($g_data);
	}
	
	public function get_Store_Page($store_number){
		$storeObj = new Store($store_number);
		$this->z_view->store_id = $storeObj->getStoreId();
		$this->z_view->store_name = $storeObj->getStoreName();
		if($storeObj->getStoreName()!=""){
		$s_address = $storeObj->getStoreAddress2().", ".$storeObj->getStoreCity() .", ".ucwords(strtolower($storeObj->getStoreState()));
		$this->z_view->store_address = $s_address;
		$this->z_view->store_image = $storeObj->getStoreImage();
		$this->z_view->store_phone = $storeObj->getStorePhone();
		$this->z_view->store_number = $storeObj->getStoreNum();
		$this->z_view->store_zipcode = $storeObj->getStoreZip();
		$this->z_view->store_gm = $storeObj->getStoreGM();
		$this->z_view->store_lat = $storeObj->getStoreLat();
		$this->z_view->store_long = $storeObj->getStoreLong();
		$this->z_view->getStoreSEOUrl = $storeObj->getStoreSEOUrl();
		$storeFeatures = $storeObj->getFeatureIds();
		if (!is_null($_SESSION["visitor"]["address"])){
   			$this->z_view->visitor_session =$_SESSION["visitor"]["address"];
   		}else{
   			$this->z_view->visitor_session = 'Enter Your Address';
   		}
		// for the serach new link Ticket 438
		$search_link = '';
		if (!empty ($_SESSION['visitor']['zip'])){
			$search_link = '?zip_code='.$_SESSION['visitor']['zip']; 
		}elseif(!empty($_SESSION["visitor"]["state"]) && !empty($_SESSION["visitor"]["city"]) ){
			$search_link = '?city='.$_SESSION["visitor"]["city"].'&state='.strtoupper($_SESSION["visitor"]["state"]);
		}elseif (!empty($_SESSION["visitor"]["state"]) && empty($_SESSION["visitor"]["city"])){
			$search_link = '?state='.strtoupper($_SESSION["visitor"]["state"]); 
		}
		$this->z_view->search_session_link = $search_link;
		
		
		
		
		$storeObj->getStoreAccouncement();
		$this->z_view->store_announcement = $storeObj->getAnnouncement();
		$banner = new Banner();
		$banner->setStoreId($storeObj->getStoreId());
        $banner->getStoreBanner();
		
		$this->z_view->banner_id = $banner->getBannerId();
		$this->z_view->banner_title = $banner->getBannerTitle();
		$this->z_view->banner_url = $banner->getDestinationUrl();
		$this->z_view->banner_img = $banner->getImageRef();

		foreach ($storeFeatures as $feature){
			$feature_name = str_replace(' ','-',strtolower($feature->getFeatureName()));
			$this->z_view->store_features .= '<li id="'.$storeObj->getStoreId().'-attr-'.$feature_name.
			'"><a class="'.$feature_name.'-attr" title="'.$feature->getFeatureName().'">'.$feature->getFeatureName().'</a></li>';
		}
		// store hours 
		$hoursPerDaysArray = $this->getRsortedWeekArray($storeObj->getStoreHours());

		for ($i = 0; $i < count($hoursPerDaysArray) ; $i++){
			$closed = $hoursPerDaysArray[$i]["hours"]["closed"] == 'N'? '': '<span class="closed">Closed ('.substr($hoursPerDaysArray[$i]["hours"]["date"],0,5).')</span>';
			$today = (strtolower(substr($hoursPerDaysArray[$i]["name"],0,3))==strtolower(date('D',strtotime(date('Y-m-d')))))?'today':strtolower(substr($hoursPerDaysArray[$i]["name"],0,3));
			$this->z_view->store_hours .= '<li class="'.$today.'" id="'.strtolower(substr($hoursPerDaysArray[$i]["name"],0,3)).'">
				<p><span class="week-day">'.$hoursPerDaysArray[$i]["name"].':</span>';
			if($hoursPerDaysArray[$i]["hours"]["closed"]=="Y")
			{
				$this->z_view->store_hours .= $closed;
			}else
			$this->z_view->store_hours .= '<span class="open-time">'.str_replace(" ","",trim($hoursPerDaysArray[$i]["hours"]["start"],"mM")).'</span> &#8209;
				<span class="close-time">'.str_replace(" ","",trim($hoursPerDaysArray[$i]["hours"]["end"]," mM\x20")).'</span>'.
				$closed. 
				'</p></li>';
		}
		//store nearest stores 
		$store_finder = new StoreFinder();
		$zip_code = $storeObj->getStoreZip();
		if (strlen($zip_code)>5){
			$zip_code = substr($zip_code,0,5);
		}
		$store_finder->setZip($zip_code);
		$store_finder->setRadius(75);
		$store_finder->setResultLimit(5);
		$stores = $store_finder->getStores();
		foreach ($stores as $store){
			if ($store->getStoreNum() != $storeObj->getStoreNum()){
				
				$this->z_view->nearest_stores .='<li><a title="'.$store->getStoreName().'" href="'. $store->getStoreSEOUrl() . '">'.$store->getStoreName().'</a></li>'."\n";
			}	
		}
		$events = $this->_showEvents($storeObj->getStoreCity(),$storeObj->getStoreState(),$storeObj->getStoreZip());
		
		$this->z_view->event_types = $this->event_types;
		$this->z_view->events = $this->events;
	
		echo $this->z_view->render("storepage_top.phtml");
		}else echo "<B>We're sorry, but you we couldn't retrieve the store you specified.</B>";
//		$this->i_site_init->loadFooter();
	//	exit();

	}
  	// to load the content of pop up window for email directions 
	public function sendemailcontentAction(){
		$this->startInit();
		echo $this->z_view->render("sendEmailContent.phtml");
	}
	
  	public function ajaxAction() {
  		$results = array();
		$this->startInit();
   		$va_params = $this->_getAllParams();
		$store_finder = new StoreFinder();
		if ($va_params["type"] == "zipcode"){
   			$store_finder->setZip($va_params["value"]);
			$store_finder->setRadius(75);
			$stores = $store_finder->getStores();
			$_SESSION['visitor']['zip'] = $va_params["value"];
   		}
   		
   		if ($va_params["type"] == "state"){
			$store_finder->setState($va_params["value"]);
			$stores = $store_finder->getStoresByState();
			// to return the state name
			$v_states_abb = new States();
 			$this->z_view->all_states_abb = $v_states_abb->getAllStates();
			foreach($v_states_abb->getAllStates() AS $state){
				if ($state->getStateAbb()== strtoupper($va_params["value"])){
					$results["statename"]= ucwords(strtolower($state->getStateName())) ;
				}
			}
			$_SESSION["visitor"]["state"]= $va_params["value"];
			
   		}
   		
   		if ($va_params["type"] == "citystate"){
   			if ($va_params["value"]=='St Louis,MO' || $va_params["value"]=='St. Louis,MO'){
				$va_params["value"]='Saint Louis,MO';
			}
   			$paras = explode (",",$va_params["value"]);
			$store_finder->setCity($paras[0]);
			$store_finder->setState($paras[1]);
			$store_finder->setRadius(75);
			$stores = $store_finder->getStores();
			$_SESSION["visitor"]["state"]= $paras[1];
			$_SESSION["visitor"]["city"]= $paras[0];
   		}
   		
   		if ($va_params["type"] == "citystate" || ($va_params["type"] == "state") || ($va_params["type"] == "zipcode")){
   		    $results["type"]= $va_params["type"];
   		    $results["status"]= "ok";
   		}else{
   		    $results["type"]= "WrongType";
   		    $results["status"]= "NoResults";
   		}
   		$g_features = $this->getFeatures();
		$results["results"] = array();
		
		$storecounter = 0;
		foreach ($stores as $store){
			$results["results"][$storecounter]["storeID"]=$store->getStoreId();
			$results["results"][$storecounter]["storeNum"]=$store->getStoreNum();
			$results["results"][$storecounter]["StoreLat"]=$store->getStoreLat();
			$results["results"][$storecounter]["StoreLong"]=$store->getStoreLong();
			$results["results"][$storecounter]["Name"]=$store->getStoreName();
			$s_address = $store->getStoreAddress2().", ".$store->getStoreCity() .", ".ucwords(strtolower($store->getStoreState()));
			$results["results"][$storecounter]["address"]= $s_address; 
			$results["results"][$storecounter]["Radius"]=$store->getDistance();
			$results["results"][$storecounter]["phones"]=$store->getStorePhone();
			$results["results"][$storecounter]["seourl"]=$store->getStoreSEOUrl();
			// formating the contextual-info of ggole map 
			$hoursResults = $store->getStoreHours();
			if($hoursResults[date('D',strtotime(date('Y-m-d')))]['closed']=='Y')
			{
				$this->z_view->start_hour = '<span class="closed">Closed</span>';
				$this->z_view->end_hour = '';
			}
			else 
			{
				$this->z_view->start_hour = $hoursResults[date('D',strtotime(date('Y-m-d')))]['start'];
				$this->z_view->end_hour = $hoursResults[date('D',strtotime(date('Y-m-d')))]['end'];
			}
			$this->z_view->store_name = $store->getStoreName();
			$this->z_view->store_name_url = $store->getStoreSEOUrl();
			$this->z_view->store_id = $store->getStoreNum();
			$this->z_view->store_image = $store->getStoreImage();
			$this->z_view->store_phones = $store->getStorePhone();
			$this->z_view->store_address = $s_address;
			
			$store_features = $store->getFeatureIds();
	
			foreach ($store_features as $feature){
					$results["results"][$storecounter]["features"] .= '<li id="'.$store->getStoreId().'-attr-'.str_replace(' ','-',strtolower($feature->getFeatureName())).
							'"><a class="'.str_replace(' ','-',strtolower($feature->getFeatureName())).'-attr" id="feature_'.$feature->getFeatureId().'" title="'.$feature->getFeatureName().'">'.
							$feature->getFeatureName().'</a></li>';
			}
			$results["results"][$storecounter]["contextualinfo"]=$this->z_view->render("contextual-info.phtml");
			$storecounter++;
		}
		$results["counter"]= $storecounter;
		$results["HTML"] = "" ;
		
		if (count($stores) > 0){
			for ($i=0;$i<$results["counter"]; $i++){
				$this->z_view->store_id = $results["results"][$i]["storeID"];
				$this->z_view->store_num = $results["results"][$i]["storeNum"];
				$this->z_view->store_name = $results["results"][$i]["Name"];
				$this->z_view->url = $results["results"][$i]["seourl"];
				$this->z_view->store_address= $results["results"][$i]["address"];
				$this->z_view->store_phonenumber= $results["results"][$i]["phones"];
				$this->z_view->loop_counter = $i;
				$this->z_view->store_features = $results["results"][$i]["features"];
				for ($j=0;$j<count ($results["results"][$i]["feature"]);$j++){
					$this->z_view->store_features .= '<li id='.$results["results"][$i]["storeID"].
													'-attr-'.$results["results"][$i]["feature"][$j]["featurename"].
													'"><a title="'.$results["results"][$i]["feature"][$j]["featurename"].
													'" class="'.$results["results"][$i]["feature"][$j]["featurdev"].
													'">'.$results["results"][$i]["feature"][$j]["featurename"].'</a></li>';
				}
				$results["HTML"] .= $this->z_view->render("store_finder_results_row.phtml");
			}
		}elseif(count($stores) == 0 && $va_params["type"] == "zipcode"){
			$store_finder->setZip($va_params["value"]);
			$store_finder->setResultLimit(5);
			$store_finder->setRadius('');
			$stores = $store_finder->getStores();
			if (count($stores) > 0){
				$results["status"]= "noStoresInLimitedRadius";
				foreach ($stores as $store){
					$url = $store->getStoreSEOUrl();
					$results["HTML"] .= "<li><a href='".$url."' title='".
						$store->getStoreName()."'>".$store->getStoreName()."</a><span> ".
						substr($store->getDistance(),0,5)." mi.</span></li>"."\n";
				}
			}else{
				$results["status"]= "noStoresFoundByZipCode";
				$results["HTML"] = "";
			}
			unset($_SESSION['visitor']['zip']);
		}elseif(count($stores) == 0 && $va_params["type"] == "state"){
			$results["status"]= "noStoresInState";
			$results["HTML"] = "Sorry, we do not have a Golfsmith store in state of <span id='search-criteria'>".$va_params["value"]."</span>";
			unset($_SESSION["visitor"]["state"]);
		}elseif(count($stores) == 0 && $va_params["type"] == "citystate"){
			$paras = explode (",",$va_params["value"]);
			$store_finder->setCity($paras[0]);
			$store_finder->setState($paras[1]);
			$store_finder->setRadius('');
			$store_finder->setResultLimit(5);
			$stores = $store_finder->getStores();
			if (count($stores) > 0){
				$results["status"]= "noStoresInLimitedRadius";
				foreach ($stores as $store){
					$url = $store->getStoreSEOUrl();
					$results["HTML"] .= "<li><a href='".$url."' title='".
						$store->getStoreName()."'>".$store->getStoreName()."</a><span> ".
						substr($store->getDistance(),0,5)." mi.</span></li>"."\n";
				}
			}else{
				$results["status"]= "noStoresInCityState";
				$results["HTML"] = "";
			}
			unset($_SESSION["visitor"]["state"]);
			unset($_SESSION["visitor"]["city"]);
		}
		header('Content-type: application/json');
		echo (json_encode($results));
	    exit();
  }

	public function emaildirsAction(){
		$this->startInit();
   		$va_params = $this->_getAllParams();
   		$email_subj = "Direction to Golfsmith ".$va_params["storeName"];
   		$to_email = $va_params["email"];
   		$start = str_replace (" ","+",$va_params["start"]);
   		$end = str_replace (" ","+",$va_params["end"]);
   		$link = "https://maps.google.com/maps?saddr=" . $start . "&daddr=" . $end . "&hl=en";
   		$this->mail_directions($va_params["storeName"],$va_params["end"], $va_params["start"], $link,$to_email);
   		header('Content-type: application/json');
		//echo (json_encode($msg));
	    exit();
	}
	
	public function getFeatures(){
		global $web_db;
    	$sql = "SELECT feature_id, feature, div_html, activated 
    			FROM webonly.gsi_store_features
    			WHERE activated = 1";
    	$result = mysqli_query($web_db,$sql);
    	$feature_array = array();
    	while($row=mysqli_fetch_array($result)){
    		$counter = $row['feature_id'];
    		$feature_array[$counter]['feature']= $row['feature'];
    		$feature_array[$counter]['class']=str_replace(' ','-',strtolower($row['feature']));
    	}
    	return $feature_array;
	} 
	
	public function addresssessionAction(){
		$this->startInit();
   		$va_params = $this->_getAllParams();
   		if ($va_params["type"]=="save"){
   			$_SESSION["visitor"]["address"]= $va_params["start"];
   		}elseif ($va_params["type"] == "get"){
   			header('Content-type: application/json');
   			if (!is_null($_SESSION["visitor"]["address"])){
   				echo (json_encode($_SESSION["visitor"]["address"]));
   			}else{
   				echo (json_encode('Enter Your Address'));
   			}
  			
			
   		}elseif ($va_params["type"] == "vSave"){
   			if ($va_params["vType"] == "zipCode"){
				$_SESSION["visitor"]["zip"]= $va_params["value"];
   			}elseif($va_params["vType"] == "state"){
   				$_SESSION["visitor"]["state"]= $va_params["value"];
   			}elseif($va_params["vType"] == "cityState"){
   				$searchCritariaArr = explode (",",$va_params["value"]);
   				$_SESSION["visitor"]["state"]= $searchCritariaArr[0];
   				$_SESSION["visitor"]["state"]= $searchCritariaArr[1];
   			}
   		}elseif($va_params["type"] == "getSearch"){
   			header('Content-type: application/json');
   			$tempArray = array("city"=>$_SESSION["visitor"]["city"],
   								"state"=>$_SESSION["visitor"]["state"],
   								"zip"=>$_SESSION["visitor"]["zip"]);
			echo (json_encode($tempArray));
   		}
	}
	
	function getRsortedWeekArray($week){
		$tempArray = array(); 
		if (is_array($week)){
			$tempArray[0]["name"]="Sunday" ;
			$tempArray[0]["hours"] =$week["Sun"]; 
			
			$tempArray[1]["name"]="Monday" ;
			$tempArray[1]["hours"] =$week["Mon"]; 
			
			$tempArray[2]["name"]="Tuesday" ;
			$tempArray[2]["hours"] =$week["Tue"]; 
			
			$tempArray[3]["name"]="Wednesday" ;
			$tempArray[3]["hours"] =$week["Wed"]; 
			
			$tempArray[4]["name"]="Thursday" ;
			$tempArray[4]["hours"] =$week["Thu"]; 
			
			$tempArray[5]["name"]="Friday" ;
			$tempArray[5]["hours"] =$week["Fri"]; 
			
			$tempArray[6]["name"]="Saturday" ;
			$tempArray[6]["hours"] =$week["Sat"]; 
			
		}
		return $tempArray;
	}
	
	function prepareURL($v_sub_url){
		$v_sub_url = preg_replace("![^a-z0-9]+!i", "-", $v_sub_url);
    	$v_sub_url    = str_replace(" - ", "-", $v_sub_url);
		$v_spec_chars = array(".", ",", "'", "*", "&", "%", "$", "#", "@", "!", "(", ")", "[", "]", "{", "}", "?", "<", ">","¿");
		$v_sub_url    = str_replace($v_spec_chars, "", $v_sub_url);
		$v_spec_chars = array("_", ":", ";");
		$v_sub_url    = str_replace($v_spec_chars, "-", $v_sub_url);
		$v_sub_url	  = strtolower($v_sub_url);
		return $v_sub_url;
	}
	
	// This function is copied from eventCtroller.php with little modifications 
	// remove all the refernces to global varibales change them to be local variables
	private function _showEvents($city,$state,$zip)
  	{
  		$v_store_finder = new StoreFinder(); // object of store finder class to retrieve stores
  		if (strlen($zip)>5){
			$zip = substr($zip,0,5);
		}
  		$v_store_finder->setZip($zip);
  		//$v_store_finder->setCity($city);
  		//$v_store_finder->setState($state);
  		$v_store_finder->setRadius(1); // within 2 miles radius to get just one store
  		$v_stores = $v_store_finder->getStores();
  		$v_events = array();
  		$v_event_types = array();
		$v_event_type_ids = array();
  		foreach($v_stores as $v_store){ // for every store, get the states and events
  			$v_event_list = new EventList(); // object of event list class to retrieve events
  			$v_event_list->setStoreId($v_store->getStoreId()); // set store id to get events that belong to this store
  			if($this->event_type_ids)
  			{
  				$v_event_list->setFilterBy(explode(',',$this->event_type_ids)); // set filters if there are any sent
  			}
  			$events = $v_event_list->getEvents(); // get Event objects
  			foreach($events as $event)
  			{
  				if(!in_array($event->getEventTypeId(),$v_event_type_ids))
  					array_push($v_event_types, new EventType($event->getEventTypeId())); // store the event type objects to show the filter listing
  				array_push($v_event_type_ids,$event->getEventTypeId());
  			}
  			$events; // get events for each store
  			break;
  		}
		
		$this->events = $events; // array of event objs for showing the event related information
		$this->event_types = $v_event_types; // array of event types for filters listing
  	}
  	
  	function mail_directions($store_name,$store_address, $from_address ,$link ,$to_email){
		$strHeaders = 'MIME-Version: 1.0' . "\r\n";
		$strHeaders .= 'Content-Type: text/html; charset=iso-8859-1' . "\r\n";
		$strHeaders .= 'From: "Golfsmith Store Directions" <do_not_reply@golfsmith.com>' . "\r\n";
		$strHeaders .= 'Reply-To: "' . $_POST['txtSendName'] . '" <' . $_POST['txtSendEmail'] . '>' . "\r\n";
		//$strHeaders .= 'To: ' . $to_email . '' . "\r\n";
		$massage = "Someone has sent you a link to view Google driving directions...
		". "<br /><br />";
		$massage .= "...from ". $from_address . "<br /><br />";
		$massage .= "...to Golfsmith ".$store_name ."( ".$store_address.")" . "<br /><br />";
		$massage .= "View Directions on Google: ".$link . "<br /><br />";
		!mail($to_email, "Driving Directions to Golfsmith", $massage, $strHeaders);
  		
  		
  	}
  	
  	function clearsessionAction()
  	{
  		$this->startInit();
  		$_SESSION['visitor']['zip'] = '';
  		$_SESSION['visitor']['city'] = '';
  		$_SESSION['visitor']['state'] = '';
  	}
	
}


?>
