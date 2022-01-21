if(jQuery){(function(a){a.extend(a.fn,{selectBox:function(k,A){var c,y="",n=navigator.platform.match(/mac/i);var s=function(I,D){var M;if(navigator.userAgent.match(/iPad|iPhone|Android|IEMobile|BlackBerry/i)){return false}if(I.tagName.toLowerCase()!=="select"){return false}I=a(I);if(I.data("selectBox-control")){return false}var C=a('<a class="selectBox" />'),G=I.attr("multiple")||parseInt(I.attr("size"))>1;var B=D||{};C.width(I.outerWidth()).addClass(I.attr("class")).attr("title",I.attr("title")||"").attr("tabindex",parseInt(I.attr("tabindex"))).css("display","inline-block").bind("focus.selectBox",function(){if(this!==document.activeElement){a(document.activeElement).blur()}if(C.hasClass("selectBox-active")){return}C.addClass("selectBox-active");I.trigger("focus")}).bind("blur.selectBox",function(){if(!C.hasClass("selectBox-active")){return}C.removeClass("selectBox-active");I.trigger("blur")});if(!a(window).data("selectBox-bindings")){a(window).data("selectBox-bindings",true).bind("scroll.selectBox",h).bind("resize.selectBox",h)}if(I.attr("disabled")){C.addClass("selectBox-disabled")}I.bind("click.selectBox",function(N){C.focus();N.preventDefault()});if(G){M=m(I,"inline");C.append(M).data("selectBox-options",M).addClass("selectBox-inline selectBox-menuShowing").bind("keydown.selectBox",function(N){o(I,N)}).bind("keypress.selectBox",function(N){d(I,N)}).bind("mousedown.selectBox",function(N){if(a(N.target).is("A.selectBox-inline")){N.preventDefault()}if(!C.hasClass("selectBox-focus")){C.focus()}}).insertAfter(I);if(!I[0].style.height){var L=I.attr("size")?parseInt(I.attr("size")):5;var E=C.clone().removeAttr("id").css({position:"absolute",top:"-9999em"}).show().appendTo("body");E.find(".selectBox-options").html("<li><a>\u00A0</a></li>");var K=parseInt(E.find(".selectBox-options A:first").html("&nbsp;").outerHeight());E.remove();C.height(K*L)}i(C)}else{var H=a('<span class="selectBox-label" />'),J=a('<span class="selectBox-arrow" />');H.attr("class",b(I)).text(q(I));M=m(I,"dropdown");M.appendTo("BODY");C.data("selectBox-options",M).addClass("selectBox-dropdown").append(H).append(J).bind("mousedown.selectBox",function(N){if(C.hasClass("selectBox-menuShowing")){h()}else{N.stopPropagation();M.data("selectBox-down-at-x",N.screenX).data("selectBox-down-at-y",N.screenY);r(I)}}).bind("keydown.selectBox",function(N){o(I,N)}).bind("keypress.selectBox",function(N){d(I,N)}).insertAfter(I);var F=C.width()-J.outerWidth()-parseInt(H.css("paddingLeft"))-parseInt(H.css("paddingLeft"));H.width(F);i(C)}I.addClass("selectBox").data("selectBox-control",C).data("selectBox-settings",B).hide()};var m=function(B,F){var C;switch(F){case"inline":C=a('<ul class="selectBox-options" />');if(B.find("OPTGROUP").length){B.find("OPTGROUP").each(function(){var G=a('<li class="selectBox-optgroup" />');G.text(a(this).attr("label"));C.append(G);g(a(this).find("OPTION"),C)})}else{g(B.find("OPTION"),C)}C.find("A").bind("mouseover.selectBox",function(G){w(B,a(this).parent())}).bind("mouseout.selectBox",function(G){z(B,a(this).parent())}).bind("mousedown.selectBox",function(G){G.preventDefault();if(!B.selectBox("control").hasClass("selectBox-active")){B.selectBox("control").focus()}}).bind("mouseup.selectBox",function(G){h();v(B,a(this).parent(),G)});i(C);return C;case"dropdown":C=a('<ul class="selectBox-dropdown-menu selectBox-options" />');if(B.find("OPTGROUP").length){B.find("OPTGROUP").each(function(){var G=a('<li class="selectBox-optgroup" />');G.text(a(this).attr("label"));C.append(G);g(a(this).find("OPTION"),C)})}else{if(B.find("OPTION").length>0){g(B.find("OPTION"),C)}else{C.append("<li>\u00A0</li>")}}C.data("selectBox-select",B).css("display","none").appendTo("BODY").find("A").bind("mousedown.selectBox",function(G){G.preventDefault();if(G.screenX===C.data("selectBox-down-at-x")&&G.screenY===C.data("selectBox-down-at-y")){C.removeData("selectBox-down-at-x").removeData("selectBox-down-at-y");h()}}).bind("mouseup.selectBox",function(G){if(G.screenX===C.data("selectBox-down-at-x")&&G.screenY===C.data("selectBox-down-at-y")){return}else{C.removeData("selectBox-down-at-x").removeData("selectBox-down-at-y")}v(B,a(this).parent());h()}).bind("mouseover.selectBox",function(G){w(B,a(this).parent())}).bind("mouseout.selectBox",function(G){z(B,a(this).parent())});var E=B.attr("class")||"";if(E!==""){E=E.split(" ");for(var D in E){C.addClass(E[D]+"-selectBox-dropdown-menu")}}i(C);return C}};var b=function(B){var C=a(B).find("OPTION:selected");return("selectBox-label "+(C.attr("class")||"")).replace(/\s+$/,"")};var q=function(B){var C=a(B).find("OPTION:selected");return C.text()||"\u00A0"};var t=function(B){B=a(B);var C=B.data("selectBox-control");if(!C){return}C.find(".selectBox-label").attr("class",b(B)).text(q(B))};var x=function(B){B=a(B);var D=B.data("selectBox-control");if(!D){return}var C=D.data("selectBox-options");C.remove();D.remove();B.removeClass("selectBox").removeData("selectBox-control").data("selectBox-control",null).removeData("selectBox-settings").data("selectBox-settings",null).show()};var l=function(B){B=a(B);B.selectBox("options",B.html())};var r=function(C){C=a(C);var G=C.data("selectBox-control"),F=C.data("selectBox-settings"),D=G.data("selectBox-options");if(G.hasClass("selectBox-disabled")){return false}h();var E=isNaN(G.css("borderBottomWidth"))?0:parseInt(G.css("borderBottomWidth"));D.width(G.innerWidth()).css({top:G.offset().top+G.outerHeight()-E,left:G.offset().left});switch(F.menuTransition){case"fade":D.fadeIn(F.menuSpeed);break;case"slide":D.slideDown(F.menuSpeed);break;default:D.slideDown(F.menuSpeed);break}var B=D.find(".selectBox-selected:first");e(C,B,true);w(C,B);G.addClass("selectBox-menuShowing");a(document).bind("mousedown.selectBox",function(H){if(a(H.target).parents().andSelf().hasClass("selectBox-options")){return}h()})};var h=function(){if(a(".selectBox-dropdown-menu").length===0){return}a(document).unbind("mousedown.selectBox");a(".selectBox-dropdown-menu").each(function(){var C=a(this),B=C.data("selectBox-select"),E=B.data("selectBox-control"),D=B.data("selectBox-settings");switch(D.menuTransition){case"fade":C.fadeOut(D.menuSpeed);break;case"slide":C.slideUp(D.menuSpeed);break;default:C.slideUp(D.menuSpeed);break}E.removeClass("selectBox-menuShowing")})};var v=function(C,B,H){C=a(C);B=a(B);var I=C.data("selectBox-control"),G=C.data("selectBox-settings");if(I.hasClass("selectBox-disabled")){return false}if(B.length===0||B.hasClass("selectBox-disabled")){return false}if(C.attr("multiple")){if(H.shiftKey&&I.data("selectBox-last-selected")){B.toggleClass("selectBox-selected");var D;if(B.index()>I.data("selectBox-last-selected").index()){D=B.siblings().slice(I.data("selectBox-last-selected").index(),B.index())}else{D=B.siblings().slice(B.index(),I.data("selectBox-last-selected").index())}D=D.not(".selectBox-optgroup, .selectBox-disabled");if(B.hasClass("selectBox-selected")){D.addClass("selectBox-selected")}else{D.removeClass("selectBox-selected")}}else{if((n&&H.metaKey)||(!n&&H.ctrlKey)){console.log(n);B.toggleClass("selectBox-selected")}else{B.siblings().removeClass("selectBox-selected");B.addClass("selectBox-selected")}}}else{B.siblings().removeClass("selectBox-selected");B.addClass("selectBox-selected")}if(I.hasClass("selectBox-dropdown")){I.find(".selectBox-label").text(B.text())}var E=0,F=[];if(C.attr("multiple")){I.find(".selectBox-selected A").each(function(){F[E++]=a(this).attr("rel")})}else{F=B.find("A").attr("rel")}I.data("selectBox-last-selected",B);if(C.val()!==F){C.val(F);t(C);C.trigger("change")}return true};var w=function(C,B){C=a(C);B=a(B);var E=C.data("selectBox-control"),D=E.data("selectBox-options");D.find(".selectBox-hover").removeClass("selectBox-hover");B.addClass("selectBox-hover")};var z=function(C,B){C=a(C);B=a(B);var E=C.data("selectBox-control"),D=E.data("selectBox-options");D.find(".selectBox-hover").removeClass("selectBox-hover")};var e=function(D,C,B){if(!C||C.length===0){return}D=a(D);var I=D.data("selectBox-control"),F=I.data("selectBox-options"),G=I.hasClass("selectBox-dropdown")?F:F.parent(),H=parseInt(C.offset().top-G.position().top),E=parseInt(H+C.outerHeight());if(B){G.scrollTop(C.offset().top-G.offset().top+G.scrollTop()-(G.height()/2))}else{if(H<0){G.scrollTop(C.offset().top-G.offset().top+G.scrollTop())}if(E>G.height()){G.scrollTop((C.offset().top+C.outerHeight())-G.offset().top+G.scrollTop()-G.height())}}};var o=function(I,B){I=a(I);var F=I.data("selectBox-control"),J=F.data("selectBox-options"),D=I.data("selectBox-settings"),E=0,G=0;if(F.hasClass("selectBox-disabled")){return}switch(B.keyCode){case 8:B.preventDefault();y="";break;case 9:case 27:h();z(I);break;case 13:if(F.hasClass("selectBox-menuShowing")){v(I,J.find("LI.selectBox-hover:first"),B);if(F.hasClass("selectBox-dropdown")){h()}}else{r(I)}break;case 38:case 37:B.preventDefault();if(F.hasClass("selectBox-menuShowing")){var C=J.find(".selectBox-hover").prev("LI");E=J.find("LI:not(.selectBox-optgroup)").length;G=0;while(C.length===0||C.hasClass("selectBox-disabled")||C.hasClass("selectBox-optgroup")){C=C.prev("LI");if(C.length===0){if(D.loopOptions){C=J.find("LI:last")}else{C=J.find("LI:first")}}if(++G>=E){break}}w(I,C);v(I,C,B);e(I,C)}else{r(I)}break;case 40:case 39:B.preventDefault();if(F.hasClass("selectBox-menuShowing")){var H=J.find(".selectBox-hover").next("LI");E=J.find("LI:not(.selectBox-optgroup)").length;G=0;while(H.length===0||H.hasClass("selectBox-disabled")||H.hasClass("selectBox-optgroup")){H=H.next("LI");if(H.length===0){if(D.loopOptions){H=J.find("LI:first")}else{H=J.find("LI:last")}}if(++G>=E){break}}w(I,H);v(I,H,B);e(I,H)}else{r(I)}break}};var d=function(B,D){B=a(B);var E=B.data("selectBox-control"),C=E.data("selectBox-options");if(E.hasClass("selectBox-disabled")){return}switch(D.keyCode){case 9:case 27:case 13:case 38:case 37:case 40:case 39:break;default:if(!E.hasClass("selectBox-menuShowing")){r(B)}D.preventDefault();clearTimeout(c);y+=String.fromCharCode(D.charCode||D.keyCode);C.find("A").each(function(){if(a(this).text().substr(0,y.length).toLowerCase()===y.toLowerCase()){w(B,a(this).parent());e(B,a(this).parent());return false}});c=setTimeout(function(){y=""},1000);break}};var p=function(B){B=a(B);B.attr("disabled",false);var C=B.data("selectBox-control");if(!C){return}C.removeClass("selectBox-disabled")};var j=function(B){B=a(B);B.attr("disabled",true);var C=B.data("selectBox-control");if(!C){return}C.addClass("selectBox-disabled")};var f=function(B,E){B=a(B);B.val(E);E=B.val();var F=B.data("selectBox-control");if(!F){return}var D=B.data("selectBox-settings"),C=F.data("selectBox-options");t(B);C.find(".selectBox-selected").removeClass("selectBox-selected");C.find("A").each(function(){if(typeof(E)==="object"){for(var G=0;G<E.length;G++){if(a(this).attr("rel")==E[G]){a(this).parent().addClass("selectBox-selected")}}}else{if(a(this).attr("rel")==E){a(this).parent().addClass("selectBox-selected")}}});if(D.change){D.change.call(B)}};var u=function(I,J){I=a(I);var E=I.data("selectBox-control"),C=I.data("selectBox-settings");switch(typeof(A)){case"string":I.html(A);break;case"object":I.html("");for(var F in A){if(A[F]===null){continue}if(typeof(A[F])==="object"){var B=a('<optgroup label="'+F+'" />');for(var D in A[F]){B.append('<option value="'+D+'">'+A[F][D]+"</option>")}I.append(B)}else{var G=a('<option value="'+F+'">'+A[F]+"</option>");I.append(G)}}break}if(!E){return}E.data("selectBox-options").remove();var H=E.hasClass("selectBox-dropdown")?"dropdown":"inline",J=m(I,H);E.data("selectBox-options",J);switch(H){case"inline":E.append(J);break;case"dropdown":t(I);a("BODY").append(J);break}};var i=function(B){a(B).css("MozUserSelect","none").bind("selectstart",function(C){C.preventDefault()})};var g=function(C,B){C.each(function(){var F=a(this);var D=a("<li />"),E=a("<a />");D.addClass(F.attr("class"));D.data(F.data());E.attr("rel",F.val()).text(F.text());D.append(E);if(F.attr("disabled")){D.addClass("selectBox-disabled")}if(F.attr("selected")){D.addClass("selectBox-selected")}B.append(D)})};switch(k){case"control":return a(this).data("selectBox-control");case"settings":if(!A){return a(this).data("selectBox-settings")}a(this).each(function(){a(this).data("selectBox-settings",a.extend(true,a(this).data("selectBox-settings"),A))});break;case"options":a(this).each(function(){u(this,A)});break;case"value":if(A===undefined){return a(this).val()}a(this).each(function(){f(this,A)});break;case"refresh":a(this).each(function(){l(this)});break;case"enable":a(this).each(function(){p(this)});break;case"disable":a(this).each(function(){j(this)});break;case"destroy":a(this).each(function(){x(this)});break;default:a(this).each(function(){s(this,k)});break}return a(this)}})})(jQuery)};