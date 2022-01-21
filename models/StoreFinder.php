<?php

/****************************************************************************
*                                                                           *
*  Program Name :  StoreFinder.php                                          *
*  Type         :  MVC Model Class                                          *
*  File Type    :  PHP                                                      *
*  Location     :  /include/Models                                          *
*  Created By   :  Hima Bindu Yellapragada                                  *
*  Created Date :  06/11/2012                                               *
*               :  Copyright 2012  Golfsmith International                  *
*---------------------------------------------------------------------------*
*                                                                           * 
* History:                                                                  *
* --------                                                                  *
* Date       By                  		Comments                            * 
* ---------- ---------------     		--------------------                *
* 06/11/2012 Hima Bindu Yellapragada	Initial Version                     *
*                                                                           *
****************************************************************************/
require_once('Store.php');

class StoreFinder {
	
	private $zip;
	private $city;
	private $state;
	private $result_limit;
	private $radius;
	
	// set property zip
	public function setZip($zip)
	{
		$this->zip = $zip;
	}
	
	// set property city
	public function setCity($city)
	{
		$this->city = $city;
	}

	// set property State
	public function setState($state)
	{
		$this->state = $state;
	}

	// set property Result Limit (number of stores to be retrieved)
	public function setResultLimit($result_limit)
	{
		$this->result_limit = $result_limit;
	}

	// set property Radius (the distance within which they want the stores to be showed)
	public function setRadius($radius)
	{
		$this->radius = $radius;
	}
	
	// function to get the stores by state search
	public function getStoresByState()
	{
		global $web_db;
		
		$sql = "SELECT DISTINCT(gs.organization_id)
				FROM gsi_store_info gs,
 					 gsi_store_info_ext ext,
				     webonly.gsi_store_web_info gw
				WHERE state='$this->state'
				AND gs.organization_id=ext.organization_id
				AND gs.organization_id=gw.store_id
				AND gw.show_flag='Y'
				ORDER BY CASE IFNULL(LENGTH(gw.store_name),0) WHEN 0 THEN ext.display_store_name ELSE gw.store_name END";
		$result = mysqli_query($web_db, $sql);
		
		$stores=array();
		// with the result set, generate the store objects array
		while ( $row = mysqli_fetch_assoc ( $result ) ) {
    			array_push($stores, new Store($row['organization_id'],0));
  		}
  		return $stores;		
	}
	
	// function to get the stores by state and city or zip
  	public function getStores()
  	{
  		global $web_db;

		$stores = array();
  		if($this->zip != '') // if zip is entered always go by zip code
  		{
			$result = $this->_get_organization_result_set();
  		} else if($this->city != '' && $this->state != ''){ // if state and city are mentioned without zip then get the postal codes based on that
			$this->zip = $this->_get_postal_codes($this->state,$this->city);
			$result = $this->_get_organization_result_set();
  		}
  		else { // when nothing is mentioned retrieve all the stores
  			$sql = "SELECT DISTINCT(gs.organization_id) 
  					FROM gsi_store_info gs,
						 gsi_store_info_ext ext,
						 webonly.gsi_store_web_info gw
  					WHERE  gs.organization_id = gw.store_id
            		AND gs.organization_id = ext.organization_id
            		AND gw.show_flag='Y' 
            		ORDER BY gs.state, 
            			CASE IFNULL(LENGTH(gw.store_name),0) 
            				WHEN 0 then ext.display_store_name 
            				ELSE gw.store_name 
            			END";
  			$result = mysqli_query($web_db,$sql);
  		}
  		
  		$store_id=array();
		// with the result set, generate the store objects array
		while ( $row = mysqli_fetch_assoc ( $result ) ) {
			if(!in_array($row['organization_id'], $store_id)) // to make sure different stores are given 
    			array_push($stores, new Store($row['organization_id'],$row['distance']));
    		array_push($store_id,$row['organization_id']);
  		}
  		return $stores;
  	}
  	// get all stores without nay conditions
  	// UAT 26 ticket to get all the sotres in the store finder page
  	public function getAllStores()
  	{
  		global $web_db;

		$stores = array();
		$sql = "SELECT * FROM gsi_store_info gs, webonly.gsi_store_web_info gw 
  				WHERE  gs.organization_id = gw.store_id AND gw.show_flag='Y'";
		$result = mysqli_query($web_db,$sql);
  		
  		$store_id=array();
		// with the result set, generate the store objects array
		while ( $row = mysqli_fetch_assoc ( $result ) ) {
			if(!in_array($row['organization_id'], $store_id)) // to make sure different stores are given 
    			array_push($stores, new Store($row['organization_id'],$row['distance']));
    		array_push($store_id,$row['organization_id']);
  		}
  		return $stores;
  	}  	

  	// function to retrieve the postal codes based on state and city (if given, as city is optional)
  	private function _get_postal_codes($state,$city=null)
  	{
  		global $web_db;
  		$sql = "SELECT postal_code FROM gsi_zipcodes WHERE state='$this->state' AND city='$this->city' LIMIT 1";
  		 
		$result = mysqli_query($web_db,$sql);
		$row = mysqli_fetch_array($result);
		return $row['postal_code'];
  	}
  	
