function checkRefresh()
{
	if( document.refreshForm.visited.value == "" )
	{
		// This is a fresh page load
		document.refreshForm.visited.value = "1";
	
		// You may want to add code here special for
		// fresh page loads
	}
	else
	{
		// This is a page refresh
		setCookie("showCustomerReview","");
		// Insert code here representing what to do on
		// a refresh
	}
} 


function setCookie(c_name,value,exdays)
{
var exdate=new Date();
exdate.setDate(exdate.getDate() + exdays);
var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
document.cookie=c_name + "=" + c_value;
}
function SERVER_HTTP_HOST(){  
    var url = window.location.href;  
    url = url.replace("https://", "");   
      
    var urlExplode = url.split("/");  
    var serverName = urlExplode[0];
    var URI = urlExplode[2];  
      
    //serverName = 'https://'+serverName+URI;  
    return URI;  
}  