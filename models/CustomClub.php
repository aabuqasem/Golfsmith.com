<?php

require_once ('Zend/View.php');
require_once ('models/Club.php');

class CustomClub {
	
	// Public variable declarations
	public $_category;		// Men, Women
	public $_club_type; 	// Hybrids, Driver, Fairway, Putter
	public $_manufacturer;	// club manufacturer: Snake Eyes, Callaway, etc.
	public $_model;			// Big Bertha w/steel, Forged Blade, etc.
	public $_dexterity;		// Right Hand / Left Hand
	public $_sessionid;		// Users' session id
	public $error_message;  
	public $_va_fitting = array();	// previously saved fitting preferences
	public $_my_clubs = array(); // and one for my clubs
	
	protected $i_site_init; // private variable used as part of the class constructor
	
	/******************************
	 * Function: __construct
	 * Purpose: Default constructor for class CustomClub
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: mainAction
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function __construct() {
		global $connect_mssql_db;
		$connect_mssql_db = 1;
		
		$this->i_site_init = new SiteInit ();
		$this->i_site_init->loadInit ();
	}
	
	/******************************
	 * Function: ShowError
	 * Purpose: renders any pre-set error message from the controller 
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: mixed
	 * Comment Date: 3/22/2010
	 * Comment Author: Robbie Smith
	 */	

	public function SendError($sql,$function) {
		mail("robbie.smith@golfsmith.com","SQL Error inside CustomClub.php :: $function",$sql);		
	}
	
	public function ShowError() {
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		$z_view->error_message = $this->error_message;
		echo $z_view->render ( "CustomClub/cc_error.phtml" );
	}
	
	/******************************
	 * Function: GetMyFitting
	 * Purpose: retrieves my fitting session basic information
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: mixed
	 * Comment Date: 3/19/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function GetMyFitting() {
		global $mssql_db;
		
		$v_sql = "
			SELECT 
				fitting_id, 
				session_id, 
				smart_fit_flag, 
				club_category, 
				player_height, 
				wrist_to_floor, 
				hand_size, 
				finger_size, 
				swing_speed, 
				driver_distance, 
				trajectory, 
				tempo, 
				target_swing, 
				started
			FROM 
				direct.dbo.GSI_CUST_CLUB_FITTING	
			WHERE 
				session_id = '" . $this->_session_id . "'
		";
		
		$v_result = mssql_query ( $v_sql );
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "getManufacturers query from Club model" );
			;//$this->SendError($v_sql,"GetMyFitting");
		}
		
		$va_fitting = array ();
		
		if (mssql_num_rows( $v_result ) > 0) {
			$c = 0;
			while ( $v_row = mssql_fetch_assoc($v_result)) {
				$col = array();
				$col['fitting_id'] 		= $v_row['fitting_id'];
				$col['session_id'] 		= $v_row['session_id'];
				$col['smart_fit_flag'] 	= $v_row['smart_fit_flag'];
				$col['club_category'] 	= $v_row['club_category'];
				$col['player_height'] 	= $v_row['player_height'];
				$col['wrist_to_floor'] 	= $v_row['wrist_to_floor'];
				$col['hand_size'] 		= $v_row['hand_size'];
				$col['finger_size'] 	= $v_row['finger_size'];
				$col['swing_speed'] 	= $v_row['swing_speed'];
				$col['driver_distance'] = $v_row['driver_distance'];
				$col['trajectory'] 		= $v_row['trajectory'];
				$col['tempo'] 			= $v_row['tempo'];
				$col['target_swing'] 	= $v_row['target_swing'];
				$col['started'] 		= $v_row['started'];
				$va_fitting = $col;
			}
			
		} else {
			$va_fitting = $v_sql;
		}
		mssql_free_result($v_result);
		
		return $va_fitting;
	}
	
	/******************************
	 * Function: GetMyClubs
	 * Purpose: retrieves my club selection(s)
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: mixed
	 * Comment Date: 3/19/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function GetMyClubs() {
		global $mssql_db;
		
		$v_sql = "
			SELECT 
				clubs.club_id, 
				clubs.fitting_id, 
				clubs.club_type, 
				clubs.manufacturer,
				clubs.model, 
				clubs.shaft_model, 
				clubs.shaft_model_price, 
				clubs.shaft_flex, 
				clubs.shaft_club_length, 
				clubs.lie_angle, 
				clubs.grip_size, 
				clubs.grip_model, 
				clubs.grip_model_price, 
				clubs.special_instructions, 
				clubs.serial_number, 
				clubs.started,
				clubs.service_price
			FROM 
				direct.dbo.gsi_cust_clubs AS clubs INNER JOIN
					direct.dbo.gsi_cust_club_fitting AS fitting ON 
						clubs.fitting_id = fitting.fitting_id  
			WHERE 
				fitting.session_id = '" . $this->_session_id . "'
		";
		
		$v_result = mssql_query ( $v_sql ) or die($v_sql);
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "getManufacturers query from Club model" );
			
			;//$this->SendError($v_sql,"GetMyClubs");
		}
		
		$va_fitting = array ();
		
		if (mssql_num_rows( $v_result ) > 0) {
			$c = 0;
			while ($v_row = mssql_fetch_assoc($v_result)) {
				$col = array();
				$col['club_id'] 			= $v_row['club_id'];
				$col['fitting_id'] 			= $v_row['fitting_id'];
				$col['club_type'] 			= $v_row['club_type'];
				$col['manufacturer'] 		= $v_row['manufacturer'];
				$col['model'] 				= $v_row['model'];
				$col['club_selection'] 		= $v_row['club_selection'];
				$col['dexterity'] 			= $v_row['dexterity'];
				$col['base_price'] 			= $v_row['base_price'];
				$col['shaft_model'] 		= $v_row['shaft_model'];
				$col['shaft_model_price'] 	= $v_row['shaft_model_price'];
				$col['shaft_flex'] 			= $v_row['shaft_flex'];
				$col['shaft_club_length']	= $v_row['shaft_club_length'];
				$col['lie_angle'] 			= $v_row['lie_angle'];
				$col['grip_size'] 			= $v_row['grip_size'];
				$col['grip_model'] 			= $v_row['grip_model'];
				$col['grip_model_price'] 	= $v_row['grip_model_price'];
				$col['special_instructions']= $v_row['special_instructions'];
				$col['serial_number'] 		= $v_row['serial_number'];
				$col['started'] 			= $v_row['started'];
				$col['service_price'] 		= $v_row['service_price'];
				$va_fitting[$c++] = $col;
			}
			mssql_free_result($v_result);
		} 
		
		return $va_fitting;
	}

	/******************************
	 * Function: DisplayHeader
	 * Purpose: Loads function loadMain
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: mainAction
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function DisplayHeader() {
		$this->i_site_init->loadMain ();
	}
	
	/******************************
	 * Function: DisplayMain
	 * Purpose: Renders the main screen for the beginning of the custom club selection process
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: mainAction
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function DisplayMain() {
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		echo $z_view->render ( "CustomClub/cc_main.phtml" );
	}
	
	/******************************
	 * Function: DisplayFooter
	 * Purpose: Renders the main screen for the beginning of the custom club selection process
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: mainAction
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function DisplayFooter() {
		$g_data["pageType"]="other";
      	$g_data["prodid"]="";
      	$g_data["totalvalue"]="";
		$this->i_site_init->loadFooter ($g_data);
	}
		
	/******************************
	 * Function: StartFittingSession
	 * Purpose: Saves, and if need be starts a club fitting, session into SQL SERVER 
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: clubselectionsaveAction
	 * Comment Date: 3/11/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function StartFittingSession ( $p_cf_method, $p_cust_category ) {
		
		global $mssql_db; 
		
		// test to see if current session is started 
		
		$v_sql = "
			SELECT 
				fitting_id 
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING
			WHERE 
				session_id = '" . $this->_sessionid . "'
		";
		
		$v_result = mssql_query ( $v_sql );
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "StartFittingSession query from CustomClub model" );
			
			;//$this->SendError($v_sql,"StartFittingSession");
			return false;
		} 
		
		if (mssql_num_rows($v_result) == 0) { // we do not have a session!
			$v_sql = "
			INSERT INTO 
				direct.dbo.GSI_CUST_CLUB_FITTING
			(
				session_id,
				smart_fit_flag,
				club_category
			)
			VALUES
			(
				'" . $this->_sessionid . "',
				'" . $p_cf_method . "',
				'" . $p_cust_category . "'
			)
			";
			
			mssql_query ( $v_sql ) or die('failed to insert'); // START THE FITTING
			
			if (! $v_result) {
				display_mssql_error ( $v_sql, "StartFittingSession query from CustomClub model" );
				;//$this->SendError($v_sql,"StartFittingSession");
				return false;
			}
		} else { // continue the custom fitting session
			$v_sql = "
			UPDATE 
				direct.dbo.GSI_CUST_CLUB_FITTING
			SET
				smart_fit_flag = '" . $p_cf_method . "',
				club_category = '" . $p_cust_category . "'
			WHERE
				session_id = '" . $this->_sessionid . "'
			";
			mssql_query ( $v_sql ); // UPDATE THE FITTING			
		}
		mssql_free_result($v_result);
		return true;
	}
	
    /************************
	 * Function: DisplayClubSelection
	 * Purpose: Renders the main screen for the beginning of the custom club selection process
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: clubselectionAction
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function DisplayClubSelection($p_order_type, $p_cust_category) {
		
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		
		$z_view->order_type = $p_order_type;
		$z_view->step = 1;
		echo $z_view->render ( "CustomClub/cc_club_selection_header.phtml" );
		
		$va_club_type = array ();
		
		$v_club_obj = new Club ();
		$v_club_obj->_category = $p_cust_category;
		$z_view->cust_category = $p_cust_category;
		$va_club_type = $v_club_obj->getClubTypes (); // returns (mixed) 

		//$z_view->cust_category = $p_cust_category;
		$z_view->club_types = $va_club_type;
		$z_view->my_clubs = $this->_my_clubs; // all selections
		if (is_array($va_club_type) && !empty($va_club_type)) {
			foreach ( $va_club_type as $key => $value ) {
			    
			    //these 3 if statments were done for the ticket
			    if($value['club_type'] == "Iron Sets - Irons Only"){
			        $z_view->type = "Irons";
			        $z_view->new_type = $value['club_type'];
			        $v_club_obj->_club_type = "Irons";
			    }else if($value['club_type'] == "Iron Sets - With Hybrids"){
			        $z_view->type = "Combo/Hybrid";
			        $z_view->new_type = $value['club_type'];
			        $v_club_obj->_club_type = "Combo/Hybrid";
			    }else{
			        $z_view->type = $value['club_type'];
			        $z_view->new_type = $value['club_type'];
			        $v_club_obj->_club_type = $value['club_type'];
			    }
			    
				
				$z_view->club_availability_date = $value['availability_date'];
				$v_club_obj->_club_availability_date = $value['availability_date'];
				
				$z_view->manufacturers = $v_club_obj->getManufacturers (); // returns available manufacturers
				$z_view->error = $va_club_type;
			
				echo $z_view->render ( "CustomClub/cc_club_selection_body.phtml" );
			} 
		} else {
			$this->error_message = "<p>According to our records, you don't have a fitting session started or the one you had has expired. Please <a href=\"/customclub\">start over</a></p>.";
			//$this->error_message .= "<BR>" . $va_club_type . "<BR>";
			echo $this->ShowError();
		}
		
		echo $z_view->render ( "CustomClub/cc_club_selection_footer.phtml" );
	}
	
	/************************
	 * Function: DisplayProductImage
	 * Purpose: Takes the MS SQL inventory_item_id and displays the model image
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: showmodelimageAction
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function DisplayProductImage($p_inventory_item_id) {
		
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		$v_product_image = "";
		
		// get the images
		$v_club_obj = new Club ();
		$v_club_obj->_inventory_item_id = $p_inventory_item_id;
		$v_product_image = $v_club_obj->getProductImages ();		
		//$z_view->result = $v_product_image;
		if ($v_product_image) {
			if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/images/" . $v_product_image))
				$z_view->image = $v_product_image;
			else
				$z_view->image = "ccm/ccf_clubfit_clean.gif";
		} else {
			$z_view->image = "ccm/ccf_clubfit_clean.gif";
		}
		
		echo $z_view->render ( "CustomClub/cc_club_image.phtml" );
	}

	/******************************
	 * Function: SaveClubSelectionCustClub
	 * Purpose: Saves club selections session into SQL SERVER at the basic level
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  new version of: SaveClubSelection
	 * Comment Date: 3/11/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function SaveClubSelectionCustClub ( $v_driver_modl_val, $session_iid ) {
	
	    global $mssql_db;
	
	    // get the current fitting_id / session
	    $v_sql = "
			SELECT
				fitting_id
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING
			WHERE
				session_id = '" . $session_iid . "'
		";
	    // execute the query
	    $v_result = mssql_query ( $v_sql );
	    // respond with error
	    if (! $v_result) {
	        display_mssql_error ( $v_sql, "SaveClubSelectionCustClub query from CustomClub model" );
	        ;//$this->SendError($v_sql,"SaveClubSelection");
	        return false;
	    }
	
	    // get the fitting_id
	    while($row = mssql_fetch_assoc($v_result)){
	        $fitting_id = $row['fitting_id'];
	    }
	
	    // free up server resources
	    mssql_free_result($v_result);
	
	    // clear out anything in the fitting table with this fitting_id
	    $v_sql = "
			DELETE FROM
				direct.dbo.GSI_CUST_CLUBS
			WHERE
				fitting_id = '" . $fitting_id . "'
			";
	    // execute the query
	    $v_result = mssql_query ( $v_sql );
	    if (! $v_result) {
	        display_mssql_error ( $v_sql, "SaveClubSelectionCustClub query from CustomClub model" );
	        ;//$this->SendError($v_sql,"SaveClubSelection");
	        return false;
	    }
	
	    // quick declarations
	    $v_insert_values = "";
	    $v_sql = "INSERT INTO direct.dbo.GSI_CUST_CLUBS (FITTING_ID,MODEL) ";

	    // add the club selections at the basic level to the fitting table
        $v_insert_values .= "SELECT '" . $fitting_id . "','" . $v_driver_modl_val."'" ;
	
	    $v_sql = $v_sql . $v_insert_values;
	
	    // execute the query
	    $v_result = mssql_query ( $v_sql );
	    if (! $v_result) {
	        display_mssql_error ( $v_sql, "SaveClubSelectionCustClub query from CustomClub model" );
	        ;//$this->SendError($v_sql,"SaveClubSelection");
	        return false;
	    }
	
	    return true;
	}
	
	
	/******************************
	 * Function: SaveClubSelection
	 * Purpose: Saves club selections session into SQL SERVER at the basic level 
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: clubselectionsaveAction
	 * Comment Date: 3/11/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function SaveClubSelection ( $v_combohybrid_menu_val, $v_combohybrid_modl_val,
			$v_driver_menu_val, $v_driver_modl_val, 
			$v_fairway_menu_val, $v_fairway_modl_val, $v_hybrids_menu_val,
			$v_hybrids_modl_val, $v_irons_menu_val, $v_irons_modl_val,
			$v_wedges_menu_val, $v_wedges_modl_val,
			$v_putter_menu_val, $v_putter_modl_val ) {
		
		global $mssql_db; 
		
		// get the current fitting_id / session 
		$v_sql = "
			SELECT 
				fitting_id 
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING
			WHERE 
				session_id = '" . $this->_sessionid . "'
		";
		
		
		/*
		echo "By Luis \n";
		echo $v_sql . "\n" ;
		echo "By Luis ENDS \n";
		*/
		// execute the query
		$v_result = mssql_query ( $v_sql );
		
		// respond with error
		if (! $v_result) {
			display_mssql_error ( $v_sql, "SaveClubSelection query from CustomClub model" );
			;//$this->SendError($v_sql,"SaveClubSelection");
			return false;
		} 
		
		// get the fitting_id
		while($row = mssql_fetch_assoc($v_result)){
			$fitting_id = $row['fitting_id'];
		}
			
		// free up server resources
		mssql_free_result($v_result);
		
		// clear out anything in the fitting table with this fitting_id
		$v_sql = "
			DELETE FROM
				direct.dbo.GSI_CUST_CLUBS
			WHERE
				fitting_id = '" . $fitting_id . "'
			";
		