  	private function _get_organization_result_set()
  	{
  		global $web_db;
		$sql = '
      		SELECT
        		distinct(gsi_store_info.organization_id),
        		IFNULL(60 * 1.515 * degrees(acos(
               		( sin( radians( origin.latitude)) * sin( radians(store.latitude)) )
	               	+
    	           	( cos( radians(origin.latitude)) * cos( radians(store.latitude)) )
        	       	* cos( radians(origin.longitude - store.longitude) )
                	)),0) as distance
      		FROM
        		gsi_zipcodes origin
      			, gsi_zipcodes store
      			, gsi_store_info      			
      			, webonly.gsi_store_web_info gsi
      		WHERE
        		substring(gsi_store_info.postal_code, 1, 5) = store.postal_code      		
        	AND 
        		gsi_store_info.organization_id = gsi.store_id
        	AND 
        		gsi.show_flag="Y"
      		AND ';
      		$sql .= (!is_array($this->zip))?"substring(origin.postal_code, 1, 5) = $this->zip":"substring(origin.postal_code, 1, 5) IN (".join(',',$this->zip).")";
      		$sql .= ($this->radius != '')?" AND IFNULL((60 * 1.515 * degrees(acos(
               		( sin( radians( origin.latitude)) * sin( radians(store.latitude)) )
	               	+
    	           	( cos( radians(origin.latitude)) * cos( radians(store.latitude)) )
        	       	* cos( radians(origin.longitude - store.longitude) )
                	))),0) <= '".$this->radius."'":"";
      		$sql .=' ORDER BY distance';
      		$sql .= ($this->result_limit != "")?" LIMIT ".$this->result_limit:"";
    		$result = mysqli_query ($web_db, $sql);
			return $result;
  	}
  	
  	public function getClosesStoresByZipCode($zipcode){
  	    
  	    global $web_db;

  	    $zipcode = mysqli_real_escape_string($web_db, $zipcode);

  	    $sql = "SELECT DISTINCT 
                gsi_store_info.organization_id
                ,    gsi_store_info.organization_code
                ,    case ifnull(LENGTH(info.store_name),0) when 0 then gsie.display_store_name else info.store_name end as location_code
                ,    gsi_store_info.address_line_2
                ,    gsi_store_info.address_line_3
                ,    gsi_store_info.town_or_city
                ,    gsi_store_info.state
                ,    gsi_store_info.postal_code
                ,    ifnull(round(60 * 1.515 * degrees(acos(
                		( sin( radians( origin.latitude)) * sin( radians(store.latitude)) )
                		+
                		( cos( radians(origin.latitude)) * cos( radians(store.latitude)) )
                		* cos( radians(origin.longitude - store.longitude) )
                		)),2),0) as distance
        		,	hours.sun_start
				,	hours.sun_end
				,	hours.mon_start
				,	hours.mon_end
				,	hours.tue_start
				,	hours.tue_end
				,	hours.wed_start
				,	hours.wed_end
				,	hours.thu_start
				,	hours.thu_end
				,	hours.fri_start
				,	hours.fri_end
				,	hours.sat_start
				,	hours.sat_end
                FROM  gsi_zipcodes origin
                ,     gsi_zipcodes store
                ,     gsi_store_info
                ,     gsi_web_store_groups
                ,     gsi_store_info_ext gsie
                LEFT JOIN webonly.gsi_store_web_info info
                ON info.store_id = gsie.organization_id
                LEFT JOIN webonly.gsi_default_weekly_hours hours
                ON hours.store_id = gsie.organization_id
                WHERE gsie.store_pickup_flag = 'Y'
                AND   gsie.organization_id = gsi_store_info.organization_id
                AND   substring(gsi_store_info.postal_code, 1, 5) = store.postal_code
                AND   gsi_web_store_groups.record_name = gsi_store_info.organization_code
                AND   substring(origin.postal_code, 1, 5) = '{$zipcode}'
                AND   (60 * 1.515 * degrees(acos(
                ( sin( radians( origin.latitude)) * sin( radians(store.latitude)) )
                +
                ( cos( radians(origin.latitude)) * cos( radians(store.latitude)) )
                * cos( radians(origin.longitude - store.longitude) )
                )) < 101
                OR
                60 * 1.515 * degrees(acos(
                ( sin( radians( origin.latitude)) * sin( radians(store.latitude)) )
                +
                ( cos( radians(origin.latitude)) * cos( radians(store.latitude)) )
                * cos( radians(origin.longitude - store.longitude) )
                )) is null
                )
                ORDER BY distance";
  	    	
  	    $result = mysqli_query($web_db, $sql);
  	    

  	    return $result;
  	    
  	}
  	
  	
  	
  	public function getClosesStoresByOrgId($org_id){
  	     
  	    global $web_db;
  	    
  	    $org_id = mysqli_real_escape_string($web_db, $org_id);
  	    $sql = "SELECT
                *,
                case ifnull(LENGTH(info.store_name),0) when 0 then gsie.display_store_name else info.store_name end as location_code
                FROM gsi_store_info gsi,
                gsi_store_info_ext gsie
                LEFT JOIN webonly.gsi_default_weekly_hours hours
                ON hours.store_id = organization_id
                LEFT JOIN webonly.gsi_store_web_info info
                ON info.store_id = gsie.organization_id
                WHERE gsi.organization_id = {$org_id} AND info.store_id = {$org_id}";
  	
  	    $result = mysqli_query($web_db, $sql);
  	
  	    return $result;
  	     
  	}
 }
?>