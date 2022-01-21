function update_states_list(states_list_arr) {
	$('#state option:gt(0)').remove();
	if (states_list_arr) {       
		$.each(states_list_arr,function(index, value) { 
			$('#state').append($("<option></option>")
    			.attr("value", value.state_abb).text(value.state_name.toUpperCase())
    			); 
		});    
	}
}

function getstateslist(p_screen_name) {

	  if(p_screen_name != '') {
	    v_url = '/' + p_screen_name + '/Tradein/getstateslist/';
	  } else {
	    v_url = '/Tradein/getstateslist/';
	  }

	  $.ajax( {
	    type : 'post',
	    url : v_url,
	    cache : false,
	    async : true,
	    beforesend : function() {

	    },
	    success : function(v_html) {
	    update_states_list(v_html);
	    }
	  });
	}

function update_stores_list(stores_list_arr) {
	$("#resort-message").hide();
	$('#store option:gt(0)').remove();
    if($('#state').val() == "NoSelection") {
        $('#store').attr('disabled','disabled');
    }
	if (stores_list_arr) {
       $('#store').removeAttr('disabled');        
       $.each(stores_list_arr,function(index, value) {
    	$('#store').append($("<option></option>")
    			.attr("value", value.organization_id).text(value.location_code)
    	); 
    }); 
	}
}

function getstoreslist(p_screen_name, p_state) {
	  if(p_screen_name != '') {
	    v_url = '/' + p_screen_name + '/Tradein/getstoreslist/';
	  } else {
	    v_url = '/Tradein/getstoreslist/';
	  }

	  $.ajax( {
	    type : 'post',
	    url : v_url,
	    data : 'state=' + p_state,
	    cache : false,
	    async : true,
	    beforesend : function() {

	    },
	    success : function(v_html) {
	    update_stores_list(v_html);
	    }
	  });

	}

function update_brands_list(brands_list_arr) {
	$('#brandname option:gt(0)').remove();
	if (brands_list_arr) {       
		$.each(brands_list_arr,function(index, value) { 
			$('#brandname').append($("<option></option>")
    			.attr("value", value.manufacturer).text(value.manufacturer)
    			); 
		});    
	}
}

function getbrandslist(p_screen_name) {

	  if(p_screen_name != '') {
	    v_url = '/' + p_screen_name + '/Tradein/getbrandslist/';
	  } else {
	    v_url = '/Tradein/getbrandslist/';
	  }

	  $.ajax( {
	    type : 'post',
	    url : v_url,
	    cache : false,
	    async : true,
	    beforesend : function() {

	    },
	    success : function(v_html) {
	    update_brands_list(v_html);
	    }
	  });
	}

function update_clubtype_list(clubtype_list_arr) {
	$('#clubtype option:gt(0)').remove();
    if($('#brandname').val() == "NoSelection") {
        $('#clubtype').attr('disabled','disabled');       
    }
    $('#clubmodel option:gt(0)').remove();
    $('#clubmodel').attr('disabled','disabled');
	if (clubtype_list_arr) {			    	
        $('#clubtype').removeAttr('disabled');        
        $.each(clubtype_list_arr,function(index, value) { 
        	$('#clubtype').append($("<option></option>")
    		.attr("value", value.club_type).text(value.club_type)
        ); 
    });
	}
}


function getclubtypelist(p_screen_name, p_brandname) {

	  if(p_screen_name != '') {
	    v_url = '/' + p_screen_name + '/Tradein/getclubtypelist/';
	  } else {
	    v_url = '/Tradein/getclubtypelist/';
	  }

	  $.ajax( {
	    type : 'post',
	    url : v_url,
	    data : 'brandname=' + p_brandname,	   
	    cache : false,
	    async : true,
	    beforesend : function() {

	    },
	    success : function(v_html) {
	    update_clubtype_list(v_html);
	    }
	  });
	}

