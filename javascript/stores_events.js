function enablestate(){var a=$("#city").val().length;if(a>0){$("#state").removeAttr("disabled")}else{$("#state").attr("disabled","disabled")}}$(document).ready(function(){if($("#city").val()=="City"){$("#state").attr("disabled","disabled")}if($("#zip").val()!="Zip Code"){$("#zip").removeClass("help-text")}if($("#city").val()!="City"){$("#city").removeClass("help-text")}});function checkZipCode(a,b){switch(a){case"US":characterReg=/^([0-9]{5})(?:[-\s]*([0-9]{4}))?$/;break;case"CA":characterReg=/^([A-Z][0-9][A-Z])\s*([0-9][A-Z][0-9])$/;break}var c=characterReg.test(b);if(c){return true}else{return false}}function showErrorMsg(b,a,c){$("#"+c).show();if(a==="all"){$("#"+c).html(b)}else{if(a==="general"){$("#"+c).html(b)}else{$("#"+c).html(b)}}if(a==="zip"){$("li#search-zip").addClass("error")}else{if(a==="state"){$("li#search-state").addClass("error");$("li#search-city").addClass("error")}else{if(a==="all"){$("li#search-zip").addClass("error");$("li#search-state").addClass("error");$("li#search-city").addClass("error")}}}errorFromClassUsed=true;errorFormFieldUsed=a}var fillTheMissingInformation="Please fill Zip Code or City/State to search";var cityWithoutStateError="Please choose a State with the City";var commonError="Please contact the webmaster about this error";var wrngZipCodeFormat="Please provide a correct Zip Code";function searchStores(){var f=$("#city").val().replace(/\s{2,}/g,"");var e=$("#state").val();var d=$("#select-store-events").val().substr(0,5);var a="";var c="";if(f=="City"){f=""}if(e=="State"){e=""}if(d==""){d=$("#zip").val().replace(/\s{2,}/g,"");d=d.substr(0,5);if(d==""||d=="Zip C"){d=""}}else{a=$("#select-store-events option:selected").text()}var b=/^\d{5}$/;if(d==""&&f==""&&e==""){showErrorMsg(fillTheMissingInformation,"all","form-error");return false}else{if(d!=""){if(!checkZipCode("US",d)){showErrorMsg(wrngZipCodeFormat,"zip","form-error");return false}}else{if(d==""&&f!=""){if(!checkZipCode("US",f.substr(0,5))){if(e==""&&f!=""){showErrorMsg(cityWithoutStateError,"state","form-error");return false}}else{d=f.substr(0,5);$("#zip").val(d);$("#city").val("City");$("#city").attr("class","help-text");$("#state").attr("class","help-text");f=""}}else{showErrorMsg(commonError,"all","form-error")}}}$("#form-error").html("");$("#form-error").hide();$("#search-form").hide();$("#search-alerternative").hide();update();$("#preview").show();if(a==""){if(f!=""&&f!="City"){a+=f+",";c="city"}if(e!=""){a+=e;c="state"}if(d!=""&&e!=""){a+=","+d;c="zip"}else{if(d!=""&&e==""){a+=d;c="zip"}}}$.post("/events/refreshEvents",{zip:d,city:f,state:e},function(g){$("#preview").hide();$("#events-loading").hide();if(g=="Error: No Stores"){$("#search-form").show();$("#search-aleternative").show();showErrorMsg('Sorry we do not recognize "'+a+'". Please try your search again.',c,"form-error")}else{$("#search_events_list").html(g);$("#search_term").html(a);$("#search_term1").html("The Nearest Store(s): "+a);$("#search-success").show();$("#search-form").hide();$("#search-aleternative").hide();$(".search-new").show()}if(f!=""){$("#city").val(f)}if(e!=""){$("#state").val(e)}if(d!=""){$("#zip").val(d)}});return false}function show_new_search(){$("#search-success").hide();$("#search-error").hide();update();$("#preview").show();$.post("/events/refreshEvents",{status:"new search"},function(a){$("#preview").hide();$("#search_events_list").html(a);$("#search-alternative").show();$("#city").val("City");$("#zip").val("Zip Code");$("#state").val("");$("#state").attr("disabled","disabled")});$("#city").attr("class","help-text");$("#state").attr("class","help-text");$("#state").attr("disabled","disabled");return false}function refresh_events(){update();var d=document.getElementsByName("filters[]");var c="";for(i=0;i<d.length;i++){if(!d[i].checked){c+=d[i].value+","}}c=c.substring(0,c.length-1);var f=$("#city").val();var e=$("#state").val();var b=$("#zip").val();if(f=="City"){f=""}if(e=="State"){e=""}if(b=="Zip Code"){b="";if(b==""){b=$("#select-store-events").val();b=b.substr(0,5)}}var a="";if(f!=""){a+=f+","}if(e!=""){a+=e}if(b!=""&&e!=""){a+=","+b}else{if(b!=""&&e==""){a+=b}}$.post("/events/refreshEventsList",{zip:b,city:f,state:e,event_type_ids:c},function(g){$("#preview").hide();$("#all-events").html(g);if(f!=""&&f!="City"){$("#city").val(f)}if(e!=""&&e!="State"){$("#state").val(e)}if(b!=""&&b!="Zip Code"){$("#zip").val(b)}})}function getDirections(a){$.post("/events/getdirections",{event_id:a},function(b){window.open(b,"_newtab")})}function save_email(){var b=$("#email").val();var c=/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i;if(!c.test(b)){alert(golfsmith.email.errorMessage);return false}var a=/[0-9]+/;$.post("/events/saveEmail",{email_address:b},function(e){var d=e.substr(e.length-7);if(d=="SUCCESS"){$("#email-error").hide();$("#email-form").hide();$("#successful-signup").show();$("#email").val("Email Address");$("#e-zip").val("Zip Code")}else{alert("Email could not be saved")}});return false}function jumpToDiv(a,b){animation_page=(typeof b=="undefined")?"":b;var c=0;if(animation_page=="show_events"){c=1500}else{c=1000}$("html, body").animate({scrollTop:$("#"+a).offset().top},c)}function jumpTo(a){$("html, body").animate({scrollTop:$("#store-"+a).offset().top-199},1000)}function backTo(){$("html, body").animate({scrollTop:$("#header").offset().top-199},1000)}function clearInputValue(b,a){$("li#search-zip").removeClass("error");$("li#search-state").removeClass("error");$("li#search-city").removeClass("error");$("li#email-email").removeClass("error");$("li#email-zip").removeClass("error");$("#email-error").hide();var c=$("#"+b).val();$("#"+b).removeClass("help-text");if(c.replace(/\s/g,"")==""||c.toLowerCase()==a.toLowerCase()){$("#"+b).val("")}}function setInputValue(b,a){var c=$("#"+b).val();if(c==""){$("#"+b).addClass("help-text")}if(c.replace(/\s/g,"")==""||c.toLowerCase()==a.toLowerCase()){$("#"+b).val(a)}}function experiancall(a){if(validate_email()){v_url="/events/experiancall";var b=$("#email_confirm").val();$.ajax({type:"post",url:v_url,data:"email="+a+"&email_confirm="+b,cache:false,async:false,beforesend:function(){},success:function(e){$("#email").val("");var d="http://ats.eccmp.com/ats/url.aspx?cr=538&wu=4&eml="+a;var c="width="+930+",height="+600;window.open(d,"_blank","screenX=1,screenY=1,left=1,top=1,scrollbars=yes, resizable=yes,"+c)}});return false}}function validate_email(){var a=$("#email").val();var b=/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i;if(!b.test(a)){alert(golfsmith.email.errorMessage);return false}return true};