 
var Rico = {
Version: '1.1rc1',
prototypeVersion: parseFloat(Prototype.Version.split(".")[0] + "." + Prototype.Version.split(".")[1])
}
Rico.ArrayExtensions = new Array();
if (Object.prototype.extend) {
 Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Object.prototype.extend;
}else{
Object.prototype.extend = function(object) {
return Object.extend.apply(this, [this, object]);
}
Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Object.prototype.extend;
}
if (Array.prototype.push) {
 Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Array.prototype.push;
}
if (!Array.prototype.remove) {
 Array.prototype.remove = function(dx) {
if( isNaN(dx) || dx > this.length )
 return false;
for( var i=0,n=0; i<this.length; i++ )
 if( i != dx )
this[n++]=this[i];
this.length-=1;
 };
Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Array.prototype.remove;
}
if (!Array.prototype.removeItem) {
 Array.prototype.removeItem = function(item) {
for ( var i = 0 ; i < this.length ; i++ )
 if ( this[i] == item ) {
this.remove(i);
break;
 }
 };
Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Array.prototype.removeItem;
}
if (!Array.prototype.indices) {
 Array.prototype.indices = function() {
var indexArray = new Array();
for ( index in this ) {
 var ignoreThis = false;
 for ( var i = 0 ; i < Rico.ArrayExtensions.length ; i++ ) {
if ( this[index] == Rico.ArrayExtensions[i] ) {
 ignoreThis = true;
 break;
}
 }
 if ( !ignoreThis )
indexArray[ indexArray.length ] = index;
}
return indexArray;
 }
Rico.ArrayExtensions[ Rico.ArrayExtensions.length ] = Array.prototype.indices;
}
if ( window.DOMParser &&
window.XMLSerializer &&
window.Node && Node.prototype && Node.prototype.__defineGetter__ ) {
if (!Document.prototype.loadXML) {
Document.prototype.loadXML = function (s) {
 var doc2 = (new DOMParser()).parseFromString(s, "text/xml");
 while (this.hasChildNodes())
this.removeChild(this.lastChild);
for (var i = 0; i < doc2.childNodes.length; i++) {
this.appendChild(this.importNode(doc2.childNodes[i], true));
 }
};
}
Document.prototype.__defineGetter__( "xml",
 function () {
 return (new XMLSerializer()).serializeToString(this);
 }
 );
}
document.getElementsByTagAndClassName = function(tagName, className) {
if ( tagName == null )
 tagName = '*';
var children = document.getElementsByTagName(tagName) || document.all;
var elements = new Array();
if ( className == null )
return children;
for (var i = 0; i < children.length; i++) {
var child = children[i];
var classNames = child.className.split(' ');
for (var j = 0; j < classNames.length; j++) {
if (classNames[j] == className) {
elements.push(child);
break;
}
}
}
return elements;
}
Rico.Accordion = Class.create();
Rico.Accordion.prototype = {
initialize: function(container, options) {
this.container= $(container);
this.lastExpandedTab= null;
this.accordionTabs= new Array();
this.setOptions(options);
this._attachBehaviors();
if(!container) return;
this.container.style.borderBottom = '1px solid ' + this.options.borderColor;
if (this.options.onLoadShowTab >= this.accordionTabs.length)
this.options.onLoadShowTab = 0;
for ( var i=0 ; i < this.accordionTabs.length ; i++ )
{
if (i != this.options.onLoadShowTab){
 this.accordionTabs[i].collapse();
 this.accordionTabs[i].content.style.display = 'none';
}
}
this.lastExpandedTab = this.accordionTabs[this.options.onLoadShowTab];
if (this.options.panelHeight == 'auto'){
var tabToCheck = (this.options.onloadShowTab === 0)? 1 : 0;
var titleBarSize = parseInt(RicoUtil.getElementsComputedStyle(this.accordionTabs[tabToCheck].titleBar, 'height'));
if (isNaN(titleBarSize))
titleBarSize = this.accordionTabs[tabToCheck].titleBar.offsetHeight;
var totalTitleBarSize = this.accordionTabs.length * titleBarSize;
var parentHeight = parseInt(RicoUtil.getElementsComputedStyle(this.container.parentNode, 'height'));
if (isNaN(parentHeight))
parentHeight = this.container.parentNode.offsetHeight;
this.options.panelHeight = parentHeight - totalTitleBarSize-2;
}
this.lastExpandedTab.content.style.height = this.options.panelHeight + "px";
this.lastExpandedTab.showExpanded();
this.lastExpandedTab.titleBar.style.fontWeight = this.options.expandedFontWeight;
},
setOptions: function(options) {
this.options = {
 expandedBg: '#63699c',
 hoverBg : '#63699c',
 collapsedBg : '#6b79a5',
 expandedTextColor : '#ffffff',
 expandedFontWeight: 'bold',
 hoverTextColor: '#ffffff',
 collapsedTextColor: '#ced7ef',
 collapsedFontWeight : 'normal',
 hoverTextColor: '#ffffff',
 borderColor : '#1f669b',
 panelHeight : 200,
 onHideTab : null,
 onShowTab : null,
 onLoadShowTab : 0
}
Object.extend(this.options, options || {});
 },
showTabByIndex: function( anIndex, animate ) {
var doAnimate = arguments.length == 1 ? true : animate;
this.showTab( this.accordionTabs[anIndex], doAnimate );
 },
showTab: function( accordionTab, animate ) {
var doAnimate = arguments.length == 1 ? true : animate;
if ( this.options.onHideTab )
 this.options.onHideTab(this.lastExpandedTab);
this.lastExpandedTab.showCollapsed();
var accordion = this;
var lastExpandedTab = this.lastExpandedTab;
this.lastExpandedTab.content.style.height = (this.options.panelHeight - 1) + 'px';
accordionTab.content.style.display = '';
accordionTab.titleBar.style.fontWeight = this.options.expandedFontWeight;
if ( doAnimate ) {
 new Effect.AccordionSize( this.lastExpandedTab.content,
 accordionTab.content,
 1,
 this.options.panelHeight,
 100, 10,
 { complete: function() {accordion.showTabDone(lastExpandedTab)} } );
 this.lastExpandedTab = accordionTab;
}
else {
 this.lastExpandedTab.content.style.height = "1px";
 accordionTab.content.style.height = this.options.panelHeight + "px";
 this.lastExpandedTab = accordionTab;
 this.showTabDone(lastExpandedTab);
}
 },
showTabDone: function(collapsedTab) {
collapsedTab.content.style.display = 'none';
this.lastExpandedTab.showExpanded();
if ( this.options.onShowTab )
 this.options.onShowTab(this.lastExpandedTab);
 },
_attachBehaviors: function() {
var panels = this._getDirectChildrenByTag(this.container, 'DIV');
for ( var i = 0 ; i < panels.length ; i++ ) {
var tabChildren = this._getDirectChildrenByTag(panels[i],'DIV');
 if ( tabChildren.length != 2 )
continue;
var tabTitleBar = tabChildren[0];
 var tabContentBox = tabChildren[1];
 this.accordionTabs.push( new Rico.Accordion.Tab(this,tabTitleBar,tabContentBox) );
}
 },
_getDirectChildrenByTag: function(e, tagName) {
var kids = new Array();
var allKids = e.childNodes;
for( var i = 0 ; i < allKids.length ; i++ )
 if ( allKids[i] && allKids[i].tagName && allKids[i].tagName == tagName )
kids.push(allKids[i]);
return kids;
 }
};
Rico.Accordion.Tab = Class.create();
Rico.Accordion.Tab.prototype = {
initialize: function(accordion, titleBar, content) {
this.accordion = accordion;
this.titleBar= titleBar;
this.content = content;
this._attachBehaviors();
 },
collapse: function() {
this.showCollapsed();
this.content.style.height = "1px";
 },
showCollapsed: function() {
this.expanded = false;
this.titleBar.style.backgroundColor = this.accordion.options.collapsedBg;
this.titleBar.style.color = this.accordion.options.collapsedTextColor;
this.titleBar.style.fontWeight= this.accordion.options.collapsedFontWeight;
this.content.style.overflow = "hidden";
 },
showExpanded: function() {
this.expanded = true;
this.titleBar.style.backgroundColor = this.accordion.options.expandedBg;
this.titleBar.style.color = this.accordion.options.expandedTextColor;
this.content.style.overflow = "visible";
 },
titleBarClicked: function(e) {
if ( this.accordion.lastExpandedTab == this )
 return;
this.accordion.showTab(this);
 },
hover: function(e) {
this.titleBar.style.backgroundColor = this.accordion.options.hoverBg;
this.titleBar.style.color = this.accordion.options.hoverTextColor;
 },
unhover: function(e) {
if ( this.expanded ) {
 this.titleBar.style.backgroundColor = this.accordion.options.expandedBg;
 this.titleBar.style.color = this.accordion.options.expandedTextColor;
}
else {
 this.titleBar.style.backgroundColor = this.accordion.options.collapsedBg;
 this.titleBar.style.color = this.accordion.options.collapsedTextColor;
}
 },
_attachBehaviors: function() {
this.content.style.border = "1px solid " + this.accordion.options.borderColor;
this.content.style.borderTopWidth= "0px";
this.content.style.borderBottomWidth = "0px";
this.content.style.margin= "0px";
this.titleBar.onclick = this.titleBarClicked.bindAsEventListener(this);
this.titleBar.onmouseover = this.hover.bindAsEventListener(this);
this.titleBar.onmouseout= this.unhover.bindAsEventListener(this);
 }
};
Rico.Corner = {
round: function(e, options) {
var e = $(e);
this._setOptions(options);
var color = this.options.color;
if ( this.options.color == "fromElement" )
 color = this._background(e);
var bgColor = this.options.bgColor;
if ( this.options.bgColor == "fromParent" )
 bgColor = this._background(e.offsetParent);
this._roundCornersImpl(e, color, bgColor);
 },
_roundCornersImpl: function(e, color, bgColor) {
if(this.options.border)
 this._renderBorder(e,bgColor);
if(this._isTopRounded())
 this._roundTopCorners(e,color,bgColor);
if(this._isBottomRounded())
 this._roundBottomCorners(e,color,bgColor);
 },
_renderBorder: function(el,bgColor) {
var borderValue = "1px solid " + this._borderColor(bgColor);
var borderL = "border-left: "+ borderValue;
var borderR = "border-right: " + borderValue;
var style = "style='" + borderL + ";" + borderR +"'";
el.innerHTML = "<div " + style + ">" + el.innerHTML + "</div>"
 },
_roundTopCorners: function(el, color, bgColor) {
var corner = this._createCorner(bgColor);
for(var i=0 ; i < this.options.numSlices ; i++ )
 corner.appendChild(this._createCornerSlice(color,bgColor,i,"top"));
el.style.paddingTop = 0;
el.insertBefore(corner,el.firstChild);
 },
_roundBottomCorners: function(el, color, bgColor) {
var corner = this._createCorner(bgColor);
for(var i=(this.options.numSlices-1) ; i >= 0 ; i-- )
 corner.appendChild(this._createCornerSlice(color,bgColor,i,"bottom"));
el.style.paddingBottom = 0;
el.appendChild(corner);
 },
_createCorner: function(bgColor) {
var corner = document.createElement("div");
corner.style.backgroundColor = (this._isTransparent() ? "transparent" : bgColor);
return corner;
 },
_createCornerSlice: function(color,bgColor, n, position) {
var slice = document.createElement("span");
var inStyle = slice.style;
inStyle.backgroundColor = color;
inStyle.display= "block";
inStyle.height = "1px";
inStyle.overflow = "hidden";
inStyle.fontSize = "1px";
var borderColor = this._borderColor(color,bgColor);
if ( this.options.border && n == 0 ) {
 inStyle.borderTopStyle= "solid";
 inStyle.borderTopWidth= "1px";
 inStyle.borderLeftWidth = "0px";
 inStyle.borderRightWidth= "0px";
 inStyle.borderBottomWidth = "0px";
 inStyle.height= "0px";
 inStyle.borderColor = borderColor;
}
else if(borderColor) {
 inStyle.borderColor = borderColor;
 inStyle.borderStyle = "solid";
 inStyle.borderWidth = "0px 1px";
}
if ( !this.options.compact && (n == (this.options.numSlices-1)) )
 inStyle.height = "2px";
this._setMargin(slice, n, position);
this._setBorder(slice, n, position);
return slice;
 },
_setOptions: function(options) {
this.options = {
 corners : "all",
 color : "fromElement",
 bgColor : "fromParent",
 blend : true,
 border: false,
 compact : false
}
Object.extend(this.options, options || {});
this.options.numSlices = this.options.compact ? 2 : 4;
if ( this._isTransparent() )
 this.options.blend = false;
 },
_whichSideTop: function() {
if ( this._hasString(this.options.corners, "all", "top") )
 return "";
if ( this.options.corners.indexOf("tl") >= 0 && this.options.corners.indexOf("tr") >= 0 )
 return "";
if (this.options.corners.indexOf("tl") >= 0)
 return "left";
else if (this.options.corners.indexOf("tr") >= 0)
return "right";
return "";
 },
_whichSideBottom: function() {
if ( this._hasString(this.options.corners, "all", "bottom") )
 return "";
if ( this.options.corners.indexOf("bl")>=0 && this.options.corners.indexOf("br")>=0 )
 return "";
if(this.options.corners.indexOf("bl") >=0)
 return "left";
else if(this.options.corners.indexOf("br")>=0)
 return "right";
return "";
 },
_borderColor : function(color,bgColor) {
if ( color == "transparent" )
 return bgColor;
else if ( this.options.border )
 return this.options.border;
else if ( this.options.blend )
 return this._blend( bgColor, color );
else
 return "";
 },
_setMargin: function(el, n, corners) {
var marginSize = this._marginSize(n);
var whichSide = corners == "top" ? this._whichSideTop() : this._whichSideBottom();
if ( whichSide == "left" ) {
 el.style.marginLeft = marginSize + "px"; el.style.marginRight = "0px";
}
else if ( whichSide == "right" ) {
 el.style.marginRight = marginSize + "px"; el.style.marginLeft= "0px";
}
else {
 el.style.marginLeft = marginSize + "px"; el.style.marginRight = marginSize + "px";
}
 },
_setBorder: function(el,n,corners) {
var borderSize = this._borderSize(n);
var whichSide = corners == "top" ? this._whichSideTop() : this._whichSideBottom();
if ( whichSide == "left" ) {
 el.style.borderLeftWidth = borderSize + "px"; el.style.borderRightWidth = "0px";
}
else if ( whichSide == "right" ) {
 el.style.borderRightWidth = borderSize + "px"; el.style.borderLeftWidth= "0px";
}
else {
 el.style.borderLeftWidth = borderSize + "px"; el.style.borderRightWidth = borderSize + "px";
}
if (this.options.border != false)
el.style.borderLeftWidth = borderSize + "px"; el.style.borderRightWidth = borderSize + "px";
 },
_marginSize: function(n) {
if ( this._isTransparent() )
 return 0;
var marginSizes= [ 5, 3, 2, 1 ];
var blendedMarginSizes = [ 3, 2, 1, 0 ];
var compactMarginSizes = [ 2, 1 ];
var smBlendedMarginSizes = [ 1, 0 ];
if ( this.options.compact && this.options.blend )
 return smBlendedMarginSizes[n];
else if ( this.options.compact )
 return compactMarginSizes[n];
else if ( this.options.blend )
 return blendedMarginSizes[n];
else
 return marginSizes[n];
 },
_borderSize: function(n) {
var transparentBorderSizes = [ 5, 3, 2, 1 ];
var blendedBorderSizes = [ 2, 1, 1, 1 ];
var compactBorderSizes = [ 1, 0 ];
var actualBorderSizes= [ 0, 2, 0, 0 ];
if ( this.options.compact && (this.options.blend || this._isTransparent()) )
 return 1;
else if ( this.options.compact )
 return compactBorderSizes[n];
else if ( this.options.blend )
 return blendedBorderSizes[n];
else if ( this.options.border )
 return actualBorderSizes[n];
else if ( this._isTransparent() )
 return transparentBorderSizes[n];
return 0;
 },
_hasString: function(str) { for(var i=1 ; i<arguments.length ; i++) if (str.indexOf(arguments[i]) >= 0) return true; return false; },
 _blend: function(c1, c2) { var cc1 = Rico.Color.createFromHex(c1); cc1.blend(Rico.Color.createFromHex(c2)); return cc1; },
 _background: function(el) { try { return Rico.Color.createColorFromBackground(el).asHex(); } catch(err) { return "#ffffff"; } },
 _isTransparent: function() { return this.options.color == "transparent"; },
 _isTopRounded: function() { return this._hasString(this.options.corners, "all", "top", "tl", "tr"); },
 _isBottomRounded: function() { return this._hasString(this.options.corners, "all", "bottom", "bl", "br"); },
 _hasSingleTextChild: function(el) { return el.childNodes.length == 1 && el.childNodes[0].nodeType == 3; }
}
if ( window.Effect == undefined )
 Effect = {};