		// execute the query
		$v_result = mssql_query ( $v_sql );   
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "SaveClubSelection query from CustomClub model" );
			;//$this->SendError($v_sql,"SaveClubSelection");
			return false;
		} 	

		// quick declarations
		$v_insert_values = "";
		$v_sql = "INSERT INTO direct.dbo.GSI_CUST_CLUBS (FITTING_ID,CLUB_TYPE,MANUFACTURER,MODEL) ";
		
		// add the club selections at the basic level to the fitting table
		if ($v_combohybrid_menu_val != "") {
			$v_insert_values .= "SELECT '" . $fitting_id . "', 'Combo/Hybrid','" . $v_combohybrid_menu_val . "','" . $v_combohybrid_modl_val . "' UNION ALL ";
		}
		if ($v_driver_menu_val != "") {
			$v_insert_values .= "SELECT '" . $fitting_id . "', 'Driver','" . $v_driver_menu_val . "','" . $v_driver_modl_val . "' UNION ALL ";
		}
		if ($v_fairway_menu_val != "") {
			$v_insert_values .= "SELECT '" . $fitting_id . "', 'Fairway','" . $v_fairway_menu_val . "','" . $v_fairway_modl_val . "' UNION ALL ";
		}
		if ($v_hybrids_menu_val != "") {
			$v_insert_values .= "SELECT '" . $fitting_id . "', 'Hybrids','" . $v_hybrids_menu_val . "','" . $v_hybrids_modl_val . "' UNION ALL ";
		}
		if ($v_irons_menu_val != "") {
			$v_insert_values .= "SELECT '" . $fitting_id . "', 'Irons','" . $v_irons_menu_val . "','" . $v_irons_modl_val . "' UNION ALL ";
		}
		if ($v_wedges_menu_val != "") {
			$v_insert_values .= "SELECT '" . $fitting_id . "', 'Wedges','" . $v_wedges_menu_val . "','" . $v_wedges_modl_val . "' UNION ALL ";
		}
		if ($v_putter_menu_val != "") {
			$v_insert_values .= "SELECT '" . $fitting_id . "', 'Putter','" . $v_putter_menu_val . "','" . $v_putter_modl_val . "' UNION ALL ";
		}
			
		$v_insert_values = rtrim($v_insert_values,"UNION ALL ");
		
		$v_sql = $v_sql . $v_insert_values;
		
		
		
		
		// execute the query
		$v_result = mssql_query ( $v_sql );   

		if (! $v_result) {
			display_mssql_error ( $v_sql, "SaveClubSelection query from CustomClub model" );
			;//$this->SendError($v_sql,"SaveClubSelection");
			return false;
		} 

		return true;
	}
	
	/******************************
	 * Function: TestFittingSession
	 * Purpose: tests the users fitting session has started or not
	 * Called From::
	 * 	Web: /customclub/(mixed) ~ /javascript/custom_club.js
	 *  Function: (mixed)
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function TestFittingSession ( $p_session_id ) {
		
		global $mssql_db; 
		
		// test to see if current session is started 
		
		$v_sql = "
			SELECT 
				fitting_id 
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING
			WHERE 
				session_id = '" . $p_session_id . "'
		";
		
		$v_result = mssql_query ( $v_sql );
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "TestFittingSession query from CustomClub model" );
			;//$this->SendError($v_sql,"TestFittingSession");
			return false;
		} 
		
		if (mssql_num_rows($v_result) == 0) { // we do not have a session!
			$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
			;//$this->SendError($v_sql,"TestFittingSession");
			$z_view->error_message = "According to our records, you don't have a fitting session started. Please <a href=\"/customclub\">start over</p>.";
			echo $z_view->render ( "CustomClub/cc_error.phtml" );
		} 
		mssql_free_result($v_result);
		
	}
	
	public function OutOfStock ( $p_session_id,$return_status ) {
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		$this->SendError($p_session_id,"OutOfStock: " . $return_status);
		$z_view->error_message = "We appologize for the inconvenience. It seems as though we encountered an error while completing your order: " . $return_status . "<br><br>Please <a href=\"/customclub\">resume your selections</a>.";
		echo $z_view->render ( "CustomClub/cc_error.phtml" );
	}
	
	/******************************
	 * Function: getModels
	 * Purpose: Gets Models by Manufacture / Club Type and displays the corresponding <select> list
	 * Called From::
	 * 	Web: /customclub/getModels ~ /javascript/custom_club.js
	 *  Function: mainAction
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function getModels($p_cust_category, $p_club_type, $p_manufacturer) {
		
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) ); // new zend view instance
		
		$v_club_obj = new Club ();  // new club instance
		$v_club_obj->_category = $p_cust_category; 
		$v_club_obj->_club_type = $p_club_type;
		$v_club_obj->_manufacturer = $p_manufacturer;
		/*echo "<pre>";
		print_r($_REQUEST);
		print_r($_SERVER);
		echo "</pre>";
		$v_club_obj->_category = $_REQUEST['cust_category'];
		$v_club_obj->_club_type = $_REQUEST['club_type'];
		$v_club_obj->_manufacturer = $_REQUEST['manufacturer'];*/
		
		$z_view->club_type = $p_club_type;
		$z_view->models = $v_club_obj->getModels (); // club -> getModels() that takes $these parameters passed in 
		//echo $z_view->models;
		echo $z_view->render ( "CustomClub/cc_club_selection_models.phtml" );
	
	}
	
	/******************************
	 * Function: DisplayPlayerProfile
	 * Purpose: Displays Player Profile screen to select person's Height and Wrist to Floor measurements
	 * Called From::
	 * 	Web: /customclub/getModels ~ /javascript/custom_club.js
	 *  Function: continueToPlayerProfile
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function DisplayPlayerProfile() {
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		$z_view->order_type = 'S'; // SmartFit
		$z_view->step = 2;
		$z_view->va_fitting = $this->_va_fitting;
		echo $z_view->render ( "CustomClub/cc_player_profile_header.phtml" );
		echo $z_view->render ( "CustomClub/cc_player_profile.phtml" );
	}
	
	/******************************
	 * Function: SavePlayerProfile
	 * Purpose: Saves customer height, and wrist to floor measurements into SQL SERVER  
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: saveplayerprofileAction
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function SavePlayerProfile ( $p_cust_height, $p_wrist_to_floor ) {
		
		global $mssql_db; 
		
		// get the current fitting_id / session 
		$v_sql = "
			SELECT 
				fitting_id 
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING
			WHERE 
				session_id = '" . $this->_sessionid . "'
		";
		
		// execute the query
		$v_result = mssql_query ( $v_sql );
		
		// respond with error
		if (! $v_result) {
			display_mssql_error ( $v_sql, "SavePlayerProfile query from CustomClub model" );
			;//$this->SendError($v_sql,"SavePlayerProfile");
			return false;
		} 		
		
		// get the fitting_id
		while($row = mssql_fetch_assoc($v_result)){
			$fitting_id = $row['fitting_id'];
		}
			
		// free up server resources
		mssql_free_result($v_result);
		
		// By the time you get to this step, you HAVE a fitting id or a previous
		// function won't even let you get to this point. So all we need to do here
		// is update your fitting session with 2 values.
		
		// quick declarations
		$v_sql = "
			UPDATE 
				direct.dbo.GSI_CUST_CLUB_FITTING
			SET
				player_height = '" . $p_cust_height . "',
				wrist_to_floor = '" . $p_wrist_to_floor . "'
			WHERE
				fitting_id = '" . $fitting_id . "'";
				
		// execute the query
		$v_result = mssql_query ( $v_sql );   
		
		if (!$v_result) {
			;//$this->SendError($v_sql,"SavePlayerProfile");
		}
		
		// REMOVE AFTER TESTING!!!!!
		#$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		#$z_view->error_message = $v_sql;
		#echo $z_view->render ( "CustomClub/cc_error.phtml" );
		
		
		return true;
	}
	
	/******************************
	 * Function: DisplayHandMeasurement
	 * Purpose: Displays Hand Measurement screen to select person's Hand measurements
	 * Called From::
	 * 	Web: /customclub/getModels ~ /javascript/custom_club.js
	 *  Function: mainAction
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function DisplayHandMeasurement() {
		
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		$z_view->order_type = 'S'; // SmartFit 
		$z_view->step = 3;
		$z_view->va_fitting = $this->_va_fitting;
		echo $z_view->render ( "CustomClub/cc_hand_measurement_header.phtml" );
		echo $z_view->render ( "CustomClub/cc_hand_measurement.phtml" );
	}
	
	/******************************
	 * Function: SaveHandMeasurement
	 * Purpose: Saves hand and finger length measurements into SQL SERVER  
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: savehandmeasurementAction
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function SaveHandMeasurement ( $p_hand_size, $p_finger_size ) {
		
		global $mssql_db; 
		
		// get the current fitting_id / session 
		$v_sql = "
			SELECT 
				fitting_id 
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING
			WHERE 
				session_id = '" . $this->_sessionid . "'
		";
		
		// execute the query
		$v_result = mssql_query ( $v_sql );
		
		// respond with error
		if (! $v_result) {
			display_mssql_error ( $v_sql, "SaveHandMeasurement query from CustomClub model" );
			;//$this->SendError($v_sql,"SaveHandMeasurement");
			return false;
		} 		
		
		// get the fitting_id
		while($row = mssql_fetch_assoc($v_result)){
			$fitting_id = $row['fitting_id'];
		}
			
		// free up server resources
		mssql_free_result($v_result);
		
		// By the time you get to this step, you HAVE a fitting id or a previous
		// function won't even let you get to this point. So all we need to do here
		// is update your fitting session with a few values.
		
		// quick declarations
		$v_sql = "
			UPDATE 
				direct.dbo.GSI_CUST_CLUB_FITTING
			SET
				hand_size = '" . $p_hand_size . "',
				finger_size = '" . $p_finger_size . "'
			WHERE
				fitting_id = '" . $fitting_id . "'";
				
		// execute the query
		$v_result = mssql_query ( $v_sql );   
		
		if (!$v_result) {
			;//$this->SendError($v_sql,"SaveHandMeasurement");
		}
		
		// REMOVE AFTER TESTING!!!!!
		#$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		#$z_view->error_message = $v_sql;
		#echo $z_view->render ( "CustomClub/cc_error.phtml" );
		
		
		return true;
	}
	
 	/******************************
	 * Function: DisplaySwingTrajectory
	 * Purpose: Displays Swing Trajectory screen to select person's Hand measurements
	 * Called From::
	 * 	Web: /customclub/getModels ~ /javascript/custom_club.js
	 *  Function: mainAction
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function DisplaySwingTrajectory() {
		
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		$z_view->order_type = 'S'; // SmartFit
		$z_view->step = 4;
		$z_view->va_fitting = $this->_va_fitting;
		echo $z_view->render ( "CustomClub/cc_swing_trajectory_header.phtml" );
		echo $z_view->render ( "CustomClub/cc_swing_trajectory.phtml" );
	}
	
	/******************************
	 * Function: SaveSwingTrajectory
	 * Purpose: Saves hand and finger length measurements into SQL SERVER  
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: savehandmeasurementAction
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function SaveSwingTrajectory ( $p_driver_speed, $p_iron_distance, $p_trajectory,
									   $p_tempo, $p_target_swing ) {
		
		global $mssql_db; 
		
		// get the current fitting_id / session 
		$v_sql = "
			SELECT 
				fitting_id 
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING
			WHERE 
				session_id = '" . $this->_sessionid . "'
		";
		
		// execute the query
		$v_result = mssql_query ( $v_sql );
		
		// respond with error
		if (! $v_result) {
			display_mssql_error ( $v_sql, "SaveHandMeasurement query from CustomClub model" );
			;//$this->SendError($v_sql,"SaveSwingTrajectory");
			return false;
		} 		
		
		// get the fitting_id
		while($row = mssql_fetch_assoc($v_result)) {
			$fitting_id = $row['fitting_id'];
		}
			
		// free up server resources
		mssql_free_result($v_result);
		
		// By the time you get to this step, you HAVE a fitting id or a previous
		// function won't even let you get to this point. So all we need to do here
		// is update your fitting session with a few values.
		
		// quick declarations
		$v_sql = "
			UPDATE 
				direct.dbo.GSI_CUST_CLUB_FITTING
			SET
				swing_speed = '" . $p_driver_speed . "',
				driver_distance = '" . $p_iron_distance . "', 
				trajectory = '" . $p_trajectory . "',
				tempo = '" . $p_tempo . "', 
				target_swing = '" . $p_target_swing . "'
			WHERE
				fitting_id = '" . $fitting_id . "'";
				
		// execute the query
		$v_result = mssql_query ( $v_sql );   
		
		if (!$v_result) {
			;//$this->SendError($v_sql,"SaveSwingTrajectory");
		}
		
		return true;
	}

	/******************************
	 * Function: DisplayCustomize
	 * Purpose: Displays the customization club interface
	 * Called From::
	 * 	Web: /customclub/customize ~ /javascript/custom_club.js
	 *  Function: continueToCustomize
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function DisplayCustomize($p_session_id) {
	
		$z_view = new Zend_View(array('scriptPath'=>VIEW_PATH));
		$z_view->order_type = 'S'; // SmartFit
		$z_view->step = 5;
		$z_view->va_fitting = $this->_va_fitting;
		echo $z_view->render("CustomClub/cc_customize_header.phtml");
	
		$va_my_clubs = array ();
		
		// get my saved settings
		$v_club_obj = new Club ();
		$v_club_obj->_session_id = $p_session_id;
		$va_my_fitting = $v_club_obj->getMyFitting();
	

		 if (is_array($va_my_fitting) && !empty($va_my_fitting)) {
		 	foreach($va_my_fitting as $key => $value) {
				$v_club_obj->_fitting_id = $value["fitting_id"];
				$z_view->fitting_id = $value["fitting_id"]; 
				$z_view->smart_fit_flag = $value["smart_fit_flag"];
				$z_view->club_category = $value['club_category'];
				$z_view->player_height = $value['player_height'];
				$z_view->wrist_to_floor = $value['wrist_to_floor'];
				$z_view->hand_size = $value['hand_size'];
				$z_view->finger_size = $value['finger_size'];
				$z_view->swing_speed = $value['swing_speed'];
				$z_view->driver_distance = $value['driver_distance'];
				$z_view->trajectory = $value['trajectory'];
				$z_view->tempo = $value['tempo'];
				$z_view->target_swing = $value['target_swing'];
				$z_view->started = $value['started'];
			}
		} else {
			$z_view->error_message = "Your fitting session has not been established";
			echo $z_view->render ( "CustomClub/cc_customize_body.phtml" );
		}
		
		// calc final swing speed 
        if (!empty($z_view->swing_speed)) {
          if ($z_view->tempo == 'slow') {
            $z_view->swing_speed -= 5;
          }
         
          if ($z_view->tempo == 'fast') {
            $z_view->swing_speed += 5;
          }
         
          if ($z_view->target_swing == 'control') {
            $z_view->swing_speed += 5;
          }
         
          if ($z_view->target_swing == 'distance') {
            $z_view->swing_speed -= 5;
          }
        }
        
        if ((empty($z_view->trajectory)) || ($z_view->smart_fit_flag == 'P')) {
			// for people who know their measurements already and don't need a suggestion
			$z_view->reco_shaft_type = 'Select';
        } else {
			// Suggests trajectory based on their swing / trajectory step
			$z_view->reco_shaft_type = $z_view->trajectory .' Launch';
        }
        
        $z_view->va_shaft_lengths = array();
		// figure out the club length recommended suggestion
        if (($z_view->smart_fit_flag == 'P')) {
        	// for people who know their measurements already and don't need a suggestion
			$z_view->reco_club_length = 'Select';
        } else {
        	// this sets a fitting global recommendation for both iron and driver lengths
			$z_view->va_shaft_lengths = $v_club_obj->GetShaftLengths($z_view->wrist_to_floor,$z_view->club_category);
        }
		$z_view->reco_grip_size = "";
        if (($z_view->smart_fit_flag == 'P')) {
			// for people who know their measurements already and don't need a suggestion
			$z_view->reco_grip_size = 'Select';
		} else {
			// this sets a fitting global recommendation for grip size
			$z_view->reco_grip_size = $v_club_obj->GetGripSize($z_view->club_category,$z_view->hand_size,$z_view->finger_size);          
        }
		// now get all my clubs that I selected in step 1
		$va_my_clubs = $v_club_obj->getMyClubs();

		// for each club selected display the customized settings.
		if (is_array($va_my_clubs) && !empty($va_my_clubs)) {
			$z_view->initial = 0;
			// grab all club types in this selection for use in club angles so we know which
			// club_lie_angles to loop through when setting dexterity
			$club_types = "";
			$club_models = "";
			foreach($va_my_clubs as $key => $value) {
				$club_types .= $value['club_type'] . ",";
				$club_models .= $value['model'] . ",";
				$club_ids .= $value['club_id'] . ",";
			}
			$club_types = rtrim($club_types,",");
			$club_models = rtrim($club_models,",");
			$club_ids = rtrim($club_ids,",");
			$z_view->club_models = $club_models;
			$z_view->club_types = $club_types;
			$z_view->club_ids = $club_ids;
			
			// now loop through the club selection for further customizations
			foreach($va_my_clubs as $key => $value) {
				#print_r($value);
				//reco calculations for the items specific to club type
				//shaft length
				if($z_view->reco_club_length != 'Select') {
					if ($value['club_type'] == 'Irons' || $value['club_type'] == 'Wedges') {
						$z_view->reco_club_length = 'Select';
					}
					if ($value['club_type'] == 'Drivers' || $value['club_type'] == 'Woods' || $value['club_type'] == 'Fairway' || $value['club_type'] == 'Hybrids') {
						$z_view->reco_club_length = 'Select';
					}
					if (empty($z_view->reco_club_length)) {
						$z_view->reco_club_length = 'Select';
					}
				}
				
				$z_view->model_availability_date = $value['availability_date'];
								
				// recommended lie angles for Irons / Wedges
				$z_view->reco_lie_angle = 'Select';
				if (($z_view->smart_fit_flag == 'P')) {
					$z_view->reco_lie_angle = 'Select';
				} else {
					if( ($value['club_type'] == 'Irons') || ($value['club_type'] == 'Wedges')) {
						if (! empty($z_view->player_height)) {
							$z_view->reco_lie_angle = '';
							$z_view->std_lie_angle = '';
                
							$z_view->std_lie_angle = 'Select';
							
							$z_view->reco_lie_angle = 'Select';

							/*if ($z_view->reco_lie_angle != 'Select') {
								$z_view->reco_lie_angle = ($z_view->reco_lie_angle - $z_view->std_lie_angle);
								if ($z_view->reco_lie_angle < 0) {
									if ($value['club_type'] == "Irons") {
										$z_view->reco_lie_angle = $z_view->reco_lie_angle.' ';
									}
									$z_view->reco_lie_angle .= ' Flat';
								} else {
									if ($z_view->reco_lie_angle > 0) {
										$z_view->reco_lie_angle = '+'.$z_view->reco_lie_angle;
										if ($value['club_type'] == 'Irons') {
											$z_view->reco_lie_angle = $z_view->reco_lie_angle.' ';
										}
										$z_view->reco_lie_angle .= ' Upright';
									} else {
										$z_view->reco_lie_angle = 'Standard';
									}
								}
							}*/
						} else {
							$z_view->reco_lie_angle = 'Select';
						}
					} else {
						$z_view->reco_lie_angle = 'Select';
					}
				}
			
				// get shaft options for said trajectory and specified club
				$va_my_shafts_options = $v_club_obj->GetShafts($p_session_id, $z_view->trajectory, $value['model']); 
				$z_view->shaft_options = $va_my_shafts_options;
				// get grips for said club model
				$va_my_grips = $v_club_obj->GetGrips($p_session_id, $value['model']);
				$z_view->grips = $va_my_grips;
				// is this club a set and if so, return the price list. 
				// if the result is empty, it's not set.
				$va_club_set = $v_club_obj->getClubSet($value['model']);
				$z_view->is_club_set = false;
				$z_view->club_set_pricing = array();
				$z_view->club_set = $va_club_set;
				if (is_array($va_club_set) && !empty($va_club_set)) {
					$z_view->is_club_set = true;
					$z_view->club_set_pricing = $va_club_set;
				} 
				
				// what services are available for 'this' club
				$va_services = array();
				$va_services = $v_club_obj->getAvailableServices($value['model'],NULL);
				$z_view->va_services = $va_services;
				
				#print_r($va_services);
				
				$z_view->value = $value; // this is the getMyClubs $value...
				#die(print_r($z_view->value));
				echo $z_view->render("CustomClub/cc_customize_body.phtml");
				
				$z_view->initial = 1; // do not show the select hand on future rows
			}
			
		} else {
			//$z_view->error_message = "You don't have any clubs at this time";
			echo $z_view->render("CustomClub/cc_customize_body.phtml");
		}
		
		echo $z_view->render("CustomClub/cc_customize_footer.phtml");
	}
	
	/******************************
	 * Function: DisplayCustomize
	 * Purpose: Displays the customization club interface
	 * Called From::
	 * 	Web: /customclub/customize ~ /javascript/custom_club.js
	 *  Function: continueToCustomize
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */
	

	public function DisplayCustomizeClub($v_style,$v_iid,$v_styleNumber,$v_brandName,$v_justStyle = false) {

	    $z_view = new Zend_View(array('scriptPath'=>VIEW_PATH));
		$z_view->order_type = 'S'; // SmartFit
		$z_view->step = 5;
		$z_view->va_fitting = $this->_va_fitting;
		$z_view->inv_id = $v_iid;
		$z_view->style = $v_style;
		$z_view->justStyle=$v_justStyle;
		$z_view->manufacturer= $v_brandName;
		//echo $z_view->render("CustomClub/cc_customize_header.phtml");
	
		$va_my_clubs = array ();
		
		// get my saved settings
		$v_club_obj = new Club ();
		// to check if it is set or not 
		$isClubSet= $v_club_obj->isClubSet($v_styleNumber,$v_iid);
		//if ($isClubSet["CATEGORY_NAME"] == "IRON SETS" || str_replace("/","",$isClubSet["Combo/Hybrid"]) == "ComboHybrid"){
		if ($isClubSet){
		    //return true;  //we don't need this anymore
		    $z_view->club_type_combo = 'combohybrid';
		}

		$isHybrid= $v_club_obj->isHybrid($v_styleNumber,$v_iid);
		//if ($isClubSet["CATEGORY_NAME"] == "IRON SETS" || str_replace("/","",$isClubSet["Combo/Hybrid"]) == "ComboHybrid"){
		if ($isHybrid){
		    $z_view->club_type_hybrid = 'hybrid';
		    
		}

		
		$clubType = $v_club_obj->getClubType($v_iid);

            // todo
            //put check point for the exists of the club
		$p_session_id = $v_club_obj->createTestFittingSession();
		
		$v_club_obj->_session_id = $p_session_id;
		$z_view->session_iid = $this->_sessionid = $p_session_id;
		$v_club_obj->StartFittingSessionCustClub($p_session_id);
		
		
		
		
	
		
		
		if($clubType == 'Combo/Hybrid'){
		    $v_combohybrid_menu_val = $v_brandName;
		    $v_combohybrid_modl_val = $v_iid;
		}elseif($clubType == 'Driver'){
		    $v_driver_menu_val = $v_brandName;
		    $v_driver_modl_val = $v_iid;
		}elseif($clubType == 'Fairway'){
		    $v_fairway_menu_val = $v_brandName;
		    $v_fairway_modl_val = $v_iid;
		}elseif($clubType == 'Hybrids'){
		    $v_hybrids_menu_val = $v_brandName;
		    $v_hybrids_modl_val = $v_iid;

		}elseif($clubType == 'Irons'){
		    $v_irons_menu_val = $v_brandName;
		    $v_irons_modl_val = $v_iid;
		}elseif($clubType == 'Wedges'){
		    $v_wedges_menu_val = $v_brandName;
		    $v_wedges_modl_val = $v_iid;
		}elseif($clubType == 'Putter'){
		    $v_putter_menu_val = $v_brandName;
		    $v_putter_modl_val = $v_iid;
		}


		$this->SaveClubSelection ( $v_combohybrid_menu_val, $v_combohybrid_modl_val,
		    $v_driver_menu_val, $v_driver_modl_val,
		    $v_fairway_menu_val, $v_fairway_modl_val, $v_hybrids_menu_val,
		    $v_hybrids_modl_val, $v_irons_menu_val, $v_irons_modl_val,
		    $v_wedges_menu_val, $v_wedges_modl_val,
		    $v_putter_menu_val, $v_putter_modl_val );
		
		

		
		
		$va_my_fitting = $v_club_obj->getMyFitting();
		if (is_array($va_my_fitting) && !empty($va_my_fitting)) {
			foreach($va_my_fitting as $key => $value) {
				$v_club_obj->_fitting_id = $value["fitting_id"];
				$z_view->fitting_id = $value["fitting_id"]; 
				$z_view->started = $value['started'];
			}
		}
		//$this->SaveClubSelectionCustClub($v_iid,$p_session_id);
		
		
		$va_my_clubs = $v_club_obj->getMyClubs();	
        //39c994144bb3a1c2ab0e61c028180b74		
        if (is_array($va_my_clubs) && !empty($va_my_clubs)) {
			// get shaft options for said trajectory and specified club
			// $value['model'] is the club inventory id make sure you get this value here 
			$club_types = "";
			$club_models = "";
			foreach($va_my_clubs as $key => $value) {
				$club_types = $value['club_type'] ; 
				$club_models = $value['model'] ;
				$club_ids = $value['club_id'];
				$value['club_type'] = ''; // Hosam doesn't need this anymore commented by Luis
			}
			$z_view->club_models = $club_models;
			//$z_view->club_types = $club_types;// Hosam doesn't need this anymore commented by Luis
			$z_view->club_types = '';// Hosam doesn't need this anymore commented by Luis
			$z_view->club_ids = $club_ids;
			$va_my_shafts_options = $v_club_obj->GetShaftsCustoClub($v_iid); 
			$z_view->shaft_options = $va_my_shafts_options;
			// get grips for said club model
			$va_my_grips = $v_club_obj->GetGripsClubs($v_iid,"");
			$z_view->grips = $va_my_grips;
			// is this club a set and if so, return the price list. 
			// if the result is empty, it's not set.
			$va_club_set = $v_club_obj->getClubSet($v_iid);
//				$z_view->is_club_set = false;
			$z_view->club_set_pricing = array();
			$z_view->club_set = $va_club_set;
			if (is_array($va_club_set) && !empty($va_club_set)) {
				$z_view->is_club_set = true;
				$z_view->club_set_pricing = $va_club_set;
			} 
			// what services are available for 'this' club
			$va_services = array();
			$va_services = $v_club_obj->getAvailableServices($v_iid,NULL);
			$z_view->va_services = $va_services;
			#print_r($va_services);
			$z_view->value = $value; // this is the getMyClubs $value...
			#die(print_r($z_view->value));
			return $z_view->render("CustomClub/CustomClubWidge.phtml");
			
			$z_view->initial = 1; // do not show the select hand on future rows
		} else {
			//will return now the next line the orginial is the one before but there are problem
		    return $z_view->render("CustomClub/CustomClubWidge.phtml");
		}
		//return $return ;
		//echo $z_view->render("CustomClub/cc_customize_footer.phtml");
	}
	
	/******************************
	 * Function: SaveCustomizationsCusClub
	 * Purpose: Saves club customizations into SQL SERVER; beast of a function
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: savecustomizeAction
	 * Comment: Finished this on the morning of the 2010 Masters 1st day ;-)
	 * Comment Date: 4/8/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function SaveCustomizationsCusClub ( $p_customize_parameters ) {
	
	    /*echo "<a href=\"javascript:ShowCCStep('ps',5,'" . session_id() . "')\">
	     <strong>
	     <img src=\"/images/ccm/ccf_step2_on.gif\" alt=\"Customized\" title=\"Customized\" height=\"19\" width=\"21\">
	     <span style=\"vertical-align: 4px;\">Customized</span>
	     </strong>
	     </a>
	     ";*/
	
	    global $mssql_db;

	    $z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
	    $z_view->order_type = 'S'; // SmartFit
	    $z_view->step = 6;
	    $z_view->va_fitting = $this->_va_fitting;
	    $z_view->session_id = $this->_session_id;
	    $v_club_obj = new Club ();
	
	    $p_customize_parameters = str_replace("@@@@","&",$p_customize_parameters);

	    // seperate the generic club things such as shaft, grip, lie angle,
	    // from the club head specifics such as
	    //		club head selection, dexterity chosen, and bottom line price per club (club head count * ind or set price if whole set * shaft cost * grip cost)
	
	    // #0 split the parameters up into manageable pieces and pull out the necessary pieces
	    $v_parameters = split("&",$p_customize_parameters); // becomes $v_parameters['something'] = somevalue;
	    #foreach($v_parameters as $k=>$para) {
	    #	echo $k . "->" . $para . "<BR>";
	    #}
	
	    #echo "<pre>";
	    #print_r($v_parameters);
	    #echo "</pre>";
	    #echo "<hr>";
	    #die ('eof');
	
	    foreach($v_parameters as $v_index => $v_pair) {
	        $v_set = split("=",$v_pair);
	        if ($v_set[0] == "club_types") {
	            $club_types = $v_set[1];
	        }
	        if ($v_set[0] == "club_models") {
	            $club_models = $v_set[1];
	        }
	        if ($v_set[0] == "club_ids") {
	            $club_ids = $v_set[1];
	        }
	    }
	    // #1 for each club_types and club_models per this fitting session update the club specifics
	    $v_club_type = array();
	    $v_club_type[0] = $club_types;
	    if (strpos($club_types,",")>0) {
	        $v_club_type = split(",",$club_types);
	    }
	
	    #print_r($v_club_type);
	    #echo "<hr>";
	
	    $v_club_model = array();
	    $v_club_model[0] = $club_models;
	    if (strpos($club_models,",")>0) {
	        $v_club_model = split(",",$club_models);
	    }
	
	    #print_r($v_club_model);
	    #echo "<hr>";
	
	
	    $v_club_ids = array();
	    $v_club_ids[0] = $club_ids;
	    if (strpos($club_ids,",")>0) {
	        $v_club_ids = split(",",$club_ids);
	    }
	
	
	
	    // step 1 goals: fill in the following data columns per club
	    // SHAFT_MODEL, SHAFT_MODEL_PRICE, SHAFT_FLEX, SHAFT_CLUB_LENGTH, LIE_ANGLE, GRIP_SIZE, GRIP_MODEL, GRIP_MODEL_PRICE, SPECIAL_INSTRUCTIONS, SERIAL_NUMBER
	    $v_dexterity = $this->extractValues($v_parameters,"dexterity");
	
	    //foreach ($v_parameters as $key => $value)
	    //	echo $key . "=>" . $value . "\r\n"; // for javascript export readability
	
	        //	echo $this->extractValues($v_parameters,$v_shaft_id . "_shaft_flex") . "...";
	        /*
	    echo "LUIS: \n";
	    print_r($v_parameters);
	    echo "LUIS: \n";
	    */
	        	
	        $v_shaft_id = $this->extractValues($v_parameters,"_shaft_id");
	        $v_shaft_model = $this->extractValues($v_parameters, "_shaft_id_name_" . $v_shaft_id);
	        $v_shaft_model_price = $this->extractValues($v_parameters,"_shaft_id_price_" . $v_shaft_id);
	        $v_shaft_flex = $this->extractValues($v_parameters,$v_shaft_id . "_shaft_flex");
	        $v_shaft_id_combo = $this->extractValues($v_parameters, "_shaft_id_combo");
	        $v_shaft_model_combo = $this->extractValues($v_parameters, "_shaft_id_name_" . $v_shaft_id_combo . "_combo");
	        $v_shaft_model_price_combo = $this->extractValues($v_parameters,"_shaft_id_price_" . $v_shaft_id_combo . "_combo");
	        $v_shaft_flex_combo = $this->extractValues($v_parameters,$v_shaft_id_combo . "_shaft_flex_combo");
	        $v_shaft_club_length = $this->extractValues($v_parameters,"_club_length");
	        if ($v_shaft_club_length < 0) {
	            $v_shaft_club_length = $v_shaft_club_length . "\" Shorter";
	        } elseif ($v_shaft_club_length == 0) {
	            $v_shaft_club_length = "Standard";
	        } else {
	            $v_shaft_club_length = $v_shaft_club_length . "\" Longer";
	        }
	        $v_lie_angle = $this->extractValues($v_parameters,"_lie_angle");
	        $v_grip_size = $this->extractValues($v_parameters,"_grip_size");
	        $v_grip_size = "+". trim($v_grip_size);
	        $v_grip_id = $this->extractValues($v_parameters,"_grip_id");
	        $v_grip_model = $this->extractValues($v_parameters,"_grip_name_id_" . $v_grip_id);
	        $v_grip_model_price = $this->extractValues($v_parameters,"_grip_id_" . $v_grip_id);
	        $v_special_instructions = $this->extractValues($v_parameters, "_addi_instru");
	        $v_serial_number = addslashes ($this->extractValues($v_parameters, "_serial_number"));
	        $v_service_price = addslashes ($this->extractValues($v_parameters, "_service_price"));
	        // step 1 goal complete
	        $v_sql = "
			UPDATE
				direct.dbo.gsi_cust_clubs
			SET
				dexterity = '" . $v_dexterity . "',
				shaft_model = '" . str_replace("'","''",$v_shaft_model) . "',
				shaft_model_price = '" . $v_shaft_model_price . "',
				shaft_flex = '" . $v_shaft_flex . "',
				shaft_model_combo = '" . str_replace("'","''",$v_shaft_model_combo) . "',
				shaft_model_price_combo = '" . str_replace("'","''",$v_shaft_model_price_combo) . "',
				shaft_flex_combo = '" . str_replace("'","''",$v_shaft_flex_combo) . "',
				shaft_club_length = '" . str_replace("'","''",$v_shaft_club_length) . "',
				lie_angle = '" . $v_lie_angle . "',
				grip_size = '" . $v_grip_size . "',
				grip_model = '" . str_replace("'","''",$v_grip_model) . "',
				grip_model_price = '" . $v_grip_model_price . "',
				special_instructions = '" . str_replace("'","''",$v_special_instructions) . "',
				serial_number = '" . $v_serial_number . "',
				service_price = '" . $v_service_price . "'
			FROM
				direct.dbo.gsi_cust_clubs AS clubs INNER JOIN
					direct.dbo.gsi_cust_club_fitting AS fitting ON
						clubs.fitting_id = fitting.fitting_id
			WHERE
				clubs.model = '" . $v_club_model[0] . "' and
				fitting.session_id = '" . $this->_session_id . "'";
	        	
	        // execute the query
	        $v_result = mssql_query ( $v_sql );
        	
	        if (!$v_result) {
	            ;//$this->SendError($v_sql,"SaveCustomizations");
	        }
	    //die("!");
	    	
	    // now for the individual club head selections and the pricing therein.
	    // club head, dexterity, and price is stored here. // dexterity could probably go back to the main clubs table
	    // step 1 clean up any existing club head selections first. easier than having to detect and conditionally repair
	    // remove the next query no need Mar. 3rd,2016
 	    $v_sql = "
			DELETE
				direct.dbo.gsi_cust_club_selection
			FROM
				direct.dbo.gsi_cust_club_selection AS selection INNER JOIN
					direct.dbo.gsi_cust_clubs AS clubs ON selection.club_id = clubs.club_id INNER JOIN
					direct.dbo.gsi_cust_club_fitting AS fitting ON 	clubs.fitting_id = fitting.fitting_id
			WHERE
				fitting.session_id = '" . $this->_session_id . "'";
	
	    // execute the query
	    $v_result = mssql_query ( $v_sql );

	    if (!$v_result) {
	        ;//$this->SendError($v_sql);
	    } 
   	
	    // step 2 clean up services that could have been added on
	    // remove the next query no need Mar. 3rd,2016
	     $v_sql = "
			DELETE
				direct.dbo.gsi_cust_club_services
			FROM
				direct.dbo.gsi_cust_club_services AS services INNER JOIN
					direct.dbo.gsi_cust_clubs AS clubs ON services.club_id = clubs.club_id INNER JOIN
					direct.dbo.gsi_cust_club_fitting AS fitting ON 	clubs.fitting_id = fitting.fitting_id
			WHERE
				fitting.session_id = '" . $this->_session_id . "'";

	    // execute the query
	    $v_result = mssql_query ( $v_sql );
	
	    if (!$v_result) {
	        ;//$this->SendError($v_sql);
	    } 
	    $v_total_amount = 0;
	    $v_total_club_count = 0;
	    $v_selected_club_count = 0;
	    $v_grand_club_count = 0;
	    $v_grand_total_amount = 0;
	    $v_dexterity = $this->extractValues($v_parameters,"dexterity");
	
	    //foreach($v_club_type as $key=>$value) { // drivers, hybrids, irons, wedges, etc.
	        	
	        $v_ind_price = 0;
	        $v_set_price = 0;
	        $v_selected_club_count = 0;
	        $v_total_amount = 0;
	        $v_club_id = $v_club_ids[0];
	        	
	        //$value = str_replace("/","",$value);
	        	
	        // club set string
	        $v_selected_clubs = "";
	        	
	        // club head count
	        $v_club_head_count = $this->extractValues($v_parameters,"_club_head_index");
	        for ($v_c=0; $v_c<$v_club_head_count;$v_c++) {
	            if ($this->extractValues($v_parameters,"_club_head_id_" . $v_c . "_checked") == true) {
	                // go through the list of selected clubs and make a list
	                $v_selected_clubs .= $this->extractValues($v_parameters,"_club_head_name_" . $v_c) . ",";
	                // incrememnt the selected club coung
	                $v_selected_club_count++;
	            }
	        }
	        	
	        // remove the trailing comma
	        $v_selected_clubs = rtrim($v_selected_clubs,",");
	        	
	        // reset the set selected flag
	        $v_set_selected = false;
	        	
	        // loop through all available sets
	        for ($v_c=0; $v_c<$this->extractValues($v_parameters,"_set_count");$v_c++) {
	            if ($this->extractValues($v_parameters,"_" . $v_c . "_set") == $v_selected_clubs)
	                $v_set_selected = true;
	        }
	        	
	        // save club selectections
	        if ($v_selected_club_count == $v_club_head_count || $v_set_selected) { // full set selected, offer discount price
	            for ($v_c=0; $v_c<$v_club_head_count;$v_c++) {
	                if ($this->extractValues($v_parameters, "_club_head_id_" . $v_c . "_checked") == true) {
	                    $v_club_name = $this->extractValues($v_parameters, "_club_head_name_" . $v_c);
	                    $v_hybrid_flag = $this->extractValues($v_parameters,"_club_head_hybrid_flag_" . $v_c);
	                    $v_set_price = $this->extractValues($v_parameters,"_club_head_set_price_" . $v_c);
	                    if ($v_set_price == 0) // use the standard price
	                        $v_set_price = $this->extractValues($v_parameters,"_club_head_ind_price_" . $v_c);

	                    $v_sql = "
							INSERT INTO
								direct.dbo.gsi_cust_club_selection
							(
								club_id,
								hybrid_flag,
								club_selection,
								price
							)
								VALUES
							(
								'" . $v_club_id . "',
								'" . $v_hybrid_flag . "',
								'" . $v_club_name . "',
								'" . $v_set_price . "'
							)
						";

	                    // execute the query
	                    $v_result = mssql_query ( $v_sql );

	                    if (!$v_result) {
	                        ;//$this->SendError($v_sql,"SaveCustomizations");
	                    }
	                }
	            }
	        } else { // individual price chosen
	            for ($v_c=0; $v_c<$v_club_head_count;$v_c++) {
	                if ($this->extractValues($v_parameters, "_club_head_id_" . $v_c . "_checked") == true) {
	                    $v_hybrid_flag = $this->extractValues($v_parameters,"_club_head_hybrid_flag_" . $v_c);
	                    $v_club_name = $this->extractValues($v_parameters, "_club_head_name_" . $v_c);
	                    $v_ind_price = $this->extractValues($v_parameters,"_club_head_ind_price_" . $v_c);

	                    $v_sql = "
							INSERT INTO
								direct.dbo.gsi_cust_club_selection
							(
								club_id,
								hybrid_flag,
								club_selection,
								price
							)
								VALUES
							(
								'" . $v_club_id . "',
								'" . $v_hybrid_flag . "',
								'" . $v_club_name . "',
								'" . $v_ind_price . "'
							)
						";

	                    // execute the query
	                    $v_result = mssql_query ( $v_sql );

	                    if (!$v_result) {
	                        ;//$this->SendError($v_sql,"SaveCustomizations");
	                    }
	                }
	            }
	        }
	
	        $servicesSelected = array();
	        // save services and their little nuances
	        $v_services_count = $this->extractValues($v_parameters,"_services_count"); // how many services are we lookin for?
	        for ($v_c=0; $v_c<$v_services_count;$v_c++) { // go through them
	            if ($this->extractValues($v_parameters,"_service_" . $v_c . "_checked") == true) { // they've elected this service
	                	
	                	
	                // what service is it? (i.e. Etching, Grinding, Fast Forward, Puring, Stabilization, etc.)
	                $v_service = $this->extractValues($v_parameters,"_service_" . $v_c);
	                
	                $servicesSelected[] = $this->extractValues($v_parameters, "_service_" . $v_c . "_id");
	                	
	                // now that all the values from choices are made, let's grab the regular feilds values which the user has checked.
	                #echo $value . "_service_" . $v_c . " = " . $v_service . "<BR>";
	                	
	                # $v_club_id (is as is) it's already solved for us (current club id in this big loop)
	                # description = $v_service
	                # user_action = NULL
	                # attribute_name = $value . "_service_" . $v_c
	                # display_label = NULL
	                # service_level = $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_service_level")
	                # value = $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_price")
	                	
	                $v_sql = "
						INSERT INTO
							direct.dbo.gsi_cust_club_services
						(
							club_id,
							description,
							user_action,
							attribute_name,
							display_label,
							service_level,
							value
						)
							VALUES
						(
							'" . $v_club_id . "',
							'" . $v_service . "',
							'',
							'" . "_service_" . $v_c . "',
							'',
							'" . $this->extractValues($v_parameters,"_service_" . $v_c . "_service_level") . "',
							'" . $this->extractValues($v_parameters,"_service_" . $v_c . "_price") . "'
						)
					";
	                	
	                // execute the query
	                $v_result = mssql_query ( $v_sql );

	                if (!$v_result) {
	                    ;//$this->SendError($v_sql,"SaveCustomizations");
	                }
	                	
	                // what are the choices should I be looking for?
	                $va_services = $v_club_obj->getAvailableServicesforSaving($v_club_model[0],$v_service);
	                if (is_array($va_services) && !empty($va_services)) {
	                    foreach($va_services as $field => $content) {
	                        // if attribute name has something (technically or user_action) is not empty, then it means user input by choice or text line
	                        # $v_club_id (is as is) it's already solved for us (current club id in this big loop)
	                        # description = $v_service
	                        # user_action = $content['user_action']; // CHOOSE, or TYPE
	                        # attribute_name = $content['attribute_name']
	                        # display_label = $content['display_label']; // Font, Case, Line 1, etc.
	                        # service_level = $content['service_level'];
	                        # value = $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_" . $content['attribute_name'])
	                        if ($content['attribute_name'] != "") {
	                            $v_sql = "
								INSERT INTO
									direct.dbo.gsi_cust_club_services
								(
									club_id,
									description,
									user_action,
									attribute_name,
									display_label,
									service_level,
									value
								)
									VALUES
								(
									'" . $v_club_id . "',
									'" . $v_service . "',
									'" . $content['user_action'] . "',
									'" . $content['attribute_name'] . "',
									'" . $content['display_label'] . "',
									'" . $content['service_level'] . "',
									'" . $this->extractValues($v_parameters, "_service_" . $v_c . "_" . $content['attribute_name']) . "'
								)
							";
	                            	
	                            // execute the query
	                            $v_result = mssql_query ( $v_sql );

	                            if (!$v_result) {
	                                ;//$this->SendError($v_sql,"SaveCustomizations");
	                            }
	                            #echo $value . "_service_" . $v_c . "_" . $content['attribute_name'] . " = " . $this->extractValues($v_parameters, "_service_" . $v_c . "_" . $content['attribute_name']) . "<BR>";
	                        }
	                    }
	                }
	                	
	                	
	            }
	        }
	        #echo "<pre>";
	        #print_r($v_parameters);
	        #echo "</pre>";
	        #echo "<hr>";
	        $_SESSION['servicesSelected'] = $servicesSelected;
	        	
	    //}
	    #die ('eof');
	}
	
	/******************************
	 * Function: SaveCustomizations
	 * Purpose: Saves club customizations into SQL SERVER; beast of a function 
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: savecustomizeAction
	 * Comment: Finished this on the morning of the 2010 Masters 1st day ;-)
	 * Comment Date: 4/8/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function SaveCustomizations ( $p_customize_parameters ) {
		
		/*echo "<a href=\"javascript:ShowCCStep('ps',5,'" . session_id() . "')\">
      					<strong>
	      					<img src=\"/images/ccm/ccf_step2_on.gif\" alt=\"Customized\" title=\"Customized\" height=\"19\" width=\"21\"> 
	      					<span style=\"vertical-align: 4px;\">Customized</span>
      					</strong>
      				</a>
		";*/
		
		global $mssql_db; 
		
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		$z_view->order_type = 'S'; // SmartFit
		$z_view->step = 6;
		$z_view->va_fitting = $this->_va_fitting;
		$z_view->session_id = $this->_session_id;
		$v_club_obj = new Club ();
		
		$p_customize_parameters = str_replace("@@@@","&",$p_customize_parameters);
		
		// seperate the generic club things such as shaft, grip, lie angle, 
		// from the club head specifics such as 
		//		club head selection, dexterity chosen, and bottom line price per club (club head count * ind or set price if whole set * shaft cost * grip cost)

		// #0 split the parameters up into manageable pieces and pull out the necessary pieces
		$v_parameters = split("&",$p_customize_parameters); // becomes $v_parameters['something'] = somevalue;
		
		#foreach($v_parameters as $k=>$para) {
		#	echo $k . "->" . $para . "<BR>";
		#}
		
		#echo "<pre>";
		#print_r($v_parameters);
		#echo "</pre>";
		#echo "<hr>";
		
		#die ('eof');
		
		foreach($v_parameters as $v_index => $v_pair) {
			$v_set = split("=",$v_pair);
			if ($v_set[0] == "club_types") {
				$club_types = $v_set[1];
			}
			if ($v_set[0] == "club_models") {
				$club_models = $v_set[1];
			}
			if ($v_set[0] == "club_ids") {
				$club_ids = $v_set[1];
			}
		}
	
		// #1 for each club_types and club_models per this fitting session update the club specifics
		$v_club_type = array();
		$v_club_type[0] = $club_types;
		if (strpos($club_types,",")>0) {
			$v_club_type = split(",",$club_types);
		}
		
		#print_r($v_club_type);
		#echo "<hr>";
		
		$v_club_model = array();
		$v_club_model[0] = $club_models;
		if (strpos($club_models,",")>0) {
			$v_club_model = split(",",$club_models);
		}
		
		#print_r($v_club_model);
		#echo "<hr>";
		
		
		$v_club_ids = array();
		$v_club_ids[0] = $club_ids;
		if (strpos($club_ids,",")>0) {
			$v_club_ids = split(",",$club_ids);
		}
		
		
		// step 1 goals: fill in the following data columns per club
		// SHAFT_MODEL, SHAFT_MODEL_PRICE, SHAFT_FLEX, SHAFT_CLUB_LENGTH, LIE_ANGLE, GRIP_SIZE, GRIP_MODEL, GRIP_MODEL_PRICE, SPECIAL_INSTRUCTIONS, SERIAL_NUMBER
		$v_dexterity = $this->extractValues($v_parameters,"dexterity");

		//foreach ($v_parameters as $key => $value)
		//	echo $key . "=>" . $value . "\r\n"; // for javascript export readability
		
		
		
		
		foreach($v_club_type as $key=>$value) {
		//	echo $this->extractValues($v_parameters,$v_shaft_id . "_shaft_flex") . "...";
			
			$value = str_replace("/","",$value);
			
			$v_shaft_id = $this->extractValues($v_parameters,$value . "_shaft_id");
			$v_shaft_model = $this->extractValues($v_parameters,$value . "_shaft_id_name_" . $v_shaft_id);
			$v_shaft_model_price = $this->extractValues($v_parameters,$value . "_shaft_id_price_" . $v_shaft_id);
			$v_shaft_flex = $this->extractValues($v_parameters,$v_shaft_id . "_shaft_flex");
			$v_shaft_id_combo = $this->extractValues($v_parameters,$value . "_shaft_id_combo");
			$v_shaft_model_combo = $this->extractValues($v_parameters,$value . "_shaft_id_name_" . $v_shaft_id_combo . "_combo");
			$v_shaft_model_price_combo = $this->extractValues($v_parameters,$value . "_shaft_id_price_" . $v_shaft_id_combo . "_combo");
			$v_shaft_flex_combo = $this->extractValues($v_parameters,$v_shaft_id_combo . "_shaft_flex_combo");
			$v_shaft_club_length = $this->extractValues($v_parameters,$value . "_club_length");
			if ($v_shaft_club_length < 0) {
				$v_shaft_club_length = $v_shaft_club_length . "\" Shorter";
			} elseif ($v_shaft_club_length == 0) {
				$v_shaft_club_length = "Standard"; 
			} else {
				$v_shaft_club_length = $v_shaft_club_length . "\" Longer";
			}
			$v_lie_angle = $this->extractValues($v_parameters,$value . "_lie_angle");
			$v_grip_size = $this->extractValues($v_parameters,$value . "_grip_size");
				$v_grip_id = $this->extractValues($v_parameters,$value . "_grip_id");
			$v_grip_model = $this->extractValues($v_parameters,$value . "_grip_name_id_" . $v_grip_id);
			$v_grip_model_price = $this->extractValues($v_parameters,$value . "_grip_id_" . $v_grip_id);
			$v_special_instructions = $this->extractValues($v_parameters,$value . "_addi_instru");
			$v_serial_number = $this->extractValues($v_parameters,$value . "_serial_number");
			$v_service_price = $this->extractValues($v_parameters,$value . "_service_price");
			
			// step 1 goal complete
			$v_sql = "
			UPDATE 
				direct.dbo.gsi_cust_clubs
			SET
				dexterity = '" . $v_dexterity . "',
				shaft_model = '" . str_replace("'","''",$v_shaft_model) . "', 
				shaft_model_price = '" . $v_shaft_model_price . "', 
				shaft_flex = '" . $v_shaft_flex . "', 
				shaft_model_combo = '" . str_replace("'","''",$v_shaft_model_combo) . "', 
				shaft_model_price_combo = '" . str_replace("'","''",$v_shaft_model_price_combo) . "', 
				shaft_flex_combo = '" . str_replace("'","''",$v_shaft_flex_combo) . "', 
				shaft_club_length = '" . str_replace("'","''",$v_shaft_club_length) . "', 
				lie_angle = '" . $v_lie_angle . "', 
				grip_size = '" . $v_grip_size . "', 
				grip_model = '" . str_replace("'","''",$v_grip_model) . "', 
				grip_model_price = '" . $v_grip_model_price . "', 
				special_instructions = '" . str_replace("'","''",$v_special_instructions) . "', 
				serial_number = '" . $v_serial_number . "',
				service_price = '" . $v_service_price . "'
			FROM         
				direct.dbo.gsi_cust_clubs AS clubs INNER JOIN
					direct.dbo.gsi_cust_club_fitting AS fitting ON 
						clubs.fitting_id = fitting.fitting_id 
			WHERE
				clubs.model = '" . $v_club_model[$key] . "' and
				fitting.session_id = '" . $this->_session_id . "'";
	
			// execute the query
			$v_result = mssql_query ( $v_sql );
			
			if (!$v_result) {
				;//$this->SendError($v_sql,"SaveCustomizations");
			}
		}
		//die("!");
			
		// now for the individual club head selections and the pricing therein.
		// club head, dexterity, and price is stored here. // dexterity could probably go back to the main clubs table
		// step 1 clean up any existing club head selections first. easier than having to detect and conditionally repair
			$v_sql = "
			DELETE 
				direct.dbo.gsi_cust_club_selection
			FROM
				direct.dbo.gsi_cust_club_selection AS selection INNER JOIN
					direct.dbo.gsi_cust_clubs AS clubs ON selection.club_id = clubs.club_id INNER JOIN
					direct.dbo.gsi_cust_club_fitting AS fitting ON 	clubs.fitting_id = fitting.fitting_id 
			WHERE
				fitting.session_id = '" . $this->_session_id . "'";
				
			// execute the query
			$v_result = mssql_query ( $v_sql );
		
			if (!$v_result) {
				;//$this->SendError($v_sql);
			}
			
		// step 2 clean up services that could have been added on
			$v_sql = "
			DELETE 
				direct.dbo.gsi_cust_club_services
			FROM
				direct.dbo.gsi_cust_club_services AS services INNER JOIN
					direct.dbo.gsi_cust_clubs AS clubs ON services.club_id = clubs.club_id INNER JOIN
					direct.dbo.gsi_cust_club_fitting AS fitting ON 	clubs.fitting_id = fitting.fitting_id 
			WHERE
				fitting.session_id = '" . $this->_session_id . "'";
				
			// execute the query
			$v_result = mssql_query ( $v_sql );
		
			if (!$v_result) {
				;//$this->SendError($v_sql);
			}

		$v_total_amount = 0;
		$v_total_club_count = 0;
		$v_selected_club_count = 0;
		$v_grand_club_count = 0;
		$v_grand_total_amount = 0;
		$v_dexterity = $this->extractValues($v_parameters,"dexterity");
		
		foreach($v_club_type as $key=>$value) { // drivers, hybrids, irons, wedges, etc.
			
			$v_ind_price = 0;
			$v_set_price = 0;
			$v_selected_club_count = 0;
			$v_total_amount = 0;
			$v_club_id = $v_club_ids[$key];
			
			$value = str_replace("/","",$value);
			
			// club set string
			$v_selected_clubs = "";
			
			// club head count
			$v_club_head_count = $this->extractValues($v_parameters,$value . "_club_head_index");
			for ($v_c=0; $v_c<$v_club_head_count;$v_c++) {
				if ($this->extractValues($v_parameters,$value . "_club_head_id_" . $v_c . "_checked") == true) {
					// go through the list of selected clubs and make a list 
					$v_selected_clubs .= $this->extractValues($v_parameters,$value . "_club_head_name_" . $v_c) . ",";
					// incrememnt the selected club coung
					$v_selected_club_count++;
				}
			}
			
			// remove the trailing comma
			$v_selected_clubs = rtrim($v_selected_clubs,",");
			
			// reset the set selected flag
			$v_set_selected = false;
			
			// loop through all available sets
			for ($v_c=0; $v_c<$this->extractValues($v_parameters,$value . "_set_count");$v_c++) {
				if ($this->extractValues($v_parameters,$value . "_" . $v_c . "_set") == $v_selected_clubs)
					$v_set_selected = true;
			}
			
			// save club selectections
			if ($v_selected_club_count == $v_club_head_count || $v_set_selected) { // full set selected, offer discount price
				for ($v_c=0; $v_c<$v_club_head_count;$v_c++) {
					if ($this->extractValues($v_parameters,$value . "_club_head_id_" . $v_c . "_checked") == true) {
						$v_club_name = $this->extractValues($v_parameters,$value . "_club_head_name_" . $v_c);
						$v_hybrid_flag = $this->extractValues($v_parameters,$value . "_club_head_hybrid_flag_" . $v_c);
						$v_set_price = $this->extractValues($v_parameters,$value . "_club_head_set_price_" . $v_c);
						if ($v_set_price == 0) // use the standard price
							$v_set_price = $this->extractValues($v_parameters,$value . "_club_head_ind_price_" . $v_c);
						$v_sql = "
							INSERT INTO 
								direct.dbo.gsi_cust_club_selection
							(
								club_id,
								hybrid_flag,
								club_selection,
								price
							)
								VALUES
							(
								'" . $v_club_id . "',
								'" . $v_hybrid_flag . "',
								'" . $v_club_name . "', 
								'" . $v_set_price . "'
							)
						";	
						

						// execute the query
						$v_result = mssql_query ( $v_sql );
		
						if (!$v_result) {
							;//$this->SendError($v_sql,"SaveCustomizations");
						}
					}
				}					
			} else { // individual price chosen
				for ($v_c=0; $v_c<$v_club_head_count;$v_c++) {
					if ($this->extractValues($v_parameters,$value . "_club_head_id_" . $v_c . "_checked") == true) {
						$v_hybrid_flag = $this->extractValues($v_parameters,$value . "_club_head_hybrid_flag_" . $v_c);
						$v_club_name = $this->extractValues($v_parameters,$value . "_club_head_name_" . $v_c);
						$v_ind_price = $this->extractValues($v_parameters,$value . "_club_head_ind_price_" . $v_c);
						$v_sql = "
							INSERT INTO 
								direct.dbo.gsi_cust_club_selection
							(
								club_id,
								hybrid_flag,
								club_selection,
								price
							)
								VALUES
							(
								'" . $v_club_id . "',
								'" . $v_hybrid_flag . "',
								'" . $v_club_name . "', 
								'" . $v_ind_price . "'
							)
						";
						/*
						echo "Query 1: <br />";
						echo "<pre>";
						print_r($v_sql);
						echo "<pre>";
                        */
						// execute the query
						$v_result = mssql_query ( $v_sql );
		
						if (!$v_result) {
							;//$this->SendError($v_sql,"SaveCustomizations");
						}
					}
				}
			}  

			$servicesSelected = array();
			// save services and their little nuances
			$v_services_count = $this->extractValues($v_parameters,$value . "_services_count"); // how many services are we lookin for?
			for ($v_c=0; $v_c<$v_services_count;$v_c++) { // go through them
				if ($this->extractValues($v_parameters,$value . "_service_" . $v_c . "_checked") == true) { // they've elected this service
					
					
					// what service is it? (i.e. Etching, Grinding, Fast Forward, Puring, Stabilization, etc.)
					$v_service = $this->extractValues($v_parameters,$value . "_service_" . $v_c);
					
					$servicesSelected[] = $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_id");

					
					// now that all the values from choices are made, let's grab the regular feilds values which the user has checked.
					#echo $value . "_service_" . $v_c . " = " . $v_service . "<BR>";
					
					# $v_club_id (is as is) it's already solved for us (current club id in this big loop)
					# description = $v_service
					# user_action = NULL
					# attribute_name = $value . "_service_" . $v_c
					# display_label = NULL
					# service_level = $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_service_level")
					# value = $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_price")			
					
					$v_sql = "
						INSERT INTO 
							direct.dbo.gsi_cust_club_services
						(
							club_id,
							description,
							user_action,
							attribute_name,
							display_label,
							service_level,
							value
						)
							VALUES
						(
							'" . $v_club_id . "',
							'" . $v_service . "', 
							'',
							'" . $value . "_service_" . $v_c . "', 
							'',
							'" . $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_service_level") . "',
							'" . $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_price") . "'
						)
					";	

					// execute the query
					$v_result = mssql_query ( $v_sql );
		
					if (!$v_result) {
						;//$this->SendError($v_sql,"SaveCustomizations");
					}
					
					// what are the choices should I be looking for?
					$va_services = $v_club_obj->getAvailableServicesforSaving($v_club_model[$key],$v_service);
					if (is_array($va_services) && !empty($va_services)) {
						foreach($va_services as $field => $content) {
							// if attribute name has something (technically or user_action) is not empty, then it means user input by choice or text line
							# $v_club_id (is as is) it's already solved for us (current club id in this big loop)
							# description = $v_service
							# user_action = $content['user_action']; // CHOOSE, or TYPE
							# attribute_name = $content['attribute_name']
							# display_label = $content['display_label']; // Font, Case, Line 1, etc.
							# service_level = $content['service_level'];
							# value = $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_" . $content['attribute_name'])
							if ($content['attribute_name'] != "") {
								$v_sql = "
								INSERT INTO 
									direct.dbo.gsi_cust_club_services
								(
									club_id,
									description,
									user_action,
									attribute_name,
									display_label,
									service_level,
									value
								)
									VALUES
								(
									'" . $v_club_id . "',
									'" . $v_service . "', 
									'" . $content['user_action'] . "',
									'" . $content['attribute_name'] . "', 
									'" . $content['display_label'] . "',
									'" . $content['service_level'] . "',
									'" . $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_" . $content['attribute_name']) . "'
								)
							";	
							
							// execute the query
							$v_result = mssql_query ( $v_sql );
							if (!$v_result) {
								;//$this->SendError($v_sql,"SaveCustomizations");
							}
							#echo $value . "_service_" . $v_c . "_" . $content['attribute_name'] . " = " . $this->extractValues($v_parameters,$value . "_service_" . $v_c . "_" . $content['attribute_name']) . "<BR>";	
						    }
						}
					} 
					
					
				}
			} 
			#echo "<pre>";
			#print_r($v_parameters);
			#echo "</pre>";
			#echo "<hr>";
			$_SESSION['servicesSelected'] = $servicesSelected;
			
		}	
		#die ('eof');	
	}
	
	/******************************
	 * Function: extractValues
	 * Purpose: When the parameter string comes in from the customization step to save, it needs to be parsed and looking for specific variable names
	 * 	that are composed of other form variable values. This runction accepts the string to parse through, a preformatted varialbe name to look for, 
	 * 	and returns the value associated with that lookup
	 * Called From::
	 * 	Model: this
	 * 	Function: SaveCustomizations
	 * Comment Date: 4/7/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function extractValues($p_parameters,$variable) {
		foreach($p_parameters as $v_index => $v_pair) {
			$v_set = split("=",$v_pair);
			if ($v_set[0] == $variable) {
				return $v_set[1];
			} 
		}
	}
	
	/******************************
	 * Function: DisplayReview
	 * Purpose: Displays the final step in a rendered review format club interface. All this is is a review of step 2/5 of customizations
	 * Called From::
	 * 	Web: /customclub/customize ~ /javascript/custom_club.js
	 *  Function: continueToReview
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function DisplayReview($p_session_id) {
		
		$z_view = new Zend_View ( array ('scriptPath' => VIEW_PATH ) );
		$z_view->order_type = 'S'; // SmartFit
		$z_view->step = 6;
		$z_view->va_fitting = $this->_va_fitting;
		echo $z_view->render ( "CustomClub/cc_review_header.phtml" );
	
		$va_my_clubs = array ();
		
		// get my saved settings
		$v_club_obj = new Club ();
		$v_club_obj->_session_id = $p_session_id;
		$va_my_fitting = $v_club_obj->getMyFitting();
		
				 
		 if (is_array($va_my_fitting) && !empty($va_my_fitting)) {
		 	foreach($va_my_fitting as $key => $value) {
				$v_club_obj->_fitting_id = $value["fitting_id"];
				$z_view->fitting_id = $value["fitting_id"]; 
				$z_view->smart_fit_flag = $value["smart_fit_flag"];
				$z_view->club_category = $value['club_category'];
				$z_view->player_height = $value['player_height'];
				$z_view->wrist_to_floor = $value['wrist_to_floor'];
				$z_view->hand_size = $value['hand_size'];
				$z_view->finger_size = $value['finger_size'];
				$z_view->swing_speed = $value['swing_speed'];
				$z_view->driver_distance = $value['driver_distance'];
				$z_view->trajectory = $value['trajectory'];
				$z_view->tempo = $value['tempo'];
				$z_view->target_swing = $value['target_swing'];
				$z_view->started = $value['started'];
			}
		} else {
			$z_view->error_message = "Your fitting session has not been established";
			echo $z_view->render ( "CustomClub/cc_review_body.phtml" );
		}
		 
		// now get all my clubs that I selected in step 1
		$va_my_clubs = $v_club_obj->getMyClubs();
		
		#echo "<pre>";
		#print_r($va_my_clubs);
		#print_r($this->_va_fitting);
		#echo "</pre>";
		
		// for each club selected display the customized settings.
		if (is_array($va_my_clubs) && !empty($va_my_clubs)) {
			$z_view->initial = 0;
			// grab all club types in this selection for use in club angles so we know which
			// club_lie_angles to loop through when setting dexterity
			$club_types = "";
			$club_models = "";
			foreach($va_my_clubs as $key => $value) {
				$club_types .= $value['club_type'] . ",";
				$club_models .= $value['model'] . ",";
				$club_ids .= $value['club_id'] . ",";
			}
			$club_types = rtrim($club_types,",");
			$club_models = rtrim($club_models,",");
			$club_ids = rtrim($club_ids,",");
			$z_view->club_models = $club_models;
			$z_view->club_types = $club_types;
			$z_view->club_ids = $club_ids;
			$z_view->total_smart_fit_price = 0;
			$z_view->total_club_count = 0;
				
			// now loop through the club selection for further customizations
			foreach($va_my_clubs as $key => $value) {

				#print_r($value);
				
				// is this club a set and if so, return the price list. 
				// if the result is empty, it's not set.
				$va_club_set = $v_club_obj->getClubSet($value['model']);
				$z_view->is_club_set = false;
				$z_view->club_set_pricing = array();
				$z_view->club_set = $va_club_set;
				if (is_array($va_club_set) && !empty($va_club_set)) {
					$z_view->is_club_set = true;
					$z_view->club_set_pricing = $va_club_set;
				} else {
					// not a club set
				}
				
				// get all club heads
				$va_selected_club_heads = $v_club_obj->getMyClubHeads($value['club_id']);
				$z_view->selected_club_heads = $va_selected_club_heads;
				 
				// get all additional services
				
				$va_additional_services = $v_club_obj->getMyClubServices($value['club_id']);
				$z_view->additional_services = $va_additional_services;
								
				// loop through the recorded prices
				if (is_array($z_view->selected_club_heads) && !empty($z_view->selected_club_heads)) {
					foreach($z_view->selected_club_heads as $head) {
						if ($head['price'] > 0) {
							$total_club_base_price = $head['price'];
							#echo "adding head price";
						} else { 
							$total_club_base_price = $this->value['retail_price'];
							#echo "adding retail price";
						}
						$z_view->total_smart_fit_price = $total_club_base_price + $value['grip_model_price'] + $value['shaft_model_price'];
					}
					$z_view->total_smart_fit_price += $value['service_price'];
					$z_view->total_club_count += sizeof($z_view->selected_club_heads);
				} else {
					$this->selected_club_heads = array(1=>1);
					$z_view->total_smart_fit_price += $value['retail_price'] + $value['grip_model_price'] + $value['shaft_model_price'] + $value['service_price'];
					$z_view->total_club_count += 1; // not part of the variable club heads but should be counteded anyway
				}
				
				$z_view->value = $value;
				#echo "<pre>";
				#print_r($value);
				#echo "</pre>";
				echo $z_view->render ( "CustomClub/cc_review_body.phtml" );
				
				$z_view->initial = 1; // do not show the select hand on future rows
			}
			
		} else {
			//$z_view->error_message = "You don't have any clubs at this time";
			echo $z_view->render ( "CustomClub/cc_review_body.phtml" );
		}
		
		echo $z_view->render ( "CustomClub/cc_review_footer.phtml" );
	}

	/*******************************
	 * Function: AddToCart
	 * Purpose: adds all club customizations to the shopping cart o.O
	 * Called From::
	 * 	Controller: controller
	 *  Function: addtocartAction
	 * Comment Date: 4/12/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function AddToCart($p_session_id) {
	
	    global $apps_db;
	    include_once(FPATH . 'fns_sc_order');
	     
	    #if (function_exists('log_end')) echo "log_end exists"; else echo "log_end does not exist. found in gsi_common.inc";
	     
	    // get my saved settings
	    $v_club_obj = new Club ();
	    $v_club_obj->_session_id = $p_session_id;
	    $va_my_fitting = $v_club_obj->getMyFitting();
	    if (is_array($va_my_fitting) && !empty($va_my_fitting)) {
	        foreach($va_my_fitting as $key => $value) {
	            $v_club_obj->_fitting_id = $value["fitting_id"];
	            $z_view->fitting_id = $value["fitting_id"];
	            $z_view->smart_fit_flag = $value["smart_fit_flag"];
	            $z_view->club_category = $value['club_category'];
	            $z_view->player_height = $value['player_height'];
	            $z_view->wrist_to_floor = $value['wrist_to_floor'];
	            $z_view->hand_size = $value['hand_size'];
	            $z_view->finger_size = $value['finger_size'];
	            $z_view->swing_speed = $value['swing_speed'];
	            $z_view->driver_distance = $value['driver_distance'];
	            $z_view->trajectory = $value['trajectory'];
	            $z_view->tempo = $value['tempo'];
	            $z_view->target_swing = $value['target_swing'];
	            $z_view->started = $value['started'];
	            $z_view->service_price = $value['service_price'];
	        }
	    }
	    	
	    // now get all my clubs that I selected in step 1
	    $va_my_clubs = array ();
	    $va_my_clubs = $v_club_obj->getMyClubs();
	
	    #die(print_r($va_my_clubs));

	    // for each club selected display the customized settings.
	    if (is_array($va_my_clubs) && !empty($va_my_clubs)) {
	        $z_view->initial = 0;
	        // grab all club types in this selection for use in club angles so we know which
	        // club_lie_angles to loop through when setting dexterity
	        $club_types = "";
	        $club_models = "";
	        foreach($va_my_clubs as $key => $value) {
	            $club_types .= $value['club_type'] . ",";
	            $club_models .= $value['model'] . ",";
	            $club_ids .= $value['club_id'] . ",";
	        }
	        $club_types = rtrim($club_types,",");
	        $club_models = rtrim($club_models,",");
	        $club_ids = rtrim($club_ids,",");
	        $z_view->club_models = $club_models;
	        $z_view->club_types = $club_types;
	        $z_view->club_ids = $club_ids;
	        $z_view->total_smart_fit_price = 0;
	        $z_view->total_club_count = 0;
	
	        // now loop through the club selection for further customizations
	        foreach($va_my_clubs as $key => $value) {
	
	            #echo "<pre>";
	            #print_r($value);
	            #echo "</pre>";
	
	
	            // is this club a set and if so, return the price list.
	            // if the result is empty, it's not set.
	            $va_club_set = $v_club_obj->getClubSet($value['model']);
	            $z_view->is_club_set = false;
	            #$z_view->club_set_pricing = array();
	            //$z_view->club_set = $va_club_set;
	            if (is_array($va_club_set) && !empty($va_club_set)) {
	                $z_view->is_club_set = true;
	                //$z_view->club_set_pricing = $va_club_set;
	            } else {
	                // not a club set
	            }
	
	            $va_selected_club_heads = $v_club_obj->getMyClubHeads($value['club_id']);
	            $z_view->selected_club_heads = $va_selected_club_heads;
	            $z_view->total_smart_fit_price = 0;
	            #die(print_r($va_selected_club_heads));
	            $va_additional_services = $v_club_obj->getMyClubServices($value['club_id']);
	            $z_view->additional_services = $va_additional_services;

	            // loop through the recorded prices
	            // alright! it's come down to this. add the custom club selections to the cart. Ready! Set! Go!!!!
	            $v_line_numb_str = "";
	            $j_line_numb_str = "";

	            if (is_array($z_view->selected_club_heads) && !empty($z_view->selected_club_heads)) {
	                // count up the
	                $hybrid_count = 0;
	                $iron_count = 0;
	                foreach($z_view->selected_club_heads as $head) {
	                    if ($head['hybrid_flag'] == "Y")
	                        $hybrid_count++;
	                    else
	                        $iron_count++;
	                }
	                $shaft_upcharge = (($value['shaft_model_price']*$iron_count)+($value['shaft_model_price_combo']*$hybrid_count));
	                foreach($z_view->selected_club_heads as $head) {
	
	                    $v_total_smart_fit_price = $head['price']+ $value['grip_model_price'] + ($shaft_upcharge/sizeof($z_view->selected_club_heads)) + ($value['service_price']/sizeof($z_view->selected_club_heads));
	                    $v_return_status = add_item_to_cart($value['model'], 1, '', '', $v_line_number);
	
	                    //die(print("..." . $v_return_status));
	
	                    if (empty($v_line_number)) {
	                        $v_curr_style = str_replace('\'','',$value['model']);
	
	                        if ($v_curr_style != $v_prev_style) {
	                            $v_no_atp_styles .= $v_no_atp_styles .  str_replace('\'','',$value['model']) . ', ';
	                            $v_prev_style = $v_curr_style;
	                        }
	                         
	                    }
	
	                    //$v_line_numb_str[$ind] .= '~'.$v_line_number[$ind];
	                    $v_line_numb_str = $v_line_numb_str . '~' . $v_line_number;
	
	                    //jmic 2/10/2010 - fix for the oem deletion on cart
	                    $j_line_numb_str = $j_line_numb_str . '~' . $v_line_number;
	
	                    $v_order_number = $_SESSION['s_order_number'];
	                    	
	                    $v_sp = mssql_init("gsi_update_oem_line_price");
	                    gsi_mssql_bind($v_sp, "@p_orig_sys_ref" , $v_order_number , "varchar", 50);
	                    gsi_mssql_bind($v_sp, "@p_line_number"  , $v_line_number  , "numeric", -1);
	                    gsi_mssql_bind($v_sp, "@p_price"	, $v_total_smart_fit_price , "gsi_price_type", -1 );
	                    gsi_mssql_bind($v_sp, "@p_price_list_id", $line_price_list[$ind]  , "numeric",-1);
	
	                    gsi_mssql_bind($v_sp, "@p_return_status" , $v_return_status , "varchar", 250 , true);
	
	                    $v_return_status[$ind] = $v_return_status;
	                    $msg ="";
	                    $msg = $msg . "@p_orig_sys_ref = ". $v_order_number;
	                    $msg = $msg . ", @p_line_number  = ". $v_line_number;
	                    $msg = $msg . ", @p_price = ". $v_total_smart_fit_price ;
	                    $msg = $msg . ", @p_price_list_id = ". $line_price_list[$ind];
	                    	
	                    $v_result = mssql_execute($v_sp);
	
	                    if (!$v_result){
	                        display_mssql_error("gsi_update_oem_line_price " . $msg, "call from oem_ocf_proc.php");
	                        $this->SendError($msg,"AddToCart");
	                    }
	                    mssql_free_statement($v_sp);
	                    mssql_free_result ($v_result);
	        
	                    $v_note_text = "";
	                    $v_note_text  .= $z_view->club_category ."\n";
	                    $v_note_text  .= "Hand: " .$value['dexterity']."\n";
	                    $v_note_text  .= "Club: " .$head['club_selection']."\n";
	                    $v_note_text  .= "Lie: " .str_replace("Standard", "Std", $value['lie_angle'])."\n";
	
	                    if ($head['hybrid_flag'] == "Y") {
	                        $v_note_text  .= "Hybrid Shaft: " . $value['shaft_model_combo'] ."\n";
	                        $v_note_text  .= "Hybrid Flex: " .str_replace("Standard", "Std", $value['shaft_flex_combo'])."\n";
	                    } else {
	                        $v_note_text  .= "Shaft: " . $value['shaft_model'] ."\n";
	                        $v_note_text  .= "Flex: " .str_replace("Standard", "Std", $value['shaft_flex'])."\n";
	                    }
	
	                    $v_note_text  .= "Length: " .str_replace("Standard", "Std", $value['shaft_club_length'])."\n";
	                    if ($value['grip_model'] == "")
	                        $value['grip_model'] = "Stock Grip";
	                    $v_note_text  .= "Grip: " .$value['grip_model']."\n";
	                    if (str_replace("Standard", "Std", $value['grip_size'] == "Select Grip Model"))
	                        $value['grip_size'] = "";
	                    if ($value['grip_size'] == "")
	                        $value['grip_size'] = "Standard";
	                    $v_note_text  .= "Grip Size: " .str_replace("Standard", "Std", $value['grip_size'])."\n";
	                    if ($value['serial_number'] != "")
	                        $v_note_text  .= "Serial Num: " . $value['serial_number']."\n";
	                    if ( (!empty($value['special_instructions'])) && ($value['special_instructions'] != 'Optional')) {
	                        $v_note_text  .= "Comments:" .str_replace("Optional", "", $value['special_instructions'])."\n";
	                    }
	                    if (!empty($store_number)) {
	                        $v_note_text  .= "Store#:" .$store_number."\n";
	                    }
	                    // services
	                    if (is_array($z_view->additional_services) && !empty($z_view->additional_services)) {
	                        $v_note_text .= $z_view->additional_services[0]['description'] . ": ";
	                        foreach($z_view->additional_services as $service => $selection) {
	                            if ($selection['display_label'] != "")
	                                $v_note_text .= $selection['display_label'] . ": ";
	                            $addon = "";
	                            $preon = "";
	                            if ($selection['user_action'] == "") { // this is a cost.
	                                if ($selection['service_level'] == "Per Order") { // this is a per model cost
	                                    $addon = "/order";
	                                    $preon = "$";
	                                } else {
	                                    $addon = "/club";
	                                    $preon = "$";
	                                }
	                            }
	                            $v_note_text .= $preon . $selection['value'] . $addon . "\n";
	                        }
	                    }
	
	
	
	                    // creating second note.
	                    // *** NoteText should not exceeeeeed  240 characters.
	                    // if exceeds, description on PO will be empty.
	
	                    $v_webprofile = C_WEB_PROFILE;
	                    $v_oem_info = 'OEM_INFO';
	
	                    $v_sp = mssql_init("gsi_cmn_order_customization_add_note");
	
	                    gsi_mssql_bind ($v_sp, "@p_profile_scope",  $v_webprofile , "varchar" ,50);
	                    gsi_mssql_bind ($v_sp, "@p_orig_sys_ref",  $v_order_number , "varchar", 50);
	                    gsi_mssql_bind ($v_sp, "@p_line_number",  $v_line_number , "bigint", -1);
	                    gsi_mssql_bind ($v_sp, "@p_note_text",  $v_note_text , "varchar", 2000);
	                    gsi_mssql_bind ($v_sp, "@p_service",  $v_oem_info , "varchar", 50);
	
	                    $msg = "";
	                    $msg .= "\n".str_pad('@p_profile_scope varchar', 40 )           . ":= '" . $v_webprofile        . "'";
	                    $msg .= "\n".str_pad('@p_orig_sys_ref  varchar', 40 )           . ":= '" . $v_order_number      . "'";
	                    $msg .= "\n".str_pad('@p_line_number    gsi_id_type', 40 )      . ":= '" . $v_line_number[$ind] . "'";
	                    $msg .= "\n".str_pad('@p_note_text varchar', 40 )               . ":= '" . $v_note_text         . "'";
	                    $msg .= "\n".str_pad('@p_service  varchar', 40 )                . ":= '" . $v_oem_info          . "'";
	
	                    $result = mssql_execute($v_sp) ;
	                    if (!$result){
	                        display_mssql_error("gsi_cmn_order_customization_add_note " . $msg , "call from oem_ocf_proc.php");
	                        $this->SendError($msg,"AddToCart");
	                    }
	                    mssql_free_statement ( $v_sp );
	                    mssql_free_result ($result);
	                     
	                    //***************************************************************************************
	                    // BY Luis cost price and retail price (This is the one)
	                    //***************************************************************************************
	                    $shaft_id = $_SESSION["selectedShaftId"];
	                    $grip_id = $_SESSION["selectedGripId"];
	                    //(inventory_item_id, shaft_id,dexterity)
	                    $cost_value = $v_club_obj->getCostValues($value['model'], $shaft_id, $grip_id, $value['dexterity']);
	                    
	                    // adding Note 2 with upcharges
	                    $v_totalShaftNGrip = $cost_value['shaft_model_cost'] + $cost_value['grip_model_cost'];
	                    $v_note_text = "";

	                    if( is_array( $z_view->additional_services ) && !empty( $z_view->additional_services ) ) {

	                        $servicesSelected = $_SESSION["servicesSelected"];
	                        $service_cost = $v_club_obj->getServiceCostValues( $servicesSelected, $value['inventory_item_id'] );
	                       
	                        $v_note_text  .= "Total:" . ($v_totalShaftNGrip + $service_cost['total']) . "~Shaft Wholesale Upcharge :".$cost_value['shaft_model_cost']."~Grip Wholesale Upcharge :".$cost_value['grip_model_cost']."~"."\n";
	                        $v_note_text .= "Service Wholesale Upcharges: " . $service_cost['total'] . " \n";
	                    }else{
	                        $v_note_text  .= "Total:" . ($v_totalShaftNGrip) . "~Shaft Wholesale Upcharge :".$cost_value['shaft_model_cost']."~Grip Wholesale Upcharge :".$cost_value['grip_model_cost']."~"."\n";
	                        $v_note_text .= "Service Wholesale Upcharges: 0 ";
	                    }
	                     
	
	                    $v_webprofile = C_WEB_PROFILE;
	                    $v_oem_info = 'OEM_UPCHARGES';
	
	                    $v_sp = mssql_init("gsi_cmn_order_customization_add_note");
	
	                    gsi_mssql_bind ($v_sp, "@p_profile_scope",  $v_webprofile , "varchar" ,50);
	                    gsi_mssql_bind ($v_sp, "@p_orig_sys_ref",  $v_order_number , "varchar", 50);
	                    gsi_mssql_bind ($v_sp, "@p_line_number",  $v_line_number, "bigint", -1);
	                    gsi_mssql_bind ($v_sp, "@p_note_text",  $v_note_text , "varchar", 2000);
	                    gsi_mssql_bind ($v_sp, "@p_service",  $v_oem_info , "varchar", 50);
	
	                    $msg = "";
	                    $msg .= "\n".str_pad('@p_profile_scope varchar', 40 )           . ":= '" . $v_webprofile        . "'";
	                    $msg .= "\n".str_pad('@p_orig_sys_ref  varchar', 40 )           . ":= '" . $v_order_number      . "'";
	                    $msg .= "\n".str_pad('@p_line_number    gsi_id_type', 40 )      . ":= '" . $v_line_number       . "'";
	                    $msg .= "\n".str_pad('@p_note_text varchar', 40 )               . ":= '" . $v_note_text         . "'";
	                    $msg .= "\n".str_pad('@p_service  varchar', 40 )                . ":= '" . $v_oem_info          . "'";
	
	                    $result = mssql_execute($v_sp) ;
	                    if (!$result){
	                        display_mssql_error("gsi_cmn_order_customization_add_note " . $msg , "call from oem_ocf_proc.php");
	                        $this->SendError($msg,"AddToCart");
	                    }
	                    mssql_free_statement ( $v_sp );
	                    mssql_free_result ($result);
	
	                    include_once(FPATH . 'session_cart.inc');
	                     
	                     
	                    	
	                }
	                	
	                $v_pure_box =1;
	                $v_pure_flag= 'Y';
	                // ship set proprietary items, if puring option is selected.
	                if (($v_pure_box == 1) && ($v_pure_flag == 'Y')) {
	                    // get PUREPAT line numbers.
	                    	
	                    $v_sql = "
	                    select
	                    line_number
	                    from
	                    gsi_cmn_order_lines_v col
	                    where
	                    col.style_number = 'PUREPAT' and
	                    col.order_number = '$v_order_number'
	                    " ;
	                    $result = mssql_query($v_sql);
	                    if (!$result){
	                        display_mssql_error($v_sql, "call from oem_ocf");
	                    }
	                    while ($row = mssql_fetch_array($result, MSSQL_BOTH)) {
	                        $v_line_numb      = $row['line_number'];
	                        $v_line_numb_str .= '~'.$v_line_numb;
	                        $j_line_numb_str .= '~'.$v_line_numb;
	                    }
	                    mssql_free_statement ( $v_sql );
	                    mssql_free_result ($result);
	
	                    $v_line_numb_str = substr($v_line_numb_str,1); // equivilant of ASP's left(string,1);
	                    $additional_atp_days = 0;
	                    $line_numbers = $racquet_line_number.'~'.$string_line_number.'~'.$labor_line_number;
	
	                    //jmic 02/10/2010 - fix for oem deletion on the cart
	                    $j_line_numb_string = substr($j_line_numb_str,1);
	                     
	                    $v_webprofile = C_WEB_PROFILE;
	                    $v_sp = mssql_init("gsi_cmn_order_ext_update_ship_set_number");
	                    $v_linenum_str =  $v_line_numb_str; // currently goes no where.
	
	                    gsi_mssql_bind ($v_sp, "@p_profile_scope",  $v_webprofile , "varchar" ,50);
	                    gsi_mssql_bind ($v_sp, "@p_orig_sys_ref",  $v_order_number , "varchar", 50);
	                    gsi_mssql_bind ($v_sp, "@p_line_numbers",  $j_line_numb_string , "varchar", 50 );
	                    gsi_mssql_bind ($v_sp, "@p_add_to_atp_date",  $additional_atp_days , "int", -1 );
	
	                    gsi_mssql_bind ($v_sp, "@p_return_status" , $v_return_status , "varchar", 250 , true);
	
	                    $msg = "";
	                    $msg .= "\n".str_pad('@p_profile_scope varchar', 40 )           . ":= '" . $v_webprofile          . "'";
	                    $msg .= "\n".str_pad('@p_orig_sys_ref  varchar', 40 )           . ":= '" . $v_order_number        . "'";
	                    $msg .= "\n".str_pad('@p_line_numbers  varchar', 40 )     	 . ":= '" . $j_line_numb_string . "'";
	                    $msg .= "\n".str_pad('@p_add_to_atp_date int', 40 )         . ":= '" . $additional_atp_days   . "'";
	
	                    $result = mssql_execute($v_sp) ;
	                    if (!$result){
	                        display_mssql_error("gsi_cmn_order_ext_update_ship_set_number " . $msg , "call from oem_ocf_proc.php");
	                    }
	                    mssql_free_statement ( $v_sp );
	                    mssql_free_result ($result);
	                } // and you're not going to have this code block in the next section
	                	
	            } else { // you're not a set. you're a club like a putter or driver
	                $v_selected_club_heads = array(1=>1);
	                $v_total_smart_fit_price = $value['retail_price'] + $value['grip_model_price'] + $value['shaft_model_price'] + $value['service_price'];
	                $v_return_status = add_item_to_cart($value['model'], 1, '', '', $v_line_number);
	                	
	                //die(print("..." . $v_return_status));
	                	
	                if (empty($v_line_number)) {
	                    $v_curr_style = str_replace('\'','',$value['model']);
	
	                    if ($v_curr_style != $v_prev_style) {
	                        $v_no_atp_styles .= $v_no_atp_styles .  str_replace('\'','',$value['model']) . ', ';
	                        $v_prev_style = $v_curr_style;
	                    }
	
	                }
	                	
	                //$v_line_numb_str[$ind] .= '~'.$v_line_number[$ind];
	                $v_line_numb_str = $v_line_numb_str . '~' . $v_line_number;
	                 
	                //jmic 2/10/2010 - fix for the oem deletion on cart
	                $j_line_numb_str = $j_line_numb_str . '~' . $v_line_number;
	                 
	                $v_order_number = $_SESSION['s_order_number'];
	                	
	                $v_sp = mssql_init("gsi_update_oem_line_price");
	                gsi_mssql_bind($v_sp, "@p_orig_sys_ref" , $v_order_number , "varchar", 50);
	                gsi_mssql_bind($v_sp, "@p_line_number"  , $v_line_number  , "numeric", -1);
	                gsi_mssql_bind($v_sp, "@p_price"	, $v_total_smart_fit_price , "gsi_price_type", -1 );
	                gsi_mssql_bind($v_sp, "@p_price_list_id", $line_price_list[$ind]  , "numeric",-1);
	                 
	                gsi_mssql_bind($v_sp, "@p_return_status" , $v_return_status , "varchar", 250 , true);
	
	                $v_return_status[$ind] = $v_return_status;
	                $msg ="";
	                $msg = $msg . "@p_orig_sys_ref = ". $v_order_number;
	                $msg = $msg . ", @p_line_number  = ". $v_line_number;
	                $msg = $msg . ", @p_price = ". $v_total_smart_fit_price ;
	                $msg = $msg . ", @p_price_list_id = ". $line_price_list[$ind];
	                 
	                $v_result = mssql_execute($v_sp);
	
	                if (!$v_result){
	                    display_mssql_error("gsi_update_oem_line_price " . $msg, "call from oem_ocf_proc.php");
	                }
	                mssql_free_statement($v_sp);
	                mssql_free_result ($v_result);
	                	
	                $v_note_text = "";
	                $v_note_text  .= $z_view->club_category ."\n";
	                $v_note_text  .= "Hand: " .$value['dexterity']."\n";
	                $v_note_text  .= "Club: " .$value['club_type']."\n";
	                $v_note_text  .= "Lie: " .str_replace("Standard", "Std", $value['lie_angle'])."\n";
	                	
	                $v_note_text  .= "Shaft: " . $value['shaft_model'] ."\n";
	                $v_note_text  .= "Flex: " .str_replace("Standard", "Std", $value['shaft_flex'])."\n";
	                $v_note_text  .= "Length: " .str_replace("Standard", "Std", $value['shaft_club_length'])."\n";
	                $v_note_text  .= "Grip: " .$value['grip_model']."\n";
	                $v_note_text  .= "Grip Size: " .str_replace("Standard", "Std", $value['grip_size'])."\n";
	                $v_note_text  .= "Serial Num: " . $value['serial_number']."\n";
	                if ( (!empty($value['special_instructions'])) && ($value['special_instructions'] != 'Optional')) {
	                    $v_note_text  .= "Comments:" .str_replace("Optional", "", $value['special_instructions'])."\n";
	                }
	                if (!empty($store_number)) {
	                    #$v_note_text  .= "Store#:" .$store_number."\n";
	                }
	                // services
	                
	                if (is_array($z_view->additional_services) && !empty($z_view->additional_services)) {
	                    $v_note_text .= $z_view->additional_services[0]['description'] . ": ";
	                    foreach($z_view->additional_services as $service => $selection) {
	                        if ($selection['display_label'] != "")
	                            $v_note_text .= $selection['display_label'] . ": ";
	                        $addon = "";
	                        $preon = "";
	                        if ($selection['user_action'] == "") { // this is a cost.
	                            if ($selection['service_level'] == "Per Order") { // this is a per model cost
	                                $addon = "/order";
	                                $preon = "$";
	                            } else {
	                                $addon = "/club";
	                                $preon = "$";
	                            }
	                        }
	                        $v_note_text .= $preon . $selection['value'] . $addon . "\n";
	                    }
	                }
	
	                
	                // creating second note.
	                // *** NoteText should not exceeeeeed  240 characters.
	                // if exceeds, description on PO will be empty.
	
	                $v_webprofile = C_WEB_PROFILE;
	                $v_oem_info = 'OEM_INFO';
	
	                $v_sp = mssql_init("gsi_cmn_order_customization_add_note");
	                	
	                gsi_mssql_bind ($v_sp, "@p_profile_scope",  $v_webprofile , "varchar" ,50);
	                gsi_mssql_bind ($v_sp, "@p_orig_sys_ref",  $v_order_number , "varchar", 50);
	                gsi_mssql_bind ($v_sp, "@p_line_number",  $v_line_number , "bigint", -1);
	                gsi_mssql_bind ($v_sp, "@p_note_text",  $v_note_text , "varchar", 2000);
	                gsi_mssql_bind ($v_sp, "@p_service",  $v_oem_info , "varchar", 50);
	                	
	                $msg = "";
	                $msg .= "\n".str_pad('@p_profile_scope varchar', 40 )           . ":= '" . $v_webprofile        . "'";
	                $msg .= "\n".str_pad('@p_orig_sys_ref  varchar', 40 )           . ":= '" . $v_order_number      . "'";
	                $msg .= "\n".str_pad('@p_line_number    gsi_id_type', 40 )      . ":= '" . $v_line_number		. "'";
	                $msg .= "\n".str_pad('@p_note_text varchar', 40 )               . ":= '" . $v_note_text         . "'";
	                $msg .= "\n".str_pad('@p_service  varchar', 40 )                . ":= '" . $v_oem_info          . "'";
	                	
	                $result = mssql_execute($v_sp) ;
	                if (!$result){
	                    display_mssql_error("gsi_cmn_order_customization_add_note " . $msg , "call from oem_ocf_proc.php");
	                }
	                mssql_free_statement ( $v_sp );
	                mssql_free_result ($result);
	
	                
	                //***************************************************************************************
	                // BY Luis cost price and retail price (another one)
	                //***************************************************************************************
	                	          
	                // adding Note 2 with upcharges
	                
	                $shaft_id = $_SESSION["selectedShaftId"];
                    $grip_id = $_SESSION["selectedGripId"];
                    //(inventory_item_id, shaft_id,dexterity)
                    $cost_value = $v_club_obj->getCostValues($value['model'], $shaft_id, $grip_id, $value['dexterity']);
                
                
                    // adding Note 2 with upcharges
                    $v_totalShaftNGrip = $cost_value['shaft_model_cost'] + $cost_value['grip_model_cost'];
                    $v_note_text = "";


                    if( is_array( $z_view->additional_services ) && !empty( $z_view->additional_services ) ) {

                        $servicesSelected = $_SESSION["servicesSelected"];
                        $service_cost = $v_club_obj->getServiceCostValues( $servicesSelected, $value['inventory_item_id'] );
                       
                        $v_note_text  .= "Total:" . ($v_totalShaftNGrip + $service_cost['total']) . "~Shaft Wholesale Upcharge :".$cost_value['shaft_model_cost']."~Grip Wholesale Upcharge :".$cost_value['grip_model_cost']."~"."\n";
                        $v_note_text .= "Service Wholesale Upcharges: " . $service_cost['total'] . " \n";
                    }else{
                        $v_note_text  .= "Total:" . ($v_totalShaftNGrip) . "~Shaft Wholesale Upcharge :".$cost_value['shaft_model_cost']."~Grip Wholesale Upcharge :".$cost_value['grip_model_cost']."~"."\n";
                        $v_note_text .= "Service Wholesale Upcharges: 0 ";
                    }
                
	                
	                $v_webprofile = C_WEB_PROFILE;
	                $v_oem_info = 'OEM_UPCHARGES';
	
	                $v_sp = mssql_init("gsi_cmn_order_customization_add_note");
	
	                gsi_mssql_bind ($v_sp, "@p_profile_scope",  $v_webprofile , "varchar" ,50);
	                gsi_mssql_bind ($v_sp, "@p_orig_sys_ref",  $v_order_number , "varchar", 50);
	                gsi_mssql_bind ($v_sp, "@p_line_number",  $v_line_number, "bigint", -1);
	                gsi_mssql_bind ($v_sp, "@p_note_text",  $v_note_text , "varchar", 2000);
	                gsi_mssql_bind ($v_sp, "@p_service",  $v_oem_info , "varchar", 50);
	                 
	                $msg = "";
	                $msg .= "\n".str_pad('@p_profile_scope varchar', 40 )           . ":= '" . $v_webprofile        . "'";
	                $msg .= "\n".str_pad('@p_orig_sys_ref  varchar', 40 )           . ":= '" . $v_order_number      . "'";
	                $msg .= "\n".str_pad('@p_line_number    gsi_id_type', 40 )      . ":= '" . $v_line_number       . "'";
	                $msg .= "\n".str_pad('@p_note_text varchar', 40 )               . ":= '" . $v_note_text         . "'";
	                $msg .= "\n".str_pad('@p_service  varchar', 40 )                . ":= '" . $v_oem_info          . "'";
	                	
	                $result = mssql_execute($v_sp) ;
	                if (!$result){
	                    display_mssql_error("gsi_cmn_order_customization_add_note " . $msg , "call from oem_ocf_proc.php");
	                }
	                mssql_free_statement($v_sp);
	                mssql_free_result($result);
	                 
	                include_once(FPATH . 'session_cart.inc');
	
	            }
	
	
	
	            #echo "<pre>";
	            #echo $v_return_status . " " . $v_line_number . "<BR>";
	            #print_r($z_view);
	
	            #print_r($z_view->value);
	            #echo "</pre><hr>";
	        }
	    }
	
	    #echo "<pre>";
	    #echo $_SESSION['store_number'];
	    #echo "</pre>";
	    #$v_redirect = '';
	     
	    #if(!empty($_SESSION['s_screen_name'])) {
	    #	$v_redirect .= '/' . $_SESSION['s_screen_name'];
	    #}
	    # $v_redirect .= '/checkout/cart/';
	
	    # header('Location: ' . $v_redirect);
	
	    return true;
	} // end addtocart function
	
	function AddToCartPopUpWindow (){
	    global $web_db;
	    
	    $z_view = new Zend_View(array('scriptPath' => VIEW_PATH));
	    //Getting manuf_desc and original price
	    $sql2= "select lower(bra.brand_name) as manuf_desc, sia.description,sia.original_price
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
	     
	    $v_donotshow_total='';
	    $v_dollar_saving='';
	    $v_peritem_saving='';
	    $v_tot_saving='';
	    
	    // Generating saving story
	    if ( (ltrim($price,'$') < ltrim($v_original_price,'$')) && $price!='0.0'){
	        $v_dollar_saving=ltrim($v_original_price,'$')-ltrim($price,'$');
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
	
	/*******************************
	 * Function: AddToCartClub
	 * Purpose: adds all club customizations to the shopping cart o.O
	 * Called From::
	 * 	Controller: controller
	 *  Function: addtocartAction
	 * Comment Date: 4/12/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function AddToCartClub($p_session_id) {
		
		global $apps_db;
	    include_once(FPATH . 'fns_sc_order');
	    
	    #if (function_exists('log_end')) echo "log_end exists"; else echo "log_end does not exist. found in gsi_common.inc";
	    
		// get my saved settings
		$v_club_obj = new Club ();
		$v_club_obj->_session_id = $p_session_id;
		$va_my_fitting = $v_club_obj->getMyFitting();
		if (is_array($va_my_fitting) && !empty($va_my_fitting)) {
		 	foreach($va_my_fitting as $key => $value) {
				$v_club_obj->_fitting_id = $value["fitting_id"];
				$z_view->fitting_id = $value["fitting_id"]; 
				$z_view->started = $value['started'];
			}
		} 
		 
		// now get all my clubs that I selected in step 1
		$va_my_clubs = array ();
		$va_my_clubs = $v_club_obj->getMyClubs();

		#die(print_r($va_my_clubs));
	
		// for each club selected display the customized settings.
		if (is_array($va_my_clubs) && !empty($va_my_clubs)) {
			$z_view->initial = 0;
			// grab all club types in this selection for use in club angles so we know which
			// club_lie_angles to loop through when setting dexterity
			$club_types = "";
			$club_models = "";
			foreach($va_my_clubs as $key => $value) {
				$club_types .= $value['club_type'] ;
				$club_models .= $value['model'] ;
				$club_ids .= $value['club_id'];
			}
			$z_view->club_models = $club_models;
			$z_view->club_types = $club_types;
			$z_view->club_ids = $club_ids;
			$z_view->total_smart_fit_price = 0;
			$z_view->total_club_count = 0;
			
			/*
			echo "va my clubs: \n";
			print_r($va_my_clubs);
			echo "va my clubs ENDS: \n";
			*/
			
			
			// now loop through the club selection for further customizations
			foreach($va_my_clubs as $key => $value) {

				#echo "<pre>";
				#print_r($value);
				#echo "</pre>";
			
				
				// is this club a set and if so, return the price list. 
				// if the result is empty, it's not set.
				$va_club_set = $v_club_obj->getClubSet($value['model']);
				$z_view->is_club_set = false;
				#$z_view->club_set_pricing = array();
				//$z_view->club_set = $va_club_set;
				if (is_array($va_club_set) && !empty($va_club_set)) {
					$z_view->is_club_set = true;
					//$z_view->club_set_pricing = $va_club_set;
				} else {
					// not a club set
				}
				
				$va_selected_club_heads = $v_club_obj->getMyClubHeads($value['club_id']);
				$z_view->selected_club_heads = $va_selected_club_heads;
				$z_view->total_smart_fit_price = 0;
				#die(print_r($va_selected_club_heads));
				$va_additional_services = $v_club_obj->getMyClubServices($value['club_id']);
				$z_view->additional_services = $va_additional_services;
			
				// loop through the recorded prices
				// alright! it's come down to this. add the custom club selections to the cart. Ready! Set! Go!!!!
				$v_line_numb_str = "";
				$j_line_numb_str = "";

				if (is_array($z_view->selected_club_heads) && !empty($z_view->selected_club_heads)) {
					// count up the 
					$hybrid_count = 0;
					$iron_count = 0;
					foreach($z_view->selected_club_heads as $head) {
						if ($head['hybrid_flag'] == "Y")
							$hybrid_count++;
						else
							$iron_count++;
					}
					$shaft_upcharge = (($value['shaft_model_price']*$iron_count)+($value['shaft_model_price_combo']*$hybrid_count));

					foreach($z_view->selected_club_heads as $head) {
						
						$v_total_smart_fit_price = $head['price']+ $value['grip_model_price'] + ($shaft_upcharge/sizeof($z_view->selected_club_heads)) + ($value['service_price']/sizeof($z_view->selected_club_heads));
						$v_return_status = add_item_to_cart($value['model'], 1, '', '', $v_line_number);
						
						//die(print("..." . $v_return_status));
						
						if (empty($v_line_number)) {
		        			$v_curr_style = str_replace('\'','',$value['model']);
		
		        			if ($v_curr_style != $v_prev_style) {
		        				$v_no_atp_styles .= $v_no_atp_styles .  str_replace('\'','',$value['model']) . ', ';
		        				$v_prev_style = $v_curr_style;
		        			}
		        			
		        		}
        				
		        		//$v_line_numb_str[$ind] .= '~'.$v_line_number[$ind];
		        		$v_line_numb_str = $v_line_numb_str . '~' . $v_line_number;
		          
		        		//jmic 2/10/2010 - fix for the oem deletion on cart
		        		$j_line_numb_str = $j_line_numb_str . '~' . $v_line_number;
		          
		        		$v_order_number = $_SESSION['s_order_number'];
						 
		        		$v_sp = mssql_init("gsi_update_oem_line_price");
		        		gsi_mssql_bind($v_sp, "@p_orig_sys_ref" , $v_order_number , "varchar", 50);
		        		gsi_mssql_bind($v_sp, "@p_line_number"  , $v_line_number  , "numeric", -1);
		        		gsi_mssql_bind($v_sp, "@p_price"	, $v_total_smart_fit_price , "gsi_price_type", -1 );
		        		gsi_mssql_bind($v_sp, "@p_price_list_id", $line_price_list[$ind]  , "numeric",-1);
		                        
		        		gsi_mssql_bind($v_sp, "@p_return_status" , $v_return_status , "varchar", 250 , true);
		
		        		$v_return_status[$ind] = $v_return_status;
		        		$msg ="";
		        		$msg = $msg . "@p_orig_sys_ref = ". $v_order_number;
		        		$msg = $msg . ", @p_line_number  = ". $v_line_number;
		        		$msg = $msg . ", @p_price = ". $v_total_smart_fit_price ;
		        		$msg = $msg . ", @p_price_list_id = ". $line_price_list[$ind];
			  
		        		$v_result = mssql_execute($v_sp);
       				
		        		if (!$v_result){
		        			display_mssql_error("gsi_update_oem_line_price " . $msg, "call from oem_ocf_proc.php");
        					$this->SendError($msg,"AddToCart");
		        		}
		        		mssql_free_statement($v_sp);
		        		mssql_free_result ($v_result);
						
		        		$v_note_text = "";
		        		$v_note_text  .= $z_view->club_category ."\n";
		        		$v_note_text  .= "Hand: " .$value['dexterity']."\n";
		        		$v_note_text  .= "Club: " .$head['club_selection']."\n";
	        			$v_note_text  .= "Lie: " .str_replace("Standard", "Std", $value['lie_angle'])."\n";
						
		        		if ($head['hybrid_flag'] == "Y") {
		        			$v_note_text  .= "Hybrid Shaft: " . $value['shaft_model_combo'] ."\n";
		        			$v_note_text  .= "Hybrid Flex: " .str_replace("Standard", "Std", $value['shaft_flex_combo'])."\n";
		        		} else {
		        			$v_note_text  .= "Shaft: " . $value['shaft_model'] ."\n";
		        			$v_note_text  .= "Flex: " .str_replace("Standard", "Std", $value['shaft_flex'])."\n";
		        		}
		        		
		        		$v_note_text  .= "Length: " .str_replace("Standard", "Std", $value['shaft_club_length'])."\n";
		        		if ($value['grip_model'] == "")
		        			$value['grip_model'] = "Stock Grip";
		        		$v_note_text  .= "Grip: " .$value['grip_model']."\n";
		        		if (str_replace("Standard", "Std", $value['grip_size'] == "Select Grip Model"))
		        			$value['grip_size'] = "";
		        		if ($value['grip_size'] == "")
		        			$value['grip_size'] = "Standard";
		        		$v_note_text  .= "Grip Size: " .str_replace("Standard", "Std", $value['grip_size'])."\n";
		        		if ($value['serial_number'] != "")
		        			$v_note_text  .= "Serial Num: " . $value['serial_number']."\n"; 
		        		if ( (!empty($value['special_instructions'])) && ($value['special_instructions'] != 'Optional')) {
		        			$v_note_text  .= "Comments:" .str_replace("Optional", "", $value['special_instructions'])."\n";
		        		}
		        		if (!empty($store_number)) {
		        			$v_note_text  .= "Store#:" .$store_number."\n";
		        		}
		        		// services
						if (is_array($z_view->additional_services) && !empty($z_view->additional_services)) {
						    $v_note_text .= $z_view->additional_services[0]['description'] . ": ";
							foreach($z_view->additional_services as $service => $selection) {
								if ($selection['display_label'] != "") 
									 $v_note_text .= $selection['display_label'] . ": "; 
								$addon = "";
								$preon = "";
								if ($selection['user_action'] == "") { // this is a cost.
									if ($selection['service_level'] == "Per Order") { // this is a per model cost
										$addon = "/order";
										$preon = "$";
									} else {
										$addon = "/club";
										$preon = "$";
									}
								}
								$v_note_text .= $preon . $selection['value'] . $addon . "\n";
							}
						}

		        		
		        		// creating second note.
		        		// *** NoteText should not exceeeeeed  240 characters.
		        		// if exceeds, description on PO will be empty.
        				
		        		$v_webprofile = C_WEB_PROFILE;
		        		$v_oem_info = 'OEM_INFO';
						
		        		$v_sp = mssql_init("gsi_cmn_order_customization_add_note");
						
				        gsi_mssql_bind ($v_sp, "@p_profile_scope",  $v_webprofile , "varchar" ,50);
				        gsi_mssql_bind ($v_sp, "@p_orig_sys_ref",  $v_order_number , "varchar", 50);
				        gsi_mssql_bind ($v_sp, "@p_line_number",  $v_line_number , "bigint", -1);
				        gsi_mssql_bind ($v_sp, "@p_note_text",  $v_note_text , "varchar", 2000);
				        gsi_mssql_bind ($v_sp, "@p_service",  $v_oem_info , "varchar", 50);
				
				        $msg = "";
				        $msg .= "\n".str_pad('@p_profile_scope varchar', 40 )           . ":= '" . $v_webprofile        . "'";
				        $msg .= "\n".str_pad('@p_orig_sys_ref  varchar', 40 )           . ":= '" . $v_order_number      . "'";
				        $msg .= "\n".str_pad('@p_line_number    gsi_id_type', 40 )      . ":= '" . $v_line_number[$ind] . "'";
				        $msg .= "\n".str_pad('@p_note_text varchar', 40 )               . ":= '" . $v_note_text         . "'";
				        $msg .= "\n".str_pad('@p_service  varchar', 40 )                . ":= '" . $v_oem_info          . "'";
						
				        $result = mssql_execute($v_sp) ;

				        if (!$result){
				        	display_mssql_error("gsi_cmn_order_customization_add_note " . $msg , "call from oem_ocf_proc.php");
        					$this->SendError($msg,"AddToCart");
				        }
				        mssql_free_statement ( $v_sp );
				        mssql_free_result ($result);
		         
				        // adding Note 2 with upcharges
				        //This is for the new custom club
					       
				        $shaft_id = $_SESSION["selectedShaftId"];
	                    $grip_id = $_SESSION["selectedGripId"];
	                    //(inventory_item_id, shaft_id,dexterity)
	                    $cost_value = $v_club_obj->getCostValues($value['model'], $shaft_id, $grip_id, $value['dexterity']);
	                    
	                    // adding Note 2 with upcharges
	                    $v_totalShaftNGrip = $cost_value['shaft_model_cost'] + $cost_value['grip_model_cost'];
	                    $v_note_text = "";


	                    if( is_array( $z_view->additional_services ) && !empty( $z_view->additional_services ) ) {

	                        $servicesSelected = $_SESSION["servicesSelected"];
	                        $service_cost = $v_club_obj->getServiceCostValues( $servicesSelected, $value['inventory_item_id'] );
	                       
	                        $v_note_text  .= "Total:" . ($v_totalShaftNGrip + $service_cost['total']) . "~Shaft Wholesale Upcharge :".$cost_value['shaft_model_cost']."~Grip Wholesale Upcharge :".$cost_value['grip_model_cost']."~"."\n";
	                        $v_note_text .= "Service Wholesale Upcharges: " . $service_cost['total'] . " \n";
	                    }else{
	                        $v_note_text  .= "Total:" . ($v_totalShaftNGrip) . "~Shaft Wholesale Upcharge :".$cost_value['shaft_model_cost']."~Grip Wholesale Upcharge :".$cost_value['grip_model_cost']."~"."\n";
	                        $v_note_text .= "Service Wholesale Upcharges: 0 ";
	                    }
	                    
	                    
	                    
				        $v_webprofile = C_WEB_PROFILE;
				        $v_oem_info = 'OEM_UPCHARGES';
		
				        $v_sp = mssql_init("gsi_cmn_order_customization_add_note");
		
				        gsi_mssql_bind ($v_sp, "@p_profile_scope",  $v_webprofile , "varchar" ,50);
				        gsi_mssql_bind ($v_sp, "@p_orig_sys_ref",  $v_order_number , "varchar", 50);
				        gsi_mssql_bind ($v_sp, "@p_line_number",  $v_line_number, "bigint", -1);
				        gsi_mssql_bind ($v_sp, "@p_note_text",  $v_note_text , "varchar", 2000);
				        gsi_mssql_bind ($v_sp, "@p_service",  $v_oem_info , "varchar", 50);
				
				        $msg = "";
				        $msg .= "\n".str_pad('@p_profile_scope varchar', 40 )           . ":= '" . $v_webprofile        . "'";
				        $msg .= "\n".str_pad('@p_orig_sys_ref  varchar', 40 )           . ":= '" . $v_order_number      . "'";
				        $msg .= "\n".str_pad('@p_line_number    gsi_id_type', 40 )      . ":= '" . $v_line_number       . "'";
				        $msg .= "\n".str_pad('@p_note_text varchar', 40 )               . ":= '" . $v_note_text         . "'";
				        $msg .= "\n".str_pad('@p_service  varchar', 40 )                . ":= '" . $v_oem_info          . "'";
				
				        $result = mssql_execute($v_sp) ;

				        if (!$result){
			        		display_mssql_error("gsi_cmn_order_customization_add_note " . $msg , "call from oem_ocf_proc.php");
        					$this->SendError($msg,"AddToCart");
				        }
				        mssql_free_statement ( $v_sp );
				        mssql_free_result ($result);
				        
				    	include_once(FPATH . 'session_cart.inc');
				    	
				    	
					
					}
					
					$v_pure_box =1;
		        	$v_pure_flag= 'Y';
		        	// ship set proprietary items, if puring option is selected.
		        	if (($v_pure_box == 1) && ($v_pure_flag == 'Y')) {
		        		// get PUREPAT line numbers. 
		 
		        		$v_sql = " 
		        		select 
		        			line_number 
		        		from   
		        			gsi_cmn_order_lines_v col 
		        		where  
		        			col.style_number = 'PUREPAT' and    
		        			col.order_number = '$v_order_number' 
		        		" ;
		        		$result = mssql_query($v_sql);
	        		
		        		if (!$result){
		        			display_mssql_error($v_sql, "call from oem_ocf");
		        		}
		        		while ($row = mssql_fetch_array($result, MSSQL_BOTH)) {
		        			$v_line_numb      = $row['line_number'];	
		        			$v_line_numb_str .= '~'.$v_line_numb;  	
		        			$j_line_numb_str .= '~'.$v_line_numb;        
		        		}
		        		mssql_free_statement ( $v_sql );
		        		mssql_free_result ($result);
		
		        		$v_line_numb_str = substr($v_line_numb_str,1); // equivilant of ASP's left(string,1);
		        		$additional_atp_days = 0; 
		        		$line_numbers = $racquet_line_number.'~'.$string_line_number.'~'.$labor_line_number;
			 
		        		//jmic 02/10/2010 - fix for oem deletion on the cart 
		        		$j_line_numb_string = substr($j_line_numb_str,1);
		     
		        		$v_webprofile = C_WEB_PROFILE;
		        		$v_sp = mssql_init("gsi_cmn_order_ext_update_ship_set_number");
		        		$v_linenum_str =  $v_line_numb_str; // currently goes no where.  
		
		        		gsi_mssql_bind ($v_sp, "@p_profile_scope",  $v_webprofile , "varchar" ,50);
		        		gsi_mssql_bind ($v_sp, "@p_orig_sys_ref",  $v_order_number , "varchar", 50);
		        		gsi_mssql_bind ($v_sp, "@p_line_numbers",  $j_line_numb_string , "varchar", 50 );
		        		gsi_mssql_bind ($v_sp, "@p_add_to_atp_date",  $additional_atp_days , "int", -1 );
		
		        		gsi_mssql_bind ($v_sp, "@p_return_status" , $v_return_status , "varchar", 250 , true);
		        
		        		$msg = "";
				        $msg .= "\n".str_pad('@p_profile_scope varchar', 40 )           . ":= '" . $v_webprofile          . "'";
				        $msg .= "\n".str_pad('@p_orig_sys_ref  varchar', 40 )           . ":= '" . $v_order_number        . "'";
				        $msg .= "\n".str_pad('@p_line_numbers  varchar', 40 )     	 . ":= '" . $j_line_numb_string . "'";
				        $msg .= "\n".str_pad('@p_add_to_atp_date int', 40 )         . ":= '" . $additional_atp_days   . "'";
		
				        $result = mssql_execute($v_sp) ;

				        if (!$result){
				        	display_mssql_error("gsi_cmn_order_ext_update_ship_set_number " . $msg , "call from oem_ocf_proc.php");
				        }
				        mssql_free_statement ( $v_sp );
				        mssql_free_result ($result);
		        	} // and you're not going to have this code block in the next section
					
				} else { // you're not a set. you're a club like a putter or driver
					$v_selected_club_heads = array(1=>1);
					$v_total_smart_fit_price = $value['retail_price'] + $value['grip_model_price'] + $value['shaft_model_price'] + $value['service_price'];
					$v_return_status = add_item_to_cart($value['model'], 1, '', '', $v_line_number);
					
					//die(print("..." . $v_return_status));
					
					if (empty($v_line_number)) {
	        			$v_curr_style = str_replace('\'','',$value['model']);
	
	        			if ($v_curr_style != $v_prev_style) {
	        				$v_no_atp_styles .= $v_no_atp_styles .  str_replace('\'','',$value['model']) . ', ';
	        				$v_prev_style = $v_curr_style;
	        			}
	        			
	        		}
					
	        		//$v_line_numb_str[$ind] .= '~'.$v_line_number[$ind];
	        		$v_line_numb_str = $v_line_numb_str . '~' . $v_line_number;
	          
	        		//jmic 2/10/2010 - fix for the oem deletion on cart
	        		$j_line_numb_str = $j_line_numb_str . '~' . $v_line_number;
	          
	        		$v_order_number = $_SESSION['s_order_number'];
					
	        		$v_sp = mssql_init("gsi_update_oem_line_price");
	        		gsi_mssql_bind($v_sp, "@p_orig_sys_ref" , $v_order_number , "varchar", 50);
	        		gsi_mssql_bind($v_sp, "@p_line_number"  , $v_line_number  , "numeric", -1);
	        		gsi_mssql_bind($v_sp, "@p_price"	, $v_total_smart_fit_price , "gsi_price_type", -1 );
	        		gsi_mssql_bind($v_sp, "@p_price_list_id", $line_price_list[$ind]  , "numeric",-1);
	                        
	        		gsi_mssql_bind($v_sp, "@p_return_status" , $v_return_status , "varchar", 250 , true);
	
	        		$v_return_status[$ind] = $v_return_status;
	        		$msg ="";
	        		$msg = $msg . "@p_orig_sys_ref = ". $v_order_number;
	        		$msg = $msg . ", @p_line_number  = ". $v_line_number;
	        		$msg = $msg . ", @p_price = ". $v_total_smart_fit_price ;
	        		$msg = $msg . ", @p_price_list_id = ". $line_price_list[$ind];
        			
	        		$v_result = mssql_execute($v_sp);
	
	        		if (!$v_result){
	        			display_mssql_error("gsi_update_oem_line_price " . $msg, "call from oem_ocf_proc.php");
	        		}
	        		mssql_free_statement($v_sp);
	        		mssql_free_result ($v_result);
					
	        		$v_note_text = "";
	        		$v_note_text  .= $z_view->club_category ."\n";
	        		$v_note_text  .= "Hand: " .$value['dexterity']."\n";
	        		$v_note_text  .= "Club: " .$value['club_type']."\n";
        			$v_note_text  .= "Lie: " .str_replace("Standard", "Std", $value['lie_angle'])."\n";
					
	        		$v_note_text  .= "Shaft: " . $value['shaft_model'] ."\n";
	        		$v_note_text  .= "Flex: " .str_replace("Standard", "Std", $value['shaft_flex'])."\n";
	        		$v_note_text  .= "Length: " .str_replace("Standard", "Std", $value['shaft_club_length'])."\n";
	        		$v_note_text  .= "Grip: " .$value['grip_model']."\n";
	        		$v_note_text  .= "Grip Size: " .str_replace("Standard", "Std", $value['grip_size'])."\n";
	        		$v_note_text  .= "Serial Num: " . $value['serial_number']."\n"; 
	        		if ( (!empty($value['special_instructions'])) && ($value['special_instructions'] != 'Optional')) {
	        			$v_note_text  .= "Comments:" .str_replace("Optional", "", $value['special_instructions'])."\n";
	        		}
	        		if (!empty($store_number)) {
	        			#$v_note_text  .= "Store#:" .$store_number."\n";
	        		}
	        		// services
        			if (is_array($z_view->additional_services) && !empty($z_view->additional_services)) {
        			    $v_note_text .= $z_view->additional_services[0]['description'] . ": ";
						foreach($z_view->additional_services as $service => $selection) {
							if ($selection['display_label'] != "") 
								 $v_note_text .= $selection['display_label'] . ": "; 
							$addon = "";
							$preon = "";
							if ($selection['user_action'] == "") { // this is a cost.
								if ($selection['service_level'] == "Per Order") { // this is a per model cost
									$addon = "/order";
									$preon = "$";
								} else {
									$addon = "/club";
									$preon = "$";
								}
							}
							$v_note_text .= $preon . $selection['value'] . $addon . "\n";
						}
					}
					
					
	        		// creating second note.
	        		// *** NoteText should not exceeeeeed  240 characters.
	        		// if exceeds, description on PO will be empty.
	
	        		$v_webprofile = C_WEB_PROFILE;
	        		$v_oem_info = 'OEM_INFO';
	
	        		$v_sp = mssql_init("gsi_cmn_order_customization_add_note");
					
			        gsi_mssql_bind ($v_sp, "@p_profile_scope",  $v_webprofile , "varchar" ,50);
			        gsi_mssql_bind ($v_sp, "@p_orig_sys_ref",  $v_order_number , "varchar", 50);
			        gsi_mssql_bind ($v_sp, "@p_line_number",  $v_line_number , "bigint", -1);
			        gsi_mssql_bind ($v_sp, "@p_note_text",  $v_note_text , "varchar", 2000);
			        gsi_mssql_bind ($v_sp, "@p_service",  $v_oem_info , "varchar", 50);
					
			        $msg = "";
			        $msg .= "\n".str_pad('@p_profile_scope varchar', 40 )           . ":= '" . $v_webprofile        . "'";
			        $msg .= "\n".str_pad('@p_orig_sys_ref  varchar', 40 )           . ":= '" . $v_order_number      . "'";
			        $msg .= "\n".str_pad('@p_line_number    gsi_id_type', 40 )      . ":= '" . $v_line_number		. "'";
			        $msg .= "\n".str_pad('@p_note_text varchar', 40 )               . ":= '" . $v_note_text         . "'";
			        $msg .= "\n".str_pad('@p_service  varchar', 40 )                . ":= '" . $v_oem_info          . "'";
					
			        $result = mssql_execute($v_sp) ;
			        if (!$result){
			        	display_mssql_error("gsi_cmn_order_customization_add_note " . $msg , "call from oem_ocf_proc.php");
			        }
			        mssql_free_statement ( $v_sp );
			        mssql_free_result ($result);
	         		
			        // adding Note 2 with upcharges
			        
			        $shaft_id = $_SESSION["selectedShaftId"];
			        $grip_id = $_SESSION["selectedGripId"];
			        //(inventory_item_id, shaft_id,dexterity)
			        $cost_value = $v_club_obj->getCostValues($value['model'], $shaft_id, $grip_id, $value['dexterity']);
			         
			        // adding Note 2 with upcharges
                    $v_totalShaftNGrip = $cost_value['shaft_model_cost'] + $cost_value['grip_model_cost'];
                    $v_note_text = "";


                    if( is_array( $z_view->additional_services ) && !empty( $z_view->additional_services ) ) {

                        $servicesSelected = $_SESSION["servicesSelected"];
                        $service_cost = $v_club_obj->getServiceCostValues( $servicesSelected, $value['inventory_item_id'] );
                       
                        $v_note_text  .= "Total:" . ($v_totalShaftNGrip + $service_cost['total']) . "~Shaft Wholesale Upcharge :".$cost_value['shaft_model_cost']."~Grip Wholesale Upcharge :".$cost_value['grip_model_cost']."~"."\n";
                        $v_note_text .= "Service Wholesale Upcharges: " . $service_cost['total'] . " \n";
                    }else{
                        $v_note_text  .= "Total:" . ($v_totalShaftNGrip) . "~Shaft Wholesale Upcharge :".$cost_value['shaft_model_cost']."~Grip Wholesale Upcharge :".$cost_value['grip_model_cost']."~"."\n";
                        $v_note_text .= "Service Wholesale Upcharges: 0 ";
                    }
			        
			        
			        
			        $v_webprofile = C_WEB_PROFILE;
			        $v_oem_info = 'OEM_UPCHARGES';
	
			        $v_sp = mssql_init("gsi_cmn_order_customization_add_note");
	
			        gsi_mssql_bind ($v_sp, "@p_profile_scope",  $v_webprofile , "varchar" ,50);
			        gsi_mssql_bind ($v_sp, "@p_orig_sys_ref",  $v_order_number , "varchar", 50);
			        gsi_mssql_bind ($v_sp, "@p_line_number",  $v_line_number, "bigint", -1);
			        gsi_mssql_bind ($v_sp, "@p_note_text",  $v_note_text , "varchar", 2000);
			        gsi_mssql_bind ($v_sp, "@p_service",  $v_oem_info , "varchar", 50);
		        	
			        $msg = "";
			        $msg .= "\n".str_pad('@p_profile_scope varchar', 40 )           . ":= '" . $v_webprofile        . "'";
			        $msg .= "\n".str_pad('@p_orig_sys_ref  varchar', 40 )           . ":= '" . $v_order_number      . "'";
			        $msg .= "\n".str_pad('@p_line_number    gsi_id_type', 40 )      . ":= '" . $v_line_number       . "'";
			        $msg .= "\n".str_pad('@p_note_text varchar', 40 )               . ":= '" . $v_note_text         . "'";
			        $msg .= "\n".str_pad('@p_service  varchar', 40 )                . ":= '" . $v_oem_info          . "'";
			
			        $result = mssql_execute($v_sp) ;
			        if (!$result){
		        		display_mssql_error("gsi_cmn_order_customization_add_note " . $msg , "call from oem_ocf_proc.php");
			        }
			        mssql_free_statement($v_sp);
			        mssql_free_result($result);
			        
			    	include_once(FPATH . 'session_cart.inc');
			    	
				}
				
				
        		
				#echo "<pre>";
				#echo $v_return_status . " " . $v_line_number . "<BR>";
				#print_r($z_view);
				
				#print_r($z_view->value);
				#echo "</pre><hr>";
			}
		} 
		
		#echo "<pre>";
		#echo $_SESSION['store_number'];
		#echo "</pre>";
		#$v_redirect = '';
	    
        #if(!empty($_SESSION['s_screen_name'])) {
        #	$v_redirect .= '/' . $_SESSION['s_screen_name'];
        #}
       # $v_redirect .= '/checkout/cart/';

       # header('Location: ' . $v_redirect);

       return true;
	} // end addtocart function
	
	/*             Forwarding functions from the CustomclubController to the Club Model                   */
	/*             --------------------------------------------------------------------  				  */
	/*																									  */
	/*      display_mssql_error is a log writing function and if the CustomclubController directly        */
	/*      accesses a club model object, club doesn't inherit this function where it is included         */
	/*      in the CustomClub model. The correct structure should be:                                     */
	/*      web > javascript > controller > customclub > club (if needed) > controller > javascript > web */
	/*      When trying to to web > javascript > controller > club ... the function inheritance breaks.   */
	/*      Hense, these forwarding functions as to retain that control needed but not breaking the       */
	/*      control structure.																			  */
	/*																			- robbie smith, 6/24/2010 */
	
	/*******************************
	 * Function: getClubHeadOptions
	 * Purpose: controller needs access to the club head options. this is a forwarding function
	 * Called From::
	 * 	Controller: controller
	 *  Function: clubheadoptionsAction
	 * Comment Date: 6/24/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getClubHeadOptions($p_model, $p_dexterity) {
		$v_club_obj = new Club ();
		return $v_club_obj->getClubHeadOptions($p_model, $p_dexterity);
	}
	
	/*******************************
	 * Function: getClubModelPrice
	 * Purpose: controller needs access to the club model price. this is a forwarding function
	 * Called From::
	 * 	Controller: controller
	 *  Function: clubheadoptionsAction
	 * Comment Date: 6/24/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getClubModelPrice($p_model) {
		$v_club_obj = new Club ();
		return $v_club_obj->getClubModelPrice($p_model);
	}
	
	/*******************************
	 * Function: GetGrips
	 * Purpose: controller needs access to the grips. this is a forwarding function
	 * Called From::
	 * 	Controller: controller
	 *  Function: clubheadoptionsAction
	 * Comment Date: 6/24/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetGripsClubs($p_model, $p_dexterity) {
	    $v_club_obj = new Club ();
	    return $v_club_obj->GetGripsClubs($p_model, $p_dexterity);
	}
	
	/*******************************
	 * Function: GetGrips
	 * Purpose: controller needs access to the grips. this is a forwarding function
	 * Called From::
	 * 	Controller: controller
	 *  Function: clubheadoptionsAction
	 * Comment Date: 6/24/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetGrips($p_session_id, $p_model, $p_dexterity) {
		$v_club_obj = new Club ();
		return $v_club_obj->GetGrips($p_session_id, $p_model, $p_dexterity);
	}
	
	/*******************************
	 * Function: GetAvailableGripSizes
	 * Purpose: controller needs access to the grip sizes. this is a forwarding function
	 * Called From::
	 * 	Controller: controller
	 *  Function: clubheadoptionsAction
	 * Comment Date: 6/24/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetAvailableGripSizes($p_session_id, $p_model, $p_grip_id, $p_dexterity) {
		$v_club_obj = new Club ();
		return $v_club_obj->GetAvailableGripSizes($p_session_id, $p_model, $p_grip_id, $p_dexterity);
	}
	
	/************************************ 
	 * Function: GetFlexOptions
	 * Purpose: controller needs access to the shaft flex. this is a forwarding function
	 * Called From::
	 * 	Controller: controller
	 *  Function: shaftflexoptionsAction
	 * Comment Date: 6/25/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetFlexOptions($p_session_id, $p_trajectory, $p_model, $p_club_type_value,$p_dexterity) {
		$v_club_obj = new Club ();
		return $v_club_obj->GetFlexOptions($p_session_id, $p_trajectory, $p_model, $p_club_type_value,$p_dexterity);
	}
	
	/************************************ 
	 * Function: GetLieAngleOptions
	 * Purpose: controller needs access to the lie angles. this is a forwarding function
	 * Called From::
	 * 	Controller: controller
	 *  Function: lieangleoptionsAction
	 * Comment Date: 7/28/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetLieAngleOptions($p_session_id, $p_model, $p_dexterity) {
		$v_club_obj = new Club ();
		return $v_club_obj->GetLieAngleOptions($p_session_id, $p_model, $p_dexterity);
	}
	
}

?>