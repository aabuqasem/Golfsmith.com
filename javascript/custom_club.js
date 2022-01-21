	function openBrWindow(theURL, winName, features) {
		//v2.0
	    window.open(theURL, winName, features);
	}

	var sw = screen.width;
	var sh = screen.height;
	var ww = 450;
	var wh = 300;
	var positionX = (sw-ww)/2;
	var positionY = (sh-wh)/2;
	features = '';
	features  += 'width=';
	features  += ww;
	features  += ',';
	features  += 'height=';
	features  += wh;
	features  += ',';
	features  += 'left=';
	features  += positionX;
	features  += ',';
	features  += 'top=';
	features  += positionY + ',';
	features  += 'scrollbars=yes,menubar=yes,resizable=no'

	function scrollToTop() {
		window.location.href="#top";
	}
		
	/*******************************************************************************
	 * Function: ShowCCStep 
	 * Purpose: offers the user a way to any previous step 
	 */

	function ShowCCStep(p_screen_name,step,session_id) {
		
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/customclub/showscreen';
		} else {
			v_url = '/customclub/showscreen';
		}
		
		// now go to the selected step
		$.ajax( {
			type : 'post',
			url : v_url,
			data : 'step=' + step + '&session_id=' + session_id,
			cache : false,
			async : true,
			success : function(v_html) {
				$("#custom_club").html(v_html);
				scrollToTop();
			}
		});
		
	}

	/*******************************************************************************
	 * Function: continueToClubSelection 
	 * Purpose: issues ajax call returns next form
	 *  to the #custom_club div innerHTML 
     */

	function continueToClubSelection(p_screen_name) {
	
		// finds and assigns which fitting method chosen (SmartFit or Regular)
		for ( var i = 0; i < document.main.cfmethod.length; i++)
			if (document.main.cfmethod[i].checked)
				var v_rad_val = document.main.cfmethod[i].value;
	
		// Men or Women club category
		var v_cust_category = $("#cust_category").val();
		
		// session id from browser
		var v_session_id = document.main.session_id.value;
		
		// Test to see if club category exist
		if (v_cust_category == "") {
			alert("Please select a club category");
			setValueToGoogleHome("Please select a club category");
			return false;
		}
		
		// Test to see if the fitting ID exists
		if (p_screen_name != '') {
			v_test_url = '/' + p_screen_name + '/customclub/sessiontest/';
		} else {
			v_test_url = '/customclub/sessiontest/';
		}
		
		$.ajax( {
			type : 'post',
			url : v_test_url,
			data : 'p_session_id=' + v_session_id,
			cache : false,
			async : true,
			success : function(v_html) {
			
				
				// ALL GOOD SO FAR, SAVE THE SELECTIONS BEFORE PROCEEDING
				if (p_screen_name != '') {
					v_save_url = '/' + p_screen_name + '/customclub/startfittingsession/';
				} else {
					v_save_url = '/customclub/startfittingsession/';
				}
				
				// ajax call to start a new custom fitting session or save the current one
				$.ajax( {
					type : 'post',
					url : v_save_url,
					data : 'session_id=' + v_session_id + '&cf_method=' + v_rad_val + '&cust_category=' + v_cust_category,
					cache : false,
					async : true,
					success : function(v_html) {
						// determine next url
						if (p_screen_name != '') {
							v_url = '/' + p_screen_name + '/customclub/clubselection/';
						} else {
							v_url = '/customclub/clubselection/';
						}
						
						// now go to the next step
						$.ajax( {
							type : 'post',
							url : v_url,
							data : 'cf_method=' + v_rad_val + '&cust_category=' + v_cust_category + '&session_id=' + v_session_id,
							cache : false,
							async : true, 
							success : function(v_html) {
								$("#custom_club").html(v_html);
								scrollToTop();
							}
						});	
					}
				});						
			}
		});
		setValueToGoogleHome();
	}

	/*******************************************************************************
	 * Function: getModels 
	 * Purpose: replaces model selection with <select> list of
	 * 	possible values based on the category, club type, and manufacturer called
	 * 	when user selects a different manufacturer. 
 	 */

	function getModels(p_cust_category, p_club_type, p_manufacturer) {
	
		var id_club_type = p_club_type.replace("/","");
		
		var v_url = '/customclub/getmodels/';
		$.ajax( {
			type : 'post',
			url : v_url,
			data : 'cust_category=' + p_cust_category + '&club_type=' + p_club_type
					+ '&manufacturer=' + p_manufacturer,
			cache : false,
			async : true, 
			success : function(v_html) {
				$("#" + id_club_type + "_model_select").html(v_html);
	
			}
		});
	}
	
	/*******************************************************************************
	 * Function: showModelImage 
	 * Purpose: displays club image based on the inventory_id called when a user selects 
	 * 	the model 
  	 */
	
	function showModelImage(p_club_type, p_inventory_item_id) {
		var v_url = '/customclub/showmodelimage/';
	
		var id_club_type = p_club_type.replace("/","");
		
		$.ajax( {
			type : 'post',
			url : v_url,
			data : 'inventory_item_id=' + p_inventory_item_id,
			cache : false,
			async : true, 
			success : function(v_html) {
				$("#" + id_club_type + "_club_image").html(v_html);
			}
		});
	}
	
	/*******************************************************************************
	 * Function: continueToPlayerProfile 
	 * Purpose: issues ajax call returns next form
	 *  to the #custom_club div innerHTML 
	 * Called From:: 
	 *  Controller: /customclub
	 * Comment Date: 3/10/2010
	 */
	
	function continueToPlayerProfile(p_screen_name) {
	
		var v_parameters = "";
		var error_message = "";
		var error_message = validateClubSelections(); // expects empty string or
														// an error message
		if (error_message.length != 0) {
			error_message = "Select " + error_message + " value(s).";
			setValueToGoogleStepSelection('Custom Clubs - SmartFit Club Selection',error_message);
			alert(error_message);
			return (false);
		}
		
		// create a query string with the selected club type + manufacturer + model
		var theForm = document.clubs
		// loop through all form elements
		for (i = 0; i < theForm.elements.length; i++) {
			// we only care about the lines that ARE checked
			if (theForm.elements[i].checked == true) {
				
				// club type
				club_type_val = theForm.elements[i].value;
	
				// club manufacturer
				club_menu_val = document.getElementById(club_type_val + '_manufacturer_id').value;
	
				// club model
				club_modl_val = document.getElementById(club_type_val + '_model_id').value;
				
				v_parameters = v_parameters + '&' +club_type_val + '_menu_val=' + club_menu_val + '&' +club_type_val + '_modl_val=' + club_modl_val;
			}
		}
		
		v_parameters = v_parameters.toLowerCase();
		
		// session id from browser
		var v_session_id = document.clubs.session_id.value;
		
	 	// URL of the fitting ID test
		if (p_screen_name != '') {
			v_test_url = '/' + p_screen_name + '/customclub/sessiontest/';
		} else {
			v_test_url = '/customclub/sessiontest/';
		}
		
		// test to see if you have a valid fitting id
		$.ajax( {
			type : 'post',
			url : v_test_url,
			data : 'p_session_id=' + v_session_id,
			cache : false,
			async : true,
			success : function(v_html) {
				if (v_html=="") { // nothing to report, proceed.
					 
					if (p_screen_name != '') {
						v_save_url = '/' + p_screen_name + '/customclub/saveclubselections/';
					} else {
						v_save_url = '/customclub/saveclubselections/';
					}
					
					// ajax call to save the basic club selections whatever they are
					$.ajax( {
						type : 'post',
						url : v_save_url,
						data : 'session_id=' + v_session_id + v_parameters,
						cache : false,
						async : true, 
						success : function() {
							// determine the next path
							if (p_screen_name != '') {
								v_url = '/' + p_screen_name + '/customclub/playerprofile/';
							} else {
								v_url = '/customclub/playerprofile/';
							}
							
							// now go to the next step
							$.ajax( {
								type : 'post',
								url : v_url,
								data : 'session_id=' + v_session_id,
								cache : false,
								async : true, 
								success : function(v_html) {
									$("#custom_club").html(v_html);
									scrollToTop();
								}
							});
						}
					}); 
				} else {
					// Oops, mulligan!
					$("#txt").html(v_html);
				}
			}
		});
		setValueToGoogleStepSelection('Custom Clubs - SmartFit Club Selection');
	}
	
	/*******************************************************************************
	 * Function: continueToCustomizedFromClubs 
	 * Purpose: issues ajax call returns next form
	 *  to the #custom_club div innerHTML 
  	 */
	
	function continueToCustomizedFromClubs(p_screen_name) {
	
		var v_parameters = "";
		var error_message = "";
		var error_message = validateClubSelections(); // expects empty string or
														// an error message
		if (error_message.length != 0) {
			error_message = "Select " + error_message + " value(s).";
			setValueToGoogleStepSelection('Custom Clubs - Order Club Selection',error_message);
			alert(error_message);
			return (false);
		}
		
		// create a query string with the selected club type + manufacturer + model
		var theForm = document.clubs
		// loop through all form elements
		for (i = 0; i < theForm.elements.length; i++) {
			// we only care about the lines that ARE checked
			if (theForm.elements[i].checked == true) {
				
				// club type
				club_type_val = theForm.elements[i].value;
	
				// club manufacturer
				club_menu_val = document.getElementById(club_type_val + '_manufacturer_id').value;
	
				// club model
				club_modl_val = document.getElementById(club_type_val + '_model_id').value;
				
				v_parameters = v_parameters + '&' +club_type_val + '_menu_val=' + club_menu_val + '&' +club_type_val + '_modl_val=' + club_modl_val;
			}
		}
		
		v_parameters = v_parameters.toLowerCase();
		
		// session id from browser
		var v_session_id = document.clubs.session_id.value;
		
		// URL of the fitting ID test
		if (p_screen_name != '') {
			v_test_url = '/' + p_screen_name + '/customclub/sessiontest/';
		} else {
			v_test_url = '/customclub/sessiontest/';
		}
		
		// test to see if you have a valid fitting id
		$.ajax( {
			type : 'post',
			url : v_test_url,
			data : 'p_session_id=' + v_session_id,
			cache : false,
			async : true,
			success : function(v_html) {
				if (v_html=="") { 
					
					// save club selections url
					if (p_screen_name != '') {
						v_save_url = '/' + p_screen_name + '/customclub/saveclubselections/';
					} else {
						v_save_url = '/customclub/saveclubselections/';
					}
					
					// save club selections 
					$.ajax( {
						type : 'post',
						url : v_save_url,
						data : 'session_id=' + v_session_id + v_parameters,
						cache : false,
						async : true,
						success : function() {
							// customize url
							if (p_screen_name != '') {
								v_url = '/' + p_screen_name + '/customclub/customize/';
							} else {
								v_url = '/customclub/customize/';
							}
							
							// go to customize
							$.ajax( {
								type : 'post',
								url : v_url,
								data : 'session_id=' + v_session_id,
								cache : false,
								async : true, 
								success : function(v_html) {
									$("#custom_club").html(v_html);
									scrollToTop();
								}
							});
						}
					});
					
					
				} else {
					// Oops, mulligan!
					$("#txt").html(v_html);
				}
			}
		});
		setValueToGoogleStepSelection('Custom Clubs - Order Club Selection');
	}
	
	/*******************************************************************************
	 * Function: validateClubSelections 
	 * Purpose: user must select at least one club
	 *  preference before proceeding to player profile. 
 	 */
	
	function validateClubSelections() {
	
		// variable declarations
		var error_message = "";
		var values_checked = false;
		var has_all_manufacturers = true;
		var has_all_models = true;
		var club_type_val = '';
	
		var theForm = document.clubs
		// loop through all form elements
		for (i = 0; i < theForm.elements.length; i++) {
			// we only care about the lines that ARE checked
			if (theForm.elements[i].checked == true) {
				values_checked = true;
	
				// check for manufacturer and model while we're here
				club_type_val = theForm.elements[i].value;
				var id_club_type = club_type_val.replace("/","");
				
				// is the manufacturer selected?
				if (document.getElementById(id_club_type + '_manufacturer_id').value < 1) {
					has_all_manufacturers = false;
				}
	
				// is the model selected?
				if (document.getElementById(id_club_type + '_model_id').value < 1) {
					has_all_models = false;
				}
			}
		}
	
		// warn about club types not checked
		if (values_checked != true) {
			error_message = error_message + " ' Club Type ' ";
		}
	
		// warn about manufacturers not selected
		if (has_all_manufacturers != true) {
			error_message = error_message + "  ' manufacturer ' ";
		}
	
		// warn about models not selected
		if (has_all_models != true) {
			error_message = error_message + "  ' Model ' ";
		}
	
		// return the appropriate error message
		return error_message;
	
	}
	
	/*******************************************************************************
	 * Function: continueToHandMeasurement 
	 * Purpose: issues ajax call returns next
	 *  form to the #custom_club div innerHTML 
  	 */
	
	function continueToHandMeasurement(p_screen_name) {
	
		var error_message = "";
		var error_message = validatePlayerProfile(); // expects empty string or
														// an error message
		if (error_message.length != 0) {
			alert(error_message);
			setValueToGooglePlayerProfile(error_message);
			return false;
		}
		
		// parameters
		var v_cust_height = (parseInt(document.profile.cust_height1.value) * 12) + parseInt(document.profile.cust_height2.value);
		var v_wrist_to_floor = parseInt(document.profile.wtofloor1.value) + parseFloat(document.profile.wtofloor2.value);
		var v_parameters = "&p_cust_height=" + v_cust_height + "&p_wrist_to_floor=" + v_wrist_to_floor;
		
		// session id from browser
		var v_session_id = document.profile.session_id.value;
		
		// URL of the fitting ID test
		if (p_screen_name != '') {
			v_test_url = '/' + p_screen_name + '/customclub/sessiontest/';
		} else {
			v_test_url = '/customclub/sessiontest/';
		}
		
		// test to see if you have a valid fitting id
		$.ajax( {
			type : 'post',
			url : v_test_url,
			data : 'p_session_id=' + v_session_id,
			cache : false,
			async : true,
			success : function(v_html) {
				if (v_html=="") { // nothing to report, proceed.
					 
					if (p_screen_name != '') {
						v_save_url = '/' + p_screen_name + '/customclub/saveplayerprofile/';
					} else {
						v_save_url = '/customclub/saveplayerprofile/';
					} 
					
					// ajax call to save the player profile
					$.ajax( {
						type : 'post',
						url : v_save_url,
						data : 'session_id=' + v_session_id + v_parameters,
						cache : false,
						async : true, 
						success : function(v_html) {
							// determine the next path
							if (p_screen_name != '') {
								v_url = '/' + p_screen_name + '/customclub/handmeasurement/';
							} else {
								v_url = '/customclub/handmeasurement/';
							}
							
							$.ajax( {
								type : 'post',
								url : v_url,
								data : 'session_id=' + v_session_id,
								cache : false,
								async : true, 
								success : function(v_html) {
									$("#custom_club").html(v_html);
									scrollToTop();
								}
							});
						}
					});
					
					
				} else {
					// Oops, mulligan!
					$("#txt").html(v_html);
				}
			}
		});
		setValueToGooglePlayerProfile();
	}
	
	/*******************************************************************************
	 * Function: validatePlayerProfile 
	 * Purpose: is to check that the user has
	 * 	selected height and wrist to floor measurement. return 
  	 */
	
	function validatePlayerProfile() {
	
		error_message = "";
		// is the height measurement selected? 0 inches is ok by default
		if (document.profile.cust_height1.value < 1) {
			error_message = error_message + "  ' Height ' ";
		}
	
		// is the wrist to floor measurement selected? 0 inches is ok by default
		if (document.profile.wtofloor1.value < 1) {
			error_message = error_message + "  ' Wrist To Floor ' ";
		}
	
		if (error_message.length != 0) {
			error_message = "Select " + error_message + " value(s).";
		}
		return error_message;
	}
	
	/*******************************************************************************
	 * Function: continueToSwingTrajectory 
	 * Purpose: issues Ajax call returns next form to the #custom_club div innerHTML 
  	 */
	
	function continueToSwingTrajectory(p_screen_name) {
	
		var error_message = "";
		error_message = validateHandMeansurement(); // expects empty string or
		
		// an error message
		if (error_message.length != 0) {
			setValueToGoogleHand(error_message);
			alert(error_message);
			return false;
		}
			
		// parameters
		var v_hand_size = parseInt(document.hand.hand_size1.value) + parseFloat(document.hand.hand_size2.value);
		var v_finger_size = document.hand.finger_size.value;
		var v_parameters = "&p_hand_size=" + v_hand_size + "&p_finger_size=" + v_finger_size;
		
		// session id from browser
		var v_session_id = document.hand.session_id.value;
		
		// URL of the fitting ID test
		if (p_screen_name != '') {
			v_test_url = '/' + p_screen_name + '/customclub/sessiontest/';
		} else {
			v_test_url = '/customclub/sessiontest/';
		}
		
		// test to see if you have a valid fitting id
		$.ajax( {
			type : 'post',
			url : v_test_url,
			data : 'p_session_id=' + v_session_id,
			cache : false,
			async : true,
			success : function(v_html) {
				if (v_html=="") { // nothing to report, proceed.
					 
					if (p_screen_name != '') {
						v_save_url = '/' + p_screen_name + '/customclub/savehandmeasurement/';
					} else {
						v_save_url = '/customclub/savehandmeasurement/';
					}
					
					// ajax call to save the player profile
					$.ajax( {
						type : 'post',
						url : v_save_url,
						data : 'session_id=' + v_session_id + v_parameters,
						cache : false,
						async : true, 
						success : function(v_html) {
							// determine the next path
							if (p_screen_name != '') {
								v_url = '/' + p_screen_name + '/customclub/swingtrajectory/';
							} else {
								v_url = '/customclub/swingtrajectory/';
							}
							
							$.ajax( {
								type : 'post',
								url : v_url,
								data : 'session_id=' + v_session_id,
								cache : false,
								async : true, 
								success : function(v_html) {
									$("#custom_club").html(v_html);
									scrollToTop();
								}
							});
						}
					}); 	
				} else {
					// Oops, mulligan!
					$("#txt").html(v_html);
				}
			}
		});	
		setValueToGoogleHand();
	}
	
	/*******************************************************************************
	 * Function: validateHandMeansurement 
	 * Purpose: is to check that the user has
	 *  selected hand size and longest finger size. return 
  	 */
	
	function validateHandMeansurement() {
		var error_message = "";
	    if (document.hand.hand_size1.value < 1) {
	      error_message = error_message + "  ' Hand Size ' ";
	    }
	             
	    if (document.hand.finger_size.value < 1) {
	      error_message = error_message + "  ' Finger Size ' ";
	    }
	    
	    if (error_message.length != 0) {
		   error_message = "Select " + error_message + " value(s).";
		}
	    return error_message;
	    
	}
	
	/*******************************************************************************
	 * Function: continueToCustomize 
	 * Purpose: issues ajax call returns next
	 * 	form to the #custom_club div innerHTML 
	 */
	
	function continueToCustomize(p_screen_name) {
	
		var error_message = "";
		var error_message = validateSwingTrajectory(); // expects empty string or
														// an error message
		if (error_message.length != 0) {
			alert(error_message);
			setValueToGoogleSwing(error_message);
			return false;
		}
		
		var v_driver_speed = document.swing.driver_speed.value;
		var v_iron_distance = document.swing.iron_distance.value;
		var v_trajectory = document.swing.trajectory.value;
		var v_tempo = document.swing.tempo.value;
		var v_target_swing = document.swing.cd_both.value;
		var v_parameters = "&p_driver_speed=" + v_driver_speed + "&p_iron_distance=" + v_iron_distance + "&p_trajectory=" + v_trajectory + "&p_tempo=" + v_tempo + "&p_target_swing=" + v_target_swing;
		
		// session id from browser
		var v_session_id = document.swing.session_id.value;
		 
		// URL of the fitting ID test
		if (p_screen_name != '') {
			v_test_url = '/' + p_screen_name + '/customclub/sessiontest/';
		} else {
			v_test_url = '/customclub/sessiontest/';
		}
		
		// test to see if you have a valid fitting id
		$.ajax( {
			type : 'post',
			url : v_test_url,
			data : 'p_session_id=' + v_session_id,
			cache : false,
			async : true,
			success : function(v_html) {
				if (v_html=="") { // nothing to report, proceed.
					// ALL GOOD SO FAR, SAVE THE SELECTIONS BEFORE PROCEEDING
					if (p_screen_name != '') {
						v_save_url = '/' + p_screen_name + '/customclub/saveswingtrajectory';
					} else {
						v_save_url = '/customclub/saveswingtrajectory/';
					}
					 
					// ajax call to save the player profile
					$.ajax( {
						type : 'post',
						url : v_save_url,
						data : 'session_id=' + v_session_id + v_parameters,
						cache : false,
						async : true, 
						success : function() {
							// determine the next path
							if (p_screen_name != '') {
								v_url = '/' + p_screen_name + '/customclub/customize/';
							} else {
								v_url = '/customclub/customize/';
							}
			
							$.ajax( {
								type : 'post',
								url : v_url,
								data : 'session_id=' + v_session_id,
								cache : false,
								async : true, 
								success : function(v_html) {
									$("#custom_club").html(v_html);
									scrollToTop();
								}
							});
						}
					});
					
					
				} else {
					// Oops, mulligan!
					$("#txt").html(v_html);
				}
			}
		});	
		setValueToGoogleSwing();
	}
	
	/*******************************************************************************
	 * Function: shaft_changedclubcust
	 * new version of haft_changed 
	 * Purpose: fine tune the Shaft Flex options based on the chosen shaft. 
	 * 	Each shaft has specific	flex possibilities
	 */
	
	function shaft_changedclubcust(p_screen_name,p_trajectory,p_model,p_club_type,p_form,p_club_type_value) {
	
		// parameters
		p_club_type = p_club_type.replace("/","");
		
		var v_shaft_model_value = document.getElementById(p_club_type+'_shaft_id').value;
		var v_dexterity = document.getElementById('dexterity').value;
		var combo = "";
		if (p_club_type == "combohybrid")
			combo = "&p_combo_iron=true";
		var v_parameters = "&p_trajectory=" + p_trajectory + "&p_model=" + p_model + "&p_club_type_value=" + v_shaft_model_value + "&p_dexterity=" + v_dexterity + combo;
		
		// URL shaft option retrieval
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/product/shaftflexoptions/';
		} else {
			v_url = '/product/shaftflexoptions/';
		}
		
		// redraw shaft flex options
		$.ajax( {
			type : 'post',
			url : v_url,
			data : v_parameters,
			cache : false,
			async : true,
			success : function(v_html) {
				$("#" + p_club_type + "_shaft_flex_select").html(v_html);
			}
		});
	}
	/*******************************************************************************
	 * Function: shaft_changed 
	 * Purpose: fine tune the Shaft Flex options based on the chosen shaft. 
	 * 	Each shaft has specific	flex possibilities
	 */
	
	function shaft_changed(p_screen_name,p_trajectory,p_model,p_club_type,p_form,p_club_type_value) {
	
		// parameters
		p_club_type = p_club_type.replace("/","");
		
		var v_shaft_model_value = document.getElementById(p_club_type+'_shaft_id').value;
		var v_dexterity = document.getElementById('dexterity').value;
		var combo = "";
		if (p_club_type == "combohybrid")
			combo = "&p_combo_iron=true";
		var v_parameters = "&p_trajectory=" + p_trajectory + "&p_model=" + p_model + "&p_club_type_value=" + v_shaft_model_value + "&p_dexterity=" + v_dexterity + combo;
		
		// session id from browser
		var v_session_id = document.customize.session_id.value;
		
		// URL shaft option retrieval
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/customclub/shaftflexoptions/';
		} else {
			v_url = '/customclub/shaftflexoptions/';
		}
		
		// redraw shaft flex options
		$.ajax( {
			type : 'post',
			url : v_url,
			data : 'p_session_id=' + v_session_id + v_parameters,
			cache : false,
			async : true,
			success : function(v_html) {
				$("#" + p_club_type + "_shaft_flex_select").html(v_html);
			}
		});
	}
	
	/*******************************************************************************
	 * Function: shaft_changed_combo 
	 * Purpose: fine tune the Shaft Flex options based on the chosen shaft. 
	 * 	Each shaft has specific	flex possibilities
	 */
	
	function shaft_changed_combo(p_screen_name,p_trajectory,p_model,p_club_type,p_form,p_club_type_value) {
	
		// parameters
		p_club_type = p_club_type.replace("/","");
		
		var v_shaft_model_value = document.getElementById(p_club_type+'_shaft_id_combo').value;
		var v_dexterity = document.getElementById('dexterity').value;
		var v_parameters = "&p_trajectory=" + p_trajectory + "&p_model=" + p_model + "&p_club_type_value=" + v_shaft_model_value + "&p_dexterity=" + v_dexterity + "&p_combo_hybrid=true";
		
		// session id from browser
		var v_session_id = document.customize.session_id.value;
		
		// URL shaft option retrieval
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/customclub/shaftflexoptions/';
		} else {
			v_url = '/customclub/shaftflexoptions/';
		}
		
		// redraw shaft flex options
		$.ajax( {
			type : 'post',
			url : v_url,
			data : 'p_session_id=' + v_session_id + v_parameters,
			cache : false,
			async : true,
			success : function(v_html) {
				$("#" + p_club_type + "_shaft_flex_select_combo").html(v_html);
			}
		});
	}
	
	
	
	/*******************************************************************************
	 * Function: shaft_changed_combocust 
	 * Purpose: fine tune the Shaft Flex options based on the chosen shaft. 
	 * 	Each shaft has specific	flex possibilities
	 */
	
	function shaft_changed_combo_cust(p_screen_name,p_trajectory,p_model,p_club_type,p_form,p_club_type_value) {
	
		// parameters
		p_club_type = p_club_type.replace("/","");
		
		var v_shaft_model_value = document.getElementById(p_club_type+'_shaft_id_combo').value;
		var v_dexterity = document.getElementById('dexterity').value;
		var v_parameters = "&p_trajectory=" + p_trajectory + "&p_model=" + p_model + "&p_club_type_value=" + v_shaft_model_value + "&p_dexterity=" + v_dexterity + "&p_combo_hybrid=true";
		
		// session id from browser
		var v_session_id = document.customize.session_id.value;
		
		// URL shaft option retrieval
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/customclub/shaftflexoptions/';
		} else {
			v_url = '/customclub/shaftflexoptions/';
		}
		
		// redraw shaft flex options
		$.ajax( {
			type : 'post',
			url : v_url,
			data : 'p_session_id=' + v_session_id + v_parameters,
			cache : false,
			async : true,
			success : function(v_html) {
				//_shaft_flex_select
				$("#_shaft_flex_select").html(v_html);
			}
		});
	}
	
	
	
	/*******************************************************************************
	 * Function: shaft_changed_combocust 
	 * Purpose: fine tune the Shaft Flex options based on the chosen shaft. 
	 * 	Each shaft has specific	flex possibilities
	 */
	
	function shaft_changed_combohybrid_cust(p_screen_name,p_trajectory,p_model,p_club_type,p_form,p_club_type_value) {
	
		// parameters
		p_club_type = p_club_type.replace("/","");
		
		var v_shaft_model_value = document.getElementById(p_club_type+'_shaft_id_combo').value;
		var v_dexterity = document.getElementById('dexterity').value;
		var v_parameters = "&p_trajectory=" + p_trajectory + "&p_model=" + p_model + "&p_club_type_value=" + v_shaft_model_value + "&p_dexterity=" + v_dexterity + "&p_combo_hybrid=true";
		
		// session id from browser
		var v_session_id = document.customize.session_id.value;
		
		// URL shaft option retrieval
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/customclub/shaftflexoptionscust/';
		} else {
			v_url = '/customclub/shaftflexoptionscust/';
		}
		

		// redraw shaft flex options
		$.ajax( {
			type : 'post',
			url : v_url,
			data : 'p_session_id=' + v_session_id + v_parameters,
			cache : false,
			async : true,
			success : function(v_html) {
				//_shaft_flex_select
				$("#_shaft_flex_select_combo").html(v_html);
			}
		});
	}
	
	/*******************************************************************************
	 * Function: grip_changedclubcust 
	 * Purpose: gets grip sizes
	 * new version of grip_changed 
	 */
	
	function grip_changedclubcust(p_screen_name,p_inventory_item_id,p_club_type,p_grip_id) {
		
		// URL shaft option retrieval
		var v_session_id = document.customize.session_id.value;
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/product/gripsizes/';
		} else {
			v_url = '/product/gripsizes/';
		}
		
		// get the dexterity chosen
		var dexterity = document.getElementById("dexterity").value;
		
		// Get grip options per grip model  
		var v_parameters = "&p_inventory_item_id=" + p_inventory_item_id + "&p_grip_id=" + p_grip_id + "&p_dexterity=" + dexterity;
		
		$.ajax( {
			type : 'post',
			url : v_url,
			data : 'p_session_id=' + v_session_id + v_parameters,
			cache : false,
			async : true,
			success : function(v_html) {
				p_club_type = p_club_type.replace("/","");
				document.getElementById('_grip_size').length = 0;
				response = v_html.split(/\|/);
				for (var k = 0; k < response.length; k++) {
					select = document.getElementById('_grip_size');
				    var index = select.options.length;
				    select.options.length = index + 1;
				    select.options[index].text = response[k];
				    if (response[k].indexOf('Standard')>-1) {
				    	select.options[index].selected = true;
				    }
				    select.options[index].value = response[k];					
				}		
			}
		});
		
	}

	/*******************************************************************************
	 * Function: grip_changed 
	 * Purpose: gets grip sizes 
	 */
	
	function grip_changed(p_screen_name,p_inventory_item_id,p_club_type,p_grip_id) {
		
		// session id from browser
		var v_session_id = document.customize.session_id.value;
		
		// URL shaft option retrieval
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/customclub/gripsizes/';
		} else {
			v_url = '/customclub/gripsizes/';
		}
		
		// get the dexterity chosen
		var dexterity = document.getElementById("dexterity").value;
		
		// Get grip options per grip model  
		var v_parameters = "&p_inventory_item_id=" + p_inventory_item_id + "&p_grip_id=" + p_grip_id + "&p_dexterity=" + dexterity;
		
		$.ajax( {
			type : 'post',
			url : v_url,
			data : 'p_session_id=' + v_session_id + v_parameters,
			cache : false,
			async : true,
			success : function(v_html) {
				p_club_type = p_club_type.replace("/","");
				document.getElementById(p_club_type+'_grip_size').length = 0;
				response = v_html.split(/\|/);
				for (var k = 0; k < response.length; k++) {
					select = document.getElementById(p_club_type+'_grip_size');
				    var index = select.options.length;
				    select.options.length = index + 1;
				    select.options[index].text = response[k];
				    if (response[k].indexOf('Standard')>-1) {
				    	select.options[index].selected = true;
				    }
				    select.options[index].value = response[k];					
				}		
			}
		});
		
	}
	/*******************************************************************************
	 * Function: changeDexterity 
	 * Purpose: sets lie angles, and club head availability 
	 * This is modified version of changeDexterity
	 */
	
	function changeDexterityHandClub(p_screen_name,p_model, p_style,dexValue,handDir) {
		
		// session id from browser
		var v_session_id = document.customize.session_id.value;
		
		// get the dexterity chosen
		var dexterity = dexValue;
		$("#dexterity").val(dexValue);
		
		if (handDir == "handRight"){
			$('#handLeft').removeClass("CC_selected");
			$('#handRight').removeClass("CC_selected");
			$('#handRight').addClass("CC_selected");
		}
		
		if (handDir == "handLeft"){
			$('#handLeft').removeClass("CC_selected");
			$('#handRight').removeClass("CC_selected");
			$('#handLeft').addClass("CC_selected");
		}
		
		
		// lie angle url
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/product/lieangleoptions/';
		} else {
			v_url = '/product/lieangleoptions/';
		}
		
		// club head url
		if (p_screen_name != '') {
			v_club_head_url = '/' + p_screen_name + '/product/clubheadoptionscust/';
		} else {
			v_club_head_url = '/product/clubheadoptionscust/';
		}
		
		// grip model
		if (p_screen_name != '') {
			v_grip_url = '/' + p_screen_name + '/product/gripoptions/';
		} else {
			v_grip_url = '/product/gripoptions/';
		}	
		
		// reset shaft and flex
			var shaft_id = 0;
			if ($('#_shaft_id')) { //--
				if ($('#_shaft_id').val() > 0) { // shaft selected --
					shaft_id = $('#_shaft_id').val(); //--
					document.getElementById('_shaft_flex').length = 0;
					select = document.getElementById('_shaft_flex');
				    var index = select.options.length;
				    select.options.length = index + 1;
				    select.options[index].text = 'Select Shaft Model';
				    select.options[index].value = '';		
				    select.options[index].selected = true;
				}
				document.getElementById('_shaft_id').selectedIndex = 0; //--
			}
		
		// go through each club type on the form and assign the designated available club lie angles 
		// and specified dexterity. 
			var v_parameters = "&p_inventory_item_id=" + p_model + "&p_dexterity=" + dexterity + "&cusStyle=" + p_style;
			
			// ajax to get and set lie angles
			$.ajax( {
				type : 'post',
				url : v_url,
				data : 'p_session_id=' + v_session_id +v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					v_html = v_html.toString().replace("1~","");
					var response = v_html.split(/\|/);
					document.getElementById('_lie_angle').length = 0;
					for (var k = 0; k < response.length; k++) {
						select = document.getElementById('_lie_angle');
					    var index = select.options.length;
					    select.options.length = index + 1;
					    select.options[index].text = response[k];
					    if (response[k].indexOf('Standard')>-1) {
					    	select.options[index].selected = true;
					    }
					    select.options[index].value = response[k];					
					}		
				}
			});
			
			// ajax to get and set grip models
			$.ajax( {
				type : 'post',
				url : v_grip_url,
				data : 'p_session_id=' + v_session_id + v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					$("#_grip_select").html(v_html);		
				}
			});
			
			// ajax to get and set club heads
			var p_club_type = $("#p_club_type").val();

			v_parameters += "&p_club_type=" + p_club_type;

			$.ajax( {
				type : 'post',
				url : v_club_head_url,
				data : 'p_session_id=' + v_session_id + v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					$('#_no_set').html(v_html);
					total_amount_cusclub();
				}
			});
			

	}
	
	
	function show_modal_possible_sets(){
		//alert();

		$("#modal_possible_sets").hide()
  		.dialog({
  		  autoOpen: false,
  		  closeText: 'Close',
  		  disabled: true,
  		  modal: true,
  		  resizable: false,
  		  height:'auto',
  		  width: 650
  		});
		
		// Make pop up visible if it was previously closed
  		$("#modal_possible_sets").parent().removeClass('ui-state-disabled');
		$("#modal_possible_sets").dialog("open");
		
		return false;
	}

	
	
	
	
	
	/*******************************************************************************
	 * Function: changeDexterity 
	 * Purpose: sets lie angles, and club head availability 
	 * This is modified version of changeDexterity
	 */
	
	function changeDexterityHandClubHybrid(p_screen_name,p_model, p_style,dexValue,handDir) {
		
		// session id from browser
		var v_session_id = document.customize.session_id.value;
		
		// get the dexterity chosen
		var dexterity = dexValue;
		$("#dexterity").val(dexValue);
		
		if (handDir == "handRight"){
			$('#handLeft').removeClass("CC_selected");
			$('#handRight').removeClass("CC_selected");
			$('#handRight').addClass("CC_selected");
		}
		
		if (handDir == "handLeft"){
			$('#handLeft').removeClass("CC_selected");
			$('#handRight').removeClass("CC_selected");
			$('#handLeft').addClass("CC_selected");
		}
		
		
		// lie angle url
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/product/lieangleoptions/';
		} else {
			v_url = '/product/lieangleoptions/';
		}
		
		// club head url
		if (p_screen_name != '') {
			v_club_head_url = '/' + p_screen_name + '/product/clubheadoptions/';
		} else {
			v_club_head_url = '/product/clubheadoptions/';
		}
		
		// grip model
		if (p_screen_name != '') {
			v_grip_url = '/' + p_screen_name + '/product/gripoptions/';
		} else {
			v_grip_url = '/product/gripoptions/';
		}	
		
		// reset shaft and flex
			var shaft_id = 0;
			if ($('#_shaft_id_combo')) { //--
				if ($('#_shaft_id_combo').val() > 0) { // shaft selected --
					shaft_id = $('#_shaft_id_combo').val(); //--
					document.getElementById('_shaft_flex').length = 0;
					select = document.getElementById('_shaft_flex');
				    var index = select.options.length;
				    select.options.length = index + 1;
				    select.options[index].text = 'Select Shaft Model';
				    select.options[index].value = '';		
				    select.options[index].selected = true;
				}
				document.getElementById('_shaft_id_combo').selectedIndex = 0; //--
			}
		
		// go through each club type on the form and assign the designated available club lie angles 
		// and specified dexterity. 
			var v_parameters = "&p_inventory_item_id=" + p_model + "&p_dexterity=" + dexterity + "&cusStyle=" + p_style;
			
			// ajax to get and set lie angles
			$.ajax( {
				type : 'post',
				url : v_url,
				data : 'p_session_id=' + v_session_id +v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					v_html = v_html.toString().replace("1~","");
					var response = v_html.split(/\|/);
					document.getElementById('_lie_angle').length = 0;
					for (var k = 0; k < response.length; k++) {
						select = document.getElementById('_lie_angle');
					    var index = select.options.length;
					    select.options.length = index + 1;
					    select.options[index].text = response[k];
					    if (response[k].indexOf('Standard')>-1) {
					    	select.options[index].selected = true;
					    }
					    select.options[index].value = response[k];					
					}		
				}
			});
			
			// ajax to get and set grip models
			$.ajax( {
				type : 'post',
				url : v_grip_url,
				data : 'p_session_id=' + v_session_id + v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					$("#_grip_select").html(v_html);		
				}
			});
			
			// ajax to get and set club heads
			$.ajax( {
				type : 'post',
				url : v_club_head_url,
				data : 'p_session_id=' + v_session_id + v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					$('#_no_set').html(v_html);
					total_amount_cusclub();
				}
			});
			

	}
	
	
	
	

	/*******************************************************************************
	 * Function: changeDexterity 
	 * Purpose: sets lie angles, and club head availability 
	 * This is modified version of changeDexterity
	 */
	
	function changeDexterityClub(p_screen_name,p_model, p_style) {
		
		// session id from browser
		var v_session_id = document.customize.session_id.value;
		
		// get the dexterity chosen
		var dexterity = document.getElementById("dexterity").value;
		
		// lie angle url
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/product/lieangleoptions/';
		} else {
			v_url = '/product/lieangleoptions/';
		}
		
		// club head url
		if (p_screen_name != '') {
			v_club_head_url = '/' + p_screen_name + '/product/clubheadoptions/';
		} else {
			v_club_head_url = '/product/clubheadoptions/';
		}
		
		// grip model
		if (p_screen_name != '') {
			v_grip_url = '/' + p_screen_name + '/product/gripoptions/';
		} else {
			v_grip_url = '/product/gripoptions/';
		}	
		
		// reset shaft and flex
			var shaft_id = 0;
			if ($('#_shaft_id')) {
				if ($('#_shaft_id').val() > 0) { // shaft selected
					shaft_id = $('#_shaft_id').val();
					document.getElementById('_shaft_flex').length = 0;
					select = document.getElementById('_shaft_flex');
				    var index = select.options.length;
				    select.options.length = index + 1;
				    select.options[index].text = 'Select Shaft Model';
				    select.options[index].value = '';		
				    select.options[index].selected = true;
				}
				document.getElementById('_shaft_id').selectedIndex = 0;
			}
		
		// go through each club type on the form and assign the designated available club lie angles 
		// and specified dexterity. 
			var v_parameters = "&p_inventory_item_id=" + p_model + "&p_dexterity=" + dexterity + "&cusStyle=" + p_style;
			
			// ajax to get and set lie angles
			$.ajax( {
				type : 'post',
				url : v_url,
				data : 'p_session_id=' + v_session_id +v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					v_html = v_html.toString().replace("1~","");
					var response = v_html.split(/\|/);
					document.getElementById('_lie_angle').length = 0;
					for (var k = 0; k < response.length; k++) {
						select = document.getElementById('_lie_angle');
					    var index = select.options.length;
					    select.options.length = index + 1;
					    select.options[index].text = response[k];
					    if (response[k].indexOf('Standard')>-1) {
					    	select.options[index].selected = true;
					    }
					    select.options[index].value = response[k];					
					}		
				}
			});
			
			// ajax to get and set grip models
			$.ajax( {
				type : 'post',
				url : v_grip_url,
				data : 'p_session_id=' + v_session_id + v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					$("#_grip_select").html(v_html);		
				}
			});
			
			// ajax to get and set club heads
			$.ajax( {
				type : 'post',
				url : v_club_head_url,
				data : 'p_session_id=' + v_session_id + v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					$('#_no_set').html(v_html);
					total_amount_cusclub();
				}
			});
			

	}

	
	/*******************************************************************************
	 * Function: changeDexterity 
	 * Purpose: sets lie angles, and club head availability 
	 */
	
	function changeDexterity(p_screen_name,p_club_types,p_club_models,p_model) {
		
		// split club parameters
		var club_types = p_club_types.split(/\,/); // splits on the comma
		var club_models = p_club_models.split(/\,/); // splits on the comma
		
		// session id from browser
		var v_session_id = document.customize.session_id.value;
		
		// get the dexterity chosen
		var dexterity = document.getElementById("dexterity").value;
		
		// lie angle url
		if (p_screen_name != '') {
			v_url = '/' + p_screen_name + '/customclub/lieangleoptions/';
		} else {
			v_url = '/customclub/lieangleoptions/';
		}
		
		// club head url
		if (p_screen_name != '') {
			v_club_head_url = '/' + p_screen_name + '/customclub/clubheadoptions/';
		} else {
			v_club_head_url = '/customclub/clubheadoptions/';
		}
		
		// grip model
		if (p_screen_name != '') {
			v_grip_url = '/' + p_screen_name + '/customclub/gripoptions/';
		} else {
			v_grip_url = '/customclub/gripoptions/';
		}	
		
		// reset shaft and flex
		$.each(club_types, function(key, value) { 
			var v_shaft_select_list = club_types[key];
			var shaft_id = 0;
			if (document.getElementById(v_shaft_select_list+'_shaft_id')) {
				if (document.getElementById(v_shaft_select_list+'_shaft_id').value > 0) { // shaft selected
					shaft_id = document.getElementById(v_shaft_select_list+'_shaft_id').value;
					document.getElementById(shaft_id+'_shaft_flex').length = 0;
					select = document.getElementById(shaft_id+'_shaft_flex');
				    var index = select.options.length;
				    select.options.length = index + 1;
				    select.options[index].text = 'Select Shaft Model';
				    select.options[index].value = '';		
				    select.options[index].selected = true;
				}
				document.getElementById(v_shaft_select_list+'_shaft_id').selectedIndex = 0;
			}
		});
		
		// go through each club type on the form and assign the designated available club lie angles 
		// and specified dexterity. 
		$.each(club_types, function(key, value) { 
			var v_parameters = "&p_inventory_item_id=" + club_models[key] + "&p_dexterity=" + dexterity + "&p_club_type=" + club_types[key];
			var v_line_angle_select_list = club_types[key];
			
			v_line_angle_select_list = v_line_angle_select_list.replace("/","");
			
			// ajax to get and set lie angles
			$.ajax( {
				type : 'post',
				url : v_url,
				data : 'p_session_id=' + v_session_id + v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					v_html = v_html.toString().replace("1~","");
					var response = v_html.split(/\|/);
					document.getElementById(v_line_angle_select_list+'_lie_angle').length = 0;
					for (var k = 0; k < response.length; k++) {
						select = document.getElementById(v_line_angle_select_list+'_lie_angle');
					    var index = select.options.length;
					    select.options.length = index + 1;
					    select.options[index].text = response[k];
					    if (response[k].indexOf('Standard')>-1) {
					    	select.options[index].selected = true;
					    }
					    select.options[index].value = response[k];					
					}		
				}
			});
			
			club_types[key] = club_types[key].replace("/","");
			
			// ajax to get and set grip models
			$.ajax( {
				type : 'post',
				url : v_grip_url,
				data : 'p_session_id=' + v_session_id + v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					$("#" + club_types[key] + "_grip_select").html(v_html);		
				}
			});
			
			// ajax to get and set club heads
			$.ajax( {
				type : 'post',
				url : v_club_head_url,
				data : 'p_session_id=' + v_session_id + v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {
					$('#' + club_types[key] + '_no_set').html(v_html);
					total_amount();
				}
			});
			
		});
	}
	
	/*******************************************************************************
	 * Function: findOldestDate 
	 * Purpose: loops through all form elements matching availability date and returns the largest value found in human format
	 */
	
	function findOldestDate() {
		var dates = new Array();
		var index_counter = 0;
		for(i=0; i<document.customize.elements.length; i++) {
			str = document.customize.elements[i].name;
			if (str.indexOf("availability_date")>0 && document.customize.elements[i].value != "")	
				dates[index_counter++] = document.customize.elements[i].value;
		}
		dates.reverse();
		dates[0]*=1000;
		var datum = new Date(dates[0]);
		var finaldate = datum.toLocaleDateString();
		if (finaldate == "NaN" || finaldate == "Invalid Date" || finaldate == "Not Available") {
			var d = new Date();
			finaldate = new Date(d.getFullYear(), d.getMonth(), d.getDate()+14, d.getHours(), d.getMinutes(), d.getSeconds(), d.getMilliseconds()); // 2 week lead time
			finaldate = finaldate.toLocaleDateString(); //doesn't work on safari
			
			//var formatDate = finaldate.getMonth() + "/" + finaldate.getDate() + "/" + finaldate.getFullYear();
		}
		document.getElementById('availability_date').value = finaldate;
	}
	
	/*******************************************************************************
	 * Function: validateSwingTrajectory 
	 * Purpose: is to check that the user has
	 *  selected driver speed, trajectory, and swing temp. return 
	 */
	
	function validateSwingTrajectory() {
		error_message = "";
		if (document.swing.driver_speed.value < 1 && document.swing.iron_distance.value < 1) {
		  error_message = error_message + "  ' Driver Speed OR  Iron Distance ' ";
		}
		         
		if (document.swing.trajectory.value < 1) {
		  error_message = error_message + "  ' Trajectory ' ";
		}
		         
		if (document.swing.tempo.value < 1) {
		  error_message = error_message + "  ' Swing Tempo ' ";
		}
		
		if (error_message.length != 0) {
		   error_message = "Select " + error_message + " value(s).";
		}
		
		return error_message;
		
	
	}
	/*******************************************************************************
	 * Function: continueToReviewCustClub
	 * Purpose: issues ajax call returns next form to the #custom_club div innerHTML
	 * new version of continueToReview 
	 */
	
	function continueToReviewCustClub(p_screen_name) {
		
		var v_club_types = document.customize.club_types.value;
		var v_club_models = document.customize.club_models.value;
		
		var error_message = "";
		var error_message = validateCustomization(p_screen_name,v_club_types,v_club_models); 
	
		if (error_message.length != 0) {
			setValueToGoogleStepCustomized(error_message);
			alert(error_message);
			return false;
		}
		
		var v_session_id = document.customize.session_id.value;
		
		if (p_screen_name != '') {
			v_save_url = '/' + p_screen_name + '/product/savecustomize/';
		} else {
			v_save_url = '/product/savecustomize/';
		}
		
		var v_parameters = "&customize_parameters=";
		var formElements = document.customize.elements;
		for (i=0; i<formElements.length; i++) {
			v_parameters += "@@@@" + formElements[i].name + "=" + formElements[i].value;
			if (formElements[i].checked)
				v_parameters += "@@@@" + formElements[i].name + "_checked=true";
		}
		
		// ajax call to save the customize selections
		$.ajax( {
			type : 'post',
			url : v_save_url,
			data : 'session_id=' + v_session_id +v_parameters,
			cache : false,
			async : true,
			success : function(v_html) {
				if (p_screen_name != '') {
					v_url = '/' + p_screen_name + '/product/review/';
				} else {
					v_url = '/product/review/';
				}
				$.ajax( {
					type : 'post',
					url : v_url,
					data : 'session_id=', // todo remove it later
					cache : false,
					async : true,
					beforesend : function() {
					},
					success : function(v_html) {
					
						setValueToGoogleStepCustomized();
						$("#custom_club").html(v_html);
						scrollToTop();
					}
				});
				
			}
		});
		
	}
	
	/*******************************************************************************
	 * Function: continueToReview
	 * Purpose: issues ajax call returns next form to the #custom_club div innerHTML 
	 */
	
	function continueToReview(p_screen_name) {
		
		var v_club_types = document.customize.club_types.value;
		var v_club_models = document.customize.club_models.value;
		
		var error_message = "";
		var error_message = validateCustomization(p_screen_name,v_club_types,v_club_models); 
	
		if (error_message.length != 0) {
			setValueToGoogleStepCustomized(error_message);
			alert(error_message);
			return false;
		}
		
		var v_session_id = document.customize.session_id.value;
		
		if (p_screen_name != '') {
			v_save_url = '/' + p_screen_name + '/customclub/savecustomize/';
		} else {
			v_save_url = '/customclub/savecustomize/';
		}
		
		var v_parameters = "&customize_parameters=";
		var formElements = document.customize.elements;
		for (i=0; i<formElements.length; i++) {
			v_parameters += "@@@@" + formElements[i].name + "=" + formElements[i].value;
			if (formElements[i].checked)
				v_parameters += "@@@@" + formElements[i].name + "_checked=true";
		}
		
		// ajax call to save the customize selections
		$.ajax( {
			type : 'post',
			url : v_save_url,
			data : 'session_id=' + v_session_id + v_parameters,
			cache : false,
			async : true,
			success : function(v_html) {
				if (p_screen_name != '') {
					v_url = '/' + p_screen_name + '/customclub/review/';
				} else {
					v_url = '/customclub/review/';
				}
				$.ajax( {
					type : 'post',
					url : v_url,
					data : 'session_id=' + v_session_id,
					cache : false,
					async : true,
					beforesend : function() {
					},
					success : function(v_html) {
					
						setValueToGoogleStepCustomized();
						$("#custom_club").html(v_html);
						scrollToTop();
					}
				});
				
			}
		});
		
	}

	/*******************************************************************************
	 * Function: validateCustomizationCustClub 
	 * Purpose: ensures customized step has at least the required information
	 * new version of validateCustomization 
	 */
	
	function validateCustomizationCustClub(p_screen_name,p_club_models) {
		
		// template error message to return
		error_message = "Select";
		
		// split club parameters
		//var club_types = p_club_types.split(/\,/); // splits on the comma
		var club_models = p_club_models.split(/\,/); // splits on the comma
		
		// session id from browser
		//var v_session_id = document.customize.session_id.value;
		
		// get the dexterity chosen
		var dexterity = document.getElementById("dexterity").value;
		if (dexterity == "") {
			error_message += " 'Hand (Right or Left)' ";
		} 
	
	 	// are any clubs selected?
		clubs_selected = false;
			
			this_club_selected = false;
			
			//value = value.replace("/","");
			
			// go through all checkboxes
			if (document.getElementById('_club_head_index')) {
				var club_count_index = document.getElementById('_club_head_index').value
				for (var index = 0; index < club_count_index; index ++) {
					if (document.getElementById('_club_head_id_' + index).checked == true) {
						clubs_selected = true; 
						this_club_selected = true;
						
					}				
				}
				if (club_count_index == 0) { // then you're not buying a club that contains set information
					clubs_selected = true; 
				}
			}
			// if so, did you at least select a shaft and grip for that club
			if (this_club_selected == true) {
					if (document.getElementById('_shaft_id')) {
						if (document.getElementById('_shaft_id').value == 0) {
							error_message +=  " '" + " shaft model' ";
						}
					}
				}
				if (document.getElementById( '_grip_id')) {
					if (document.getElementById('_grip_id').value == 0) {
						error_message +=  " '" +  " grip model' ";
					}
				}
				// if serial replacement, then ignore
				if (document.getElementById( '_serial_number'))
					if (document.getElementById('_serial_number').value != "")
						error_message = "";
			
			
		//hosam
		
		// need at least 1 club
		if (!clubs_selected) {
			error_message += " 'at least 1 club' "
		}
		
		// and send back an error message if found
		if (error_message != "Select") {
			return error_message;
		} else {
			return '';
		}
	}

	/*******************************************************************************
	 * Function: validateCustomization 
	 * Purpose: ensures customized step has at least the required information 
	 */
	
	function validateCustomization(p_screen_name,p_club_types,p_club_models) {
		
		// template error message to return
		error_message = "Select";
		
		// split club parameters
		var club_types = p_club_types.split(/\,/); // splits on the comma
		var club_models = '';
		
		if (p_club_models != null) {
			var club_models = p_club_models.split(/\,/); // splits on the comma
		}
		
		
		// session id from browser
		var v_session_id = document.customize.session_id.value;
		
		// get the dexterity chosen
		var dexterity = document.getElementById("dexterity").value;
		if (dexterity == "") {
			error_message += " 'Hand (Right or Left)' ";
		} 
	
	 	// are any clubs selected?
		clubs_selected = false;
		$.each(club_types, function(key, value) { 
			
			this_club_selected = false;
			
			value = value.replace("/","");
			
			// go through all checkboxes
			if (document.getElementById(value + '_club_head_index')) {
				var club_count_index = document.getElementById(value + '_club_head_index').value
				for (var index = 0; index < club_count_index; index ++) {
					if (document.getElementById(value + '_club_head_id_' + index).checked == true) {
						clubs_selected = true; 
						this_club_selected = true;
						
					}				
				}
				if (club_count_index == 0) { // then you're not buying a club that contains set information
					clubs_selected = true; 
				}
			}
			// if so, did you at least select a shaft and grip for that club
			if (this_club_selected == true) {
				if (value == "combohybrid") { // look at the hybrid and iron counts if either one is > 0, then is that shaft selected.
					var club_head_count = document.getElementById(value + '_club_head_index').value;
					var hybrid_count = 0;
					var iron_count = 0;
					if (club_head_count>0) { 
						for (var c=0; c<club_head_count;c++) {
							if (document.getElementById(value + '_club_head_id_'+c).checked) {
								if (document.getElementById(value + '_club_head_hybrid_flag_'+c)) {
									if (document.getElementById(value + '_club_head_hybrid_flag_'+c).value == "Y")
										hybrid_count++;
									else
										iron_count++;
								}
							}
						}
					}
					
					if (iron_count > 0 && document.getElementById(value + '_shaft_id')) {
						if (document.getElementById(value + '_shaft_id').value == 0) {
							error_message +=  " 'Irons shaft model' "
						} else {
							combohybrid = true;
						}
					}
					if (hybrid_count > 0 && document.getElementById(value + '_shaft_id_combo')) {
						if (document.getElementById(value + '_shaft_id_combo').value == 0) {
							error_message +=  " 'Hybrids shaft model' "
						} else {
							combohybrid = true;
						}
					}
				} else { // other club model
					if (document.getElementById(value + '_shaft_id')) {
						if (document.getElementById(value + '_shaft_id').value == 0) {
							error_message +=  " '" + value + " shaft model' "
						}
					}
				}
				if (document.getElementById(value + '_grip_id')) {
					if (document.getElementById(value + '_grip_id').value == 0) {
						error_message +=  " '" + value + " grip model' "
					}
				}
				// if serial replacement, then ignore
				if (document.getElementById(value + '_serial_number'))
					if (document.getElementById(value + '_serial_number').value != "")
						error_message = "";
			}
			
			
		});
		
		// need at least 1 club
		if (!clubs_selected) {
			error_message += " 'at least 1 club' "
		}
		
		// and send back an error message if found
		if (error_message != "Select") {
			return error_message;
		} else {
			return '';
		}
	}
	
	/*******************************************************************************
	 * Function: formatCurrency
	 * Purpose: returns the number sent formatted in to currency form 
	 */
	
	function formatCurrency(num) {
		num = num.toString().replace(/\$|\,/g,'');
		if(isNaN(num))
			num = "0";
		sign = (num == (num = Math.abs(num)));
		num = Math.floor(num*100+0.50000000001);
		cents = num%100;
		num = Math.floor(num/100).toString();
		if(cents<10)
			cents = "0" + cents;
		for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++)
			num = num.substring(0,num.length-(4*i+3))+','+ num.substring(num.length-(4*i+3));
		return (((sign)?'':'-') + '$' + num + '.' + cents);
	}
	/*******************************************************************************
	 * Function: total_amount_cusclub
	 * Purpose: tallys up the custom club fitting 
	 * todo this is very crtical function need a lot of attention
	 * This is new version ototal_amount function
	 */
	
	function total_amount_cusclub() {
		
		var total_amount = 0;
		var total_club_count = 0;
		var selected_club_count = 0;
		
		if (document.getElementById('total_count_container'))
			document.getElementById('total_count_container').innerHTML = '0';
		
		var grand_club_count = 0;
		var grand_total_amount = 0;
		var per_order_services = [];
		var per_order_service_count = 0;
		var oktoadd = true;
		
		
			
			//va_club_types[i] = va_club_types[i].replace("/","");
			
			// some variables
			var ind_price = 0;
			var set_price = 0;
			var selected_club_count = 0;
			var total_amount = 0;
			var set = "";
			var club_head_count = 0;
			var ind_club_count = 0;
			var hybrid_count = 0;
			var iron_count = 0;
			
			// figure out the selected club count(s)
			if ($('#_club_head_index')) {
				var club_head_count = $('#_club_head_index').val();
				if (club_head_count>0) { 
					for (var c=0; c<club_head_count;c++) {
						if (document.getElementById('_club_head_id_'+c).checked) {
							if ($('#_club_head_ind_price_'+c)) { 
								ind_price += parseFloat($('#_club_head_ind_price_'+c).val());
							}
							if ($('#_club_head_set_price_'+c)) {
								set_price += parseFloat($('#_club_head_set_price_'+c).val());
							}
							if ($('#_club_head_description_'+c)) {
								set += $('#_club_head_description_'+c).val() + ",";
							}
							selected_club_count++;
							
							// now to deal with the combo/hybrid selection
							if (document.getElementById('_club_head_hybrid_flag_'+c)) {
								if (document.getElementById('_club_head_hybrid_flag_'+c).value == "Y")
									hybrid_count++;
								else
									iron_count++;
							}
						}
					}
				} else { // for putters
					if (document.getElementById('_club_head_ind_price_'+club_head_count)) {
						set_price += parseFloat(document.getElementById('_club_head_ind_price_'+club_head_count).value);
						ind_club_count++;
					}
				}				
			}
			
			 // removes the trailing ','
			if (set) set = set.substring(0, set.length-1); 
			
			set_matched = false;
			
			// did you select all the clubs in the set?
			if (document.getElementById('combohybrid_set_count')) {
				var club_set_count = document.getElementById('combohybrid_set_count').value;
				if (club_set_count>0) { 
					for (var c=0; c<club_set_count;c++) {
						if (set) {
							if (set == document.getElementById('combohybrid_'+c+'_set').value)
								set_matched = true;
						}
					}
				}
			}			
			
			// figure out the total amount
			if (selected_club_count == club_head_count || set_matched) {
				if (set_price > 0)
					total_amount += set_price;
				else 
					total_amount += ind_price;
			} else {
				total_amount += ind_price;
			}   
			selected_club_count += ind_club_count;
			var service_charges = 0;
			
			// add up the services charges per club
			if (document.getElementById('_services_count')) {
				service_count = document.getElementById('_services_count').value;
				if (service_count>0) { 
					
					for (var c=0; c<service_count;c++) {
						if (document.getElementById('_service_'+c).checked) {
							if (document.getElementById('_service_'+c+'_service_level').value == "Per Order") {
								for (var p=0; p<per_order_service_count;p++) {
									if (per_order_services[p] == document.getElementById('_service_'+c+'_id').value)
										oktoadd = false;
								}
								if (oktoadd) {
									if (document.getElementById('_service_'+c+'_price'))
										service_charges += parseFloat(document.getElementById('_service_'+c+'_price').value);
									if (document.getElementById('_service_'+c+'_id'))
										per_order_services[per_order_service_count++] = document.getElementById('_service_'+c+'_id').value;
								}
							} else {
								if (document.getElementById('_service_'+c+'_price'))
									service_charges += (parseFloat(document.getElementById('_service_'+c+'_price').value) * selected_club_count);
							}
						}
					}
				}
			}
			
			// update the visual charges
			if (service_charges > 0) {
				if (document.getElementById('_service_price_container'))
					document.getElementById('_service_price_container').innerHTML = formatCurrency(service_charges);
				if (document.getElementById('_service_price'))
					document.getElementById('_service_price').value = service_charges;
				total_amount += (service_charges);
			} else {
				if (document.getElementById('_service_price_container'))
					document.getElementById('_service_price_container').innerHTML = formatCurrency(0);
			}
			
			// figure out grip charges
			if (document.getElementById('_grip_id'))
				var grip_id = document.getElementById('_grip_id').value;
			if (document.getElementById('_grip_id_' + grip_id))
				var grip_upcharge = document.getElementById('_grip_id_' + grip_id).value;
			if (grip_upcharge) {
				if (document.getElementById('_grip_price_container'))
					document.getElementById('_grip_price_container').innerHTML = formatCurrency(grip_upcharge);
				total_amount += (grip_upcharge*selected_club_count);		
			}
			
			// now the shaft charges
			if (hybrid_count > 0) { // we could have an iron count too
				var shaft_id = 0;
				if (document.getElementById('_shaft_id'))	
					shaft_id = document.getElementById('_shaft_id').value;
				
				var iron_shaft_charge = 0;
				if (document.getElementById('_shaft_id_price_' + shaft_id))
					iron_shaft_charge = document.getElementById('_shaft_id_price_' + shaft_id).value;
				
				var combo_shaft_id = 0;
				if (document.getElementById('_shaft_id_combo'))	
					combo_shaft_id = document.getElementById('_shaft_id_combo').value;
				
				var combo_shaft_upcharge = 0;
				if (document.getElementById('_shaft_id_price_' + combo_shaft_id + '_combo'))
					combo_shaft_upcharge = document.getElementById('_shaft_id_price_' + combo_shaft_id + '_combo').value;
				
				if (combo_shaft_upcharge || iron_shaft_charge)
					if (document.getElementById('_shaft_price_container'))
						document.getElementById('_shaft_price_container').innerHTML = formatCurrency(parseFloat(combo_shaft_upcharge*hybrid_count) + parseFloat(iron_shaft_charge*iron_count));
				
				total_amount += ((combo_shaft_upcharge*hybrid_count) + (iron_shaft_charge*iron_count));
				selected_club_count = (hybrid_count + iron_count);
			} else {
				if (document.getElementById('_shaft_id'))	
					var shaft_id = document.getElementById('_shaft_id').value;
				if (document.getElementById('_shaft_id_price_' + shaft_id))
					var shaft_upcharge = document.getElementById('_shaft_id_price_' + shaft_id).value;
				
				if (shaft_upcharge)
					if (document.getElementById('_shaft_price_container'))
						document.getElementById('_shaft_price_container').innerHTML = formatCurrency(shaft_upcharge*selected_club_count);
				
				total_amount += (shaft_upcharge*selected_club_count);
			} 
					
			 
			// update the visuals
			if (total_amount) {
				if (document.getElementById('_total_amount_container'))
					document.getElementById('_total_amount_container').innerHTML = formatCurrency(total_amount);
				if (document.getElementById('_total_amount'))
					document.getElementById('_total_amount').value = total_amount;
				if (document.getElementById('_count_container'))
					document.getElementById('_count_container').innerHTML = selected_club_count;
				if (document.getElementById('_count'))
					document.getElementById('_count').value = selected_club_count;
				grand_club_count += selected_club_count;
				grand_total_amount += total_amount;
			} else {
				document.getElementById('_total_amount_container').innerHTML = formatCurrency(0);
				document.getElementById('_count_container').innerHTML = 0;
				
			}


		// display the final calculated totals
		if (document.getElementById('total_count_container'))
			document.getElementById('total_count_container').innerHTML = grand_club_count;
		
		if (document.getElementById('total_amount_container'))
			document.getElementById('total_amount_container').innerHTML = formatCurrency(grand_total_amount);
		if (document.getElementById('grand_total_amount'))
			document.getElementById('grand_total_amount').value = grand_total_amount;
	 	
	}

	
	/*******************************************************************************
	 * Function: total_amount
	 * Purpose: tallys up the custom club fitting 
	 */
	
	function total_amount() {
		
		var total_amount = 0;
		var total_club_count = 0;
		var selected_club_count = 0;
		
		if (document.getElementById('total_count_container'))
			document.getElementById('total_count_container').innerHTML = '0';
		
		var va_club_types = new Array();
		if (document.getElementById("club_types"))
			var club_types = document.getElementById("club_types").value;
		if (club_types.indexOf(",")>-1) { 
			va_club_types = club_types.split(",");
		} else {
			va_club_types[0] = club_types;
		}
		
		var grand_club_count = 0;
		var grand_total_amount = 0;
		var per_order_services = [];
		var per_order_service_count = 0;
		var oktoadd = true;
		
		
		for (var i=0; i<va_club_types.length; i++) { // combo/hybrid, drivers, hybrids, irons, wedges, etc.
			
			va_club_types[i] = va_club_types[i].replace("/","");
			
			// some variables
			var ind_price = 0;
			var set_price = 0;
			var selected_club_count = 0;
			var total_amount = 0;
			var set = "";
			var club_head_count = 0;
			var ind_club_count = 0;
			var hybrid_count = 0;
			var iron_count = 0;
			
			// figure out the selected club count(s)
			if (document.getElementById(va_club_types[i]+'_club_head_index')) {
				var club_head_count = document.getElementById(va_club_types[i]+'_club_head_index').value;
				if (club_head_count>0) { 
					for (var c=0; c<club_head_count;c++) {
						if (document.getElementById(va_club_types[i]+'_club_head_id_'+c).checked) {
							if (document.getElementById(va_club_types[i]+'_club_head_ind_price_'+c)) { 
								ind_price += parseFloat(document.getElementById(va_club_types[i]+'_club_head_ind_price_'+c).value);
							}
							if (document.getElementById(va_club_types[i]+'_club_head_set_price_'+c)) {
								set_price += parseFloat(document.getElementById(va_club_types[i]+'_club_head_set_price_'+c).value);
							}
							if (document.getElementById(va_club_types[i]+'_club_head_description_'+c)) {
								set += document.getElementById(va_club_types[i]+'_club_head_description_'+c).value + ",";
							}
							selected_club_count++;
							
							// now to deal with the combo/hybrid selection
							if (document.getElementById(va_club_types[i]+'_club_head_hybrid_flag_'+c)) {
								if (document.getElementById(va_club_types[i]+'_club_head_hybrid_flag_'+c).value == "Y")
									hybrid_count++;
								else
									iron_count++;
							}
						}
					}
				} else { // for putters
					if (document.getElementById(va_club_types[i]+'_club_head_ind_price_'+club_head_count)) {
						set_price += parseFloat(document.getElementById(va_club_types[i]+'_club_head_ind_price_'+club_head_count).value);
						ind_club_count++;
					}
				}				
			}
			
			 // removes the trailing ','
			if (set) set = set.substring(0, set.length-1); 
			
			set_matched = false;
			
			// did you select all the clubs in the set?
			if (document.getElementById(va_club_types[i]+'_set_count')) {
				var club_set_count = document.getElementById(va_club_types[i]+'_set_count').value;
				if (club_set_count>0) { 
					for (var c=0; c<club_set_count;c++) {
						if (set) {
							if (set == document.getElementById(va_club_types[i]+'_'+c+'_set').value)
								set_matched = true;
						}
					}
				}
			}			
			
			// figure out the total amount
			if (selected_club_count == club_head_count || set_matched) {
				if (set_price > 0)
					total_amount += set_price;
				else 
					total_amount += ind_price;
			} else {
				total_amount += ind_price;
			}   
			selected_club_count += ind_club_count;
			var service_charges = 0;
			
			// add up the services charges per club
			if (document.getElementById(va_club_types[i]+'_services_count')) {
				service_count = document.getElementById(va_club_types[i]+'_services_count').value;
				if (service_count>0) { 
					
					for (var c=0; c<service_count;c++) {
						if (document.getElementById(va_club_types[i]+'_service_'+c).checked) {
							if (document.getElementById(va_club_types[i]+'_service_'+c+'_service_level').value == "Per Order") {
								for (var p=0; p<per_order_service_count;p++) {
									if (per_order_services[p] == document.getElementById(va_club_types[i]+'_service_'+c+'_id').value)
										oktoadd = false;
								}
								if (oktoadd) {
									if (document.getElementById(va_club_types[i]+'_service_'+c+'_price'))
										service_charges += parseFloat(document.getElementById(va_club_types[i]+'_service_'+c+'_price').value);
									if (document.getElementById(va_club_types[i]+'_service_'+c+'_id'))
										per_order_services[per_order_service_count++] = document.getElementById(va_club_types[i]+'_service_'+c+'_id').value;
								}
							} else {
								if (document.getElementById(va_club_types[i]+'_service_'+c+'_price'))
									service_charges += (parseFloat(document.getElementById(va_club_types[i]+'_service_'+c+'_price').value) * selected_club_count);
							}
						}
					}
				}
			}
			
			// update the visual charges
			if (service_charges > 0) {
				if (document.getElementById(va_club_types[i]+'_service_price_container'))
					document.getElementById(va_club_types[i]+'_service_price_container').innerHTML = formatCurrency(service_charges);
				if (document.getElementById(va_club_types[i]+'_service_price'))
					document.getElementById(va_club_types[i]+'_service_price').value = service_charges;
				total_amount += (service_charges);
			} else {
				if (document.getElementById(va_club_types[i]+'_service_price_container'))
					document.getElementById(va_club_types[i]+'_service_price_container').innerHTML = formatCurrency(0);
			}
			
			// figure out grip charges
			if (document.getElementById(va_club_types[i]+'_grip_id'))
				var grip_id = document.getElementById(va_club_types[i]+'_grip_id').value;
			if (document.getElementById(va_club_types[i]+'_grip_id_' + grip_id))
				var grip_upcharge = document.getElementById(va_club_types[i]+'_grip_id_' + grip_id).value;
			if (grip_upcharge) {
				if (document.getElementById(va_club_types[i]+'_grip_price_container'))
					document.getElementById(va_club_types[i]+'_grip_price_container').innerHTML = formatCurrency(grip_upcharge);
				total_amount += (grip_upcharge*selected_club_count);		
			}
			
			// now the shaft charges
			if (hybrid_count > 0) { // we could have an iron count too
				var shaft_id = 0;
				if (document.getElementById(va_club_types[i]+'_shaft_id'))	
					shaft_id = document.getElementById(va_club_types[i]+'_shaft_id').value;
				
				var iron_shaft_charge = 0;
				if (document.getElementById(va_club_types[i]+'_shaft_id_price_' + shaft_id))
					iron_shaft_charge = document.getElementById(va_club_types[i]+'_shaft_id_price_' + shaft_id).value;
				
				var combo_shaft_id = 0;
				if (document.getElementById(va_club_types[i]+'_shaft_id_combo'))	
					combo_shaft_id = document.getElementById(va_club_types[i]+'_shaft_id_combo').value;
				
				var combo_shaft_upcharge = 0;
				if (document.getElementById(va_club_types[i]+'_shaft_id_price_' + combo_shaft_id + '_combo'))
					combo_shaft_upcharge = document.getElementById(va_club_types[i]+'_shaft_id_price_' + combo_shaft_id + '_combo').value;
				
				if (combo_shaft_upcharge || iron_shaft_charge)
					if (document.getElementById(va_club_types[i]+'_shaft_price_container'))
						document.getElementById(va_club_types[i]+'_shaft_price_container').innerHTML = formatCurrency(parseFloat(combo_shaft_upcharge*hybrid_count) + parseFloat(iron_shaft_charge*iron_count));
				
				total_amount += ((combo_shaft_upcharge*hybrid_count) + (iron_shaft_charge*iron_count));
				selected_club_count = (hybrid_count + iron_count);
			} else {
				if (document.getElementById(va_club_types[i]+'_shaft_id'))	
					var shaft_id = document.getElementById(va_club_types[i]+'_shaft_id').value;
				if (document.getElementById(va_club_types[i]+'_shaft_id_price_' + shaft_id))
					var shaft_upcharge = document.getElementById(va_club_types[i]+'_shaft_id_price_' + shaft_id).value;
				
				if (shaft_upcharge)
					if (document.getElementById(va_club_types[i]+'_shaft_price_container'))
						document.getElementById(va_club_types[i]+'_shaft_price_container').innerHTML = formatCurrency(shaft_upcharge*selected_club_count);
				
				total_amount += (shaft_upcharge*selected_club_count);
			} 
					
			 
			// update the visuals
			if (total_amount) {
				if (document.getElementById(va_club_types[i]+'_total_amount_container'))
					document.getElementById(va_club_types[i]+'_total_amount_container').innerHTML = formatCurrency(total_amount);
				if (document.getElementById(va_club_types[i]+'_total_amount'))
					document.getElementById(va_club_types[i]+'_total_amount').value = total_amount;
				if (document.getElementById(va_club_types[i]+'_count_container'))
					document.getElementById(va_club_types[i]+'_count_container').innerHTML = selected_club_count;
				if (document.getElementById(va_club_types[i]+'_count'))
					document.getElementById(va_club_types[i]+'_count').value = selected_club_count;
				grand_club_count += selected_club_count;
				grand_total_amount += total_amount;
			} else {
				document.getElementById(va_club_types[i]+'_total_amount_container').innerHTML = formatCurrency(0);
				document.getElementById(va_club_types[i]+'_count_container').innerHTML = 0;
				
			}
		}

		// display the final calculated totals
		if (document.getElementById('total_count_container'))
			document.getElementById('total_count_container').innerHTML = grand_club_count;
		
		if (document.getElementById('total_amount_container'))
			document.getElementById('total_amount_container').innerHTML = formatCurrency(grand_total_amount);
		if (document.getElementById('grand_total_amount'))
			document.getElementById('grand_total_amount').value = grand_total_amount;
	 	
	}

	/*******************************************************************************
	 * Function: SmartFitSubmitClub
	 * Dual Purpose: sees if the customer checked the terms, if so, adds items to cart
	 * New version of SmartFitSubmit
	 */
	
	function SubmitCustClub(p_screen_name) {
		if (document.getElementById('customclubterms').checked == true) {
			var v_club_types = document.customize.club_types.value;
			var v_club_models = document.customize.club_models.value;
			
			var error_message = "";
			var error_message = validateCustomization(p_screen_name,v_club_types,v_club_models); 
		
			if (error_message.length != 0) {
				setValueToGoogleStepCustomized(error_message);
				alert(error_message);
				return false;
			}
			
			var v_session_id = document.customize.session_id.value;
			
			if (p_screen_name != '') {
				v_save_url = '/' + p_screen_name + '/product/savecustomize/';
			} else {
				v_save_url = '/product/savecustomize/';
			}
			
			var v_parameters = "&customize_parameters=";
			var formElements = document.customize.elements;
			for (i=0; i<formElements.length; i++) {
				v_parameters += "@@@@" + formElements[i].name + "=" + formElements[i].value;
				if (formElements[i].checked)
					v_parameters += "@@@@" + formElements[i].name + "_checked=true";
			}
			
			
			
			//check if they checked personalize checkbox
			var checkPersonalization = true;
			if($(":checkbox[value=PERSONALIZATION]").is(':checked')){
				
				$(".required").each(function(){
					
					if( $(this).is('input:text') ) {
						if(this.value == ''){
						  	checkPersonalization = false;
						  	return false;
						}
					}
					
					if( $(this).prop('type') == 'select-one'){
						if( $(this).val() == '' ){
							checkPersonalization = false;
					  		return false;
						}
					}
				});

			}
			
			if(!checkPersonalization){
				alert("Please update all options under the Additional Services section");
				return checkPersonalization;
			}
			
			
			
			//alert('session_id=' + v_session_id +v_parameters);
			//return false;
			
			// ajax call to save the customize selections
			$.ajax( {
				type : 'post',
				url : v_save_url,
				data : 'session_id=' + v_session_id +v_parameters,
				cache : false,
				async : true,
				success : function(v_html) {

					// url
					if (p_screen_name != '') {
						v_url = '/' + p_screen_name + '/product/addtocartclub/';
					} else {
						v_url = '/product/addtocartclub/';
					}
					
					// out of stockurl
					// todo 
					if (p_screen_name != '') {
						v_oos_url = '/' + p_screen_name + '/product/outofstock/';
					} else {
						v_oos_url = '/product/outofstock/';
					}
					
					// session id from browser
					var v_session_id = document.customize.session_id.value;
					// todo uncomment and fix the next function
					//setValueToGoogleAddToCart();
					// add the club customization to the cart
					$.ajax( {
						type : 'post',
						url : v_url,
						data : 'session_id=' + v_session_id,
						cache : false,
						async : true,
					    success: function(v_html) {
							if (v_html=="") {
								// todo remove it later 
								window.location.href = '/checkout/cart/';
								//alert("BY Luis Uncomment the line above");
							} else {
								$.ajax( {
									type : 'post',
									url : v_oos_url,
									data : 'session_id=' + v_session_id + '&return_status=' + v_html,
									cache : false,
									async : true,
									success: function(v_html) {
										$("#custom_club").html(v_html);
										scrollToTop();
									}
								});
							}
						}
					});
			

					
				}
			});
			
		} else {
			alert('Please Check the "Terms and Conditions" Check Box');
			setValueToGoogleAddToCart('Please Check the "Terms and Conditions" Check Box');
			return false;
		}
	}

	/*******************************************************************************
	 * Function: SmartFitSubmit
	 * Dual Purpose: sees if the customer checked the terms, if so, adds items to cart
	 */
	
	function SmartFitSubmit(p_screen_name) {
		if (document.getElementById('terms').checked == true) {
			
			// url
			if (p_screen_name != '') {
				v_url = '/' + p_screen_name + '/customclub/addtocart/';
			} else {
				v_url = '/customclub/addtocart/';
			}
			
			// out of stockurl
			if (p_screen_name != '') {
				v_oos_url = '/' + p_screen_name + '/customclub/outofstock/';
			} else {
				v_oos_url = '/customclub/outofstock/';
			}
			
			// session id from browser
			var v_session_id = document.review.session_id.value;
			setValueToGoogleAddToCart();
			// add the club customization to the cart
			$.ajax( {
				type : 'post',
				url : v_url,
				data : 'session_id=' + v_session_id,
				cache : false,
				async : true,
			    success: function(v_html) {
					if (v_html=="") {
						window.location.href = '/checkout/cart/';
					} else {
						$.ajax( {
							type : 'post',
							url : v_oos_url,
							data : 'session_id=' + v_session_id + '&return_status=' + v_html,
							cache : false,
							async : true,
							success: function(v_html) {
								$("#custom_club").html(v_html);
								scrollToTop();
							}
						});
					}
				}
			});
	
		} else {
			alert('Please Check the "Terms and Conditions" Check Box');
			setValueToGoogleAddToCart('Please Check the "Terms and Conditions" Check Box');
			return false;
		}
	}

	/*******************************************************************************
	 * Function disableCusomizations
	 * Purpose: Customizations should be disabled if club replacement; club model dependant
	 */
	
	function disableCusomizations(p_club_type) {
		if (document.getElementById(p_club_type + '_serial_number').value != '') {
			document.getElementById(p_club_type + '_shaft_id').disabled=true;
			document.getElementById(p_club_type + '_shaft_flex').disabled=true;
			document.getElementById(p_club_type + '_club_length').disabled=true;
			document.getElementById(p_club_type + '_lie_angle').disabled=true;
			document.getElementById(p_club_type + '_grip_id').disabled=true;
			document.getElementById(p_club_type + '_grip_size').disabled=true;
		} else {
			document.getElementById(p_club_type + '_shaft_id').disabled=false;
			document.getElementById(p_club_type + '_shaft_flex').disabled=false;
			document.getElementById(p_club_type + '_club_length').disabled=false;
			document.getElementById(p_club_type + '_lie_angle').disabled=false;
			document.getElementById(p_club_type + '_grip_id').disabled=false;
			document.getElementById(p_club_type + '_grip_size').disabled=false;
		}
	}
	