String.prototype.parseColor=function(){
var _1="#";
if(this.slice(0,4)=="rgb("){
var _2=this.slice(4,this.length-1).split(",");
var i=0;
do{
_1+=parseInt(_2[i]).toColorPart();
}while(++i<3);
}else{
if(this.slice(0,1)=="#"){
if(this.length==4){
for(var i=1;i<4;i++){
_1+=(this.charAt(i)+this.charAt(i)).toLowerCase();
}
}
if(this.length==7){
_1=this.toLowerCase();
}
}
}
return (_1.length==7?_1:(arguments[0]||this));
};
Element.collectTextNodesIgnoreClass=function(_4,_5){
var _6=$(_4).childNodes;
var _7="";
var _8=new RegExp("^([^ ]+ )*"+_5+"( [^ ]+)*$","i");
for(var i=0;i<_6.length;i++){
if(_6[i].nodeType==3){
_7+=_6[i].nodeValue;
}else{
if((!_6[i].className.match(_8))&&_6[i].hasChildNodes()){
_7+=Element.collectTextNodesIgnoreClass(_6[i],_5);
}
}
}
return _7;
};
Element.setStyle=function(_9,_10){
_9=$(_9);
for(k in _10){
_9.style[k.camelize()]=_10[k];
}
};
Element.setContentZoom=function(_11,_12){
Element.setStyle(_11,{fontSize:(_12/100)+"em"});
if(navigator.appVersion.indexOf("AppleWebKit")>0){
window.scrollBy(0,0);
}
};
Element.getOpacity=function(_13){
var _14;
if(_14=Element.getStyle(_13,"opacity")){
return parseFloat(_14);
}
if(_14=(Element.getStyle(_13,"filter")||"").match(/alpha\(opacity=(.*)\)/)){
if(_14[1]){
return parseFloat(_14[1])/100;
}
}
return 1;
};
Element.setOpacity=function(_15,_16){
_15=$(_15);
if(_16==1){
Element.setStyle(_15,{opacity:(/Gecko/.test(navigator.userAgent)&&!/Konqueror|Safari|KHTML/.test(navigator.userAgent))?0.999999:null});
if(/MSIE/.test(navigator.userAgent)){
Element.setStyle(_15,{filter:Element.getStyle(_15,"filter").replace(/alpha\([^\)]*\)/gi,"")});
}
}else{
if(_16<0.00001){
_16=0;
}
Element.setStyle(_15,{opacity:_16});
if(/MSIE/.test(navigator.userAgent)){
Element.setStyle(_15,{filter:Element.getStyle(_15,"filter").replace(/alpha\([^\)]*\)/gi,"")+"alpha(opacity="+_16*100+")"});
}
}
};
Element.getInlineOpacity=function(_17){
return $(_17).style.opacity||"";
};
Element.childrenWithClassName=function(_18,_19){
return $A($(_18).getElementsByTagName("*")).select(function(c){
return Element.hasClassName(c,_19);
});
};
Array.prototype.call=function(){
var _21=arguments;
this.each(function(f){
f.apply(this,_21);
});
};
var Effect={tagifyText:function(_23){
var _24="position:relative";
if(/MSIE/.test(navigator.userAgent)){
_24+=";zoom:1";
}
_23=$(_23);
$A(_23.childNodes).each(function(_25){
if(_25.nodeType==3){
_25.nodeValue.toArray().each(function(_26){
_23.insertBefore(Builder.node("span",{style:_24},_26==" "?String.fromCharCode(160):_26),_25);
});
Element.remove(_25);
}
});
},multiple:function(_27,_28){
var _29;
if(((typeof _27=="object")||(typeof _27=="function"))&&(_27.length)){
_29=_27;
}else{
_29=$(_27).childNodes;
}
var _30=Object.extend({speed:0.1,delay:0},arguments[2]||{});
var _31=_30.delay;
$A(_29).each(function(_27,_32){
new _28(_27,Object.extend(_30,{delay:_32*_30.speed+_31}));
});
}};
var Effect2=Effect;
Effect.Transitions={};
Effect.Transitions.linear=function(pos){
return pos;
};
Effect.Transitions.sinoidal=function(pos){
return (-Math.cos(pos*Math.PI)/2)+0.5;
};
Effect.Transitions.reverse=function(pos){
return 1-pos;
};
Effect.Transitions.flicker=function(pos){
return ((-Math.cos(pos*Math.PI)/4)+0.75)+Math.random()/4;
};
Effect.Transitions.wobble=function(pos){
return (-Math.cos(pos*Math.PI*(9*pos))/2)+0.5;
};
Effect.Transitions.pulse=function(pos){
return (Math.floor(pos*10)%2==0?(pos*10-Math.floor(pos*10)):1-(pos*10-Math.floor(pos*10)));
};
Effect.Transitions.none=function(pos){
return 0;
};
Effect.Transitions.full=function(pos){
return 1;
};
Effect.Queue={effects:[],_each:function(_34){
this.effects._each(_34);
},interval:null,add:function(_35){
var _36=new Date().getTime();
switch(_35.options.queue){
case "front":
this.effects.findAll(function(e){
return e.state=="idle";
}).each(function(e){
e.startOn+=_35.finishOn;
e.finishOn+=_35.finishOn;
});
break;
case "end":
_36=this.effects.pluck("finishOn").max()||_36;
break;
}
_35.startOn+=_36;
_35.finishOn+=_36;
this.effects.push(_35);
if(!this.interval){
this.interval=setInterval(this.loop.bind(this),40);
}
},remove:function(_38){
this.effects=this.effects.reject(function(e){
return e==_38;
});
if(this.effects.length==0){
clearInterval(this.interval);
this.interval=null;
}
},loop:function(){
var _39=new Date().getTime();
this.effects.invoke("loop",_39);
}};
Object.extend(Effect.Queue,Enumerable);
Effect.Base=function(){
};
Effect.Base.prototype={position:null,setOptions:function(_40){
this.options=Object.extend({transition:Effect.Transitions.sinoidal,duration:1,fps:25,sync:false,from:0,to:1,delay:0,queue:"parallel"},_40||{});
},start:function(_41){
this.setOptions(_41||{});
this.currentFrame=0;
this.state="idle";
this.startOn=this.options.delay*1000;
this.finishOn=this.startOn+(this.options.duration*1000);
this.event("beforeStart");
if(!this.options.sync){
Effect.Queue.add(this);
}
},loop:function(_42){
if(_42>=this.startOn){
if(_42>=this.finishOn){
this.render(1);
this.cancel();
this.event("beforeFinish");
if(this.finish){
this.finish();
}
this.event("afterFinish");
return;
}
var pos=(_42-this.startOn)/(this.finishOn-this.startOn);
var _43=Math.round(pos*this.options.fps*this.options.duration);
if(_43>this.currentFrame){
this.render(pos);
this.currentFrame=_43;
}
}
},render:function(pos){
if(this.state=="idle"){
this.state="running";
this.event("beforeSetup");
if(this.setup){
this.setup();
}
this.event("afterSetup");
}
if(this.state=="running"){
if(this.options.transition){
pos=this.options.transition(pos);
}
pos*=(this.options.to-this.options.from);
pos+=this.options.from;
this.position=pos;
this.event("beforeUpdate");
if(this.update){
this.update(pos);
}
this.event("afterUpdate");
}
},cancel:function(){
if(!this.options.sync){
Effect.Queue.remove(this);
}
this.state="finished";
},event:function(_44){
if(this.options[_44+"Internal"]){
this.options[_44+"Internal"](this);
}
if(this.options[_44]){
this.options[_44](this);
}
},inspect:function(){
return "#<Effect:"+$H(this).inspect()+",options:"+$H(this.options).inspect()+">";
}};
Effect.Parallel=Class.create();
Object.extend(Object.extend(Effect.Parallel.prototype,Effect.Base.prototype),{initialize:function(_45){
this.effects=_45||[];
this.start(arguments[1]);
},update:function(_46){
this.effects.invoke("render",_46);
},finish:function(_47){
this.effects.each(function(_48){
_48.render(1);
_48.cancel();
_48.event("beforeFinish");
if(_48.finish){
_48.finish(_47);
}
_48.event("afterFinish");
});
}});
Effect.Opacity=Class.create();
Object.extend(Object.extend(Effect.Opacity.prototype,Effect.Base.prototype),{initialize:function(_49){
this.element=$(_49);
if(/MSIE/.test(navigator.userAgent)&&(!this.element.hasLayout)){
Element.setStyle(this.element,{zoom:1});
}
var _50=Object.extend({from:Element.getOpacity(this.element)||0,to:1},arguments[1]||{});
this.start(_50);
},update:function(_51){
Element.setOpacity(this.element,_51);
}});
Effect.MoveBy=Class.create();
Object.extend(Object.extend(Effect.MoveBy.prototype,Effect.Base.prototype),{initialize:function(_52,_53,_54){
this.element=$(_52);
this.toTop=_53;
this.toLeft=_54;
this.start(arguments[3]);
},setup:function(){
Element.makePositioned(this.element);
this.originalTop=parseFloat(Element.getStyle(this.element,"top")||"0");
this.originalLeft=parseFloat(Element.getStyle(this.element,"left")||"0");
},update:function(_55){
Element.setStyle(this.element,{top:this.toTop*_55+this.originalTop+"px",left:this.toLeft*_55+this.originalLeft+"px"});
}});
Effect.Scale=Class.create();
Object.extend(Object.extend(Effect.Scale.prototype,Effect.Base.prototype),{initialize:function(_56,_57){
this.element=$(_56);
var _58=Object.extend({scaleX:true,scaleY:true,scaleContent:true,scaleFromCenter:false,scaleMode:"box",scaleFrom:100,scaleTo:_57},arguments[2]||{});
this.start(_58);
},setup:function(){
this.restoreAfterFinish=this.options.restoreAfterFinish||false;
this.elementPositioning=Element.getStyle(this.element,"position");
this.originalStyle={};
["top","left","width","height","fontSize"].each(function(k){
this.originalStyle[k]=this.element.style[k];
}.bind(this));
this.originalTop=this.element.offsetTop;
this.originalLeft=this.element.offsetLeft;
var _60=Element.getStyle(this.element,"font-size")||"100%";
["em","px","%"].each(function(_61){
if(_60.indexOf(_61)>0){
this.fontSize=parseFloat(_60);
this.fontSizeType=_61;
}
}.bind(this));
this.factor=(this.options.scaleTo-this.options.scaleFrom)/100;
this.dims=null;
if(this.options.scaleMode=="box"){
this.dims=[this.element.offsetHeight,this.element.offsetWidth];
}
if(/^content/.test(this.options.scaleMode)){
this.dims=[this.element.scrollHeight,this.element.scrollWidth];
}
if(!this.dims){
this.dims=[this.options.scaleMode.originalHeight,this.options.scaleMode.originalWidth];
}
},update:function(_62){
var _63=(this.options.scaleFrom/100)+(this.factor*_62);
if(this.options.scaleContent&&this.fontSize){
Element.setStyle(this.element,{fontSize:this.fontSize*_63+this.fontSizeType});
}
this.setDimensions(this.dims[0]*_63,this.dims[1]*_63);
},finish:function(_64){
if(this.restoreAfterFinish){
Element.setStyle(this.element,this.originalStyle);
}
},setDimensions:function(_65,_66){
var d={};
if(this.options.scaleX){
d.width=_66+"px";
}
if(this.options.scaleY){
d.height=_65+"px";
}
if(this.options.scaleFromCenter){
var _68=(_65-this.dims[0])/2;
var _69=(_66-this.dims[1])/2;
if(this.elementPositioning=="absolute"){
if(this.options.scaleY){
d.top=this.originalTop-_68+"px";
}
if(this.options.scaleX){
d.left=this.originalLeft-_69+"px";
}
}else{
if(this.options.scaleY){
d.top=-_68+"px";
}
if(this.options.scaleX){
d.left=-_69+"px";
}
}
}
Element.setStyle(this.element,d);
}});
Effect.Highlight=Class.create();
Object.extend(Object.extend(Effect.Highlight.prototype,Effect.Base.prototype),{initialize:function(_70){
this.element=$(_70);
var _71=Object.extend({startcolor:"#ffff99"},arguments[1]||{});
this.start(_71);
},setup:function(){
if(Element.getStyle(this.element,"display")=="none"){
this.cancel();
return;
}
this.oldStyle={backgroundImage:Element.getStyle(this.element,"background-image")};
Element.setStyle(this.element,{backgroundImage:"none"});
if(!this.options.endcolor){
this.options.endcolor=Element.getStyle(this.element,"background-color").parseColor("#ffffff");
}
if(!this.options.restorecolor){
this.options.restorecolor=Element.getStyle(this.element,"background-color");
}
this._base=$R(0,2).map(function(i){
return parseInt(this.options.startcolor.slice(i*2+1,i*2+3),16);
}.bind(this));
this._delta=$R(0,2).map(function(i){
return parseInt(this.options.endcolor.slice(i*2+1,i*2+3),16)-this._base[i];
}.bind(this));
},update:function(_72){
Element.setStyle(this.element,{backgroundColor:$R(0,2).inject("#",function(m,v,i){
return m+(Math.round(this._base[i]+(this._delta[i]*_72)).toColorPart());
}.bind(this))});
},finish:function(){
Element.setStyle(this.element,Object.extend(this.oldStyle,{backgroundColor:this.options.restorecolor}));
}});
Effect.ScrollTo=Class.create();
Object.extend(Object.extend(Effect.ScrollTo.prototype,Effect.Base.prototype),{initialize:function(_75){
this.element=$(_75);
this.start(arguments[1]||{});
},setup:function(){
Position.prepare();
var _76=Position.cumulativeOffset(this.element);
if(this.options.offset){
_76[1]+=this.options.offset;
}
var max=window.innerHeight?window.height-window.innerHeight:document.body.scrollHeight-(document.documentElement.clientHeight?document.documentElement.clientHeight:document.body.clientHeight);
this.scrollStart=Position.deltaY;
this.delta=(_76[1]>max?max:_76[1])-this.scrollStart;
},update:function(_78){
Position.prepare();
window.scrollTo(Position.deltaX,this.scrollStart+(_78*this.delta));
}});
Effect.Fade=function(_79){
var _80=Element.getInlineOpacity(_79);
var _81=Object.extend({from:Element.getOpacity(_79)||1,to:0,afterFinishInternal:function(_82){
with(Element){
if(_82.options.to!=0){
return;
}
hide(_82.element);
setStyle(_82.element,{opacity:_80});
}
}},arguments[1]||{});
return new Effect.Opacity(_79,_81);
};
Effect.Appear=function(_83){
var _84=Object.extend({from:(Element.getStyle(_83,"display")=="none"?0:Element.getOpacity(_83)||0),to:1,beforeSetup:function(_85){
with(Element){
setOpacity(_85.element,_85.options.from);
show(_85.element);
}
}},arguments[1]||{});
return new Effect.Opacity(_83,_84);
};
Effect.Puff=function(_86){
_86=$(_86);
var _87={opacity:Element.getInlineOpacity(_86),position:Element.getStyle(_86,"position")};
return new Effect.Parallel([new Effect.Scale(_86,200,{sync:true,scaleFromCenter:true,scaleContent:true,restoreAfterFinish:true}),new Effect.Opacity(_86,{sync:true,to:0})],Object.extend({duration:1,beforeSetupInternal:function(_88){
with(Element){
setStyle(_88.effects[0].element,{position:"absolute"});
}
},afterFinishInternal:function(_89){
with(Element){
hide(_89.effects[0].element);
setStyle(_89.effects[0].element,_87);
}
}},arguments[1]||{}));
};
Effect.BlindUp=function(_90){
_90=$(_90);
Element.makeClipping(_90);
return new Effect.Scale(_90,0,Object.extend({scaleContent:false,scaleX:false,restoreAfterFinish:true,afterFinishInternal:function(_91){
with(Element){
[hide,undoClipping].call(_91.element);
}
}},arguments[1]||{}));
};
Effect.BlindDown=function(_92){
_92=$(_92);
var _93=Element.getStyle(_92,"height");
var _94=Element.getDimensions(_92);
return new Effect.Scale(_92,100,Object.extend({scaleContent:false,scaleX:false,scaleFrom:0,scaleMode:{originalHeight:_94.height,originalWidth:_94.width},restoreAfterFinish:true,afterSetup:function(_95){
with(Element){
makeClipping(_95.element);
setStyle(_95.element,{height:"0px"});
show(_95.element);
}
},afterFinishInternal:function(_96){
with(Element){
undoClipping(_96.element);
setStyle(_96.element,{height:_93});
}
}},arguments[1]||{}));
};
Effect.SwitchOff=function(_97){
_97=$(_97);
var _98=Element.getInlineOpacity(_97);
return new Effect.Appear(_97,{duration:0.4,from:0,transition:Effect.Transitions.flicker,afterFinishInternal:function(_99){
new Effect.Scale(_99.element,1,{duration:0.3,scaleFromCenter:true,scaleX:false,scaleContent:false,restoreAfterFinish:true,beforeSetup:function(_99){
with(Element){
[makePositioned,makeClipping].call(_99.element);
}
},afterFinishInternal:function(_100){
with(Element){
[hide,undoClipping,undoPositioned].call(_100.element);
setStyle(_100.element,{opacity:_98});
}
}});
}});
};
Effect.DropOut=function(_101){
_101=$(_101);
var _102={top:Element.getStyle(_101,"top"),left:Element.getStyle(_101,"left"),opacity:Element.getInlineOpacity(_101)};
return new Effect.Parallel([new Effect.MoveBy(_101,100,0,{sync:true}),new Effect.Opacity(_101,{sync:true,to:0})],Object.extend({duration:0.5,beforeSetup:function(_103){
with(Element){
makePositioned(_103.effects[0].element);
}
},afterFinishInternal:function(_104){
with(Element){
[hide,undoPositioned].call(_104.effects[0].element);
setStyle(_104.effects[0].element,_102);
}
}},arguments[1]||{}));
};
Effect.Shake=function(_105){
_105=$(_105);
var _106={top:Element.getStyle(_105,"top"),left:Element.getStyle(_105,"left")};
return new Effect.MoveBy(_105,0,20,{duration:0.05,afterFinishInternal:function(_107){
new Effect.MoveBy(_107.element,0,-40,{duration:0.1,afterFinishInternal:function(_107){
new Effect.MoveBy(_107.element,0,40,{duration:0.1,afterFinishInternal:function(_107){
new Effect.MoveBy(_107.element,0,-40,{duration:0.1,afterFinishInternal:function(_107){
new Effect.MoveBy(_107.element,0,40,{duration:0.1,afterFinishInternal:function(_107){
new Effect.MoveBy(_107.element,0,-20,{duration:0.05,afterFinishInternal:function(_107){
with(Element){
undoPositioned(_107.element);
setStyle(_107.element,_106);
}
}});
}});
}});
}});
}});
}});
};
Effect.SlideDown=function(_108){
_108=$(_108);
Element.cleanWhitespace(_108);
var _109=Element.getStyle(_108.firstChild,"bottom");
var _110=Element.getDimensions(_108);
return new Effect.Scale(_108,100,Object.extend({scaleContent:false,scaleX:false,scaleFrom:0,scaleMode:{originalHeight:_110.height,originalWidth:_110.width},restoreAfterFinish:true,afterSetup:function(_111){
with(Element){
makePositioned(_111.element);
makePositioned(_111.element.firstChild);
if(window.opera){
setStyle(_111.element,{top:""});
}
makeClipping(_111.element);
setStyle(_111.element,{height:"0px"});
show(_108);
}
},afterUpdateInternal:function(_112){
with(Element){
setStyle(_112.element.firstChild,{bottom:(_112.dims[0]-_112.element.clientHeight)+"px"});
}
},afterFinishInternal:function(_113){
with(Element){
undoClipping(_113.element);
undoPositioned(_113.element.firstChild);
undoPositioned(_113.element);
setStyle(_113.element.firstChild,{bottom:_109});
}
}},arguments[1]||{}));
};
Effect.SlideUp=function(_114){
_114=$(_114);
Element.cleanWhitespace(_114);
var _115=Element.getStyle(_114.firstChild,"bottom");
return new Effect.Scale(_114,0,Object.extend({scaleContent:false,scaleX:false,scaleMode:"box",scaleFrom:100,restoreAfterFinish:true,beforeStartInternal:function(_116){
with(Element){
makePositioned(_116.element);
makePositioned(_116.element.firstChild);
if(window.opera){
setStyle(_116.element,{top:""});
}
makeClipping(_116.element);
show(_114);
}
},afterUpdateInternal:function(_117){
with(Element){
setStyle(_117.element.firstChild,{bottom:(_117.dims[0]-_117.element.clientHeight)+"px"});
}
},afterFinishInternal:function(_118){
with(Element){
[hide,undoClipping].call(_118.element);
undoPositioned(_118.element.firstChild);
undoPositioned(_118.element);
setStyle(_118.element.firstChild,{bottom:_115});
}
}},arguments[1]||{}));
};
Effect.Squish=function(_119){
return new Effect.Scale(_119,window.opera?1:0,{restoreAfterFinish:true,beforeSetup:function(_120){
with(Element){
makeClipping(_120.element);
}
},afterFinishInternal:function(_121){
with(Element){
hide(_121.element);
undoClipping(_121.element);
}
}});
};
Effect.Grow=function(_122){
_122=$(_122);
var _123=Object.extend({direction:"center",moveTransistion:Effect.Transitions.sinoidal,scaleTransition:Effect.Transitions.sinoidal,opacityTransition:Effect.Transitions.full},arguments[1]||{});
var _124={top:_122.style.top,left:_122.style.left,height:_122.style.height,width:_122.style.width,opacity:Element.getInlineOpacity(_122)};
var dims=Element.getDimensions(_122);
var _126,initialMoveY;
var _127,moveY;
switch(_123.direction){
case "top-left":
_126=initialMoveY=_127=moveY=0;
break;
case "top-right":
_126=dims.width;
initialMoveY=moveY=0;
_127=-dims.width;
break;
case "bottom-left":
_126=_127=0;
initialMoveY=dims.height;
moveY=-dims.height;
break;
case "bottom-right":
_126=dims.width;
initialMoveY=dims.height;
_127=-dims.width;
moveY=-dims.height;
break;
case "center":
_126=dims.width/2;
initialMoveY=dims.height/2;
_127=-dims.width/2;
moveY=-dims.height/2;
break;
}
return new Effect.MoveBy(_122,initialMoveY,_126,{duration:0.01,beforeSetup:function(_128){
with(Element){
hide(_128.element);
makeClipping(_128.element);
makePositioned(_128.element);
}
},afterFinishInternal:function(_129){
new Effect.Parallel([new Effect.Opacity(_129.element,{sync:true,to:1,from:0,transition:_123.opacityTransition}),new Effect.MoveBy(_129.element,moveY,_127,{sync:true,transition:_123.moveTransition}),new Effect.Scale(_129.element,100,{scaleMode:{originalHeight:dims.height,originalWidth:dims.width},sync:true,scaleFrom:window.opera?1:0,transition:_123.scaleTransition,restoreAfterFinish:true})],Object.extend({beforeSetup:function(_129){
with(Element){
setStyle(_129.effects[0].element,{height:"0px"});
show(_129.effects[0].element);
}
},afterFinishInternal:function(_130){
with(Element){
[undoClipping,undoPositioned].call(_130.effects[0].element);
setStyle(_130.effects[0].element,_124);
}
}},_123));
}});
};
Effect.Shrink=function(_131){
_131=$(_131);
var _132=Object.extend({direction:"center",moveTransistion:Effect.Transitions.sinoidal,scaleTransition:Effect.Transitions.sinoidal,opacityTransition:Effect.Transitions.none},arguments[1]||{});
var _133={top:_131.style.top,left:_131.style.left,height:_131.style.height,width:_131.style.width,opacity:Element.getInlineOpacity(_131)};
var dims=Element.getDimensions(_131);
var _134,moveY;
switch(_132.direction){
case "top-left":
_134=moveY=0;
break;
case "top-right":
_134=dims.width;
moveY=0;
break;
case "bottom-left":
_134=0;
moveY=dims.height;
break;
case "bottom-right":
_134=dims.width;
moveY=dims.height;
break;
case "center":
_134=dims.width/2;
moveY=dims.height/2;
break;
}
return new Effect.Parallel([new Effect.Opacity(_131,{sync:true,to:0,from:1,transition:_132.opacityTransition}),new Effect.Scale(_131,window.opera?1:0,{sync:true,transition:_132.scaleTransition,restoreAfterFinish:true}),new Effect.MoveBy(_131,moveY,_134,{sync:true,transition:_132.moveTransition})],Object.extend({beforeStartInternal:function(_135){
with(Element){
[makePositioned,makeClipping].call(_135.effects[0].element);
}
},afterFinishInternal:function(_136){
with(Element){
[hide,undoClipping,undoPositioned].call(_136.effects[0].element);
setStyle(_136.effects[0].element,_133);
}
}},_132));
};
Effect.Pulsate=function(_137){
_137=$(_137);
var _138=arguments[1]||{};
var _139=Element.getInlineOpacity(_137);
var _140=_138.transition||Effect.Transitions.sinoidal;
var _141=function(pos){
return _140(1-Effect.Transitions.pulse(pos));
};
_141.bind(_140);
return new Effect.Opacity(_137,Object.extend(Object.extend({duration:3,from:0,afterFinishInternal:function(_142){
Element.setStyle(_142.element,{opacity:_139});
}},_138),{transition:_141}));
};
Effect.Fold=function(_143){
_143=$(_143);
var _144={top:_143.style.top,left:_143.style.left,width:_143.style.width,height:_143.style.height};
Element.makeClipping(_143);
return new Effect.Scale(_143,5,Object.extend({scaleContent:false,scaleX:false,afterFinishInternal:function(_145){
new Effect.Scale(_143,1,{scaleContent:false,scaleY:false,afterFinishInternal:function(_145){
with(Element){
[hide,undoClipping].call(_145.element);
setStyle(_145.element,_144);
}
}});
}},arguments[1]||{}));
};

Prado.Effect={Highlight:function(_1,_2){
new Effect.Highlight(_1,{"duration":_2});
},Scale:function(_3,_4){
new Effect.Scale(_3,_4);
},MoveBy:function(_5,_6,_7){
new Effect.MoveBy(_5,_6,_7);
},ScrollTo:function(_8,_9){
new Effect.ScrollTo(_8,{"duration":_9});
}};

