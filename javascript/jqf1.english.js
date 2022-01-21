/*! 
 * jQuery FormOne - JQF1 1.0.111 - 06/06/2010
 * 
 * Author: Thiago Azurem - azurem@gmail.com
 * Collaborator: Raimundo Neto - sputnykster@gmail.com
 * Thanks to: Renato Nascimento, Thiago Galvão Soares
 * Basic usage:
    
    Using at containner element, like a Form, to style form elements inside.
    Usando em um elemento container, como um formulario por exemplo, para estilizar os seus elementos de formulario internos ao mesmo.

      $('.formExample').jqf1();
	  <form id="" action="" class=".formExample">

      OBS: NOT APPLY FOR HIDDEN (display:none) ELEMENTS - Hidden elements needs to apply the .jqf() in his containner after onload.
      OBS: NAO SE APLICA A ELEMENTOS ESCONDIDOS (display:none) - Elementos escondidos precisam aplicar .jqf1() em seu containner depois de carregados.

    Building or Updating a single form element;
    Construindo ou Atualizando um simples elemento de formulario;

      $('#select1').jqf1();
      <select id="select1" name="">

    Special select attributes:
	Atributos especiais nos selects:

      Multiple select with size=1 attribute:  Select with slide toggle and input chekcs for each option.
	  Select Multiple com atributo size=1: Select com slide e input checks para cada option.
        <select id="" name="" multiple="multiple" size="1">
	  Search=ok: Puts a input text field inside working like "options filter".
	  Search=ok: Coloca um campo input de texto como "filtro de options".
        <select id="" name="" search="ok">
	  Openheight=?: Altura da lista aberta de options.
	  Openheight=?: Opened options list height.
        <select id="" name="" openheight="150">

    "Required Fields" Addon coming soon in the next version.
    Addon de "Campos Obrigatorios" em breve na proxima versao.
*/

