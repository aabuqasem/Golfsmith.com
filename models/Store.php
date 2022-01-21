<?php

/****************************************************************************
*                                                                           *
*  Program Name :  Store.php                                          		*
*  Type         :  MVC Model Class                                          *
*  File Type    :  PHP                                                      *
*  Location     :  /include/Models                                          *
*  Created By   :  Hima Bindu Yellapragada                                  *
*  Created Date :  06/12/2012                                               *
*               :  Copyright 2012  Golfsmith International                  *
*---------------------------------------------------------------------------*
*                                                                           * 
* History:                                                                  *
* --------                                                                  *
* Date       By                  		Comments                            * 
* ---------- ---------------     		--------------------                *
* 06/12/2012 Hima Bindu Yellapragada	Initial Version                     *
*                                                                           *
****************************************************************************/
include('StoreFeature.php');
include('Banner.php');
class Store {
	
	private $store_id;
	private $store_num;
	private $distance;
	private $store_name;
	private $store_address_1;
	private $store_address_2;
	private $store_address_3;
	private $store_city;
	private $store_state;
	private $store_zip;
	private $store_phone;
	private $store_gm;
	private $store_lat;
	private $store_long;
	private $store_image;
	private $feature_ids;
	private $announ_id;
	private $announcement;
	
  	//Contructor to set the id 
  	public function __construct($store_id,$distance) {
  		$arguments = func_get_args();
  		if(count($arguments) == 2)
  		{
    		$this->setStoreId($store_id);
    		$this->setDistance($distance);
  		}
  		elseif (count($arguments) == 1)
  		{
  			$this->setStoreNum($store_id);
	   		$this->setDistance(0);
  		}
		$this->_set_properties();  		
  	}

  	private function _set_properties()
  	{
  		global $web_db;  		
  		$sql = "SELECT case ifnull(LENGTH(sw.store_name),0) when 0 then se.display_store_name else sw.store_name end AS store_name, s.organization_code, s.address_line_1,s.address_line_2,s.address_line_3,s.town_or_city,st.state_name,s.postal_code,s.telephone_number_1, sw.store_manager_name,sw.longitude,sw.latitude,sw.store_image,sw.features, se.organization_id 
    			FROM gsi_store_info s, gsi_store_info_ext se,webonly.gsi_store_web_info sw, gsi_states st
    			WHERE s.organization_id = se.organization_id
    			AND s.state = st.state_abb 
    			AND sw.show_flag = 'Y'
    			AND s.organization_id = sw.store_id";
  		$sql .= (isset($this->store_num))?" AND s.organization_code='$this->store_num'":" AND s.organization_id = '$this->store_id'";
    	$result = mysqli_query($web_db,$sql);
    	$row = mysqli_fetch_assoc($result);
    	$this->store_num = $row['organization_code'];
    	$this->store_id = $row['organization_id'];
    	$this->store_name = $row['store_name'];
    	$this->store_address_1 = $row['address_line_1'];
    	$this->store_address_2 = $row['address_line_2'];
    	$this->store_address_3 = $row['address_line_3'];
    	$this->store_city = $row['town_or_city'];
    	$this->store_state = $row['state_name'];
    	$this->store_zip = $row['postal_code'];
    	$this->store_phone = $row['telephone_number_1'];
    	$this->store_gm = $row['store_manager_name'];
    	$this->store_lat = $row['latitude'];
    	$this->store_long = $row['longitude'];
    	$this->store_image = $row['store_image'];
    	$this->feature_ids = $row['features'];
  	}

  	// set property Store Id 
	public function setStoreId($store_id)
	{
		$this->store_id = $store_id;
	}

  	// set property Distance 
	public function setDistance($distance)
	{
		$this->distance = $distance;
	}

  	// set property Store Num (organization code) 
	public function setStoreNum($store_num)
	{
		$this->store_num = $store_num;
	}

  	// set property Distance 
	public function getStoreNum()
	{
		return $this->store_num;
	}
	
  	// get property Distance 
	public function getDistance($distance)
	{
		return $this->distance;
	}
	
	// set property Store Id 
	public function getStoreId()
	{
		return $this->store_id;
	}
		
	// returns Store Name
	public function getStoreName()
	{
		return $this->store_name;
	}
	
	// returns Store Address
	public function getStoreAddress1()
	{
		return $this->store_address_1;
	}
	
	// returns Store Address
	public function getStoreAddress2()
	{
		return $this->store_address_2;
	}

	// returns Store Address
	public function getStoreAddress3()
	{
		return $this->store_address_3;
	}
		
	// returns Store City
	public function getStoreCity()
	{
		return $this->store_city;
	}
	
	// returns Store State
	public function getStoreState()
	{
		return ucfirst(strtolower($this->store_state));
	}

	// returns Store Zip code
	public function getStoreZip()
	{
		return $this->store_zip;
	}

	// returns Store Phone
	public function getStorePhone()
	{
		return $this->store_phone;
	}
	
