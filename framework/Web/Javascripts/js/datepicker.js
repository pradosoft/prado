Prado.WebUI.TDatePicker=Class.create();
Prado.WebUI.TDatePicker.prototype={MonthNames:["January","February","March","April","May","June","July","August","September","October","November","December"],ShortWeekDayNames:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],Format:"yyyy-MM-dd",FirstDayOfWeek:1,ClassName:"TDatePicker",FromYear:2000,UpToYear:2015,initialize:function(_1){
this.options=_1||[];
this.control=$(_1.ID);
this.dateSlot=new Array(42);
this.weekSlot=new Array(6);
this.minimalDaysInFirstWeek=4;
this.selectedDate=this.newDate();
if(this.options.Trigger){
this.trigger=$(this.options.Trigger);
var _2=this.options.TriggerEvent||"click";
}else{
this.trigger=this.control;
var _2=this.options.TriggerEvent||"focus";
}
Object.extend(this,_1);
Event.observe(this.trigger,_2,this.show.bindEvent(this));
this.create();
},create:function(){
var _3;
var _4;
var _5;
var tr;
var td;
this._calDiv=document.createElement("div");
this._calDiv.className=this.ClassName;
this._calDiv.style.display="none";
this._calDiv.style.position="absolute";
_3=document.createElement("div");
_3.className="calendarHeader";
this._calDiv.appendChild(_3);
_4=document.createElement("table");
_4.style.cellSpacing=0;
_3.appendChild(_4);
_5=document.createElement("tbody");
_4.appendChild(_5);
tr=document.createElement("tr");
_5.appendChild(tr);
td=document.createElement("td");
var _8=document.createElement("button");
_8.className="prevMonthButton";
_8.appendChild(document.createTextNode("<<"));
td.appendChild(_8);
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
var _11=document.createElement("button");
_11.appendChild(document.createTextNode(">>"));
td.appendChild(_11);
tr.appendChild(td);
_3=document.createElement("div");
_3.className="calendarBody";
this._calDiv.appendChild(_3);
var _12=_3;
var _13;
_4=document.createElement("table");
_4.className="grid";
_3.appendChild(_4);
var _14=document.createElement("thead");
_4.appendChild(_14);
tr=document.createElement("tr");
_14.appendChild(tr);
for(i=0;i<7;++i){
td=document.createElement("th");
_13=document.createTextNode(this.ShortWeekDayNames[(i+this.FirstDayOfWeek)%7]);
td.appendChild(_13);
td.className="weekDayHead";
tr.appendChild(td);
}
_5=document.createElement("tbody");
_4.appendChild(_5);
for(week=0;week<6;++week){
tr=document.createElement("tr");
_5.appendChild(tr);
for(day=0;day<7;++day){
td=document.createElement("td");
td.className="calendarDate";
_13=document.createTextNode(String.fromCharCode(160));
td.appendChild(_13);
tr.appendChild(td);
var tmp=new Object();
tmp.tag="DATE";
tmp.value=-1;
tmp.data=_13;
this.dateSlot[(week*7)+day]=tmp;
Event.observe(td,"mouseover",this.hover.bindEvent(this));
Event.observe(td,"mouseout",this.hover.bindEvent(this));
}
}
_3=document.createElement("div");
_3.className="calendarFooter";
this._calDiv.appendChild(_3);
var _16=document.createElement("button");
_16.className="todayButton";
var _17=this.newDate();
var _18=_17.SimpleFormat(this.Format);
_16.appendChild(document.createTextNode(_18));
_3.appendChild(_16);
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
_8.hideFocus=true;
_11.hideFocus=true;
_16.hideFocus=true;
Event.observe(_8,"click",this.prevMonth.bindEvent(this));
Event.observe(_11,"click",this.nextMonth.bindEvent(this));
Event.observe(_16,"click",this.selectToday.bindEvent(this));
Event.observe(this._monthSelect,"change",this.monthSelect.bindEvent(this));
Event.observe(this._yearSelect,"change",this.yearSelect.bindEvent(this));
Event.observe(this._calDiv,"mousewheel",this.mouseWheelChange.bindEvent(this));
Event.observe(_12,"click",this.selectDate.bindEvent(this));
},ieHack:function(_19){
if(this.iePopUp){
this.iePopUp.style.display="block";
this.iePopUp.style.top=(this._calDiv.offsetTop-1)+"px";
this.iePopUp.style.left=(this._calDiv.offsetLeft-1)+"px";
this.iePopUp.style.width=Math.abs(this._calDiv.offsetWidth-2)+"px";
this.iePopUp.style.height=(this._calDiv.offsetHeight+1)+"px";
if(_19){
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
var _22=function(_23,_24){
_23=(_23+12)%12;
var _25=[31,28,31,30,31,30,31,31,30,31,30,31];
var res=_25[_23];
if(_23==1){
res+=_24%4==0&&!(_24%400==0)?1:0;
}
return res;
};
if(kc<37||kc>40){
return true;
}
var _27=this.selectedDate;
var d=_27.valueOf();
if(kc==Event.KEY_LEFT){
if(ev.ctrlKey||ev.shiftKey){
_27.setDate(Math.min(_27.getDate(),_22(_27.getMonth()-1,_27.getFullYear())));
d=_27.setMonth(_27.getMonth()-1);
}else{
d-=86400000;
}
}else{
if(kc==Event.KEY_RIGHT){
if(ev.ctrlKey||ev.shiftKey){
_27.setDate(Math.min(_27.getDate(),_22(_27.getMonth()+1,_27.getFullYear())));
d=_27.setMonth(_27.getMonth()+1);
}else{
d+=86400000;
}
}else{
if(kc==Event.KEY_UP){
if(ev.ctrlKey||ev.shiftKey){
_27.setDate(Math.min(_27.getDate(),_22(_27.getMonth(),_27.getFullYear()-1)));
d=_27.setFullYear(_27.getFullYear()-1);
}else{
d-=604800000;
}
}else{
if(kc==Event.KEY_DOWN){
if(ev.ctrlKey||ev.shiftKey){
_27.setDate(Math.min(_27.getDate(),_22(_27.getMonth(),_27.getFullYear()+1)));
d=_27.setFullYear(_27.getFullYear()+1);
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
if(this.options.InputMode=="TextBox"){
this.control.value=this.formatDate();
}else{
var day=$(this.options.ID+"_day");
var _34=$(this.options.ID+"_month");
var _35=$(this.options.ID+"_year");
var _36=this.selectedDate;
if(day){
day.selectedIndex=_36.getDate()-1;
}
if(_34){
_34.selectedIndex=_36.getMonth();
}
if(_35){
var _37=_35.options;
var _38=_36.getFullYear();
for(var i=0;i<_37.length;i++){
_37[i].selected=_37[i].value.toInteger()==_38;
}
}
}
},formatDate:function(){
return this.selectedDate?this.selectedDate.SimpleFormat(this.Format):"";
},newDate:function(_39){
if(!_39){
_39=new Date();
}
if(isString(_39)||isNumber(_39)){
_39=new Date(_39);
}
return new Date(_39.getFullYear(),_39.getMonth(),_39.getDate(),0,0,0);
},setSelectedDate:function(_40){
if(_40==null){
return;
}
this.selectedDate=this.newDate(_40);
this.updateHeader();
this.update();
if(isFunction(this.onchange)){
this.onchange();
}
},getElement:function(){
return this._calDiv;
},getSelectedDate:function(){
return isNull(this.selectedDate)?null:this.newDate(this.selectedDate);
},setYear:function(_41){
var d=this.newDate(this.selectedDate);
d.setFullYear(_41);
this.setSelectedDate(d);
},setMonth:function(_42){
var d=this.newDate(this.selectedDate);
d.setMonth(_42);
this.setSelectedDate(d);
},nextMonth:function(){
this.setMonth(this.selectedDate.getMonth()+1);
},prevMonth:function(){
this.setMonth(this.selectedDate.getMonth()-1);
},show:function(){
if(!this.showing){
var pos=Position.cumulativeOffset(this.control);
if(this.options.InputMode=="TextBox"){
pos[1]+=this.control.offsetHeight;
}else{
if($(this.options.ID+"_day")){
pos[1]+=$(this.options.ID+"_day").offsetHeight-1;
}
}
this._calDiv.style.display="block";
this._calDiv.style.top=(pos[1]-1)+"px";
this._calDiv.style.left=pos[0]+"px";
this.ieHack(false);
this.documentClickEvent=this.hideOnClick.bindEvent(this);
this.documentKeyDownEvent=this.keyPressed.bindEvent(this);
Event.observe(document.body,"click",this.documentClickEvent);
var _44=this.getDateFromInput();
if(!isNull(_44)){
this.selectedDate=_44;
this.setSelectedDate(_44);
}
Event.observe(document,"keydown",this.documentKeyDownEvent);
this.showing=true;
}
},getDateFromInput:function(){
if(this.options.InputMode=="TextBox"){
return Date.SimpleParse($F(this.control),this.Format);
}else{
var now=new Date();
var _46=now.getFullYear();
var _47=now.getMonth();
var _48=1;
if($(this.options.ID+"_day")){
day=$F(this.options.ID+"_day");
}
if($(this.options.ID+"_month")){
_47=$F(this.options.ID+"_month");
}
if($(this.options.ID+"_year")){
_46=$F(this.options.ID+"_year");
}
var _49=new Date(_46,_47,day,0,0,0);
return _49;
}
},hideOnClick:function(ev){
if(!this.showing){
return;
}
var el=Event.element(ev);
var _50=false;
do{
_50=_50||el.className==this.ClassName;
_50=_50||el==this.trigger;
_50=_50||el==this.control;
if(_50){
break;
}
el=el.parentNode;
}while(el);
if(!_50){
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
var _51=this.selectedDate;
var _52=(this.newDate()).toISODate();
var _53=_51.toISODate();
var d1=new Date(_51.getFullYear(),_51.getMonth(),1);
var d2=new Date(_51.getFullYear(),_51.getMonth()+1,1);
var _56=Math.round((d2-d1)/(24*60*60*1000));
var _57=(d1.getDay()-this.FirstDayOfWeek)%7;
if(_57<0){
_57+=7;
}
var _58=0;
while(_58<_57){
this.dateSlot[_58].value=-1;
this.dateSlot[_58].data.data=String.fromCharCode(160);
this.dateSlot[_58].data.parentNode.className="empty";
_58++;
}
for(i=1;i<=_56;i++,_58++){
var _59=this.dateSlot[_58];
var _60=_59.data.parentNode;
_59.value=i;
_59.data.data=i;
_60.className="date";
if(d1.toISODate()==_52){
_60.className+=" today";
}
if(d1.toISODate()==_53){
_60.className+=" selected";
}
d1=new Date(d1.getFullYear(),d1.getMonth(),d1.getDate()+1);
}
var _61=_58;
while(_58<42){
this.dateSlot[_58].value=-1;
this.dateSlot[_58].data.data=String.fromCharCode(160);
this.dateSlot[_58].data.parentNode.className="empty";
++_58;
}
},hover:function(ev){
Element.condClassName(Event.element(ev),"hover",ev.type=="mouseover");
},updateHeader:function(){
var _62=this._monthSelect.options;
var m=this.selectedDate.getMonth();
for(var i=0;i<_62.length;++i){
_62[i].selected=false;
if(_62[i].value==m){
_62[i].selected=true;
}
}
_62=this._yearSelect.options;
var _63=this.selectedDate.getFullYear();
for(var i=0;i<_62.length;++i){
_62[i].selected=false;
if(_62[i].value==_63){
_62[i].selected=true;
}
}
}};

