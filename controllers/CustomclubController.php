<?php
require_once ('Zend/Controller/Action.php');
require_once ('models/SiteInit.php');
require_once ('models/CustomClub.php');

class CustomclubController extends Zend_Controller_Action {
	
	/**************************
	 * Function: __call
	 * Purpose: Default constructor for the CustomClub controller
	 * Called From::
	 * 	web: /customclub
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function __call($p_method, $p_args) {
		$this->indexAction ();
	}
	
	/**************************
	 * Function: indexAction
	 * Purpose: default action calls main
	 * Called From::
	 *  Contoller: this
	 *  Function: __call()
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function indexAction() {
		$this->mainAction ();
	}
	
	/**************************
	 * Function: showscreenAction
	 * Purpose: assist the user in selecting any previous step in the custom club process
	 * 	The idea is to take a parameter of the step (1->6), and the session_id
	 * 	then go grab what would be the browser parameters, and reconstruct what 
	 * 	would have been and then simulate that request by passing control to the correct action
	 * Called From::
	 * 	web: Custom Club Step Navigaton
	 * Comment Date: 3/19/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function showscreenAction() {
		$i_request = $this->getRequest ();
		$v_step = strip_tags ( $i_request->getParam ( 'step' ) );
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		
		// Get all parameter data from the database from $v_session_id;
		$cc_obj = new CustomClub ();
		$cc_obj->_session_id = $v_session_id;
		$va_fitting = array();
		$va_fitting = $cc_obj->GetMyFitting();
		$cc_obj->_va_fitting = $va_fitting; // save these fittings so that the CustomClub object 
											// may gain access to this incase someone goes 
											// back, they can quickly get their values again
		if (is_array($va_fitting) && !empty($va_fitting)) {
			$va_clubs = array();
			$va_clubs = $cc_obj->GetMyClubs();
			$cc_obj->_my_clubs = $va_clubs; // same for fitting, but any and all clubs returned
			
			switch($v_step) {
				case "1": // restart club selection
					$cc_obj->DisplayClubSelection ( $va_fitting['smart_fit_flag'], $va_fitting['club_category'] );
					break;
				case "2":
					$cc_obj->DisplayPlayerProfile ();
					break;
				case "3":
					$cc_obj->DisplayHandMeasurement ();
					break;
				case "4":
					$cc_obj->DisplaySwingTrajectory ();
					break;
				case "5":
					$cc_obj->DisplayCustomize ($v_session_id);
					break;
				case "6":
					$cc_obj->DisplayReview ($v_session_id);
					break;
				default: // step 1 stuff
					$cc_obj->DisplayClubSelection ( $va_fitting['smart_fit_flag'], $va_fitting['club_category'] );
					break;
			}
		} else { // you came back after a loooooooooong break. Start over pls
			
			$cc_obj->error_message = "<p>According to our records, you don't have a fitting session started or the one you had has expired. Please <a href=\"/customclub\">start over</a>.</p>";
			echo $cc_obj->ShowError();
		}
		
	}
	
	/**************************
	 * Function: mainAction
	 * Purpose: calls out to 3 functions that help display the custom club making process
	 * Called From::
	 *  Contoller: this
	 *  Function: indexAction()
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function mainAction() {
		
		$cc_obj = new CustomClub ();
		$cc_obj->DisplayHeader ();
		$cc_obj->DisplayMain ();
		$cc_obj->DisplayFooter ();
	}
	
	/**************************
	 * Function: startfittingsessionAction
	 * Purpose: sends parameters to save to the SQL SERVER
	 * Called From::
	 *  web: /customclub/clubselectionsave ~ /javascript/custom_club.js
	 *  Function: continueToClubSelection() 
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function startfittingsessionAction() {
		
		$cc_obj = new CustomClub ();
		
		$i_request = $this->getRequest ();
		$v_cf_method = strip_tags ( $i_request->getParam ( 'cf_method' ) );
		$v_cust_category = strip_tags ( $i_request->getParam ( 'cust_category' ) );
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_sessionid = $v_session_id; 
		
		$cc_obj->StartFittingSession ( $v_cf_method, $v_cust_category );		
	}
	
	/**************************
	 * Function: clubselectionAction
	 * Purpose: Assigns customer supplied variables from selection method and 
	 *   club category (step 1) and then calls out to display the club selection 
	 *   process based on those parameters
	 * Called From::
	 *  web: /customclub/clubselection ~ /javascript/custom_club.js
	 *  Function: continueToClubSelection() 
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function clubselectionAction() {
		
		$cc_obj = new CustomClub ();
		
		$i_request = $this->getRequest ();
		$v_cf_method = strip_tags ( $i_request->getParam ( 'cf_method' ) );
		$v_cust_category = strip_tags ( $i_request->getParam ( 'cust_category' ) );
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_session_id = $v_session_id;
		$va_fitting = array();
		$va_fitting = $cc_obj->GetMyFitting();
		$cc_obj->_va_fitting = $va_fitting; // save these fittings so that the CustomClub object 
											// may gain access to this incase someone goes 
											// back, they can quickly get their values again
		$va_clubs = array();
		$va_clubs = $cc_obj->GetMyClubs();
		$cc_obj->_my_clubs = $va_clubs; // same for fitting, but any and all clubs returned
		
		
		
		$cc_obj->DisplayClubSelection ( $v_cf_method, $v_cust_category );
	
	}
	
	/**************************
	 * Function: showmodelimageAction
	 * Purpose: starts request to retrieve model image
	 * Called From::
	 *  web: /customclub/displaymodelimage ~ /javascript/custom_club.js
	 *  Function: showModelImage() 
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function showmodelimageAction() {
		$cc_obj = new CustomClub ();
		
		$i_request = $this->getRequest ();
		$v_inventory_item_id = strip_tags ( $i_request->getParam ( 'inventory_item_id' ) );
		
		$cc_obj->DisplayProductImage ( $v_inventory_item_id );
	}
	
	/**************************
	 * Function: sessiontestAction
	 * Purpose: calls out to test a fitting sessoin has started
	 * Called From::
	 *  web: /customclub/clubselectionsave ~ /javascript/custom_club.js
	 *  Function: continueToClubSelection() 
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function sessiontestAction() {
		
		$cc_obj = new CustomClub ();
		$i_request = $this->getRequest ();
		$v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
		$cc_obj->TestFittingSession ( $v_session_id );
			
	}
	
	/**************************
	 * Function: outofstockAction
	 * Purpose: gets called if an item was attempting to go into the cart but a last minute check claimed 0 quantity; happens rarely
	 * Called From::
	 *  web: /customclub/addtocartAction ~ /javascript/custom_club.js
	 *  Function: AddToCart() 
	 * Comment Date: 6/23/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function outofstockAction() {
		
		$cc_obj = new CustomClub ();
		$i_request = $this->getRequest ();
		$v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
		$v_return_status = strip_tags ( $i_request->getParam ( 'return_status' ) );
		$cc_obj->OutOfStock ( $v_session_id,$v_return_status );
			
	}
	
	/**************************
	 * Function: saveclubselectionsAction
	 * Purpose: sends parameters to save to the SQL SERVER
	 * Called From::
	 *  web: /customclub/clubselectionsave ~ /javascript/custom_club.js
	 *  Function: continueToClubSelection() 
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
		
	public function saveclubselectionsAction() {
		
		$cc_obj = new CustomClub ();
		
		$i_request = $this->getRequest ();
		
		$v_combohybrid_menu_val = strip_tags ( $i_request->getParam ( 'combohybrid_menu_val' ) );
		$v_combohybrid_modl_val = strip_tags ( $i_request->getParam ( 'combohybrid_modl_val' ) );
		$v_driver_menu_val = strip_tags ( $i_request->getParam ( 'driver_menu_val' ) );
		$v_driver_modl_val = strip_tags ( $i_request->getParam ( 'driver_modl_val' ) );
		$v_fairway_menu_val = strip_tags ( $i_request->getParam ( 'fairway_menu_val' ) );
		$v_fairway_modl_val = strip_tags ( $i_request->getParam ( 'fairway_modl_val' ) );
		$v_hybrids_menu_val = strip_tags ( $i_request->getParam ( 'hybrids_menu_val' ) );
		$v_hybrids_modl_val = strip_tags ( $i_request->getParam ( 'hybrids_modl_val' ) );
		$v_irons_menu_val = strip_tags ( $i_request->getParam ( 'irons_menu_val' ) );
		$v_irons_modl_val = strip_tags ( $i_request->getParam ( 'irons_modl_val' ) );
		$v_wedges_menu_val = strip_tags ( $i_request->getParam ( 'wedges_menu_val' ) );
		$v_wedges_modl_val = strip_tags ( $i_request->getParam ( 'wedges_modl_val' ) );
		$v_putter_menu_val = strip_tags ( $i_request->getParam ( 'putter_menu_val' ) );
		$v_putter_modl_val = strip_tags ( $i_request->getParam ( 'putter_modl_val' ) );
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_sessionid = $v_session_id; 
		
		$cc_obj->SaveClubSelection ( $v_combohybrid_menu_val, $v_combohybrid_modl_val,
			$v_driver_menu_val, $v_driver_modl_val, 
			$v_fairway_menu_val, $v_fairway_modl_val, $v_hybrids_menu_val,
			$v_hybrids_modl_val, $v_irons_menu_val, $v_irons_modl_val,
			$v_wedges_menu_val, $v_wedges_modl_val,
			$v_putter_menu_val, $v_putter_modl_val );
		
		
	}
	
	/**************************
	 * Function: saveplayerprofileAction
	 * Purpose: sends parameters to save to the SQL SERVER
	 * Called From::
	 *  web: /customclub/saveplayerprofile ~ /javascript/custom_club.js
	 *  Function: continueToHandMeasurement() 
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */	
		