	// returns Store General Manager Name
	public function getStoreGM()
	{
		return $this->store_gm;
	}

	// returns Store Latitude
	public function getStoreLat()
	{
		return $this->store_lat;
	}

	// returns Store longitude
	public function getStoreLong()
	{
		return $this->store_long;
	}

	// returns Store Hours 
	public function getStoreHours()
	{
		global $web_db;
		
		$store_hours = array();
		$day = date('Y-m-d');
		for($i=1;$i<=7;$i++)
		{
			$sql = "SELECT * FROM webonly.gsi_store_business_hours WHERE daystart='".$day."' AND store_id='".$this->store_id."'";
			$result = mysqli_query($web_db,$sql);
			$row = mysqli_fetch_assoc($result);
			
			
			if(mysqli_num_rows($result)>0)
			{
				$store_hours[date('D',strtotime($day))]['start'] = date('g:i a',strtotime($row['start_hour']));
				$store_hours[date('D',strtotime($day))]['end'] = date('g:i a',strtotime($row['end_hour']));
				$store_hours[date('D',strtotime($day))]['closed'] = ($row['closed'])?$row['closed']:'N'; // get the closed tag				
				
			 	
			}
			else
			{
				$sql = 'SELECT * FROM webonly.gsi_default_weekly_hours WHERE store_id="'.$this->store_id.'"';
				$result1 = mysqli_query($web_db,$sql);
				$row1 = mysqli_fetch_assoc($result1);
				$arrtimeend = array("Sun"=>0,"Mon"=>1,"Tue"=>2,"Wed"=>3,"Thu"=>4,"Fri"=>5,"Sat"=>6);	
				$varclosed = 'closed_'.$arrtimeend[date('D',strtotime($day))];
				//echo $varclosed."<BR />";	
				if($row1[$varclosed]=="Y"){
				$store_hours[date('D',strtotime($day))]['closed'] = 'Y'; // get the closed tag	
				}else{
				$store_hours[date('D',strtotime($day))]['start'] = date('g:i a',strtotime($row1[strtolower(date('D',strtotime($day))).'_start']));
				$store_hours[date('D',strtotime($day))]['end'] = date('g:i a',strtotime($row1[strtolower(date('D',strtotime($day))).'_end']));
				$store_hours[date('D',strtotime($day))]['closed'] = 'N'; // get the closed tag	
				}
			}
							
			$store_hours[date('D',strtotime($day))]['date'] = date('m/d/Y',strtotime($day));
			
			$day = date('Y-m-d H:i:s',mktime(0,0,0,date('m'),date('d')+$i,date('Y')));
		
			
		}
		return $store_hours;
	}
	
	// returns Store Image
	public function getStoreImage()
	{
		return $this->store_image;
	}
	
	// returns Store features
	public function getFeatureIds()
	{
		$feature_ids = array();
		$feature_arr = explode  (',' , $this->feature_ids);
		foreach($feature_arr as $feature_id)
		{
			array_push($feature_ids,new StoreFeature($feature_id));
		}
		return $feature_ids;
	}

	// returns Store Banner
	public function getStoreBanner()
	{
		$ObjBanner = new Banner();
		$ObjBanner->setStoreId($this->store_id);
		return $ObjBanner;
	}
	
	// sets Store Announcement
	public function getStoreAccouncement()
	{
		global $web_db;  		
  		$sql = "SELECT announcement FROM webonly.gsi_store_announcement
    			WHERE organization_id = '$this->store_id' AND activated=1
    			AND '".date('Y-m-d H:i:s')."' BETWEEN start_date AND end_date";
  		$result = mysqli_query($web_db,$sql);
  		$announcement = mysqli_fetch_array($result);

  		$this->announcement = $announcement['announcement'];
	}
	
	// returns store announcement
	public function getAnnouncement()
	{
		return $this->announcement;
	}	
	
    // get store seo url 
	public function getStoreSEOUrl()
	{
		$v_seo_url = $this->store_name;
		
		$v_seo_url = strtolower(str_replace(' - ','-',$v_seo_url));
    	$v_seo_url = strtolower(str_replace(' ','-',$v_seo_url));
    	$v_seo_url = strtolower(str_replace('/','-',$v_seo_url));    			
    	$v_seo_url = preg_replace("![^a-z0-9]+!i", "-", $v_seo_url);  			
    			
    	$v_spec_chars = array(".", ",", "'", "*", "&", "%", "$", "#", "@", "!", "(", ")", "[", "]", "{", "}", "?", "<", ">","¿");
		$v_seo_url    = str_replace($v_spec_chars, "", $v_seo_url);

		$v_spec_chars = array("_", ":", ";");
		$v_seo_url    = str_replace($v_spec_chars, "-", $v_seo_url);
    			
    	$v_seo_url = '/stores/' . $this->store_num . '/' . $v_seo_url;
		return $v_seo_url;
	}
 }
?>