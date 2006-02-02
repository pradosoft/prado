var Rico={Version:"1.1rc1",prototypeVersion:parseFloat(Prototype.Version.split(".")[0]+"."+Prototype.Version.split(".")[1])};
Rico.ArrayExtensions=new Array();
if(Object.prototype.extend){
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Object.prototype.extend;
}else{
Object.prototype.extend=function(_1){
return Object.extend.apply(this,[this,_1]);
};
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Object.prototype.extend;
}
if(Array.prototype.push){
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.push;
}
if(!Array.prototype.remove){
Array.prototype.remove=function(dx){
if(isNaN(dx)||dx>this.length){
return false;
}
for(var i=0,n=0;i<this.length;i++){
if(i!=dx){
this[n++]=this[i];
}
}
this.length-=1;
};
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.remove;
}
if(!Array.prototype.removeItem){
Array.prototype.removeItem=function(_4){
for(var i=0;i<this.length;i++){
if(this[i]==_4){
this.remove(i);
break;
}
}
};
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.removeItem;
}
if(!Array.prototype.indices){
Array.prototype.indices=function(){
var _5=new Array();
for(index in this){
var _6=false;
for(var i=0;i<Rico.ArrayExtensions.length;i++){
if(this[index]==Rico.ArrayExtensions[i]){
_6=true;
break;
}
}
if(!_6){
_5[_5.length]=index;
}
}
return _5;
};
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.indices;
}
if(window.DOMParser&&window.XMLSerializer&&window.Node&&Node.prototype&&Node.prototype.__defineGetter__){
if(!Document.prototype.loadXML){
Document.prototype.loadXML=function(s){
var _8=(new DOMParser()).parseFromString(s,"text/xml");
while(this.hasChildNodes()){
this.removeChild(this.lastChild);
}
for(var i=0;i<_8.childNodes.length;i++){
this.appendChild(this.importNode(_8.childNodes[i],true));
}
};
}
Document.prototype.__defineGetter__("xml",function(){
return (new XMLSerializer()).serializeToString(this);
});
}
document.getElementsByTagAndClassName=function(_9,_10){
if(_9==null){
_9="*";
}
var _11=document.getElementsByTagName(_9)||document.all;
var _12=new Array();
if(_10==null){
return _11;
}
for(var i=0;i<_11.length;i++){
var _13=_11[i];
var _14=_13.className.split(" ");
for(var j=0;j<_14.length;j++){
if(_14[j]==_10){
_12.push(_13);
break;
}
}
}
return _12;
};
Rico.Accordion=Class.create();
Rico.Accordion.prototype={initialize:function(_16,_17){
this.container=$(_16);
this.lastExpandedTab=null;
this.accordionTabs=new Array();
this.setOptions(_17);
this._attachBehaviors();
if(!_16){
return;
}
this.container.style.borderBottom="1px solid "+this.options.borderColor;
if(this.options.onLoadShowTab>=this.accordionTabs.length){
this.options.onLoadShowTab=0;
}
for(var i=0;i<this.accordionTabs.length;i++){
if(i!=this.options.onLoadShowTab){
this.accordionTabs[i].collapse();
this.accordionTabs[i].content.style.display="none";
}
}
this.lastExpandedTab=this.accordionTabs[this.options.onLoadShowTab];
if(this.options.panelHeight=="auto"){
var _18=(this.options.onloadShowTab===0)?1:0;
var _19=parseInt(RicoUtil.getElementsComputedStyle(this.accordionTabs[_18].titleBar,"height"));
if(isNaN(_19)){
_19=this.accordionTabs[_18].titleBar.offsetHeight;
}
var _20=this.accordionTabs.length*_19;
var _21=parseInt(RicoUtil.getElementsComputedStyle(this.container.parentNode,"height"));
if(isNaN(_21)){
_21=this.container.parentNode.offsetHeight;
}
this.options.panelHeight=_21-_20-2;
}
this.lastExpandedTab.content.style.height=this.options.panelHeight+"px";
this.lastExpandedTab.showExpanded();
this.lastExpandedTab.titleBar.style.fontWeight=this.options.expandedFontWeight;
},setOptions:function(_22){
this.options={expandedBg:"#63699c",hoverBg:"#63699c",collapsedBg:"#6b79a5",expandedTextColor:"#ffffff",expandedFontWeight:"bold",hoverTextColor:"#ffffff",collapsedTextColor:"#ced7ef",collapsedFontWeight:"normal",hoverTextColor:"#ffffff",borderColor:"#1f669b",panelHeight:200,onHideTab:null,onShowTab:null,onLoadShowTab:0};
Object.extend(this.options,_22||{});
},showTabByIndex:function(_23,_24){
var _25=arguments.length==1?true:_24;
this.showTab(this.accordionTabs[_23],_25);
},showTab:function(_26,_27){
var _28=arguments.length==1?true:_27;
if(this.options.onHideTab){
this.options.onHideTab(this.lastExpandedTab);
}
this.lastExpandedTab.showCollapsed();
var _29=this;
var _30=this.lastExpandedTab;
this.lastExpandedTab.content.style.height=(this.options.panelHeight-1)+"px";
_26.content.style.display="";
_26.titleBar.style.fontWeight=this.options.expandedFontWeight;
if(_28){
new Effect.AccordionSize(this.lastExpandedTab.content,_26.content,1,this.options.panelHeight,100,10,{complete:function(){
_29.showTabDone(_30);
}});
this.lastExpandedTab=_26;
}else{
this.lastExpandedTab.content.style.height="1px";
_26.content.style.height=this.options.panelHeight+"px";
this.lastExpandedTab=_26;
this.showTabDone(_30);
}
},showTabDone:function(_31){
_31.content.style.display="none";
this.lastExpandedTab.showExpanded();
if(this.options.onShowTab){
this.options.onShowTab(this.lastExpandedTab);
}
},_attachBehaviors:function(){
var _32=this._getDirectChildrenByTag(this.container,"DIV");
for(var i=0;i<_32.length;i++){
var _33=this._getDirectChildrenByTag(_32[i],"DIV");
if(_33.length!=2){
continue;
}
var _34=_33[0];
var _35=_33[1];
this.accordionTabs.push(new Rico.Accordion.Tab(this,_34,_35));
}
},_getDirectChildrenByTag:function(e,_37){
var _38=new Array();
var _39=e.childNodes;
for(var i=0;i<_39.length;i++){
if(_39[i]&&_39[i].tagName&&_39[i].tagName==_37){
_38.push(_39[i]);
}
}
return _38;
}};
Rico.Accordion.Tab=Class.create();
Rico.Accordion.Tab.prototype={initialize:function(_40,_41,_42){
this.accordion=_40;
this.titleBar=_41;
this.content=_42;
this._attachBehaviors();
},collapse:function(){
this.showCollapsed();
this.content.style.height="1px";
},showCollapsed:function(){
this.expanded=false;
this.titleBar.style.backgroundColor=this.accordion.options.collapsedBg;
this.titleBar.style.color=this.accordion.options.collapsedTextColor;
this.titleBar.style.fontWeight=this.accordion.options.collapsedFontWeight;
this.content.style.overflow="hidden";
},showExpanded:function(){
this.expanded=true;
this.titleBar.style.backgroundColor=this.accordion.options.expandedBg;
this.titleBar.style.color=this.accordion.options.expandedTextColor;
this.content.style.overflow="visible";
},titleBarClicked:function(e){
if(this.accordion.lastExpandedTab==this){
return;
}
this.accordion.showTab(this);
},hover:function(e){
this.titleBar.style.backgroundColor=this.accordion.options.hoverBg;
this.titleBar.style.color=this.accordion.options.hoverTextColor;
},unhover:function(e){
if(this.expanded){
this.titleBar.style.backgroundColor=this.accordion.options.expandedBg;
this.titleBar.style.color=this.accordion.options.expandedTextColor;
}else{
this.titleBar.style.backgroundColor=this.accordion.options.collapsedBg;
this.titleBar.style.color=this.accordion.options.collapsedTextColor;
}
},_attachBehaviors:function(){
this.content.style.border="1px solid "+this.accordion.options.borderColor;
this.content.style.borderTopWidth="0px";
this.content.style.borderBottomWidth="0px";
this.content.style.margin="0px";
this.titleBar.onclick=this.titleBarClicked.bindAsEventListener(this);
this.titleBar.onmouseover=this.hover.bindAsEventListener(this);
this.titleBar.onmouseout=this.unhover.bindAsEventListener(this);
}};
Rico.Color=Class.create();
Rico.Color.prototype={initialize:function(red,_44,_45){
this.rgb={r:red,g:_44,b:_45};
},setRed:function(r){
this.rgb.r=r;
},setGreen:function(g){
this.rgb.g=g;
},setBlue:function(b){
this.rgb.b=b;
},setHue:function(h){
var hsb=this.asHSB();
hsb.h=h;
this.rgb=Rico.Color.HSBtoRGB(hsb.h,hsb.s,hsb.b);
},setSaturation:function(s){
var hsb=this.asHSB();
hsb.s=s;
this.rgb=Rico.Color.HSBtoRGB(hsb.h,hsb.s,hsb.b);
},setBrightness:function(b){
var hsb=this.asHSB();
hsb.b=b;
this.rgb=Rico.Color.HSBtoRGB(hsb.h,hsb.s,hsb.b);
},darken:function(_51){
var hsb=this.asHSB();
this.rgb=Rico.Color.HSBtoRGB(hsb.h,hsb.s,Math.max(hsb.b-_51,0));
},brighten:function(_52){
var hsb=this.asHSB();
this.rgb=Rico.Color.HSBtoRGB(hsb.h,hsb.s,Math.min(hsb.b+_52,1));
},blend:function(_53){
this.rgb.r=Math.floor((this.rgb.r+_53.rgb.r)/2);
this.rgb.g=Math.floor((this.rgb.g+_53.rgb.g)/2);
this.rgb.b=Math.floor((this.rgb.b+_53.rgb.b)/2);
},isBright:function(){
var hsb=this.asHSB();
return this.asHSB().b>0.5;
},isDark:function(){
return !this.isBright();
},asRGB:function(){
return "rgb("+this.rgb.r+","+this.rgb.g+","+this.rgb.b+")";
},asHex:function(){
return "#"+this.rgb.r.toColorPart()+this.rgb.g.toColorPart()+this.rgb.b.toColorPart();
},asHSB:function(){
return Rico.Color.RGBtoHSB(this.rgb.r,this.rgb.g,this.rgb.b);
},toString:function(){
return this.asHex();
}};
Rico.Color.createFromHex=function(_54){
if(_54.indexOf("#")==0){
_54=_54.substring(1);
}
var red=_54.substring(0,2);
var _55=_54.substring(2,4);
var _56=_54.substring(4,6);
return new Rico.Color(parseInt(red,16),parseInt(_55,16),parseInt(_56,16));
};
Rico.Color.createColorFromBackground=function(_57){
var _58=RicoUtil.getElementsComputedStyle($(_57),"backgroundColor","background-color");
if(_58=="transparent"&&_57.parent){
return Rico.Color.createColorFromBackground(_57.parent);
}
if(_58==null){
return new Rico.Color(255,255,255);
}
if(_58.indexOf("rgb(")==0){
var _59=_58.substring(4,_58.length-1);
var _60=_59.split(",");
return new Rico.Color(parseInt(_60[0]),parseInt(_60[1]),parseInt(_60[2]));
}else{
if(_58.indexOf("#")==0){
var _61=parseInt(_58.substring(1,3),16);
var _62=parseInt(_58.substring(3,5),16);
var _63=parseInt(_58.substring(5),16);
return new Rico.Color(_61,_62,_63);
}else{
return new Rico.Color(255,255,255);
}
}
};
Rico.Color.HSBtoRGB=function(hue,_65,_66){
var red=0;
var _67=0;
var _68=0;
if(_65==0){
red=parseInt(_66*255+0.5);
_67=red;
_68=red;
}else{
var h=(hue-Math.floor(hue))*6;
var f=h-Math.floor(h);
var p=_66*(1-_65);
var q=_66*(1-_65*f);
var t=_66*(1-(_65*(1-f)));
switch(parseInt(h)){
case 0:
red=(_66*255+0.5);
_67=(t*255+0.5);
_68=(p*255+0.5);
break;
case 1:
red=(q*255+0.5);
_67=(_66*255+0.5);
_68=(p*255+0.5);
break;
case 2:
red=(p*255+0.5);
_67=(_66*255+0.5);
_68=(t*255+0.5);
break;
case 3:
red=(p*255+0.5);
_67=(q*255+0.5);
_68=(_66*255+0.5);
break;
case 4:
red=(t*255+0.5);
_67=(p*255+0.5);
_68=(_66*255+0.5);
break;
case 5:
red=(_66*255+0.5);
_67=(p*255+0.5);
_68=(q*255+0.5);
break;
}
}
return {r:parseInt(red),g:parseInt(_67),b:parseInt(_68)};
};
Rico.Color.RGBtoHSB=function(r,g,b){
var hue;
var _73;
var _74;
var _75=(r>g)?r:g;
if(b>_75){
_75=b;
}
var _76=(r<g)?r:g;
if(b<_76){
_76=b;
}
_74=_75/255;
if(_75!=0){
saturation=(_75-_76)/_75;
}else{
saturation=0;
}
if(saturation==0){
hue=0;
}else{
var _77=(_75-r)/(_75-_76);
var _78=(_75-g)/(_75-_76);
var _79=(_75-b)/(_75-_76);
if(r==_75){
hue=_79-_78;
}else{
if(g==_75){
hue=2+_77-_79;
}else{
hue=4+_78-_77;
}
}
hue=hue/6;
if(hue<0){
hue=hue+1;
}
}
return {h:hue,s:saturation,b:_74};
};
Rico.Corner={round:function(e,_80){
var e=$(e);
this._setOptions(_80);
var _81=this.options.color;
if(this.options.color=="fromElement"){
_81=this._background(e);
}
var _82=this.options.bgColor;
if(this.options.bgColor=="fromParent"){
_82=this._background(e.offsetParent);
}
this._roundCornersImpl(e,_81,_82);
},_roundCornersImpl:function(e,_83,_84){
if(this.options.border){
this._renderBorder(e,_84);
}
if(this._isTopRounded()){
this._roundTopCorners(e,_83,_84);
}
if(this._isBottomRounded()){
this._roundBottomCorners(e,_83,_84);
}
},_renderBorder:function(el,_86){
var _87="1px solid "+this._borderColor(_86);
var _88="border-left: "+_87;
var _89="border-right: "+_87;
var _90="style='"+_88+";"+_89+"'";
el.innerHTML="<div "+_90+">"+el.innerHTML+"</div>";
},_roundTopCorners:function(el,_91,_92){
var _93=this._createCorner(_92);
for(var i=0;i<this.options.numSlices;i++){
_93.appendChild(this._createCornerSlice(_91,_92,i,"top"));
}
el.style.paddingTop=0;
el.insertBefore(_93,el.firstChild);
},_roundBottomCorners:function(el,_94,_95){
var _96=this._createCorner(_95);
for(var i=(this.options.numSlices-1);i>=0;i--){
_96.appendChild(this._createCornerSlice(_94,_95,i,"bottom"));
}
el.style.paddingBottom=0;
el.appendChild(_96);
},_createCorner:function(_97){
var _98=document.createElement("div");
_98.style.backgroundColor=(this._isTransparent()?"transparent":_97);
return _98;
},_createCornerSlice:function(_99,_100,n,_102){
var _103=document.createElement("span");
var _104=_103.style;
_104.backgroundColor=_99;
_104.display="block";
_104.height="1px";
_104.overflow="hidden";
_104.fontSize="1px";
var _105=this._borderColor(_99,_100);
if(this.options.border&&n==0){
_104.borderTopStyle="solid";
_104.borderTopWidth="1px";
_104.borderLeftWidth="0px";
_104.borderRightWidth="0px";
_104.borderBottomWidth="0px";
_104.height="0px";
_104.borderColor=_105;
}else{
if(_105){
_104.borderColor=_105;
_104.borderStyle="solid";
_104.borderWidth="0px 1px";
}
}
if(!this.options.compact&&(n==(this.options.numSlices-1))){
_104.height="2px";
}
this._setMargin(_103,n,_102);
this._setBorder(_103,n,_102);
return _103;
},_setOptions:function(_106){
this.options={corners:"all",color:"fromElement",bgColor:"fromParent",blend:true,border:false,compact:false};
Object.extend(this.options,_106||{});
this.options.numSlices=this.options.compact?2:4;
if(this._isTransparent()){
this.options.blend=false;
}
},_whichSideTop:function(){
if(this._hasString(this.options.corners,"all","top")){
return "";
}
if(this.options.corners.indexOf("tl")>=0&&this.options.corners.indexOf("tr")>=0){
return "";
}
if(this.options.corners.indexOf("tl")>=0){
return "left";
}else{
if(this.options.corners.indexOf("tr")>=0){
return "right";
}
}
return "";
},_whichSideBottom:function(){
if(this._hasString(this.options.corners,"all","bottom")){
return "";
}
if(this.options.corners.indexOf("bl")>=0&&this.options.corners.indexOf("br")>=0){
return "";
}
if(this.options.corners.indexOf("bl")>=0){
return "left";
}else{
if(this.options.corners.indexOf("br")>=0){
return "right";
}
}
return "";
},_borderColor:function(_107,_108){
if(_107=="transparent"){
return _108;
}else{
if(this.options.border){
return this.options.border;
}else{
if(this.options.blend){
return this._blend(_108,_107);
}else{
return "";
}
}
}
},_setMargin:function(el,n,_109){
var _110=this._marginSize(n);
var _111=_109=="top"?this._whichSideTop():this._whichSideBottom();
if(_111=="left"){
el.style.marginLeft=_110+"px";
el.style.marginRight="0px";
}else{
if(_111=="right"){
el.style.marginRight=_110+"px";
el.style.marginLeft="0px";
}else{
el.style.marginLeft=_110+"px";
el.style.marginRight=_110+"px";
}
}
},_setBorder:function(el,n,_112){
var _113=this._borderSize(n);
var _114=_112=="top"?this._whichSideTop():this._whichSideBottom();
if(_114=="left"){
el.style.borderLeftWidth=_113+"px";
el.style.borderRightWidth="0px";
}else{
if(_114=="right"){
el.style.borderRightWidth=_113+"px";
el.style.borderLeftWidth="0px";
}else{
el.style.borderLeftWidth=_113+"px";
el.style.borderRightWidth=_113+"px";
}
}
if(this.options.border!=false){
el.style.borderLeftWidth=_113+"px";
}
el.style.borderRightWidth=_113+"px";
},_marginSize:function(n){
if(this._isTransparent()){
return 0;
}
var _115=[5,3,2,1];
var _116=[3,2,1,0];
var _117=[2,1];
var _118=[1,0];
if(this.options.compact&&this.options.blend){
return _118[n];
}else{
if(this.options.compact){
return _117[n];
}else{
if(this.options.blend){
return _116[n];
}else{
return _115[n];
}
}
}
},_borderSize:function(n){
var _119=[5,3,2,1];
var _120=[2,1,1,1];
var _121=[1,0];
var _122=[0,2,0,0];
if(this.options.compact&&(this.options.blend||this._isTransparent())){
return 1;
}else{
if(this.options.compact){
return _121[n];
}else{
if(this.options.blend){
return _120[n];
}else{
if(this.options.border){
return _122[n];
}else{
if(this._isTransparent()){
return _119[n];
}
}
}
}
}
return 0;
},_hasString:function(str){
for(var i=1;i<arguments.length;i++){
if(str.indexOf(arguments[i])>=0){
return true;
}
}
return false;
},_blend:function(c1,c2){
var cc1=Rico.Color.createFromHex(c1);
cc1.blend(Rico.Color.createFromHex(c2));
return cc1;
},_background:function(el){
try{
return Rico.Color.createColorFromBackground(el).asHex();
}
catch(err){
return "#ffffff";
}
},_isTransparent:function(){
return this.options.color=="transparent";
},_isTopRounded:function(){
return this._hasString(this.options.corners,"all","top","tl","tr");
},_isBottomRounded:function(){
return this._hasString(this.options.corners,"all","bottom","bl","br");
},_hasSingleTextChild:function(el){
return el.childNodes.length==1&&el.childNodes[0].nodeType==3;
}};
if(window.Effect==undefined){
Effect={};
}
Effect.SizeAndPosition=Class.create();
Effect.SizeAndPosition.prototype={initialize:function(_127,x,y,w,h,_131,_132,_133){
this.element=$(_127);
this.x=x;
this.y=y;
this.w=w;
this.h=h;
this.duration=_131;
this.steps=_132;
this.options=arguments[7]||{};
this.sizeAndPosition();
},sizeAndPosition:function(){
if(this.isFinished()){
if(this.options.complete){
this.options.complete(this);
}
return;
}
if(this.timer){
clearTimeout(this.timer);
}
var _134=Math.round(this.duration/this.steps);
var _135=this.element.offsetLeft;
var _136=this.element.offsetTop;
var _137=this.element.offsetWidth;
var _138=this.element.offsetHeight;
this.x=(this.x)?this.x:_135;
this.y=(this.y)?this.y:_136;
this.w=(this.w)?this.w:_137;
this.h=(this.h)?this.h:_138;
var difX=this.steps>0?(this.x-_135)/this.steps:0;
var difY=this.steps>0?(this.y-_136)/this.steps:0;
var difW=this.steps>0?(this.w-_137)/this.steps:0;
var difH=this.steps>0?(this.h-_138)/this.steps:0;
this.moveBy(difX,difY);
this.resizeBy(difW,difH);
this.duration-=_134;
this.steps--;
this.timer=setTimeout(this.sizeAndPosition.bind(this),_134);
},isFinished:function(){
return this.steps<=0;
},moveBy:function(difX,difY){
var _143=this.element.offsetLeft;
var _144=this.element.offsetTop;
var _145=parseInt(difX);
var _146=parseInt(difY);
var _147=this.element.style;
if(_145!=0){
_147.left=(_143+_145)+"px";
}
if(_146!=0){
_147.top=(_144+_146)+"px";
}
},resizeBy:function(difW,difH){
var _148=this.element.offsetWidth;
var _149=this.element.offsetHeight;
var _150=parseInt(difW);
var _151=parseInt(difH);
var _152=this.element.style;
if(_150!=0){
_152.width=(_148+_150)+"px";
}
if(_151!=0){
_152.height=(_149+_151)+"px";
}
}};
Effect.Size=Class.create();
Effect.Size.prototype={initialize:function(_153,w,h,_154,_155,_156){
new Effect.SizeAndPosition(_153,null,null,w,h,_154,_155,_156);
}};
Effect.Position=Class.create();
Effect.Position.prototype={initialize:function(_157,x,y,_158,_159,_160){
new Effect.SizeAndPosition(_157,x,y,null,null,_158,_159,_160);
}};
Effect.Round=Class.create();
Effect.Round.prototype={initialize:function(_161,_162,_163){
var _164=document.getElementsByTagAndClassName(_161,_162);
for(var i=0;i<_164.length;i++){
Rico.Corner.round(_164[i],_163);
}
}};
Effect.FadeTo=Class.create();
Effect.FadeTo.prototype={initialize:function(_165,_166,_167,_168,_169){
this.element=$(_165);
this.opacity=_166;
this.duration=_167;
this.steps=_168;
this.options=arguments[4]||{};
this.fadeTo();
},fadeTo:function(){
if(this.isFinished()){
if(this.options.complete){
this.options.complete(this);
}
return;
}
if(this.timer){
clearTimeout(this.timer);
}
var _170=Math.round(this.duration/this.steps);
var _171=this.getElementOpacity();
var _172=this.steps>0?(this.opacity-_171)/this.steps:0;
this.changeOpacityBy(_172);
this.duration-=_170;
this.steps--;
this.timer=setTimeout(this.fadeTo.bind(this),_170);
},changeOpacityBy:function(v){
var _174=this.getElementOpacity();
var _175=Math.max(0,Math.min(_174+v,1));
this.element.ricoOpacity=_175;
this.element.style.filter="alpha(opacity:"+Math.round(_175*100)+")";
this.element.style.opacity=_175;
},isFinished:function(){
return this.steps<=0;
},getElementOpacity:function(){
if(this.element.ricoOpacity==undefined){
var _176=RicoUtil.getElementsComputedStyle(this.element,"opacity");
this.element.ricoOpacity=_176!=undefined?_176:1;
}
return parseFloat(this.element.ricoOpacity);
}};
Effect.AccordionSize=Class.create();
Effect.AccordionSize.prototype={initialize:function(e1,e2,_179,end,_181,_182,_183){
this.e1=$(e1);
this.e2=$(e2);
this.start=_179;
this.end=end;
this.duration=_181;
this.steps=_182;
this.options=arguments[6]||{};
this.accordionSize();
},accordionSize:function(){
if(this.isFinished()){
this.e1.style.height=this.start+"px";
this.e2.style.height=this.end+"px";
if(this.options.complete){
this.options.complete(this);
}
return;
}
if(this.timer){
clearTimeout(this.timer);
}
var _184=Math.round(this.duration/this.steps);
var diff=this.steps>0?(parseInt(this.e1.offsetHeight)-this.start)/this.steps:0;
this.resizeBy(diff);
this.duration-=_184;
this.steps--;
this.timer=setTimeout(this.accordionSize.bind(this),_184);
},isFinished:function(){
return this.steps<=0;
},resizeBy:function(diff){
var _186=this.e1.offsetHeight;
var _187=this.e2.offsetHeight;
var _188=parseInt(diff);
if(diff!=0){
this.e1.style.height=(_186-_188)+"px";
this.e2.style.height=(_187+_188)+"px";
}
}};
if(window.Effect==undefined){
Effect={};
}
Effect.SizeAndPosition=Class.create();
Effect.SizeAndPosition.prototype={initialize:function(_189,x,y,w,h,_190,_191,_192){
this.element=$(_189);
this.x=x;
this.y=y;
this.w=w;
this.h=h;
this.duration=_190;
this.steps=_191;
this.options=arguments[7]||{};
this.sizeAndPosition();
},sizeAndPosition:function(){
if(this.isFinished()){
if(this.options.complete){
this.options.complete(this);
}
return;
}
if(this.timer){
clearTimeout(this.timer);
}
var _193=Math.round(this.duration/this.steps);
var _194=this.element.offsetLeft;
var _195=this.element.offsetTop;
var _196=this.element.offsetWidth;
var _197=this.element.offsetHeight;
this.x=(this.x)?this.x:_194;
this.y=(this.y)?this.y:_195;
this.w=(this.w)?this.w:_196;
this.h=(this.h)?this.h:_197;
var difX=this.steps>0?(this.x-_194)/this.steps:0;
var difY=this.steps>0?(this.y-_195)/this.steps:0;
var difW=this.steps>0?(this.w-_196)/this.steps:0;
var difH=this.steps>0?(this.h-_197)/this.steps:0;
this.moveBy(difX,difY);
this.resizeBy(difW,difH);
this.duration-=_193;
this.steps--;
this.timer=setTimeout(this.sizeAndPosition.bind(this),_193);
},isFinished:function(){
return this.steps<=0;
},moveBy:function(difX,difY){
var _198=this.element.offsetLeft;
var _199=this.element.offsetTop;
var _200=parseInt(difX);
var _201=parseInt(difY);
var _202=this.element.style;
if(_200!=0){
_202.left=(_198+_200)+"px";
}
if(_201!=0){
_202.top=(_199+_201)+"px";
}
},resizeBy:function(difW,difH){
var _203=this.element.offsetWidth;
var _204=this.element.offsetHeight;
var _205=parseInt(difW);
var _206=parseInt(difH);
var _207=this.element.style;
if(_205!=0){
_207.width=(_203+_205)+"px";
}
if(_206!=0){
_207.height=(_204+_206)+"px";
}
}};
Effect.Size=Class.create();
Effect.Size.prototype={initialize:function(_208,w,h,_209,_210,_211){
new Effect.SizeAndPosition(_208,null,null,w,h,_209,_210,_211);
}};
Effect.Position=Class.create();
Effect.Position.prototype={initialize:function(_212,x,y,_213,_214,_215){
new Effect.SizeAndPosition(_212,x,y,null,null,_213,_214,_215);
}};
Effect.Round=Class.create();
Effect.Round.prototype={initialize:function(_216,_217,_218){
var _219=document.getElementsByTagAndClassName(_216,_217);
for(var i=0;i<_219.length;i++){
Rico.Corner.round(_219[i],_218);
}
}};
Effect.FadeTo=Class.create();
Effect.FadeTo.prototype={initialize:function(_220,_221,_222,_223,_224){
this.element=$(_220);
this.opacity=_221;
this.duration=_222;
this.steps=_223;
this.options=arguments[4]||{};
this.fadeTo();
},fadeTo:function(){
if(this.isFinished()){
if(this.options.complete){
this.options.complete(this);
}
return;
}
if(this.timer){
clearTimeout(this.timer);
}
var _225=Math.round(this.duration/this.steps);
var _226=this.getElementOpacity();
var _227=this.steps>0?(this.opacity-_226)/this.steps:0;
this.changeOpacityBy(_227);
this.duration-=_225;
this.steps--;
this.timer=setTimeout(this.fadeTo.bind(this),_225);
},changeOpacityBy:function(v){
var _228=this.getElementOpacity();
var _229=Math.max(0,Math.min(_228+v,1));
this.element.ricoOpacity=_229;
this.element.style.filter="alpha(opacity:"+Math.round(_229*100)+")";
this.element.style.opacity=_229;
},isFinished:function(){
return this.steps<=0;
},getElementOpacity:function(){
if(this.element.ricoOpacity==undefined){
var _230=RicoUtil.getElementsComputedStyle(this.element,"opacity");
this.element.ricoOpacity=_230!=undefined?_230:1;
}
return parseFloat(this.element.ricoOpacity);
}};
Effect.AccordionSize=Class.create();
Effect.AccordionSize.prototype={initialize:function(e1,e2,_231,end,_232,_233,_234){
this.e1=$(e1);
this.e2=$(e2);
this.start=_231;
this.end=end;
this.duration=_232;
this.steps=_233;
this.options=arguments[6]||{};
this.accordionSize();
},accordionSize:function(){
if(this.isFinished()){
this.e1.style.height=this.start+"px";
this.e2.style.height=this.end+"px";
if(this.options.complete){
this.options.complete(this);
}
return;
}
if(this.timer){
clearTimeout(this.timer);
}
var _235=Math.round(this.duration/this.steps);
var diff=this.steps>0?(parseInt(this.e1.offsetHeight)-this.start)/this.steps:0;
this.resizeBy(diff);
this.duration-=_235;
this.steps--;
this.timer=setTimeout(this.accordionSize.bind(this),_235);
},isFinished:function(){
return this.steps<=0;
},resizeBy:function(diff){
var _236=this.e1.offsetHeight;
var _237=this.e2.offsetHeight;
var _238=parseInt(diff);
if(diff!=0){
this.e1.style.height=(_236-_238)+"px";
this.e2.style.height=(_237+_238)+"px";
}
}};
Rico.LiveGridMetaData=Class.create();
Rico.LiveGridMetaData.prototype={initialize:function(_239,_240,_241,_242){
this.pageSize=_239;
this.totalRows=_240;
this.setOptions(_242);
this.ArrowHeight=16;
this.columnCount=_241;
},setOptions:function(_243){
this.options={largeBufferSize:7,nearLimitFactor:0.2};
Object.extend(this.options,_243||{});
},getPageSize:function(){
return this.pageSize;
},getTotalRows:function(){
return this.totalRows;
},setTotalRows:function(n){
this.totalRows=n;
},getLargeBufferSize:function(){
return parseInt(this.options.largeBufferSize*this.pageSize);
},getLimitTolerance:function(){
return parseInt(this.getLargeBufferSize()*this.options.nearLimitFactor);
}};
Rico.LiveGridScroller=Class.create();
Rico.LiveGridScroller.prototype={initialize:function(_244,_245){
this.isIE=navigator.userAgent.toLowerCase().indexOf("msie")>=0;
this.liveGrid=_244;
this.metaData=_244.metaData;
this.createScrollBar();
this.scrollTimeout=null;
this.lastScrollPos=0;
this.viewPort=_245;
this.rows=new Array();
},isUnPlugged:function(){
return this.scrollerDiv.onscroll==null;
},plugin:function(){
this.scrollerDiv.onscroll=this.handleScroll.bindAsEventListener(this);
},unplug:function(){
this.scrollerDiv.onscroll=null;
},sizeIEHeaderHack:function(){
if(!this.isIE){
return;
}
var _246=$(this.liveGrid.tableId+"_header");
if(_246){
_246.rows[0].cells[0].style.width=(_246.rows[0].cells[0].offsetWidth+1)+"px";
}
},createScrollBar:function(){
var _247=this.liveGrid.viewPort.visibleHeight();
this.scrollerDiv=document.createElement("div");
var _248=this.scrollerDiv.style;
_248.borderRight=this.liveGrid.options.scrollerBorderRight;
_248.position="relative";
_248.left=this.isIE?"-6px":"-3px";
_248.width="19px";
_248.height=_247+"px";
_248.overflow="auto";
this.heightDiv=document.createElement("div");
this.heightDiv.style.width="1px";
this.heightDiv.style.height=parseInt(_247*this.metaData.getTotalRows()/this.metaData.getPageSize())+"px";
this.scrollerDiv.appendChild(this.heightDiv);
this.scrollerDiv.onscroll=this.handleScroll.bindAsEventListener(this);
var _249=this.liveGrid.table;
_249.parentNode.parentNode.insertBefore(this.scrollerDiv,_249.parentNode.nextSibling);
var _250=this.isIE?"mousewheel":"DOMMouseScroll";
Event.observe(_249,_250,function(evt){
if(evt.wheelDelta>=0||evt.detail<0){
this.scrollerDiv.scrollTop-=(2*this.viewPort.rowHeight);
}else{
this.scrollerDiv.scrollTop+=(2*this.viewPort.rowHeight);
}
this.handleScroll(false);
}.bindAsEventListener(this),false);
},updateSize:function(){
var _252=this.liveGrid.table;
var _253=this.viewPort.visibleHeight();
this.heightDiv.style.height=parseInt(_253*this.metaData.getTotalRows()/this.metaData.getPageSize())+"px";
},rowToPixel:function(_254){
return (_254/this.metaData.getTotalRows())*this.heightDiv.offsetHeight;
},moveScroll:function(_255){
this.scrollerDiv.scrollTop=this.rowToPixel(_255);
if(this.metaData.options.onscroll){
this.metaData.options.onscroll(this.liveGrid,_255);
}
},handleScroll:function(){
if(this.scrollTimeout){
clearTimeout(this.scrollTimeout);
}
var _256=this.lastScrollPos-this.scrollerDiv.scrollTop;
if(_256!=0){
var r=this.scrollerDiv.scrollTop%this.viewPort.rowHeight;
if(r!=0){
this.unplug();
if(_256<0){
this.scrollerDiv.scrollTop+=(this.viewPort.rowHeight-r);
}else{
this.scrollerDiv.scrollTop-=r;
}
this.plugin();
}
}
var _257=parseInt(this.scrollerDiv.scrollTop/this.viewPort.rowHeight);
this.liveGrid.requestContentRefresh(_257);
this.viewPort.scrollTo(this.scrollerDiv.scrollTop);
if(this.metaData.options.onscroll){
this.metaData.options.onscroll(this.liveGrid,_257);
}
this.scrollTimeout=setTimeout(this.scrollIdle.bind(this),1200);
this.lastScrollPos=this.scrollerDiv.scrollTop;
},scrollIdle:function(){
if(this.metaData.options.onscrollidle){
this.metaData.options.onscrollidle();
}
}};
Rico.LiveGridBuffer=Class.create();
Rico.LiveGridBuffer.prototype={initialize:function(_258,_259){
this.startPos=0;
this.size=0;
this.metaData=_258;
this.rows=new Array();
this.updateInProgress=false;
this.viewPort=_259;
this.maxBufferSize=_258.getLargeBufferSize()*2;
this.maxFetchSize=_258.getLargeBufferSize();
this.lastOffset=0;
},getBlankRow:function(){
if(!this.blankRow){
this.blankRow=new Array();
for(var i=0;i<this.metaData.columnCount;i++){
this.blankRow[i]="&nbsp;";
}
}
return this.blankRow;
},loadRows:function(_260){
var _261=_260.getElementsByTagName("rows")[0];
this.updateUI=_261.getAttribute("update_ui")=="true";
var _262=new Array();
var trs=_261.getElementsByTagName("tr");
for(var i=0;i<trs.length;i++){
var row=_262[i]=new Array();
var _265=trs[i].getElementsByTagName("td");
for(var j=0;j<_265.length;j++){
var cell=_265[j];
var _267=cell.getAttribute("convert_spaces")=="true";
var _268=RicoUtil.getContentAsString(cell);
row[j]=_267?this.convertSpaces(_268):_268;
if(!row[j]){
row[j]="&nbsp;";
}
}
}
return _262;
},update:function(_269,_270){
var _271=this.loadRows(_269);
if(this.rows.length==0){
this.rows=_271;
this.size=this.rows.length;
this.startPos=_270;
return;
}
if(_270>this.startPos){
if(this.startPos+this.rows.length<_270){
this.rows=_271;
this.startPos=_270;
}else{
this.rows=this.rows.concat(_271.slice(0,_271.length));
if(this.rows.length>this.maxBufferSize){
var _272=this.rows.length;
this.rows=this.rows.slice(this.rows.length-this.maxBufferSize,this.rows.length);
this.startPos=this.startPos+(_272-this.rows.length);
}
}
}else{
if(_270+_271.length<this.startPos){
this.rows=_271;
}else{
this.rows=_271.slice(0,this.startPos).concat(this.rows);
if(this.rows.length>this.maxBufferSize){
this.rows=this.rows.slice(0,this.maxBufferSize);
}
}
this.startPos=_270;
}
this.size=this.rows.length;
},clear:function(){
this.rows=new Array();
this.startPos=0;
this.size=0;
},isOverlapping:function(_273,size){
return ((_273<this.endPos())&&(this.startPos<_273+size))||(this.endPos()==0);
},isInRange:function(_275){
return (_275>=this.startPos)&&(_275+this.metaData.getPageSize()<=this.endPos());
},isNearingTopLimit:function(_276){
return _276-this.startPos<this.metaData.getLimitTolerance();
},endPos:function(){
return this.startPos+this.rows.length;
},isNearingBottomLimit:function(_277){
return this.endPos()-(_277+this.metaData.getPageSize())<this.metaData.getLimitTolerance();
},isAtTop:function(){
return this.startPos==0;
},isAtBottom:function(){
return this.endPos()==this.metaData.getTotalRows();
},isNearingLimit:function(_278){
return (!this.isAtTop()&&this.isNearingTopLimit(_278))||(!this.isAtBottom()&&this.isNearingBottomLimit(_278));
},getFetchSize:function(_279){
var _280=this.getFetchOffset(_279);
var _281=0;
if(_280>=this.startPos){
var _282=this.maxFetchSize+_280;
if(_282>this.metaData.totalRows){
_282=this.metaData.totalRows;
}
_281=_282-_280;
if(_280==0&&_281<this.maxFetchSize){
_281=this.maxFetchSize;
}
}else{
var _281=this.startPos-_280;
if(_281>this.maxFetchSize){
_281=this.maxFetchSize;
}
}
return _281;
},getFetchOffset:function(_283){
var _284=_283;
if(_283>this.startPos){
_284=(_283>this.endPos())?_283:this.endPos();
}else{
if(_283+this.maxFetchSize>=this.startPos){
var _284=this.startPos-this.maxFetchSize;
if(_284<0){
_284=0;
}
}
}
this.lastOffset=_284;
return _284;
},getRows:function(_285,_286){
var _287=_285-this.startPos;
var _288=_287+_286;
if(_288>this.size){
_288=this.size;
}
var _289=new Array();
var _290=0;
for(var i=_287;i<_288;i++){
_289[_290++]=this.rows[i];
}
return _289;
},convertSpaces:function(s){
return s.split(" ").join("&nbsp;");
}};
Rico.GridViewPort=Class.create();
Rico.GridViewPort.prototype={initialize:function(_291,_292,_293,_294,_295){
this.lastDisplayedStartPos=0;
this.div=_291.parentNode;
this.table=_291;
this.rowHeight=_292;
this.div.style.height=this.rowHeight*_293;
this.div.style.overflow="hidden";
this.buffer=_294;
this.liveGrid=_295;
this.visibleRows=_293+1;
this.lastPixelOffset=0;
this.startPos=0;
},populateRow:function(_296,row){
for(var j=0;j<row.length;j++){
_296.cells[j].innerHTML=row[j];
}
},bufferChanged:function(){
this.refreshContents(parseInt(this.lastPixelOffset/this.rowHeight));
},clearRows:function(){
if(!this.isBlank){
this.liveGrid.table.className=this.liveGrid.options.loadingClass;
for(var i=0;i<this.visibleRows;i++){
this.populateRow(this.table.rows[i],this.buffer.getBlankRow());
}
this.isBlank=true;
}
},clearContents:function(){
this.clearRows();
this.scrollTo(0);
this.startPos=0;
this.lastStartPos=-1;
},refreshContents:function(_297){
if(_297==this.lastRowPos&&!this.isPartialBlank&&!this.isBlank){
return;
}
if((_297+this.visibleRows<this.buffer.startPos)||(this.buffer.startPos+this.buffer.size<_297)||(this.buffer.size==0)){
this.clearRows();
return;
}
this.isBlank=false;
var _298=this.buffer.startPos>_297;
var _299=_298?this.buffer.startPos:_297;
var _300=(this.buffer.startPos+this.buffer.size<_297+this.visibleRows)?this.buffer.startPos+this.buffer.size:_297+this.visibleRows;
var _301=_300-_299;
var rows=this.buffer.getRows(_299,_301);
var _303=this.visibleRows-_301;
var _304=_298?0:_301;
var _305=_298?_303:0;
for(var i=0;i<rows.length;i++){
this.populateRow(this.table.rows[i+_305],rows[i]);
}
for(var i=0;i<_303;i++){
this.populateRow(this.table.rows[i+_304],this.buffer.getBlankRow());
}
this.isPartialBlank=_303>0;
this.lastRowPos=_297;
this.liveGrid.table.className=this.liveGrid.options.tableClass;
var _306=this.liveGrid.options.onRefreshComplete;
if(_306!=null){
_306();
}
},scrollTo:function(_307){
if(this.lastPixelOffset==_307){
return;
}
this.refreshContents(parseInt(_307/this.rowHeight));
this.div.scrollTop=_307%this.rowHeight;
this.lastPixelOffset=_307;
},visibleHeight:function(){
return parseInt(RicoUtil.getElementsComputedStyle(this.div,"height"));
}};
Rico.LiveGridRequest=Class.create();
Rico.LiveGridRequest.prototype={initialize:function(_308,_309){
this.requestOffset=_308;
}};
Rico.LiveGrid=Class.create();
Rico.LiveGrid.prototype={initialize:function(_310,_311,_312,url,_314,_315){
this.options={tableClass:$(_310).className,loadingClass:$(_310).className,scrollerBorderRight:"1px solid #ababab",bufferTimeout:20000,sortAscendImg:"images/sort_asc.gif",sortDescendImg:"images/sort_desc.gif",sortImageWidth:9,sortImageHeight:5,ajaxSortURLParms:[],onRefreshComplete:null,requestParameters:null,inlineStyles:true};
Object.extend(this.options,_314||{});
this.ajaxOptions={parameters:null};
Object.extend(this.ajaxOptions,_315||{});
this.tableId=_310;
this.table=$(_310);
this.addLiveGridHtml();
var _316=this.table.rows[0].cells.length;
this.metaData=new Rico.LiveGridMetaData(_311,_312,_316,_314);
this.buffer=new Rico.LiveGridBuffer(this.metaData);
var _317=this.table.rows.length;
this.viewPort=new Rico.GridViewPort(this.table,this.table.offsetHeight/_317,_311,this.buffer,this);
this.scroller=new Rico.LiveGridScroller(this,this.viewPort);
this.options.sortHandler=this.sortHandler.bind(this);
if($(_310+"_header")){
this.sort=new Rico.LiveGridSort(_310+"_header",this.options);
}
this.processingRequest=null;
this.unprocessedRequest=null;
this.initAjax(url);
if(this.options.prefetchBuffer||this.options.prefetchOffset>0){
var _318=0;
if(this.options.offset){
_318=this.options.offset;
this.scroller.moveScroll(_318);
this.viewPort.scrollTo(this.scroller.rowToPixel(_318));
}
if(this.options.sortCol){
this.sortCol=_314.sortCol;
this.sortDir=_314.sortDir;
}
this.requestContentRefresh(_318);
}
},addLiveGridHtml:function(){
if(this.table.getElementsByTagName("thead").length>0){
var _319=this.table.cloneNode(true);
_319.setAttribute("id",this.tableId+"_header");
_319.setAttribute("class",this.table.className+"_header");
for(var i=0;i<_319.tBodies.length;i++){
_319.removeChild(_319.tBodies[i]);
}
this.table.deleteTHead();
this.table.parentNode.insertBefore(_319,this.table);
}
new Insertion.Before(this.table,"<div id='"+this.tableId+"_container'></div>");
this.table.previousSibling.appendChild(this.table);
new Insertion.Before(this.table,"<div id='"+this.tableId+"_viewport' style='float:left;'></div>");
this.table.previousSibling.appendChild(this.table);
},resetContents:function(){
this.scroller.moveScroll(0);
this.buffer.clear();
this.viewPort.clearContents();
},sortHandler:function(_320){
this.sortCol=_320.name;
this.sortDir=_320.currentSort;
this.resetContents();
this.requestContentRefresh(0);
},setTotalRows:function(_321){
this.resetContents();
this.metaData.setTotalRows(_321);
this.scroller.updateSize();
},initAjax:function(url){
ajaxEngine.registerRequest(this.tableId+"_request",url);
ajaxEngine.registerAjaxObject(this.tableId+"_updater",this);
},invokeAjax:function(){
},handleTimedOut:function(){
this.processingRequest=null;
this.processQueuedRequest();
},fetchBuffer:function(_322){
if(this.buffer.isInRange(_322)&&!this.buffer.isNearingLimit(_322)){
return;
}
if(this.processingRequest){
this.unprocessedRequest=new Rico.LiveGridRequest(_322);
return;
}
var _323=this.buffer.getFetchOffset(_322);
this.processingRequest=new Rico.LiveGridRequest(_322);
this.processingRequest.bufferOffset=_323;
var _324=this.buffer.getFetchSize(_322);
var _325=false;
var _326;
if(this.options.requestParameters){
_326=this._createQueryString(this.options.requestParameters,0);
}
_326=(_326==null)?"":_326+"&";
_326=_326+"id="+this.tableId+"&page_size="+_324+"&offset="+_323;
if(this.sortCol){
_326=_326+"&sort_col="+escape(this.sortCol)+"&sort_dir="+this.sortDir;
}
this.ajaxOptions.parameters=_326;
ajaxEngine.sendRequest(this.tableId+"_request",this.ajaxOptions);
this.timeoutHandler=setTimeout(this.handleTimedOut.bind(this),this.options.bufferTimeout);
},setRequestParams:function(){
this.options.requestParameters=[];
for(var i=0;i<arguments.length;i++){
this.options.requestParameters[i]=arguments[i];
}
},requestContentRefresh:function(_327){
this.fetchBuffer(_327);
},ajaxUpdate:function(_328){
try{
clearTimeout(this.timeoutHandler);
this.buffer.update(_328,this.processingRequest.bufferOffset);
this.viewPort.bufferChanged();
}
catch(err){
}
finally{
this.processingRequest=null;
}
this.processQueuedRequest();
},_createQueryString:function(_329,_330){
var _331="";
if(!_329){
return _331;
}
for(var i=_330;i<_329.length;i++){
if(i!=_330){
_331+="&";
}
var _332=_329[i];
if(_332.name!=undefined&&_332.value!=undefined){
_331+=_332.name+"="+escape(_332.value);
}else{
var ePos=_332.indexOf("=");
var _334=_332.substring(0,ePos);
var _335=_332.substring(ePos+1);
_331+=_334+"="+escape(_335);
}
}
return _331;
},processQueuedRequest:function(){
if(this.unprocessedRequest!=null){
this.requestContentRefresh(this.unprocessedRequest.requestOffset);
this.unprocessedRequest=null;
}
}};
Rico.LiveGridSort=Class.create();
Rico.LiveGridSort.prototype={initialize:function(_336,_337){
this.headerTableId=_336;
this.headerTable=$(_336);
this.options=_337;
this.setOptions();
this.applySortBehavior();
if(this.options.sortCol){
this.setSortUI(this.options.sortCol,this.options.sortDir);
}
},setSortUI:function(_338,_339){
var cols=this.options.columns;
for(var i=0;i<cols.length;i++){
if(cols[i].name==_338){
this.setColumnSort(i,_339);
break;
}
}
},setOptions:function(){
new Image().src=this.options.sortAscendImg;
new Image().src=this.options.sortDescendImg;
this.sort=this.options.sortHandler;
if(!this.options.columns){
this.options.columns=this.introspectForColumnInfo();
}else{
this.options.columns=this.convertToTableColumns(this.options.columns);
}
},applySortBehavior:function(){
var _341=this.headerTable.rows[0];
var _342=_341.cells;
for(var i=0;i<_342.length;i++){
this.addSortBehaviorToColumn(i,_342[i]);
}
},addSortBehaviorToColumn:function(n,cell){
if(this.options.columns[n].isSortable()){
cell.id=this.headerTableId+"_"+n;
cell.style.cursor="pointer";
cell.onclick=this.headerCellClicked.bindAsEventListener(this);
cell.innerHTML=cell.innerHTML+"<span id=\""+this.headerTableId+"_img_"+n+"\">"+"&nbsp;&nbsp;&nbsp;</span>";
}
},headerCellClicked:function(evt){
var _343=evt.target?evt.target:evt.srcElement;
var _344=_343.id;
var _345=parseInt(_344.substring(_344.lastIndexOf("_")+1));
var _346=this.getSortedColumnIndex();
if(_346!=-1){
if(_346!=_345){
this.removeColumnSort(_346);
this.setColumnSort(_345,Rico.TableColumn.SORT_ASC);
}else{
this.toggleColumnSort(_346);
}
}else{
this.setColumnSort(_345,Rico.TableColumn.SORT_ASC);
}
if(this.options.sortHandler){
this.options.sortHandler(this.options.columns[_345]);
}
},removeColumnSort:function(n){
this.options.columns[n].setUnsorted();
this.setSortImage(n);
},setColumnSort:function(n,_347){
this.options.columns[n].setSorted(_347);
this.setSortImage(n);
},toggleColumnSort:function(n){
this.options.columns[n].toggleSort();
this.setSortImage(n);
},setSortImage:function(n){
var _348=this.options.columns[n].getSortDirection();
var _349=$(this.headerTableId+"_img_"+n);
if(_348==Rico.TableColumn.UNSORTED){
_349.innerHTML="&nbsp;&nbsp;";
}else{
if(_348==Rico.TableColumn.SORT_ASC){
_349.innerHTML="&nbsp;&nbsp;<img width=\""+this.options.sortImageWidth+"\" "+"height=\""+this.options.sortImageHeight+"\" "+"src=\""+this.options.sortAscendImg+"\"/>";
}else{
if(_348==Rico.TableColumn.SORT_DESC){
_349.innerHTML="&nbsp;&nbsp;<img width=\""+this.options.sortImageWidth+"\" "+"height=\""+this.options.sortImageHeight+"\" "+"src=\""+this.options.sortDescendImg+"\"/>";
}
}
}
},getSortedColumnIndex:function(){
var cols=this.options.columns;
for(var i=0;i<cols.length;i++){
if(cols[i].isSorted()){
return i;
}
}
return -1;
},introspectForColumnInfo:function(){
var _350=new Array();
var _351=this.headerTable.rows[0];
var _352=_351.cells;
for(var i=0;i<_352.length;i++){
_350.push(new Rico.TableColumn(this.deriveColumnNameFromCell(_352[i],i),true));
}
return _350;
},convertToTableColumns:function(cols){
var _353=new Array();
for(var i=0;i<cols.length;i++){
_353.push(new Rico.TableColumn(cols[i][0],cols[i][1]));
}
return _353;
},deriveColumnNameFromCell:function(cell,_354){
var _355=cell.innerText!=undefined?cell.innerText:cell.textContent;
return _355?_355.toLowerCase().split(" ").join("_"):"col_"+_354;
}};
Rico.TableColumn=Class.create();
Rico.TableColumn.UNSORTED=0;
Rico.TableColumn.SORT_ASC="ASC";
Rico.TableColumn.SORT_DESC="DESC";
Rico.TableColumn.prototype={initialize:function(name,_357){
this.name=name;
this.sortable=_357;
this.currentSort=Rico.TableColumn.UNSORTED;
},isSortable:function(){
return this.sortable;
},isSorted:function(){
return this.currentSort!=Rico.TableColumn.UNSORTED;
},getSortDirection:function(){
return this.currentSort;
},toggleSort:function(){
if(this.currentSort==Rico.TableColumn.UNSORTED||this.currentSort==Rico.TableColumn.SORT_DESC){
this.currentSort=Rico.TableColumn.SORT_ASC;
}else{
if(this.currentSort==Rico.TableColumn.SORT_ASC){
this.currentSort=Rico.TableColumn.SORT_DESC;
}
}
},setUnsorted:function(_358){
this.setSorted(Rico.TableColumn.UNSORTED);
},setSorted:function(_359){
this.currentSort=_359;
}};
Rico.ArrayExtensions=new Array();
if(Object.prototype.extend){
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Object.prototype.extend;
}else{
Object.prototype.extend=function(_360){
return Object.extend.apply(this,[this,_360]);
};
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Object.prototype.extend;
}
if(Array.prototype.push){
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.push;
}
if(!Array.prototype.remove){
Array.prototype.remove=function(dx){
if(isNaN(dx)||dx>this.length){
return false;
}
for(var i=0,n=0;i<this.length;i++){
if(i!=dx){
this[n++]=this[i];
}
}
this.length-=1;
};
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.remove;
}
if(!Array.prototype.removeItem){
Array.prototype.removeItem=function(item){
for(var i=0;i<this.length;i++){
if(this[i]==item){
this.remove(i);
break;
}
}
};
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.removeItem;
}
if(!Array.prototype.indices){
Array.prototype.indices=function(){
var _362=new Array();
for(index in this){
var _363=false;
for(var i=0;i<Rico.ArrayExtensions.length;i++){
if(this[index]==Rico.ArrayExtensions[i]){
_363=true;
break;
}
}
if(!_363){
_362[_362.length]=index;
}
}
return _362;
};
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.indices;
}
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.unique;
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Array.prototype.inArray;
if(window.DOMParser&&window.XMLSerializer&&window.Node&&Node.prototype&&Node.prototype.__defineGetter__){
if(!Document.prototype.loadXML){
Document.prototype.loadXML=function(s){
var doc2=(new DOMParser()).parseFromString(s,"text/xml");
while(this.hasChildNodes()){
this.removeChild(this.lastChild);
}
for(var i=0;i<doc2.childNodes.length;i++){
this.appendChild(this.importNode(doc2.childNodes[i],true));
}
};
}
Document.prototype.__defineGetter__("xml",function(){
return (new XMLSerializer()).serializeToString(this);
});
}
document.getElementsByTagAndClassName=function(_365,_366){
if(_365==null){
_365="*";
}
var _367=document.getElementsByTagName(_365)||document.all;
var _368=new Array();
if(_366==null){
return _367;
}
for(var i=0;i<_367.length;i++){
var _369=_367[i];
var _370=_369.className.split(" ");
for(var j=0;j<_370.length;j++){
if(_370[j]==_366){
_368.push(_369);
break;
}
}
}
return _368;
};
var RicoUtil={getElementsComputedStyle:function(_371,_372,_373){
if(arguments.length==2){
_373=_372;
}
var el=$(_371);
if(el.currentStyle){
return el.currentStyle[_372];
}else{
return document.defaultView.getComputedStyle(el,null).getPropertyValue(_373);
}
},createXmlDocument:function(){
if(document.implementation&&document.implementation.createDocument){
var doc=document.implementation.createDocument("","",null);
if(doc.readyState==null){
doc.readyState=1;
doc.addEventListener("load",function(){
doc.readyState=4;
if(typeof doc.onreadystatechange=="function"){
doc.onreadystatechange();
}
},false);
}
return doc;
}
if(window.ActiveXObject){
return Try.these(function(){
return new ActiveXObject("MSXML2.DomDocument");
},function(){
return new ActiveXObject("Microsoft.DomDocument");
},function(){
return new ActiveXObject("MSXML.DomDocument");
},function(){
return new ActiveXObject("MSXML3.DomDocument");
})||false;
}
return null;
},getContentAsString:function(_375){
return _375.xml!=undefined?this._getContentAsStringIE(_375):this._getContentAsStringMozilla(_375);
},_getContentAsStringIE:function(_376){
var _377="";
for(var i=0;i<_376.childNodes.length;i++){
var n=_376.childNodes[i];
if(n.nodeType==4){
_377+=n.nodeValue;
}else{
_377+=n.xml;
}
}
return _377;
},_getContentAsStringMozilla:function(_378){
var _379=new XMLSerializer();
var _380="";
for(var i=0;i<_378.childNodes.length;i++){
var n=_378.childNodes[i];
if(n.nodeType==4){
_380+=n.nodeValue;
}else{
_380+=_379.serializeToString(n);
}
}
return _380;
},toViewportPosition:function(_381){
return this._toAbsolute(_381,true);
},toDocumentPosition:function(_382){
return this._toAbsolute(_382,false);
},_toAbsolute:function(_383,_384){
if(navigator.userAgent.toLowerCase().indexOf("msie")==-1){
return this._toAbsoluteMozilla(_383,_384);
}
var x=0;
var y=0;
var _385=_383;
while(_385){
var _386=0;
var _387=0;
if(_385!=_383){
var _386=parseInt(this.getElementsComputedStyle(_385,"borderLeftWidth"));
var _387=parseInt(this.getElementsComputedStyle(_385,"borderTopWidth"));
_386=isNaN(_386)?0:_386;
_387=isNaN(_387)?0:_387;
}
x+=_385.offsetLeft-_385.scrollLeft+_386;
y+=_385.offsetTop-_385.scrollTop+_387;
_385=_385.offsetParent;
}
if(_384){
x-=this.docScrollLeft();
y-=this.docScrollTop();
}
return {x:x,y:y};
},_toAbsoluteMozilla:function(_388,_389){
var x=0;
var y=0;
var _390=_388;
while(_390){
x+=_390.offsetLeft;
y+=_390.offsetTop;
_390=_390.offsetParent;
}
_390=_388;
while(_390&&_390!=document.body&&_390!=document.documentElement){
if(_390.scrollLeft){
x-=_390.scrollLeft;
}
if(_390.scrollTop){
y-=_390.scrollTop;
}
_390=_390.parentNode;
}
if(_389){
x-=this.docScrollLeft();
y-=this.docScrollTop();
}
return {x:x,y:y};
},docScrollLeft:function(){
if(window.pageXOffset){
return window.pageXOffset;
}else{
if(document.documentElement&&document.documentElement.scrollLeft){
return document.documentElement.scrollLeft;
}else{
if(document.body){
return document.body.scrollLeft;
}else{
return 0;
}
}
}
},docScrollTop:function(){
if(window.pageYOffset){
return window.pageYOffset;
}else{
if(document.documentElement&&document.documentElement.scrollTop){
return document.documentElement.scrollTop;
}else{
if(document.body){
return document.body.scrollTop;
}else{
return 0;
}
}
}
}};
Prado.RicoLiveGrid=Class.create();
Prado.RicoLiveGrid.prototype=Object.extend(Rico.LiveGrid.prototype,{initialize:function(_391,_392){
this.options={tableClass:$(_391).className||"",loadingClass:$(_391).className||"",scrollerBorderRight:"1px solid #ababab",bufferTimeout:20000,sortAscendImg:"images/sort_asc.gif",sortDescendImg:"images/sort_desc.gif",sortImageWidth:9,sortImageHeight:5,ajaxSortURLParms:[],onRefreshComplete:null,requestParameters:null,inlineStyles:true,visibleRows:10,totalRows:0,initialOffset:0};
Object.extend(this.options,_392||{});
this.tableId=_391;
this.table=$(_391);
this.addLiveGridHtml();
var _393=this.table.rows[0].cells.length;
this.metaData=new Rico.LiveGridMetaData(this.options.visibleRows,this.options.totalRows,_393,_392);
this.buffer=new Rico.LiveGridBuffer(this.metaData);
var _394=this.table.rows.length;
this.viewPort=new Rico.GridViewPort(this.table,this.table.offsetHeight/_394,this.options.visibleRows,this.buffer,this);
this.scroller=new Rico.LiveGridScroller(this,this.viewPort);
this.options.sortHandler=this.sortHandler.bind(this);
if($(_391+"_header")){
this.sort=new Rico.LiveGridSort(_391+"_header",this.options);
}
this.processingRequest=null;
this.unprocessedRequest=null;
if(this.options.initialOffset>=0){
var _395=this.options.initialOffset;
this.scroller.moveScroll(_395);
this.viewPort.scrollTo(this.scroller.rowToPixel(_395));
if(this.options.sortCol){
this.sortCol=_392.sortCol;
this.sortDir=_392.sortDir;
}
var grid=this;
setTimeout(function(){
grid.requestContentRefresh(_395);
},100);
}
},fetchBuffer:function(_397){
if(this.buffer.isInRange(_397)&&!this.buffer.isNearingLimit(_397)){
return;
}
if(this.processingRequest){
this.unprocessedRequest=new Rico.LiveGridRequest(_397);
return;
}
var _398=this.buffer.getFetchOffset(_397);
this.processingRequest=new Rico.LiveGridRequest(_397);
this.processingRequest.bufferOffset=_398;
var _399=this.buffer.getFetchSize(_397);
var _400=false;
var _401={"page_size":_399,"offset":_398};
if(this.sortCol){
Object.extend(_401,{"sort_col":this.sortCol,"sort_dir":this.sortDir});
}
Prado.Callback(this.tableId,_401,this.ajaxUpdate.bind(this),this.options);
this.timeoutHandler=setTimeout(this.handleTimedOut.bind(this),this.options.bufferTimeout);
},ajaxUpdate:function(_402,_403){
try{
clearTimeout(this.timeoutHandler);
this.buffer.update(_402,this.processingRequest.bufferOffset);
this.viewPort.bufferChanged();
}
catch(err){
}
finally{
this.processingRequest=null;
}
this.processQueuedRequest();
}});
Object.extend(Rico.LiveGridBuffer.prototype,{update:function(_404,_405){
if(this.rows.length==0){
this.rows=_404;
this.size=this.rows.length;
this.startPos=_405;
return;
}
if(_405>this.startPos){
if(this.startPos+this.rows.length<_405){
this.rows=_404;
this.startPos=_405;
}else{
this.rows=this.rows.concat(_404.slice(0,_404.length));
if(this.rows.length>this.maxBufferSize){
var _406=this.rows.length;
this.rows=this.rows.slice(this.rows.length-this.maxBufferSize,this.rows.length);
this.startPos=this.startPos+(_406-this.rows.length);
}
}
}else{
if(_405+_404.length<this.startPos){
this.rows=_404;
}else{
this.rows=_404.slice(0,this.startPos).concat(this.rows);
if(this.rows.length>this.maxBufferSize){
this.rows=this.rows.slice(0,this.maxBufferSize);
}
}
this.startPos=_405;
}
this.size=this.rows.length;
}});
Object.extend(Rico.GridViewPort.prototype,{populateRow:function(_407,row){
if(isdef(_407)){
for(var j=0;j<row.length;j++){
_407.cells[j].innerHTML=row[j];
}
}
}});