function update_clubmodel_list(clubmodel_list_arr) {
	$('#clubmodel option:gt(0)').remove();
    if($('#clubtype').val() == "NoSelection") {
        $('#clubmodel').attr('disabled','disabled');
    }
	if (clubmodel_list_arr) {				    	
        $('#clubmodel').removeAttr('disabled');        
        $.each(clubmodel_list_arr,function(index, value) { 
        	$('#clubmodel').append($("<option></option>")
    		.attr("value", value.style).text(value.model)
    	); 
    });
	}
}


function getclubmodellist(p_screen_name, p_clubtype,p_brandname) {

	  if(p_screen_name != '') {
	    v_url = '/' + p_screen_name + '/Tradein/getclubmodellist/';
	  } else {
	    v_url = '/Tradein/getclubmodellist/';
	  }

	  $.ajax( {
	    type : 'post',
	    url : v_url,
	    data : 'clubtype=' + p_clubtype + '&brandname=' + p_brandname,
	    cache : false,
	    async : true,
	    beforesend : function() {

	    },
	    success : function(v_html) {
	    update_clubmodel_list(v_html);
	    }
	  });
	}

function clear_club_value() {
			$('#good_price_input').val('');
			$('#excellent_price_input').val('');
			$('#like_new_price_input').val('');
	}

function update_club_value(clubvalue_list_arr) {
	if (clubvalue_list_arr) {
		$.each(clubvalue_list_arr,function(index, value) { 
			$('#good_price_input').val('$'+value.good_buyback_price);
			$('#excellent_price_input').val('$'+value.excellent_buyback_price);
			$('#like_new_price_input').val('$'+value.like_new_buyback_price);
		});
		}
	}

function modelTradeinClose(){
	  $("#modal_Tradein").dialog("close");
	  location.reload(true);  
}
	