	public function saveplayerprofileAction() {
		
		$cc_obj = new CustomClub ();
		
		$i_request = $this->getRequest ();
		
		$v_cust_height = strip_tags ( $i_request->getParam ( 'p_cust_height' ) );
		$v_wrist_to_floor = strip_tags ( $i_request->getParam ( 'p_wrist_to_floor' ) );
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_sessionid = $v_session_id; 
		
		$cc_obj->SavePlayerProfile ( $v_cust_height, $v_wrist_to_floor );
		
		
	}
	
	/*********************************
	 * Function: getmodelsAction
	 * Purpose: collect customer supplied club category, club type, and manufacturer
	 * 	then pass those values on to the Custom Club Object function getModels
	 * Called From::
	 *  web: /customclub/getmodels ~ /javascript/custom_club.js
	 *  Function: getModels() 
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */		
	
	public function getmodelsAction() {
		$cc_obj = new CustomClub ();
		
		$i_request = $this->getRequest ();
		$v_cust_category = strip_tags ( $i_request->getParam ( 'cust_category' ) );
		$v_club_type = strip_tags ( $i_request->getParam ( 'club_type' ) );
		$v_manufacturer = strip_tags ( $i_request->getParam ( 'manufacturer' ) );
		
		$cc_obj->getModels ( $v_cust_category, $v_club_type, $v_manufacturer );
	
	}
	
