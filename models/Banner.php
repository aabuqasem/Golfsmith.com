<?php

/****************************************************************************
*                                                                           *
*  Program Name :  StoreFeature.php                                          		*
*  Type         :  MVC Model Class                                          *
*  File Type    :  PHP                                                      *
*  Location     :  /include/Models                                          *
*  Created By   :  Hima Bindu Yellapragada                                  *
*  Created Date :  06/29/2012                                               *
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
class Banner {
	
	private $store_id;
	private $banner_id;
	private $banner_title;
	private $destination_url;
	private $image_ref;
	private $mobile_image_ref;
	
	//function to set store Id
	public function setStoreId($store_id)
	{
		$this->store_id = $store_id;
	}
 	// function to retrieve for store detail page
  	public function getStoreBanner()
  	{
		global $web_db;
    	$sql = "SELECT gb.banner_id, banner_title, destination_url, image_reference, mobile_image_reference
    			FROM webonly.gsi_banner gb, webonly.gsi_banner_schedule_store_details gbs
    			WHERE gb.banner_id = gbs.banner_id
    			AND store_id='".$this->store_id."' 
    			AND '".date('Y-m-d H:i')."' BETWEEN start_date AND end_date";
    	$result = mysqli_query($web_db,$sql);
    	$row = mysqli_fetch_assoc($result);
    	$this->banner_id = $row['banner_id'];
    	$this->banner_title = $row['banner_title'];
    	$this->destination_url = $row['destination_url'];
    	$this->image_ref = $row['image_reference'];
    	$this->mobile_image_ref = $row['mobile_image_reference'];
  	}

	// function to retrieve the Banner for events page
	public function getEventBanner()
	{
  		global $web_db;
  		
  		$sql = "SELECT * FROM webonly.gsi_banner_schedule g, webonly.gsi_banner gsi 
  				WHERE g.banner_id=gsi.banner_id
				AND '".date('Y-m-d H:i:s')."' BETWEEN start_date AND end_date 
				AND banner_type=2";
		$result = mysqli_query($web_db,$sql);
		$row = mysqli_fetch_assoc($result);
		$this->banner_id = $row['banner_id'];
		$this->banner_title = $row['banner_title'];
    	$this->destination_url = $row['destination_url'];
    	$this->image_ref = $row['image_reference'];
    	$this->mobile_image_ref = $row['mobile_image_reference'];		
	}

	// function to retrieve the Banner for store finder page
	public function getStoreFinderBanner()
	{
  		global $web_db;
  		
  		$sql = "SELECT * FROM webonly.gsi_banner_schedule g, webonly.gsi_banner gsi 
  				WHERE g.banner_id=gsi.banner_id
				AND '".date('Y-m-d H:i:s')."' BETWEEN start_date AND end_date 
				AND banner_type=1";
		$result = mysqli_query($web_db,$sql);
		$row = mysqli_fetch_assoc($result);
		$this->banner_id = $row['banner_id'];
		$this->banner_title = $row['banner_title'];
    	$this->destination_url = $row['destination_url'];
    	$this->image_ref = $row['image_reference'];
    	$this->mobile_image_ref = $row['mobile_image_reference'];		
	}
	
	// set property Banner Id 
	public function getBannerId()
	{
		return $this->banner_id;
	}
	
	// returns Banner title
	public function getBannerTitle()
	{
		return $this->banner_title;
	}

	// returns destination URL
	public function getDestinationUrl()
	{
		return $this->destination_url;
	}
		
	// returns Image reference
	public function getImageRef()
	{
		return $this->image_ref;
	}
	
	// returns Mobile Image reference
	public function getMobileImageRef()
	{
		return $this->mobile_image_ref;
	}	
 }
?>