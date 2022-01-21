<?php

/****************************************************************************
*                                                                           *
*  Program Name :  Event.php                                          		*
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
class Event {
	
	private $event_id;
	private $store_id;
	private $event_title;
	private $event_desc;
	private $event_type;
	private $event_type_id;
	private $event_css;
	private $image_path;
	private $event_location;
	private $event_address;
	private $event_contact;
	private $event_contact_phone;
	private $sponsor_name;
	private $sponsor_css;
	private $start_date;
	private $end_date;
	
  	//Contructor to set the id 
  	public function __construct($event_id) {
		$this->setEventId($event_id);
		$this->_set_properties();
  	}

  	// set properties
  	private function _set_properties()
  	{
		global $web_db;
    	$sql = "SELECT g.store_id, gt.event_id, event_type, custom_event_title, custom_event_desc, event_location, CONCAT_WS(',',event_address, event_city, event_state, event_zip) as address, event_contact, event_phone, start_date, end_date, g.custom_event_css,gt.event_css, g.sponsor_id
    			FROM webonly.gsi_events g, webonly.gsi_event_type gt
    			WHERE g.event_type_id=gt.event_id 
    			AND g.event_id = '$this->event_id'";
    	$result = mysqli_query($web_db,$sql);
    	$row = mysqli_fetch_assoc($result);
    	$this->store_id = $row['store_id'];
    	$this->event_title = $row['custom_event_title'];
    	$this->event_type_id = $row['event_id'];
    	$this->event_type = $row['event_type'];
    	$this->event_css = $row['event_css'];
    	$this->image_path = $row['custom_event_css'];
    	$this->event_desc = $row['custom_event_desc'];
    	$this->event_location = $row['event_location'];
    	$this->event_address = $row['address'];
    	$this->event_contact = $row['event_contact'];
    	$this->event_contact_phone = $row['event_phone'];
    	$this->start_date = $row['start_date'];
    	$this->end_date = $row['end_date'];
    	if($row['sponsor_id'] != '')
    	{
    		$sql = "SELECT group_concat(a.sponsor_name) as name
    					  ,group_concat(a.sponsor_css) as css from
    					  ( SELECT sponsor_name , sponsor_css 
    					    FROM webonly.gsi_event_sponsor 
    					    WHERE sponsor_id IN (".$row['sponsor_id'].") 
    					    ORDER BY sponsor_name) a";
    		$result = mysqli_query($web_db,$sql);
    		$row = mysqli_fetch_assoc($result);
    		$this->sponsor_name = $row['name'];
    		$this->sponsor_css = $row['css'];
    	}
    	else
    	{
    		$this->sponsor_name = '';
    		$this->sponsor_css = '';
    	}
  	}
	// set property Event Id 
	public function setEventId($event_id)
	{
		$this->event_id = $event_id;
	}

	// get property Event Id 
	public function getEventId()
	{
		return $this->event_id;
	}
	
	// get property Store Id 
	public function getStoreId()
	{
		return $this->store_id;
	}
	
	// set property Event Type Id 
	public function getEventTypeId()
	{
		return $this->event_type_id;
	}

	// set property Event Type 
	public function getEventType()
	{
		return $this->event_type;
	}
	
	// returns Event Title
	public function getEventTitle()
	{
		return $this->event_title;
	}
	
	// returns Event css
	public function getEventCss()
	{
		return $this->event_css;
	}

	// returns Image Path
	public function getImagePath()
	{
		return $this->image_path;
	}
	
	// returns description
	public function getEventDescription()
	{
		return $this->event_desc;
	}

	// returns location
	public function getEventLocation()
	{
		return $this->event_location;
	}

	// returns address
	public function getEventAddress()
	{
		if($this->event_address==",,,"){
			$this->event_address = "";
		}
		return $this->event_address;
	}

	// returns contact person name
	public function getEventContact()
	{
		return $this->event_contact;
	}

	// returns phone
	public function getEventPhone()
	{
		return $this->event_contact_phone;
	}

	// returns Sponsoror name
	public function getSponsorName()
	{
		return $this->sponsor_name;
	}
	
	// returns Sponsor css
	public function getSponsorCss()
	{
		return $this->sponsor_css;
	}
	
	// returns start date
	public function getStartDay()
	{
		return date('l',strtotime($this->start_date));
	}
		
	// returns start date
	public function getStartDate()
	{
		return date('F d, Y',strtotime($this->start_date));
	}

	// returns start time
	public function getStartTime()
	{
		return date('g:ia',strtotime($this->start_date));
	}
	
	// returns end date
	public function getEndDay()
	{
		return date('l',strtotime($this->end_date));
	}
	
	// returns end date
	public function getEndDate()
	{
		return date('F d, Y',strtotime($this->end_date));
	}
	
	// returns end time
	public function getEndTime()
	{
		return date('g:ia',strtotime($this->end_date));
	}	
 }
?>