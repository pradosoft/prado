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
Rico.Corner={round:function(e,_43){
var e=$(e);
this._setOptions(_43);
var _44=this.options.color;
if(this.options.color=="fromElement"){
_44=this._background(e);
}
var _45=this.options.bgColor;
if(this.options.bgColor=="fromParent"){
_45=this._background(e.offsetParent);
}
this._roundCornersImpl(e,_44,_45);
},_roundCornersImpl:function(e,_46,_47){
if(this.options.border){
this._renderBorder(e,_47);
}
if(this._isTopRounded()){
this._roundTopCorners(e,_46,_47);
}
if(this._isBottomRounded()){
this._roundBottomCorners(e,_46,_47);
}
},_renderBorder:function(el,_49){
var _50="1px solid "+this._borderColor(_49);
var _51="border-left: "+_50;
var _52="border-right: "+_50;
var _53="style='"+_51+";"+_52+"'";
el.innerHTML="<div "+_53+">"+el.innerHTML+"</div>";
},_roundTopCorners:function(el,_54,_55){
var _56=this._createCorner(_55);
for(var i=0;i<this.options.numSlices;i++){
_56.appendChild(this._createCornerSlice(_54,_55,i,"top"));
}
el.style.paddingTop=0;
el.insertBefore(_56,el.firstChild);
},_roundBottomCorners:function(el,_57,_58){
var _59=this._createCorner(_58);
for(var i=(this.options.numSlices-1);i>=0;i--){
_59.appendChild(this._createCornerSlice(_57,_58,i,"bottom"));
}
el.style.paddingBottom=0;
el.appendChild(_59);
},_createCorner:function(_60){
var _61=document.createElement("div");
_61.style.backgroundColor=(this._isTransparent()?"transparent":_60);
return _61;
},_createCornerSlice:function(_62,_63,n,_65){
var _66=document.createElement("span");
var _67=_66.style;
_67.backgroundColor=_62;
_67.display="block";
_67.height="1px";
_67.overflow="hidden";
_67.fontSize="1px";
var _68=this._borderColor(_62,_63);
if(this.options.border&&n==0){
_67.borderTopStyle="solid";
_67.borderTopWidth="1px";
_67.borderLeftWidth="0px";
_67.borderRightWidth="0px";
_67.borderBottomWidth="0px";
_67.height="0px";
_67.borderColor=_68;
}else{
if(_68){
_67.borderColor=_68;
_67.borderStyle="solid";
_67.borderWidth="0px 1px";
}
}
if(!this.options.compact&&(n==(this.options.numSlices-1))){
_67.height="2px";
}
this._setMargin(_66,n,_65);
this._setBorder(_66,n,_65);
return _66;
},_setOptions:function(_69){
this.options={corners:"all",color:"fromElement",bgColor:"fromParent",blend:true,border:false,compact:false};
Object.extend(this.options,_69||{});
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
},_borderColor:function(_70,_71){
if(_70=="transparent"){
return _71;
}else{
if(this.options.border){
return this.options.border;
}else{
if(this.options.blend){
return this._blend(_71,_70);
}else{
return "";
}
}
}
},_setMargin:function(el,n,_72){
var _73=this._marginSize(n);
var _74=_72=="top"?this._whichSideTop():this._whichSideBottom();
if(_74=="left"){
el.style.marginLeft=_73+"px";
el.style.marginRight="0px";
}else{
if(_74=="right"){
el.style.marginRight=_73+"px";
el.style.marginLeft="0px";
}else{
el.style.marginLeft=_73+"px";
el.style.marginRight=_73+"px";
}
}
},_setBorder:function(el,n,_75){
var _76=this._borderSize(n);
var _77=_75=="top"?this._whichSideTop():this._whichSideBottom();
if(_77=="left"){
el.style.borderLeftWidth=_76+"px";
el.style.borderRightWidth="0px";
}else{
if(_77=="right"){
el.style.borderRightWidth=_76+"px";
el.style.borderLeftWidth="0px";
}else{
el.style.borderLeftWidth=_76+"px";
el.style.borderRightWidth=_76+"px";
}
}
if(this.options.border!=false){
el.style.borderLeftWidth=_76+"px";
}
el.style.borderRightWidth=_76+"px";
},_marginSize:function(n){
if(this._isTransparent()){
return 0;
}
var _78=[5,3,2,1];
var _79=[3,2,1,0];
var _80=[2,1];
var _81=[1,0];
if(this.options.compact&&this.options.blend){
return _81[n];
}else{
if(this.options.compact){
return _80[n];
}else{
if(this.options.blend){
return _79[n];
}else{
return _78[n];
}
}
}
},_borderSize:function(n){
var _82=[5,3,2,1];
var _83=[2,1,1,1];
var _84=[1,0];
var _85=[0,2,0,0];
if(this.options.compact&&(this.options.blend||this._isTransparent())){
return 1;
}else{
if(this.options.compact){
return _84[n];
}else{
if(this.options.blend){
return _83[n];
}else{
if(this.options.border){
return _85[n];
}else{
if(this._isTransparent()){
return _82[n];
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
Effect.SizeAndPosition.prototype={initialize:function(_90,x,y,w,h,_95,_96,_97){
this.element=$(_90);
this.x=x;
this.y=y;
this.w=w;
this.h=h;
this.duration=_95;
this.steps=_96;
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
var _98=Math.round(this.duration/this.steps);
var _99=this.element.offsetLeft;
var _100=this.element.offsetTop;
var _101=this.element.offsetWidth;
var _102=this.element.offsetHeight;
this.x=(this.x)?this.x:_99;
this.y=(this.y)?this.y:_100;
this.w=(this.w)?this.w:_101;
this.h=(this.h)?this.h:_102;
var difX=this.steps>0?(this.x-_99)/this.steps:0;
var difY=this.steps>0?(this.y-_100)/this.steps:0;
var difW=this.steps>0?(this.w-_101)/this.steps:0;
var difH=this.steps>0?(this.h-_102)/this.steps:0;
this.moveBy(difX,difY);
this.resizeBy(difW,difH);
this.duration-=_98;
this.steps--;
this.timer=setTimeout(this.sizeAndPosition.bind(this),_98);
},isFinished:function(){
return this.steps<=0;
},moveBy:function(difX,difY){
var _107=this.element.offsetLeft;
var _108=this.element.offsetTop;
var _109=parseInt(difX);
var _110=parseInt(difY);
var _111=this.element.style;
if(_109!=0){
_111.left=(_107+_109)+"px";
}
if(_110!=0){
_111.top=(_108+_110)+"px";
}
},resizeBy:function(difW,difH){
var _112=this.element.offsetWidth;
var _113=this.element.offsetHeight;
var _114=parseInt(difW);
var _115=parseInt(difH);
var _116=this.element.style;
if(_114!=0){
_116.width=(_112+_114)+"px";
}
if(_115!=0){
_116.height=(_113+_115)+"px";
}
}};
Effect.Size=Class.create();
Effect.Size.prototype={initialize:function(_117,w,h,_118,_119,_120){
new Effect.SizeAndPosition(_117,null,null,w,h,_118,_119,_120);
}};
Effect.Position=Class.create();
Effect.Position.prototype={initialize:function(_121,x,y,_122,_123,_124){
new Effect.SizeAndPosition(_121,x,y,null,null,_122,_123,_124);
}};
Effect.Round=Class.create();
Effect.Round.prototype={initialize:function(_125,_126,_127){
var _128=document.getElementsByTagAndClassName(_125,_126);
for(var i=0;i<_128.length;i++){
Rico.Corner.round(_128[i],_127);
}
}};
Effect.FadeTo=Class.create();
Effect.FadeTo.prototype={initialize:function(_129,_130,_131,_132,_133){
this.element=$(_129);
this.opacity=_130;
this.duration=_131;
this.steps=_132;
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
var _134=Math.round(this.duration/this.steps);
var _135=this.getElementOpacity();
var _136=this.steps>0?(this.opacity-_135)/this.steps:0;
this.changeOpacityBy(_136);
this.duration-=_134;
this.steps--;
this.timer=setTimeout(this.fadeTo.bind(this),_134);
},changeOpacityBy:function(v){
var _138=this.getElementOpacity();
var _139=Math.max(0,Math.min(_138+v,1));
this.element.ricoOpacity=_139;
this.element.style.filter="alpha(opacity:"+Math.round(_139*100)+")";
this.element.style.opacity=_139;
},isFinished:function(){
return this.steps<=0;
},getElementOpacity:function(){
if(this.element.ricoOpacity==undefined){
var _140=RicoUtil.getElementsComputedStyle(this.element,"opacity");
this.element.ricoOpacity=_140!=undefined?_140:1;
}
return parseFloat(this.element.ricoOpacity);
}};
Effect.AccordionSize=Class.create();
Effect.AccordionSize.prototype={initialize:function(e1,e2,_143,end,_145,_146,_147){
this.e1=$(e1);
this.e2=$(e2);
this.start=_143;
this.end=end;
this.duration=_145;
this.steps=_146;
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
var _148=Math.round(this.duration/this.steps);
var diff=this.steps>0?(parseInt(this.e1.offsetHeight)-this.start)/this.steps:0;
this.resizeBy(diff);
this.duration-=_148;
this.steps--;
this.timer=setTimeout(this.accordionSize.bind(this),_148);
},isFinished:function(){
return this.steps<=0;
},resizeBy:function(diff){
var _150=this.e1.offsetHeight;
var _151=this.e2.offsetHeight;
var _152=parseInt(diff);
if(diff!=0){
this.e1.style.height=(_150-_152)+"px";
this.e2.style.height=(_151+_152)+"px";
}
}};
if(window.Effect==undefined){
Effect={};
}
Effect.SizeAndPosition=Class.create();
Effect.SizeAndPosition.prototype={initialize:function(_153,x,y,w,h,_154,_155,_156){
this.element=$(_153);
this.x=x;
this.y=y;
this.w=w;
this.h=h;
this.duration=_154;
this.steps=_155;
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
var _157=Math.round(this.duration/this.steps);
var _158=this.element.offsetLeft;
var _159=this.element.offsetTop;
var _160=this.element.offsetWidth;
var _161=this.element.offsetHeight;
this.x=(this.x)?this.x:_158;
this.y=(this.y)?this.y:_159;
this.w=(this.w)?this.w:_160;
this.h=(this.h)?this.h:_161;
var difX=this.steps>0?(this.x-_158)/this.steps:0;
var difY=this.steps>0?(this.y-_159)/this.steps:0;
var difW=this.steps>0?(this.w-_160)/this.steps:0;
var difH=this.steps>0?(this.h-_161)/this.steps:0;
this.moveBy(difX,difY);
this.resizeBy(difW,difH);
this.duration-=_157;
this.steps--;
this.timer=setTimeout(this.sizeAndPosition.bind(this),_157);
},isFinished:function(){
return this.steps<=0;
},moveBy:function(difX,difY){
var _162=this.element.offsetLeft;
var _163=this.element.offsetTop;
var _164=parseInt(difX);
var _165=parseInt(difY);
var _166=this.element.style;
if(_164!=0){
_166.left=(_162+_164)+"px";
}
if(_165!=0){
_166.top=(_163+_165)+"px";
}
},resizeBy:function(difW,difH){
var _167=this.element.offsetWidth;
var _168=this.element.offsetHeight;
var _169=parseInt(difW);
var _170=parseInt(difH);
var _171=this.element.style;
if(_169!=0){
_171.width=(_167+_169)+"px";
}
if(_170!=0){
_171.height=(_168+_170)+"px";
}
}};
Effect.Size=Class.create();
Effect.Size.prototype={initialize:function(_172,w,h,_173,_174,_175){
new Effect.SizeAndPosition(_172,null,null,w,h,_173,_174,_175);
}};
Effect.Position=Class.create();
Effect.Position.prototype={initialize:function(_176,x,y,_177,_178,_179){
new Effect.SizeAndPosition(_176,x,y,null,null,_177,_178,_179);
}};
Effect.Round=Class.create();
Effect.Round.prototype={initialize:function(_180,_181,_182){
var _183=document.getElementsByTagAndClassName(_180,_181);
for(var i=0;i<_183.length;i++){
Rico.Corner.round(_183[i],_182);
}
}};
Effect.FadeTo=Class.create();
Effect.FadeTo.prototype={initialize:function(_184,_185,_186,_187,_188){
this.element=$(_184);
this.opacity=_185;
this.duration=_186;
this.steps=_187;
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
var _189=Math.round(this.duration/this.steps);
var _190=this.getElementOpacity();
var _191=this.steps>0?(this.opacity-_190)/this.steps:0;
this.changeOpacityBy(_191);
this.duration-=_189;
this.steps--;
this.timer=setTimeout(this.fadeTo.bind(this),_189);
},changeOpacityBy:function(v){
var _192=this.getElementOpacity();
var _193=Math.max(0,Math.min(_192+v,1));
this.element.ricoOpacity=_193;
this.element.style.filter="alpha(opacity:"+Math.round(_193*100)+")";
this.element.style.opacity=_193;
},isFinished:function(){
return this.steps<=0;
},getElementOpacity:function(){
if(this.element.ricoOpacity==undefined){
var _194=RicoUtil.getElementsComputedStyle(this.element,"opacity");
this.element.ricoOpacity=_194!=undefined?_194:1;
}
return parseFloat(this.element.ricoOpacity);
}};
Effect.AccordionSize=Class.create();
Effect.AccordionSize.prototype={initialize:function(e1,e2,_195,end,_196,_197,_198){
this.e1=$(e1);
this.e2=$(e2);
this.start=_195;
this.end=end;
this.duration=_196;
this.steps=_197;
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
var _199=Math.round(this.duration/this.steps);
var diff=this.steps>0?(parseInt(this.e1.offsetHeight)-this.start)/this.steps:0;
this.resizeBy(diff);
this.duration-=_199;
this.steps--;
this.timer=setTimeout(this.accordionSize.bind(this),_199);
},isFinished:function(){
return this.steps<=0;
},resizeBy:function(diff){
var _200=this.e1.offsetHeight;
var _201=this.e2.offsetHeight;
var _202=parseInt(diff);
if(diff!=0){
this.e1.style.height=(_200-_202)+"px";
this.e2.style.height=(_201+_202)+"px";
}
}};
Rico.LiveGridMetaData=Class.create();
Rico.LiveGridMetaData.prototype={initialize:function(_203,_204,_205,_206){
this.pageSize=_203;
this.totalRows=_204;
this.setOptions(_206);
this.ArrowHeight=16;
this.columnCount=_205;
},setOptions:function(_207){
this.options={largeBufferSize:7,nearLimitFactor:0.2};
Object.extend(this.options,_207||{});
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
Rico.LiveGridScroller.prototype={initialize:function(_208,_209){
this.isIE=navigator.userAgent.toLowerCase().indexOf("msie")>=0;
this.liveGrid=_208;
this.metaData=_208.metaData;
this.createScrollBar();
this.scrollTimeout=null;
this.lastScrollPos=0;
this.viewPort=_209;
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
var _210=$(this.liveGrid.tableId+"_header");
if(_210){
_210.rows[0].cells[0].style.width=(_210.rows[0].cells[0].offsetWidth+1)+"px";
}
},createScrollBar:function(){
var _211=this.liveGrid.viewPort.visibleHeight();
this.scrollerDiv=document.createElement("div");
var _212=this.scrollerDiv.style;
_212.borderRight=this.liveGrid.options.scrollerBorderRight;
_212.position="relative";
_212.left=this.isIE?"-6px":"-3px";
_212.width="19px";
_212.height=_211+"px";
_212.overflow="auto";
this.heightDiv=document.createElement("div");
this.heightDiv.style.width="1px";
this.heightDiv.style.height=parseInt(_211*this.metaData.getTotalRows()/this.metaData.getPageSize())+"px";
this.scrollerDiv.appendChild(this.heightDiv);
this.scrollerDiv.onscroll=this.handleScroll.bindAsEventListener(this);
var _213=this.liveGrid.table;
_213.parentNode.parentNode.insertBefore(this.scrollerDiv,_213.parentNode.nextSibling);
var _214=this.isIE?"mousewheel":"DOMMouseScroll";
Event.observe(_213,_214,function(evt){
if(evt.wheelDelta>=0||evt.detail<0){
this.scrollerDiv.scrollTop-=(2*this.viewPort.rowHeight);
}else{
this.scrollerDiv.scrollTop+=(2*this.viewPort.rowHeight);
}
this.handleScroll(false);
}.bindAsEventListener(this),false);
},updateSize:function(){
var _216=this.liveGrid.table;
var _217=this.viewPort.visibleHeight();
this.heightDiv.style.height=parseInt(_217*this.metaData.getTotalRows()/this.metaData.getPageSize())+"px";
},rowToPixel:function(_218){
return (_218/this.metaData.getTotalRows())*this.heightDiv.offsetHeight;
},moveScroll:function(_219){
this.scrollerDiv.scrollTop=this.rowToPixel(_219);
if(this.metaData.options.onscroll){
this.metaData.options.onscroll(this.liveGrid,_219);
}
},handleScroll:function(){
if(this.scrollTimeout){
clearTimeout(this.scrollTimeout);
}
var _220=this.lastScrollPos-this.scrollerDiv.scrollTop;
if(_220!=0){
var r=this.scrollerDiv.scrollTop%this.viewPort.rowHeight;
if(r!=0){
this.unplug();
if(_220<0){
this.scrollerDiv.scrollTop+=(this.viewPort.rowHeight-r);
}else{
this.scrollerDiv.scrollTop-=r;
}
this.plugin();
}
}
var _222=parseInt(this.scrollerDiv.scrollTop/this.viewPort.rowHeight);
this.liveGrid.requestContentRefresh(_222);
this.viewPort.scrollTo(this.scrollerDiv.scrollTop);
if(this.metaData.options.onscroll){
this.metaData.options.onscroll(this.liveGrid,_222);
}
this.scrollTimeout=setTimeout(this.scrollIdle.bind(this),1200);
this.lastScrollPos=this.scrollerDiv.scrollTop;
},scrollIdle:function(){
if(this.metaData.options.onscrollidle){
this.metaData.options.onscrollidle();
}
}};
Rico.LiveGridBuffer=Class.create();
Rico.LiveGridBuffer.prototype={initialize:function(_223,_224){
this.startPos=0;
this.size=0;
this.metaData=_223;
this.rows=new Array();
this.updateInProgress=false;
this.viewPort=_224;
this.maxBufferSize=_223.getLargeBufferSize()*2;
this.maxFetchSize=_223.getLargeBufferSize();
this.lastOffset=0;
},getBlankRow:function(){
if(!this.blankRow){
this.blankRow=new Array();
for(var i=0;i<this.metaData.columnCount;i++){
this.blankRow[i]="&nbsp;";
}
}
return this.blankRow;
},loadRows:function(_225){
var _226=_225.getElementsByTagName("rows")[0];
this.updateUI=_226.getAttribute("update_ui")=="true";
var _227=new Array();
var trs=_226.getElementsByTagName("tr");
for(var i=0;i<trs.length;i++){
var row=_227[i]=new Array();
var _230=trs[i].getElementsByTagName("td");
for(var j=0;j<_230.length;j++){
var cell=_230[j];
var _232=cell.getAttribute("convert_spaces")=="true";
var _233=RicoUtil.getContentAsString(cell);
row[j]=_232?this.convertSpaces(_233):_233;
if(!row[j]){
row[j]="&nbsp;";
}
}
}
return _227;
},update:function(_234,_235){
var _236=this.loadRows(_234);
if(this.rows.length==0){
this.rows=_236;
this.size=this.rows.length;
this.startPos=_235;
return;
}
if(_235>this.startPos){
if(this.startPos+this.rows.length<_235){
this.rows=_236;
this.startPos=_235;
}else{
this.rows=this.rows.concat(_236.slice(0,_236.length));
if(this.rows.length>this.maxBufferSize){
var _237=this.rows.length;
this.rows=this.rows.slice(this.rows.length-this.maxBufferSize,this.rows.length);
this.startPos=this.startPos+(_237-this.rows.length);
}
}
}else{
if(_235+_236.length<this.startPos){
this.rows=_236;
}else{
this.rows=_236.slice(0,this.startPos).concat(this.rows);
if(this.rows.length>this.maxBufferSize){
this.rows=this.rows.slice(0,this.maxBufferSize);
}
}
this.startPos=_235;
}
this.size=this.rows.length;
},clear:function(){
this.rows=new Array();
this.startPos=0;
this.size=0;
},isOverlapping:function(_238,size){
return ((_238<this.endPos())&&(this.startPos<_238+size))||(this.endPos()==0);
},isInRange:function(_240){
return (_240>=this.startPos)&&(_240+this.metaData.getPageSize()<=this.endPos());
},isNearingTopLimit:function(_241){
return _241-this.startPos<this.metaData.getLimitTolerance();
},endPos:function(){
return this.startPos+this.rows.length;
},isNearingBottomLimit:function(_242){
return this.endPos()-(_242+this.metaData.getPageSize())<this.metaData.getLimitTolerance();
},isAtTop:function(){
return this.startPos==0;
},isAtBottom:function(){
return this.endPos()==this.metaData.getTotalRows();
},isNearingLimit:function(_243){
return (!this.isAtTop()&&this.isNearingTopLimit(_243))||(!this.isAtBottom()&&this.isNearingBottomLimit(_243));
},getFetchSize:function(_244){
var _245=this.getFetchOffset(_244);
var _246=0;
if(_245>=this.startPos){
var _247=this.maxFetchSize+_245;
if(_247>this.metaData.totalRows){
_247=this.metaData.totalRows;
}
_246=_247-_245;
if(_245==0&&_246<this.maxFetchSize){
_246=this.maxFetchSize;
}
}else{
var _246=this.startPos-_245;
if(_246>this.maxFetchSize){
_246=this.maxFetchSize;
}
}
return _246;
},getFetchOffset:function(_248){
var _249=_248;
if(_248>this.startPos){
_249=(_248>this.endPos())?_248:this.endPos();
}else{
if(_248+this.maxFetchSize>=this.startPos){
var _249=this.startPos-this.maxFetchSize;
if(_249<0){
_249=0;
}
}
}
this.lastOffset=_249;
return _249;
},getRows:function(_250,_251){
var _252=_250-this.startPos;
var _253=_252+_251;
if(_253>this.size){
_253=this.size;
}
var _254=new Array();
var _255=0;
for(var i=_252;i<_253;i++){
_254[_255++]=this.rows[i];
}
return _254;
},convertSpaces:function(s){
return s.split(" ").join("&nbsp;");
}};
Rico.GridViewPort=Class.create();
Rico.GridViewPort.prototype={initialize:function(_256,_257,_258,_259,_260){
this.lastDisplayedStartPos=0;
this.div=_256.parentNode;
this.table=_256;
this.rowHeight=_257;
this.div.style.height=this.rowHeight*_258;
this.div.style.overflow="hidden";
this.buffer=_259;
this.liveGrid=_260;
this.visibleRows=_258+1;
this.lastPixelOffset=0;
this.startPos=0;
},populateRow:function(_261,row){
for(var j=0;j<row.length;j++){
_261.cells[j].innerHTML=row[j];
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
},refreshContents:function(_262){
if(_262==this.lastRowPos&&!this.isPartialBlank&&!this.isBlank){
return;
}
if((_262+this.visibleRows<this.buffer.startPos)||(this.buffer.startPos+this.buffer.size<_262)||(this.buffer.size==0)){
this.clearRows();
return;
}
this.isBlank=false;
var _263=this.buffer.startPos>_262;
var _264=_263?this.buffer.startPos:_262;
var _265=(this.buffer.startPos+this.buffer.size<_262+this.visibleRows)?this.buffer.startPos+this.buffer.size:_262+this.visibleRows;
var _266=_265-_264;
var rows=this.buffer.getRows(_264,_266);
var _268=this.visibleRows-_266;
var _269=_263?0:_266;
var _270=_263?_268:0;
for(var i=0;i<rows.length;i++){
this.populateRow(this.table.rows[i+_270],rows[i]);
}
for(var i=0;i<_268;i++){
this.populateRow(this.table.rows[i+_269],this.buffer.getBlankRow());
}
this.isPartialBlank=_268>0;
this.lastRowPos=_262;
this.liveGrid.table.className=this.liveGrid.options.tableClass;
var _271=this.liveGrid.options.onRefreshComplete;
if(_271!=null){
_271();
}
},scrollTo:function(_272){
if(this.lastPixelOffset==_272){
return;
}
this.refreshContents(parseInt(_272/this.rowHeight));
this.div.scrollTop=_272%this.rowHeight;
this.lastPixelOffset=_272;
},visibleHeight:function(){
return parseInt(RicoUtil.getElementsComputedStyle(this.div,"height"));
}};
Rico.LiveGridRequest=Class.create();
Rico.LiveGridRequest.prototype={initialize:function(_273,_274){
this.requestOffset=_273;
}};
Rico.LiveGrid=Class.create();
Rico.LiveGrid.prototype={initialize:function(_275,_276,_277,url,_279,_280){
this.options={tableClass:$(_275).className,loadingClass:$(_275).className,scrollerBorderRight:"1px solid #ababab",bufferTimeout:20000,sortAscendImg:"images/sort_asc.gif",sortDescendImg:"images/sort_desc.gif",sortImageWidth:9,sortImageHeight:5,ajaxSortURLParms:[],onRefreshComplete:null,requestParameters:null,inlineStyles:true};
Object.extend(this.options,_279||{});
this.ajaxOptions={parameters:null};
Object.extend(this.ajaxOptions,_280||{});
this.tableId=_275;
this.table=$(_275);
this.addLiveGridHtml();
var _281=this.table.rows[0].cells.length;
this.metaData=new Rico.LiveGridMetaData(_276,_277,_281,_279);
this.buffer=new Rico.LiveGridBuffer(this.metaData);
var _282=this.table.rows.length;
this.viewPort=new Rico.GridViewPort(this.table,this.table.offsetHeight/_282,_276,this.buffer,this);
this.scroller=new Rico.LiveGridScroller(this,this.viewPort);
this.options.sortHandler=this.sortHandler.bind(this);
if($(_275+"_header")){
this.sort=new Rico.LiveGridSort(_275+"_header",this.options);
}
this.processingRequest=null;
this.unprocessedRequest=null;
this.initAjax(url);
if(this.options.prefetchBuffer||this.options.prefetchOffset>0){
var _283=0;
if(this.options.offset){
_283=this.options.offset;
this.scroller.moveScroll(_283);
this.viewPort.scrollTo(this.scroller.rowToPixel(_283));
}
if(this.options.sortCol){
this.sortCol=_279.sortCol;
this.sortDir=_279.sortDir;
}
this.requestContentRefresh(_283);
}
},addLiveGridHtml:function(){
if(this.table.getElementsByTagName("thead").length>0){
var _284=this.table.cloneNode(true);
_284.setAttribute("id",this.tableId+"_header");
_284.setAttribute("class",this.table.className+"_header");
for(var i=0;i<_284.tBodies.length;i++){
_284.removeChild(_284.tBodies[i]);
}
this.table.deleteTHead();
this.table.parentNode.insertBefore(_284,this.table);
}
new Insertion.Before(this.table,"<div id='"+this.tableId+"_container'></div>");
this.table.previousSibling.appendChild(this.table);
new Insertion.Before(this.table,"<div id='"+this.tableId+"_viewport' style='float:left;'></div>");
this.table.previousSibling.appendChild(this.table);
},resetContents:function(){
this.scroller.moveScroll(0);
this.buffer.clear();
this.viewPort.clearContents();
},sortHandler:function(_285){
this.sortCol=_285.name;
this.sortDir=_285.currentSort;
this.resetContents();
this.requestContentRefresh(0);
},setTotalRows:function(_286){
this.resetContents();
this.metaData.setTotalRows(_286);
this.scroller.updateSize();
},initAjax:function(url){
ajaxEngine.registerRequest(this.tableId+"_request",url);
ajaxEngine.registerAjaxObject(this.tableId+"_updater",this);
},invokeAjax:function(){
},handleTimedOut:function(){
this.processingRequest=null;
this.processQueuedRequest();
},fetchBuffer:function(_287){
if(this.buffer.isInRange(_287)&&!this.buffer.isNearingLimit(_287)){
return;
}
if(this.processingRequest){
this.unprocessedRequest=new Rico.LiveGridRequest(_287);
return;
}
var _288=this.buffer.getFetchOffset(_287);
this.processingRequest=new Rico.LiveGridRequest(_287);
this.processingRequest.bufferOffset=_288;
var _289=this.buffer.getFetchSize(_287);
var _290=false;
var _291;
if(this.options.requestParameters){
_291=this._createQueryString(this.options.requestParameters,0);
}
_291=(_291==null)?"":_291+"&";
_291=_291+"id="+this.tableId+"&page_size="+_289+"&offset="+_288;
if(this.sortCol){
_291=_291+"&sort_col="+escape(this.sortCol)+"&sort_dir="+this.sortDir;
}
this.ajaxOptions.parameters=_291;
ajaxEngine.sendRequest(this.tableId+"_request",this.ajaxOptions);
this.timeoutHandler=setTimeout(this.handleTimedOut.bind(this),this.options.bufferTimeout);
},setRequestParams:function(){
this.options.requestParameters=[];
for(var i=0;i<arguments.length;i++){
this.options.requestParameters[i]=arguments[i];
}
},requestContentRefresh:function(_292){
this.fetchBuffer(_292);
},ajaxUpdate:function(_293){
try{
clearTimeout(this.timeoutHandler);
this.buffer.update(_293,this.processingRequest.bufferOffset);
this.viewPort.bufferChanged();
}
catch(err){
}
finally{
this.processingRequest=null;
}
this.processQueuedRequest();
},_createQueryString:function(_294,_295){
var _296="";
if(!_294){
return _296;
}
for(var i=_295;i<_294.length;i++){
if(i!=_295){
_296+="&";
}
var _297=_294[i];
if(_297.name!=undefined&&_297.value!=undefined){
_296+=_297.name+"="+escape(_297.value);
}else{
var ePos=_297.indexOf("=");
var _299=_297.substring(0,ePos);
var _300=_297.substring(ePos+1);
_296+=_299+"="+escape(_300);
}
}
return _296;
},processQueuedRequest:function(){
if(this.unprocessedRequest!=null){
this.requestContentRefresh(this.unprocessedRequest.requestOffset);
this.unprocessedRequest=null;
}
}};
Rico.LiveGridSort=Class.create();
Rico.LiveGridSort.prototype={initialize:function(_301,_302){
this.headerTableId=_301;
this.headerTable=$(_301);
this.options=_302;
this.setOptions();
this.applySortBehavior();
if(this.options.sortCol){
this.setSortUI(this.options.sortCol,this.options.sortDir);
}
},setSortUI:function(_303,_304){
var cols=this.options.columns;
for(var i=0;i<cols.length;i++){
if(cols[i].name==_303){
this.setColumnSort(i,_304);
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
var _306=this.headerTable.rows[0];
var _307=_306.cells;
for(var i=0;i<_307.length;i++){
this.addSortBehaviorToColumn(i,_307[i]);
}
},addSortBehaviorToColumn:function(n,cell){
if(this.options.columns[n].isSortable()){
cell.id=this.headerTableId+"_"+n;
cell.style.cursor="pointer";
cell.onclick=this.headerCellClicked.bindAsEventListener(this);
cell.innerHTML=cell.innerHTML+"<span id=\""+this.headerTableId+"_img_"+n+"\">"+"&nbsp;&nbsp;&nbsp;</span>";
}
},headerCellClicked:function(evt){
var _308=evt.target?evt.target:evt.srcElement;
var _309=_308.id;
var _310=parseInt(_309.substring(_309.lastIndexOf("_")+1));
var _311=this.getSortedColumnIndex();
if(_311!=-1){
if(_311!=_310){
this.removeColumnSort(_311);
this.setColumnSort(_310,Rico.TableColumn.SORT_ASC);
}else{
this.toggleColumnSort(_311);
}
}else{
this.setColumnSort(_310,Rico.TableColumn.SORT_ASC);
}
if(this.options.sortHandler){
this.options.sortHandler(this.options.columns[_310]);
}
},removeColumnSort:function(n){
this.options.columns[n].setUnsorted();
this.setSortImage(n);
},setColumnSort:function(n,_312){
this.options.columns[n].setSorted(_312);
this.setSortImage(n);
},toggleColumnSort:function(n){
this.options.columns[n].toggleSort();
this.setSortImage(n);
},setSortImage:function(n){
var _313=this.options.columns[n].getSortDirection();
var _314=$(this.headerTableId+"_img_"+n);
if(_313==Rico.TableColumn.UNSORTED){
_314.innerHTML="&nbsp;&nbsp;";
}else{
if(_313==Rico.TableColumn.SORT_ASC){
_314.innerHTML="&nbsp;&nbsp;<img width=\""+this.options.sortImageWidth+"\" "+"height=\""+this.options.sortImageHeight+"\" "+"src=\""+this.options.sortAscendImg+"\"/>";
}else{
if(_313==Rico.TableColumn.SORT_DESC){
_314.innerHTML="&nbsp;&nbsp;<img width=\""+this.options.sortImageWidth+"\" "+"height=\""+this.options.sortImageHeight+"\" "+"src=\""+this.options.sortDescendImg+"\"/>";
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
var _315=new Array();
var _316=this.headerTable.rows[0];
var _317=_316.cells;
for(var i=0;i<_317.length;i++){
_315.push(new Rico.TableColumn(this.deriveColumnNameFromCell(_317[i],i),true));
}
return _315;
},convertToTableColumns:function(cols){
var _318=new Array();
for(var i=0;i<cols.length;i++){
_318.push(new Rico.TableColumn(cols[i][0],cols[i][1]));
}
return _318;
},deriveColumnNameFromCell:function(cell,_319){
var _320=cell.innerText!=undefined?cell.innerText:cell.textContent;
return _320?_320.toLowerCase().split(" ").join("_"):"col_"+_319;
}};
Rico.TableColumn=Class.create();
Rico.TableColumn.UNSORTED=0;
Rico.TableColumn.SORT_ASC="ASC";
Rico.TableColumn.SORT_DESC="DESC";
Rico.TableColumn.prototype={initialize:function(name,_322){
this.name=name;
this.sortable=_322;
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
},setUnsorted:function(_323){
this.setSorted(Rico.TableColumn.UNSORTED);
},setSorted:function(_324){
this.currentSort=_324;
}};
Rico.ArrayExtensions=new Array();
if(Object.prototype.extend){
Rico.ArrayExtensions[Rico.ArrayExtensions.length]=Object.prototype.extend;
}else{
Object.prototype.extend=function(_325){
return Object.extend.apply(this,[this,_325]);
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
var _327=new Array();
for(index in this){
var _328=false;
for(var i=0;i<Rico.ArrayExtensions.length;i++){
if(this[index]==Rico.ArrayExtensions[i]){
_328=true;
break;
}
}
if(!_328){
_327[_327.length]=index;
}
}
return _327;
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
document.getElementsByTagAndClassName=function(_330,_331){
if(_330==null){
_330="*";
}
var _332=document.getElementsByTagName(_330)||document.all;
var _333=new Array();
if(_331==null){
return _332;
}
for(var i=0;i<_332.length;i++){
var _334=_332[i];
var _335=_334.className.split(" ");
for(var j=0;j<_335.length;j++){
if(_335[j]==_331){
_333.push(_334);
break;
}
}
}
return _333;
};
var RicoUtil={getElementsComputedStyle:function(_336,_337,_338){
if(arguments.length==2){
_338=_337;
}
var el=$(_336);
if(el.currentStyle){
return el.currentStyle[_337];
}else{
return document.defaultView.getComputedStyle(el,null).getPropertyValue(_338);
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
},getContentAsString:function(_340){
return _340.xml!=undefined?this._getContentAsStringIE(_340):this._getContentAsStringMozilla(_340);
},_getContentAsStringIE:function(_341){
var _342="";
for(var i=0;i<_341.childNodes.length;i++){
var n=_341.childNodes[i];
if(n.nodeType==4){
_342+=n.nodeValue;
}else{
_342+=n.xml;
}
}
return _342;
},_getContentAsStringMozilla:function(_343){
var _344=new XMLSerializer();
var _345="";
for(var i=0;i<_343.childNodes.length;i++){
var n=_343.childNodes[i];
if(n.nodeType==4){
_345+=n.nodeValue;
}else{
_345+=_344.serializeToString(n);
}
}
return _345;
},toViewportPosition:function(_346){
return this._toAbsolute(_346,true);
},toDocumentPosition:function(_347){
return this._toAbsolute(_347,false);
},_toAbsolute:function(_348,_349){
if(navigator.userAgent.toLowerCase().indexOf("msie")==-1){
return this._toAbsoluteMozilla(_348,_349);
}
var x=0;
var y=0;
var _350=_348;
while(_350){
var _351=0;
var _352=0;
if(_350!=_348){
var _351=parseInt(this.getElementsComputedStyle(_350,"borderLeftWidth"));
var _352=parseInt(this.getElementsComputedStyle(_350,"borderTopWidth"));
_351=isNaN(_351)?0:_351;
_352=isNaN(_352)?0:_352;
}
x+=_350.offsetLeft-_350.scrollLeft+_351;
y+=_350.offsetTop-_350.scrollTop+_352;
_350=_350.offsetParent;
}
if(_349){
x-=this.docScrollLeft();
y-=this.docScrollTop();
}
return {x:x,y:y};
},_toAbsoluteMozilla:function(_353,_354){
var x=0;
var y=0;
var _355=_353;
while(_355){
x+=_355.offsetLeft;
y+=_355.offsetTop;
_355=_355.offsetParent;
}
_355=_353;
while(_355&&_355!=document.body&&_355!=document.documentElement){
if(_355.scrollLeft){
x-=_355.scrollLeft;
}
if(_355.scrollTop){
y-=_355.scrollTop;
}
_355=_355.parentNode;
}
if(_354){
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
Prado.RicoLiveGrid.prototype=Object.extend(Rico.LiveGrid.prototype,{initialize:function(_356,_357){
this.options={tableClass:$(_356).className||"",loadingClass:$(_356).className||"",scrollerBorderRight:"1px solid #ababab",bufferTimeout:20000,sortAscendImg:"images/sort_asc.gif",sortDescendImg:"images/sort_desc.gif",sortImageWidth:9,sortImageHeight:5,ajaxSortURLParms:[],onRefreshComplete:null,requestParameters:null,inlineStyles:true,visibleRows:10,totalRows:0,initialOffset:0};
Object.extend(this.options,_357||{});
this.tableId=_356;
this.table=$(_356);
this.addLiveGridHtml();
var _358=this.table.rows[0].cells.length;
this.metaData=new Rico.LiveGridMetaData(this.options.visibleRows,this.options.totalRows,_358,_357);
this.buffer=new Rico.LiveGridBuffer(this.metaData);
var _359=this.table.rows.length;
this.viewPort=new Rico.GridViewPort(this.table,this.table.offsetHeight/_359,this.options.visibleRows,this.buffer,this);
this.scroller=new Rico.LiveGridScroller(this,this.viewPort);
this.options.sortHandler=this.sortHandler.bind(this);
if($(_356+"_header")){
this.sort=new Rico.LiveGridSort(_356+"_header",this.options);
}
this.processingRequest=null;
this.unprocessedRequest=null;
if(this.options.initialOffset>=0){
var _360=this.options.initialOffset;
this.scroller.moveScroll(_360);
this.viewPort.scrollTo(this.scroller.rowToPixel(_360));
if(this.options.sortCol){
this.sortCol=_357.sortCol;
this.sortDir=_357.sortDir;
}
var grid=this;
setTimeout(function(){
grid.requestContentRefresh(_360);
},100);
}
},fetchBuffer:function(_362){
if(this.buffer.isInRange(_362)&&!this.buffer.isNearingLimit(_362)){
return;
}
if(this.processingRequest){
this.unprocessedRequest=new Rico.LiveGridRequest(_362);
return;
}
var _363=this.buffer.getFetchOffset(_362);
this.processingRequest=new Rico.LiveGridRequest(_362);
this.processingRequest.bufferOffset=_363;
var _364=this.buffer.getFetchSize(_362);
var _365=false;
var _366={"page_size":_364,"offset":_363};
if(this.sortCol){
Object.extend(_366,{"sort_col":this.sortCol,"sort_dir":this.sortDir});
}
Prado.Callback(this.tableId,_366,this.ajaxUpdate.bind(this),this.options);
this.timeoutHandler=setTimeout(this.handleTimedOut.bind(this),this.options.bufferTimeout);
},ajaxUpdate:function(_367,_368){
try{
clearTimeout(this.timeoutHandler);
this.buffer.update(_367,this.processingRequest.bufferOffset);
this.viewPort.bufferChanged();
}
catch(err){
}
finally{
this.processingRequest=null;
}
this.processQueuedRequest();
}});
Object.extend(Rico.LiveGridBuffer.prototype,{update:function(_369,_370){
if(this.rows.length==0){
this.rows=_369;
this.size=this.rows.length;
this.startPos=_370;
return;
}
if(_370>this.startPos){
if(this.startPos+this.rows.length<_370){
this.rows=_369;
this.startPos=_370;
}else{
this.rows=this.rows.concat(_369.slice(0,_369.length));
if(this.rows.length>this.maxBufferSize){
var _371=this.rows.length;
this.rows=this.rows.slice(this.rows.length-this.maxBufferSize,this.rows.length);
this.startPos=this.startPos+(_371-this.rows.length);
}
}
}else{
if(_370+_369.length<this.startPos){
this.rows=_369;
}else{
this.rows=_369.slice(0,this.startPos).concat(this.rows);
if(this.rows.length>this.maxBufferSize){
this.rows=this.rows.slice(0,this.maxBufferSize);
}
}
this.startPos=_370;
}
this.size=this.rows.length;
}});
Object.extend(Rico.GridViewPort.prototype,{populateRow:function(_372,row){
if(isdef(_372)){
for(var j=0;j<row.length;j++){
_372.cells[j].innerHTML=row[j];
}
}
}});

