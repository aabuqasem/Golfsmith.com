var geocoder,map,marker,infowindow,directionsDisplay;var directionsService;var errorFromClassUsed=false;var errorFormFieldUsed;var boundsPoints,parsedJsonPoints,latLongStore;var searchValue,visitorAddress;var gotRoute=false;var opts={lines:7,length:2,width:4,radius:4,rotate:0,color:"#666",speed:1,trail:52,shadow:false,hwaccel:false,className:"spinner",zIndex:2000000000,top:"auto",left:"auto"};notFoundAddressErrMsg="Sorry, your location could not be found. Please check your address and try again. For best results, please enter your full street address, city, state and zip code.";var markers=[];function resetSearchFields(){$("#driving-directions").hide();$("#search-criteria").html("");$("#search-criteria-limit").html("");$("#search-criteria-notfound").html("");$("#store-map h2").html("States with Stores");$("#map p:first").html("Click a highlighted state below to view all Golfsmith stores in that state.");$("#directions").hide();$("#search-error").hide();$("#view-directions").hide();$("#directions-steps").html("");$("#search-results").hide();$("#city").addClass("help-text");$("#state").addClass("help-text");$("#zip").addClass("help-text");$("#state").attr("disabled","disabled");$("#form-error").html("");$("#form-error").hide();$("#results-filters input:checkbox").attr("checked","checked");$.post("/stores/clearSession",function(a){$(".search-new").hide();$("#search-form").show();$("#zip").val("Zip Code");$("#city").val("City");$("#state").val("")});$("#map-google").hide();$("#map-states").show();for(m=0;m<markers.length;m++){markers[m].setVisible(false)}}if(typeof String.prototype.trim!=="function"){String.prototype.trim=function(){var b=/^\s+/;var a=/\s+$/;return this.toString().replace(b,"").replace(a,"")}}function clearInputValue(b,a){$("li#search-zip").removeClass("error");$("li#search-state").removeClass("error");$("li#search-city").removeClass("error");$("li#email-email").removeClass("error");$("li#email-zip").removeClass("error");$("#email-error").hide();$("#"+b).removeClass("help-text");var c=$("#"+b).val();if(c.replace(/\s/g,"")==""||c.toLowerCase()==a.toLowerCase()){$("#"+b).val("")}var c=$("#"+b).val();if(c==""||c!=a){$("#"+b).removeClass("help-text")}if(c==a){$("#"+b).addClass("help-text")}}function setInputValue(b,a){var c=$("#"+b).val();if(c.replace(" ","")==""||c.toLowerCase()==a.toLowerCase()){$("#"+b).val(a);$("#"+b).addClass("help-text")}var c=$("#"+b).val();if(c!=a){$("#"+b).removeClass("help-text")}if(c==a){$("#"+b).addClass("help-text")}}function jumpToDiv(a,b){animation_page=(typeof b=="undefined")?"":b;var d=20;var c=0;c=1000;if(animation_page=="events-email"||a=="store-hours"){d=0}$("html, body").animate({scrollTop:$("#"+a).offset().top-d},c)}function showEvents(a){animation_page=(typeof a=="undefined")?"":a;if(animation_page==""){jumpToDiv("events")}if(animation_page=="show_events"){jumpToDiv("events",animation_page)}if(animation_page=="store_hours"){jumpToDiv("store-hours",animation_page)}}$(document).ready(function(){var b=new String(window.location);var a=b.substring((b.length)-7,b.length);if(a=="@events"){showEvents("show_events")}if(a=="e-hours"){showEvents("store_hours")}if($("#city").val()=="City"){$("#state").attr("disabled","disabled")}});function enablestate(){var a=$("#city").val().length;if(a>0){$("#state").removeAttr("disabled")}else{$("#state").attr("disabled","disabled")}}function clearInputTextValue(b,a){var c=$("#"+b);c.focus(function(){if($(this).val()==a){$(this).val("")}}).blur(function(){if($(this).val().trim()==""||$(this).val().toLowerCase()==a.toLowerCase()){$(this).val(a)}})}function checkZipCode(a,c){switch(a){case"US":characterReg=/^([0-9]{5})(?:[-\s]*([0-9]{4}))?$/;break;case"CA":characterReg=/^([A-Z][0-9][A-Z])\s*([0-9][A-Z][0-9])$/;break}var b=$("#"+c).val().trim().substr(0,5);var d=characterReg.test(b);if(d){return true}else{return false}}function searchStores(){var h,g,b;var d=false;var e=false;var l="Please use zip code or city/state search";var a="Please choose a state with the city";var f="Please contact the webmaster about this error";var c="Please provide a correct zipcode";h=$("#zip").val();g=$("#city").val();b=$("#state").val();$("#search-form li").removeClass("error");if(errorFromClassUsed){$("#"+errorFormFieldUsed).toggleClass("error")}if(h==="Zip Code"){h=""}if(g==="City"){g=""}if(h==""&&g==""&&b==""){showErrorMsg(l,"all","form-error");return}else{if(h!=""){if(checkZipCode("US","zip")){getStores("zipcode",h)}else{showErrorMsg(c,"zip","form-error");return}}else{if(g!=""&&b!=""){getStores("citystate",g+","+b);return}else{if(b!=""){getStores("state",b);return}else{if(h==""&&g!=""){if(!checkZipCode("US","city")){if(b==""&&g!=""){showErrorMsg(a,"state","form-error");return false}}else{h=g.substr(0,5);$("#zip").val(h);$("#city").val("City");$("#city").attr("class","help-text");$("#state").attr("class","help-text");g="";getStores("zipcode",h)}}else{showErrorMsg(f,"all","form-error")}}}}}$("#form-error").html("");$("#form-error").hide()}function showErrorMsg(b,a,c){$("#"+c).show();if(a==="all"){$("#"+c).html(b)}else{if(a==="general"){$("#"+c).html(b)}else{$("#"+c).html(b)}}if(a==="zip"){$("li#search-zip").toggleClass("error")}else{if(a==="state"){$("li#search-state").toggleClass("error")}else{if(a==="start-address"){$("#start-address").toggleClass("error")}else{if(a==="all"){$("li#search-zip").addClass("error");$("li#search-city").addClass("error");$("li#search-state").addClass("error")}}}}errorFromClassUsed=true;errorFormFieldUsed=a}function getStores(b,a){$("#search-criteria").html(a);$("h3 #search-criteria-limit").html(a);$("#search-criteria-notfound").html(a);$("#store-map h2").html("Stores Near: "+a);$("#map p:first").html("Click a Golfsmith store to get directions and view some details");var c;searchValue=a;if(b==="state"){c="type=state&value="+a}else{if(b==="citystate"){c="type=citystate&value="+a}else{if(b==="zipcode"){c="&type=zipcode&value="+a}}}getStoresResultsAjax(c)}function showDirections(){$("#directions").slideDown("slow",function(){});$("#enter-directions").show();$("#address-entered").remove();getSessionAddress();$("#start-address").show();$("#submit-address").show();$("#driving-directions").hide();$("#directions-steps").html("");jumpToDiv("store-map");var a={};$("#enter-directions").effect("highlight",a,5000,callbackFn);$("#store-map h2:first").html("Directions to the "+$("#to-store-name").text()+" Store");$("#map p:first").html('Enter your starting address below and click "Get Directions".');return false}function showStoreDirections(){$("#directions").slideDown("slow",function(){});$("#enter-directions").show();jumpToDiv("store-map");return false}function callbackFn(){setTimeout(function(){},1000)}function getStoresResultsAjax(a){$.ajax({url:"/stores/ajax/",data:a,dataType:"json",type:"post",success:function(b){processResults(b)},error:function(b){showErrorMsg("Please try again there are slight problem with the server","general","form-error")}})}function processResults(c){if(c.status==="ok"){$("#results-counter").html(c.counter);var b=$("#results-filters p").html();if(c.counter==1){b=b.replace("Stores Near","Store Near");$("#results-filters p").html(b.replace("/Stores Nears/g","Store Near"))}else{b=b.replace("Store Near","Stores Near");$("#results-filters p").html(b.replace("/Store Near/","Stores Near"))}if(c.type=="state"){$("#search-criteria").html(c.statename);$("#search-criteria-limit").html(c.statename);$("#search-criteria-notfound").html(c.statename);$("#store-map h2").html("Stores Near: "+c.statename)}$(".search-new").show();loadResultsColumn(c.HTML);$("#search-new").show();var d=document.getElementById("map-google");var e=new Spinner(opts).spin(d);var a=[];$.each(c.results,function(f,g){a.push({StoreID:$.trim(g.storeID),StoreLat:$.trim(g.StoreLat),StoreLong:$.trim(g.StoreLong),Name:$.trim(g.Name),address:$.trim(g.address),contextualinfo:$.trim(g.contextualinfo),feature:$.trim(g.feature)})});parsedJsonPoints=a;loadmap("map-google",a);$("#map-states").hide();$("#map-google").show()}else{if(c.status==="noStoresInLimitedRadius"){$("#map-states").show();$("#map-google").hide();$(".search-new").show();$("#search-form").hide();$("#search-error").show();$("#other-stores").show();$("div#other-stores ul").html("");$("div#other-stores ul").append(c.HTML)}else{if(c.status==="noStoresFoundByZipCode"){$("#map-states").show();$("#map-google").hide();$("#store-search .search-new").hide();$("#search-form").removeAttr("style");$("#other-stores").hide();$("#search-zip").addClass("error");$("#form-error").text('Sorry we do not recognize "'+searchValue+'". Please try your search again.');$("#form-error").show();$("div#other-stores ul").html("");$("div#other-stores ul").append(c.HTML);$("#search-form").show()}else{if(c.status==="noStoresInState"){$("#map-states").show();$("#map-google").hide();$("#search-form").show();$(".search-new").hide();$("#form-error").text('Sorry we do not recognize "'+searchValue+'". Please try your search again.');$("#search-error").show();$("#other-stores").hide();$("#search-error h3").html(c.HTML);$("div#other-stores ul").html("")}else{if(c.status==="noStoresInCityState"){$("#map-states").show();$("#map-google").hide();$("#search-form").show();$(".search-new").hide();$("#search-city").addClass("error");$("#search-state").addClass("error");$("#form-error").text('Sorry we do not recognize "'+searchValue+'". Please try your search again.');$("div#other-stores ul").html("");$("div#other-stores ul").append(c.HTML);$("#form-error").show()}}}}}}function loadResultsColumn(a){$("div#other-stores ul").html("");$("#search-form").hide();$("#search-error").hide();$("#search-results").show();$("div#results-stores ol").html("");$("div#results-stores ol").append(a)}function loadmap(f,d){var b={mapTypeId:google.maps.MapTypeId.ROADMAP};map=new google.maps.Map(document.getElementById(f),b);directionsService=new google.maps.DirectionsService();directionsDisplay=new google.maps.DirectionsRenderer();geocoder=new google.maps.Geocoder();$(function(){$("#start-address").autocomplete({source:function(h,g){geocoder.geocode({address:h.term+",USA"},function(n,l){g($.map(n,function(o){return{label:o.formatted_address,value:o.formatted_address,latitude:o.geometry.location.lat(),longitude:o.geometry.location.lng()}}))})}})});var c=new google.maps.LatLngBounds();markers=[];var a;$.each(d,function(g,h){var l=h;a=new google.maps.LatLng(l.StoreLat,l.StoreLong);marker=new google.maps.Marker({position:a,map:map,title:l.Name,icon:"/_site_images/_retail_pages/map-icon_32.png",animation:google.maps.Animation.DROP});markers.push(marker);(function(n){google.maps.event.addListener(n,"click",function(){$("#to-store-address").html(l.address);$("#to-store-name").html(l.Name);$("div#results-stores li").removeClass("selected");$("#store-"+l.StoreID).addClass("selected");if(!infowindow){infowindow=new google.maps.InfoWindow()}infowindow.setContent(l.contextualinfo);infowindow.open(map,n)})})(marker);c.extend(a)});boundsPoints=c;parsedJsonPoints=d;map.fitBounds(c);var e=google.maps.event.addListener(map,"bounds_changed",function(g){if(this.getZoom()>15){this.setZoom(15)}google.maps.event.removeListener(e)});if(d.length==1){map.setZoom(16)}}function highLightedStoreRow(a,c){var a="#"+a;var b=$(a+" h3").text();var d=$(a+" p:first").text();$("#to-store-address").html(d);$("#to-store-name").html(b);$("#results-stores ol>li").removeClass("selected");$(a).addClass("selected");if(!gotRoute){launchInfoWindow(c)}else{getRoute()}}function getSelectedStoreInfo(a,c){var a="#"+a;var b=$(a+" h3").text();var d=$(a+" p:first").text();$("#to-store-address").html(d);$("#to-store-name").html(b);$("#results-stores ol>li").removeClass("selected");$(a).toggleClass("selected");showDirections();launchInfoWindow(c)}function launchInfoWindow(a){markers[a].setMap(map);map.setCenter(markers[a].getPosition());google.maps.event.trigger(markers[a],"click")}function restedStartAddress(){if($("#start-address").val()==""||$("#start-address").val()=="Enter Your Address"){$("#start-address").css("color","#999999")}}function focusStartAddress(){if($("#start-address").val()!=""||$("#start-address").val()!="Enter Your Address"){$("#start-address").css("color","#000000")}}function resetEnteredAddressStorePage(){$("#address-entered").remove();loadmap("map-google",parsedJsonPoints);$("#start-address").show();$("#submit-address").show();$("#driving-directions").hide();$("#view-directions").hide();return false}function resetEnteredAddress(){var b=$(location).attr("href").length;var a=$(location).attr("href").lastIndexOf("stores");if(b===(a+7)){}else{if(b>(a+7)){}}$("#address-entered").remove();$("#start-address").show();$("#start-address").css("color","#000");$("#submit-address").show();$("#driving-directions").hide();$("#view-directions").hide();return false}function getRoute(){var a,c,g,l;$("#address-entered").remove();$("#start-address").show();$("#address_errors").hide();var b=$("#start-address").val();if(typeof(pageType)!=="undefined"&&visitorAddress!=""){}var e=$("#to-store-address").text();var d=google.maps.DirectionsTravelMode.DRIVING;var h=false;var f={origin:b,destination:e,travelMode:d,avoidHighways:h};directionsService.route(f,function(n,o){if(o==google.maps.DirectionsStatus.OK){gotRoute=true;$("#submit-address").hide();submitSessionAddress(b);$("#start-address").hide();$('label[for="start-address"] ').after('<p id="address-entered"><strong>'+$("#start-address").val()+'</strong><a id="change-address" title="Change Address" onCLick="resetEnteredAddress();">Change</a></p>');if(infowindow){infowindow.close()}for(m=0;m<markers.length;m++){markers[m].setVisible(false)}directionsDisplay.setMap(map);directionsDisplay.setDirections(n);dirRoute=n.routes[0].legs[0];$("#total-distance-time strong:first").html(dirRoute.distance.text);$("#total-distance-time strong:last").html(dirRoute.duration.text);$("#uStartAddress").html(dirRoute.start_address);$("#uEndAddress").html(dirRoute.end_address);$("p#google-copyright").html(n.routes[0].copyrights);$("#driving-directions").show();$("#directions-steps").html("");for(var q=0;q<dirRoute.steps.length;q++){var p=dirRoute.steps[q].instructions.replace(/<div.*?>/gi,'<br /><span class="step-bearing">');var p=p.replace(/<\/div>/gi,"</span>");if(p&&p.length>0){dirRoute.steps[q].instructions=p}a='<li><p><span class="directions-text">'+dirRoute.steps[q].instructions+'</span><span class="distance">'+dirRoute.steps[q].distance.text+"</span></p></li>";$("#directions-steps").append(a)}$("#view-directions").show()}else{if(o=google.maps.DirectionsStatus.NOT_FOUND){showErrorMsg(notFoundAddressErrMsg,"start-address","address_errors")}else{showErrorMsg(o,"all","address_errors")}}});return false}function visitGoogleMaps(){var c=$("#start-address").val().replace(/\s/gi,"+");var a=$("#to-store-address").text().replace(/\s/gi,"+");var b="https://maps.google.com/maps?saddr="+c+"&daddr="+a+"&hl=en";window.open(b);return false}function printGoogleMaps(){var c=$("#start-address").val().replace(/\s/gi,"+");var a=$("#to-store-address").text().replace(/\s/gi,"+");var b="https://maps.google.com/maps?saddr="+c+"&daddr="+a+"&pw=2";window.open(b);return false}function filterResults(){var d=new Array();var g=new Array();var c=$("#results-filters :checked").length;var f=new Array();var h=new Array();var l=new Array();$("#results-filters input").each(function(){var n=$(this).attr("id");var o=$(this).val();var p=new Array(o,n);if($(this).is(":checked")==false){g.push(p)}else{d.push(p)}});if(c==0){$("#results-stores ol>li").slideUp("normal",function(){});return}$("#results-stores ol>li").each(function(){var p=$(this).attr("id");var o=$(this).attr("id").replace("store-","");var n=new Array(p,false);h.push(n);$("#"+p+" ul>li>a").each(function(){var q=$(this).attr("id").replace("feature_","");var r=new Array(q,o);f.push(r)})});var a=0;var l;for(j=0;j<d.length;j++){for(i=0;i<f.length;i++){var b=f[i];if(b[0]==d[j][0]){l.push(parseInt(b[1]))}}}for(i=0;i<h.length;i++){var b=h[i][0];var e=parseInt(b.replace("store-",""));if($.inArray(e,l)>-1){h[i][1]=true}}for(k=0;k<h.length;k++){if(h[k][1]){$("#results-stores ol>li#"+h[k]).slideDown("normal",function(){})}else{$("#results-stores ol>li#"+h[k]).slideUp("normal",function(){})}}}function getCoordinates(a){if(!geocoder){geocoder=new google.maps.Geocoder()}var b={address:a};geocoder.geocode(b,function(d,c){if(c==google.maps.GeocoderStatus.OK){map.setCenter(d[0].geometry.location);if(!marker){marker=new google.maps.Marker({map:map})}marker.setPosition(d[0].geometry.location);if(!infowindow){infowindow=new google.maps.InfoWindow()}var e="<strong>"+d[0].formatted_address+"</strong><br />";e+="Lat: "+d[0].geometry.location.lat()+"<br />";e+="Lng: "+d[0].geometry.location.lng();infowindow.setContent(e);infowindow.open(map,marker)}})}function prepareStorePage(){$("#submit-address").click(function(){getRoute()});clearInputTextValue("start-address","Enter Your Address");getSessionAddress();directionsService=new google.maps.DirectionsService();directionsDisplay=new google.maps.DirectionsRenderer();geocoder=new google.maps.Geocoder();$(function(){$("#start-address").autocomplete({source:function(b,a){geocoder.geocode({address:b.term+",USA"},function(d,c){a($.map(d,function(e){return{label:e.formatted_address,value:e.formatted_address,latitude:e.geometry.location.lat(),longitude:e.geometry.location.lng()}}))})}})});if($("#start-address").val()!="Enter Your Address"){getRoute()}else{}}function emailGoogleMaps(){v_url="/stores/sendemailcontent";$.ajax({type:"post",url:v_url,cache:false,async:true,beforesend:function(){},success:function(a){$("#send-email-google").html(a);$("#send-email-google").dialog({autoOpen:false,closeText:"Close",modal:true,resizable:false,width:500});$("#send-email-google").dialog("open");$(".ui-widget-overlay").click(function(){modalAddEmailClose()});$(".ui-dialog-titlebar-close").click(function(){modalAddEmailClose()})}});return false}function modalAddEmailClose(){$("#send-email-google").dialog("close")}function save_email(){var b=$("#email").val();var d=$("#e-zip").val();var c=/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i;if(!c.test(b)){$("#email-error").html("Invalid email address");$("#email-email").addClass("error");$("#email-error").show();$("#email").blur();return false}var a=/[0-9]+/;if(!a.test(d)){$("#email-error").html("Invalid Postal Code");$("#email-zip").addClass("error");$("#email-error").show();$("#e-zip").blur();return false}$.post("/events/saveEmail",{email_address:b,postal_code:d},function(f){var e=f.substr(f.length-7);if(e=="SUCCESS"){$("#email-error").hide();$("#email-form").hide();$("#successful-signup").show();$("#email").val("Email Address");$("#e-zip").val("Zip Code")}else{alert("Email could not be saved")}});return false}function sendDirsEmail(){var c=$("#to-emailaddress").val();var d=/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i;if(!d.test(c)){$("#email-form-error").show();showErrorMsg("Invalid email address","general","email-form-error");$("#to-emailaddress").focus();return false}else{$("#google-email-form").hide();var e=$("#start-address").val().replace(/\s/gi,"+");var a=$("#to-store-address").text().replace(/\s/gi,"+");var b=$("#to-store-name").text();dataValue="email="+c+"&storeName="+b+"&start="+e+"&end="+a;$.ajax({url:"/stores/emaildirs/",data:dataValue,dataType:"json",type:"post",success:function(f){$("#to-emailaddress").attr("disabled","disabled");$("#email-form-error").hide();$("#email-confirm").show()},error:function(f){showErrorMsg("Please try again there are slight problem with the server","general","email-form-error")}})}}function submitSessionAddress(a){dataValue="start="+a+"&type=save";$.ajax({url:"/stores/addresssession/",data:dataValue,dataType:"json",type:"post",success:function(b){},error:function(b){}})}function setVisitorAddress(a){visitorAddress=a}function getSessionAddress(){dataValue="type=get";$.ajax({url:"/stores/addresssession/",data:dataValue,dataType:"json",type:"post",success:function(a){$("#start-address").val(a);if(a=="Enter Your Address"||a==""){$("#start-address").css("color","#999999")}else{$("#start-address").css("color","#000")}setVisitorAddress(a)},error:function(a){}})}function submitSessionSearch(b,a){dataValue="value="+b+"&vType="+a+"&type=vSave";$.ajax({url:"/stores/addresssession/",data:dataValue,dataType:"json",type:"post",success:function(c){},error:function(c){}})}function getSessionSearch(){dataValue="type=getSearch";$.ajax({url:"/stores/addresssession/",data:dataValue,dataType:"json",type:"post",success:function(a){$("#start-address").val(a)},error:function(a){}})}function filterEvents(){var e=new Array();var d=new Array();var f=$("#event-filters :checked").length;var b=new Array();var c=new Array();$("#event-filters input").each(function(){var g=$(this).attr("id");var h=$(this).val();var l=new Array(h,g);if($(this).is(":checked")==false){d.push(h)}else{e.push(h)}});$(".event-info").each(function(){var h=$(this).attr("id").replace("filter-","");var g=$(this).attr("class").replace("event-info ","");var g=g.replace(" event-toggle-"+h,"");var l=new Array(h,false);c.push(l)});for(i=0;i<c.length;i++){var a=c[i][0];if($.inArray(a,d)>-1){c[i][1]=true}}for(k=0;k<c.length;k++){if(c[k][1]){$(".event-toggle-"+c[k][0]).slideUp("normal",function(){})}else{$(".event-toggle-"+c[k][0]).slideDown("normal",function(){})}}}function initStorePageMap(c,b){latLongStore=c;var a={zoom:14,center:c,mapTypeId:google.maps.MapTypeId.ROADMAP};map=new google.maps.Map(document.getElementById("map-google"),a);marker=new google.maps.Marker({position:c,map:map,title:b,icon:"/_site_images/_retail_pages/map-icon_32.png",animation:google.maps.Animation.DROP});markers.push(marker)};