Effect.SizeAndPosition = Class.create();
Effect.SizeAndPosition.prototype = {
initialize: function(element, x, y, w, h, duration, steps, options) {
this.element = $(element);
this.x = x;
this.y = y;
this.w = w;
this.h = h;
this.duration = duration;
this.steps= steps;
this.options= arguments[7] || {};
this.sizeAndPosition();
 },
sizeAndPosition: function() {
if (this.isFinished()) {
 if(this.options.complete) this.options.complete(this);
 return;
}
if (this.timer)
 clearTimeout(this.timer);
var stepDuration = Math.round(this.duration/this.steps) ;
var currentX = this.element.offsetLeft;
var currentY = this.element.offsetTop;
var currentW = this.element.offsetWidth;
var currentH = this.element.offsetHeight;
this.x = (this.x) ? this.x : currentX;
this.y = (this.y) ? this.y : currentY;
this.w = (this.w) ? this.w : currentW;
this.h = (this.h) ? this.h : currentH;
var difX = this.steps >0 ? (this.x - currentX)/this.steps : 0;
var difY = this.steps >0 ? (this.y - currentY)/this.steps : 0;
var difW = this.steps >0 ? (this.w - currentW)/this.steps : 0;
var difH = this.steps >0 ? (this.h - currentH)/this.steps : 0;
this.moveBy(difX, difY);
this.resizeBy(difW, difH);
this.duration -= stepDuration;
this.steps--;
this.timer = setTimeout(this.sizeAndPosition.bind(this), stepDuration);
 },
isFinished: function() {
return this.steps <= 0;
 },
moveBy: function( difX, difY ) {
var currentLeft = this.element.offsetLeft;
var currentTop= this.element.offsetTop;
var intDifX = parseInt(difX);
var intDifY = parseInt(difY);
var style = this.element.style;
if ( intDifX != 0 )
 style.left = (currentLeft + intDifX) + "px";
if ( intDifY != 0 )
 style.top= (currentTop + intDifY) + "px";
 },
resizeBy: function( difW, difH ) {
var currentWidth= this.element.offsetWidth;
var currentHeight = this.element.offsetHeight;
var intDifW = parseInt(difW);
var intDifH = parseInt(difH);
var style = this.element.style;
if ( intDifW != 0 )
 style.width = (currentWidth+ intDifW) + "px";
if ( intDifH != 0 )
 style.height= (currentHeight + intDifH) + "px";
 }
}
Effect.Size = Class.create();
Effect.Size.prototype = {
initialize: function(element, w, h, duration, steps, options) {
new Effect.SizeAndPosition(element, null, null, w, h, duration, steps, options);
}
}
Effect.Position = Class.create();
Effect.Position.prototype = {
initialize: function(element, x, y, duration, steps, options) {
new Effect.SizeAndPosition(element, x, y, null, null, duration, steps, options);
}
}
Effect.Round = Class.create();
Effect.Round.prototype = {
initialize: function(tagName, className, options) {
var elements = document.getElementsByTagAndClassName(tagName,className);
for ( var i = 0 ; i < elements.length ; i++ )
 Rico.Corner.round( elements[i], options );
 }
};
Effect.FadeTo = Class.create();
Effect.FadeTo.prototype = {
initialize: function( element, opacity, duration, steps, options) {
this.element= $(element);
this.opacity= opacity;
this.duration = duration;
this.steps= steps;
this.options= arguments[4] || {};
this.fadeTo();
 },
fadeTo: function() {
if (this.isFinished()) {
 if(this.options.complete) this.options.complete(this);
 return;
}
if (this.timer)
 clearTimeout(this.timer);
var stepDuration = Math.round(this.duration/this.steps) ;
var currentOpacity = this.getElementOpacity();
var delta = this.steps > 0 ? (this.opacity - currentOpacity)/this.steps : 0;
this.changeOpacityBy(delta);
this.duration -= stepDuration;
this.steps--;
this.timer = setTimeout(this.fadeTo.bind(this), stepDuration);
 },
changeOpacityBy: function(v) {
var currentOpacity = this.getElementOpacity();
var newOpacity = Math.max(0, Math.min(currentOpacity+v, 1));
this.element.ricoOpacity = newOpacity;
this.element.style.filter = "alpha(opacity:"+Math.round(newOpacity*100)+")";
this.element.style.opacity = newOpacity;
if ( window.Effect == undefined )
 Effect = {};
Effect.SizeAndPosition = Class.create();
Effect.SizeAndPosition.prototype = {
initialize: function(element, x, y, w, h, duration, steps, options) {
this.element = $(element);
this.x = x;
this.y = y;
this.w = w;
this.h = h;
this.duration = duration;
this.steps= steps;
this.options= arguments[7] || {};
this.sizeAndPosition();
 },
sizeAndPosition: function() {
if (this.isFinished()) {
 if(this.options.complete) this.options.complete(this);
 return;
}
if (this.timer)
 clearTimeout(this.timer);
var stepDuration = Math.round(this.duration/this.steps) ;
var currentX = this.element.offsetLeft;
var currentY = this.element.offsetTop;
var currentW = this.element.offsetWidth;
var currentH = this.element.offsetHeight;
this.x = (this.x) ? this.x : currentX;
this.y = (this.y) ? this.y : currentY;
this.w = (this.w) ? this.w : currentW;
this.h = (this.h) ? this.h : currentH;
var difX = this.steps >0 ? (this.x - currentX)/this.steps : 0;
var difY = this.steps >0 ? (this.y - currentY)/this.steps : 0;
var difW = this.steps >0 ? (this.w - currentW)/this.steps : 0;
var difH = this.steps >0 ? (this.h - currentH)/this.steps : 0;
this.moveBy(difX, difY);
this.resizeBy(difW, difH);
this.duration -= stepDuration;
this.steps--;
this.timer = setTimeout(this.sizeAndPosition.bind(this), stepDuration);
 },
isFinished: function() {
return this.steps <= 0;
 },
moveBy: function( difX, difY ) {
var currentLeft = this.element.offsetLeft;
var currentTop= this.element.offsetTop;
var intDifX = parseInt(difX);
var intDifY = parseInt(difY);
var style = this.element.style;
if ( intDifX != 0 )
 style.left = (currentLeft + intDifX) + "px";
if ( intDifY != 0 )
 style.top= (currentTop + intDifY) + "px";
 },
resizeBy: function( difW, difH ) {
var currentWidth= this.element.offsetWidth;
var currentHeight = this.element.offsetHeight;
var intDifW = parseInt(difW);
var intDifH = parseInt(difH);
var style = this.element.style;
if ( intDifW != 0 )
 style.width = (currentWidth+ intDifW) + "px";
if ( intDifH != 0 )
 style.height= (currentHeight + intDifH) + "px";
 }
}
Effect.Size = Class.create();
Effect.Size.prototype = {
initialize: function(element, w, h, duration, steps, options) {
new Effect.SizeAndPosition(element, null, null, w, h, duration, steps, options);
}
}
Effect.Position = Class.create();
Effect.Position.prototype = {
initialize: function(element, x, y, duration, steps, options) {
new Effect.SizeAndPosition(element, x, y, null, null, duration, steps, options);
}
}
Effect.Round = Class.create();
Effect.Round.prototype = {
initialize: function(tagName, className, options) {
var elements = document.getElementsByTagAndClassName(tagName,className);
for ( var i = 0 ; i < elements.length ; i++ )
 Rico.Corner.round( elements[i], options );
 }
};
Effect.FadeTo = Class.create();
Effect.FadeTo.prototype = {
initialize: function( element, opacity, duration, steps, options) {
this.element= $(element);
this.opacity= opacity;
this.duration = duration;
this.steps= steps;
this.options= arguments[4] || {};
this.fadeTo();
 },
fadeTo: function() {
if (this.isFinished()) {
 if(this.options.complete) this.options.complete(this);
 return;
}
if (this.timer)
 clearTimeout(this.timer);
var stepDuration = Math.round(this.duration/this.steps) ;
var currentOpacity = this.getElementOpacity();
var delta = this.steps > 0 ? (this.opacity - currentOpacity)/this.steps : 0;
this.changeOpacityBy(delta);
this.duration -= stepDuration;
this.steps--;
this.timer = setTimeout(this.fadeTo.bind(this), stepDuration);
 },
changeOpacityBy: function(v) {
var currentOpacity = this.getElementOpacity();
var newOpacity = Math.max(0, Math.min(currentOpacity+v, 1));
this.element.ricoOpacity = newOpacity;
this.element.style.filter = "alpha(opacity:"+Math.round(newOpacity*100)+")";
this.element.style.opacity = newOpacity;
 _toAbsolute: function(element,accountForDocScroll) {
if ( navigator.userAgent.toLowerCase().indexOf("msie") == -1 )
 return this._toAbsoluteMozilla(element,accountForDocScroll);
var x = 0;
var y = 0;
var parent = element;
while ( parent ) {
var borderXOffset = 0;
 var borderYOffset = 0;
 if ( parent != element ) {
var borderXOffset = parseInt(this.getElementsComputedStyle(parent, "borderLeftWidth" ));
var borderYOffset = parseInt(this.getElementsComputedStyle(parent, "borderTopWidth" ));
borderXOffset = isNaN(borderXOffset) ? 0 : borderXOffset;
borderYOffset = isNaN(borderYOffset) ? 0 : borderYOffset;
 }
x += parent.offsetLeft - parent.scrollLeft + borderXOffset;
 y += parent.offsetTop - parent.scrollTop + borderYOffset;
 parent = parent.offsetParent;
}
if ( accountForDocScroll ) {
 x -= this.docScrollLeft();
 y -= this.docScrollTop();
}
return { x:x, y:y };
 },
_toAbsoluteMozilla: function(element,accountForDocScroll) {
var x = 0;
var y = 0;
var parent = element;
while ( parent ) {
 x += parent.offsetLeft;
 y += parent.offsetTop;
 parent = parent.offsetParent;
}
parent = element;
while ( parent &&
parent != document.body &&
parent != document.documentElement ) {
 if ( parent.scrollLeft)
x -= parent.scrollLeft;
 if ( parent.scrollTop )
y -= parent.scrollTop;
 parent = parent.parentNode;
}
if ( accountForDocScroll ) {
 x -= this.docScrollLeft();
 y -= this.docScrollTop();
}
return { x:x, y:y };
 },
docScrollLeft: function() {
if ( window.pageXOffset )
 return window.pageXOffset;
else if ( document.documentElement && document.documentElement.scrollLeft )
 return document.documentElement.scrollLeft;
else if ( document.body )
 return document.body.scrollLeft;
else
 return 0;
 },
docScrollTop: function() {
if ( window.pageYOffset )
 return window.pageYOffset;
else if ( document.documentElement && document.documentElement.scrollTop )
 return document.documentElement.scrollTop;
else if ( document.body )
 return document.body.scrollTop;
else
 return 0;
 }
};
Prado.RicoLiveGrid = Class.create();
Prado.RicoLiveGrid.prototype = Object.extend(Rico.LiveGrid.prototype,
{
initialize : function(tableId, options)
{
 this.options = {
tableClass: $(tableId).className || '',
loadingClass: $(tableId).className || '',
scrollerBorderRight: '1px solid #ababab',
bufferTimeout:20000,
sortAscendImg:'images/sort_asc.gif',
sortDescendImg: 'images/sort_desc.gif',
sortImageWidth: 9,
sortImageHeight:5,
ajaxSortURLParms: [],
onRefreshComplete:null,
requestParameters:null,
inlineStyles: true,
visibleRows:10,
totalRows:0,
initialOffset:0
};
Object.extend(this.options, options || {});
this.tableId = tableId; 
this.table = $(tableId);
this.addLiveGridHtml();
var columnCount= this.table.rows[0].cells.length;
this.metaData= new Rico.LiveGridMetaData(this.options.visibleRows, this.options.totalRows, columnCount, options);
this.buffer= new Rico.LiveGridBuffer(this.metaData);
var rowCount = this.table.rows.length;
this.viewPort =new Rico.GridViewPort(this.table, 
this.table.offsetHeight/rowCount,
this.options.visibleRows,
this.buffer, this);
this.scroller= new Rico.LiveGridScroller(this,this.viewPort);
this.options.sortHandler = this.sortHandler.bind(this);
if ( $(tableId + '_header') )
 this.sort = new Rico.LiveGridSort(tableId + '_header', this.options)
this.processingRequest = null;
this.unprocessedRequest = null;
if (this.options.initialOffset >= 0) 
{
 var offset = this.options.initialOffset;
this.scroller.moveScroll(offset);
this.viewPort.scrollTo(this.scroller.rowToPixel(offset));
 if (this.options.sortCol) {
 this.sortCol = options.sortCol;
 this.sortDir = options.sortDir;
 }
 var grid = this;
 setTimeout(function(){
 grid.requestContentRefresh(offset);
 },100);
}
},
fetchBuffer: function(offset) 
 {
if ( this.buffer.isInRange(offset) &&
 !this.buffer.isNearingLimit(offset)) {
 return;
 }
if (this.processingRequest) {
this.unprocessedRequest = new Rico.LiveGridRequest(offset);
 return;
}
var bufferStartPos = this.buffer.getFetchOffset(offset);
this.processingRequest = new Rico.LiveGridRequest(offset);
this.processingRequest.bufferOffset = bufferStartPos; 
var fetchSize = this.buffer.getFetchSize(offset);
var partialLoaded = false;
var param = 
 {
'page_size' : fetchSize,
'offset' : bufferStartPos
 };
if(this.sortCol)
 {
Object.extend(param,
 {
'sort_col': this.sortCol,
'sort_dir': this.sortDir
});
 }
Prado.Callback(this.tableId, param, this.ajaxUpdate.bind(this), this.options);
 this.timeoutHandler = setTimeout( this.handleTimedOut.bind(this), this.options.bufferTimeout);
},
ajaxUpdate: function(result, output) 
 {
try {
 clearTimeout( this.timeoutHandler );
 this.buffer.update(result,this.processingRequest.bufferOffset);
 this.viewPort.bufferChanged();
}
catch(err) {}
finally {this.processingRequest = null; }
this.processQueuedRequest();
 }
});
Object.extend(Rico.LiveGridBuffer.prototype,
{
 update: function(newRows, start) 
{
 if (this.rows.length == 0) {
 this.rows = newRows;
 this.size = this.rows.length;
 this.startPos = start;
 return;
}
if (start > this.startPos) {
 if (this.startPos + this.rows.length < start) {
this.rows =newRows;
this.startPos = start; 
 } else {
this.rows = this.rows.concat( newRows.slice(0, newRows.length));
if (this.rows.length > this.maxBufferSize) {
 var fullSize = this.rows.length;
 this.rows = this.rows.slice(this.rows.length - this.maxBufferSize, this.rows.length)
 this.startPos = this.startPos +(fullSize - this.rows.length);
}
 }
} else {
 if (start + newRows.length < this.startPos) {
this.rows =newRows;
 } else {
this.rows = newRows.slice(0, this.startPos).concat(this.rows);
if (this.rows.length > this.maxBufferSize) 
 this.rows = this.rows.slice(0, this.maxBufferSize)
 }
 this.startPos =start;
}
this.size = this.rows.length;
 }
});
Object.extend(Rico.GridViewPort.prototype,
{
 populateRow: function(htmlRow, row) 
 {
 if(isdef(htmlRow))
 {
for (var j=0; j < row.length; j++) {
 htmlRow.cells[j].innerHTML = row[j]
}
 }
 }
});
