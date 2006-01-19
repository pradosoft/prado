Prado.Calendar = Class.create();

Prado.Calendar.Util = Class.create();

Object.extend(Prado.Calendar.Util,
{	
	IsLeapYear : function (year) 
	{
		return ((year%4 == 0) && ((year%100 != 0) || (year%400 == 0)));
	},
	
	yearLength : function(year) 
	{
		if (this.isLeapYear(year))
			return 366;
		else
			return 365;
	},
	
	dayOfYear : function(date) 
	{
		var a = this.isLeapYear(date.getFullYear()) ? 
					Calendar.LEAP_NUM_DAYS : Calendar.NUM_DAYS;	
		return a[date.getMonth()] + date.getDate();
	},
	
	browser : function()
	{
		var info = { Version : "1.0" };
		var is_major = parseInt( navigator.appVersion );
		info.nver = is_major;
		info.ver = navigator.appVersion;
		info.agent = navigator.userAgent;
		info.dom = document.getElementById ? 1 : 0;
		info.opera = window.opera ? 1 : 0;
		info.ie5 = ( info.ver.indexOf( "MSIE 5" ) > -1 && info.dom && !info.opera ) ? 1 : 0;
		info.ie6 = ( info.ver.indexOf( "MSIE 6" ) > -1 && info.dom && !info.opera ) ? 1 : 0;
		info.ie4 = ( document.all && !info.dom && !info.opera ) ? 1 : 0;
		info.ie = info.ie4 || info.ie5 || info.ie6;
		info.mac = info.agent.indexOf( "Mac" ) > -1;
		info.ns6 = ( info.dom && parseInt( info.ver ) >= 5 ) ? 1 : 0;
		info.ie3 = ( info.ver.indexOf( "MSIE" ) && ( is_major < 4 ) );
		info.hotjava = ( info.agent.toLowerCase().indexOf( 'hotjava' ) != -1 ) ? 1 : 0;
		info.ns4 = ( document.layers && !info.dom && !info.hotjava ) ? 1 : 0;
		info.bw = ( info.ie6 || info.ie5 || info.ie4 || info.ns4 || info.ns6 || info.opera );
		info.ver3 = ( info.hotjava || info.ie3 );
		info.opera7 = ( ( info.agent.toLowerCase().indexOf( 'opera 7' ) > -1 ) || ( info.agent.toLowerCase().indexOf( 'opera/7' ) > -1 ) );
		info.operaOld = info.opera && !info.opera7;
		return info;
	},
	
	ImportCss : function(doc, css_file) 
	{
		if (this.browser().ie)
			var styleSheet = doc.createStyleSheet(css_file);
		else 
		{
			var elm = doc.createElement("link");

			elm.rel = "stylesheet";
			elm.href = css_file;

			if (headArr = doc.getElementsByTagName("head"))
				headArr[0].appendChild(elm);
		}
	}
});

Object.extend(Prado.Calendar,
{
	// Accumulated days per month, for normal and for leap years.
	// Used in week number calculations.	
	NUM_DAYS : [0,31,59,90,120,151,181,212,243,273,304,334],
	LEAP_NUM_DAYS : [0,31,60,91,121,152,182,213,244,274,305,335]
});

