Prado.WebUI.TDatePicker = Class.create();
Prado.WebUI.TDatePicker.prototype = 
{
MonthNames : ["January","February","March","April",
"May","June","July","August",
"September","October","November","December"
],
ShortWeekDayNames : ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ],
Format : "yyyy-MM-dd",
FirstDayOfWeek : 1,
ClassName : "TDatePicker",
FromYear : 2000, UpToYear: 2015,
initialize : function(options)
{
this.options = options || [];
this.control = $(options.ID);
this.dateSlot = new Array(42);
this.weekSlot = new Array(6);
this.minimalDaysInFirstWeek= 4;
this.selectedDate = this.newDate();
if(this.options.Trigger)
{
this.trigger = $(this.options.Trigger) ;
var triggerEvent = this.options.TriggerEvent || "click";
}
else
{
this.trigger= this.control;
var triggerEvent = this.options.TriggerEvent || "focus";
}
Object.extend(this,options);
Event.observe(this.trigger, triggerEvent, this.show.bindEvent(this));
this.create();
},
create : function()
{
var div;
var table;
var tbody;
var tr;
var td;
this._calDiv = document.createElement("div");
this._calDiv.className = this.ClassName;
this._calDiv.style.display = "none";
this._calDiv.style.position = "absolute"
div = document.createElement("div");
div.className = "calendarHeader";
this._calDiv.appendChild(div);
table = document.createElement("table");
table.style.cellSpacing = 0;
div.appendChild(table);
tbody = document.createElement("tbody");
table.appendChild(tbody);
tr = document.createElement("tr");
tbody.appendChild(tr);
td = document.createElement("td");
var previousMonth = document.createElement("button");
previousMonth.className = "prevMonthButton";
previousMonth.appendChild(document.createTextNode("<<"));
td.appendChild(previousMonth);
tr.appendChild(td);
td = document.createElement("td");
tr.appendChild(td);
this._monthSelect = document.createElement("select");
this._monthSelect.className = "months";
for (var i = 0 ; i < this.MonthNames.length ; i++) {
var opt = document.createElement("option");
opt.innerHTML = this.MonthNames[i];
opt.value = i;
if (i == this.selectedDate.getMonth()) {
opt.selected = true;
}
this._monthSelect.appendChild(opt);
}
td.appendChild(this._monthSelect);
td = document.createElement("td");
td.className = "labelContainer";
tr.appendChild(td);
this._yearSelect = document.createElement("select");
for(var i=this.FromYear; i <= this.UpToYear; ++i) {
var opt = document.createElement("option");
opt.innerHTML = i;
opt.value = i;
if (i == this.selectedDate.getFullYear()) {
opt.selected = false;
}
this._yearSelect.appendChild(opt);
}
td.appendChild(this._yearSelect);
td = document.createElement("td");
td.className = "nextMonthButton";
var nextMonth = document.createElement("button");
nextMonth.appendChild(document.createTextNode(">>"));
td.appendChild(nextMonth);
tr.appendChild(td);
div = document.createElement("div");
div.className = "calendarBody";
this._calDiv.appendChild(div);
var calendarBody = div;
var text;
table = document.createElement("table");
table.className = "grid";
div.appendChild(table);
var thead = document.createElement("thead");
table.appendChild(thead);
tr = document.createElement("tr");
thead.appendChild(tr);
for(i=0; i < 7; ++i) {
td = document.createElement("th");
text = document.createTextNode(this.ShortWeekDayNames[(i+this.FirstDayOfWeek)%7]);
td.appendChild(text);
td.className = "weekDayHead";
tr.appendChild(td);
}
tbody = document.createElement("tbody");
table.appendChild(tbody);
for(week=0; week<6; ++week) {
tr = document.createElement("tr");
tbody.appendChild(tr);
for(day=0; day<7; ++day) {
td = document.createElement("td");
td.className = "calendarDate";
text = document.createTextNode(String.fromCharCode(160));
td.appendChild(text);
tr.appendChild(td);
var tmp = new Object();
tmp.tag = "DATE";
tmp.value = -1;
tmp.data = text;
this.dateSlot[(week*7)+day] = tmp;
Event.observe(td, "mouseover", this.hover.bindEvent(this));
Event.observe(td, "mouseout", this.hover.bindEvent(this));
}
}
div = document.createElement("div");
div.className = "calendarFooter";
this._calDiv.appendChild(div);
var todayButton = document.createElement("button");
todayButton.className = "todayButton";
var today = this.newDate();
var buttonText = today.SimpleFormat(this.Format);
todayButton.appendChild(document.createTextNode(buttonText));
div.appendChild(todayButton);
if(Prado.Browser().ie)
{
this.iePopUp = document.createElement('iframe');
this.iePopUp.src = "";
this.iePopUp.style.position = "absolute"
this.iePopUp.scrolling="no"
this.iePopUp.frameBorder="0"
document.body.appendChild(this.iePopUp);
}
document.body.appendChild(this._calDiv);
this.update();
this.updateHeader();
this.ieHack(true);
previousMonth.hideFocus = true;
nextMonth.hideFocus = true;
todayButton.hideFocus = true;
Event.observe(previousMonth, "click", this.prevMonth.bindEvent(this));
Event.observe(nextMonth, "click", this.nextMonth.bindEvent(this));
Event.observe(todayButton, "click", this.selectToday.bindEvent(this));
Event.observe(this._monthSelect, "change", this.monthSelect.bindEvent(this));
Event.observe(this._yearSelect, "change", this.yearSelect.bindEvent(this));
Event.observe(this._calDiv, "mousewheel", this.mouseWheelChange.bindEvent(this));
Event.observe(calendarBody, "click", this.selectDate.bindEvent(this));
},
ieHack : function(cleanup) 
{
if(this.iePopUp) 
{
this.iePopUp.style.display = "block";
this.iePopUp.style.top = (this._calDiv.offsetTop -1 ) + "px";
this.iePopUp.style.left = (this._calDiv.offsetLeft -1)+ "px";
this.iePopUp.style.width = Math.abs(this._calDiv.offsetWidth -2)+ "px";
this.iePopUp.style.height = (this._calDiv.offsetHeight + 1)+ "px";
if(cleanup) this.iePopUp.style.display = "none";
}
},
keyPressed : function(ev)
{
if(!this.showing) return;
if (!ev) ev = document.parentWindow.event;
var kc = ev.keyCode != null ? ev.keyCode : ev.charCode;
if(kc == Event.KEY_RETURN)
{
this.setSelectedDate(this.selectedDate);
Event.stop(ev);
this.hide();
}
if(kc == Event.KEY_ESC)
{
Event.stop(ev); this.hide();
}
var getDaysPerMonth = function (nMonth, nYear) 
{
nMonth = (nMonth + 12) % 12;
var days= [31,28,31,30,31,30,31,31,30,31,30,31];
var res = days[nMonth];
if (nMonth == 1)
res += nYear % 4 == 0 && !(nYear % 400 == 0) ? 1 : 0;
return res;
}
if(kc < 37 || kc > 40) return true;
var current = this.selectedDate;
var d = current.valueOf();
if(kc == Event.KEY_LEFT)
{
if(ev.ctrlKey || ev.shiftKey)
{
current.setDate( Math.min(current.getDate(), getDaysPerMonth(current.getMonth() - 1,current.getFullYear())) );
d = current.setMonth( current.getMonth() - 1 );
}
else
d -= 86400000;
}
else if (kc == Event.KEY_RIGHT)
{
if(ev.ctrlKey || ev.shiftKey)
{
current.setDate( Math.min(current.getDate(), getDaysPerMonth(current.getMonth() + 1,current.getFullYear())) );
d = current.setMonth( current.getMonth() + 1 );
}
else
d += 86400000;
}
else if (kc == Event.KEY_UP)
{
if(ev.ctrlKey || ev.shiftKey)
{
current.setDate( Math.min(current.getDate(), getDaysPerMonth(current.getMonth(),current.getFullYear() - 1)) );
d = current.setFullYear( current.getFullYear() - 1 );
}
else
d -= 604800000;
}
else if (kc == Event.KEY_DOWN) 
{
if(ev.ctrlKey || ev.shiftKey)
{
current.setDate( Math.min(current.getDate(), getDaysPerMonth(current.getMonth(),current.getFullYear() + 1)) );
d = current.setFullYear( current.getFullYear() + 1 );
}
else 
d += 7 * 24 * 61 * 60 * 1000;
}
this.setSelectedDate(d);
Event.stop(ev);
},
selectDate : function(ev)
{
var el = Event.element(ev);
while (el.nodeType != 1)
el = el.parentNode;
while (el != null && el.tagName && el.tagName.toLowerCase() != "td")
el = el.parentNode;
if (el == null || el.tagName == null || el.tagName.toLowerCase() != "td")
return;
var d = this.newDate(this.selectedDate);
var n = Number(el.firstChild.data);
if (isNaN(n) || n <= 0 || n == null)
return;
d.setDate(n);
this.setSelectedDate(d);
this.hide();
},
selectToday : function()
{
if(this.selectedDate.toISODate() == this.newDate().toISODate())
this.hide();
this.setSelectedDate(this.newDate());
},
clearSelection : function()
{
this.setSelectedDate(this.newDate());
this.hide();
},
monthSelect : function(ev)
{
this.setMonth(Form.Element.getValue(Event.element(ev)));
},
yearSelect : function(ev)
{
this.setYear(Form.Element.getValue(Event.element(ev)));
},
mouseWheelChange : function (e) 
{
if (e == null) e = document.parentWindow.event;
var n = - e.wheelDelta / 120;
var d = this.newDate(this.selectedDate);
var m = d.getMonth() + n;
this.setMonth(m);
return false;
},
onchange : function() 
{
if(this.options.InputMode == "TextBox")
this.control.value = this.formatDate();
else
{
var day = $(this.options.ID+"_day");
var month = $(this.options.ID+"_month");
var year = $(this.options.ID+"_year");
var date = this.selectedDate;
if(day)
day.selectedIndex = date.getDate()-1;
if(month)
month.selectedIndex = date.getMonth();
if(year)
{
var years = year.options;
var currentYear = date.getFullYear();
for(var i = 0; i < years.length; i++)
years[i].selected = years[i].value.toInteger() == currentYear;
}
}
},
formatDate : function()
{
return this.selectedDate ? this.selectedDate.SimpleFormat(this.Format) : '';
},
newDate : function(date)
{
if(!date)
date = new Date();
if(isString(date)|| isNumber(date))
date = new Date(date);
return new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0,0,0);
},
setSelectedDate : function(date) 
{
if (date == null)
return;
this.selectedDate = this.newDate(date);
this.updateHeader();
this.update();
if (isFunction(this.onchange))
this.onchange();
},
getElement : function() 
{
return this._calDiv;
},
getSelectedDate : function () 
{
return isNull(this.selectedDate) ? null : this.newDate(this.selectedDate);
},
setYear : function(year) 
{
var d = this.newDate(this.selectedDate);
d.setFullYear(year);
this.setSelectedDate(d);
},
setMonth : function (month) 
{
var d = this.newDate(this.selectedDate);
d.setMonth(month);
this.setSelectedDate(d);
},
nextMonth : function () 
{
this.setMonth(this.selectedDate.getMonth()+1);
},
prevMonth : function () 
{
this.setMonth(this.selectedDate.getMonth()-1);
},
show : function() 
{
if(!this.showing)
{
var pos = Position.cumulativeOffset(this.control);
if(this.options.InputMode == "TextBox")
pos[1] += this.control.offsetHeight;
else
{
if($(this.options.ID+"_day"))
pos[1] += $(this.options.ID+"_day").offsetHeight-1;
}
this._calDiv.style.display = "block";
this._calDiv.style.top = (pos[1]-1) + "px";
this._calDiv.style.left = pos[0] + "px";
this.ieHack(false);
this.documentClickEvent = this.hideOnClick.bindEvent(this);
this.documentKeyDownEvent = this.keyPressed.bindEvent(this);
Event.observe(document.body, "click", this.documentClickEvent);
var date = this.getDateFromInput();
if(!isNull(date))
{
this.selectedDate = date;
this.setSelectedDate(date);
}
Event.observe(document,"keydown", this.documentKeyDownEvent); 
this.showing = true;
}
},
getDateFromInput : function()
{
if(this.options.InputMode == "TextBox")
return Date.SimpleParse($F(this.control), this.Format);
else
{
var now=new Date();
var year=now.getFullYear();
var month=now.getMonth();
var date=1;
if($(this.options.ID+"_day"))
day = $F(this.options.ID+"_day");
if($(this.options.ID+"_month"))
month = $F(this.options.ID+"_month");
if($(this.options.ID+"_year"))
year = $F(this.options.ID+"_year");
var newdate=new Date(year,month,day, 0, 0, 0);
return newdate;
}
},
hideOnClick : function(ev)
{
if(!this.showing) return;
var el = Event.element(ev);
var within = false;
do
{
within = within || el.className == this.ClassName;
within = within || el == this.trigger;
within = within || el == this.control;
if(within) break;
el = el.parentNode;
}
while(el);
if(!within) this.hide();
},
hide : function()
{
if(this.showing)
{
this._calDiv.style.display = "none";
if(this.iePopUp)
this.iePopUp.style.display = "none";
this.showing = false;
Event.stopObserving(document.body, "click", this.documentClickEvent);
Event.stopObserving(document,"keydown", this.documentKeyDownEvent); 
}
},
update : function() 
{
var date = this.selectedDate;
var today = (this.newDate()).toISODate();
var selected = date.toISODate();
var d1 = new Date(date.getFullYear(), date.getMonth(), 1);
var d2 = new Date(date.getFullYear(), date.getMonth()+1, 1);
var monthLength = Math.round((d2 - d1) / (24 * 60 * 60 * 1000));
var firstIndex = (d1.getDay() - this.FirstDayOfWeek) % 7 ;
if (firstIndex < 0)
firstIndex += 7;
var index = 0;
while (index < firstIndex) {
this.dateSlot[index].value = -1;
this.dateSlot[index].data.data = String.fromCharCode(160);
this.dateSlot[index].data.parentNode.className = "empty";
index++;
}
for (i = 1; i <= monthLength; i++, index++) {
var slot = this.dateSlot[index];
var slotNode = slot.data.parentNode;
slot.value = i;
slot.data.data = i;
slotNode.className = "date";
if (d1.toISODate() == today) {
slotNode.className += " today";
}
if (d1.toISODate() == selected) {
slotNode.className += " selected";
}
d1 = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate()+1);
}
var lastDateIndex = index;
while(index < 42) {
this.dateSlot[index].value = -1;
this.dateSlot[index].data.data = String.fromCharCode(160);
this.dateSlot[index].data.parentNode.className = "empty";
++index;
}
},
hover : function(ev)
{
Element.condClassName(Event.element(ev), "hover", ev.type=="mouseover");
},
updateHeader : function () {
var options = this._monthSelect.options;
var m = this.selectedDate.getMonth();
for(var i=0; i < options.length; ++i) {
options[i].selected = false;
if (options[i].value == m) {
options[i].selected = true;
}
}
options = this._yearSelect.options;
var year = this.selectedDate.getFullYear();
for(var i=0; i < options.length; ++i) {
options[i].selected = false;
if (options[i].value == year) {
options[i].selected = true;
}
}
}
};
