function validate_email()
{
	$("#btnSendEmail").prop("disabled",true);
  var strValidation = '';
  
  var numOfEmails = new RegExp("^([^;]*;){0,4}[^;]*$");
  if(!numOfEmails.test($("#txtRecEmail").val()))
	  strValidation += '- No more than 5 recipient emails<br>';

  if ($("#txtSendName").val() == '')
    strValidation += '- Your Name<br>';

  if ($("#txtSendEmail").val() == '' || !emailCheck($("#txtSendEmail").val()))
    strValidation += '- Your Email Address<br>';
  
  if($("#txtSendEmail").val().indexOf(';') > 0)
	  strValidation += 'Only 1 email allowed for your email address.';

  if (strValidation != '')
  {
    $("#divEmailCartErrorMessages").html(strValidation);
    $("#divEmailCartError").show();
  }
  else
  {
	  setValueToGoogle('Cart','Email Cart');
	  $.post("/sc_emailcart.php", $("#formEmailCart").serialize(), function(data){
		  $("#divSavedCart").hide();
		  $("#divEmailSent").html(data);
		  $("#divEmailSent").show();
	  });
  }
  $("#btnSendEmail").prop("disabled",false);
  return false;
}

function emailCheck (emailStr)
{
  var emailPat=/^(.+)@(.+)$/;
  var specialChars="\\(\\)<>@,;:\\\\\\\"\\.\\[\\]";
  var validChars="\[^\\s" + specialChars + "\]"
  var ipDomainPat=/^\[(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\]$/;
  var atom=validChars + '+';
  var matchArray=emailStr.match(emailPat);

  if (matchArray==null) 
    return false;

  var domain=matchArray[2];

  var atomPat=new RegExp(atom,"g");
  var domArr=domain.match(atomPat);
  var len=domArr.length;

  if (domArr[domArr.length-1].length < 2 || domArr[domArr.length-1].length>4) 
    return false;

  if (len<2) 
    return false;

  return true;
}
//Show pop up of save cart results
function saveCart()
{
	// Close dialog and empty it
	$("#modal_save_cart").dialog("close");
	$("#modal_save_cart").empty();

	// Load save cart
	$("#modal_save_cart")
	.load("/savedcart/savecart", function(){
		$("#modal_save_cart").dialog('option', 'position', 'center');
	})
    .dialog({
      title: 'Your Shopping Cart Has Been Saved!',
      closeText: 'Close',
      modal: true,
      resizable: false,
      height: 'auto',
      width: 'auto'
    });

	// Set close button and clicking outside of dialog to close dialog
	$(".ui-widget-overlay").click(function(){
	      $("#modal_save_cart").dialog("close"); 
	      });
	$(".ui-dialog-titlebar-close").click(function() {
		$("#modal_save_cart").dialog("close");
	});

	// Apply css to dialog
	$(".ui-dialog-titlebar").attr('style', 'letter-spacing:1px; background-color:#f3f3f3;');
	$(".ui-dialog-title").attr('style', 'float:none; margin: .25em auto; text-align:center; width: 600px; display: block;');
	$(".ui-dialog").css('padding', '0px');
}

// Show email friend the cart
function emailFriend(cartId)
{
	// Close dialog and empty it
	$("#modal_save_cart").dialog("close");
	$("#modal_save_cart").empty();
	
	// Load email friend
	$("#modal_save_cart")
	.load("/sc_emailcart.php?saved_cart_id=" + cartId, function(){
		$("#modal_save_cart").dialog('option', 'position', 'center');
	})
	.dialog({
		title: 'Email Saved Cart',
		closeText: 'Close',
		modal: true,
		resizable: false
	});

	// Set close button and clicking outside of dialog to close dialog
	$(".ui-widget-overlay").click(function(){
	      $("#modal_save_cart").dialog("close"); 
	      });
	$(".ui-dialog-titlebar-close").click(function() {
		$("#modal_save_cart").dialog("close");
	});

	// Apply css to dialog
	$(".ui-dialog-titlebar").attr('style', 'letter-spacing:1px; background-color:#f3f3f3;');
	$(".ui-dialog-title").attr('style', 'float:none; margin: .25em auto; text-align:center; width: 600px; display: block;');
	$(".ui-dialog").css('padding', '0px');
}