Prado.Calendar.prototype = 
{
	monthNames : [	"January",		"February",		"March",	"April",
		"May",			"June",			"July",		"August",
		"September",	"October",		"November",	"December"
	],

	shortWeekDayNames : ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ],

	format : "yyyy-MM-dd",
	
	css : "calendar_system.css",
	
	initialize : function(control, attr)
	{
		this.attr = attr || [];
		this.control = $(control);	
		this.dateSlot = new Array(42);
		this.weekSlot = new Array(6);
		this.firstDayOfWeek = 1;
		this.minimalDaysInFirstWeek	= 4;
		this.currentDate = new Date();
		this.selectedDate = null;
		this.className = "TDatePicker";
		
		//which element to trigger to show the calendar
		this.trigger = this.attr.trigger ? $(this.attr.trigger) : this.control;
		Event.observe(this.trigger, "click", this.show.bind(this));
		
		Prado.Calendar.Util.ImportCss(document, this.css);
		
		if(this.attr.format) this.format = this.attr.format;
		
		//create it
		this.create();	
		//alert("ok");
		this.hookEvents();	
	},
	
	create : function()
	{
		var div;
		var table;
		var tbody;
		var tr;
		var td;
	
		// Create the top-level div element
		this._calDiv = document.createElement("div");
		this._calDiv.className = this.className;
		this._calDiv.style.display = "none";		
		
		// header div
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
		
		// Previous Month Button
		td = document.createElement("td");
		td.className = "prevMonthButton";
		this._previousMonth = document.createElement("button");
		this._previousMonth.appendChild(document.createTextNode("<<"));
		td.appendChild(this._previousMonth);
		tr.appendChild(td);
		
		
		
		//
		// Create the month drop down 
		//
		td = document.createElement("td");
		td.className = "labelContainer";
		tr.appendChild(td);
		this._monthSelect = document.createElement("select");
	    for (var i = 0 ; i < this.monthNames.length ; i++) {
	        var opt = document.createElement("option");
	        opt.innerHTML = this.monthNames[i];
	        opt.value = i;
	        if (i == this.currentDate.getMonth()) {
	            opt.selected = true;
	        }
	        this._monthSelect.appendChild(opt);
	    }
		td.appendChild(this._monthSelect);
		

		// 
		// Create the year drop down
		//
		td = document.createElement("td");
		td.className = "labelContainer";
		tr.appendChild(td);
		this._yearSelect = document.createElement("select");
		for(var i=1920; i < 2050; ++i) {
			var opt = document.createElement("option");
			opt.innerHTML = i;
			opt.value = i;
			if (i == this.currentDate.getFullYear()) {
				opt.selected = false;
			}
			this._yearSelect.appendChild(opt);
		}
		td.appendChild(this._yearSelect);
		
		
		td = document.createElement("td");
		td.className = "nextMonthButton";
		this._nextMonth = document.createElement("button");
		this._nextMonth.appendChild(document.createTextNode(">>"));
		td.appendChild(this._nextMonth);
		tr.appendChild(td);
		
		// Calendar body
		div = document.createElement("div");
		div.className = "calendarBody";
		this._calDiv.appendChild(div);
		this._table = div;
		
		// Create the inside of calendar body	
		
		var text;
		table = document.createElement("table");
		//table.style.width="100%";
		table.className = "grid";
	
	    div.appendChild(table);
		var thead = document.createElement("thead");
		table.appendChild(thead);
		tr = document.createElement("tr");
		thead.appendChild(tr);
		
		for(i=0; i < 7; ++i) {
			td = document.createElement("th");
			text = document.createTextNode(this.shortWeekDayNames[(i+this.firstDayOfWeek)%7]);
			td.appendChild(text);
			td.className = "weekDayHead";
			tr.appendChild(td);
		}
		
		// Date grid
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
				
				Event.observe(td, "mouseover", this.hover.bind(this));
				Event.observe(td, "mouseout", this.hover.bind(this));
				
			}
		}
		
		// Calendar Footer
		div = document.createElement("div");
		div.className = "calendarFooter";
		this._calDiv.appendChild(div);
		
		table = document.createElement("table");
		//table.style.width="100%";
		table.className = "footerTable";
		//table.cellSpacing = 0;
		div.appendChild(table);
		
		tbody = document.createElement("tbody");
		table.appendChild(tbody);
		
		tr = document.createElement("tr");
		tbody.appendChild(tr);

		//
		// The TODAY button	
		//
		td = document.createElement("td");
		td.className = "todayButton";
		this._todayButton = document.createElement("button");
		var today = new Date();
		var buttonText = today.getDate() + " " + this.monthNames[today.getMonth()] + ", " + today.getFullYear();
		this._todayButton.appendChild(document.createTextNode(buttonText));
		td.appendChild(this._todayButton);
		tr.appendChild(td);
		
		//
		// The CLEAR button
		//
		td = document.createElement("td");
		td.className = "clearButton";
		this._clearButton = document.createElement("button");
		var today = new Date();
		buttonText = "Clear";
		this._clearButton.appendChild(document.createTextNode(buttonText));
		td.appendChild(this._clearButton);
		tr.appendChild(td);
		
		document.body.appendChild(this._calDiv);
		
		this.update();
		this.updateHeader();
		
		return this._calDiv;
	},
	
	hookEvents : function()
	{
		// IE55+ extension		
		this._previousMonth.hideFocus = true;
		this._nextMonth.hideFocus = true;
		this._todayButton.hideFocus = true;
		// end IE55+ extension
		
		// hook up events
		Event.observe(this._previousMonth, "click", this.prevMonth.bind(this));
		Event.observe(this._nextMonth, "click", this.nextMonth.bind(this));
		Event.observe(this._todayButton, "click", this.selectToday.bind(this));
		Event.observe(this._clearButton, "click", this.clearSelection.bind(this));
		Event.observe(this._monthSelect, "change", this.monthSelect.bind(this));
		Event.observe(this._yearSelect, "change", this.yearSelect.bind(this));

		// ie6 extension
		Event.observe(this._calDiv, "mousewheel", this.mouseWheelChange.bind(this));		
		
		Event.observe(this._table, "click", this.selectDate.bind(this));
		
		Event.observe(this._calDiv,"keydown", this.keyPressed.bind(this)); 
		
		/*
		this._calDiv.onkeydown = function (e) {
			if (e == null) e = document.parentWindow.event;
			var kc = e.keyCode != null ? e.keyCode : e.charCode;

			if(kc == 13) {
				var d = new Date(dp._currentDate).valueOf();
				dp.setSelectedDate(d);

				if (!dp._alwaysVisible && dp._hideOnSelect) {
					dp.hide();
				}
				return false;
			}
				
			
			if (kc < 37 || kc > 40) return true;
			
			var d = new Date(dp._currentDate).valueOf();
			if (kc == 37) // left
				d -= 24 * 60 * 60 * 1000;
			else if (kc == 39) // right
				d += 24 * 60 * 60 * 1000;
			else if (kc == 38) // up
				d -= 7 * 24 * 60 * 60 * 1000;
			else if (kc == 40) // down
				d += 7 * 24 * 60 * 60 * 1000;

			dp.setCurrentDate(new Date(d));
			return false;
		}*/
		
		
	},
	
	keyPressed : function(ev)
	{
		if (!ev) ev = document.parentWindow.event;
		var kc = ev.keyCode != null ? ev.keyCode : ev.charCode;
		
		if(kc = Event.KEY_RETURN)
		{
			//var d = new Date(this.currentDate);
			this.setSelectedDate(this.currentDate);
			this.hide();
			return false;
		}
		
		if(kc < 37 || kc > 40) return true;
		
		var d = new Date(this.currentDate).valueOf();
		if(kc == Event.KEY_LEFT)
			d -= 86400000; //-1 day
		else if (kc == Event.KEY_RIGHT)
			d += 86400000; //+1 day
		else if (kc == Event.KEY_UP)
			d -= 604800000; // -7 days
		else if (kc == Event.KEY_DOWN)
			d += 604800000; // +7 days
		this.setCurrentDate(new Date(d));
		return false;		
	},
	
	selectDate : function(ev)
	{
		var el = Event.element(ev);
		while (el.nodeType != 1)
			el = el.parentNode;
		
		while (el != null && el.tagName && el.tagName.toLowerCase() != "td")
			el = el.parentNode;
			
		// if no td found, return
		if (el == null || el.tagName == null || el.tagName.toLowerCase() != "td")
			return;
			
		var d = new Date(this.currentDate);
		var n = Number(el.firstChild.data);
		if (isNaN(n) || n <= 0 || n == null)
			return;
					
		d.setDate(n);
		this.setSelectedDate(d);
		this.hide();
	},
	
	selectToday : function()
	{
		this.setSelectedDate(new Date());
		this.hide();
	},
	
	clearSelection : function()
	{
		this.selectedDate = null;
		if (isFunction(this.onchange))
			this.onchange();
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
	
	// ie6 extension
	mouseWheelChange : function (e) 
	{
		if (e == null) e = document.parentWindow.event;
		var n = - e.wheelDelta / 120;
		var d = new Date(this.currentDate);
		var m = d.getMonth() + n;
		this.setMonth(m);
		//this.setCurrentDate(d);
			
		return false;
	},

	onchange : function() 
	{
		this.control.value = this.formatDate();
	},
	
	formatDate : function()
	{
		return this.selectedDate.SimpleFormat(this.format);
	},

	setCurrentDate : function(date) 
	{
		if (date == null)
			return;

		// if string or number create a Date object
		if (isString(date)  || isNumber(date))
			date = new Date(date);
	
		// do not update if not really changed
		if (this.currentDate.getDate() != date.getDate() ||
			this.currentDate.getMonth() != date.getMonth() || 
			this.currentDate.getFullYear() != date.getFullYear()) 
		{
		
			this.currentDate = new Date(date);
	
			this.updateHeader();
			this.update();
		}
	
	},
	
	setSelectedDate : function(date) 
	{
		this.selectedDate = new Date(date);
		this.setCurrentDate(this.selectedDate);
		if (isFunction(this.onchange))
			this.onchange();
	},

	getElement : function() 
	{
		return this._calDiv;
	},

	getSelectedDate : function () 
	{
		return isNull(this.selectedDate) ? null : new Date(this.selectedDate);
	},
	
	setYear : function(year) 
	{
		var d = new Date(this.currentDate);
		d.setFullYear(year);
		this.setCurrentDate(d);
	},

	setMonth : function (month) 
	{
		var d = new Date(this.currentDate);
		d.setMonth(month);
		this.setCurrentDate(d);
	},

	nextMonth : function () 
	{
		this.setMonth(this.currentDate.getMonth()+1);
	},

	prevMonth : function () 
	{
		this.setMonth(this.currentDate.getMonth()-1);
	},
	
	show : function() 
	{
		if(!this.showing)
		{
			var pos = Position.cumulativeOffset(this.control);
			pos[1] += this.control.offsetHeight;
			this._calDiv.style.display = "block";
			this._calDiv.style.top = pos[1] + "px";
			this._calDiv.style.left = pos[0] + "px";
			Event.observe(document.body, "click", this.hideOnClick.bind(this));
			var date = Date.SimpleParse(Form.Element.getValue(this.control), this.format);
			if(!isNull(date))
			{
				this.selectedDate = date;
				this.setCurrentDate(date);
			}
			this.showing = true;
		}
	},
	
	//hide the calendar when clicked outside any calendar
	hideOnClick : function(ev)
	{
		if(!this.showing) return;
		var el = Event.element(ev);
		var within = false;
		do
		{
			within = within || el.className == this.className;
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
			this.showing = false;	
			Event.stopObserving(document.body, "click", this.hideOnClick.bind(this));	
		}	
	},
	
	update : function() 
	{
		var Util = Prado.Calendar.Util;

		// Calculate the number of days in the month for the selected date
		var date = this.currentDate;
		var today = (new Date()).toISODate();
		
		var selected = isNull(this.selectedDate) ? "" : this.selectedDate.toISODate();
		var current = date.toISODate();
		var d1 = new Date(date.getFullYear(), date.getMonth(), 1);
		var d2 = new Date(date.getFullYear(), date.getMonth()+1, 1);
		var monthLength = Math.round((d2 - d1) / (24 * 60 * 60 * 1000));
		
		// Find out the weekDay index for the first of this month
		var firstIndex = (d1.getDay() - this.firstDayOfWeek) % 7 ;
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
			if (d1.toISODate() == current) {
				slotNode.className += " current";
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
		//conditionally add the hover class to the event target element.
		Element.condClassName(Event.element(ev), "hover", ev.type=="mouseover");	
	},
	
	updateHeader : function () {

		var options = this._monthSelect.options;
		var m = this.currentDate.getMonth();
		for(var i=0; i < options.length; ++i) {
			options[i].selected = false;
			if (options[i].value == m) {
				options[i].selected = true;
			}
		}
		
		options = this._yearSelect.options;
		var year = this.currentDate.getFullYear();
		for(var i=0; i < options.length; ++i) {
			options[i].selected = false;
			if (options[i].value == year) {
				options[i].selected = true;
			}
		}
	
	}
	
	
};