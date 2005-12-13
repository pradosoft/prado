CustomEvent=Class.create();
CustomEvent.prototype={initialize:function(){
this.listeners=[];
},addListener:function(_1){
this.listeners.push(_1);
},removeListener:function(_2){
var _3=this._findListenerIndexes(_2);
for(var i=0;i<_3.length;i++){
this.listeners.splice(_3[i],1);
}
},dispatch:function(_5){
for(var i=0;i<this.listeners.length;i++){
try{
this.listeners[i](_5);
}
catch(e){
alert("Could not run the listener "+this.listeners[i]+". "+e);
}
}
},_findListenerIndexes:function(_6){
var _7=[];
for(var i=0;i<this.listeners.length;i++){
if(this.listeners[i]==_6){
_7.push(i);
}
}
return _7;
}};
var Cookie={set:function(_8,_9,_10,_11){
var _12=escape(_8)+"="+escape(_9);
if(_10){
var _13=new Date();
_13.setDate(_13.getDate()+_10);
_12+="; expires="+_13.toGMTString();
}
if(_11){
_12+=";path="+_11;
}
document.cookie=_12;
if(_9&&(_10==undefined||_10>0)&&!this.get(_8)){
Logger.error("Cookie ("+_8+") was not set correctly... The value was "+_9.toString().length+" charachters long (This may be over the cookie limit)");
}
},get:function(_14){
var _15="(^|;)\\s*"+escape(_14)+"=([^;]+)";
var m=document.cookie.match(_15);
if(m&&m[2]){
return unescape(m[2]);
}else{
return null;
}
},getAll:function(){
var _17=document.cookie.split(";");
var _18=[];
for(var i=0;i<_17.length;i++){
try{
var _19=unescape(_17[i].match(/^\s*([^=]+)/m)[1]);
var _20=unescape(_17[i].match(/=(.*$)/m)[1]);
}
catch(e){
continue;
}
_18.push({name:_19,value:_20});
if(_18[_19]!=undefined){
Logger.waring("Trying to retrieve cookie named("+_19+"). There appears to be another property with this name though.");
}
_18[_19]=_20;
}
return _18;
},clear:function(_21){
this.set(_21,"",-1);
},clearAll:function(){
var _22=this.getAll();
for(var i=0;i<_22.length;i++){
this.clear(_22[i].name);
}
}};
Logger={logEntries:[],onupdate:new CustomEvent(),onclear:new CustomEvent(),log:function(_23,tag){
var _25=new LogEntry(_23,tag||"info");
this.logEntries.push(_25);
this.onupdate.dispatch(_25);
},info:function(_26){
this.log(_26,"info");
},debug:function(_27){
this.log(_27,"debug");
},warn:function(_28){
this.log(_28,"warning");
},error:function(_29,_30){
this.log(_29+": \n"+_30,"error");
},clear:function(){
this.logEntries=[];
this.onclear.dispatch();
}};
LogEntry=Class.create();
LogEntry.prototype={initialize:function(_31,tag){
this.message=_31;
this.tag=tag;
}};
LogConsole=Class.create();
LogConsole.prototype={commandHistory:[],commandIndex:0,initialize:function(){
this.outputCount=0;
this.tagPattern=Cookie.get("tagPattern")||".*";
this.logElement=document.createElement("div");
document.body.appendChild(this.logElement);
Element.hide(this.logElement);
this.logElement.style.position="absolute";
this.logElement.style.left="0px";
this.logElement.style.width="100%";
this.logElement.style.textAlign="left";
this.logElement.style.fontFamily="lucida console";
this.logElement.style.fontSize="100%";
this.logElement.style.backgroundColor="darkgray";
this.logElement.style.opacity=0.9;
this.logElement.style.zIndex=2000;
this.toolbarElement=document.createElement("div");
this.logElement.appendChild(this.toolbarElement);
this.toolbarElement.style.padding="0 0 0 2px";
this.buttonsContainerElement=document.createElement("span");
this.toolbarElement.appendChild(this.buttonsContainerElement);
this.buttonsContainerElement.innerHTML+="<button onclick=\"logConsole.toggle()\" style=\"float:right;color:black\">close</button>";
this.buttonsContainerElement.innerHTML+="<button onclick=\"Logger.clear()\" style=\"float:right;color:black\">clear</button>";
if(!Prado.Inspector.disabled){
this.buttonsContainerElement.innerHTML+="<button onclick=\"Prado.Inspector.inspect()\" style=\"float:right;color:black; margin-right:15px;\">Object Tree</button>";
}
this.tagFilterContainerElement=document.createElement("span");
this.toolbarElement.appendChild(this.tagFilterContainerElement);
this.tagFilterContainerElement.style.cssFloat="left";
this.tagFilterContainerElement.appendChild(document.createTextNode("Log Filter"));
this.tagFilterElement=document.createElement("input");
this.tagFilterContainerElement.appendChild(this.tagFilterElement);
this.tagFilterElement.style.width="200px";
this.tagFilterElement.value=this.tagPattern;
this.tagFilterElement.setAttribute("autocomplete","off");
Event.observe(this.tagFilterElement,"keyup",this.updateTags.bind(this));
Event.observe(this.tagFilterElement,"click",function(){
this.tagFilterElement.select();
}.bind(this));
this.outputElement=document.createElement("div");
this.logElement.appendChild(this.outputElement);
this.outputElement.style.overflow="auto";
this.outputElement.style.clear="both";
this.outputElement.style.height="200px";
this.outputElement.style.backgroundColor="black";
this.inputContainerElement=document.createElement("div");
this.inputContainerElement.style.width="100%";
this.logElement.appendChild(this.inputContainerElement);
this.inputElement=document.createElement("input");
this.inputContainerElement.appendChild(this.inputElement);
this.inputElement.style.width="100%";
this.inputElement.style.borderWidth="0px";
this.inputElement.style.margin="0px";
this.inputElement.style.padding="0px";
this.inputElement.value="Type command here";
this.inputElement.setAttribute("autocomplete","off");
Event.observe(this.inputElement,"keyup",this.handleInput.bind(this));
Event.observe(this.inputElement,"click",function(){
this.inputElement.select();
}.bind(this));
window.setInterval(this.repositionWindow.bind(this),500);
this.repositionWindow();
Logger.onupdate.addListener(this.logUpdate.bind(this));
Logger.onclear.addListener(this.clear.bind(this));
for(var i=0;i<Logger.logEntries.length;i++){
this.logUpdate(Logger.logEntries[i]);
}
Event.observe(window,"error",function(msg,url,_34){
Logger.error("Error in ("+(url||location)+") on line "+_34+"",msg);
});
var _35=document.createElement("span");
_35.innerHTML="<button style=\"position:absolute;top:-100px\" onclick=\"javascript:logConsole.toggle()\" accesskey=\"d\"></button>";
document.body.appendChild(_35);
if(Cookie.get("ConsoleVisible")=="true"){
this.toggle();
}
},toggle:function(){
if(this.logElement.style.display=="none"){
this.show();
}else{
this.hide();
}
},show:function(){
Element.show(this.logElement);
this.outputElement.scrollTop=this.outputElement.scrollHeight;
Cookie.set("ConsoleVisible","true");
this.inputElement.select();
},hide:function(){
Element.hide(this.logElement);
Cookie.set("ConsoleVisible","false");
},output:function(_36,_37){
var _38=(this.outputElement.scrollTop+(2*this.outputElement.clientHeight))>=this.outputElement.scrollHeight;
this.outputCount++;
_37=(_37?_37+=";":"");
_37+="padding:1px;margin:0 0 5px 0";
if(this.outputCount%2==0){
_37+=";background-color:#101010";
}
_36=_36||"undefined";
_36=_36.toString().escapeHTML();
this.outputElement.innerHTML+="<pre style='"+_37+"'>"+_36+"</pre>";
if(_38){
this.outputElement.scrollTop=this.outputElement.scrollHeight;
}
},updateTags:function(){
var _39=this.tagFilterElement.value;
if(this.tagPattern==_39){
return;
}
try{
new RegExp(_39);
}
catch(e){
return;
}
this.tagPattern=_39;
Cookie.set("tagPattern",this.tagPattern);
this.outputElement.innerHTML="";
this.outputCount=0;
for(var i=0;i<Logger.logEntries.length;i++){
this.logUpdate(Logger.logEntries[i]);
}
},repositionWindow:function(){
var _40=window.pageYOffset||document.documentElement.scrollTop||document.body.scrollTop;
var _41=self.innerHeight||document.documentElement.clientHeight||document.body.clientHeight;
this.logElement.style.top=(_40+_41-Element.getHeight(this.logElement))+"px";
},logUpdate:function(_42){
if(_42.tag.search(new RegExp(this.tagPattern,"igm"))==-1){
return;
}
var _43="";
if(_42.tag.search(/error/)!=-1){
_43+="color:red";
}else{
if(_42.tag.search(/warning/)!=-1){
_43+="color:orange";
}else{
if(_42.tag.search(/debug/)!=-1){
_43+="color:green";
}else{
if(_42.tag.search(/info/)!=-1){
_43+="color:white";
}else{
_43+="color:yellow";
}
}
}
}
this.output(_42.message,_43);
},clear:function(e){
this.outputElement.innerHTML="";
},handleInput:function(e){
if(e.keyCode==Event.KEY_RETURN){
var _45=this.inputElement.value;
switch(_45){
case "clear":
Logger.clear();
break;
default:
var _46="";
try{
_46=eval(this.inputElement.value);
}
catch(e){
Logger.error("Problem parsing input <"+_45+">",e);
break;
}
Logger.log(_46);
break;
}
if(this.inputElement.value!=""&&this.inputElement.value!=this.commandHistory[0]){
this.commandHistory.unshift(this.inputElement.value);
}
this.commandIndex=0;
this.inputElement.value="";
}else{
if(e.keyCode==Event.KEY_UP&&this.commandHistory.length>0){
this.inputElement.value=this.commandHistory[this.commandIndex];
if(this.commandIndex<this.commandHistory.length-1){
this.commandIndex+=1;
}
}else{
if(e.keyCode==Event.KEY_DOWN&&this.commandHistory.length>0){
if(this.commandIndex>0){
this.commandIndex-=1;
}
this.inputElement.value=this.commandHistory[this.commandIndex];
}else{
this.commandIndex=0;
}
}
}
}};
Event.observe(window,"load",function(){
logConsole=new LogConsole();
});
function inspect(_47,_48,_49){
var _50=[];
var _51=[];
for(var _52 in _47){
if(_52=="______array"){
continue;
}
try{
if(_47[_52] instanceof Function){
if(_49){
_51.push(_52+":\t"+_47[_52]);
}
}else{
if(_47[_52] instanceof Object){
_51.push(_52+":\t"+inspect(_47[_52],_48,_49));
}else{
if(!_48){
_50.push(_52+":\t"+_47[_52]);
}
}
}
}
catch(e){
Logger.error("Excetion thrown while inspecting object.",e);
}
}
_50.sort();
_51.sort();
var _53=_50.concat(_51);
var _54="";
for(var i=0;i<_53.length;i++){
_54+=(_53[i]+"\n");
}
return _54;
}
Array.prototype.contains=function(_55){
for(var i=0;i<this.length;i++){
if(_55==this[i]){
return true;
}
}
return false;
};
var puts=function(){
return Logger.log(arguments[0],arguments[1]);
};
if(typeof Prado=="undefined"){
var Prado={};
}
Prado.Inspector={d:document,types:new Array(),objs:new Array(),hidden:new Array(),opera:window.opera,displaying:"",format:function(str){
str=str.replace(/</g,"&lt;");
str=str.replace(/>/g,"&gt;");
return str;
},parseJS:function(obj){
var _58;
if(typeof obj=="string"){
_58=obj;
obj=eval(obj);
}
win=typeof obj=="undefined"?window:obj;
this.displaying=_58?_58:win.toString();
for(js in win){
try{
if(win[js]&&js.toString().indexOf("Inspector")==-1&&win[js].toString().indexOf("[native code]")==-1){
t=typeof (win[js]);
if(!this.objs[t.toString()]){
this.types[this.types.length]=t;
this.objs[t]=new Array();
}
index=this.objs[t].length;
this.objs[t][index]=new Array();
this.objs[t][index][0]=js;
this.objs[t][index][1]=this.format(win[js].toString());
}
}
catch(err){
}
}
},show:function(_59){
this.d.getElementById(_59).style.display=this.hidden[_59]?"none":"block";
this.hidden[_59]=this.hidden[_59]?0:1;
},changeSpan:function(_60){
if(this.d.getElementById(_60).innerHTML.indexOf("+")>-1){
this.d.getElementById(_60).innerHTML="[-]";
}else{
this.d.getElementById(_60).innerHTML="[+]";
}
},buildInspectionLevel:function(){
var _61=this.displaying;
var _62=_61.split(".");
var _63=["<a href=\"javascript:var_dump()\">[object Window]</a>"];
var _64="";
if(_61.indexOf("[object ")>=0){
return _63.join(".");
}
for(var i=0;i<_62.length;i++){
_64+=(_64.length?".":"")+_62[i];
_63[i+1]="<a href=\"javascript:var_dump('"+_64+"')\">"+_62[i]+"</a>";
}
return _63.join(".");
},buildTree:function(){
mHTML="<div>Inspecting "+this.buildInspectionLevel()+"</div>";
mHTML+="<ul class=\"topLevel\">";
this.types.sort();
var _65=0;
for(i=0;i<this.types.length;i++){
mHTML+="<li style=\"cursor:pointer;\" onclick=\"Prado.Inspector.show('ul"+i+"');Prado.Inspector.changeSpan('sp"+i+"')\"><span id=\"sp"+i+"\">[+]</span><b>"+this.types[i]+"</b> ("+this.objs[this.types[i]].length+")</li><ul style=\"display:none;\" id=\"ul"+i+"\">";
this.hidden["ul"+i]=0;
for(e=0;e<this.objs[this.types[i]].length;e++){
var _66="";
if(this.objs[this.types[i]][e][1].indexOf("[object ")>=0&&/^[a-zA-Z_]/.test(this.objs[this.types[i]][e][0][0])){
if(this.displaying.indexOf("[object ")<0){
_66=" <a href=\"javascript:var_dump('"+this.displaying+"."+this.objs[this.types[i]][e][0]+"')\"><b>more</b></a>";
}else{
if(this.displaying.indexOf("[object Window]")>=0){
_66=" <a href=\"javascript:var_dump('"+this.objs[this.types[i]][e][0]+"')\"><b>more</b></a>";
}
}
}
mHTML+="<li style=\"cursor:pointer;\" onclick=\"Prado.Inspector.show('mul"+_65+"');Prado.Inspector.changeSpan('sk"+_65+"')\"><span id=\"sk"+_65+"\">[+]</span>"+this.objs[this.types[i]][e][0]+"</li><ul id=\"mul"+_65+"\" style=\"display:none;\"><li style=\"list-style-type:none;\"><pre>"+this.objs[this.types[i]][e][1]+_66+"</pre></li></ul>";
this.hidden["mul"+_65]=0;
_65++;
}
mHTML+="</ul>";
}
mHTML+="</ul>";
this.d.getElementById("so_mContainer").innerHTML=mHTML;
},handleKeyEvent:function(e){
keyCode=document.all?window.event.keyCode:e.keyCode;
if(keyCode==27){
this.cleanUp();
}
},cleanUp:function(){
if(this.d.getElementById("so_mContainer")){
this.d.body.removeChild(this.d.getElementById("so_mContainer"));
this.d.body.removeChild(this.d.getElementById("so_mStyle"));
if(typeof Event!="undefined"){
Event.stopObserving(this.d,"keydown",this.handleKeyEvent.bind(this));
}
this.types=new Array();
this.objs=new Array();
this.hidden=new Array();
}
},disabled:document.all&&!this.opera,inspect:function(obj){
if(this.disabled){
return alert("Sorry, this only works in Mozilla and Firefox currently.");
}
this.cleanUp();
mObj=this.d.body.appendChild(this.d.createElement("div"));
mObj.id="so_mContainer";
sObj=this.d.body.appendChild(this.d.createElement("style"));
sObj.id="so_mStyle";
sObj.type="text/css";
sObj.innerHTML=this.style;
if(typeof Event!="undefined"){
Event.observe(this.d,"keydown",this.handleKeyEvent.bind(this));
}
this.parseJS(obj);
this.buildTree();
cObj=mObj.appendChild(this.d.createElement("div"));
cObj.className="credits";
cObj.innerHTML="<b>[esc] to <a href=\"javascript:Prado.Inspector.cleanUp();\">close</a></b><br />Javascript Object Tree V2.0, <a target=\"_blank\" href=\"http://slayeroffice.com/?c=/content/tools/js_tree.html\">more info</a>.";
window.scrollTo(0,0);
},style:"#so_mContainer { position:absolute; top:5px; left:5px; background-color:#E3EBED; text-align:left; font:9pt verdana; width:85%; border:2px solid #000; padding:5px; z-index:1000;  color:#000; } "+"#so_mContainer ul { padding-left:20px; } "+"#so_mContainer ul li { display:block; list-style-type:none; list-style-image:url(); line-height:2em; -moz-border-radius:.75em; font:10px verdana; padding:0; margin:2px; color:#000; } "+"#so_mContainer li:hover { background-color:#E3EBED; } "+"#so_mContainer ul li span { position:relative; width:15px; height:15px; margin-right:4px; } "+"#so_mContainer pre { background-color:#F9FAFB; border:1px solid #638DA1; height:auto; padding:5px; font:9px verdana; color:#000; } "+"#so_mContainer .topLevel { margin:0; padding:0; } "+"#so_mContainer .credits { float:left; width:200px; font:6.5pt verdana; color:#000; padding:2px; margin-left:5px; text-align:left; border-top:1px solid #000; margin-top:15px; width:75%; } "+"#so_mContainer .credits a { font:9px verdana; font-weight:bold; color:#004465; text-decoration:none; background-color:transparent; }"};
function var_dump(obj){
Prado.Inspector.inspect(obj);
}

