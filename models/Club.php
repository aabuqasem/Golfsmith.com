<?php
require_once ('Zend/View.php');
require_once ('models/SiteInit.php');

class Club {
	
	public $_category;			//
	public $_club_type;			//
	public $_manufacturer;		//
	public $_model;				//	
	public $_dexterity;			//
	public $_session_id;
	
	/******************************
	 * Function: __construct
	 * Purpose: Default constructor for class Club
	 * Called From::
	 * 	Controller: CustomclubController.php
	 *  Function: mainAction
	 * Comment Date: 3/10/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function __construct() {
		global $connect_mssql_db;
		$connect_mssql_db = 1;
		
		//$this->i_site_init = new SiteInit ();
		//$this->i_site_init->loadInit ();
	}
	
	public function SendError($sql,$function) {
		mail("robbie.smith@golfsmith.com","SQL Error inside Club.php :: $function",$sql);		
	}
	
	/******************************
	 * Function: getClubTypes
	 * Purpose: retrieves a distinct list of available club types (i.e. Irons, Driver
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayClubSelection
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */	
	
	public function getClubTypes() {
		
		global $mssql_db;
		
		/*$v_sql = "
			SELECT DISTINCT 
				club_type 
			FROM
				r12pricing.dbo.GSI_CUS_MODELS 
			WHERE 
				((end_date IS NULL AND GETDATE() > start_date) OR 
				(GETDATE() BETWEEN start_date AND end_date)) AND
				customer_category = '" . $this->_category . "'
		";*/
		
		$v_sql="
		  	SELECT DISTINCT 
        	CASE [club_type]
               WHEN 'Combo/Hybrid' THEN 'Iron Sets - With Hybrids'
               WHEN 'Irons' THEN 'Iron Sets - Irons Only'
               ELSE club_type
            END AS [club_type] 
        	FROM
        	r12pricing.dbo.GSI_CUS_MODELS 
        	WHERE 
        	((end_date IS NULL AND GETDATE() > start_date) OR 
        	(GETDATE() BETWEEN start_date AND end_date)) AND
        	customer_category = '" . $this->_category . "' 
		";
		
		
		$v_result = mssql_query ( $v_sql );
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "getClubTypes query from Club model" );
			//$this->SendError($v_sql,"getClubTypes");
		}
		
		$va_club_type = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while ($row = mssql_fetch_assoc($v_result)) {
				$col = array();
				$col["club_type"]			= $row["club_type"];
				$va_club_type[$c++] = $col;
			}
		} //else {
			//$va_club_type = $v_sql;
		//}
		
		return $va_club_type; // returns array (key => value) of available club types for club category. 
							  // There may be different sets of clubs offered to men vs women.
	
	}
	
	/******************************
	 * Function: getManufacturers
	 * Purpose: returns a list of Brands based on club availability and by customer category
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayClubSelection
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getManufacturers() {
		
		global $mssql_db;
		
		$v_sql = "
			SELECT DISTINCT 
				manuf.brand
			FROM 
				r12pricing.dbo.GSI_CUS_MODELS model INNER JOIN 
					r12pricing.dbo.GSI_ITEM_INFO manuf ON model.inventory_item_id = manuf.inventory_item_id
				
			WHERE 
				((model.end_date IS NULL AND GETDATE() > model.start_date) OR 
				(GETDATE() BETWEEN model.start_date AND model.end_date)) AND 
				model.customer_category = '" . $this->_category . "' and 
				model.club_type = '" . $this->_club_type . "'
		";
		
		$v_result = mssql_query ( $v_sql );
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "getManufacturers query from Club model" );
			//$this->SendError($v_sql,"getManufacturers");
		}
		
		$va_manufacturers = array ();
		
		while ( $v_row = mssql_fetch_array ( $v_result ) ) {
			$va_manufacturers [$v_row ['brand']] = $v_row ['brand'];
		}
		
		return $va_manufacturers;
	}
	
	/******************************
	 * Function: getModels
	 * Purpose: returns a list of styles and models of clubs based on the Brands 
	 * 	based on club availability and by customer category, club type, and by
	 *  manufacturer
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: getModels
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getModels() {
		
		global $mssql_db;
		
		$v_sql= "
				SELECT 	
					model.inventory_item_id,
					sia.description,
					model.availability_date
				FROM 
					r12pricing.dbo.GSI_STYLE_INFO_ALL sia INNER JOIN 
						r12pricing.dbo.GSI_ITEM_INFO info LEFT OUTER JOIN
							r12pricing.dbo.GSI_CUS_MODELS model 
							ON info.inventory_item_id = model.inventory_item_id
						ON sia.style_number=info.segment1
				WHERE 
					((model.end_date IS NULL AND GETDATE() > model.start_date) OR 
					(GETDATE() BETWEEN model.start_date AND model.end_date)) and 
					model.customer_category = '" . $this->_category . "' and 
					model.club_type='" . $this->_club_type . "' and 
					info.brand='" . $this->_manufacturer . "' and
					model.STATUS = 'SUBMITTED'";
		$v_result = mssql_query ( $v_sql );
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "getModels query from Club model" );
			//$this->SendError($v_sql,"getModels");
		}
		
		$va_model = array ();
		
		while ( $v_row = mssql_fetch_array ( $v_result ) ) {
			#Ticket TICK:45265
			#$va_model [$v_row ['inventory_item_id']] = ucwords(strtolower($v_row ['description']));
			$va_model [$v_row ['inventory_item_id']] = $v_row ['description'];
			 
			#$va_model [$v_row ['description']] = $v_row ['description']; 
			#$va_model [$v_row ['availability_date']] = $v_row ['availability_date']; 
		}
		//$va_model = $v_sql;
		return $va_model; // returns array("style_number"=>"model_name");
	
	}
	
	/******************************
	 * Function: getModelStyle
	 * Purpose: returns model style from inventory_item_id
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayProductImage
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 3/17/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getProductImages() {
		
		global $mssql_db;
		
		// fetch the style from inventory_item_id
		$v_sql = "
			SELECT 
				style_image
			FROM 
				r12pricing.dbo.GSI_CUS_MODELS as models
			WHERE 
				((models.end_date IS NULL AND GETDATE() > models.start_date) OR 
				(GETDATE() BETWEEN models.start_date AND models.end_date)) AND 
				inventory_item_id ='" . $this->_inventory_item_id . "'";
		
		$v_result = mssql_query ( $v_sql );
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "getModels query from Club model" );
			//$this->SendError($v_sql,"getProductImages");
		}
		
		if (mssql_num_rows( $v_result )> 0) {
			while ( $v_row = mssql_fetch_array ( $v_result ) ) {
				mssql_free_result( $v_result );
				return $v_row ['style_image']; 
			}	
		} 
	}
	
	/******************************
	 * Function: getMyFitting
	 * Purpose: my fitting selections (Steps 1 - 4) 
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getMyFitting() {
		global $mssql_db;
		
		$v_sql = "
			SELECT 
				fitting_id, 
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
				session_id = '" . $this->_session_id . "'";
		
		$v_result = mssql_query ( $v_sql );
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "getMyClubs query from Club model" );
			//$this->SendError($v_sql,"getMyFitting");
		}
		
		//echo $v_sql;
		
		$va_fitting = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while ($row = mssql_fetch_assoc($v_result)) {
				$col = array();
				$col["fitting_id"]		= $row["fitting_id"];
				$col["smart_fit_flag"]	= $row["smart_fit_flag"];
				$col["club_category"]	= $row["club_category"];
				$col["player_height"]	= $row["player_height"];
				$col["wrist_to_floor"]	= $row["wrist_to_floor"];
				$col["hand_size"]		= $row["hand_size"];
				$col["finger_size"]		= $row["finger_size"];
				$col["swing_speed"]		= $row["swing_speed"];
				$col["driver_distance"]	= $row["driver_distance"];
				$col["trajectory"]		= $row["trajectory"];
				$col["tempo"]			= $row["tempo"];
				$col["target_swing"]	= $row["target_swing"];
				$col["started"]			= $row["started"];
				$va_fitting[$c++] = $col;				
			}	
		}
		mssql_free_result($v_result);	
		return $va_fitting; // returns array("style_number"=>"model_name");
	}
	
	/******************************
	 * Function: getMyClubs
	 * Purpose: returns my selected club choices from Step 1
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 3/15/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getMyClubs() {
		global $mssql_db;
		
		$v_sql = "
			SELECT 
				clubs.club_id, 
				clubs.club_type, 
				clubs.manufacturer, 
				clubs.model, 
				clubs.dexterity, 
				clubs.shaft_model, 
                clubs.shaft_model_price,
                clubs.shaft_flex, 
                clubs.shaft_model_combo, 
                clubs.shaft_model_price_combo,
                clubs.shaft_flex_combo, 
                clubs.shaft_club_length, 
                clubs.lie_angle, 
                clubs.grip_size, 
                clubs.grip_model, 
                clubs.grip_model_price, 
                clubs.special_instructions, 
                clubs.serial_number, 
                clubs.service_price,
                clubs.started,
                sia.description,
                models.style_image,
                models.standard_lie_angle,
                models.standard_length,
                models.retail_price,
                models.availability_date,
                models.style,
				models.inventory_item_id
			FROM 
				direct.dbo.gsi_cust_clubs AS clubs INNER JOIN
					r12pricing.dbo.gsi_cus_models as models on clubs.model = models.inventory_item_id INNER JOIN
						r12pricing.dbo.gsi_item_info AS info ON models.inventory_item_id = info.inventory_item_id INNER JOIN
							r12pricing.dbo.gsi_style_info_all AS sia on info.segment1=sia.style_number			
			WHERE 
				((models.end_date IS NULL AND GETDATE() > models.start_date) OR 
				(GETDATE() BETWEEN models.start_date AND models.end_date)) AND 
				fitting_id = '" . $this->_fitting_id . "'";

		$v_result = mssql_query ( $v_sql );
		
		if (! $v_result) {
			display_mssql_error ( $v_sql, "getMyClubs query from Club model" );
			//$this->SendError($v_sql,"getMyClubs");
		}
		
		$va_model = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while ($va_row = mssql_fetch_assoc($v_result)) {
				$va_col = array();
				$va_col["club_id"]					= $va_row["club_id"];
				$va_col["club_type"]				= $va_row["club_type"];
				$va_col["manufacturer"]				= $va_row["manufacturer"];
				$va_col["model"]					= $va_row["model"];
				$va_col["dexterity"]				= $va_row["dexterity"];
				$va_col["shaft_model"]				= $va_row["shaft_model"];
				$va_col["shaft_model_price"]		= $va_row["shaft_model_price"];
				$va_col["shaft_flex"]				= $va_row["shaft_flex"];
				$va_col["shaft_model_combo"]		= $va_row["shaft_model_combo"];
				$va_col["shaft_model_price_combo"]	= $va_row["shaft_model_price_combo"];
				$va_col["shaft_flex_combo"]			= $va_row["shaft_flex_combo"];
				$va_col["shaft_club_length"]		= $va_row["shaft_club_length"];
				$va_col["lie_angle"]				= $va_row["lie_angle"];
				$va_col["grip_size"]				= $va_row["grip_size"];
				$va_col["grip_model"]				= $va_row["grip_model"];
				$va_col["grip_model_price"]			= $va_row["grip_model_price"];
				$va_col["special_instructions"]		= $va_row["special_instructions"];
				$va_col["serial_number"]			= $va_row["serial_number"];
				$va_col["service_price"]			= $va_row["service_price"];
				$va_col["started"]					= $va_row["started"];
				$va_col["description"]				= $va_row["description"];
				$va_col["standard_length"]			= $va_row["standard_length"];
				$va_col["standard_lie_angle"]		= $va_row["standard_lie_angle"];
				$va_col["style_image"]				= $va_row["style_image"];
				$va_col["retail_price"]				= $va_row["retail_price"];
				$va_col["inventory_item_id"]				= $va_row["inventory_item_id"];
				$va_col["availability_date"]		= $va_row["availability_date"];
				$va_col["style"]					= $va_row["style"];
				$va_model[$c++] = $va_col;				
			}		
		}
		mssql_free_result($v_result);
		return $va_model; // returns array("style_number"=>"model_name");
	}
	
	/******************************
	 * Function: getMyClubHeads
	 * Purpose: returns selected club heads
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayReview
	 * Comment Date: 4/8/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getMyClubHeads($p_club_id) {
		global $mssql_db;
		
		$v_sql = "
			SELECT     
				hybrid_flag,
				club_selection,
				price
			FROM        
				direct.dbo.gsi_cust_club_selection
			WHERE     
				club_id = '" . $p_club_id . "'
			ORDER BY 
				club_selection_id";
            
		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			//$this->SendError($v_sql,"getMyClubHeads");
		}

		$va_set = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['hybrid_flag'] 		= $v_row['hybrid_flag'];
				$va_col['club_selection'] 	= $v_row['club_selection'];
				$va_col['price'] 			= $v_row['price'];
				$va_set[$c++] = $va_col;
			}
		} 

        mssql_free_result($v_result);
        return $va_set;
	}
	
	/******************************
	 * Function: getMyClubServices
	 * Purpose: returns selected services and customizations by club id
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayReview
	 * Comment Date: 5/24/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getMyClubServices($p_club_id) {
		global $mssql_db;
		
		$v_sql = "
			SELECT     
				service_id,
				club_id,
				description,
				user_action,
				attribute_name,
				display_label,
				service_level,
				value
			FROM        
				direct.dbo.gsi_cust_club_services
			WHERE     
				club_id = '" . $p_club_id . "'
			ORDER BY 
				service_id";
            
		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			//$this->SendError($v_sql,"getMyClubServices");
		}

		$va_services = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['service_id'] 		= $v_row['service_id'];
				$va_col['club_id'] 			= $v_row['club_id'];
				$va_col['description'] 		= $v_row['description'];
				$va_col['user_action'] 		= $v_row['user_action'];
				$va_col['attribute_name'] 	= $v_row['attribute_name'];
				$va_col['display_label'] 	= $v_row['display_label'];
				$va_col['service_level'] 	= $v_row['service_level'];
				$va_col['value'] 			= $v_row['value'];
				$va_services[$c++] = $va_col;
			}
		} else 
			$va_services = $v_sql;

        mssql_free_result($v_result);
        return $va_services;
	}
	
	/******************************
	 * Function: GetShaftsCustoClub
	 * Purpose: based on the fitting session, and selected trajectory, this function
	 * 	returns all the shaft possibilities for selection, shaft variences, and flex
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 3/22/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetShaftsCustoClub($p_inventory_item_id) {
	    global $mssql_db;
	
	    //$v_trajectory = "";
	    //if ($p_trajectory != "") {
	    //$v_trajectory = " shafts.trajectory = '" . $p_trajectory . "' and";
	    //$v_trajectory
	    //}
	
	    $v_sql = "
			SELECT
				DISTINCT modelshafts.inventory_item_id,
				shafts.trajectory,
				shafts.brand,
				shafts.description,
				modelshafts.shaft_id,
				modelshafts.retail_price,
				modelshafts.min_shaft_length,
				modelshafts.max_shaft_length,
				modelshafts.shaft_increment,
				modelshafts.hybrid_flag,
                modelshafts.cost
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING as fitting INNER JOIN
					direct.dbo.GSI_CUST_CLUBS AS clubs ON fitting.fitting_id = clubs.fitting_id INNER JOIN
					r12pricing.dbo.GSI_CUS_MODEL_SHAFTS AS modelshafts ON clubs.model = modelshafts.inventory_item_id INNER JOIN
					r12pricing.dbo.GSI_CUS_SHAFTS as shafts ON modelshafts.shaft_id = shafts.shaft_id
			WHERE
				((modelshafts.end_date IS NULL AND GETDATE() > modelshafts.start_date) OR
				(GETDATE() BETWEEN modelshafts.start_date AND modelshafts.end_date)) AND
				modelshafts.inventory_item_id = '" . $p_inventory_item_id . "'
			ORDER BY
				shafts.brand,
				shafts.description
		";
	    $v_result = mssql_query ($v_sql);
	
	    if (!$v_result) {
	        display_mssql_error($v_sql);
	        //$this->SendError($v_sql,"GetShafts");
	    }
	
	    #echo $v_sql . "<BR><BR>";
	
	    $va_shafts = array ();
	
	    if (mssql_num_rows($v_result)>0) {
	        $c = 0;
	        while($v_row = mssql_fetch_assoc ($v_result)) {
	            $va_col = array();
	            $va_col['inventory_item_id']	= $v_row['inventory_item_id'];
	            $va_col['trajectory'] 			= $v_row['trajectory'];
	            $va_col['description'] 			= $v_row['brand'] ." ". $v_row['description'];
	            $va_col['shaft_id'] 			= $v_row['shaft_id'];
	            $va_col['retail_price'] 		= $v_row['retail_price'];
                $va_col['cost_price'] 		    = $v_row['cost'];
	            $va_col['min_shaft_length'] 	= $v_row['min_shaft_length'];
	            $va_col['max_shaft_length'] 	= $v_row['max_shaft_length'];
	            $va_col['shaft_increment'] 		= $v_row['shaft_increment'];
	            $va_col['hybrid_flag'] 			= $v_row['hybrid_flag'];
	            $va_shafts[$c++] = $va_col;
	        }
	    } else {
	        $va_shafts = $v_sql;
	    }
	
	    mssql_free_result($v_result);
	    return $va_shafts;
	}
	
	

	public function getCostValues($inventory_item_id, $shaft_id, $grip_id, $dexterity){
	    $v_sql =   "SELECT cost FROM r12pricing.dbo.GSI_CUS_MODEL_SHAFTS s
	    WHERE INVENTORY_ITEM_ID = '{$inventory_item_id}'
	    and SHAFT_ID = '{$shaft_id}'
	    and hand = '{$dexterity}' ";


	    $v_result = mssql_query ($v_sql);
	
	    if (!$v_result) {
	    display_mssql_error($v_sql);
	    //$this->SendError($v_sql,"getClubSet");
	    }
	
	    $v_row = mssql_fetch_assoc ($v_result);
	    
	    
	    //Find grip cost
	    $v_sql2 =   "SELECT cost FROM r12pricing.dbo.GSI_CUS_MODEL_GRIPS
	    WHERE INVENTORY_ITEM_ID = '{$inventory_item_id}'
	    and GRIP_ID = '{$grip_id}'
	    and hand = '{$dexterity}' ";
	    
	    $v_result2 = mssql_query ($v_sql2);
	    
	    if (!$v_result) {
	        display_mssql_error($v_sql2);
	        //$this->SendError($v_sql,"getClubSet");
	    }
	    
	    $v_row2 = mssql_fetch_assoc ($v_result2);
	     
	    mssql_free_result($v_result);
	    mssql_free_result($v_result2);
	
	    $response = array();
	    $response['shaft_model_cost'] = $v_row['cost'];
	    $response['grip_model_cost'] = $v_row2['cost'];

	    
        return $response;
    	
    }
    
    
    
    function getServiceCostValues( $additional_services, $iid ){

        $services = array();

        
        $services_id = implode(",", $additional_services);
        
        
        $v_sql =   "SELECT cost AS total_cost
                    FROM r12pricing.[dbo].[gsi_cus_model_services] ms
                    WHERE service_id in ({$services_id})
                    AND inventory_item_id = {$iid}";
        

        $v_result = mssql_query ($v_sql);
        
        if (!$v_result) {
            display_mssql_error($v_sql);
            //$this->SendError($v_sql,"getClubSet");
        }
        

        $response = array();
        $total = 0;
        while($v_row = mssql_fetch_assoc ($v_result)){
            $total += $v_row['total_cost'];
        }
        
        $response['total'] = $total;
        
        mssql_free_result($v_result);
        
        return $response;

    }
    	
	
	/******************************
	 * Function: GetShafts
	 * Purpose: based on the fitting session, and selected trajectory, this function
	 * 	returns all the shaft possibilities for selection, shaft variences, and flex
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 3/22/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetShafts($p_fitting_id, $p_trajectory, $p_inventory_item_id) {
		global $mssql_db;
		
		//$v_trajectory = "";
		//if ($p_trajectory != "") {
			//$v_trajectory = " shafts.trajectory = '" . $p_trajectory . "' and";
			//$v_trajectory
		//}
		
		$v_sql = "
			SELECT 
				DISTINCT modelshafts.inventory_item_id,
				shafts.trajectory,
				shafts.brand,
				shafts.description,
				modelshafts.shaft_id, 
				modelshafts.retail_price,
				modelshafts.min_shaft_length, 
				modelshafts.max_shaft_length, 
				modelshafts.shaft_increment,
				modelshafts.hybrid_flag
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING as fitting INNER JOIN 
					direct.dbo.GSI_CUST_CLUBS AS clubs ON fitting.fitting_id = clubs.fitting_id INNER JOIN
					r12pricing.dbo.GSI_CUS_MODEL_SHAFTS AS modelshafts ON clubs.model = modelshafts.inventory_item_id INNER JOIN 
					r12pricing.dbo.GSI_CUS_SHAFTS as shafts ON modelshafts.shaft_id = shafts.shaft_id
			WHERE
				((modelshafts.end_date IS NULL AND GETDATE() > modelshafts.start_date) OR 
				(GETDATE() BETWEEN modelshafts.start_date AND modelshafts.end_date)) AND 
				fitting.session_id = '" . $p_fitting_id . "' and
				modelshafts.inventory_item_id = '" . $p_inventory_item_id . "'
			ORDER BY
				shafts.brand,
				shafts.description
		";
//echo "GetShafts".$v_sql;	
		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			//$this->SendError($v_sql,"GetShafts");
		}
		
		#echo $v_sql . "<BR><BR>";

		$va_shafts = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['inventory_item_id']	= $v_row['inventory_item_id'];
				$va_col['trajectory'] 			= $v_row['trajectory'];
				$va_col['description'] 			= $v_row['brand'] ." ". $v_row['description'];
				$va_col['shaft_id'] 			= $v_row['shaft_id'];
				$va_col['retail_price'] 		= $v_row['retail_price'];
				$va_col['min_shaft_length'] 	= $v_row['min_shaft_length'];
				$va_col['max_shaft_length'] 	= $v_row['max_shaft_length'];
				$va_col['shaft_increment'] 		= $v_row['shaft_increment'];
				$va_col['hybrid_flag'] 			= $v_row['hybrid_flag'];
				$va_shafts[$c++] = $va_col;
			}
		} else {
			$va_shafts = $v_sql;
		}

        mssql_free_result($v_result);
        return $va_shafts;
	}
	/******************************
	 * Function: GetGripsClubs
	 * Purpose: based on the club inventory item id, this function
	 * 	returns all the grips, images, and standard grip retail price
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/2/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetGripsClubs($p_inventory_item_id, $p_dexterity) {
	    global $mssql_db;
	
	    #if ($p_dexterity != "") { // user must select a hand first.
	    $v_sql = "SELECT 
                        models.club_type,
                        grips.grip_id,
                        grips.description,
                        grips.brand,
                        grips.style_image,
                        modelgrips.retail_price,
                        modelgrips.hand,
                        modelgrips.availability_date
                    FROM
                        r12pricing.dbo.gsi_cus_models AS models INNER JOIN
                        r12pricing.dbo.gsi_cus_model_grips AS modelgrips ON models.inventory_item_id = modelgrips.inventory_item_id INNER JOIN
                        r12pricing.dbo.gsi_cus_grips AS grips ON modelgrips.grip_id = grips.grip_id
                    WHERE
                        ((modelgrips.end_date IS NULL AND GETDATE() > modelgrips.start_date) OR
                        (GETDATE() BETWEEN modelgrips.start_date AND modelgrips.end_date))
                        AND modelgrips.inventory_item_id = '" . $p_inventory_item_id . "'
                        AND modelgrips.hand = '" . $p_dexterity . "'
                    ORDER BY
                        grips.brand,
                        grips.description";
	    $v_result = mssql_query ($v_sql);

	    if (!$v_result) {
	        display_mssql_error($v_sql);
	        //$this->SendError($v_sql,"GetGrips");
	    }
	
	    $va_shafts = array ();
	    if (mssql_num_rows($v_result)>0) {
	        $c = 0;
	        while($v_row = mssql_fetch_assoc ($v_result)) {
	            $va_col = array();
	            $va_col['club_type'] 			= $v_row['club_type'];
	            $va_col['grip_id'] 				= $v_row['grip_id'];
	            $va_col['description'] 			= $v_row['brand']." ".$v_row['description'];
	            $va_col['style_image'] 			= $v_row['style_image'];
	            $va_col['retail_price'] 		= $v_row['retail_price'];
	            $va_col['hand'] 				= $v_row['hand'];
	            $va_col['availability_date'] 	= $v_row['availability_date'];
	            $va_shafts[$c++] = $va_col;
	        }
	    }
	
	    mssql_free_result($v_result);
	    return $va_shafts;
	    #}
	}
	
	
	/******************************
	 * Function: GetGrips
	 * Purpose: based on the fitting session, and club inventory item id, this function
	 * 	returns all the grips, images, and standard grip retail price
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/2/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetGrips($p_fitting_id, $p_inventory_item_id, $p_dexterity) {
		global $mssql_db;
		
		#if ($p_dexterity != "") { // user must select a hand first.
			$v_sql = "
				SELECT 
					models.club_type,
					grips.grip_id, 
					grips.description,
					grips.brand, 
					grips.style_image, 
					modelgrips.retail_price,
					modelgrips.hand,
					modelgrips.availability_date
				FROM
					 direct.dbo.gsi_cust_club_fitting AS fitting INNER JOIN
	                      direct.dbo.gsi_cust_clubs AS clubs ON fitting.fitting_id = clubs.fitting_id INNER JOIN
	                      r12pricing.dbo.gsi_cus_models AS models ON clubs.model = models.inventory_item_id INNER JOIN
	                      r12pricing.dbo.gsi_cus_model_grips AS modelgrips ON models.inventory_item_id = modelgrips.inventory_item_id INNER JOIN
	                      r12pricing.dbo.gsi_cus_grips AS grips ON modelgrips.grip_id = grips.grip_id
				WHERE
					((modelgrips.end_date IS NULL AND GETDATE() > modelgrips.start_date) OR 
					(GETDATE() BETWEEN modelgrips.start_date AND modelgrips.end_date)) AND 
					fitting.session_id = '" . $p_fitting_id . "' AND
					modelgrips.inventory_item_id = '" . $p_inventory_item_id . "' AND
					modelgrips.hand = '" . $p_dexterity . "'
				ORDER BY
					grips.brand,
					grips.description
			";

			$v_result = mssql_query ($v_sql);
			
			if (!$v_result) {
				display_mssql_error($v_sql);
				//$this->SendError($v_sql,"GetGrips");
			}
	
			$va_shafts = array ();
			if (mssql_num_rows($v_result)>0) {
				$c = 0;
				while($v_row = mssql_fetch_assoc ($v_result)) {
					$va_col = array();
					$va_col['club_type'] 			= $v_row['club_type'];
					$va_col['grip_id'] 				= $v_row['grip_id'];
					$va_col['description'] 			= $v_row['brand']." ".$v_row['description'];
					$va_col['style_image'] 			= $v_row['style_image'];
					$va_col['retail_price'] 		= $v_row['retail_price'];
					$va_col['hand'] 				= $v_row['hand'];
					$va_col['availability_date'] 	= $v_row['availability_date'];
					$va_shafts[$c++] = $va_col;
				}
			} 
	
	        mssql_free_result($v_result);
	        return $va_shafts;
		#}
	}
	
	/******************************
	 * Function: GetShaftLengths
	 * Purpose: based on the fitting session, and club inventory item id, this function
	 * 	returns all the grips, images, and standard grip retail price
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/2/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetShaftLengths($p_wrist_to_floor,$p_club_category) {
		global $mssql_db;
		
		/*$v_sql = "
			SELECT 
				iron_shaft_length, 
				driver_shaft_length
			FROM
				pricing.dbo.gsi_cus_shaft_length
			WHERE
				wtofloor = '" . $p_wrist_to_floor . "' and 
				cust_category = '" . $p_club_category . "'";
            
		//$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			//$this->SendError($v_sql,"GetShaftLengths");
		}

		$va_lengths = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['iron'] = $v_row['iron_shaft_length'];
				$va_col['driver'] = $v_row['driver_shaft_length'];
				$va_lengths[$c++] = $va_col;
			}
		}

        mssql_free_result($v_result);*/
        return 'Select';
	}
	
	/******************************
	 * Function: GetFlexOptions
	 * Purpose: returns shaft flex options based on the club model
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/5/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetFlexOptions($p_fitting_id, $p_trajectory, $p_inventory_item_id, $p_shaft_id, $p_dexterity) {
		global $mssql_db;
		
		$v_trajectory = "";
		#if ($p_trajectory != "") {
		#	$v_trajectory = "shafts.trajectory = '" . $p_trajectory . "' and ";
		#}
		
		$v_sql = "
			SELECT 
				DISTINCT
				options.flex,
				options.availability_date,
				shafts.trajectory
			FROM
				direct.dbo.GSI_CUST_CLUBS AS clubs INNER JOIN 
					r12pricing.dbo.GSI_CUS_MODEL_SHAFTS AS modelshafts ON clubs.model = modelshafts.inventory_item_id INNER JOIN 
					r12pricing.dbo.GSI_CUS_SHAFTS as shafts ON modelshafts.shaft_id = shafts.shaft_id INNER JOIN
					r12pricing.dbo.GSI_CUS_MODEL_SHAFT_OPTIONS AS options ON  modelshafts.shaft_id = options.shaft_id and modelshafts.inventory_item_id = options.inventory_item_id
			WHERE
				$v_trajectory
				modelshafts.inventory_item_id = '" . $p_inventory_item_id . "' and
				modelshafts.shaft_id = '" . $p_shaft_id . "' and 
				options.hand = '" . $p_dexterity . "' and 
				modelshafts.hand = '" . $p_dexterity . "'
			ORDER BY
				shafts.trajectory desc
		";
		
		$v_result = mssql_query ($v_sql);
		
		#echo $v_sql;
		
		if (!$v_result) {
			// The query has failed, print a nice error message
		    // using mssql_get_last_message()
		    display_mssql_error($v_sql);
			#$this->SendError($v_sql,"GetFlexOptions");
		}
		
		$va_shafts = array ();
		
		if (mssql_num_rows($v_result)>0) {
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['flex'] = $v_row['flex'];
				$va_col['availability_date'] = $v_row['availability_date'];
				$va_shafts[$c++] = $va_col;
			}
		} 

        mssql_free_result($v_result);
        return $va_shafts;
	}		
	
	/******************************
	 * Function: GetLieAngleOptionsClub
	 * Purpose: returns lie angles based on club selection
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/5/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetLieAngleOptionsClub($p_inventory_item_id, $p_dexterity) {
	    global $mssql_db;
	
	    $v_sql = "
			SELECT
				DISTINCT angles.inventory_item_id,
				angles.hand,
				angles.min_lie_angle,
				angles.max_lie_angle,
				angles.lie_increment,
				models.standard_lie_angle
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING as fitting INNER JOIN
					direct.dbo.GSI_CUST_CLUBS AS clubs ON fitting.fitting_id = clubs.fitting_id INNER JOIN
					r12pricing.dbo.GSI_CUS_MODELS AS models on clubs.model = models.inventory_item_ID INNER JOIN
					r12pricing.dbo.GSI_CUS_MODEL_HEADS AS modelheads ON models.inventory_item_ID = modelheads.inventory_item_id INNER JOIN
					r12pricing.dbo.GSI_CUS_MODEL_LIE_ANGLES as angles ON modelheads.inventory_item_id = angles.inventory_item_id
			WHERE
				((models.end_date IS NULL AND GETDATE() > models.start_date) OR
				(GETDATE() BETWEEN models.start_date AND models.end_date)) AND
				angles.inventory_item_id = '" . $p_inventory_item_id . "' and
				angles.hand = '" . $p_dexterity . "' and
				angles.lie_increment != 0 and (
					(angles.min_lie_angle != 0 and angles.max_lie_angle !=0) OR
					(angles.min_lie_angle = 0 and angles.max_lie_angle !=0) OR
					(angles.min_lie_angle != 0 and angles.max_lie_angle =0)
				)
		";
	

	    $v_result = mssql_query ($v_sql);
	
	    if (!$v_result) {
	        // The query has failed, print a nice error message
	        // using mssql_get_last_message()
	        #die('MSSQL error: ' . mssql_get_last_message() . $v_sql . $mssql_db);
	        //$this->SendError($v_sql,"GetLieAngleOptions");
	    }
	
	    $va_lie_angles = array ();
	
	    if (mssql_num_rows($v_result)>0) {
	        $c = 0;
	        while ($row = mssql_fetch_assoc($v_result)) {
	            $col = array();
	            $col["inventory_item_id"]		= $row["inventory_item_id"];
	            $col["hand"]					= $row["hand"];
	            $col["min_lie_angle"]			= $row["min_lie_angle"];
	            $col["max_lie_angle"]			= $row["max_lie_angle"];
	            $col["lie_increment"]			= $row["lie_increment"];
	            $col["standard_lie_angle"]		= $row["standard_lie_angle"];
	            $va_lie_angles[$c++] = $col;
	        }
	    } else
	        $va_lie_angles = $v_sql;
	    mssql_free_result($v_result);
	    return $va_lie_angles; // returns array("style_number"=>"model_name");
	}
	
	
	/******************************
	 * Function: GetLieAngleOptions
	 * Purpose: returns lie angles based on club selection
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/5/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetLieAngleOptions($p_session_id, $p_inventory_item_id, $p_dexterity) {
		global $mssql_db;
		
		$v_sql = "
			SELECT 
				DISTINCT angles.inventory_item_id,
				angles.hand,
				angles.min_lie_angle,
				angles.max_lie_angle,
				angles.lie_increment,
				models.standard_lie_angle
			FROM 
				direct.dbo.GSI_CUST_CLUB_FITTING as fitting INNER JOIN 
					direct.dbo.GSI_CUST_CLUBS AS clubs ON fitting.fitting_id = clubs.fitting_id INNER JOIN
					r12pricing.dbo.GSI_CUS_MODELS AS models on clubs.model = models.inventory_item_ID INNER JOIN
					r12pricing.dbo.GSI_CUS_MODEL_HEADS AS modelheads ON models.inventory_item_ID = modelheads.inventory_item_id INNER JOIN 
					r12pricing.dbo.GSI_CUS_MODEL_LIE_ANGLES as angles ON modelheads.inventory_item_id = angles.inventory_item_id 
			WHERE
				((models.end_date IS NULL AND GETDATE() > models.start_date) OR 
				(GETDATE() BETWEEN models.start_date AND models.end_date)) AND 
				fitting.session_id = '" . $p_session_id . "' and
				angles.inventory_item_id = '" . $p_inventory_item_id . "' and
				angles.hand = '" . $p_dexterity . "' and 
				angles.lie_increment != 0 and (
					(angles.min_lie_angle != 0 and angles.max_lie_angle !=0) OR
					(angles.min_lie_angle = 0 and angles.max_lie_angle !=0) OR	
					(angles.min_lie_angle != 0 and angles.max_lie_angle =0)
				)
		";
		
	
		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			// The query has failed, print a nice error message
		    // using mssql_get_last_message()
		    #die('MSSQL error: ' . mssql_get_last_message() . $v_sql . $mssql_db);
			//$this->SendError($v_sql,"GetLieAngleOptions");
		}
		
		$va_lie_angles = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while ($row = mssql_fetch_assoc($v_result)) {
				$col = array();
				$col["inventory_item_id"]		= $row["inventory_item_id"];
				$col["hand"]					= $row["hand"];
				$col["min_lie_angle"]			= $row["min_lie_angle"];
				$col["max_lie_angle"]			= $row["max_lie_angle"];
				$col["lie_increment"]			= $row["lie_increment"];
				$col["standard_lie_angle"]		= $row["standard_lie_angle"];
				$va_lie_angles[$c++] = $col;				
			}		
		} else 
			$va_lie_angles = $v_sql;
		mssql_free_result($v_result);
		return $va_lie_angles; // returns array("style_number"=>"model_name");
	}
	
	/******************************
	 * Function: getClubHeadOptions
	 * Purpose: returns club head options based on club selection
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/5/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getClubHeadOptions($p_inventory_item_id, $p_dexterity) {
		global $mssql_db;
		
		// check sets first
		$v_sql = "
			SELECT     
				sets.set_id, 
				models.club_type, 
				modelheads.hand, 
				heads.head_id,
				heads.description, 
				heads.hybrid_flag, 
                sets.description as setdescription, 
                modelheads.ind_retail, 
                modelheads.set_retail, 
                modelheads.availability_date,
            	modelheads.start_date,
			    modelheads.end_date
			FROM         
				r12pricing.dbo.GSI_CUS_MODELS AS models INNER JOIN
                   r12pricing.dbo.GSI_CUS_MODEL_SETS AS modelsets ON models.INVENTORY_ITEM_ID = modelsets.INVENTORY_ITEM_ID INNER JOIN
                   r12pricing.dbo.GSI_CUS_SETS AS sets ON modelsets.SET_ID = sets.SET_ID INNER JOIN
                   r12pricing.dbo.GSI_CUS_MODEL_HEADS AS modelheads ON models.INVENTORY_ITEM_ID = modelheads.INVENTORY_ITEM_ID INNER JOIN
                   r12pricing.dbo.GSI_CUS_HEADS AS heads ON modelheads.HEAD_ID = heads.HEAD_ID AND modelheads.HEAD_ID = sets.HEAD_ID
				
			WHERE     
				(models.INVENTORY_ITEM_ID = '$p_inventory_item_id') AND 
				(modelheads.HAND = '$p_dexterity') AND 
				(modelsets.HAND = '$p_dexterity') AND 
				(sets.ACTIVE_FLAG = 'Y') AND
				(modelheads.start_date <= getdate() AND  isnull(modelheads.END_DATE, getdate()) >= getdate() )
			ORDER BY 
				heads.description
		";
        //echo $v_sql;
		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			#$this->SendError($v_sql,"getClubHeadOptions");
		}

		
		$va_set = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['set_id']			= $v_row['set_id'];
				$va_col['club_type'] 		= $v_row['club_type'];
				$va_col['hand'] 			= $v_row['hand'];
				$va_col['head_id'] 			= $v_row['head_id'];
				$va_col['description'] 		= $v_row['description'];
				$va_col['hybrid_flag'] 		= $v_row['hybrid_flag'];
				$va_col['setdescription']  	= $v_row['setdescription'];
				$va_col['ind_retail'] = $v_row['ind_retail'];
				$va_col['set_retail'] = $v_row['set_retail'];
				$va_col['availability_date']= $v_row['availability_date'];
				$va_set[$c++] = $va_col;
		 	}
		} else { // try the non-set option
			$v_sql = "
				SELECT     
					models.club_type, 
					modelheads.hand,
					heads.head_id, 
                    heads.description, 
                    heads.hybrid_flag, 
                    modelheads.ind_retail, 
                    modelheads.set_retail, 
                	modelheads.availability_date,
                	modelheads.start_date,
				    modelheads.end_date
				FROM         
					r12pricing.dbo.gsi_cus_models as models inner join
                      r12pricing.dbo.gsi_cus_model_heads as modelheads on models.inventory_item_id = modelheads.inventory_item_id inner join
                      r12pricing.dbo.gsi_cus_heads as heads on modelheads.head_id = heads.head_id
	            WHERE     
					(modelheads.inventory_item_id = '$p_inventory_item_id') AND 
					(modelheads.hand = '$p_dexterity') AND
					(modelheads.start_date <= getdate() AND  isnull(modelheads.END_DATE, getdate()) >= getdate() )
				ORDER BY 
					heads.description
			";
	        #echo $v_sql;

			$v_result = mssql_query ($v_sql);
			
			if (!$v_result) {
				display_mssql_error($v_sql);
				#$this->SendError($v_sql,"getClubHeadOptions");
			}
			$va_set = array ();
			
			if (mssql_num_rows($v_result)>0) {
				$c = 0;
				while($v_row = mssql_fetch_assoc ($v_result)) {
					$va_col = array();
					$va_col['set_id']			= $v_row['set_id']; //
					$va_col['club_type'] 		= $v_row['club_type'];
					$va_col['hand'] 			= $v_row['hand']; 
					$va_col['head_id'] 			= $v_row['head_id'];
					$va_col['description'] 		= $v_row['description'];
					$va_col['hybrid_flag'] 		= $v_row['hybrid_flag'];
					$va_col['setdescription']  	= $v_row['setdescription']; //
					$va_col['ind_retail'] 		= $v_row['ind_retail'];
					$va_col['set_retail'] 		= $v_row['set_retail'];
					$va_col['availability_date']= $v_row['availability_date'];
					$va_set[$c++] = $va_col;
			 	}
			}
		}

        mssql_free_result($v_result);
        return $va_set;
	}
	
/******************************
	 * Function: getClubHeadOptions
	 * Purpose: returns club head options based on club selection
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/5/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getClubModelPrice($p_inventory_item_id) {
		global $mssql_db;
		
		$v_sql = "
			SELECT     
				retail_price
			FROM        
				r12pricing.dbo.gsi_cus_models as models
			WHERE     
				((models.end_date IS NULL AND GETDATE() > models.start_date) OR 
				(GETDATE() BETWEEN models.start_date AND models.end_date)) AND 
				inventory_item_id = '" . $p_inventory_item_id . "'
		";
            
		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			//$this->SendError($v_sql,"getClubModelPrice");
		}

		$va_set = 0;
		
		if (mssql_num_rows($v_result)>0) {
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_set = $v_row['retail_price'];
			}
		} 

        mssql_free_result($v_result);
        return $va_set;
	}
	
	/******************************
	 * Function: GetGripSize
	 * Purpose: returns grip recommendations based on the complete hand size
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/5/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetGripSize($p_club_category,$p_hand_size,$p_finger_size) {
		global $mssql_db;
		
		/************
		 * NEW QUERY:
		  	SELECT 
				DISTINCT 
				modelgrips.grip_id,
				clubs.club_id, 
				clubs.fitting_id, 
				clubs.club_type, 
				clubs.manufacturer, 
				clubs.model, 
				grips.description,
				options.record_value,
				modelgrips.availability_date
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING as fitting INNER JOIN 
				direct.dbo.GSI_CUST_CLUBS AS clubs ON fitting.fitting_id = clubs.fitting_id INNER JOIN
					pricing.dbo.GSI_CUS_MODEL_GRIPS as modelgrips ON clubs.model = modelgrips.inventory_item_id INNER JOIN
					pricing.dbo.GSI_CUS_GRIPS as grips ON grips.grip_id = modelgrips.grip_id INNER JOIN 
					pricing.dbo.GSI_CUS_GRIP_OPTIONS as options on grips.grip_id = options.grip_id
			WHERE
				options.record_type = 'SIZE' and
				fitting.session_id = '65277361315cd82f8053fa9a5291ce84'
			ORDER BY
				clubs.model
		 
		
		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			//$this->SendError($v_sql,"GetGripSize");
		}

		$v_grip = "";
		
		if (mssql_num_rows($v_result)>0) {
			while($v_row = mssql_fetch_assoc ($v_result)) {
		        $v_grip = $v_row['grip_reco'];
            }
		}
		mssql_free_result($v_result);*/
		return 'Select';
	}
	
	/******************************
	 * Function: GetAvailableGripSizes
	 * Purpose: returns grip available sizes based on the complete hand size
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/5/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function GetAvailableGripSizes($p_session_id, $p_inventory_item_id, $p_grip_id, $p_dexterity) {
		global $mssql_db;
		// Comment the next lines becuase it will be just fixed values 
		// 0, 1, 2, 3, 4, or 5 
		// Date Mar. 14th, 2016
		// Hosam Mahmoud
		/* $v_sql = "
			SELECT 
				modelgrips.grip_id,
				clubs.club_id, 
				clubs.fitting_id, 
				clubs.club_type, 
				clubs.manufacturer, 
				clubs.model, 
				grips.description,
				options.record_value
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING as fitting INNER JOIN 
				direct.dbo.GSI_CUST_CLUBS AS clubs ON fitting.fitting_id = clubs.fitting_id INNER JOIN
					r12pricing.dbo.GSI_CUS_MODEL_GRIPS as modelgrips ON clubs.model = modelgrips.inventory_item_id INNER JOIN
					r12pricing.dbo.GSI_CUS_GRIPS as grips ON grips.grip_id = modelgrips.grip_id INNER JOIN 
					r12pricing.dbo.GSI_CUS_GRIP_OPTIONS as options on grips.grip_id = options.grip_id
			WHERE
				options.record_type = 'GRIP SIZE' and
				fitting.session_id = '" . $p_session_id. "' and
				clubs.model = '" . $p_inventory_item_id . "' and 
				modelgrips.grip_id = '" . $p_grip_id . "' and 
				modelgrips.hand = '" . $p_dexterity . "'
			ORDER BY
				r12pricing.dbo.gsi_fraction_to_decimal(options.record_value)
		";
		 
		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			#$this->SendError($v_sql,"GetAvailableGripSizes");
		}

		$va_grip = array();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['grip_id'] 				= $v_row['grip_id'];
				$va_col['club_id'] 				= $v_row['club_id'];
				$va_col['fitting_id'] 			= $v_row['fitting_id'];
				$va_col['club_type'] 			= $v_row['club_type'];
				$va_col['manufacturer'] 		= $v_row['manufacturer'];
				$va_col['model'] 				= $v_row['model'];
				$va_col['description'] 			= $v_row['description'];
				$va_col['grip_size'] 			= $v_row['record_value'];
				$va_grip[$c++] = $va_col;
			}
		} else {
			$va_grip = $v_sql;
		
		}
		mssql_free_result($v_result); 
		for ($i=0;$i<6;$i++){
		    $va_grip[$i] = array("grip_size"=>$i);
		}*/
		
		
		// --------------------------------------------------------------------------------------
		// This Query only looks for the brand and if is TITLEIST it won't show any grip size
		// --------------------------------------------------------------------------------------
		$v_sql = "
		SELECT
		brands.brand_id,
		brands.BRAND_NAME,
		brands.DESCRIPTION,
		item.INVENTORY_ITEM_ID
		FROM r12pricing.[dbo].[GSI_BRANDS] as brands
		JOIN r12pricing.[dbo].[GSI_ITEM_INFO_ALL] as item
		ON brands.brand_id=item.brand_id
		WHERE item.INVENTORY_ITEM_ID = " . $p_inventory_item_id;
		
		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
		    display_mssql_error($v_sql);
		}
		
		$v_row = mssql_fetch_assoc ($v_result);
		
		$manufacture = $v_row['BRAND_NAME'];

		
		$va_grip["Standard"] = array("grip_size"=>"Standard");
		if($manufacture != 'TITLEIST'){
    		$va_grip["+1 Wrap"] =  array("grip_size"=>"+1 Wrap");
    		$va_grip["+2 Wrap"] =  array("grip_size"=>"+2 Wrap");
    		$va_grip["+3 Wrap"] =  array("grip_size"=>"+3 Wrap");
		}
		
		return $va_grip;
	}
	
	/******************************
	 * Function: getClubSet
	 * Purpose: returns, if available, the entire club set's hand choice, head_id, description,
	 * 	individual retail and set retail price that only applies if the entire set is selected
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/1/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function isClubSet($p_StyleNumber,$ii_inv) {
	    global $mssql_db;
	    $v_sql = " SELECT top 1 * FROM r12pricing.dbo.GSI_CUS_MODELS
                    where INVENTORY_ITEM_ID='".addslashes($ii_inv)."' AND club_type='Combo/Hybrid'
	        ";
	    //var_dump( $p_StyleNumber,$ii_inv,$v_sql);
	    $v_result = mssql_query ($v_sql);
	     
	    if (!$v_result) {
	        display_mssql_error($v_sql);
	        //$this->SendError($v_sql,"getClubSet");
	    }
	     
	    if (mssql_num_rows($v_result)>0) {
	        $c = 0;
	        while($v_row = mssql_fetch_assoc ($v_result)) {
	            return true;
	        }
	    }
	    mssql_free_result($v_result);
	     
/* 	    
	    $v_sql = " SELECT TOP 1 CATEGORY_NAME
                    FROM r12pricing.dbo.GSI_ITEM_DETAIL 
                    where segment1 in ('".addslashes($p_StyleNumber)."') AND CATEGORY_NAME='IRON SETS'
	        ";
var_dump( $p_StyleNumber,$ii_inv,$v_sql);	
	    $v_result = mssql_query ($v_sql);
	
	    if (!$v_result) {
	        display_mssql_error($v_sql);
	        //$this->SendError($v_sql,"getClubSet");
	    }
	
	    if (mssql_num_rows($v_result)>0) {
	        $c = 0;
	        while($v_row = mssql_fetch_assoc ($v_result)) {
                return true; 
	        }
	    } */
	    mssql_free_result($v_result);

	     
	    
	    return false;
	    
	}
	
	
	public function isHybrid($p_StyleNumber,$ii_inv) {
	    global $mssql_db;
	    $v_sql = " SELECT top 1 * FROM r12pricing.dbo.GSI_CUS_MODELS
                    where INVENTORY_ITEM_ID='".addslashes($ii_inv)."' AND club_type='Hybrids'
	        ";
	    //var_dump( $p_StyleNumber,$ii_inv,$v_sql);
	    $v_result = mssql_query ($v_sql);
	     
	    if (!$v_result) {
	        display_mssql_error($v_sql);
	        //$this->SendError($v_sql,"getClubSet");
	    }

	     
	    if (mssql_num_rows($v_result)>0) {
	        $c = 0;
	        while($v_row = mssql_fetch_assoc ($v_result)) {
	            return true;
	        }
	    }
	    mssql_free_result($v_result);
	    
	    
	    
	    
	    return false;
	    
	}
	
	
	public function getClubType($ii_inv) {
	    global $mssql_db;
	    $v_sql = " SELECT top 1 * FROM r12pricing.dbo.GSI_CUS_MODELS
                    where INVENTORY_ITEM_ID='".addslashes($ii_inv)."'
	        ";
	    //var_dump( $p_StyleNumber,$ii_inv,$v_sql);
	    $v_result = mssql_query ($v_sql);
	
	    if (!$v_result) {
	        display_mssql_error($v_sql);
	        //$this->SendError($v_sql,"getClubSet");
	    }

	    $v_row = mssql_fetch_assoc ($v_result);
	    
	    mssql_free_result($v_result);

	     
	    return $v_row['CLUB_TYPE'];
	     
	}
	
	/******************************
	 * Function: getClubSet
	 * Purpose: returns, if available, the entire club set's hand choice, head_id, description, 
	 * 	individual retail and set retail price that only applies if the entire set is selected
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/1/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getClubSet($p_inventory_item_id) {
		global $mssql_db;
		
		$v_sql = "
			SELECT
				DISTINCT   
				modelheads.hand,
				modelheads.head_id, 
				heads.description, 
				modelheads.ind_retail, 
				modelheads.set_retail
			FROM        
				r12pricing.dbo.GSI_CUS_MODEL_HEADS AS modelheads INNER JOIN
                      r12pricing.dbo.GSI_CUS_HEADS AS heads ON heads.HEAD_ID = modelheads.HEAD_ID INNER JOIN
					  r12pricing.dbo.GSI_CUS_SETS as sets on modelheads.head_id = sets.head_id INNER JOIN
					  r12pricing.dbo.GSI_CUS_MODEL_SETS as modelsets on sets.set_id = modelsets.set_id and modelsets.hand = modelheads.hand
           
			WHERE     
				((modelheads.end_date IS NULL AND GETDATE() > modelheads.start_date) OR 
				(GETDATE() BETWEEN modelheads.start_date AND modelheads.end_date)) AND 
				modelheads.inventory_item_id = '" . $p_inventory_item_id . "' and 
				sets.active_flag = 'Y'
			ORDER BY 
				modelheads.hand, 
				heads.description";
        //echo "getClubSet".$v_sql;

		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			//$this->SendError($v_sql,"getClubSet");
		}
		
		$va_set = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['hand'] 			= $v_row['hand'];
				$va_col['head_id'] 			= $v_row['head_id'];
				$va_col['description'] 		= $v_row['description'];
				$va_col['ind_retail'] = $v_row['ind_retail'];
				$va_col['set_retail'] = $v_row['set_retail'];
				$va_set[$c++] = $va_col;
			}
		} 
        mssql_free_result($v_result);
        return $va_set;
	}
	
	/******************************
	 * Function: getLieAngles
	 * Purpose: returns available lie angles based on the complete hand size
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 4/5/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getLieAngles() {
		global $mssql_db;
		
		$v_sql = "
			SELECT 
				DISTINCT angles.inventory_item_id,
				angles.hand,
				angles.min_lie_angle,
				angles.max_lie_angle,
				angles.lie_increment
			FROM 
				direct.dbo.GSI_CUST_CLUB_FITTING as fitting INNER JOIN 
				direct.dbo.GSI_CUST_CLUBS AS clubs ON fitting.fitting_id = clubs.fitting_id INNER JOIN 
				r12pricing.dbo.GSI_CUS_MODELS AS models on clubs.model = models.inventory_item_ID INNER JOIN
				r12pricing.dbo.GSI_CUS_MODEL_HEADS AS modelheads ON models.inventory_item_ID = modelheads.inventory_item_id INNER JOIN 
				r12pricing.dbo.GSI_CUS_MODEL_LIE_ANGLES as angles ON modelheads.inventory_item_id = angles.inventory_item_id 
			WHERE
				((modelheads.end_date IS NULL AND GETDATE() > modelheads.start_date) OR 
				(GETDATE() BETWEEN modelheads.start_date AND modelheads.end_date)) AND 
				angles.inventory_item_id = '" . $this->inventory_item_id . "' and
				angles.hand = '" . $this->hand;
            
		$v_result = mssql_query ($v_sql);
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			//$this->SendError($v_sql,"getLieAngles");
		}

		$va_lengths = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['inventory_item_id'] = $v_row['inventory_item_id'];
				$va_col['hand'] 			= $v_row['hand'];
				$va_col['min_lie_angle'] 	= $v_row['min_lie_angle'];
				$va_col['max_lie_angle'] 	= $v_row['max_lie_angle'];
				$va_col['lie_increment'] 	= $v_row['lie_increment'];
				$va_lengths[$c++] = $va_col;
			}
		}

        mssql_free_result($v_result);
        return $va_lengths;
	
	}
	
	/******************************
	 * Function: getAvailableServices
	 * Purpose: finds and returns all available services for this club
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 5/12/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getAvailableServices($p_inventory_item_id,$p_service) {
		global $mssql_db;
		
		if ($p_service != "") 	
			$v_service = " and se.description = '" . $p_service . "'";
		
		$v_sql = "
			SELECT
				ta.template_id,      
				ta.display_sequence,		 
				se.description,
				ms.retail_price,
				ms.service_id,      
				al.user_action,      
				al.attribute_name,      
				al.display_label,      
				ta.attribute_value,
				se.service_level,
				ms.availability_date,
                avl.DISPLAY_LABEL as display_label_colors
			FROM
				r12pricing.dbo.gsi_cus_model_services AS ms LEFT JOIN      
				r12pricing.dbo.gsi_per_template_attributes ta ON ms.template_id = ta.template_id LEFT JOIN 
				r12pricing.dbo.gsi_per_attribute_lookup al ON ta.attribute_name = al.attribute_name LEFT JOIN
				r12pricing.dbo.gsi_cus_services se ON se.service_id = ms.service_id 
                LEFT JOIN (select distinct av.ATTRIBUTE_VALUE,av.DISPLAY_LABEL from r12pricing.dbo.GSI_PER_ATTRIBUTE_VALUE_LOOKUP av) avl ON ta.ATTRIBUTE_VALUE = avl.ATTRIBUTE_VALUE
			WHERE 
				((ms.end_date IS NULL AND GETDATE() > ms.start_date) OR 
				(GETDATE() BETWEEN ms.start_date AND ms.end_date)) and 
				ms.inventory_item_id = '" . $p_inventory_item_id . "'
				$v_service AND
				se.active_flag = 'Y'
			ORDER BY
				ta.display_sequence, 
				ta.attribute_value
  		";

		$v_result = mssql_query ($v_sql);

		
		if (!$v_result) {
			display_mssql_error($v_sql);
			//$this->SendError($v_sql,"getAvailableServices");
		}

		$va_set = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['template_id'] 			= $v_row['template_id'];
				$va_col['display_sequence']		= $v_row['display_sequence'];
				$va_col['description'] 			= $v_row['description'];
				$va_col['retail_price'] 		= $v_row['retail_price'];
				$va_col['service_id'] 			= $v_row['service_id'];
				$va_col['user_action'] 			= $v_row['user_action'];
				$va_col['attribute_name'] 		= $v_row['attribute_name'];
				$va_col['display_label'] 		= $v_row['display_label'];
				$va_col['attribute_value'] 		= $v_row['attribute_value'];
				$va_col['service_level'] 		= $v_row['service_level'];
				$va_col['availability_date']	= $v_row['availability_date'];
				$va_col['display_label_colors']	= $v_row['display_label_colors'];
				$va_set[$c++] = $va_col;
			}
		}
        mssql_free_result($v_result);
        return $va_set;
	}
		
	/******************************
	 * Function: getAvailableServicesforSaving
	 * Purpose: finds and returns all available services for this club
	 * Called From::
	 * 	Model: CustomClub
	 *  Function: DisplayCustomize
	 * Comment: This table is using the new Custom Club table(s)
	 * Comment Date: 8/5/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function getAvailableServicesforSaving($p_inventory_item_id,$p_service) {
		global $mssql_db;
		
		if ($p_service != "") 	
			$v_service = " and se.description = '" . $p_service . "'";
		
		$v_sql = "
			SELECT
				distinct
				al.attribute_name,      
				ta.template_id,      
				ta.display_sequence,		 
				se.description,
				ms.retail_price,
				ms.service_id,      
				al.user_action,      
				al.display_label,  
				se.service_level,
				ms.availability_date
			FROM
				r12pricing.dbo.gsi_cus_model_services AS ms LEFT JOIN      
				r12pricing.dbo.gsi_per_template_attributes ta ON ms.template_id = ta.template_id LEFT JOIN 
				r12pricing.dbo.gsi_per_attribute_lookup al ON ta.attribute_name = al.attribute_name LEFT JOIN
				r12pricing.dbo.gsi_cus_services se ON se.service_id = ms.service_id 
			WHERE 
				((ms.end_date IS NULL AND GETDATE() > ms.start_date) OR 
				(GETDATE() BETWEEN ms.start_date AND ms.end_date)) and 
				ms.inventory_item_id = '" . $p_inventory_item_id . "'
				$v_service AND
				se.active_flag = 'Y'
			ORDER BY
				ta.display_sequence
  		";
            
		$v_result = mssql_query ($v_sql);
		
		
		
		if (!$v_result) {
			display_mssql_error($v_sql);
			//$this->SendError($v_sql,"getAvailableServices");
		}

		$va_set = array ();
		
		if (mssql_num_rows($v_result)>0) {
			$c = 0;
			while($v_row = mssql_fetch_assoc ($v_result)) {
				$va_col = array();
				$va_col['template_id'] 			= $v_row['template_id'];
				$va_col['display_sequence']		= $v_row['display_sequence'];
				$va_col['description'] 			= $v_row['description'];
				$va_col['retail_price'] 		= $v_row['retail_price'];
				$va_col['service_id'] 			= $v_row['service_id'];
				$va_col['user_action'] 			= $v_row['user_action'];
				$va_col['attribute_name'] 		= $v_row['attribute_name'];
				$va_col['display_label'] 		= $v_row['display_label'];
				$va_col['service_level'] 		= $v_row['service_level'];
				$va_col['availability_date']	= $v_row['availability_date'];
				$va_set[$c++] = $va_col;
			}
		}

        mssql_free_result($v_result);
        return $va_set;
	}
	
	/******************************
	 * Function: flex_reco
	 * Purpose: ...
	 * Called From::
	 * 	Model: ...
	 *  Function: ...
	 * Comment Date: 3/17/2010
	 * Comment Author: Robbie Smith
	 */
	
	public function flex_reco($swing_speed_string, $final_swing_speed, $flex_str) {
      if ($final_swing_speed < 11)
        return 'Select';
             
      $shaft_swing_arr = '';
      $shaft_swing_arr = split('~', $swing_speed_string);
      $flex_arr = split('~', $flex_str);
      $swing_len = count($shaft_swing_arr);
      $extract_swing_val = '';
      for ($idx = 0; $idx < $swing_len; $idx++) {
        $extract_swing_val = '';
        $extract_swing_val = trim(trim($shaft_swing_arr[$idx], '"'), '$');
        list($low_swing_speed, $high_swing_speed) = split('-', $extract_swing_val);
        $flex = $flex_arr[$idx];
        if ($final_swing_speed <= $high_swing_speed) {
          return $flex;
        }
      }
      return $flex;
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
    
    public function createTestFittingSession() {
    
        global $mssql_db;
        $p_session_id = session_id();
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
    
        if (!$v_result) {
            return false;
        }
       
        if (mssql_num_rows($v_result) == 0) { // we do not have a session!
            return $p_session_id;
        }
        mssql_free_result($v_result);        
    
        return $p_session_id;
    
    }
    
    /******************************
     * Function: StartFittingSessionCustClub
     * Purpose: Saves, and if need be starts a club fitting, session into SQL SERVER
     * Called From::
     * 	Controller: CustomclubController.php
     *  Function: clubselectionsaveAction
     * Comment Date: 3/11/2010
     * Comment Author: Robbie Smith
     */
    
    public function StartFittingSessionCustClub ( $v_sessionId ) {
    
        global $mssql_db;

        // test to see if current session is started
    
        $v_sql = "
			SELECT
				fitting_id
			FROM
				direct.dbo.GSI_CUST_CLUB_FITTING
			WHERE
				session_id = '" . $v_sessionId . "'
		";
        $v_result = mssql_query ( $v_sql );
   
        if (! $v_result) {
            display_mssql_error ( $v_sql, "StartFittingSessionCustClub query from Club model" );
            	
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
				'" . $v_sessionId . "',
				'P',
				''
			)
			";
          	
            mssql_query ( $v_sql ) or die('failed to insert'); // START THE FITTING
          	
            if (! $v_result) {
                display_mssql_error ( $v_sql, "StartFittingSessionCustClub query from Club model" );
                ;//$this->SendError($v_sql,"StartFittingSession");
                return false;
            }
        } else { // continue the custom fitting session
            $v_sql = "
			UPDATE
				direct.dbo.GSI_CUST_CLUB_FITTING
			SET
				smart_fit_flag = 'P',
				club_category = ''
			WHERE
				session_id = '" . $v_sessionId . "'
			";
            mssql_query ( $v_sql ); // UPDATE THE FITTING
        }
        mssql_free_result($v_result);
        return true;
    }

}
?>