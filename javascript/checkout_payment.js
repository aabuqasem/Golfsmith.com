$(document).ready(function(){$("#country_ship_select").change(function(){modifyAddressFields("ship");hide_show_ca_select()})});function setStateAndCountry(b,a){$("#state_ship_select").val(b);$("#state_ship_ca_select").val(b);$("#country_ship_select").val(a);if(a=="CA"){$("#state_ship_select").hide();$("#state_ship_input").hide();$("#state_ship_ca_select").show();$("#state_ship_label").html('STATE/PROVINCE <span id="state_ship_span" class="save">* </span> <span id="state_ship_error" class="validate_message"/></label>');$("#run_fedex_validation").val("Y")}else{if(a=="US"){$("#state_ship_ca_select").hide();$("#state_ship_input").hide();$("#state_ship_select").show();$("#state_ship_label").html('STATE <span id="state_ship_span" class="save">* </span> <span id="state_ship_error" class="validate_message"/></label>');$("#run_fedex_validation").val("Y")}else{$("#state_ship_ca_select").hide();$("#run_fedex_validation").val("N");modifyAddressFields()}}}function hide_show_ca_select(){if(document.getElementById("country_ship_select").value=="CA"){$("#state_ship_select").hide();$("#state_ship_input").hide();$("#state_ship_ca_select").show();$("#state_ship_label").html('STATE/PROVINCE <span id="state_ship_span" class="save">* </span> <span id="state_ship_error" class="validate_message"/></label>');$("#run_fedex_validation").val("Y")}else{if(document.getElementById("country_ship_select").value=="US"){$("#state_ship_select").show();$("#state_ship_ca_select").hide();$("#state_ship_input").hide();$("#state_ship_label").html('STATE <span id="state_ship_span" class="save">* </span> <span id="state_ship_error" class="validate_message"/></label>');$("#run_fedex_validation").val("Y")}else{$("#state_ship_select").hide();$("#state_ship_ca_select").hide();$("#state_ship_input").show();$("#run_fedex_validation").val("N")}}}function modifyAddressFields(){v_country=$("#country_ship_select").val();if(v_country!="US"&&v_country!="CA"){$("#shipping_state").removeClass("country_us");$("#pcode_ship_label").html("POSTAL CODE");$("#state_ship_label").html("STATE/REGION/PROVINCE");$("#phone_ship_label").html('PHONE <span class="save">*</span> <span id="phone_bill_error" class="validate_message"></span>');$("#shipping_postalCode").removeClass("validate");$("#shipping_state").removeClass("validate");$("#run_fedex_validation").val("N")}else{$("#shipping_state").addClass("country_us");$("#pcode_ship_label").html('ZIP CODE <span id="zip_bill_span" class="save">*</span><span id="zip_bill_error" class="validate_message"/>');$("#state_ship_label").html('STATE <span id="state_bill_span" class="save">*</span> <span id="state_bill_error" class="validate_message"/>');$("#phone_ship_label").html('PHONE <span style="font-weight: normal;">(10 digits) </span> <span class="save">*</span> <span id="phone_bill_error" class="validate_message"/>');$("#run_fedex_validation").val("Y")}};