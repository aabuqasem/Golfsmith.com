(function(g){var f=new Array;var c=new Array;var n=new Array;var p=new Array;var e=new Array;var l=new Array;var d=new Array;var b=new Array;var h=new Array;var o=new Array;var a=new Array;var m=new Array;g.fn.coinslider=g.fn.CoinSlider=function(q){init=function(r){c[r.id]=new Array();n[r.id]=new Array();p[r.id]=new Array();e[r.id]=new Array();l[r.id]=new Array();d[r.id]=new Array();h[r.id]=0;a[r.id]=0;m[r.id]=1;f[r.id]=g.extend({},g.fn.coinslider.defaults,q);g.each(g("#"+r.id+" img"),function(s,t){n[r.id][s]=g(t).attr("src");l[r.id][s]=g(t).next().html();d[r.id][s]=g(t).next().next().val();p[r.id][s]=g(t).next().next().next().is("input")?g(t).next().next().next().val():"";e[r.id][s]=g(t).parent().is("a")?g(t).parent().attr("target"):"";g(t).hide();g(t).next().hide()});g(r).css({"background-image":"url("+n[r.id][0]+")",width:f[r.id].width,height:f[r.id].height,position:"relative","background-position":"top left"}).wrap("<div class='coin-slider' id='coin-slider-"+r.id+"' />");g("#"+r.id).append("<div class='cs-title' id='cs-title-"+r.id+"' style='z-index: 1000;'></div>");g.setFields(r);if(f[r.id].navigation){g.setNavigation(r)}g.transition(r,0);g.transitionCall(r)};g.setFields=function(r){tWidth=sWidth=parseInt(f[r.id].width/f[r.id].spw);tHeight=sHeight=parseInt(f[r.id].height/f[r.id].sph);counter=sLeft=sTop=0;tgapx=gapx=f[r.id].width-f[r.id].spw*sWidth;tgapy=gapy=f[r.id].height-f[r.id].sph*sHeight;for(i=1;i<=f[r.id].sph;i++){gapx=tgapx;if(gapy>0){gapy--;sHeight=tHeight+1}else{sHeight=tHeight}for(j=1;j<=f[r.id].spw;j++){if(gapx>0){gapx--;sWidth=tWidth+1}else{sWidth=tWidth}c[r.id][counter]=i+""+j;counter++;if(f[r.id].links){g("#"+r.id).append("<a href='"+p[r.id][0]+"' class='cs-"+r.id+"' id='cs-"+r.id+i+j+"' style='width:"+sWidth+"px; height:"+sHeight+"px; float: left; position: absolute;'></a>")}else{g("#"+r.id).append("<div class='cs-"+r.id+"' id='cs-"+r.id+i+j+"' style='width:"+sWidth+"px; height:"+sHeight+"px; float: left; position: absolute;'></div>")}g("#cs-"+r.id+i+j).css({"background-position":-sLeft+"px "+(-sTop+"px"),left:sLeft,top:sTop});sLeft+=sWidth}sTop+=sHeight;sLeft=0}g(".cs-"+r.id).mouseover(function(){g("#cs-navigation-"+r.id).show()});g(".cs-"+r.id).mouseout(function(){g("#cs-navigation-"+r.id).hide()});g("#cs-title-"+r.id).mouseover(function(){g("#cs-navigation-"+r.id).show()});g("#cs-title-"+r.id).mouseout(function(){g("#cs-navigation-"+r.id).hide()});if(f[r.id].hoverPause){g(".cs-"+r.id).mouseover(function(){f[r.id].pause=true});g(".cs-"+r.id).mouseout(function(){f[r.id].pause=false});g("#cs-title-"+r.id).mouseover(function(){f[r.id].pause=true});g("#cs-title-"+r.id).mouseout(function(){f[r.id].pause=false})}};g.transitionCall=function(r){clearInterval(b[r.id]);delay=f[r.id].delay+f[r.id].spw*f[r.id].sph*f[r.id].sDelay;b[r.id]=setInterval(function(){g.transition(r)},delay)};g.transition=function(r,s){if(f[r.id].pause==true){return}g.effect(r);a[r.id]=0;o[r.id]=setInterval(function(){g.appereance(r,c[r.id][a[r.id]])},f[r.id].sDelay);g(r).css({"background-image":"url("+n[r.id][h[r.id]]+")"});if(typeof(s)=="undefined"){h[r.id]++}else{if(s=="prev"){h[r.id]--}else{h[r.id]=s}}if(h[r.id]==n[r.id].length){h[r.id]=0}if(h[r.id]==-1){h[r.id]=n[r.id].length-1}g(".cs-button-"+r.id).removeClass("cs-active");g("#cs-button-"+r.id+"-"+(h[r.id]+1)).addClass("cs-active");if(l[r.id][h[r.id]]){g("#cs-title-"+r.id).css({opacity:0}).animate({opacity:f[r.id].opacity},f[r.id].titleSpeed);switch(d[r.id][h[r.id]]){case"top":g("#cs-title-"+r.id).removeClass("featureBottom featureLeft featureRight featureNone").addClass("featureTop");break;case"bottom":g("#cs-title-"+r.id).removeClass("featureTop featureLeft featureRight featureNone").addClass("featureBottom");break;case"left":g("#cs-title-"+r.id).removeClass("featureBottom featureTop featureRight featureNone").addClass("featureLeft");break;case"right":g("#cs-title-"+r.id).removeClass("featureTop featureLeft featureBottom featureNone").addClass("featureRight");break;case"none":g("#cs-title-"+r.id).removeClass("featureTop featureLeft featureBottom featureRight").addClass("featureNone");break}g("#cs-title-"+r.id).html(l[r.id][h[r.id]])}else{g("#cs-title-"+r.id).css("opacity",0)}};g.appereance=function(s,r){g(".cs-"+s.id).attr("href",p[s.id][h[s.id]]).attr("target",e[s.id][h[s.id]]);if(a[s.id]==f[s.id].spw*f[s.id].sph){clearInterval(o[s.id]);return}g("#cs-"+s.id+r).css({opacity:0,"background-image":"url("+n[s.id][h[s.id]]+")"});g("#cs-"+s.id+r).animate({opacity:1},300);a[s.id]++};g.setNavigation=function(r){g(r).append("<div id='cs-navigation-"+r.id+"'></div>");g("#cs-navigation-"+r.id).hide();g("#cs-navigation-"+r.id).append("<a href='#' id='cs-prev-"+r.id+"' class='cs-prev'>prev</a>");g("#cs-navigation-"+r.id).append("<a href='#' id='cs-next-"+r.id+"' class='cs-next'>next</a>");g("#cs-prev-"+r.id).css({position:"absolute",top:f[r.id].height/2-15,left:0,"z-index":1001,"line-height":"30px",opacity:"none",color:"white"}).click(function(s){s.preventDefault();g.transition(r,"prev");g.transitionCall(r)}).mouseover(function(){g("#cs-navigation-"+r.id).show()});g("#cs-next-"+r.id).css({position:"absolute",top:f[r.id].height/2-15,right:0,"z-index":1001,"line-height":"30px",opacity:"none",color:"white"}).click(function(s){s.preventDefault();g.transition(r);g.transitionCall(r)}).mouseover(function(){g("#cs-navigation-"+r.id).show()});g("<div id='cs-buttons-"+r.id+"' class='cs-buttons'></div>").appendTo(g("#coin-slider-"+r.id));for(k=1;k<n[r.id].length+1;k++){g("#cs-buttons-"+r.id).append("<a href='#' class='cs-button-"+r.id+"' id='cs-button-"+r.id+"-"+k+"'><span class='feature_text' id='feature_text_"+k+"'></span></a>")}g.each(g(".cs-button-"+r.id),function(s,t){g(t).click(function(u){g(".cs-button-"+r.id).removeClass("cs-active");g(this).addClass("cs-active");u.preventDefault();g.transition(r,s);g.transitionCall(r)})});g("#cs-navigation-"+r.id+" a").mouseout(function(){g("#cs-navigation-"+r.id).hide();f[r.id].pause=false});g("#cs-buttons-"+r.id).css({position:"relative"})};g.effect=function(r){effA=["random","swirl","rain","straight"];if(f[r.id].effect==""){eff=effA[Math.floor(Math.random()*(effA.length))]}else{eff=f[r.id].effect}c[r.id]=new Array();if(eff=="random"){counter=0;for(i=1;i<=f[r.id].sph;i++){for(j=1;j<=f[r.id].spw;j++){c[r.id][counter]=i+""+j;counter++}}g.random(c[r.id])}if(eff=="rain"){g.rain(r)}if(eff=="swirl"){g.swirl(r)}if(eff=="straight"){g.straight(r)}m[r.id]*=-1;if(m[r.id]>0){c[r.id].reverse()}};g.random=function(r){var t=r.length;if(t==0){return false}while(--t){var s=Math.floor(Math.random()*(t+1));var v=r[t];var u=r[s];r[t]=u;r[s]=v}};g.swirl=function(r){var t=f[r.id].sph;var u=f[r.id].spw;var B=1;var A=1;var s=0;var v=0;var z=0;var w=true;while(w){v=(s==0||s==2)?u:t;for(i=1;i<=v;i++){c[r.id][z]=B+""+A;z++;if(i!=v){switch(s){case 0:A++;break;case 1:B++;break;case 2:A--;break;case 3:B--;break}}}s=(s+1)%4;switch(s){case 0:u--;A++;break;case 1:t--;B++;break;case 2:u--;A--;break;case 3:t--;B--;break}check=g.max(t,u)-g.min(t,u);if(u<=check&&t<=check){w=false}}};g.rain=function(t){var w=f[t.id].sph;var r=f[t.id].spw;var v=0;var u=to2=from=1;var s=true;while(s){for(i=from;i<=u;i++){c[t.id][v]=i+""+parseInt(to2-i+1);v++}to2++;if(u<w&&to2<r&&w<r){u++}if(u<w&&w>=r){u++}if(to2>r){from++}if(from>u){s=false}}};g.straight=function(r){counter=0;for(i=1;i<=f[r.id].sph;i++){for(j=1;j<=f[r.id].spw;j++){c[r.id][counter]=i+""+j;counter++}}};g.min=function(s,r){if(s>r){return r}else{return s}};g.max=function(s,r){if(s<r){return r}else{return s}};this.each(function(){init(this)})};g.fn.coinslider.defaults={width:565,height:290,spw:7,sph:5,delay:3000,sDelay:30,opacity:0.7,titleSpeed:500,effect:"",navigation:true,links:true,hoverPause:true}})(jQuery);