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
Element.collectTextNodes=function(_4){
return $A($(_4).childNodes).collect(function(_5){
return (_5.nodeType==3?_5.nodeValue:(_5.hasChildNodes()?Element.collectTextNodes(_5):""));
}).flatten().join("");
};
Element.collectTextNodesIgnoreClass=function(_6,_7){
return $A($(_6).childNodes).collect(function(_8){
return (_8.nodeType==3?_8.nodeValue:((_8.hasChildNodes()&&!Element.hasClassName(_8,_7))?Element.collectTextNodes(_8):""));
}).flatten().join("");
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
},PAIRS:{"slide":["SlideDown","SlideUp"],"blind":["BlindDown","BlindUp"],"appear":["Appear","Fade"]},toggle:function(_33,_34){
_33=$(_33);
_34=(_34||"appear").toLowerCase();
var _35=Object.extend({queue:{position:"end",scope:(_33.id||"global")}},arguments[2]||{});
Effect[Element.visible(_33)?Effect.PAIRS[_34][1]:Effect.PAIRS[_34][0]](_33,_35);
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
Effect.ScopedQueue=Class.create();
Object.extend(Object.extend(Effect.ScopedQueue.prototype,Enumerable),{initialize:function(){
this.effects=[];
this.interval=null;
},_each:function(_37){
this.effects._each(_37);
},add:function(_38){
var _39=new Date().getTime();
var _40=(typeof _38.options.queue=="string")?_38.options.queue:_38.options.queue.position;
switch(_40){
case "front":
this.effects.findAll(function(e){
return e.state=="idle";
}).each(function(e){
e.startOn+=_38.finishOn;
e.finishOn+=_38.finishOn;
});
break;
case "end":
_39=this.effects.pluck("finishOn").max()||_39;
break;
}
_38.startOn+=_39;
_38.finishOn+=_39;
this.effects.push(_38);
if(!this.interval){
this.interval=setInterval(this.loop.bind(this),40);
}
},remove:function(_42){
this.effects=this.effects.reject(function(e){
return e==_42;
});
if(this.effects.length==0){
clearInterval(this.interval);
this.interval=null;
}
},loop:function(){
var _43=new Date().getTime();
this.effects.invoke("loop",_43);
}});
Effect.Queues={instances:$H(),get:function(_44){
if(typeof _44!="string"){
return _44;
}
if(!this.instances[_44]){
this.instances[_44]=new Effect.ScopedQueue();
}
return this.instances[_44];
}};
Effect.Queue=Effect.Queues.get("global");
Effect.DefaultOptions={transition:Effect.Transitions.sinoidal,duration:1,fps:25,sync:false,from:0,to:1,delay:0,queue:"parallel"};
Effect.Base=function(){
};
Effect.Base.prototype={position:null,start:function(_45){
this.options=Object.extend(Object.extend({},Effect.DefaultOptions),_45||{});
this.currentFrame=0;
this.state="idle";
this.startOn=this.options.delay*1000;
this.finishOn=this.startOn+(this.options.duration*1000);
this.event("beforeStart");
if(!this.options.sync){
Effect.Queues.get(typeof this.options.queue=="string"?"global":this.options.queue.scope).add(this);
}
},loop:function(_46){
if(_46>=this.startOn){
if(_46>=this.finishOn){
this.render(1);
this.cancel();
this.event("beforeFinish");
if(this.finish){
this.finish();
}
this.event("afterFinish");
return;
}
var pos=(_46-this.startOn)/(this.finishOn-this.startOn);
var _47=Math.round(pos*this.options.fps*this.options.duration);
if(_47>this.currentFrame){
this.render(pos);
this.currentFrame=_47;
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
Effect.Queues.get(typeof this.options.queue=="string"?"global":this.options.queue.scope).remove(this);
}
this.state="finished";
},event:function(_48){
if(this.options[_48+"Internal"]){
this.options[_48+"Internal"](this);
}
if(this.options[_48]){
this.options[_48](this);
}
},inspect:function(){
return "#<Effect:"+$H(this).inspect()+",options:"+$H(this.options).inspect()+">";
}};
Effect.Parallel=Class.create();
Object.extend(Object.extend(Effect.Parallel.prototype,Effect.Base.prototype),{initialize:function(_49){
this.effects=_49||[];
this.start(arguments[1]);
},update:function(_50){
this.effects.invoke("render",_50);
},finish:function(_51){
this.effects.each(function(_52){
_52.render(1);
_52.cancel();
_52.event("beforeFinish");
if(_52.finish){
_52.finish(_51);
}
_52.event("afterFinish");
});
}});
Effect.Opacity=Class.create();
Object.extend(Object.extend(Effect.Opacity.prototype,Effect.Base.prototype),{initialize:function(_53){
this.element=$(_53);
if(/MSIE/.test(navigator.userAgent)&&(!this.element.hasLayout)){
Element.setStyle(this.element,{zoom:1});
}
var _54=Object.extend({from:Element.getOpacity(this.element)||0,to:1},arguments[1]||{});
this.start(_54);
},update:function(_55){
Element.setOpacity(this.element,_55);
}});
Effect.Move=Class.create();
Object.extend(Object.extend(Effect.Move.prototype,Effect.Base.prototype),{initialize:function(_56){
this.element=$(_56);
var _57=Object.extend({x:0,y:0,mode:"relative"},arguments[1]||{});
this.start(_57);
},setup:function(){
Element.makePositioned(this.element);
this.originalLeft=parseFloat(Element.getStyle(this.element,"left")||"0");
this.originalTop=parseFloat(Element.getStyle(this.element,"top")||"0");
if(this.options.mode=="absolute"){
this.options.x=this.options.x-this.originalLeft;
this.options.y=this.options.y-this.originalTop;
}
},update:function(_58){
Element.setStyle(this.element,{left:this.options.x*_58+this.originalLeft+"px",top:this.options.y*_58+this.originalTop+"px"});
}});
Effect.MoveBy=function(_59,_60,_61){
return new Effect.Move(_59,Object.extend({x:_61,y:_60},arguments[3]||{}));
};
Effect.Scale=Class.create();
Object.extend(Object.extend(Effect.Scale.prototype,Effect.Base.prototype),{initialize:function(_62,_63){
this.element=$(_62);
var _64=Object.extend({scaleX:true,scaleY:true,scaleContent:true,scaleFromCenter:false,scaleMode:"box",scaleFrom:100,scaleTo:_63},arguments[2]||{});
this.start(_64);
},setup:function(){
this.restoreAfterFinish=this.options.restoreAfterFinish||false;
this.elementPositioning=Element.getStyle(this.element,"position");
this.originalStyle={};
["top","left","width","height","fontSize"].each(function(k){
this.originalStyle[k]=this.element.style[k];
}.bind(this));
this.originalTop=this.element.offsetTop;
this.originalLeft=this.element.offsetLeft;
var _66=Element.getStyle(this.element,"font-size")||"100%";
["em","px","%"].each(function(_67){
if(_66.indexOf(_67)>0){
this.fontSize=parseFloat(_66);
this.fontSizeType=_67;
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
},update:function(_68){
var _69=(this.options.scaleFrom/100)+(this.factor*_68);
if(this.options.scaleContent&&this.fontSize){
Element.setStyle(this.element,{fontSize:this.fontSize*_69+this.fontSizeType});
}
this.setDimensions(this.dims[0]*_69,this.dims[1]*_69);
},finish:function(_70){
if(this.restoreAfterFinish){
Element.setStyle(this.element,this.originalStyle);
}
},setDimensions:function(_71,_72){
var d={};
if(this.options.scaleX){
d.width=_72+"px";
}
if(this.options.scaleY){
d.height=_71+"px";
}
if(this.options.scaleFromCenter){
var _74=(_71-this.dims[0])/2;
var _75=(_72-this.dims[1])/2;
if(this.elementPositioning=="absolute"){
if(this.options.scaleY){
d.top=this.originalTop-_74+"px";
}
if(this.options.scaleX){
d.left=this.originalLeft-_75+"px";
}
}else{
if(this.options.scaleY){
d.top=-_74+"px";
}
if(this.options.scaleX){
d.left=-_75+"px";
}
}
}
Element.setStyle(this.element,d);
}});
Effect.Highlight=Class.create();
Object.extend(Object.extend(Effect.Highlight.prototype,Effect.Base.prototype),{initialize:function(_76){
this.element=$(_76);
var _77=Object.extend({startcolor:"#ffff99"},arguments[1]||{});
this.start(_77);
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
},update:function(_78){
Element.setStyle(this.element,{backgroundColor:$R(0,2).inject("#",function(m,v,i){
return m+(Math.round(this._base[i]+(this._delta[i]*_78)).toColorPart());
}.bind(this))});
},finish:function(){
Element.setStyle(this.element,Object.extend(this.oldStyle,{backgroundColor:this.options.restorecolor}));
}});
Effect.ScrollTo=Class.create();
Object.extend(Object.extend(Effect.ScrollTo.prototype,Effect.Base.prototype),{initialize:function(_81){
this.element=$(_81);
this.start(arguments[1]||{});
},setup:function(){
Position.prepare();
var _82=Position.cumulativeOffset(this.element);
if(this.options.offset){
_82[1]+=this.options.offset;
}
var max=window.innerHeight?window.height-window.innerHeight:document.body.scrollHeight-(document.documentElement.clientHeight?document.documentElement.clientHeight:document.body.clientHeight);
this.scrollStart=Position.deltaY;
this.delta=(_82[1]>max?max:_82[1])-this.scrollStart;
},update:function(_84){
Position.prepare();
window.scrollTo(Position.deltaX,this.scrollStart+(_84*this.delta));
}});
Effect.Fade=function(_85){
var _86=Element.getInlineOpacity(_85);
var _87=Object.extend({from:Element.getOpacity(_85)||1,to:0,afterFinishInternal:function(_88){
with(Element){
if(_88.options.to!=0){
return;
}
hide(_88.element);
setStyle(_88.element,{opacity:_86});
}
}},arguments[1]||{});
return new Effect.Opacity(_85,_87);
};
Effect.Appear=function(_89){
var _90=Object.extend({from:(Element.getStyle(_89,"display")=="none"?0:Element.getOpacity(_89)||0),to:1,beforeSetup:function(_91){
with(Element){
setOpacity(_91.element,_91.options.from);
show(_91.element);
}
}},arguments[1]||{});
return new Effect.Opacity(_89,_90);
};
Effect.Puff=function(_92){
_92=$(_92);
var _93={opacity:Element.getInlineOpacity(_92),position:Element.getStyle(_92,"position")};
return new Effect.Parallel([new Effect.Scale(_92,200,{sync:true,scaleFromCenter:true,scaleContent:true,restoreAfterFinish:true}),new Effect.Opacity(_92,{sync:true,to:0})],Object.extend({duration:1,beforeSetupInternal:function(_94){
with(Element){
setStyle(_94.effects[0].element,{position:"absolute"});
}
},afterFinishInternal:function(_95){
with(Element){
hide(_95.effects[0].element);
setStyle(_95.effects[0].element,_93);
}
}},arguments[1]||{}));
};
Effect.BlindUp=function(_96){
_96=$(_96);
Element.makeClipping(_96);
return new Effect.Scale(_96,0,Object.extend({scaleContent:false,scaleX:false,restoreAfterFinish:true,afterFinishInternal:function(_97){
with(Element){
[hide,undoClipping].call(_97.element);
}
}},arguments[1]||{}));
};
Effect.BlindDown=function(_98){
_98=$(_98);
var _99=Element.getStyle(_98,"height");
var _100=Element.getDimensions(_98);
return new Effect.Scale(_98,100,Object.extend({scaleContent:false,scaleX:false,scaleFrom:0,scaleMode:{originalHeight:_100.height,originalWidth:_100.width},restoreAfterFinish:true,afterSetup:function(_101){
with(Element){
makeClipping(_101.element);
setStyle(_101.element,{height:"0px"});
show(_101.element);
}
},afterFinishInternal:function(_102){
with(Element){
undoClipping(_102.element);
setStyle(_102.element,{height:_99});
}
}},arguments[1]||{}));
};
Effect.SwitchOff=function(_103){
_103=$(_103);
var _104=Element.getInlineOpacity(_103);
return new Effect.Appear(_103,{duration:0.4,from:0,transition:Effect.Transitions.flicker,afterFinishInternal:function(_105){
new Effect.Scale(_105.element,1,{duration:0.3,scaleFromCenter:true,scaleX:false,scaleContent:false,restoreAfterFinish:true,beforeSetup:function(_105){
with(Element){
[makePositioned,makeClipping].call(_105.element);
}
},afterFinishInternal:function(_106){
with(Element){
[hide,undoClipping,undoPositioned].call(_106.element);
setStyle(_106.element,{opacity:_104});
}
}});
}});
};
Effect.DropOut=function(_107){
_107=$(_107);
var _108={top:Element.getStyle(_107,"top"),left:Element.getStyle(_107,"left"),opacity:Element.getInlineOpacity(_107)};
return new Effect.Parallel([new Effect.Move(_107,{x:0,y:100,sync:true}),new Effect.Opacity(_107,{sync:true,to:0})],Object.extend({duration:0.5,beforeSetup:function(_109){
with(Element){
makePositioned(_109.effects[0].element);
}
},afterFinishInternal:function(_110){
with(Element){
[hide,undoPositioned].call(_110.effects[0].element);
setStyle(_110.effects[0].element,_108);
}
}},arguments[1]||{}));
};
Effect.Shake=function(_111){
_111=$(_111);
var _112={top:Element.getStyle(_111,"top"),left:Element.getStyle(_111,"left")};
return new Effect.Move(_111,{x:20,y:0,duration:0.05,afterFinishInternal:function(_113){
new Effect.Move(_113.element,{x:-40,y:0,duration:0.1,afterFinishInternal:function(_113){
new Effect.Move(_113.element,{x:40,y:0,duration:0.1,afterFinishInternal:function(_113){
new Effect.Move(_113.element,{x:-40,y:0,duration:0.1,afterFinishInternal:function(_113){
new Effect.Move(_113.element,{x:40,y:0,duration:0.1,afterFinishInternal:function(_113){
new Effect.Move(_113.element,{x:-20,y:0,duration:0.05,afterFinishInternal:function(_113){
with(Element){
undoPositioned(_113.element);
setStyle(_113.element,_112);
}
}});
}});
}});
}});
}});
}});
};
Effect.SlideDown=function(_114){
_114=$(_114);
Element.cleanWhitespace(_114);
var _115=Element.getStyle(_114.firstChild,"bottom");
var _116=Element.getDimensions(_114);
return new Effect.Scale(_114,100,Object.extend({scaleContent:false,scaleX:false,scaleFrom:0,scaleMode:{originalHeight:_116.height,originalWidth:_116.width},restoreAfterFinish:true,afterSetup:function(_117){
with(Element){
makePositioned(_117.element);
makePositioned(_117.element.firstChild);
if(window.opera){
setStyle(_117.element,{top:""});
}
makeClipping(_117.element);
setStyle(_117.element,{height:"0px"});
show(_114);
}
},afterUpdateInternal:function(_118){
with(Element){
setStyle(_118.element.firstChild,{bottom:(_118.dims[0]-_118.element.clientHeight)+"px"});
}
},afterFinishInternal:function(_119){
with(Element){
undoClipping(_119.element);
undoPositioned(_119.element.firstChild);
undoPositioned(_119.element);
setStyle(_119.element.firstChild,{bottom:_115});
}
}},arguments[1]||{}));
};
Effect.SlideUp=function(_120){
_120=$(_120);
Element.cleanWhitespace(_120);
var _121=Element.getStyle(_120.firstChild,"bottom");
return new Effect.Scale(_120,0,Object.extend({scaleContent:false,scaleX:false,scaleMode:"box",scaleFrom:100,restoreAfterFinish:true,beforeStartInternal:function(_122){
with(Element){
makePositioned(_122.element);
makePositioned(_122.element.firstChild);
if(window.opera){
setStyle(_122.element,{top:""});
}
makeClipping(_122.element);
show(_120);
}
},afterUpdateInternal:function(_123){
with(Element){
setStyle(_123.element.firstChild,{bottom:(_123.dims[0]-_123.element.clientHeight)+"px"});
}
},afterFinishInternal:function(_124){
with(Element){
[hide,undoClipping].call(_124.element);
undoPositioned(_124.element.firstChild);
undoPositioned(_124.element);
setStyle(_124.element.firstChild,{bottom:_121});
}
}},arguments[1]||{}));
};
Effect.Squish=function(_125){
return new Effect.Scale(_125,window.opera?1:0,{restoreAfterFinish:true,beforeSetup:function(_126){
with(Element){
makeClipping(_126.element);
}
},afterFinishInternal:function(_127){
with(Element){
hide(_127.element);
undoClipping(_127.element);
}
}});
};
Effect.Grow=function(_128){
_128=$(_128);
var _129=Object.extend({direction:"center",moveTransistion:Effect.Transitions.sinoidal,scaleTransition:Effect.Transitions.sinoidal,opacityTransition:Effect.Transitions.full},arguments[1]||{});
var _130={top:_128.style.top,left:_128.style.left,height:_128.style.height,width:_128.style.width,opacity:Element.getInlineOpacity(_128)};
var dims=Element.getDimensions(_128);
var _132,initialMoveY;
var _133,moveY;
switch(_129.direction){
case "top-left":
_132=initialMoveY=_133=moveY=0;
break;
case "top-right":
_132=dims.width;
initialMoveY=moveY=0;
_133=-dims.width;
break;
case "bottom-left":
_132=_133=0;
initialMoveY=dims.height;
moveY=-dims.height;
break;
case "bottom-right":
_132=dims.width;
initialMoveY=dims.height;
_133=-dims.width;
moveY=-dims.height;
break;
case "center":
_132=dims.width/2;
initialMoveY=dims.height/2;
_133=-dims.width/2;
moveY=-dims.height/2;
break;
}
return new Effect.Move(_128,{x:_132,y:initialMoveY,duration:0.01,beforeSetup:function(_134){
with(Element){
hide(_134.element);
makeClipping(_134.element);
makePositioned(_134.element);
}
},afterFinishInternal:function(_135){
new Effect.Parallel([new Effect.Opacity(_135.element,{sync:true,to:1,from:0,transition:_129.opacityTransition}),new Effect.Move(_135.element,{x:_133,y:moveY,sync:true,transition:_129.moveTransition}),new Effect.Scale(_135.element,100,{scaleMode:{originalHeight:dims.height,originalWidth:dims.width},sync:true,scaleFrom:window.opera?1:0,transition:_129.scaleTransition,restoreAfterFinish:true})],Object.extend({beforeSetup:function(_135){
with(Element){
setStyle(_135.effects[0].element,{height:"0px"});
show(_135.effects[0].element);
}
},afterFinishInternal:function(_136){
with(Element){
[undoClipping,undoPositioned].call(_136.effects[0].element);
setStyle(_136.effects[0].element,_130);
}
}},_129));
}});
};
Effect.Shrink=function(_137){
_137=$(_137);
var _138=Object.extend({direction:"center",moveTransistion:Effect.Transitions.sinoidal,scaleTransition:Effect.Transitions.sinoidal,opacityTransition:Effect.Transitions.none},arguments[1]||{});
var _139={top:_137.style.top,left:_137.style.left,height:_137.style.height,width:_137.style.width,opacity:Element.getInlineOpacity(_137)};
var dims=Element.getDimensions(_137);
var _140,moveY;
switch(_138.direction){
case "top-left":
_140=moveY=0;
break;
case "top-right":
_140=dims.width;
moveY=0;
break;
case "bottom-left":
_140=0;
moveY=dims.height;
break;
case "bottom-right":
_140=dims.width;
moveY=dims.height;
break;
case "center":
_140=dims.width/2;
moveY=dims.height/2;
break;
}
return new Effect.Parallel([new Effect.Opacity(_137,{sync:true,to:0,from:1,transition:_138.opacityTransition}),new Effect.Scale(_137,window.opera?1:0,{sync:true,transition:_138.scaleTransition,restoreAfterFinish:true}),new Effect.Move(_137,{x:_140,y:moveY,sync:true,transition:_138.moveTransition})],Object.extend({beforeStartInternal:function(_141){
with(Element){
[makePositioned,makeClipping].call(_141.effects[0].element);
}
},afterFinishInternal:function(_142){
with(Element){
[hide,undoClipping,undoPositioned].call(_142.effects[0].element);
setStyle(_142.effects[0].element,_139);
}
}},_138));
};
Effect.Pulsate=function(_143){
_143=$(_143);
var _144=arguments[1]||{};
var _145=Element.getInlineOpacity(_143);
var _146=_144.transition||Effect.Transitions.sinoidal;
var _147=function(pos){
return _146(1-Effect.Transitions.pulse(pos));
};
_147.bind(_146);
return new Effect.Opacity(_143,Object.extend(Object.extend({duration:3,from:0,afterFinishInternal:function(_148){
Element.setStyle(_148.element,{opacity:_145});
}},_144),{transition:_147}));
};
Effect.Fold=function(_149){
_149=$(_149);
var _150={top:_149.style.top,left:_149.style.left,width:_149.style.width,height:_149.style.height};
Element.makeClipping(_149);
return new Effect.Scale(_149,5,Object.extend({scaleContent:false,scaleX:false,afterFinishInternal:function(_151){
new Effect.Scale(_149,1,{scaleContent:false,scaleY:false,afterFinishInternal:function(_151){
with(Element){
[hide,undoClipping].call(_151.element);
setStyle(_151.element,_150);
}
}});
}},arguments[1]||{}));
};

