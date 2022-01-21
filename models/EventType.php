<?php

/****************************************************************************
*                                                                           *
*  Program Name :  EventType.php                                          		*
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
class EventType {
	
	private $event_type_id;
	private $event_type;
	private $event_type_title;
	private $event_type_desc;
	private $show_filter;
	private $event_type_css;
	
  	//Contructor to set the id 
  	public function __construct($event_type_id) {
		$this->setEventTypeId($event_type_id);
		$this->_set_properties();
  	}

  	// set properties
  	private function _set_properties()
  	{
		global $web_db;
    	$sql = "SELECT event_id,event_type,event_title,event_desc,event_css,show_filter 
    			FROM webonly.gsi_event_type
    			WHERE event_id='".$this->getEventTypeId()."'";
    	$result = mysqli_query($web_db,$sql);
    	$row = mysqli_fetch_assoc($result);
    	$this->event_type_title = $row['event_title'];
    	$this->event_type_id = $row['event_id'];
    	$this->event_type = $row['event_type'];
    	$this->event_type_desc = $row['event_desc'];
    	$this->event_type_css = $row['event_css'];
    	$this->show_filter = $row['show_filter'];
  	}
	// set property Event Type Id 
	public function setEventTypeId($event_type_id)
	{
		$this->event_type_id = $event_type_id;
	}

	// set property Event Type Id 
	public function getEventTypeId()
	{
		return $this->event_type_id;
	}
	
	// returns Event Title
	public function getEventTypeTitle()
	{
		return $this->event_type_title;
	}

	// returns Event CSS
	public function getEventTypeCss()
	{
		return $this->event_type_css;
	}
		
	// returns Event Type
	public function getEventType()
	{
		return $this->event_type;
	}
	
	// returns description
	public function getEventTypeDescription()
	{
		return $this->event_type_desc;
	}
	
	// returns filter
	public function getShowFilter()
	{
		return $this->show_filter;
	}	
 }
?>