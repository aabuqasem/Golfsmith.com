<?php

/****************************************************************************
*                                                                           *
*  Program Name :  States.php                                          		*
*  Type         :  MVC Model Class                                          *
*  File Type    :  PHP                                                      *
*  Location     :  /include/Models                                          *
*  Created By   :  Hima Bindu Yellapragada                                  *
*  Created Date :  08/08/2012                                               *
*               :  Copyright 2012  Golfsmith International                  *
*---------------------------------------------------------------------------*
*                                                                           * 
* History:                                                                  *
* --------                                                                  *
* Date       By                  		Comments                            * 
* ---------- ---------------     		--------------------                *
* 08/08/2012 Hima Bindu Yellapragada	Initial Version                     *
*                                                                           *
****************************************************************************/
class States {

	private $state_abb;
	private $state_name;
	private $is_show;
	
  	// Get All States
  	public function getAllStates()
  	{
		global $web_db;
    	$sql = "SELECT * FROM webdata.gsi_states order by state_abb";
    	$result = mysqli_query($web_db,$sql);
    	$states = array();
    	while($row = mysqli_fetch_assoc($result))
    	{
    		$state = new States();
    		$state->state_abb = $row['state_abb'];
    		$state->state_name = $row['state_name'];
    		$state->is_show = $row['is_show'];
    		array_push($states,$state);
    	}
    	return $states;    	
  	}
  	
  	// abb
  	public function getStateAbb()
  	{
  		return $this->state_abb;
  	}
  	
  	// name
	public function getStateName()
  	{
  		return $this->state_name;
  	}
  	
  	// is show
	public function getIsShow()
  	{
  		return $this->is_show;
  	}
 }
?>