	/*********************************
	 * Function: playerprofileAction
	 * Purpose: calls an instance of CustomClub->DisplayPlayerProfile
	 * Called From::
	 *  web: /customclub/playerprofile ~ /javascript/custom_club.js
	 *  Function: continueToPlayerProfile() 
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */		
	
	public function playerprofileAction() {
		
		$cc_obj = new CustomClub ();
		$i_request = $this->getRequest ();
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_session_id = $v_session_id;
		$va_fitting = array();
		$va_fitting = $cc_obj->GetMyFitting();
		$cc_obj->_va_fitting = $va_fitting; // save these fittings so that the CustomClub object 
											// may gain access to this incase someone goes 
											// back, they can quickly get their values again
		$cc_obj->DisplayPlayerProfile ();
	
	}
	
	/**************************
	 * Function: savehandmeasurementAction
	 * Purpose: sends parameters to save to the SQL SERVER
	 * Called From::
	 *  web: /customclub/savehandmeasurement ~ /javascript/custom_club.js
	 *  Function: continueToSwingTrajectory() 
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */	
		
	public function savehandmeasurementAction() {
		
		$cc_obj = new CustomClub ();
		
		$i_request = $this->getRequest ();
		
		$v_hand_size = strip_tags ( $i_request->getParam ( 'p_hand_size' ) );
		$v_finger_size = strip_tags ( $i_request->getParam ( 'p_finger_size' ) );
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_sessionid = $v_session_id; 
		
		$cc_obj->SaveHandMeasurement ( $v_hand_size, $v_finger_size );
		
	}
	
	/*********************************
	 * Function: handmeasurementAction
	 * Purpose: calls an instance of CustomClub->DisplayHandMeasurement
	 * Called From::
	 *  web: /customclub/handmeasurement ~ /javascript/custom_club.js
	 *  Function: continueToHandMeasurement() 
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */		
	
	public function handmeasurementAction() {
		
		$cc_obj = new CustomClub ();
		$i_request = $this->getRequest ();
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_session_id = $v_session_id;
		$va_fitting = array();
		$va_fitting = $cc_obj->GetMyFitting();
		$cc_obj->_va_fitting = $va_fitting; // save these fittings so that the CustomClub object 
											// may gain access to this incase someone goes 
											// back, they can quickly get their values again
		$cc_obj->DisplayHandMeasurement ();
	
	}
	
	/*********************************
	 * Function: swingtrajectoryAction
	 * Purpose: calls an instance of CustomClub->...
	 * Called From::
	 *  web: /customclub/... ~ /javascript/custom_club.js
	 *  Function: ...() 
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */		
	
	public function swingtrajectoryAction() {
	
		$cc_obj = new CustomClub ();
		$i_request = $this->getRequest ();
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_session_id = $v_session_id;
		$va_fitting = array();
		$va_fitting = $cc_obj->GetMyFitting();
		$cc_obj->_va_fitting = $va_fitting; // save these fittings so that the CustomClub object 
											// may gain access to this incase someone goes 
											// back, they can quickly get their values again
		$cc_obj->DisplaySwingTrajectory ();
		
	}
	
	/**************************
	 * Function: saveswingtrajectoryAction
	 * Purpose: sends parameters to save to the SQL SERVER
	 * Called From::
	 *  web: /customclub/saveswingtrajectory ~ /javascript/custom_club.js
	 *  Function: continueToCustomize() 
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */	
		
	public function saveswingtrajectoryAction() {
		
		$cc_obj = new CustomClub ();
		
		$i_request = $this->getRequest ();
		
		$v_driver_speed = strip_tags ( $i_request->getParam ( 'p_driver_speed' ) );
		$v_iron_distance = strip_tags ( $i_request->getParam ( 'p_iron_distance' ) );
		$v_trajectory = strip_tags ( $i_request->getParam ( 'p_trajectory' ) );
		$v_tempo = strip_tags ( $i_request->getParam ( 'p_tempo' ) );
		$v_target_swing = strip_tags ( $i_request->getParam ( 'p_target_swing' ) );
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_sessionid = $v_session_id;
		$cc_obj->SaveSwingTrajectory ( $v_driver_speed, $v_iron_distance, $v_trajectory,
									   $v_tempo, $v_target_swing );
		$cc_obj->error_message = "According to our records, you don't have a fitting session started or the one you had has expired. Please <a href=\"/customclub\">start over</p>.";
		echo $cc_obj->ShowError();
	}
	
