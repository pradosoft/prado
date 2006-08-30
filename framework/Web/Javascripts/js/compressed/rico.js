
var Rico={Version:'1.1rc1',prototypeVersion:parseFloat(Prototype.Version.split(".")[0]+"."+Prototype.Version.split(".")[1])}
Rico.ArrayExtensions=new Array();if(Object.prototype.extend){Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Object.prototype.extend;}else{Object.prototype.extend=function(object){return Object.extend.apply(this,[this,object]);}
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Object.prototype.extend;}
if(Array.prototype.push){Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.push;}
if(!Array.prototype.remove){Array.prototype.remove=function(dx){if(isNaN(dx)||dx>this.length)
return false;for(var i=0,n=0;i<this.length;i++)
if(i!=dx)
this[n++]=this[i];this.length-=1;};Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.remove;}
if(!Array.prototype.removeItem){Array.prototype.removeItem=function(item){for(var i=0;i<this.length;i++)
if(this[i]==item){this.remove(i);break;}};Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.removeItem;}
if(!Array.prototype.indices){Array.prototype.indices=function(){var indexArray=new Array();for(index in this){var ignoreThis=false;for(var i=0;i<Rico.ArrayExtensions.length;i++){if(this[index]==Rico.ArrayExtensions[i]){ignoreThis=true;break;}}
if(!ignoreThis)
indexArray[indexArray.length]=index;}
return indexArray;}
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.indices;}
if(window.DOMParser&&window.XMLSerializer&&window.Node&&Node.prototype&&Node.prototype.__defineGetter__){if(!Document.prototype.loadXML){Document.prototype.loadXML=function(s){var doc2=(new DOMParser()).parseFromString(s,"text/xml");while(this.hasChildNodes())
this.removeChild(this.lastChild);for(var i=0;i<doc2.childNodes.length;i++){this.appendChild(this.importNode(doc2.childNodes[i],true));}};}
Document.prototype.__defineGetter__("xml",function(){return(new XMLSerializer()).serializeToString(this);});}
document.getElementsByTagAndClassName=function(tagName,className){if(tagName==null)
tagName='*';var children=document.getElementsByTagName(tagName)||document.all;var elements=new Array();if(className==null)
return children;for(var i=0;i<children.length;i++){var child=children[i];var classNames=child.className.split(' ');for(var j=0;j<classNames.length;j++){if(classNames[j]==className){elements.push(child);break;}}}
return elements;}
Rico.Accordion=Class.create();Rico.Accordion.prototype={initialize:function(container,options){this.container=$(container);this.lastExpandedTab=null;this.accordionTabs=new Array();this.setOptions(options);this._attachBehaviors();if(!container)return;this.container.style.borderBottom='1px solid '+this.options.borderColor;if(this.options.onLoadShowTab>=this.accordionTabs.length)
this.options.onLoadShowTab=0;for(var i=0;i<this.accordionTabs.length;i++)
{if(i!=this.options.onLoadShowTab){this.accordionTabs[i].collapse();this.accordionTabs[i].content.style.display='none';}}
this.lastExpandedTab=this.accordionTabs[this.options.onLoadShowTab];if(this.options.panelHeight=='auto'){var tabToCheck=(this.options.onloadShowTab===0)?1:0;var titleBarSize=parseInt(RicoUtil.getElementsComputedStyle(this.accordionTabs[tabToCheck].titleBar,'height'));if(isNaN(titleBarSize))
titleBarSize=this.accordionTabs[tabToCheck].titleBar.offsetHeight;var totalTitleBarSize=this.accordionTabs.length*titleBarSize;var parentHeight=parseInt(RicoUtil.getElementsComputedStyle(this.container.parentNode,'height'));if(isNaN(parentHeight))
parentHeight=this.container.parentNode.offsetHeight;this.options.panelHeight=parentHeight-totalTitleBarSize-2;}
this.lastExpandedTab.content.style.height=this.options.panelHeight+"px";this.lastExpandedTab.showExpanded();this.lastExpandedTab.titleBar.style.fontWeight=this.options.expandedFontWeight;},setOptions:function(options){this.options={expandedBg:'#63699c',hoverBg:'#63699c',collapsedBg:'#6b79a5',expandedTextColor:'#ffffff',expandedFontWeight:'bold',hoverTextColor:'#ffffff',collapsedTextColor:'#ced7ef',collapsedFontWeight:'normal',hoverTextColor:'#ffffff',borderColor:'#1f669b',panelHeight:200,onHideTab:null,onShowTab:null,onLoadShowTab:0}
Object.extend(this.options,options||{});},showTabByIndex:function(anIndex,animate){var doAnimate=arguments.length==1?true:animate;this.showTab(this.accordionTabs[anIndex],doAnimate);},showTab:function(accordionTab,animate){var doAnimate=arguments.length==1?true:animate;if(this.options.onHideTab)
this.options.onHideTab(this.lastExpandedTab);this.lastExpandedTab.showCollapsed();var accordion=this;var lastExpandedTab=this.lastExpandedTab;this.lastExpandedTab.content.style.height=(this.options.panelHeight-1)+'px';accordionTab.content.style.display='';accordionTab.titleBar.style.fontWeight=this.options.expandedFontWeight;if(doAnimate){new Effect.AccordionSize(this.lastExpandedTab.content,accordionTab.content,1,this.options.panelHeight,100,10,{complete:function(){accordion.showTabDone(lastExpandedTab)}});this.lastExpandedTab=accordionTab;}
else{this.lastExpandedTab.content.style.height="1px";accordionTab.content.style.height=this.options.panelHeight+"px";this.lastExpandedTab=accordionTab;this.showTabDone(lastExpandedTab);}},showTabDone:function(collapsedTab){collapsedTab.content.style.display='none';this.lastExpandedTab.showExpanded();if(this.options.onShowTab)
this.options.onShowTab(this.lastExpandedTab);},_attachBehaviors:function(){var panels=this._getDirectChildrenByTag(this.container,'DIV');for(var i=0;i<panels.length;i++){var tabChildren=this._getDirectChildrenByTag(panels[i],'DIV');if(tabChildren.length!=2)
continue;var tabTitleBar=tabChildren[0];var tabContentBox=tabChildren[1];this.accordionTabs.push(new Rico.Accordion.Tab(this,tabTitleBar,tabContentBox));}},_getDirectChildrenByTag:function(e,tagName){var kids=new Array();var allKids=e.childNodes;for(var i=0;i<allKids.length;i++)
if(allKids[i]&&allKids[i].tagName&&allKids[i].tagName==tagName)
kids.push(allKids[i]);return kids;}};Rico.Accordion.Tab=Class.create();Rico.Accordion.Tab.prototype={initialize:function(accordion,titleBar,content){this.accordion=accordion;this.titleBar=titleBar;this.content=content;this._attachBehaviors();},collapse:function(){this.showCollapsed();this.content.style.height="1px";},showCollapsed:function(){this.expanded=false;this.titleBar.style.backgroundColor=this.accordion.options.collapsedBg;this.titleBar.style.color=this.accordion.options.collapsedTextColor;this.titleBar.style.fontWeight=this.accordion.options.collapsedFontWeight;this.content.style.overflow="hidden";},showExpanded:function(){this.expanded=true;this.titleBar.style.backgroundColor=this.accordion.options.expandedBg;this.titleBar.style.color=this.accordion.options.expandedTextColor;this.content.style.overflow="visible";},titleBarClicked:function(e){if(this.accordion.lastExpandedTab==this)
return;this.accordion.showTab(this);},hover:function(e){this.titleBar.style.backgroundColor=this.accordion.options.hoverBg;this.titleBar.style.color=this.accordion.options.hoverTextColor;},unhover:function(e){if(this.expanded){this.titleBar.style.backgroundColor=this.accordion.options.expandedBg;this.titleBar.style.color=this.accordion.options.expandedTextColor;}
else{this.titleBar.style.backgroundColor=this.accordion.options.collapsedBg;this.titleBar.style.color=this.accordion.options.collapsedTextColor;}},_attachBehaviors:function(){this.content.style.border="1px solid "+this.accordion.options.borderColor;this.content.style.borderTopWidth="0px";this.content.style.borderBottomWidth="0px";this.content.style.margin="0px";this.titleBar.onclick=this.titleBarClicked.bindAsEventListener(this);this.titleBar.onmouseover=this.hover.bindAsEventListener(this);this.titleBar.onmouseout=this.unhover.bindAsEventListener(this);}};Rico.Corner={round:function(e,options){var e=$(e);this._setOptions(options);var color=this.options.color;if(this.options.color=="fromElement")
color=this._background(e);var bgColor=this.options.bgColor;if(this.options.bgColor=="fromParent")
bgColor=this._background(e.offsetParent);this._roundCornersImpl(e,color,bgColor);},_roundCornersImpl:function(e,color,bgColor){if(this.options.border)
this._renderBorder(e,bgColor);if(this._isTopRounded())
this._roundTopCorners(e,color,bgColor);if(this._isBottomRounded())
this._roundBottomCorners(e,color,bgColor);},_renderBorder:function(el,bgColor){var borderValue="1px solid "+this._borderColor(bgColor);var borderL="border-left: "+borderValue;var borderR="border-right: "+borderValue;var style="style='"+borderL+";"+borderR+"'";el.innerHTML="<div "+style+">"+el.innerHTML+"</div>"},_roundTopCorners:function(el,color,bgColor){var corner=this._createCorner(bgColor);for(var i=0;i<this.options.numSlices;i++)
corner.appendChild(this._createCornerSlice(color,bgColor,i,"top"));el.style.paddingTop=0;el.insertBefore(corner,el.firstChild);},_roundBottomCorners:function(el,color,bgColor){var corner=this._createCorner(bgColor);for(var i=(this.options.numSlices-1);i>=0;i--)
corner.appendChild(this._createCornerSlice(color,bgColor,i,"bottom"));el.style.paddingBottom=0;el.appendChild(corner);},_createCorner:function(bgColor){var corner=document.createElement("div");corner.style.backgroundColor=(this._isTransparent()?"transparent":bgColor);return corner;},_createCornerSlice:function(color,bgColor,n,position){var slice=document.createElement("span");var inStyle=slice.style;inStyle.backgroundColor=color;inStyle.display="block";inStyle.height="1px";inStyle.overflow="hidden";inStyle.fontSize="1px";var borderColor=this._borderColor(color,bgColor);if(this.options.border&&n==0){inStyle.borderTopStyle="solid";inStyle.borderTopWidth="1px";inStyle.borderLeftWidth="0px";inStyle.borderRightWidth="0px";inStyle.borderBottomWidth="0px";inStyle.height="0px";inStyle.borderColor=borderColor;}
else if(borderColor){inStyle.borderColor=borderColor;inStyle.borderStyle="solid";inStyle.borderWidth="0px 1px";}
if(!this.options.compact&&(n==(this.options.numSlices-1)))
inStyle.height="2px";this._setMargin(slice,n,position);this._setBorder(slice,n,position);return slice;},_setOptions:function(options){this.options={corners:"all",color:"fromElement",bgColor:"fromParent",blend:true,border:false,compact:false}
Object.extend(this.options,options||{});this.options.numSlices=this.options.compact?2:4;if(this._isTransparent())
this.options.blend=false;},_whichSideTop:function(){if(this._hasString(this.options.corners,"all","top"))
return"";if(this.options.corners.indexOf("tl")>=0&&this.options.corners.indexOf("tr")>=0)
return"";if(this.options.corners.indexOf("tl")>=0)
return"left";else if(this.options.corners.indexOf("tr")>=0)
return"right";return"";},_whichSideBottom:function(){if(this._hasString(this.options.corners,"all","bottom"))
return"";if(this.options.corners.indexOf("bl")>=0&&this.options.corners.indexOf("br")>=0)
return"";if(this.options.corners.indexOf("bl")>=0)
return"left";else if(this.options.corners.indexOf("br")>=0)
return"right";return"";},_borderColor:function(color,bgColor){if(color=="transparent")
return bgColor;else if(this.options.border)
return this.options.border;else if(this.options.blend)
return this._blend(bgColor,color);else
return"";},_setMargin:function(el,n,corners){var marginSize=this._marginSize(n);var whichSide=corners=="top"?this._whichSideTop():this._whichSideBottom();if(whichSide=="left"){el.style.marginLeft=marginSize+"px";el.style.marginRight="0px";}
else if(whichSide=="right"){el.style.marginRight=marginSize+"px";el.style.marginLeft="0px";}
else{el.style.marginLeft=marginSize+"px";el.style.marginRight=marginSize+"px";}},_setBorder:function(el,n,corners){var borderSize=this._borderSize(n);var whichSide=corners=="top"?this._whichSideTop():this._whichSideBottom();if(whichSide=="left"){el.style.borderLeftWidth=borderSize+"px";el.style.borderRightWidth="0px";}
else if(whichSide=="right"){el.style.borderRightWidth=borderSize+"px";el.style.borderLeftWidth="0px";}
else{el.style.borderLeftWidth=borderSize+"px";el.style.borderRightWidth=borderSize+"px";}
if(this.options.border!=false)
el.style.borderLeftWidth=borderSize+"px";el.style.borderRightWidth=borderSize+"px";},_marginSize:function(n){if(this._isTransparent())
return 0;var marginSizes=[5,3,2,1];var blendedMarginSizes=[3,2,1,0];var compactMarginSizes=[2,1];var smBlendedMarginSizes=[1,0];if(this.options.compact&&this.options.blend)
return smBlendedMarginSizes[n];else if(this.options.compact)
return compactMarginSizes[n];else if(this.options.blend)
return blendedMarginSizes[n];else
return marginSizes[n];},_borderSize:function(n){var transparentBorderSizes=[5,3,2,1];var blendedBorderSizes=[2,1,1,1];var compactBorderSizes=[1,0];var actualBorderSizes=[0,2,0,0];if(this.options.compact&&(this.options.blend||this._isTransparent()))
return 1;else if(this.options.compact)
return compactBorderSizes[n];else if(this.options.blend)
return blendedBorderSizes[n];else if(this.options.border)
return actualBorderSizes[n];else if(this._isTransparent())
return transparentBorderSizes[n];return 0;},_hasString:function(str){for(var i=1;i<arguments.length;i++)if(str.indexOf(arguments[i])>=0)return true;return false;},_blend:function(c1,c2){var cc1=Rico.Color.createFromHex(c1);cc1.blend(Rico.Color.createFromHex(c2));return cc1;},_background:function(el){try{return Rico.Color.createColorFromBackground(el).asHex();}catch(err){return"#ffffff";}},_isTransparent:function(){return this.options.color=="transparent";},_isTopRounded:function(){return this._hasString(this.options.corners,"all","top","tl","tr");},_isBottomRounded:function(){return this._hasString(this.options.corners,"all","bottom","bl","br");},_hasSingleTextChild:function(el){return el.childNodes.length==1&&el.childNodes[0].nodeType==3;}}
if(window.Effect==undefined)
Effect={};Effect.SizeAndPosition=Class.create();Effect.SizeAndPosition.prototype={initialize:function(element,x,y,w,h,duration,steps,options){this.element=$(element);this.x=x;this.y=y;this.w=w;this.h=h;this.duration=duration;this.steps=steps;this.options=arguments[7]||{};this.sizeAndPosition();},sizeAndPosition:function(){if(this.isFinished()){if(this.options.complete)this.options.complete(this);return;}
if(this.timer)
clearTimeout(this.timer);var stepDuration=Math.round(this.duration/this.steps);var currentX=this.element.offsetLeft;var currentY=this.element.offsetTop;var currentW=this.element.offsetWidth;var currentH=this.element.offsetHeight;this.x=(this.x)?this.x:currentX;this.y=(this.y)?this.y:currentY;this.w=(this.w)?this.w:currentW;this.h=(this.h)?this.h:currentH;var difX=this.steps>0?(this.x-currentX)/this.steps:0;var difY=this.steps>0?(this.y-currentY)/this.steps:0;var difW=this.steps>0?(this.w-currentW)/this.steps:0;var difH=this.steps>0?(this.h-currentH)/this.steps:0;this.moveBy(difX,difY);this.resizeBy(difW,difH);this.duration-=stepDuration;this.steps--;this.timer=setTimeout(this.sizeAndPosition.bind(this),stepDuration);},isFinished:function(){return this.steps<=0;},moveBy:function(difX,difY){var currentLeft=this.element.offsetLeft;var currentTop=this.element.offsetTop;var intDifX=parseInt(difX);var intDifY=parseInt(difY);var style=this.element.style;if(intDifX!=0)
style.left=(currentLeft+intDifX)+"px";if(intDifY!=0)
style.top=(currentTop+intDifY)+"px";},resizeBy:function(difW,difH){var currentWidth=this.element.offsetWidth;var currentHeight=this.element.offsetHeight;var intDifW=parseInt(difW);var intDifH=parseInt(difH);var style=this.element.style;if(intDifW!=0)
style.width=(currentWidth+intDifW)+"px";if(intDifH!=0)
style.height=(currentHeight+intDifH)+"px";}}
Effect.Size=Class.create();Effect.Size.prototype={initialize:function(element,w,h,duration,steps,options){new Effect.SizeAndPosition(element,null,null,w,h,duration,steps,options);}}
Effect.Position=Class.create();Effect.Position.prototype={initialize:function(element,x,y,duration,steps,options){new Effect.SizeAndPosition(element,x,y,null,null,duration,steps,options);}}
Effect.Round=Class.create();Effect.Round.prototype={initialize:function(tagName,className,options){var elements=document.getElementsByTagAndClassName(tagName,className);for(var i=0;i<elements.length;i++)
Rico.Corner.round(elements[i],options);}};Effect.FadeTo=Class.create();Effect.FadeTo.prototype={initialize:function(element,opacity,duration,steps,options){this.element=$(element);this.opacity=opacity;this.duration=duration;this.steps=steps;this.options=arguments[4]||{};this.fadeTo();},fadeTo:function(){if(this.isFinished()){if(this.options.complete)this.options.complete(this);return;}
if(this.timer)
clearTimeout(this.timer);var stepDuration=Math.round(this.duration/this.steps);var currentOpacity=this.getElementOpacity();var delta=this.steps>0?(this.opacity-currentOpacity)/this.steps:0;this.changeOpacityBy(delta);this.duration-=stepDuration;this.steps--;this.timer=setTimeout(this.fadeTo.bind(this),stepDuration);},changeOpacityBy:function(v){var currentOpacity=this.getElementOpacity();var newOpacity=Math.max(0,Math.min(currentOpacity+v,1));this.element.ricoOpacity=newOpacity;this.element.style.filter="alpha(opacity:"+Math.round(newOpacity*100)+")";this.element.style.opacity=newOpacity;;},isFinished:function(){return this.steps<=0;},getElementOpacity:function(){if(this.element.ricoOpacity==undefined){var opacity=RicoUtil.getElementsComputedStyle(this.element,'opacity');this.element.ricoOpacity=opacity!=undefined?opacity:1.0;}
return parseFloat(this.element.ricoOpacity);}}
Effect.AccordionSize=Class.create();Effect.AccordionSize.prototype={initialize:function(e1,e2,start,end,duration,steps,options){this.e1=$(e1);this.e2=$(e2);this.start=start;this.end=end;this.duration=duration;this.steps=steps;this.options=arguments[6]||{};this.accordionSize();},accordionSize:function(){if(this.isFinished()){this.e1.style.height=this.start+"px";this.e2.style.height=this.end+"px";if(this.options.complete)
this.options.complete(this);return;}
if(this.timer)
clearTimeout(this.timer);var stepDuration=Math.round(this.duration/this.steps);var diff=this.steps>0?(parseInt(this.e1.offsetHeight)-this.start)/this.steps:0;this.resizeBy(diff);this.duration-=stepDuration;this.steps--;this.timer=setTimeout(this.accordionSize.bind(this),stepDuration);},isFinished:function(){return this.steps<=0;},resizeBy:function(diff){var h1Height=this.e1.offsetHeight;var h2Height=this.e2.offsetHeight;var intDiff=parseInt(diff);if(diff!=0){this.e1.style.height=(h1Height-intDiff)+"px";this.e2.style.height=(h2Height+intDiff)+"px";}}};if(window.Effect==undefined)
Effect={};Effect.SizeAndPosition=Class.create();Effect.SizeAndPosition.prototype={initialize:function(element,x,y,w,h,duration,steps,options){this.element=$(element);this.x=x;this.y=y;this.w=w;this.h=h;this.duration=duration;this.steps=steps;this.options=arguments[7]||{};this.sizeAndPosition();},sizeAndPosition:function(){if(this.isFinished()){if(this.options.complete)this.options.complete(this);return;}
if(this.timer)
clearTimeout(this.timer);var stepDuration=Math.round(this.duration/this.steps);var currentX=this.element.offsetLeft;var currentY=this.element.offsetTop;var currentW=this.element.offsetWidth;var currentH=this.element.offsetHeight;this.x=(this.x)?this.x:currentX;this.y=(this.y)?this.y:currentY;this.w=(this.w)?this.w:currentW;this.h=(this.h)?this.h:currentH;var difX=this.steps>0?(this.x-currentX)/this.steps:0;var difY=this.steps>0?(this.y-currentY)/this.steps:0;var difW=this.steps>0?(this.w-currentW)/this.steps:0;var difH=this.steps>0?(this.h-currentH)/this.steps:0;this.moveBy(difX,difY);this.resizeBy(difW,difH);this.duration-=stepDuration;this.steps--;this.timer=setTimeout(this.sizeAndPosition.bind(this),stepDuration);},isFinished:function(){return this.steps<=0;},moveBy:function(difX,difY){var currentLeft=this.element.offsetLeft;var currentTop=this.element.offsetTop;var intDifX=parseInt(difX);var intDifY=parseInt(difY);var style=this.element.style;if(intDifX!=0)
style.left=(currentLeft+intDifX)+"px";if(intDifY!=0)
style.top=(currentTop+intDifY)+"px";},resizeBy:function(difW,difH){var currentWidth=this.element.offsetWidth;var currentHeight=this.element.offsetHeight;var intDifW=parseInt(difW);var intDifH=parseInt(difH);var style=this.element.style;if(intDifW!=0)
style.width=(currentWidth+intDifW)+"px";if(intDifH!=0)
style.height=(currentHeight+intDifH)+"px";}}
Effect.Size=Class.create();Effect.Size.prototype={initialize:function(element,w,h,duration,steps,options){new Effect.SizeAndPosition(element,null,null,w,h,duration,steps,options);}}
Effect.Position=Class.create();Effect.Position.prototype={initialize:function(element,x,y,duration,steps,options){new Effect.SizeAndPosition(element,x,y,null,null,duration,steps,options);}}
Effect.Round=Class.create();Effect.Round.prototype={initialize:function(tagName,className,options){var elements=document.getElementsByTagAndClassName(tagName,className);for(var i=0;i<elements.length;i++)
Rico.Corner.round(elements[i],options);}};Effect.FadeTo=Class.create();Effect.FadeTo.prototype={initialize:function(element,opacity,duration,steps,options){this.element=$(element);this.opacity=opacity;this.duration=duration;this.steps=steps;this.options=arguments[4]||{};this.fadeTo();},fadeTo:function(){if(this.isFinished()){if(this.options.complete)this.options.complete(this);return;}
if(this.timer)
clearTimeout(this.timer);var stepDuration=Math.round(this.duration/this.steps);var currentOpacity=this.getElementOpacity();var delta=this.steps>0?(this.opacity-currentOpacity)/this.steps:0;this.changeOpacityBy(delta);this.duration-=stepDuration;this.steps--;this.timer=setTimeout(this.fadeTo.bind(this),stepDuration);},changeOpacityBy:function(v){var currentOpacity=this.getElementOpacity();var newOpacity=Math.max(0,Math.min(currentOpacity+v,1));this.element.ricoOpacity=newOpacity;this.element.style.filter="alpha(opacity:"+Math.round(newOpacity*100)+")";this.element.style.opacity=newOpacity;;},isFinished:function(){return this.steps<=0;},getElementOpacity:function(){if(this.element.ricoOpacity==undefined){var opacity=RicoUtil.getElementsComputedStyle(this.element,'opacity');this.element.ricoOpacity=opacity!=undefined?opacity:1.0;}
return parseFloat(this.element.ricoOpacity);}}
Effect.AccordionSize=Class.create();Effect.AccordionSize.prototype={initialize:function(e1,e2,start,end,duration,steps,options){this.e1=$(e1);this.e2=$(e2);this.start=start;this.end=end;this.duration=duration;this.steps=steps;this.options=arguments[6]||{};this.accordionSize();},accordionSize:function(){if(this.isFinished()){this.e1.style.height=this.start+"px";this.e2.style.height=this.end+"px";if(this.options.complete)
this.options.complete(this);return;}
if(this.timer)
clearTimeout(this.timer);var stepDuration=Math.round(this.duration/this.steps);var diff=this.steps>0?(parseInt(this.e1.offsetHeight)-this.start)/this.steps:0;this.resizeBy(diff);this.duration-=stepDuration;this.steps--;this.timer=setTimeout(this.accordionSize.bind(this),stepDuration);},isFinished:function(){return this.steps<=0;},resizeBy:function(diff){var h1Height=this.e1.offsetHeight;var h2Height=this.e2.offsetHeight;var intDiff=parseInt(diff);if(diff!=0){this.e1.style.height=(h1Height-intDiff)+"px";this.e2.style.height=(h2Height+intDiff)+"px";}}};Rico.LiveGridMetaData=Class.create();Rico.LiveGridMetaData.prototype={initialize:function(pageSize,totalRows,columnCount,options){this.pageSize=pageSize;this.totalRows=totalRows;this.setOptions(options);this.ArrowHeight=16;this.columnCount=columnCount;},setOptions:function(options){this.options={largeBufferSize:7.0,nearLimitFactor:0.2};Object.extend(this.options,options||{});},getPageSize:function(){return this.pageSize;},getTotalRows:function(){return this.totalRows;},setTotalRows:function(n){this.totalRows=n;},getLargeBufferSize:function(){return parseInt(this.options.largeBufferSize*this.pageSize);},getLimitTolerance:function(){return parseInt(this.getLargeBufferSize()*this.options.nearLimitFactor);}};Rico.LiveGridScroller=Class.create();Rico.LiveGridScroller.prototype={initialize:function(liveGrid,viewPort){this.isIE=navigator.userAgent.toLowerCase().indexOf("msie")>=0;this.liveGrid=liveGrid;this.metaData=liveGrid.metaData;this.createScrollBar();this.scrollTimeout=null;this.lastScrollPos=0;this.viewPort=viewPort;this.rows=new Array();},isUnPlugged:function(){return this.scrollerDiv.onscroll==null;},plugin:function(){this.scrollerDiv.onscroll=this.handleScroll.bindAsEventListener(this);},unplug:function(){this.scrollerDiv.onscroll=null;},sizeIEHeaderHack:function(){if(!this.isIE)return;var headerTable=$(this.liveGrid.tableId+"_header");if(headerTable)
headerTable.rows[0].cells[0].style.width=(headerTable.rows[0].cells[0].offsetWidth+1)+"px";},createScrollBar:function(){var visibleHeight=this.liveGrid.viewPort.visibleHeight();this.scrollerDiv=document.createElement("div");var scrollerStyle=this.scrollerDiv.style;scrollerStyle.borderRight=this.liveGrid.options.scrollerBorderRight;scrollerStyle.position="relative";scrollerStyle.left=this.isIE?"-6px":"-3px";scrollerStyle.width="19px";scrollerStyle.height=visibleHeight+"px";scrollerStyle.overflow="auto";this.heightDiv=document.createElement("div");this.heightDiv.style.width="1px";this.heightDiv.style.height=parseInt(visibleHeight*this.metaData.getTotalRows()/this.metaData.getPageSize())+"px";this.scrollerDiv.appendChild(this.heightDiv);this.scrollerDiv.onscroll=this.handleScroll.bindAsEventListener(this);var table=this.liveGrid.table;table.parentNode.parentNode.insertBefore(this.scrollerDiv,table.parentNode.nextSibling);var eventName=this.isIE?"mousewheel":"DOMMouseScroll";Event.observe(table,eventName,function(evt){if(evt.wheelDelta>=0||evt.detail<0)
this.scrollerDiv.scrollTop-=(2*this.viewPort.rowHeight);else
this.scrollerDiv.scrollTop+=(2*this.viewPort.rowHeight);this.handleScroll(false);}.bindAsEventListener(this),false);},updateSize:function(){var table=this.liveGrid.table;var visibleHeight=this.viewPort.visibleHeight();this.heightDiv.style.height=parseInt(visibleHeight*this.metaData.getTotalRows()/this.metaData.getPageSize())+"px";},rowToPixel:function(rowOffset){return(rowOffset/this.metaData.getTotalRows())*this.heightDiv.offsetHeight},moveScroll:function(rowOffset){this.scrollerDiv.scrollTop=this.rowToPixel(rowOffset);if(this.metaData.options.onscroll)
this.metaData.options.onscroll(this.liveGrid,rowOffset);},handleScroll:function(){if(this.scrollTimeout)
clearTimeout(this.scrollTimeout);var scrollDiff=this.lastScrollPos-this.scrollerDiv.scrollTop;if(scrollDiff!=0.00){var r=this.scrollerDiv.scrollTop%this.viewPort.rowHeight;if(r!=0){this.unplug();if(scrollDiff<0){this.scrollerDiv.scrollTop+=(this.viewPort.rowHeight-r);}else{this.scrollerDiv.scrollTop-=r;}
this.plugin();}}
var contentOffset=parseInt(this.scrollerDiv.scrollTop/this.viewPort.rowHeight);this.liveGrid.requestContentRefresh(contentOffset);this.viewPort.scrollTo(this.scrollerDiv.scrollTop);if(this.metaData.options.onscroll)
this.metaData.options.onscroll(this.liveGrid,contentOffset);this.scrollTimeout=setTimeout(this.scrollIdle.bind(this),1200);this.lastScrollPos=this.scrollerDiv.scrollTop;},scrollIdle:function(){if(this.metaData.options.onscrollidle)
this.metaData.options.onscrollidle();}};Rico.LiveGridBuffer=Class.create();Rico.LiveGridBuffer.prototype={initialize:function(metaData,viewPort){this.startPos=0;this.size=0;this.metaData=metaData;this.rows=new Array();this.updateInProgress=false;this.viewPort=viewPort;this.maxBufferSize=metaData.getLargeBufferSize()*2;this.maxFetchSize=metaData.getLargeBufferSize();this.lastOffset=0;},getBlankRow:function(){if(!this.blankRow){this.blankRow=new Array();for(var i=0;i<this.metaData.columnCount;i++)
this.blankRow[i]="&nbsp;";}
return this.blankRow;},loadRows:function(ajaxResponse){var rowsElement=ajaxResponse.getElementsByTagName('rows')[0];this.updateUI=rowsElement.getAttribute("update_ui")=="true"
var newRows=new Array()
var trs=rowsElement.getElementsByTagName("tr");for(var i=0;i<trs.length;i++){var row=newRows[i]=new Array();var cells=trs[i].getElementsByTagName("td");for(var j=0;j<cells.length;j++){var cell=cells[j];var convertSpaces=cell.getAttribute("convert_spaces")=="true";var cellContent=RicoUtil.getContentAsString(cell);row[j]=convertSpaces?this.convertSpaces(cellContent):cellContent;if(!row[j])
row[j]='&nbsp;';}}
return newRows;},update:function(ajaxResponse,start){var newRows=this.loadRows(ajaxResponse);if(this.rows.length==0){this.rows=newRows;this.size=this.rows.length;this.startPos=start;return;}
if(start>this.startPos){if(this.startPos+this.rows.length<start){this.rows=newRows;this.startPos=start;}else{this.rows=this.rows.concat(newRows.slice(0,newRows.length));if(this.rows.length>this.maxBufferSize){var fullSize=this.rows.length;this.rows=this.rows.slice(this.rows.length-this.maxBufferSize,this.rows.length)
this.startPos=this.startPos+(fullSize-this.rows.length);}}}else{if(start+newRows.length<this.startPos){this.rows=newRows;}else{this.rows=newRows.slice(0,this.startPos).concat(this.rows);if(this.rows.length>this.maxBufferSize)
this.rows=this.rows.slice(0,this.maxBufferSize)}
this.startPos=start;}
this.size=this.rows.length;},clear:function(){this.rows=new Array();this.startPos=0;this.size=0;},isOverlapping:function(start,size){return((start<this.endPos())&&(this.startPos<start+size))||(this.endPos()==0)},isInRange:function(position){return(position>=this.startPos)&&(position+this.metaData.getPageSize()<=this.endPos());},isNearingTopLimit:function(position){return position-this.startPos<this.metaData.getLimitTolerance();},endPos:function(){return this.startPos+this.rows.length;},isNearingBottomLimit:function(position){return this.endPos()-(position+this.metaData.getPageSize())<this.metaData.getLimitTolerance();},isAtTop:function(){return this.startPos==0;},isAtBottom:function(){return this.endPos()==this.metaData.getTotalRows();},isNearingLimit:function(position){return(!this.isAtTop()&&this.isNearingTopLimit(position))||(!this.isAtBottom()&&this.isNearingBottomLimit(position))},getFetchSize:function(offset){var adjustedOffset=this.getFetchOffset(offset);var adjustedSize=0;if(adjustedOffset>=this.startPos){var endFetchOffset=this.maxFetchSize+adjustedOffset;if(endFetchOffset>this.metaData.totalRows)
endFetchOffset=this.metaData.totalRows;adjustedSize=endFetchOffset-adjustedOffset;if(adjustedOffset==0&&adjustedSize<this.maxFetchSize){adjustedSize=this.maxFetchSize;}}else{var adjustedSize=this.startPos-adjustedOffset;if(adjustedSize>this.maxFetchSize)
adjustedSize=this.maxFetchSize;}
return adjustedSize;},getFetchOffset:function(offset){var adjustedOffset=offset;if(offset>this.startPos)
adjustedOffset=(offset>this.endPos())?offset:this.endPos();else{if(offset+this.maxFetchSize>=this.startPos){var adjustedOffset=this.startPos-this.maxFetchSize;if(adjustedOffset<0)
adjustedOffset=0;}}
this.lastOffset=adjustedOffset;return adjustedOffset;},getRows:function(start,count){var begPos=start-this.startPos
var endPos=begPos+count
if(endPos>this.size)
endPos=this.size
var results=new Array()
var index=0;for(var i=begPos;i<endPos;i++){results[index++]=this.rows[i]}
return results},convertSpaces:function(s){return s.split(" ").join("&nbsp;");}};Rico.GridViewPort=Class.create();Rico.GridViewPort.prototype={initialize:function(table,rowHeight,visibleRows,buffer,liveGrid){this.lastDisplayedStartPos=0;this.div=table.parentNode;this.table=table
this.rowHeight=rowHeight;this.div.style.height=this.rowHeight*visibleRows;this.div.style.overflow="hidden";this.buffer=buffer;this.liveGrid=liveGrid;this.visibleRows=visibleRows+1;this.lastPixelOffset=0;this.startPos=0;},populateRow:function(htmlRow,row){for(var j=0;j<row.length;j++){htmlRow.cells[j].innerHTML=row[j]}},bufferChanged:function(){this.refreshContents(parseInt(this.lastPixelOffset/this.rowHeight));},clearRows:function(){if(!this.isBlank){this.liveGrid.table.className=this.liveGrid.options.loadingClass;for(var i=0;i<this.visibleRows;i++)
this.populateRow(this.table.rows[i],this.buffer.getBlankRow());this.isBlank=true;}},clearContents:function(){this.clearRows();this.scrollTo(0);this.startPos=0;this.lastStartPos=-1;},refreshContents:function(startPos){if(startPos==this.lastRowPos&&!this.isPartialBlank&&!this.isBlank){return;}
if((startPos+this.visibleRows<this.buffer.startPos)||(this.buffer.startPos+this.buffer.size<startPos)||(this.buffer.size==0)){this.clearRows();return;}
this.isBlank=false;var viewPrecedesBuffer=this.buffer.startPos>startPos
var contentStartPos=viewPrecedesBuffer?this.buffer.startPos:startPos;var contentEndPos=(this.buffer.startPos+this.buffer.size<startPos+this.visibleRows)?this.buffer.startPos+this.buffer.size:startPos+this.visibleRows;var rowSize=contentEndPos-contentStartPos;var rows=this.buffer.getRows(contentStartPos,rowSize);var blankSize=this.visibleRows-rowSize;var blankOffset=viewPrecedesBuffer?0:rowSize;var contentOffset=viewPrecedesBuffer?blankSize:0;for(var i=0;i<rows.length;i++){this.populateRow(this.table.rows[i+contentOffset],rows[i]);}
for(var i=0;i<blankSize;i++){this.populateRow(this.table.rows[i+blankOffset],this.buffer.getBlankRow());}
this.isPartialBlank=blankSize>0;this.lastRowPos=startPos;this.liveGrid.table.className=this.liveGrid.options.tableClass;var onRefreshComplete=this.liveGrid.options.onRefreshComplete;if(onRefreshComplete!=null)
onRefreshComplete();},scrollTo:function(pixelOffset){if(this.lastPixelOffset==pixelOffset)
return;this.refreshContents(parseInt(pixelOffset/this.rowHeight))
this.div.scrollTop=pixelOffset%this.rowHeight
this.lastPixelOffset=pixelOffset;},visibleHeight:function(){return parseInt(RicoUtil.getElementsComputedStyle(this.div,'height'));}};Rico.LiveGridRequest=Class.create();Rico.LiveGridRequest.prototype={initialize:function(requestOffset,options){this.requestOffset=requestOffset;}};Rico.LiveGrid=Class.create();Rico.LiveGrid.prototype={initialize:function(tableId,visibleRows,totalRows,url,options,ajaxOptions){this.options={tableClass:$(tableId).className,loadingClass:$(tableId).className,scrollerBorderRight:'1px solid #ababab',bufferTimeout:20000,sortAscendImg:'images/sort_asc.gif',sortDescendImg:'images/sort_desc.gif',sortImageWidth:9,sortImageHeight:5,ajaxSortURLParms:[],onRefreshComplete:null,requestParameters:null,inlineStyles:true};Object.extend(this.options,options||{});this.ajaxOptions={parameters:null};Object.extend(this.ajaxOptions,ajaxOptions||{});this.tableId=tableId;this.table=$(tableId);this.addLiveGridHtml();var columnCount=this.table.rows[0].cells.length;this.metaData=new Rico.LiveGridMetaData(visibleRows,totalRows,columnCount,options);this.buffer=new Rico.LiveGridBuffer(this.metaData);var rowCount=this.table.rows.length;this.viewPort=new Rico.GridViewPort(this.table,this.table.offsetHeight/rowCount,visibleRows,this.buffer,this);this.scroller=new Rico.LiveGridScroller(this,this.viewPort);this.options.sortHandler=this.sortHandler.bind(this);if($(tableId+'_header'))
this.sort=new Rico.LiveGridSort(tableId+'_header',this.options)
this.processingRequest=null;this.unprocessedRequest=null;this.initAjax(url);if(this.options.prefetchBuffer||this.options.prefetchOffset>0){var offset=0;if(this.options.offset){offset=this.options.offset;this.scroller.moveScroll(offset);this.viewPort.scrollTo(this.scroller.rowToPixel(offset));}
if(this.options.sortCol){this.sortCol=options.sortCol;this.sortDir=options.sortDir;}
this.requestContentRefresh(offset);}},addLiveGridHtml:function(){if(this.table.getElementsByTagName("thead").length>0){var tableHeader=this.table.cloneNode(true);tableHeader.setAttribute('id',this.tableId+'_header');tableHeader.setAttribute('class',this.table.className+'_header');for(var i=0;i<tableHeader.tBodies.length;i++)
tableHeader.removeChild(tableHeader.tBodies[i]);this.table.deleteTHead();this.table.parentNode.insertBefore(tableHeader,this.table);}
new Insertion.Before(this.table,"<div id='"+this.tableId+"_container'></div>");this.table.previousSibling.appendChild(this.table);new Insertion.Before(this.table,"<div id='"+this.tableId+"_viewport' style='float:left;'></div>");this.table.previousSibling.appendChild(this.table);},resetContents:function(){this.scroller.moveScroll(0);this.buffer.clear();this.viewPort.clearContents();},sortHandler:function(column){this.sortCol=column.name;this.sortDir=column.currentSort;this.resetContents();this.requestContentRefresh(0)},setTotalRows:function(newTotalRows){this.resetContents();this.metaData.setTotalRows(newTotalRows);this.scroller.updateSize();},initAjax:function(url){ajaxEngine.registerRequest(this.tableId+'_request',url);ajaxEngine.registerAjaxObject(this.tableId+'_updater',this);},invokeAjax:function(){},handleTimedOut:function(){this.processingRequest=null;this.processQueuedRequest();},fetchBuffer:function(offset){if(this.buffer.isInRange(offset)&&!this.buffer.isNearingLimit(offset)){return;}
if(this.processingRequest){this.unprocessedRequest=new Rico.LiveGridRequest(offset);return;}
var bufferStartPos=this.buffer.getFetchOffset(offset);this.processingRequest=new Rico.LiveGridRequest(offset);this.processingRequest.bufferOffset=bufferStartPos;var fetchSize=this.buffer.getFetchSize(offset);var partialLoaded=false;var queryString
if(this.options.requestParameters)
queryString=this._createQueryString(this.options.requestParameters,0);queryString=(queryString==null)?'':queryString+'&';queryString=queryString+'id='+this.tableId+'&page_size='+fetchSize+'&offset='+bufferStartPos;if(this.sortCol)
queryString=queryString+'&sort_col='+escape(this.sortCol)+'&sort_dir='+this.sortDir;this.ajaxOptions.parameters=queryString;ajaxEngine.sendRequest(this.tableId+'_request',this.ajaxOptions);this.timeoutHandler=setTimeout(this.handleTimedOut.bind(this),this.options.bufferTimeout);},setRequestParams:function(){this.options.requestParameters=[];for(var i=0;i<arguments.length;i++)
this.options.requestParameters[i]=arguments[i];},requestContentRefresh:function(contentOffset){this.fetchBuffer(contentOffset);},ajaxUpdate:function(ajaxResponse){try{clearTimeout(this.timeoutHandler);this.buffer.update(ajaxResponse,this.processingRequest.bufferOffset);this.viewPort.bufferChanged();}
catch(err){}
finally{this.processingRequest=null;}
this.processQueuedRequest();},_createQueryString:function(theArgs,offset){var queryString=""
if(!theArgs)
return queryString;for(var i=offset;i<theArgs.length;i++){if(i!=offset)
queryString+="&";var anArg=theArgs[i];if(anArg.name!=undefined&&anArg.value!=undefined){queryString+=anArg.name+"="+escape(anArg.value);}
else{var ePos=anArg.indexOf('=');var argName=anArg.substring(0,ePos);var argValue=anArg.substring(ePos+1);queryString+=argName+"="+escape(argValue);}}
return queryString;},processQueuedRequest:function(){if(this.unprocessedRequest!=null){this.requestContentRefresh(this.unprocessedRequest.requestOffset);this.unprocessedRequest=null}}};Rico.LiveGridSort=Class.create();Rico.LiveGridSort.prototype={initialize:function(headerTableId,options){this.headerTableId=headerTableId;this.headerTable=$(headerTableId);this.options=options;this.setOptions();this.applySortBehavior();if(this.options.sortCol){this.setSortUI(this.options.sortCol,this.options.sortDir);}},setSortUI:function(columnName,sortDirection){var cols=this.options.columns;for(var i=0;i<cols.length;i++){if(cols[i].name==columnName){this.setColumnSort(i,sortDirection);break;}}},setOptions:function(){new Image().src=this.options.sortAscendImg;new Image().src=this.options.sortDescendImg;this.sort=this.options.sortHandler;if(!this.options.columns)
this.options.columns=this.introspectForColumnInfo();else{this.options.columns=this.convertToTableColumns(this.options.columns);}},applySortBehavior:function(){var headerRow=this.headerTable.rows[0];var headerCells=headerRow.cells;for(var i=0;i<headerCells.length;i++){this.addSortBehaviorToColumn(i,headerCells[i]);}},addSortBehaviorToColumn:function(n,cell){if(this.options.columns[n].isSortable()){cell.id=this.headerTableId+'_'+n;cell.style.cursor='pointer';cell.onclick=this.headerCellClicked.bindAsEventListener(this);cell.innerHTML=cell.innerHTML+'<span id="'+this.headerTableId+'_img_'+n+'">'
+'&nbsp;&nbsp;&nbsp;</span>';}},headerCellClicked:function(evt){var eventTarget=evt.target?evt.target:evt.srcElement;var cellId=eventTarget.id;var columnNumber=parseInt(cellId.substring(cellId.lastIndexOf('_')+1));var sortedColumnIndex=this.getSortedColumnIndex();if(sortedColumnIndex!=-1){if(sortedColumnIndex!=columnNumber){this.removeColumnSort(sortedColumnIndex);this.setColumnSort(columnNumber,Rico.TableColumn.SORT_ASC);}
else
this.toggleColumnSort(sortedColumnIndex);}
else
this.setColumnSort(columnNumber,Rico.TableColumn.SORT_ASC);if(this.options.sortHandler){this.options.sortHandler(this.options.columns[columnNumber]);}},removeColumnSort:function(n){this.options.columns[n].setUnsorted();this.setSortImage(n);},setColumnSort:function(n,direction){this.options.columns[n].setSorted(direction);this.setSortImage(n);},toggleColumnSort:function(n){this.options.columns[n].toggleSort();this.setSortImage(n);},setSortImage:function(n){var sortDirection=this.options.columns[n].getSortDirection();var sortImageSpan=$(this.headerTableId+'_img_'+n);if(sortDirection==Rico.TableColumn.UNSORTED)
sortImageSpan.innerHTML='&nbsp;&nbsp;';else if(sortDirection==Rico.TableColumn.SORT_ASC)
sortImageSpan.innerHTML='&nbsp;&nbsp;<img width="'+this.options.sortImageWidth+'" '+'height="'+this.options.sortImageHeight+'" '+'src="'+this.options.sortAscendImg+'"/>';else if(sortDirection==Rico.TableColumn.SORT_DESC)
sortImageSpan.innerHTML='&nbsp;&nbsp;<img width="'+this.options.sortImageWidth+'" '+'height="'+this.options.sortImageHeight+'" '+'src="'+this.options.sortDescendImg+'"/>';},getSortedColumnIndex:function(){var cols=this.options.columns;for(var i=0;i<cols.length;i++){if(cols[i].isSorted())
return i;}
return-1;},introspectForColumnInfo:function(){var columns=new Array();var headerRow=this.headerTable.rows[0];var headerCells=headerRow.cells;for(var i=0;i<headerCells.length;i++)
columns.push(new Rico.TableColumn(this.deriveColumnNameFromCell(headerCells[i],i),true));return columns;},convertToTableColumns:function(cols){var columns=new Array();for(var i=0;i<cols.length;i++)
columns.push(new Rico.TableColumn(cols[i][0],cols[i][1]));return columns;},deriveColumnNameFromCell:function(cell,columnNumber){var cellContent=cell.innerText!=undefined?cell.innerText:cell.textContent;return cellContent?cellContent.toLowerCase().split(' ').join('_'):"col_"+columnNumber;}};Rico.TableColumn=Class.create();Rico.TableColumn.UNSORTED=0;Rico.TableColumn.SORT_ASC="ASC";Rico.TableColumn.SORT_DESC="DESC";Rico.TableColumn.prototype={initialize:function(name,sortable){this.name=name;this.sortable=sortable;this.currentSort=Rico.TableColumn.UNSORTED;},isSortable:function(){return this.sortable;},isSorted:function(){return this.currentSort!=Rico.TableColumn.UNSORTED;},getSortDirection:function(){return this.currentSort;},toggleSort:function(){if(this.currentSort==Rico.TableColumn.UNSORTED||this.currentSort==Rico.TableColumn.SORT_DESC)
this.currentSort=Rico.TableColumn.SORT_ASC;else if(this.currentSort==Rico.TableColumn.SORT_ASC)
this.currentSort=Rico.TableColumn.SORT_DESC;},setUnsorted:function(direction){this.setSorted(Rico.TableColumn.UNSORTED);},setSorted:function(direction){this.currentSort=direction;}};Rico.ArrayExtensions=new Array();if(Object.prototype.extend){Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Object.prototype.extend;}else{Object.prototype.extend=function(object){return Object.extend.apply(this,[this,object]);}
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Object.prototype.extend;}
if(Array.prototype.push){Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.push;}
if(!Array.prototype.remove){Array.prototype.remove=function(dx){if(isNaN(dx)||dx>this.length)
return false;for(var i=0,n=0;i<this.length;i++)
if(i!=dx)
this[n++]=this[i];this.length-=1;};Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.remove;}
if(!Array.prototype.removeItem){Array.prototype.removeItem=function(item){for(var i=0;i<this.length;i++)
if(this[i]==item){this.remove(i);break;}};Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.removeItem;}
if(!Array.prototype.indices){Array.prototype.indices=function(){var indexArray=new Array();for(index in this){var ignoreThis=false;for(var i=0;i<Rico.ArrayExtensions.length;i++){if(this[index]==Rico.ArrayExtensions[i]){ignoreThis=true;break;}}
if(!ignoreThis)
indexArray[indexArray.length]=index;}
return indexArray;}
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.indices;}
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.unique;Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.inArray;if(window.DOMParser&&window.XMLSerializer&&window.Node&&Node.prototype&&Node.prototype.__defineGetter__){if(!Document.prototype.loadXML){Document.prototype.loadXML=function(s){var doc2=(new DOMParser()).parseFromString(s,"text/xml");while(this.hasChildNodes())
this.removeChild(this.lastChild);for(var i=0;i<doc2.childNodes.length;i++){this.appendChild(this.importNode(doc2.childNodes[i],true));}};}
Document.prototype.__defineGetter__("xml",function(){return(new XMLSerializer()).serializeToString(this);});}
document.getElementsByTagAndClassName=function(tagName,className){if(tagName==null)
tagName='*';var children=document.getElementsByTagName(tagName)||document.all;var elements=new Array();if(className==null)
return children;for(var i=0;i<children.length;i++){var child=children[i];var classNames=child.className.split(' ');for(var j=0;j<classNames.length;j++){if(classNames[j]==className){elements.push(child);break;}}}
return elements;}
var RicoUtil={getElementsComputedStyle:function(htmlElement,cssProperty,mozillaEquivalentCSS){if(arguments.length==2)
mozillaEquivalentCSS=cssProperty;var el=$(htmlElement);if(el.currentStyle)
return el.currentStyle[cssProperty];else
return document.defaultView.getComputedStyle(el,null).getPropertyValue(mozillaEquivalentCSS);},createXmlDocument:function(){if(document.implementation&&document.implementation.createDocument){var doc=document.implementation.createDocument("","",null);if(doc.readyState==null){doc.readyState=1;doc.addEventListener("load",function(){doc.readyState=4;if(typeof doc.onreadystatechange=="function")
doc.onreadystatechange();},false);}
return doc;}
if(window.ActiveXObject)
return Try.these(function(){return new ActiveXObject('MSXML2.DomDocument')},function(){return new ActiveXObject('Microsoft.DomDocument')},function(){return new ActiveXObject('MSXML.DomDocument')},function(){return new ActiveXObject('MSXML3.DomDocument')})||false;return null;},getContentAsString:function(parentNode){return parentNode.xml!=undefined?this._getContentAsStringIE(parentNode):this._getContentAsStringMozilla(parentNode);},_getContentAsStringIE:function(parentNode){var contentStr="";for(var i=0;i<parentNode.childNodes.length;i++){var n=parentNode.childNodes[i];if(n.nodeType==4){contentStr+=n.nodeValue;}
else{contentStr+=n.xml;}}
return contentStr;},_getContentAsStringMozilla:function(parentNode){var xmlSerializer=new XMLSerializer();var contentStr="";for(var i=0;i<parentNode.childNodes.length;i++){var n=parentNode.childNodes[i];if(n.nodeType==4){contentStr+=n.nodeValue;}
else{contentStr+=xmlSerializer.serializeToString(n);}}
return contentStr;},toViewportPosition:function(element){return this._toAbsolute(element,true);},toDocumentPosition:function(element){return this._toAbsolute(element,false);},_toAbsolute:function(element,accountForDocScroll){if(navigator.userAgent.toLowerCase().indexOf("msie")==-1)
return this._toAbsoluteMozilla(element,accountForDocScroll);var x=0;var y=0;var parent=element;while(parent){var borderXOffset=0;var borderYOffset=0;if(parent!=element){var borderXOffset=parseInt(this.getElementsComputedStyle(parent,"borderLeftWidth"));var borderYOffset=parseInt(this.getElementsComputedStyle(parent,"borderTopWidth"));borderXOffset=isNaN(borderXOffset)?0:borderXOffset;borderYOffset=isNaN(borderYOffset)?0:borderYOffset;}
x+=parent.offsetLeft-parent.scrollLeft+borderXOffset;y+=parent.offsetTop-parent.scrollTop+borderYOffset;parent=parent.offsetParent;}
if(accountForDocScroll){x-=this.docScrollLeft();y-=this.docScrollTop();}
return{x:x,y:y};},_toAbsoluteMozilla:function(element,accountForDocScroll){var x=0;var y=0;var parent=element;while(parent){x+=parent.offsetLeft;y+=parent.offsetTop;parent=parent.offsetParent;}
parent=element;while(parent&&parent!=document.body&&parent!=document.documentElement){if(parent.scrollLeft)
x-=parent.scrollLeft;if(parent.scrollTop)
y-=parent.scrollTop;parent=parent.parentNode;}
if(accountForDocScroll){x-=this.docScrollLeft();y-=this.docScrollTop();}
return{x:x,y:y};},docScrollLeft:function(){if(window.pageXOffset)
return window.pageXOffset;else if(document.documentElement&&document.documentElement.scrollLeft)
return document.documentElement.scrollLeft;else if(document.body)
return document.body.scrollLeft;else
return 0;},docScrollTop:function(){if(window.pageYOffset)
return window.pageYOffset;else if(document.documentElement&&document.documentElement.scrollTop)
return document.documentElement.scrollTop;else if(document.body)
return document.body.scrollTop;else
return 0;}};Prado.RicoLiveGrid=Class.create();Prado.RicoLiveGrid.prototype=Object.extend(Rico.LiveGrid.prototype,{initialize:function(tableId,options)
{this.options={tableClass:$(tableId).className||'',loadingClass:$(tableId).className||'',scrollerBorderRight:'1px solid #ababab',bufferTimeout:20000,sortAscendImg:'images/sort_asc.gif',sortDescendImg:'images/sort_desc.gif',sortImageWidth:9,sortImageHeight:5,ajaxSortURLParms:[],onRefreshComplete:null,requestParameters:null,inlineStyles:true,visibleRows:10,totalRows:0,initialOffset:0};Object.extend(this.options,options||{});this.tableId=tableId;this.table=$(tableId);this.addLiveGridHtml();var columnCount=this.table.rows[0].cells.length;this.metaData=new Rico.LiveGridMetaData(this.options.visibleRows,this.options.totalRows,columnCount,options);this.buffer=new Rico.LiveGridBuffer(this.metaData);var rowCount=this.table.rows.length;this.viewPort=new Rico.GridViewPort(this.table,this.table.offsetHeight/rowCount,this.options.visibleRows,this.buffer,this);this.scroller=new Rico.LiveGridScroller(this,this.viewPort);this.options.sortHandler=this.sortHandler.bind(this);if($(tableId+'_header'))
this.sort=new Rico.LiveGridSort(tableId+'_header',this.options)
this.processingRequest=null;this.unprocessedRequest=null;if(this.options.initialOffset>=0)
{var offset=this.options.initialOffset;this.scroller.moveScroll(offset);this.viewPort.scrollTo(this.scroller.rowToPixel(offset));if(this.options.sortCol){this.sortCol=options.sortCol;this.sortDir=options.sortDir;}
var grid=this;setTimeout(function(){grid.requestContentRefresh(offset);},100);}},fetchBuffer:function(offset)
{if(this.buffer.isInRange(offset)&&!this.buffer.isNearingLimit(offset)){return;}
if(this.processingRequest){this.unprocessedRequest=new Rico.LiveGridRequest(offset);return;}
var bufferStartPos=this.buffer.getFetchOffset(offset);this.processingRequest=new Rico.LiveGridRequest(offset);this.processingRequest.bufferOffset=bufferStartPos;var fetchSize=this.buffer.getFetchSize(offset);var partialLoaded=false;var param={'page_size':fetchSize,'offset':bufferStartPos};if(this.sortCol)
{Object.extend(param,{'sort_col':this.sortCol,'sort_dir':this.sortDir});}
Prado.Callback(this.tableId,param,this.ajaxUpdate.bind(this),this.options);this.timeoutHandler=setTimeout(this.handleTimedOut.bind(this),this.options.bufferTimeout);},ajaxUpdate:function(result,output)
{try{clearTimeout(this.timeoutHandler);this.buffer.update(result,this.processingRequest.bufferOffset);this.viewPort.bufferChanged();}
catch(err){}
finally{this.processingRequest=null;}
this.processQueuedRequest();}});Object.extend(Rico.LiveGridBuffer.prototype,{update:function(newRows,start)
{if(this.rows.length==0){this.rows=newRows;this.size=this.rows.length;this.startPos=start;return;}
if(start>this.startPos){if(this.startPos+this.rows.length<start){this.rows=newRows;this.startPos=start;}else{this.rows=this.rows.concat(newRows.slice(0,newRows.length));if(this.rows.length>this.maxBufferSize){var fullSize=this.rows.length;this.rows=this.rows.slice(this.rows.length-this.maxBufferSize,this.rows.length)
this.startPos=this.startPos+(fullSize-this.rows.length);}}}else{if(start+newRows.length<this.startPos){this.rows=newRows;}else{this.rows=newRows.slice(0,this.startPos).concat(this.rows);if(this.rows.length>this.maxBufferSize)
this.rows=this.rows.slice(0,this.maxBufferSize)}
this.startPos=start;}
this.size=this.rows.length;}});Object.extend(Rico.GridViewPort.prototype,{populateRow:function(htmlRow,row)
{if(isdef(htmlRow))
{for(var j=0;j<row.length;j++){htmlRow.cells[j].innerHTML=row[j]}}}});