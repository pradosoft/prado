Prado.Calendar=Class.create();
Prado.Calendar.Util=Class.create();
Object.extend(Prado.Calendar.Util,{IsLeapYear:function(_1){
return ((_1%4==0)&&((_1%100!=0)||(_1%400==0)));
},yearLength:function(_2){
if(this.isLeapYear(_2)){
return 366;
}else{
return 365;
}
},dayOfYear:function(_3){
var a=this.isLeapYear(_3.getFullYear())?Calendar.LEAP_NUM_DAYS:Calendar.NUM_DAYS;
return a[_3.getMonth()]+_3.getDate();
},browser:function(){
var _5={Version:"1.0"};
var _6=parseInt(navigator.appVersion);
_5.nver=_6;
_5.ver=navigator.appVersion;
_5.agent=navigator.userAgent;
_5.dom=document.getElementById?1:0;
_5.opera=window.opera?1:0;
_5.ie5=(_5.ver.indexOf("MSIE 5")>-1&&_5.dom&&!_5.opera)?1:0;
_5.ie6=(_5.ver.indexOf("MSIE 6")>-1&&_5.dom&&!_5.opera)?1:0;
_5.ie4=(document.all&&!_5.dom&&!_5.opera)?1:0;
_5.ie=_5.ie4||_5.ie5||_5.ie6;
_5.mac=_5.agent.indexOf("Mac")>-1;
_5.ns6=(_5.dom&&parseInt(_5.ver)>=5)?1:0;
_5.ie3=(_5.ver.indexOf("MSIE")&&(_6<4));
_5.hotjava=(_5.agent.toLowerCase().indexOf("hotjava")!=-1)?1:0;
_5.ns4=(document.layers&&!_5.dom&&!_5.hotjava)?1:0;
_5.bw=(_5.ie6||_5.ie5||_5.ie4||_5.ns4||_5.ns6||_5.opera);
_5.ver3=(_5.hotjava||_5.ie3);
_5.opera7=((_5.agent.toLowerCase().indexOf("opera 7")>-1)||(_5.agent.toLowerCase().indexOf("opera/7")>-1));
_5.operaOld=_5.opera&&!_5.opera7;
return _5;
},ImportCss:function(_7,_8){
if(this.browser().ie){
var _9=_7.createStyleSheet(_8);
}else{
var elm=_7.createElement("link");
elm.rel="stylesheet";
elm.href=_8;
if(headArr=_7.getElementsByTagName("head")){
headArr[0].appendChild(elm);
}
}
}});
Object.extend(Prado.Calendar,{NUM_DAYS:[0,31,59,90,120,151,181,212,243,273,304,334],LEAP_NUM_DAYS:[0,31,60,91,121,152,182,213,244,274,305,335]});
Prado.Calendar.prototype={monthNames:["January","February","March","April","May","June","July","August","September","October","November","December"],shortWeekDayNames:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],format:"yyyy-MM-dd",css:"calendar_system.css",initialize:function(_11,_12){
this.attr=_12||[];
this.control=$(_11);
this.dateSlot=new Array(42);
this.weekSlot=new Array(6);
this.firstDayOfWeek=1;
this.minimalDaysInFirstWeek=4;
this.currentDate=new Date();
this.selectedDate=null;
this.className="Prado_Calendar";
this.trigger=this.attr.trigger?$(this.attr.trigger):this.control;
Event.observe(this.trigger,"click",this.show.bind(this));
Prado.Calendar.Util.ImportCss(document,this.css);
if(this.attr.format){
this.format=this.attr.format;
}
this.create();
this.hookEvents();
},create:function(){
var div;
var _14;
var _15;
var tr;
var td;
this._calDiv=document.createElement("div");
this._calDiv.className=this.className;
this._calDiv.style.display="none";
div=document.createElement("div");
div.className="calendarHeader";
this._calDiv.appendChild(div);
_14=document.createElement("table");
_14.style.cellSpacing=0;
div.appendChild(_14);
_15=document.createElement("tbody");
_14.appendChild(_15);
tr=document.createElement("tr");
_15.appendChild(tr);
td=document.createElement("td");
td.className="prevMonthButton";
this._previousMonth=document.createElement("button");
this._previousMonth.appendChild(document.createTextNode("<<"));
td.appendChild(this._previousMonth);
tr.appendChild(td);
td=document.createElement("td");
td.className="labelContainer";
tr.appendChild(td);
this._monthSelect=document.createElement("select");
for(var i=0;i<this.monthNames.length;i++){
var opt=document.createElement("option");
opt.innerHTML=this.monthNames[i];
opt.value=i;
if(i==this.currentDate.getMonth()){
opt.selected=true;
}
this._monthSelect.appendChild(opt);
}
td.appendChild(this._monthSelect);
td=document.createElement("td");
td.className="labelContainer";
tr.appendChild(td);
this._yearSelect=document.createElement("select");
for(var i=1920;i<2050;++i){
var opt=document.createElement("option");
opt.innerHTML=i;
opt.value=i;
if(i==this.currentDate.getFullYear()){
opt.selected=false;
}
this._yearSelect.appendChild(opt);
}
td.appendChild(this._yearSelect);
td=document.createElement("td");
td.className="nextMonthButton";
this._nextMonth=document.createElement("button");
this._nextMonth.appendChild(document.createTextNode(">>"));
td.appendChild(this._nextMonth);
tr.appendChild(td);
div=document.createElement("div");
div.className="calendarBody";
this._calDiv.appendChild(div);
this._table=div;
var _20;
_14=document.createElement("table");
_14.className="grid";
div.appendChild(_14);
var _21=document.createElement("thead");
_14.appendChild(_21);
tr=document.createElement("tr");
_21.appendChild(tr);
for(i=0;i<7;++i){
td=document.createElement("th");
_20=document.createTextNode(this.shortWeekDayNames[(i+this.firstDayOfWeek)%7]);
td.appendChild(_20);
td.className="weekDayHead";
tr.appendChild(td);
}
_15=document.createElement("tbody");
_14.appendChild(_15);
for(week=0;week<6;++week){
tr=document.createElement("tr");
_15.appendChild(tr);
for(day=0;day<7;++day){
td=document.createElement("td");
td.className="calendarDate";
_20=document.createTextNode(String.fromCharCode(160));
td.appendChild(_20);
tr.appendChild(td);
var tmp=new Object();
tmp.tag="DATE";
tmp.value=-1;
tmp.data=_20;
this.dateSlot[(week*7)+day]=tmp;
Event.observe(td,"mouseover",this.hover.bind(this));
Event.observe(td,"mouseout",this.hover.bind(this));
}
}
div=document.createElement("div");
div.className="calendarFooter";
this._calDiv.appendChild(div);
_14=document.createElement("table");
_14.className="footerTable";
div.appendChild(_14);
_15=document.createElement("tbody");
_14.appendChild(_15);
tr=document.createElement("tr");
_15.appendChild(tr);
td=document.createElement("td");
td.className="todayButton";
this._todayButton=document.createElement("button");
var _23=new Date();
var _24=_23.getDate()+" "+this.monthNames[_23.getMonth()]+", "+_23.getFullYear();
this._todayButton.appendChild(document.createTextNode(_24));
td.appendChild(this._todayButton);
tr.appendChild(td);
td=document.createElement("td");
td.className="clearButton";
this._clearButton=document.createElement("button");
var _23=new Date();
_24="Clear";
this._clearButton.appendChild(document.createTextNode(_24));
td.appendChild(this._clearButton);
tr.appendChild(td);
document.body.appendChild(this._calDiv);
this.update();
this.updateHeader();
return this._calDiv;
},hookEvents:function(){
this._previousMonth.hideFocus=true;
this._nextMonth.hideFocus=true;
this._todayButton.hideFocus=true;
Event.observe(this._previousMonth,"click",this.prevMonth.bind(this));
Event.observe(this._nextMonth,"click",this.nextMonth.bind(this));
Event.observe(this._todayButton,"click",this.selectToday.bind(this));
Event.observe(this._clearButton,"click",this.clearSelection.bind(this));
Event.observe(this._monthSelect,"change",this.monthSelect.bind(this));
Event.observe(this._yearSelect,"change",this.yearSelect.bind(this));
Event.observe(this._calDiv,"mousewheel",this.mouseWheelChange.bind(this));
Event.observe(this._table,"click",this.selectDate.bind(this));
Event.observe(this._calDiv,"keydown",this.keyPressed.bind(this));
},keyPressed:function(ev){
if(!ev){
ev=document.parentWindow.event;
}
var kc=ev.keyCode!=null?ev.keyCode:ev.charCode;
if(kc=Event.KEY_RETURN){
this.setSelectedDate(this.currentDate);
this.hide();
return false;
}
if(kc<37||kc>40){
return true;
}
var d=new Date(this.currentDate).valueOf();
if(kc==Event.KEY_LEFT){
d-=86400000;
}else{
if(kc==Event.KEY_RIGHT){
d+=86400000;
}else{
if(kc==Event.KEY_UP){
d-=604800000;
}else{
if(kc==Event.KEY_DOWN){
d+=604800000;
}
}
}
}
this.setCurrentDate(new Date(d));
return false;
},selectDate:function(ev){
var el=Event.element(ev);
while(el.nodeType!=1){
el=el.parentNode;
}
while(el!=null&&el.tagName&&el.tagName.toLowerCase()!="td"){
el=el.parentNode;
}
if(el==null||el.tagName==null||el.tagName.toLowerCase()!="td"){
return;
}
var d=new Date(this.currentDate);
var n=Number(el.firstChild.data);
if(isNaN(n)||n<=0||n==null){
return;
}
d.setDate(n);
this.setSelectedDate(d);
this.hide();
},selectToday:function(){
this.setSelectedDate(new Date());
this.hide();
},clearSelection:function(){
this.selectedDate=null;
if(isFunction(this.onchange)){
this.onchange();
}
this.hide();
},monthSelect:function(ev){
this.setMonth(Form.Element.getValue(Event.element(ev)));
},yearSelect:function(ev){
this.setYear(Form.Element.getValue(Event.element(ev)));
},mouseWheelChange:function(e){
if(e==null){
e=document.parentWindow.event;
}
var n=-e.wheelDelta/120;
var d=new Date(this.currentDate);
var m=this.getMonth()+n;
this.setMonth(m);
this.setCurrentDate(d);
return false;
},onchange:function(){
this.control.value=this.formatDate();
},formatDate:function(){
return Prado.Calendar.Util.FormatDate(this.selectedDate,this.format);
},setCurrentDate:function(_32){
if(_32==null){
return;
}
if(isString(_32)||isNumber(_32)){
_32=new Date(_32);
}
if(this.currentDate.getDate()!=_32.getDate()||this.currentDate.getMonth()!=_32.getMonth()||this.currentDate.getFullYear()!=_32.getFullYear()){
this.currentDate=new Date(_32);
this.updateHeader();
this.update();
}
},setSelectedDate:function(_33){
this.selectedDate=new Date(_33);
this.setCurrentDate(this.selectedDate);
if(isFunction(this.onchange)){
this.onchange();
}
},getElement:function(){
return this._calDiv;
},getSelectedDate:function(){
return isNull(this.selectedDate)?null:new Date(this.selectedDate);
},setYear:function(_34){
var d=new Date(this.currentDate);
d.setFullYear(_34);
this.setCurrentDate(d);
},setMonth:function(_35){
var d=new Date(this.currentDate);
d.setMonth(_35);
this.setCurrentDate(d);
},nextMonth:function(){
this.setMonth(this.currentDate.getMonth()+1);
},prevMonth:function(){
this.setMonth(this.currentDate.getMonth()-1);
},show:function(){
if(!this.showing){
var pos=Position.cumulativeOffset(this.control);
pos[1]+=this.control.offsetHeight;
this._calDiv.style.display="block";
this._calDiv.style.top=pos[1]+"px";
this._calDiv.style.left=pos[0]+"px";
Event.observe(document.body,"click",this.hideOnClick.bind(this));
var _37=Prado.Calendar.Util.ParseDate(Form.Element.getValue(this.control),this.format);
if(!isNull(_37)){
this.selectedDate=_37;
this.setCurrentDate(_37);
}
this.showing=true;
}
},hideOnClick:function(ev){
if(!this.showing){
return;
}
var el=Event.element(ev);
var _38=false;
do{
_38=_38||el.className==this.className;
_38=_38||el==this.trigger;
_38=_38||el==this.control;
if(_38){
break;
}
el=el.parentNode;
}while(el);
if(!_38){
this.hide();
}
},hide:function(){
if(this.showing){
this._calDiv.style.display="none";
this.showing=false;
Event.stopObserving(document.body,"click",this.hideOnClick.bind(this));
}
},update:function(){
var _39=Prado.Calendar.Util;
var _40=this.currentDate;
var _41=_39.ISODate(new Date());
var _42=isNull(this.selectedDate)?"":_39.ISODate(this.selectedDate);
var _43=_39.ISODate(_40);
var d1=new Date(_40.getFullYear(),_40.getMonth(),1);
var d2=new Date(_40.getFullYear(),_40.getMonth()+1,1);
var _46=Math.round((d2-d1)/(24*60*60*1000));
var _47=(d1.getDay()-this.firstDayOfWeek)%7;
if(_47<0){
_47+=7;
}
var _48=0;
while(_48<_47){
this.dateSlot[_48].value=-1;
this.dateSlot[_48].data.data=String.fromCharCode(160);
this.dateSlot[_48].data.parentNode.className="empty";
_48++;
}
for(i=1;i<=_46;i++,_48++){
var _49=this.dateSlot[_48];
var _50=_49.data.parentNode;
_49.value=i;
_49.data.data=i;
_50.className="date";
if(_39.ISODate(d1)==_41){
_50.className+=" today";
}
if(_39.ISODate(d1)==_43){
_50.className+=" current";
}
if(_39.ISODate(d1)==_42){
_50.className+=" selected";
}
d1=new Date(d1.getFullYear(),d1.getMonth(),d1.getDate()+1);
}
var _51=_48;
while(_48<42){
this.dateSlot[_48].value=-1;
this.dateSlot[_48].data.data=String.fromCharCode(160);
this.dateSlot[_48].data.parentNode.className="empty";
++_48;
}
},hover:function(ev){
Element.condClassName(Event.element(ev),"hover",ev.type=="mouseover");
},updateHeader:function(){
var _52=this._monthSelect.options;
var m=this.currentDate.getMonth();
for(var i=0;i<_52.length;++i){
_52[i].selected=false;
if(_52[i].value==m){
_52[i].selected=true;
}
}
_52=this._yearSelect.options;
var _53=this.currentDate.getFullYear();
for(var i=0;i<_52.length;++i){
_52[i].selected=false;
if(_52[i].value==_53){
_52[i].selected=true;
}
}
}};