	/*********************************
	 * Function: customizeAction
	 * Purpose: calls an instance of CustomClub->DisplayCustomize
	 * Called From::
	 *  web: /customclub/customize ~ /javascript/custom_club.js
	 *  Function: continueToCustomize() 
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */		
	
	public function customizeAction() {
		
		$cc_obj = new CustomClub ();
		$i_request = $this->getRequest ();
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_session_id = $v_session_id;
		$va_fitting = array();
		$va_fitting = $cc_obj->GetMyFitting();
		$cc_obj->_va_fitting = $va_fitting; // save these fittings so that the CustomClub object 
											// may gain access to this incase someone goes 
											// back, they can quickly get their values again
		$cc_obj->DisplayCustomize ($v_session_id);
	}
	
	/********************************
	 * Function: shaftflexoptionsAction
	 * Purpose: Retrieves flex options for said fitting session, trajectory, model, and shaft
	 * Called From::
	 * 	web: /customclub/shaftflexoptions ~ javascript/custom_club.js
	 * 	Function: shaft_changed()
	 * Comment Date: 3/25/2010
	 * Comment Author: Robbie Smith 
	 */
	
	public function shaftflexoptionsAction() {
	    
	    global $_SESSION;
		$i_request = $this->getRequest ();
		
		$v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
		$v_trajectory = strip_tags ( $i_request->getParam ( 'p_trajectory' ) );
		$v_combo_iron = strip_tags ( $i_request->getParam ( 'p_combo_iron' ) );
		$v_combo_hyrbid = strip_tags ( $i_request->getParam ( 'p_combo_hybrid' ) );
		$v_model = strip_tags ( $i_request->getParam ( 'p_model' ) );
		$v_club_type_value = strip_tags ( $i_request->getParam ( 'p_club_type_value' ) );
		$v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );


		$cc_club_obj = new CustomClub();

		//This is to get cost price
		$_SESSION["selectedShaftId"] = $v_club_type_value;
		