(function($)     {
$.fn.extend(     {
jqf1: function() {

  //Textos dos inputs
  //Inputs texts
  var jqf1InpFileText   = 'Choose file';
  var jqf1InpFileText2  = 'Use the button to choose file';
  var jqf1InpSearchText = 'Type here to filter';

  //Detectar navegador
  //Browser detection
  var jqf1Browser = navigator.userAgent.toLowerCase();
  var j1f1Tag = $(this)[0].tagName;
  if ( (j1f1Tag != "SELECT") && (j1f1Tag != "INPUT") && (j1f1Tag != "TEXTAREA") && (j1f1Tag != "BUTTON") && j1f1Tag!=null ) {
  //Transforms all form elements inside the selected element.
  //Transforma tudo dentro do elemento escolhido.

    // Update select values to original selects.
    // Funcao que repassa valores dos select criados aos originais.
    $.jqf1SelectValue = function(tamanho,idJqf1,idSelect,jqf1SelOptVal,jqf1SelOptHtml){
      var jqf1SelTempVal = $('#'+idSelect).val();
      if (jqf1SelTempVal == null ) { var jqf1SelTempVal = Array (tamanho); }
      if ($('#'+idSelect).attr('multiple') == true) {
        var jqf1Index = jQuery.inArray(jqf1SelOptVal, jqf1SelTempVal);
        if(jqf1Index== -1) jqf1SelTempVal.push(jqf1SelOptVal); else jqf1SelTempVal.splice(jqf1Index,1);
        $('#'+idSelect).val(jqf1SelTempVal);
        /*Funciona no select especial*/ $('#'+idJqf1).text($('#'+idSelect+' option[selected]').size()+' item(s)');
        $('#'+idSelect).trigger('change');
      } else {
        $('#'+idJqf1).text(jqf1SelOptHtml);
        if (jqf1SelOptVal != jqf1SelTempVal) {
          $('#'+idSelect).val(jqf1SelOptVal);
          $('#'+idSelect).trigger('change');
        }
      }
    }

    // Manipulation string function for select with search field. (By Raimundo Neto)
    // Funcao de manipulacao de string para Select com Search (Por Raimundo Neto)
    $.jqf1Strstr = function(haystack, needle, bool) {
      var pos = 0;
      haystack += '';
      pos = haystack.indexOf(needle);
      if (pos == -1) {
        return false;
      } else {
        if (bool) {
          return haystack.substr(0, pos);
        } else {
          return haystack.slice(pos);
        }
      }
    }

    $(this).addClass('jqf1_temp');
    // Global vars
    // Declarando variaveis globais

    var jqf1InpArray = $('.jqf1_temp input');
    var jqf1TxtArray = $('.jqf1_temp textarea');
    var jqf1SelArray = $('.jqf1_temp select');
    var jqf1BtnArray = $('.jqf1_temp input[type="button"], .jqf1_temp input[type="submit"], .jqf1_temp button');
    var jqf1TxtInner;

	//Textarea build
    function jqf1EscreveTextarea(tipo,name,contador,conteudo){
      jqf1TxtInner = "<div class='jqf1"+tipo+" jqf1"+tipo+name+" jqf1"+tipo+contador+" jqf1Normal'><div class='div'><div><b class='jqf1_a'></b><b class='jqf1_b'></b><b class='jqf1_c'></b><b class='jqf1_d'></b></div>"+conteudo+"</div></div>";
    }
    function jqf1TransformaTextarea(tipo,contador,wid,hei) {
      $('.jqf1'+tipo+contador).attr('style','width:'+(wid+2)+'px;height:'+(hei)+'px;');
      $('.jqf1'+tipo+contador+' .div').attr('style','width:'+(wid)+'px;');
      $('.jqf1'+tipo+contador+' .jqf1_b').attr('style','margin-left:'+(wid-2)+'px;');
      $('.jqf1'+tipo+contador+' .jqf1_c').attr('style','margin-top:'+(hei-3)+'px;');
      $('.jqf1'+tipo+contador+' .jqf1_d').attr('style','margin:'+(hei-3)+'px 0px 0px '+(wid-2)+'px;');
    }
    // Inputs build
    jQuery.each(jqf1InpArray, function(i) {
    var jqf1InpWid = $(this).width();
    if ( ($(this).attr("jqf1") == null) && (jqf1InpWid != 0) ) {

      if ($(this).attr("type") != "button" && $(this).attr("type") != "submit"){
        $(this).attr("jqf1","ok");
      }
      var jqf1InpName = $(this).attr("name");
      var jqf1InpId = $(this).attr("id");
      var jqf1InpWid = $(this).width();

      // Inputs text
      if ( ($(this).attr("type") == "text") || ($(this).attr("type") == "password") ){
        $(this).after("<div class='jqf1InpTextNormal"+" jqf1InpText"+jqf1InpName+" jqf1InpText"+i+"' style='width:"+jqf1InpWid+"px'><div class='div'><div>");
        $(this).appendTo('div.jqf1InpText'+i+' .div div');
        $(this).focus(function(){$('.jqf1InpText'+i).addClass('jqf1InpTextFocus').removeClass('jqf1InpTextNormal');});
        $(this).blur(function(){$('.jqf1InpText'+i).addClass('jqf1InpTextNormal').removeClass('jqf1InpTextFocus');});
        $(this).width(jqf1InpWid-8); // The corner images have 4px
      }

      // Inputs Radio
      if ($(this).attr("type") == "radio"){
        $(this).before("<div class='jqf1InpRadio jqf1InpRadio"+jqf1InpName+" jqf1InpRadio"+jqf1InpId+" jqf1InpRadio"+i+"'><div class='inpRadioOff'>");
        $(this).appendTo('div.jqf1InpRadio'+i+' div');
        $(this).focus( function(){ $('div.jqf1InpRadio'+i+' div').css({'border':'1px dotted gray','width':'13px','height':'15px'})});
        $(this).blur( function(){ $('div.jqf1InpRadio'+i+' div').css({'border':'0px','width':'15px','height':'17px'})});
        if ($(this).attr("checked") == true){ $('.jqf1InpRadio'+i+' div').toggleClass('inpRadioOn'); }
        $(this).click(function(){
          $('.jqf1InpRadio'+jqf1InpName+' div').removeClass('inpRadioOn');
          $('.jqf1InpRadio'+jqf1InpName+' div').addClass('inpRadioOff');
          $('.jqf1InpRadio'+i+' div').addClass('inpRadioOn');
          $('.jqf1InpRadio'+i+' div').removeClass('inpRadioOff');
        });
        $(this).attr('style','width:15px;height:15px;');
        $(this).addClass('jqf1Hidden');
      }

      // Inputs checkbox
      if ($(this).attr("type") == "checkbox"){
        $(this).before("<div class='jqf1InpCheck jqf1InpCheck"+jqf1InpName+" jqf1InpCheck"+jqf1InpId+" jqf1InpCheck"+i+"'><div>");
        $(this).appendTo('div.jqf1InpCheck'+i+' div');

        $(this).focus( function(){ $('.jqf1InpCheck'+i+' div').css({'border':'1px dotted gray','width':'12px','height':'15px'})});
        $(this).blur( function(){ $('.jqf1InpCheck'+i+' div').css({'border':'0px','width':'14px','height':'17px'})});
        if ($(this).attr("checked") == true){ $('.jqf1InpCheck'+i+' div').toggleClass('inpCheckOn'); }
        $(this).click(function(){
          $('.jqf1InpCheck'+i+' div').toggleClass('inpCheckOn');
        });
        $(this).addClass('jqf1Hidden');
      }

      // Inputs file
      if ($(this).attr("type") == "file"){
        var jqf1InpFileVal = $(this).attr("value");
		  $(this).change( function() {
		    jqf1InpFileVal = $(this).attr("value");
          $(".jqf1InpFile"+i+" .div div span").html(jqf1InpFileVal);
        });

        $(this).after("<div class='jqf1InpFile jqf1InpF"+i+"'>"+
                      "  <div class='jqf1InpTextNormal jqf1InpFile"+jqf1InpName+" jqf1InpFile"+i+"' style='position:relative;z-index:2;'>"+
                      "    <div class='div'><div><span><input type='text' id='jqf1TextExample"+i+"'><input type='file' id='jqf1FileExample"+i+"'><button></button></span></div></div>"+
                      "  </div>"+
                      "  <div class='jqf1Btn jqf1Btn"+i+"'><div class='div'><div><span>"+jqf1InpFileText+"</span></div></div></div>"+
                      "  <div class='jqf1File'></div>"+
                      "</div>");
        var jqf1InpFilePadrao = $('#jqf1FileExample'+i).width();
        var jqf1InpTextPadrao = $('#jqf1TextExample'+i).width();
        jqf1InpTextPadrao = jqf1InpTextPadrao-50;
        if (jqf1Browser.indexOf('firefox') != -1){  jqf1InpTextPadrao = jqf1InpTextPadrao+50; }
        var jqf1InpBtnPadrao = jqf1InpFilePadrao-jqf1InpTextPadrao;
        var jqf1InpTextTemp = jqf1InpWid-jqf1InpBtnPadrao;
		  $(".jqf1Btn"+i+" span").width(jqf1InpBtnPadrao-18);
        $(".jqf1InpFile"+i+" .div div").width(jqf1InpTextTemp);
		if (jqf1InpFileVal == '') { jqf1InpFileVal = '<em style="font:11px arial">&nbsp;'+jqf1InpFileText2+'</em>' } 
        $(".jqf1InpFile"+i+" .div div span").attr('style','position:absolute;width:2000px;margin-left:-3px;z-index:2;').html(jqf1InpFileVal);
        $(this).attr('style','position:absolute;z-index:1;margin-top:-22px;height:22px;*height:auto;width:'+jqf1InpWid+'px;');
        $(this).addClass('jqf1Hidden');
        $(".jqf1InpF"+i+" .jqf1File").attr("style","clear:both;width:"+jqf1InpWid+"px");
        $(this).prependTo(".jqf1InpF"+i+" .jqf1File");
      }
    }
    });

    // Buttons
    jQuery.each(jqf1BtnArray, function(i) {
    var jqf1BtnWid = $(this).width();
    if ( ($(this).attr("jqf1") == null) && (jqf1BtnWid != 0) ) {
      $(this).attr("jqf1","ok");
	  var jqf1BtnWidIe = 0;
	  if (jqf1Browser.indexOf('firefox') != -1){  jqf1BtnWidIe = 8 }
	  if (jqf1Browser.indexOf('msie') != -1){  jqf1BtnWidIe = 0 }
      // Inputs text
      var jqf1BtnVal;
      var jqf1BtnBold = $(this).css("font-weight");
      if ($(this)[0].tagName == "INPUT") {
        var jqf1BtnVal = $(this).attr("value");
      } else {
        var jqf1BtnVal = $(this).text();
      }
      $(this).before("<div class='jqf1Btn jqf1BtnNormal jqf1Btn"+i+"'><div class='div'><div><span>"+jqf1BtnVal+"</span></div></div></div>");
      $(this).appendTo("div.jqf1Btn"+i);
      $("div.jqf1Btn"+i+" .div span").attr("style","font-weight:"+jqf1BtnBold+";width:"+(jqf1BtnWid-jqf1BtnWidIe+6)+"px");
      $(this).attr('style',' float:left;clear:both;margin-top:-22px;width:'+(jqf1BtnWid+5)+'px;height:22px;');
      $(this).addClass('jqf1Hidden');
	}
    });

    // Textareas
    jQuery.each(jqf1TxtArray, function(i) {
    var jqf1TxtWid = $(this).width();
    if ( ($(this).attr("jqf1") == null) && (jqf1TxtWid != 0) ) {
      $(this).attr("jqf1","ok");
      var jqf1TxtHei  = $(this).height();
      var jqf1TxtName = $(this).attr("name");

      jqf1EscreveTextarea('Txt',jqf1TxtName,i,'<div class="jqf1_textarea"></div>');
      $(this).before(jqf1TxtInner);
      $(this).width(jqf1TxtWid);
      $(this).height(jqf1TxtHei);
      $(this).appendTo('div.jqf1Txt'+jqf1TxtName+' .jqf1_textarea');
      jqf1TransformaTextarea('Txt',i,jqf1TxtWid,jqf1TxtHei);
      $(this).focus(function(){$('.jqf1Txt'+i+'').addClass('jqf1Focus').removeClass('jqf1Normal');});
      $(this).blur(function(){$('.jqf1Txt'+i+'').addClass('jqf1Normal').removeClass('jqf1Focus');});
    }
    });

    // Selects
    jQuery.each(jqf1SelArray, function(i) {
    var jqf1SelWid        = $(this).width();
    if ( ($(this).attr("jqf1") == null) && (jqf1SelWid != 0) ) {
      $(this).attr("jqf1","ok");

      var jqf1Multiple      = $(this).attr('multiple');
      var jqf1Special       = $(this).attr('size');
      var jqf1Search        = $(this).attr('search');

      var jqf1SelHei        = $(this).height();
      var jqf1SelName       = $(this).attr("name");
      var jqf1SelId         = $(this).attr("id");
      var jqf1SelClick      = $(this).attr("onclick");
      var jqf1SelOpenHeight = $(this).attr('openheight');

      var jqf1SelFocus      = 0;
      $(this).removeAttr("onclick");

      // Select Labels [for=id] toggling created elements
      // Select Labels [for=id] executando toggle para os elementos criados
      var jqf1SelLabel = $("label[for='"+jqf1SelId+"']");
      if (jqf1SelLabel != null) {
        $(jqf1SelLabel).attr("for","");
        $(jqf1SelLabel).click( function(){
          if ($('.jqf1Sel'+i+' .jqf1SelList ul').css('display') == 'none') {
            $('.jqf1Sel .jqf1SelList ul').hide();
          }
          $('.jqf1Sel'+i+' .jqf1SelList ul').slideToggle();
        });
      }
      // Selects
      if (jqf1Multiple == true) {
        if (jqf1Special == '1') {
          // Select Multiple Especial
          if (jQuery.browser.msie){ jqf1SelWid = jqf1SelWid+5 }
          else if (jQuery.browser.mozilla){ jqf1SelWid = jqf1SelWid+3 }
          else if(jQuery.browser.safari){ jqf1SelWid = jqf1SelWid+29 }
          else if(jQuery.browser.opera){ jqf1SelWid = jqf1SelWid+7 }
          $(this).before("<div class='jqf1Sel jqf1SelMultiple jqf1Sel"+jqf1SelName+" jqf1Sel"+i+"'><div class='jqf1SelList'><ul style='max-height:"+jqf1SelOpenHeight+"px;_height:"+jqf1SelOpenHeight+"px'></ul></div><div class='jqf1SelAtivo'><a href='javascript:void(0);' id='jqf1SelA"+i+"'></a><span></span></div></div>");
        } else {
          // Select Multiple Normal
          jqf1EscreveTextarea('SelMulti',jqf1SelName,i,'<div class="jqf1SelList"><ul style="width:'+jqf1SelWid+'px;height:'+jqf1SelHei+'px;border-bottom:0px;"></ul></div>');
          $(this).before(jqf1TxtInner);
          jqf1TransformaTextarea('SelMulti',i,jqf1SelWid,jqf1SelHei);
        }
      } else {
        // Select Normal
        if (jQuery.browser.msie){ jqf1SelWid = jqf1SelWid+5 }
        else if (jQuery.browser.mozilla){ jqf1SelWid = jqf1SelWid+3 }
        else if(jQuery.browser.safari){ jqf1SelWid = jqf1SelWid+29 }
        else if(jQuery.browser.opera){ jqf1SelWid = jqf1SelWid+7 }
        $(this).before("<div class='jqf1Sel jqf1Sel"+jqf1SelName+" jqf1Sel"+i+"'><div class='jqf1SelList'><ul style='max-height:"+jqf1SelOpenHeight+"px;_height:"+jqf1SelOpenHeight+"px'></ul></div><div class='jqf1SelAtivo'><a href='javascript:void(0);' id='jqf1SelA"+i+"'></a><span></span></div></div>");
      }

      // Select Options / Optgroups
      var jqf1SelTempArray = $(this).find("option,optgroup");
      jQuery.each(jqf1SelTempArray, function() {
        $(this).addClass('jqf1OptCheck');
      });
      var jqf1SelTempArray = $(this).children().find("option");
      jQuery.each(jqf1SelTempArray, function() {
        $(this).addClass('jqf1OptPadding');
      });
      var jqf1SelOptArray = $(this).find(".jqf1OptCheck");
      var jqf1SelOptCount = 0;
      var jqf1SelOptLabel = "";
      jQuery.each(jqf1SelOptArray, function(z) {
        $(this).removeClass('jqf1OptCheck');
        // Optgroup
        if($(this).is('optgroup')){
          jqf1SelOptLabel = $(this).attr('label');
          if (jqf1Multiple == true && jqf1Special != '1') {
            // Multiple Comum
            $('.jqf1SelMulti'+i+' .jqf1SelList ul').append('<li class="jqf1Opt jqf1OptG" style="border:0px"><em><span>'+jqf1SelOptLabel+'</span></em></li>');
          } else {
            $('.jqf1Sel'+i+' .jqf1SelList ul').append('<li class="jqf1Opt jqf1OptG"><span>'+jqf1SelOptLabel+'</span></li>');
          }
        } else {
        // Option
          var jqf1SelOptHtml = $(this).html();
          var jqf1SelOptVal = $(this).val();
          var jqf1OptPadding = '';
          var jqf1SelOptHtmlId = jqf1SelOptHtml;
          var jqf1SelOptLabelId = jqf1SelOptLabel;
          jqf1SelOptLabelId = jqf1SelOptLabelId.replace(/ /g,'');
          jqf1SelOptLabelId = jqf1SelOptLabelId.toLowerCase();
          jqf1SelOptHtmlId = jqf1SelOptHtmlId.replace(/ /g,'');
          jqf1SelOptHtmlId = jqf1SelOptHtmlId.toLowerCase();
          if($(this).hasClass('jqf1OptPadding')){
            jqf1OptPadding = 'jqf1OptPadding';
            $(this).removeClass('jqf1OptPadding');
          }
          if (jqf1Multiple == true) {
            if (jqf1Special == '1') {
              // Multiple Especial
              $('.jqf1Sel'+i).addClass('jqf1SelMulti');
              $('.jqf1Sel'+i+' .jqf1SelList ul').append('<li class="jqf1Opt jqf1Opt'+z+'" onclick="$.jqf1SelectValue(\''+jqf1SelOptArray.size()+'\',\'jqf1SelA'+i+'\',\''+jqf1SelId+'\',\''+jqf1SelOptVal+'\');"><a class="'+jqf1OptPadding+'" href="javascript:void(0)"><span>'+jqf1SelOptHtml+'</span></a></li>');
              if ($(this).attr('selected') == true) {
                jqf1SelOptCount = jqf1SelOptCount+1;
                $('.jqf1Sel'+i+' .jqf1Opt'+z).toggleClass('jqf1SelChecked');
              }
              $('#jqf1SelA'+i).text(jqf1SelOptCount+' item(s)');
            } else {
              // Multiple Comum
              $('.jqf1SelMulti'+i+' .jqf1SelList ul').append('<li class="jqf1Opt jqf1Opt'+z+'" onclick="$.jqf1SelectValue(\''+jqf1SelOptArray.size()+'\',\'jqf1SelA'+i+'\',\''+jqf1SelId+'\',\''+jqf1SelOptVal+'\');"><a class="'+jqf1OptPadding+'" href="javascript:void(0)"><span>'+jqf1SelOptHtml+'</span></a></li>');
              $('.jqf1SelMulti'+i).click(jqf1SelClick);
              if ($(this).attr('selected') == true) {
                $('.jqf1SelMulti'+i+' .jqf1Opt'+z).toggleClass('jqf1SelChecked');
              }
            }
          } else {
            // Normal
            $('.jqf1Sel'+i+' .jqf1SelList ul').append('<li class="jqf1Opt" onclick="$.jqf1SelectValue(\''+jqf1SelOptArray.size()+'\',\'jqf1SelA'+i+'\',\''+jqf1SelId+'\',\''+jqf1SelOptVal+'\',\''+jqf1SelOptHtml+'\');"><a class="'+jqf1OptPadding+'" href="javascript:void(0)"><span id="' + jqf1SelOptLabelId + '_' + jqf1SelOptHtmlId + '">'+jqf1SelOptHtml+'</span></a></li>');
            if ($(this).attr('selected') == true) { $('#jqf1SelA'+i).text(jqf1SelOptHtml); }
          }
        }
      });
      // Select Styles
      $(this).css('margin-left','-'+jqf1SelWid+'px').hide();
      //$('.jqf1Sel'+i).attr('style','width:'+jqf1SelWid+'px;');
      //$('.jqf1Sel'+i+' .jqf1SelList').attr('style','width:'+jqf1SelWid+'px;');
      jqf1SelWid = jqf1SelWid-9;
      //$('.jqf1Sel'+i+' ul').css('width',jqf1SelWid+'px');
      $('.jqf1Sel'+i+' ul').hide();
      jqf1SelWid = jqf1SelWid-16;
      $('#jqf1SelA'+i).attr('style','width:'+(jqf1SelWid)+'px;').click(jqf1SelClick);
      $('.jqf1Sel'+i+' .jqf1SelAtivo span').attr('style','width:'+(jqf1SelWid)+'px;');
      // Variaveis temporaria Multi e Timer
      var jqf1Time=new Array();
      var jqf1TempClass = '';
      if ( (jqf1Multiple == true) && (jqf1Special != '1') ) {
        jqf1TempClass = 'Multi';
      }
      // Select Binds
      function jqf1ToggleSelect(item) {
        if ($('.jqf1Sel'+item+' .jqf1SelList ul').css('display') == 'none') {
          $('.jqf1Sel .jqf1SelList ul').hide();
          $('.jqf1Sel'+item+' input').hide();
        }
        $('.jqf1Sel'+i+' .jqf1SelList ul').slideToggle();
        $('.jqf1Sel'+i+' input').toggle();
      }
      
      $('.jqf1Sel'+i).click( function(){ jqf1ToggleSelect(i) });
	      $('.jqf1Sel'+i).focus( function(){
	      $('.jqf1Sel'+i).trigger('click');
      });
	      
	  var jqf1SelClickOut = true;
        
      $('.jqf1Sel'+i).mouseout(function(){
        jqf1SelClickOut = true;
      });
      $('.jqf1Sel'+i).mouseover(function(){
        jqf1SelClickOut = false;
      });
        
	  $(document).click( function(){
		  if(jqf1SelClickOut){
			  $('.jqf1SelList input').attr('value','').keyup();
		  	  $('.jqf1Sel .jqf1SelList ul').slideUp();
		  } 
	  });
      
      $('.jqf1Sel'+jqf1TempClass+i+'').mouseover(function(){
        clearTimeout(jqf1Time[i]);
        $('#jqf1Search'+i+'').unbind('blur');
      });
      if ( (jqf1Multiple == true) && (jqf1Special != '1') ) {
        $('.jqf1Sel'+jqf1TempClass+i+'').mouseout(function(){
          jqf1Time[i] = setTimeout("$('#jqf1Search"+i+"').attr('value','').keyup();",10000);
        });
      } else {
        $('.jqf1Sel'+i+'').mouseout(function(){
          jqf1Time[i] = setTimeout("$('#jqf1Search"+i+"').attr('value','').keyup().hide();$('.jqf1Sel"+i+" .jqf1SelList ul').slideUp();",2000);
          $('#jqf1Search'+i+'').unbind('blur');
        });
      }

      if (jqf1Multiple == false) {
        $('.jqf1Sel'+i+' li').click( function() {
          $('.jqf1Sel'+i+' .jqf1SelList ul').fadeOut('normal');
        });
      } else {
        $('.jqf1Sel'+i+' li').click( function() {
          $(this).toggleClass('jqf1SelChecked');
        });
        $('.jqf1SelMulti'+i+' li').click( function() {
          $(this).toggleClass('jqf1SelChecked');
        });
      }

      // Search input inside of select
      if (jqf1Search == 'ok') {
        $('.jqf1Sel'+jqf1TempClass+i+' .jqf1SelList ul').prepend('<li><input id="jqf1Search'+i+'" type="text" jqf1="ok" size="8" value="'+jqf1InpSearchText+'" onfocus="$(this).attr(\'value\',\'\');$(this).removeAttr(\'onfocus\');" /></li>');
      }
      $('#jqf1Search'+i).bind('keyup', function(){
        jqf1SearchText = $.trim($(this).val());
        $(".jqf1Sel"+jqf1TempClass+i+" .jqf1Opt").each(function(i,e){
          vRowText = $(e).find('span').html();
          vResult = $.jqf1Strstr(vRowText.toLowerCase(), jqf1SearchText.toLowerCase());
          if (vResult == false && jqf1SearchText != '' || $(e).hasClass('jqf1OptG')) {
            $(e).hide();
          } else {
            $(e).show();
          }
          if (jqf1SearchText == ''){
            $(e).show();
          }
        });
      });
    }
    });
    $(this).addClass('jqf1');

  }// FIM DO IF

  //Faz updade se executado para um elemento select.
  if ($(this)[0].tagName == "SELECT") {

    var j1f1UpSelName = $(this).attr('name');
    var jqf1Multiple = $(this).attr('multiple');
    var jqf1Special = $(this).attr('size');
    $(this).show();
    $(this).removeAttr("jqf1");
    if (jqf1Multiple == true) {
      if (jqf1Special != '1') {
        $('.jqf1SelMulti'+j1f1UpSelName).remove(); 
      }
    } else {
      $('.jqf1Sel'+j1f1UpSelName).remove(); 
    }
    var parentSel = $(this).parent();
    parentSel.jqf1();
    if (parentSel[0].tagName != 'FORM') {
      parentSel.removeClass('jqf1');
    }

  }
  //Faz updade se executado para um radio ou checkbox parei aqui.
  if ($(this)[0].tagName == "INPUT") {
    var jqf1TypeClass;
    var jqf1Id = $(this).attr('id');
    var jqf1Type = $(this).attr('type');
    if (jqf1Type == "radio") { jqf1TypeClass = 'Radio'; }
    if (jqf1Type == "checkbox") { jqf1TypeClass = 'Check'; }
    if ($(this).attr("checked") == true){
      $('.jqf1Inp'+jqf1TypeClass+jqf1Id+' div').attr('className','inp'+jqf1TypeClass+'On');
	} else {
      $('.jqf1Inp'+jqf1TypeClass+jqf1Id+' div').attr('className','');
	}
  }





}
});
})(jQuery);

