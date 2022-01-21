<?php 
global $connect_mssql_db;
$connect_mssql_db = 1;
require_once('Zend/Controller/Action.php');
require_once('Zend/View.php');
require_once('models/SiteInit.php');
require_once('models/StoreFinder.php');
require_once('models/EventList.php');
require_once('models/EventType.php');
include_once('models/Slider.php');
include_once('models/States.php');
include_once('functions/email_preferences.inc');

class EventsController extends Zend_Controller_Action {

	private $i_site_init;
	private $zip;
	private $state;
	private $city;
	private $status;
	private $z_view;
	private $states;
	private $stores;
	private $all_states; // for choose a store
	private $all_stores; // for choose a store
	private $events;
	private $event_type_ids;
	private $event_types;
	private $show_error_flag;
	private $allevents_count;
	private $allstateevents_count;
	
	private function startInit()
	{       
    	$this->i_site_init = new SiteInit();
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Pragma: no-cache");
        $this->i_site_init->loadInit($v_connect_mssql);
        $this->z_view = new Zend_view(array('scriptPath' => VIEW_PATH));
	}
        
	public function indexAction() {
    	$v_port = $v_port = $_SERVER['SERVER_PORT'];
    	if($v_port == '443') {
      		$this->_redirect('http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
      	return;
    	}
    	$this->startInit();
		$this->i_site_init->loadMain();  
		
		$this->_showEvents(); // call the function that will retrieve store objects, events objects and states

		$v_event_banner = new Banner(); 
		$v_event_banner->getEventBanner(); // call the function that will retrieve banner for events page
		$v_states_abb = new States();
		
		// set properties for view
		$this->z_view->states = $this->states;
		$this->z_view->stores = $this->stores;
		$this->z_view->allstates = $this->states; // since in index action you get all the stores and states
		$this->z_view->allstores = $this->stores;
		$this->z_view->events = $this->events;
		$this->z_view->event_types = $this->event_types;
		$this->z_view->all_states_abb = $v_states_abb->getAllStates(); 
		$this->z_view->show_error_flag = false;
		$this->z_view->where_flag = 'Default';
		$this->z_view->page_name = 'Events';
		$this->z_view->banner_id = $v_event_banner->getBannerId();
		$this->z_view->banner_title = $v_event_banner->getBannerTitle();
		$this->z_view->destination_url = $v_event_banner->getDestinationUrl();
		$this->z_view->image_ref = $v_event_banner->getImageRef();
		
		$this->z_view->obj_slider = new Slider($_SESSION ['recently_viewed_item_list']['order'],6,"review");

		echo $this->z_view->render("events_lp_start.phtml") . $this->z_view->render("events_top.phtml") . $this->z_view->render("store_search.phtml");
		echo $this->z_view->render("choose_store.phtml") . $this->z_view->render("search_success_error.phtml") . $this->z_view->render("events_email_filters.phtml");
		echo $this->z_view->render("show_events.phtml");		
		echo $this->z_view->render("events_bottom.phtml");
		// google remarketing vars 
		$g_data["pageType"]="other";
      	$g_data["prodid"]="";
      	$g_data["totalvalue"]="";
		
		$this->i_site_init->loadFooter($g_data);
  	}

  	// function to get the stores, states, events
  	private function _showEvents()
  	{
  		$v_store_finder = new StoreFinder(); // object of store finder class to retrieve stores
  		$v_store_finder->setZip($this->zip);
  		$v_store_finder->setCity($this->city);
  		$v_store_finder->setState($this->state);
  		
  		$v_store_finder->setRadius(75); // search stores within 100 miles radius
  		
  		if($this->state != '' && $this->zip == '' && $this->city == '')
  		{
  			$v_stores = $v_store_finder->getStoresByState();
  			if(count($v_stores) == 0)
  			{
  				$this->show_error_flag = true; // if no stores found in the specified state
  			}
  		}
  		else
  		{
  			if($this->city !='' || $this->state!='' || $this->zip!='' || $this->status == 'new search') // save the search options in session variables.
  			{
  				$_SESSION['visitor']['zip'] = $this->zip;
  				$_SESSION['visitor']['state'] = $this->state;
  				$_SESSION['visitor']['city'] = $this->city;
  			}
  		
  			$v_stores = $v_store_finder->getStores(); // get the store objects
  			if(count($v_stores)==0)
  			{
  				$this->show_error_flag = true;  // if stores are found set this flag so that you can toggle in html page
  				$v_store_finder->setRadius(''); // if none found then set radius null to find nearest stores by distance
  				$v_store_finder->setResultLimit(5); // to get 5 stores within these miles
  				$v_stores = $v_store_finder->getStores(); // get the store objects
  			}
  		}
  		$v_states = array();
  		$v_stores_ids = array();
  		$v_events = array();
  		$v_event_types = array();
		$v_event_type_ids = array();
		$this->allevents_count = array();
  		foreach($v_stores as $v_store){ // for every store, get the states and events
  			$v_state = $v_store->getStoreState();
  			if(!in_array($v_store->getStoreState(),$v_states))
  			{
  				array_push($v_states, $v_state); // get distinct store state names into an array, to show events by state
  			}
  			$v_store_ids[$v_state][] = $v_store; // push the store objects to use them in views
  			$v_event_list = new EventList(); // object of event list class to retrieve events
  			$v_event_list->setStoreId($v_store->getStoreId()); // set store id to get events that belong to this store
  			if($this->event_type_ids)
  			{
  				$v_event_list->setFilterBy(explode(',',$this->event_type_ids)); // set filters if there are any sent
  			}
  			$events = $v_event_list->getEvents(); // get Event objects
  			foreach($events as $event)
  			{
  				if(!in_array($event->getEventTypeId(),$v_event_type_ids) && $event->getEventType()!='Custom')
  					array_push($v_event_types, new EventType($event->getEventTypeId())); // store the event type objects to show the filter listing
  				array_push($v_event_type_ids,$event->getEventTypeId());
  			}
  			$v_events[$v_state][$v_store->getStoreNum()] = $events; // get events for each store
  			$this->allstateevents_count += count($events); // count the events available in a state for filtering purpose
  			$this->allevents_count[$v_state] += count($events); 
  		}
		
		$this->states = $v_states; // array of state names for showing the events state wise
		$this->stores = $v_store_ids; // array of store objs for showing the store related information
		$this->events = $v_events; // array of event objs for showing the event related information
		$this->event_types = $v_event_types; // array of event types for filters listing
  	}

  	// function to retrieve all the stores for choose a store
  	private function _getAllStores()
  	{
  		$v_store_finder = new StoreFinder(); // object of store finder class to retrieve stores
  		$v_stores = $v_store_finder->getStores(); // get the store objects
  		$v_states = array();
  		$v_stores_ids = array();
  		foreach($v_stores as $v_store){ // for every store, get the states and events
  			$v_state = $v_store->getStoreState();
  			if(!in_array($v_store->getStoreState(),$v_states))
  			{
  				array_push($v_states, $v_state); // get distinct store state names into an array, to show events by state
  			}
  			$v_store_ids[$v_state][] = $v_store; // push the store objects to use them in views
  		}
		
		$this->all_states = $v_states; // array of state names for showing the events state wise
		$this->all_stores = $v_store_ids; // array of store objs for showing the store related information
  	}
  	
  	// function called when city, zip and state are submitted and whole search is refreshed.
  	public function refresheventsAction()
  	{
  		$this->startInit();
  		
  		// set the search criteria 
  		$this->zip = post_or_get('zip');
  		$this->state = post_or_get('state');
  		$this->city = post_or_get('city');
  		$this->status = post_or_get('status'); // will be set when new search link is clicked to clear the session values
  		/*
  		 * If St Louis or St. Louis Should replace to Saint Louis
  		 */
  		if($this->city=="St Louis"||$this->city=="St. Louis") $this->city = "Saint Louis";
  		$this->status = post_or_get('status'); // will be set when new search link is clicked to clear the session values
		$this->_showEvents(); // call the function to get the result set (stores, states, events, filters etc.)
		$this->_getAllStores(); // to get all the states and stores for choose a store purpose
		$v_states_abb = new States();
		
		$this->z_view->states = $this->states;
		$this->z_view->stores = $this->stores;
		$this->z_view->allstates = $this->all_states;
		$this->z_view->allstores = $this->all_stores;
		$this->z_view->events = $this->events;
		$this->z_view->event_types = $this->event_types;
		$this->z_view->all_states_abb = $v_states_abb->getAllStates();
		$this->z_view->show_error_flag = $this->show_error_flag;
		$this->z_view->where_flag = 'Search';
		$this->z_view->page_name = 'Events';
		
		if(count($this->stores)>0)
		{
			// refresh all the areas to reflect the search results
			echo $this->z_view->render("events_top.phtml");
			echo $this->z_view->render("store_search.phtml") . $this->z_view->render("choose_store.phtml");
			echo $this->z_view->render("search_success_error.phtml") . $this->z_view->render("events_email_filters.phtml");
			echo $this->z_view->render("show_events.phtml");
		}
		else
		{
			// where no stores in second search also then tell that the search is problem
			echo 'Error: No Stores';
		}		
  	}
  	
  	// function called when filters are clicked
  	public function refresheventslistAction()
  	{
  		$this->startInit();
  		// set the variables to get same result set with the specific filters
  		$this->zip = post_or_get('zip'); // set zip
  		$this->state = post_or_get('state'); // state
  		$this->city = post_or_get('city'); // city
  		$this->event_type_ids = post_or_get('event_type_ids');
  		
		$this->_showEvents(); // call the function to get the result set
		
		$this->z_view->events = $this->events;
		$this->z_view->stores = $this->stores;
		$this->z_view->states = $this->states;
		$this->z_view->show_error_flag = $this->show_error_flag;
		$this->z_view->filter = 'filter';
		$this->z_view->allstateevents_count = $this->allstateevents_count;
		$this->z_view->allevents_count = $this->allevents_count;
		
		// refresh only events list
		echo $this->z_view->render("show_events.phtml");		
  	}
  	
  	// function to store the email address and zip code
  	public function saveemailAction()
  	{
  		$this->startInit();

  		$v_status = 'FAILED';
        $_POST['category']['all'] = 'true';
  		
  		$save_email = new EmailPreferences(); // create an object of email preferences class
  		$save_email->store(); // call the store function
  		
  		if ($save_email->complete)
        {
        	$v_status = 'SUCCESS';
        }
        echo $v_status;
  	}
  	
  	// function to get directions to the store
  	public function getdirectionsAction()
  	{
  		$this->startInit();
  		$event_id = post_or_get('event_id');
		
  		$event = new Event($event_id);
  		$daddr = $event->getEventAddress();
  		$store = new Store($event->getStoreId(),0); //send the store id and distance as 0
  		//$saddr = $store->getStoreAddress2().','.$store->getStoreAddress3.','.$store->getStoreCity().','.$store->getStoreState().','.$store->getStoreZip();
  		//$url = 'http://maps.google.com/maps?saddr='.str_replace(' ','+',$saddr).'&daddr='.str_replace(' ','+',$daddr);
  		$url = 'http://maps.google.com/maps?daddr='.str_replace(' ','+',$daddr);
  		echo  $url;
  	}
  	
    public function experiancallAction()
    {
        if(ISSET($_REQUEST["email_confirm"]) && !$_REQUEST["email_confirm"])
        {

            $email_address = post_or_get('email');
            // Get cURL resource
            $curl = curl_init();
            // Set some options - we are passing in a useragent too here
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_URL => 'http://forms.expemail.golftown.com/ats/post.aspx',
                CURLOPT_USERAGENT => 'Golfsmith',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => array(
                  		cr => '538',
                  		fm => '30',
                  		s_email => $email_address
                )
            ));
            // Send the request & save response to $resp
            if(!curl_exec($curl)){
                $v_status = 'ERROR';
                die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
            }
            $v_status = 'SUCCESS';
            echo "<br/>\nEmail: " . $email_address . "<br/>\n";
            // Close request to clear up some resources
            curl_close($curl);
            echo $v_status;
        }
        else
        {
            header("HTTP/1.0 404 Not Found");
        }
    }
}
?>
