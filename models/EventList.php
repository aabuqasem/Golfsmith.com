<?php

/****************************************************************************
*                                                                           *
*  Program Name :  EventList.php                                          *
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
require_once('Event.php');

class EventList {
	
	private $event_type_ids;
	private $store_id;

	// set property Event Type Ids based on which the events are filtered and displayed
	public function setFilterBy($filter_by)
	{
		$this->event_type_ids = $filter_by;
	}
	
	// set property store id where the events are pulled for this store
	public function setStoreId($store_id)
	{
		$this->store_id = $store_id;
	}
	
	public function getEvents()
	{
		global $web_db;
		
		$sql = "SELECT event_id FROM webonly.gsi_events 
				WHERE store_id='$this->store_id' AND is_approved='Y' AND IFNULL(end_date,NOW()+1) >= NOW()";
		if(is_array($this->event_type_ids) && count($this->event_type_ids)>0)
		{
			$sql .= " AND event_type_id NOT IN (".join(',',$this->event_type_ids).")";
		}
		$sql .= " ORDER BY start_date";
		$result = mysqli_query($web_db,$sql);
		$events = array();
		while($row = mysqli_fetch_assoc($result)){
			array_push($events, new Event($row['event_id']));
		}
		return $events;
	}
 }
?>