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
var _19=_18.getDate()+" "+this.MonthNames[_18.getMonth()]+", "+_18.getFullYear();
_17.appendChild(document.createTextNode(_19));
_4.appendChild(_17);
var _20=document.createElement("button");
_20.className="clearButton";
_19="Clear";
_20.appendChild(document.createTextNode(_19));
_4.appendChild(_20);
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
Event.observe(_20,"click",this.clearSelection.bindEvent(this));
Event.observe(this._monthSelect,"change",this.monthSelect.bindEvent(this));
Event.observe(this._yearSelect,"change",this.yearSelect.bindEvent(this));
Event.observe(this._calDiv,"mousewheel",this.mouseWheelChange.bindEvent(this));
Event.observe(_13,"click",this.selectDate.bindEvent(this));
},ieHack:function(_21){
if(this.iePopUp){
this.iePopUp.style.display="block";
this.iePopUp.style.top=(this._calDiv.offsetTop-1)+"px";
this.iePopUp.style.left=(this._calDiv.offsetLeft-1)+"px";
this.iePopUp.style.width=Math.abs(this._calDiv.offsetWidth-2)+"px";
this.iePopUp.style.height=(this._calDiv.offsetHeight+1)+"px";
if(_21){
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
Event.stop(ev);
this.hide();
}
var _24=function(_25,_26){
_25=(_25+12)%12;
var _27=[31,28,31,30,31,30,31,31,30,31,30,31];
var res=_27[_25];
if(_25==1){
res+=_26%4==0&&!(_26%400==0)?1:0;
}
return res;
};
if(kc<37||kc>40){
return true;
}
var _29=this.selectedDate;
var d=_29.valueOf();
if(kc==Event.KEY_LEFT){
if(ev.ctrlKey||ev.shiftKey){
_29.setDate(Math.min(_29.getDate(),_24(_29.getMonth()-1,_29.getFullYear())));
d=_29.setMonth(_29.getMonth()-1);
}else{
d-=86400000;
}
}else{
if(kc==Event.KEY_RIGHT){
if(ev.ctrlKey||ev.shiftKey){
_29.setDate(Math.min(_29.getDate(),_24(_29.getMonth()+1,_29.getFullYear())));
d=_29.setMonth(_29.getMonth()+1);
}else{
d+=86400000;
}
}else{
if(kc==Event.KEY_UP){
if(ev.ctrlKey||ev.shiftKey){
_29.setDate(Math.min(_29.getDate(),_24(_29.getMonth(),_29.getFullYear()-1)));
d=_29.setFullYear(_29.getFullYear()-1);
}else{
d-=604800000;
}
}else{
if(kc==Event.KEY_DOWN){
if(ev.ctrlKey||ev.shiftKey){
_29.setDate(Math.min(_29.getDate(),_24(_29.getMonth(),_29.getFullYear()+1)));
d=_29.setFullYear(_29.getFullYear()+1);
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
this.setSelectedDate(this.newDate());
this.hide();
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
},newDate:function(_35){
if(!_35){
_35=new Date();
}
if(isString(_35)||isNumber(_35)){
_35=new Date(_35);
}
return new Date(_35.getFullYear(),_35.getMonth(),_35.getDate(),0,0,0);
},setSelectedDate:function(_36){
if(_36==null){
return;
}
this.selectedDate=this.newDate(_36);
this.updateHeader();
this.update();
if(isFunction(this.onchange)){
this.onchange();
}
},getElement:function(){
return this._calDiv;
},getSelectedDate:function(){
return isNull(this.selectedDate)?null:this.newDate(this.selectedDate);
},setYear:function(_37){
var d=this.newDate(this.selectedDate);
d.setFullYear(_37);
this.setSelectedDate(d);
},setMonth:function(_38){
var d=this.newDate(this.selectedDate);
d.setMonth(_38);
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
var _40=Date.SimpleParse(Form.Element.getValue(this.control),this.Format);
if(!isNull(_40)){
this.selectedDate=_40;
this.setSelectedDate(_40);
}
Event.observe(document,"keydown",this.documentKeyDownEvent);
this.showing=true;
}
},hideOnClick:function(ev){
if(!this.showing){
return;
}
var el=Event.element(ev);
var _41=false;
do{
_41=_41||el.className==this.ClassName;
_41=_41||el==this.trigger;
_41=_41||el==this.control;
if(_41){
break;
}
el=el.parentNode;
}while(el);
if(!_41){
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
var _42=this.selectedDate;
var _43=(this.newDate()).toISODate();
var _44=_42.toISODate();
var d1=new Date(_42.getFullYear(),_42.getMonth(),1);
var d2=new Date(_42.getFullYear(),_42.getMonth()+1,1);
var _47=Math.round((d2-d1)/(24*60*60*1000));
var _48=(d1.getDay()-this.FirstDayOfWeek)%7;
if(_48<0){
_48+=7;
}
var _49=0;
while(_49<_48){
this.dateSlot[_49].value=-1;
this.dateSlot[_49].data.data=String.fromCharCode(160);
this.dateSlot[_49].data.parentNode.className="empty";
_49++;
}
for(i=1;i<=_47;i++,_49++){
var _50=this.dateSlot[_49];
var _51=_50.data.parentNode;
_50.value=i;
_50.data.data=i;
_51.className="date";
if(d1.toISODate()==_43){
_51.className+=" today";
}
if(d1.toISODate()==_44){
_51.className+=" selected";
}
d1=new Date(d1.getFullYear(),d1.getMonth(),d1.getDate()+1);
}
var _52=_49;
while(_49<42){
this.dateSlot[_49].value=-1;
this.dateSlot[_49].data.data=String.fromCharCode(160);
this.dateSlot[_49].data.parentNode.className="empty";
++_49;
}
},hover:function(ev){
Element.condClassName(Event.element(ev),"hover",ev.type=="mouseover");
},updateHeader:function(){
var _53=this._monthSelect.options;
var m=this.selectedDate.getMonth();
for(var i=0;i<_53.length;++i){
_53[i].selected=false;
if(_53[i].value==m){
_53[i].selected=true;
}
}
_53=this._yearSelect.options;
var _54=this.selectedDate.getFullYear();
for(var i=0;i<_53.length;++i){
_53[i].selected=false;
if(_53[i].value==_54){
_53[i].selected=true;
}
}
}};