function getestimatevalue(p_screen_name, p_organization_id,p_style,p_platform) {
	if (validate_selection()) {
	  if(p_screen_name != '') {
	    v_url = '/' + p_screen_name + '/Tradein/getestimatevalue/';
	  } else {
	    v_url = '/Tradein/getestimatevalue/';
	  }

	  $.ajax( {
	    type : 'post',
	    url : v_url,
	    data : 'organization_id=' + p_organization_id + '&style=' + p_style + '&platform=' + p_platform,
	    cache : false,
	    async : true,
	    beforesend : function() {

	    },
	    success : function(v_html) {	    	
            if (v_html.length > 0) {
            	if (p_platform == 'Mobile') {
				$.each(v_html,function(index, value) { 
				var v_good_buyback_price = value.good_buyback_price;				
				var v_excellent_buyback_price = value.excellent_buyback_price;
				var v_like_new_buyback_price = value.like_new_buyback_price;
				var v_result_html=	'<div class="mobile-only" style="width:100%;"> \
				<div style="width:90%; text-align:center; margin:0 auto;"> \
				  <div id="like_new_buyback_price" style="margin: 15px auto 0; text-align:center; padding:5px 0px 0px 0px;" > \
					<label id="like_new_price_label">Excellent buyback price </label> \
					<input id="like_new_price_input" style="height:25px; font-weight: bold; font-size:1.5em; height:2em;" name="good_price_input" type="text"  value="$'+v_like_new_buyback_price+'" disabled="disabled"/> \
				  </div><br>'; 
				  v_result_html+= '<div id="excellent_buyback_price" style="margin: 15px auto 0; text-align:center; padding:5px 0px 0px 0px;" > \
					<label id="excellent_price_label">Very Good buyback price </label> \
					<input id="excellent_price_input" style="height:25px; font-weight: bold; font-size:1.5em; height:2em;" name="good_price_input" type="text" value="$'+v_excellent_buyback_price+'" disabled="disabled"/> \
				  </div><br/> \
				  <div id="good_buyback_price" style="margin: 15px auto; text-align:center; padding:5px 0px 0px 0px;" > \
					<label id="good_price_label">Good buyback price </label> \
					<input id="good_price_input" style="height:25px; font-weight: bold; font-size:1.5em; height:2em;" name="good_price_input" type="text" value="$'+v_good_buyback_price+'" disabled="disabled"/> \
				  </div> \
				</div> \
				<div id="buyback-descriptions" style="width:90%; margin:15px auto 15px auto; text-align:center;"> \
					<div style="text-align:left;"> \
						<h6 style="font-weight:bold; font-size:1.2em; padding-top:15px;">BUYBACK DESCRIPTIONS:</h6> \
						<p><span style="font-weight:bold;">EXCELLENT:</span> These clubs have been gently played but are extremely clean and near new with only slight signs of wear. Should have few, if any, nicks, marks, or scratches on head or shaft and any wear will be faint and mild. Will have no "pop-up" marks or visible damage to paint or graphics. Finish on club should still have original luster with virtually no wear except for normal ball impact and minimal turf wear. All grooves will be in superior condition. Shaft must be factory installed original with little to no visible wear and logo intact. Grips should be in playable and like new condition, showing no wear.</p> \
						<p>&nbsp;</p> \
						<p><span style="font-weight:bold; padding-top:15px;">VERY GOOD:</span> Clubs in this category have clearly been played but are very clean with visible wear and moderate signs of use. Will have some nicks or scratches consistent with average use, but should be limited in number and minor in appearance. Markings and scoring lines should be adequately sharp with only reasonable signs of use. Face and sole will show normal wear with no dents, gouges, or rock marks. Crown, finish, and shaft should show no pitting or excessive wear. Shaft must be factory original or high-quality, properly installed replacement. Grips should still be playable and free of excessive wear and tears.</p> \
						<p>&nbsp;</p> \
						<p><span style="font-weight:bold;">GOOD:</span> These clubs have been well played but remain clean, well cared for, and structurally sound. Clubs will clearly show wear and visual blemishes such as nicks, scratches, or possible "pop-up" marks consistent with normal usage, but free of defects and playability not affected. Scoring lines should be fairly sharp with only reasonable signs of wear. Certain clubs may look new, but model is simply five or more years old. If replacement shaft, must be properly installed with no defects (such as excess epoxy, scratches or improper bore-thru). Shafts may show normal bag wear. Grips should still be playable and free of tears.</p> \
					</div>';
				 v_result_html+= '</div> \
				</div>';				

	                $("#m-content").empty();
	                $("#m-content").append(v_result_html);
	                window.scrollTo(0, 0);
			});
	            } else {
	                $("#modal_Tradein").empty();
	                $("#modal_Tradein").append(v_html);
	                
	                $("#modal_Tradein").hide()
	                  .dialog({
	                    autoOpen: false,
	                    closeText: 'Close',
	                    disabled: true,
	                    maxHeight: 400,
	                    modal: true,
	                    resizable: false,
	                    height:600,
	                    width: 400
	                  });
	                $("#modal_Tradein").dialog("open");
	                $(".ui-widget-overlay").click(function(){
	                	modelTradeinClose();
	                  });
	                $(".ui-dialog-titlebar-close").click(function() {
	                	modelTradeinClose();
	                });
            	}
            } 
	    }
	  });
	}
	}

function validate_selection()
{
	if($('#state').val() == "NoSelection") {
		alert("Please select State.");
		return false;
	}
	if($('#store').val() == "NoSelection") {
		alert("Please select Store.");
		return false;
	}
	if($('#brandname').val() == "NoSelection") {
		alert("Please select Brand.");
		return false;
	}
	if($('#clubtype').val() == "NoSelection") {
		alert("Please select Club type.");
		return false;
	}
	if($('#clubmodel').val() == "NoSelection") {
		alert("Please select Club model.");
		return false;
	}
	return true;
	}


function getstorepromo(p_screen_name, p_store_id) {

	  if(p_screen_name != '') {
	    v_url = '/' + p_screen_name + '/Tradein/getstorepromo/';
	  } else {
	    v_url = '/Tradein/getstorepromo/';
	  }

	  $.ajax( {
	    type : 'post',
	    url : v_url,
	    data : 'store_id=' + p_store_id,
	    cache : false,
	    async : true,
	    beforesend : function() {

	    },
	    success : function(v_html) {	    	
	    	 if (v_html) {
	    		 $("#resort-message").show();	    		 	    		 
	    	 } else {  
	    		 $("#resort-message").hide();
	    	 }
	    }
	  });
	}
