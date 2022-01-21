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
class StoreFeature {
	
	private $feature_id;
	private $feature_name;
	private $div_html;
	private $activated;
	
  	//Contructor to set the id 
  	public function __construct($feature_id) {
		$this->setFeatureId($feature_id);
		$this->_set_properties();
  	}

  	// set properties
  	private function _set_properties()
  	{
		global $web_db;
    	$sql = "SELECT feature_id, feature, div_html, activated 
    			FROM webonly.gsi_store_features
    			WHERE feature_id='".$this->getFeatureId()."'";
    	$result = mysqli_query($web_db,$sql);
    	$row = mysqli_fetch_assoc($result);
    	$this->feature_name = $row['feature'];
    	$this->feature_id = $row['feature_id'];
    	$this->div_html = $row['div_html'];
    	$this->activated = $row['activated'];
  	}
	// set property Feature Id 
	public function setFeatureId($feature_id)
	{
		$this->feature_id = $feature_id;
	}

	// set property Feature Id 
	public function getFeatureId()
	{
		return $this->feature_id;
	}
	
	// returns Feature Name
	public function getFeatureName()
	{
		return $this->feature_name;
	}

	// returns Div Html
	public function getDivHtml()
	{
		return $this->div_html;
	}
		
	// returns Activated
	public function getActivated()
	{
		return $this->activated;
	}
 }
?>