		$va_flex_options = $cc_club_obj->GetFlexOptions($v_session_id, $v_trajectory, $v_model, $v_club_type_value,$v_dexterity);
		$options = "";
		if (is_array($va_flex_options) && !empty($va_flex_options)) { // list the flex options given by the shaft selection
			if ($v_combo_iron)
				echo "Irons: ";
			if ($v_combo_hyrbid) {
				echo "Hybrids: ";
				$appendation = "_combo";
			}
			echo "<select name=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\"  id=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\" class=\"formbox\" onchange=\"findOldestDate();\">";
			$availability_dates = "";
			$flex_count = 0;
			foreach($va_flex_options as $value) {
				echo "<option value=\"" . $value['flex'] . "\">" . $value['flex'] . "</option>";
				$availability_dates .= "<input type=\"hidden\" name=\"" . strtolower($v_club_type_value) . "_shaft_flex_availability_date_" . $flex_count . "\" id=\"" . strtolower($v_club_type_value) . "_shaft_flex_availability_date_" . $flex_count++ . "\" value=\"" . strtotime($value['availability_date']) . "\" />";
			}
			echo "</select>";
			echo "<input type=\"hidden\" name=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" id=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" value=\"" . sizeof($va_flex_options) . "\" />";
			echo $availability_dates;

		} else { // no shaft flex options available
			if ($v_combo_iron)
				echo "Irons: ";
			if ($v_combo_hyrbid) {
				echo "Hybrids: ";
				$appendation = "_combo";
			}
			echo "<select name=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\"  id=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\"   class=\"formbox\" >";
				echo "<option value=\"\">No shaft flex options</option>";
			echo "</select>";
			echo "<input type=\"hidden\" name=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" id=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" value=\"0\" />";
		}
		
		
	}
	
	
	
	public function shaftflexoptionscustAction() {
	    $i_request = $this->getRequest ();
	
	    $v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
	    $v_trajectory = strip_tags ( $i_request->getParam ( 'p_trajectory' ) );
	    $v_combo_iron = strip_tags ( $i_request->getParam ( 'p_combo_iron' ) );
	    $v_combo_hyrbid = strip_tags ( $i_request->getParam ( 'p_combo_hybrid' ) );
	    $v_model = strip_tags ( $i_request->getParam ( 'p_model' ) );
	    $v_club_type_value = strip_tags ( $i_request->getParam ( 'p_club_type_value' ) );
	    $v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
	
	
	    $cc_club_obj = new CustomClub();
	    $va_flex_options = $cc_club_obj->GetFlexOptions($v_session_id, $v_trajectory, $v_model, $v_club_type_value,$v_dexterity);
	    $options = "";
	    if (is_array($va_flex_options) && !empty($va_flex_options)) { // list the flex options given by the shaft selection
	        if ($v_combo_iron){
	            //echo "Irons: ";
	           }
	            if ($v_combo_hyrbid) {
	                //echo "Hybrids: ";
	                $appendation = "_combo";
	            }
	        echo "<select name=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\"  id=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\" class=\"formbox\" onchange=\"findOldestDate();\">";
	        $availability_dates = "";
	        $flex_count = 0;
	        foreach($va_flex_options as $value) {
	            echo "<option value=\"" . $value['flex'] . "\">" . $value['flex'] . "</option>";
	            $availability_dates .= "<input type=\"hidden\" name=\"" . strtolower($v_club_type_value) . "_shaft_flex_availability_date_" . $flex_count . "\" id=\"" . strtolower($v_club_type_value) . "_shaft_flex_availability_date_" . $flex_count++ . "\" value=\"" . strtotime($value['availability_date']) . "\" />";
	        }
	        echo "</select>";
	        echo "<input type=\"hidden\" name=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" id=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" value=\"" . sizeof($va_flex_options) . "\" />";
	        echo $availability_dates;
	
	    } else { // no shaft flex options available
	        if ($v_combo_iron){
                //echo "Irons: ";
            }
            if ($v_combo_hyrbid) {
                //echo "Hybrids: ";
                $appendation = "_combo";
            }
	        echo "<select name=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\"  id=\"" . strtolower($v_club_type_value) . "_shaft_flex$appendation\"   class=\"formbox\" >";
	        echo "<option value=\"\">No shaft flex options</option>";
	        echo "</select>";
	        echo "<input type=\"hidden\" name=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" id=\"" . strtolower($v_club_type_value) . "_shaft_flex_count\" value=\"0\" />";
	    }
	
	
	}
	
	/********************************
	 * Function: lieangleoptionsAction
	 * Purpose: Retrieves lie angle options for said fitting session, model, and dexterity
	 * Called From::
	 * 	web: /customclub/changeDexterity ~ javascript/custom_club.js
	 * 	Function: shaft_changed()
	 * Comment Date: 3/25/2010
	 * Comment Author: Robbie Smith 
	 */
	
	public function lieangleoptionsAction() {
		$i_request = $this->getRequest ();
		
		$v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
		$v_model = strip_tags ( $i_request->getParam ( 'p_inventory_item_id' ) );
		$v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
		
		$cc_club_obj = new CustomClub();
		$va_lie_angle_options = $cc_club_obj->GetLieAngleOptions($v_session_id, $v_model, $v_dexterity);
		
		/****************
		 * 		inventory_item_id,
				hand,
				min_lie_angle,
				max_lie_angle,
				lie_increment,
				standard_lie_angle
		 */
		$options = "";
		
		if (is_array($va_lie_angle_options) && !empty($va_lie_angle_options)) {
			if ($va_lie_angle_options[0]["standard_lie_angle"] != "") {
				$v_standard_lie_angle = $va_lie_angle_options[0]["standard_lie_angle"];
			} else { // it's absent so we need a bit of math to figure out the "standard" 
				$v_standard_lie_angle = 0;
			}
			for($x=$va_lie_angle_options[0]["min_lie_angle"];$x<=$va_lie_angle_options[0]["max_lie_angle"];$x+=$va_lie_angle_options[0]["lie_increment"]) {
				if ($x<0) {
					$options .= (number_format($v_standard_lie_angle,2)+$x) . " Flat (" . number_format($x,2) . ")" . "|";
				} elseif ($x == 0 || number_format($x,1) == 0.0) {
					$options .= number_format($v_standard_lie_angle,2) . " Standard (" . number_format($x,2) . ")" . "|";
				} else {
					$options .= (number_format($v_standard_lie_angle,2)+$x) . " Upright (+" . number_format($x,2) . ")" . "|";
				}
			}	
			$options = rtrim($options,"|");	
		} else {
			$options = "Standard";
		}
		
		echo $options;
	}
	
	/********************************
	 * Function: gripoptionsAction
	 * Purpose: Retrieves grip model options for said fitting session, model, and dexterity
	 * Called From::
	 * 	web: /customclub/changeDexterity ~ javascript/custom_club.js
	 * 	Function: shaft_changed()
	 * Comment Date: 4/26/2010
	 * Comment Author: Robbie Smith 
	 */
	
	public function gripoptionsAction() {
		$i_request = $this->getRequest ();
		
		$v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
		$v_model = strip_tags ( $i_request->getParam ( 'p_inventory_item_id' ) );
		$v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
		
		$cc_club_obj = new CustomClub();
		$va_grip_options = $cc_club_obj->GetGrips($v_session_id, $v_model, $v_dexterity);
		
		$options = "";
		// redraw the grip model select feature...
		if (is_array($va_grip_options) && !empty($va_grip_options)) {
			$options = "
				<select name=\"" . strtolower(str_replace("/","",$va_grip_options[0]['club_type'])) . "_grip_id\" 
				  id=\"" . strtolower(str_replace("/","",$va_grip_options[0]['club_type'])) . "_grip_id\" 
				  onChange=\"JavaScript: grip_changed('ps','" . $v_model . "','" . strtolower($va_grip_options[0]['club_type']) . "',this.value);return total_amount(); findOldestDate();\"  
				  class=\"formbox\">
				<option value=\"0\">Select Grip</option>
			";
			$v_grip_hidden_options = "
				<input type=\"hidden\" 
					name=\"" . strtolower(str_replace("/","",$va_grip_options[0]['club_type'])) . "_grip_id_0\" 
					id=\"" . strtolower(str_replace("/","",$va_grip_options[0]['club_type'])) . "_grip_id_0\" 
					value=\"0\" />";
			$availability_dates = "";
			$grip_count = 0;
			foreach($va_grip_options as $key => $value) {
				$options .= "<option value=\"" . $value['grip_id'] . "\">" . $value['description'] . " - " . format_currency($value['retail_price']) . "</option>";
				$v_grip_hidden_options .= '
					<input type="hidden" name="' . strtolower(str_replace("/","",$value['club_type'])) . '_grip_id_' . $value['grip_id'] . '" 
						   id="' . strtolower(str_replace("/","",$value['club_type'])) . '_grip_id_' . $value['grip_id'] . '" value="' . $value['retail_price'] . '" />
					<input type="hidden" name="' . strtolower(str_replace("/","",$value['club_type'])) . '_grip_name_id_' . $value['grip_id'] . '" 
						   id="' . strtolower(str_replace("/","",$value['club_type'])) . '_grip_name_id_' . $value['grip_id'] . '" value="' . $value['description'] . '" />
				';	
				$availability_dates .= "<input type=\"hidden\" name=\"" . $value['grip_id'] . "_grip_availability_date_" . $grip_count . "\" id=\"" . $value['grip_id'] . "_grip_availability_date_" . $grip_count++ . "\" value=\"" . strtotime($value['availability_date']) . "\" />";
			
			}
			$options .= "</select>";
			echo "<input type=\"hidden\" name=\"" . $value['grip_id'] . "_grip_count\" id=\"" . $value['grip_id'] . "_grip_count\" value=\"" . sizeof($va_grip_options) . "\" />";
			echo $availability_dates;
			echo $v_grip_hidden_options; 
		} else {
			$options = "<select class=\"formbox\"><option>Not Available</option></select>";
		}
		
			$options .="
				 &nbsp; &nbsp; <a href=\"javascript:openBrWindow('/display_page.php?page_num=ccm_glossary&amp;hdr=N#Grip','',features);\">Help</a>";
		
		echo $options;
	}
	
	/********************************
	 * Function: clubheadoptionsAction
	 * Purpose: Retrieves club head options for said fitting session, model, and dexterity
	 * Called From::
	 * 	web: /customclub/changeDexterity ~ javascript/custom_club.js
	 * 	Function: shaft_changed()
	 * Comment: This function is called for each club type selected in Step 1.
	 * Comment Date: 3/25/2010
	 * Comment Author: Robbie Smith 
	 */
	
	public function clubheadoptionsAction() {
		$i_request = $this->getRequest ();
		
		$v_model = strip_tags ( $i_request->getParam ( 'p_inventory_item_id' ) );
		$v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
		$v_club_type = strip_tags( $i_request->getParam('p_club_type'));
		
		$cc_club_obj = new CustomClub();
		$va_club_head_options = $cc_club_obj->getClubHeadOptions($v_model, $v_dexterity);
		#print_r($va_club_head_options);
		// as a backup find out the normal club retail price for these club models
		$v_retail_price = $cc_club_obj->getClubModelPrice($v_model);
		
		//echo $va_club_head_options;
		if (is_array($va_club_head_options) && !empty($va_club_head_options)) {
			$va_clubheads = array();
			$va_tempclubheads = array();
			$va_sets = array();
			$va_clubset = array();
			$va_tempsets = array();
			//echo "<pre>";
			//print_r($va_club_head_options);
			//echo "</pre>";
			
			foreach($va_club_head_options as $key => $value) {
				// loop through the record set and find the dist
				$va_sets[$value['set_id']][] = $value['description'];
				$va_clubheads[] = $value['description'];
				
				// the combined list over ALL club possibilities
				$va_clubset[$value['head_id']]['head_id'] 			= $value['head_id'];
				$va_clubset[$value['head_id']]['club_type'] 		= strtolower(str_replace("/","",$value['club_type']));
				$va_clubset[$value['head_id']]['ind_retail'] 		= $value['ind_retail'];
				$va_clubset[$value['head_id']]['set_retail'] 		= $value['set_retail'];
				$va_clubset[$value['head_id']]['description'] 		= $value['description'];
				$va_clubset[$value['head_id']]['hybrid_flag'] 		= $value['hybrid_flag'];
				$va_clubset[$value['head_id']]['availability_date']	= $value['availability_date'];
			}
			$va_tempclubheads = array_unique($va_clubheads);
			#echo "<pre>";
			#print_r($va_sets);
			#echo "</pre>";
			
			$va_clubheads = array(); // reset
			$va_clubheads = array_values($va_tempclubheads);
			#print_r($va_club_head_options);
			foreach($va_sets as $set_id => $list) {
				$v_tempsetlist = "";
				$list = array_unique($list);
				foreach($list as $index => $head_id) {
					$v_tempsetlist .= $head_id . ",";
				}
				$v_tempsetlist = rtrim($v_tempsetlist,",");
				$va_tempsets[$set_id] = $v_tempsetlist;
			}
			//print_r($va_tempsets);
			$va_tempsets = array_unique($va_tempsets);
			#print_r($va_tempsets);
			
		}
		
		// single out the sets if there are more than one set
		$v_club_set_count = 0;
		if (is_array($va_tempsets) && !empty($va_tempsets)) {
			if (sizeof($va_tempsets)>0 and (strtolower($v_club_type) == "irons" || strtolower($v_club_type) == "combo/hybrid")) {
				$va_tempsets = array_unique($va_tempsets);
				echo "<strong>Possible Sets:</strong><br>";
				$listcount = 0;
				
				foreach($va_tempsets as $selection => $list) {
					echo "<input type=\"hidden\" name=\"" . strtolower(str_replace("/","",$v_club_type)) . "_" . $listcount . "_set\" id=\"" . strtolower(str_replace("/","",$v_club_type)) . "_" . $listcount . "_set\" value=\"" . $list . "\" />\r\n";
					echo (++$listcount) . ") " . $list . "<BR>";
				}
				$v_club_set_count = sizeof ($va_tempsets);
			}
		}
		echo "<input type=\"hidden\" name=\"" . strtolower(str_replace("/","",$v_club_type)) . "_set_count\" id=\"" . strtolower(str_replace("/","",$v_club_type)) . "_set_count\" value=\"" . $v_club_set_count . "\" />";
		echo "<BR>";
		#echo "<pre>";
		#print_r($va_clubset);
		#echo "</pre>";
		$options = "";
		$index = 0;
		if (is_array($va_clubset) && !empty($va_clubset)) {
			
			$options .=  "<table cellpadding=\"2\" cellspacing=\"0\" border=\"0\">";
			$options .= "
					<tr>
						<th style=\"padding:2px;\" colspan=\"2\"><strong>Club</strong></th>
						<th style=\"padding:2px; text-align:right;\"><strong>Ind. Price</strong></th>
						<th style=\"padding:2px; text-align:right;\"><strong>Set Price*</strong></th>
					</tr>
			";
			foreach($va_clubset as $head_id => $value) {
				// attempt to override and detect if the set relationship exists.
				$v_setprice = str_replace("$0.00","N/A",format_currency($value['set_retail']));
			
			
				$options .= "
					<tr valign=\"top\">
						<td style=\"padding:2px;\">
							<input 
								type=\"checkbox\" 
								name=\"" . $value["club_type"] . "_club_head_id_" . $index . "\" 
								id=\"" . $value["club_type"] . "_club_head_id_" . $index . "\" 
								value=\"" . $value["head_id"] . "\"
								onKeyUp=\"javascript: total_amount(); findOldestDate();\"
								onClick=\"javascript: total_amount(); findOldestDate();\"
								
							\">
							<input type=\"hidden\" name=\"" . str_replace("/","",$value["club_type"]) . "_club_head_description_" . $index . "\" id=\"" . $value["club_type"] . "_club_head_description_" . $index . "\" value=\"" . $value['description'] . "\" />
							<input type=\"hidden\" name=\"" . str_replace("/","",$value["club_type"]) . "_club_head_hybrid_flag_" . $index . "\" id=\"" . $value["club_type"] . "_club_head_hybrid_flag_" . $index . "\" value=\"" . $value['hybrid_flag'] . "\" />
							<input type=\"hidden\" name=\"" . str_replace("/","",$value["club_type"]) . "_club_head_set_price_" . $index . "\" id=\"" . $value["club_type"] . "_club_head_set_price_" . $index . "\" value=\"" . $value['set_retail'] . "\" />
							<input type=\"hidden\" name=\"" . str_replace("/","",$value["club_type"]) . "_club_head_ind_price_" . $index . "\" id=\"" . $value["club_type"] . "_club_head_ind_price_" . $index . "\" value=\"" . $value['ind_retail'] . "\" />
							<input type=\"hidden\" name=\"" . str_replace("/","",$value["club_type"]) . "_club_head_name_" . $index . "\" id=\"" . $value["club_type"] . "_club_head_name_" . $index . "\" value=\"" . $value['description'] . "\" />
							<input type=\"hidden\" name=\"" . str_replace("/","",$value["club_type"]) . "_availability_date_" . $index . "\" id=\"" . $value["club_type"] . "_availability_date_" . $index . "\" value=\"" . strtotime($value['availability_date']) . "\" />
						</td>
						<td style=\"padding:2px;\">" . $value["description"] . "</td>
						<td style=\"padding:2px; text-align:right;\">" . format_currency($value['ind_retail']) . "</td>
						<td style=\"padding:2px; text-align:right;\" >$v_setprice</td>
					</tr>
				";
				$index++;
				$v_club_type = $value["club_type"];
			}
			$options .= "</table>
			<input type=\"hidden\" name=\"" . $v_club_type . "_club_head_index\" id=\"" . $v_club_type . "_club_head_index\" value=\"$index\" /> <br>
			<small style=\"$v_setprice\">*Set price applies to combo/hybrid iron sets only and is applied when the individual clubs<br> 
			selected complete a qualifying set composition. Qualifying set compositions are determined <br>  
			by the manufacturer and vary by make and model.</small>";	
		} else {
			$options = "
				<input type=\"hidden\" name=\"" . strtolower($v_club_type) . "_club_head_index\" id=\"" . strtolower($v_club_type) . "_club_head_index\" value=\"$index\" />
				<input type=\"hidden\" name=\"" . strtolower($v_club_type) . "_club_head_ind_price_" . $index . "\" id=\"" . strtolower($v_club_type) . "_club_head_ind_price_" . $index . "\" value=\"" . $v_retail_price . "\" />
				<input type=\"hidden\" name=\"" . strtolower($v_club_type) . "_club_head_name_" . $index . "\" id=\"" . strtolower($v_club_type) . "_club_head_name_" . $index . "\" value=\"\" />
				Club Heads aren't available for this hand selection yet.";
		}
		
		echo $options;
	}
	
	/********************************
	 * Function: gripsizesAction
	 * Purpose: Retrieves lie angle options for said fitting session, model, and dexterity
	 * Called From::
	 * 	web: /customclub/shaftflexoptions ~ javascript/custom_club.js
	 * 	Function: shaft_changed()
	 * Comment Date: 3/29/2010
	 * Comment Author: Robbie Smith 
	 */
	
	public function gripsizesAction() {
		$i_request = $this->getRequest ();
		
		$v_session_id = strip_tags ( $i_request->getParam ( 'p_session_id' ) );
		$v_model = strip_tags ( $i_request->getParam ( 'p_inventory_item_id' ) );
		$v_grip_id = strip_tags ( $i_request->getParam ( 'p_grip_id' ) );
		$v_dexterity = strip_tags ( $i_request->getParam ( 'p_dexterity' ) );
		
		$cc_club_obj = new CustomClub();
		
		
		$_SESSION["selectedGripId"] = $v_grip_id;
		
		$va_gripsizes = $cc_club_obj->GetAvailableGripSizes($v_session_id, $v_model, $v_grip_id, $v_dexterity);
		
		/****************
		 * 		
	  			modelgrips.grip_id,
				clubs.club_id, 
				clubs.fitting_id, 
				clubs.club_type, 
				clubs.manufacturer, 
				clubs.model, 
				grips.description,
				options.record_value
		 */
		$options = "";
		if (is_array($va_gripsizes) && !empty($va_gripsizes)) {
			foreach($va_gripsizes as $key => $value) {
				$options .= $value['grip_size'] . "|";
				
			}	
			$options = rtrim($options,"|");			
		} else {
			$options = "Standard";
		}
		
		echo $options;
	}
	
	/**************************
	 * Function: savecustomizeAction
	 * Purpose: sends all data fields and collected values from the customize screen as one big parameter to a custom club object for parsing and saving
	 * Called From::
	 *  web: /customclub/clubselectionsave ~ /javascript/custom_club.js
	 *  Function: continueToClubSelection() 
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
		
	public function savecustomizeAction() {
		
		$cc_obj = new CustomClub ();
		$i_request = $this->getRequest ();
		
		$v_customize_parameters = strip_tags ( $i_request->getParam ( 'customize_parameters' ) );
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_session_id = $v_session_id; 
		$va_fitting = array();
		$va_fitting = $cc_obj->GetMyFitting();
		$cc_obj->_va_fitting = $va_fitting; // save these fittings so that the CustomClub object 
											// may gain access to this incase someone goes 
											// back, they can quickly get their values again
		
		$cc_obj->SaveCustomizations ( $v_customize_parameters );
		
		
	}
	
	/*********************************
	 * Function: reviewAction
	 * Purpose: calls an instance of CustomClub->DisplayReview
	 * Called From::
	 *  web: /customclub/customize ~ /javascript/custom_club.js
	 *  Function: continueToReview() 
	 * Comment Date: 4/6/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function reviewAction() {
		$cc_obj = new CustomClub ();
		$i_request = $this->getRequest ();
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		$cc_obj->_session_id = $v_session_id;
		$va_fitting = array();
		$va_fitting = $cc_obj->GetMyFitting();
		$cc_obj->_va_fitting = $va_fitting; // save these fittings so that the CustomClub object 
											// may gain access to this incase someone goes 
											// back, they can quickly get their values again
		$cc_obj->DisplayReview ($v_session_id);
	}
	
	/*********************************
	 * Function: addtocartAction
	 * Purpose: calls an instance of CustomClub->AddToCart() 
	 * Called From::
	 *  web: /customclub/customize ~ /javascript/custom_club.js
	 *  Function: SmartFitSubmit() 
	 * Comment Date: 4/12/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function addtocartAction() {
		$cc_obj = new CustomClub ();
		
		$i_request = $this->getRequest ();
		$v_session_id = strip_tags ( $i_request->getParam ( 'session_id' ) );
		
		$cc_obj->AddToCart ($v_session_id);
	}

}
?>