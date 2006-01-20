Prado.WebUI.TDatePicker=Class.create();
Prado.WebUI.TDatePicker.prototype={MonthNames:["January","February","March","April","May","June","July","August","September","October","November","December"],ShortWeekDayNames:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],Format:"yyyy-MM-dd",FirstDayOfWeek:1,ClassName:"TDatePicker",FromYear:2000,UpToYear:2015,initialize:function(_1,_2){
this.attr=_2||[];
this.control=$(_1);
this.dateSlot=new Array(42);
this.weekSlot=new Array(6);
this.minimalDaysInFirstWeek=4;
this.selectedDate=this.newDate();
if(this.attr.Trigger){
this.trigger=$(this.attr.Trigger);
var _3=this.attr.TriggerEvent||"click";
}else{
this.trigger=this.control;
var _3=this.attr.TriggerEvent||"focus";
}
Event.observe(this.trigger,_3,this.show.bindEvent(this));
Object.extend(this,_2);
this.create();
},create:function(){
var _4;
var _5;
var _6;
var tr;
var td;
this._calDiv=document.createElement("div");
this._calDiv.className=this.ClassName;
this._calDiv.style.display="none";
this._calDiv.style.position="absolute";
_4=document.createElement("div");
_4.className="calendarHeader";
this._calDiv.appendChild(_4);
_5=document.createElement("table");
_5.style.cellSpacing=0;
_4.appendChild(_5);
_6=document.createElement("tbody");
_5.appendChild(_6);
tr=document.createElement("tr");
_6.appendChild(tr);
td=document.createElement("td");
var _9=document.createElement("button");
_9.className="prevMonthButton";
_9.appendChild(document.createTextNode("<<"));
td.appendChild(_9);
tr.appendChild(td);
td=document.createElement("td");
tr.appendChild(td);
this._monthSelect=document.createElement("select");
this._monthSelect.className="months";
for(var i=0;i<this.MonthNames.length;i++){
var opt=document.createElement("option");
opt.innerHTML=this.MonthNames[i];
opt.value=i;
if(i==this.selectedDate.getMonth()){
opt.selected=true;
}
this._monthSelect.appendChild(opt);
}
td.appendChild(this._monthSelect);
td=document.createElement("td");
td.className="labelContainer";
tr.appendChild(td);
this._yearSelect=document.createElement("select");
for(var i=this.FromYear;i<=this.UpToYear;++i){
var opt=document.createElement("option");
opt.innerHTML=i;
opt.value=i;
if(i==this.selectedDate.getFullYear()){
opt.selected=false;
}
this._yearSelect.appendChild(opt);
}
td.appendChild(this._yearSelect);
td=document.createElement("td");
td.className="nextMonthButton";
var _12=document.createElement("button");
_12.appendChild(document.createTextNode(">>"));
td.appendChild(_12);
tr.appendChild(td);
_4=document.createElement("div");
_4.className="calendarBody";
this._calDiv.appendChild(_4);
var _13=_4;
var _14;
_5=document.createElement("table");
_5.className="grid";
_4.appendChild(_5);
var _15=document.createElement("thead");
_5.appendChild(_15);
tr=document.createElement("tr");
_15.appendChild(tr);
for(i=0;i<7;++i){
td=document.createElement("th");
_14=document.createTextNode(this.ShortWeekDayNames[(i+this.FirstDayOfWeek)%7]);
td.appendChild(_14);
td.className="weekDayHead";
tr.appendChild(td);
}
_6=document.createElement("tbody");
_5.appendChild(_6);
for(week=0;week<6;++week){
tr=document.createElement("tr");
_6.appendChild(tr);
for(day=0;day<7;++day){
td=document.createElement("td");
td.className="calendarDate";
_14=document.createTextNode(String.fromCharCode(160));
td.appendChild(_14);
tr.appendChild(td);
var tmp=new Object();
tmp.tag="DATE";
tmp.value=-1;
tmp.data=_14;
this.dateSlot[(week*7)+day]=tmp;
Event.observe(td,"mouseover",this.hover.bindEvent(this));
Event.observe(td,"mouseout",this.hover.bindEvent(this));
}
}
_4=document.createElement("div");
_4.className="calendarFooter";
this._calDiv.appendChild(_4);
var _17=document.createElement("button");
_17.className="todayButton";
var _18=this.newDate();
var _19=_18.SimpleFormat(this.Format);
_17.appendChild(document.createTextNode(_19));
_4.appendChild(_17);
if(Prado.Browser().ie){
this.iePopUp=document.createElement("iframe");
this.iePopUp.src="";
this.iePopUp.style.position="absolute";
this.iePopUp.scrolling="no";
this.iePopUp.frameBorder="0";
document.body.appendChild(this.iePopUp);
}
document.body.appendChild(this._calDiv);
this.update();
this.updateHeader();
this.ieHack(true);
_9.hideFocus=true;
_12.hideFocus=true;
_17.hideFocus=true;
Event.observe(_9,"click",this.prevMonth.bindEvent(this));
Event.observe(_12,"click",this.nextMonth.bindEvent(this));
Event.observe(_17,"click",this.selectToday.bindEvent(this));
Event.observe(this._monthSelect,"change",this.monthSelect.bindEvent(this));
Event.observe(this._yearSelect,"change",this.yearSelect.bindEvent(this));
Event.observe(this._calDiv,"mousewheel",this.mouseWheelChange.bindEvent(this));
Event.observe(_13,"click",this.selectDate.bindEvent(this));
},ieHack:function(_20){
if(this.iePopUp){
this.iePopUp.style.display="block";
this.iePopUp.style.top=(this._calDiv.offsetTop-1)+"px";
this.iePopUp.style.left=(this._calDiv.offsetLeft-1)+"px";
this.iePopUp.style.width=Math.abs(this._calDiv.offsetWidth-2)+"px";
this.iePopUp.style.height=(this._calDiv.offsetHeight+1)+"px";
if(_20){
this.iePopUp.style.display="none";
}
}
},keyPressed:function(ev){
if(!this.showing){
return;
}
if(!ev){
ev=document.parentWindow.event;
}
var kc=ev.keyCode!=null?ev.keyCode:ev.charCode;
if(kc==Event.KEY_RETURN){
this.setSelectedDate(this.selectedDate);
Event.stop(ev);
this.hide();
}
if(kc==Event.KEY_ESC){
Event.stop(ev);
this.hide();
}
var _23=function(_24,_25){
_24=(_24+12)%12;
var _26=[31,28,31,30,31,30,31,31,30,31,30,31];
var res=_26[_24];
if(_24==1){
res+=_25%4==0&&!(_25%400==0)?1:0;
}
return res;
};
if(kc<37||kc>40){
return true;
}
var _28=this.selectedDate;
var d=_28.valueOf();
if(kc==Event.KEY_LEFT){
if(ev.ctrlKey||ev.shiftKey){
_28.setDate(Math.min(_28.getDate(),_23(_28.getMonth()-1,_28.getFullYear())));
d=_28.setMonth(_28.getMonth()-1);
}else{
d-=86400000;
}
}else{
if(kc==Event.KEY_RIGHT){
if(ev.ctrlKey||ev.shiftKey){
_28.setDate(Math.min(_28.getDate(),_23(_28.getMonth()+1,_28.getFullYear())));
d=_28.setMonth(_28.getMonth()+1);
}else{
d+=86400000;
}
}else{
if(kc==Event.KEY_UP){
if(ev.ctrlKey||ev.shiftKey){
_28.setDate(Math.min(_28.getDate(),_23(_28.getMonth(),_28.getFullYear()-1)));
d=_28.setFullYear(_28.getFullYear()-1);
}else{
d-=604800000;
}
}else{
if(kc==Event.KEY_DOWN){
if(ev.ctrlKey||ev.shiftKey){
_28.setDate(Math.min(_28.getDate(),_23(_28.getMonth(),_28.getFullYear()+1)));
d=_28.setFullYear(_28.getFullYear()+1);
}else{
d+=7*24*61*60*1000;
}
}
}
}
}
this.setSelectedDate(d);
Event.stop(ev);
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
var d=this.newDate(this.selectedDate);
var n=Number(el.firstChild.data);
if(isNaN(n)||n<=0||n==null){
return;
}
d.setDate(n);
this.setSelectedDate(d);
this.hide();
},selectToday:function(){
if(this.selectedDate.toISODate()==this.newDate().toISODate()){
this.hide();
}
this.setSelectedDate(this.newDate());
},clearSelection:function(){
this.setSelectedDate(this.newDate());
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
var d=this.newDate(this.selectedDate);
var m=d.getMonth()+n;
this.setMonth(m);
return false;
},onchange:function(){
this.control.value=this.formatDate();
},formatDate:function(){
return this.selectedDate?this.selectedDate.SimpleFormat(this.Format):"";
},newDate:function(_34){
if(!_34){
_34=new Date();
}
if(isString(_34)||isNumber(_34)){
_34=new Date(_34);
}
return new Date(_34.getFullYear(),_34.getMonth(),_34.getDate(),0,0,0);
},setSelectedDate:function(_35){
if(_35==null){
return;
}
this.selectedDate=this.newDate(_35);
this.updateHeader();
this.update();
if(isFunction(this.onchange)){
this.onchange();
}
},getElement:function(){
return this._calDiv;
},getSelectedDate:function(){
return isNull(this.selectedDate)?null:this.newDate(this.selectedDate);
},setYear:function(_36){
var d=this.newDate(this.selectedDate);
d.setFullYear(_36);
this.setSelectedDate(d);
},setMonth:function(_37){
var d=this.newDate(this.selectedDate);
d.setMonth(_37);
this.setSelectedDate(d);
},nextMonth:function(){
this.setMonth(this.selectedDate.getMonth()+1);
},prevMonth:function(){
this.setMonth(this.selectedDate.getMonth()-1);
},show:function(){
if(!this.showing){
var pos=Position.cumulativeOffset(this.control);
pos[1]+=this.control.offsetHeight;
this._calDiv.style.display="block";
this._calDiv.style.top=(pos[1]-1)+"px";
this._calDiv.style.left=pos[0]+"px";
this.ieHack(false);
this.documentClickEvent=this.hideOnClick.bindEvent(this);
this.documentKeyDownEvent=this.keyPressed.bindEvent(this);
Event.observe(document.body,"click",this.documentClickEvent);
var _39=Date.SimpleParse(Form.Element.getValue(this.control),this.Format);
if(!isNull(_39)){
this.selectedDate=_39;
this.setSelectedDate(_39);
}
Event.observe(document,"keydown",this.documentKeyDownEvent);
this.showing=true;
}
},hideOnClick:function(ev){
if(!this.showing){
return;
}
var el=Event.element(ev);
var _40=false;
do{
_40=_40||el.className==this.ClassName;
_40=_40||el==this.trigger;
_40=_40||el==this.control;
if(_40){
break;
}
el=el.parentNode;
}while(el);
if(!_40){
this.hide();
}
},hide:function(){
if(this.showing){
this._calDiv.style.display="none";
if(this.iePopUp){
this.iePopUp.style.display="none";
}
this.showing=false;
Event.stopObserving(document.body,"click",this.documentClickEvent);
Event.stopObserving(document,"keydown",this.documentKeyDownEvent);
}
},update:function(){
var _41=this.selectedDate;
var _42=(this.newDate()).toISODate();
var _43=_41.toISODate();
var d1=new Date(_41.getFullYear(),_41.getMonth(),1);
var d2=new Date(_41.getFullYear(),_41.getMonth()+1,1);
var _46=Math.round((d2-d1)/(24*60*60*1000));
var _47=(d1.getDay()-this.FirstDayOfWeek)%7;
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
if(d1.toISODate()==_42){
_50.className+=" today";
}
if(d1.toISODate()==_43){
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
var m=this.selectedDate.getMonth();
for(var i=0;i<_52.length;++i){
_52[i].selected=false;
if(_52[i].value==m){
_52[i].selected=true;
}
}
_52=this._yearSelect.options;
var _53=this.selectedDate.getFullYear();
for(var i=0;i<_52.length;++i){
_52[i].selected=false;
if(_52[i].value==_53){
_52[i].selected=true;
}
}
}};

