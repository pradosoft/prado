/*! PRADO TDatePicker javascript file | github.com/pradosoft/prado */

Prado.WebUI.TDatePicker = jQuery.klass(Prado.WebUI.Control,
{
	MonthNames : [	"January",		"February",		"March",	"April",
		"May",			"June",			"July",		"August",
		"September",	"October",		"November",	"December"
	],
	AbbreviatedMonthNames : ["Jan", "Feb", "Mar", "Apr", "May",
						"Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],

	ShortWeekDayNames : ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ],

	Format : "yyyy-MM-dd",

	FirstDayOfWeek : 1, // 0 for sunday

	ClassName : "",

	CalendarStyle : "default",

	FromYear : new Date().getFullYear() - 10, UpToYear: new Date().getFullYear() + 5,

	onInit : function(options)
	{
		this.options = options || [];
		this.control = jQuery('#'+options.ID).get(0);
		this.dateSlot = new Array(42);
		this.weekSlot = new Array(6);
		this.minimalDaysInFirstWeek	= 4;
		this.positionMode = 'Bottom';

		Prado.Registry[options.ID] = this;

		//which element to trigger to show the calendar
		if(this.options.Trigger)
		{
			this.trigger = jQuery('#'+this.options.Trigger).get(0);
			var triggerEvent = this.options.TriggerEvent || "click";
		}
		else
		{
			this.trigger  = this.control;
			var triggerEvent = this.options.TriggerEvent || "focus";
		}

		// Popup position
		if(this.options.PositionMode == 'Top')
		{
			this.positionMode = this.options.PositionMode;
		}

		jQuery.extend(this,options);
		// generate default date _after_ extending options
		this.selectedDate = this.newDate();

		this.observe(this.trigger, triggerEvent, jQuery.proxy(this.show,this));

		// Listen to change event if needed
		if (typeof(this.options.OnDateChanged) == "function")
		{
			if(this.options.InputMode == "TextBox")
			{
				this.observe(this.control, "change", jQuery.proxy(this.onDateChanged,this));
			}
			else
			{
				var day = Prado.WebUI.TDatePicker.getDayListControl(this.control);
				var month = Prado.WebUI.TDatePicker.getMonthListControl(this.control);
				var year = Prado.WebUI.TDatePicker.getYearListControl(this.control);
				this.observe (day, "change", jQuery.proxy(this.onDateChanged,this));
				this.observe (month, "change", jQuery.proxy(this.onDateChanged,this));
				this.observe (year, "change", jQuery.proxy(this.onDateChanged,this));

			}


		}

	},

	create : function()
	{
		if(typeof(this._calDiv) != "undefined")
			return;

		var div;
		var table;
		var tbody;
		var tr;
		var td;

		// Create the top-level div element
		this._calDiv = document.createElement("div");
		this._calDiv.className = "TDatePicker_"+this.CalendarStyle+" "+this.ClassName;
		this._calDiv.style.display = "none";
		this._calDiv.style.position = "absolute"

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
		var previousMonth = document.createElement("input");
		previousMonth.className = "prevMonthButton button";
		previousMonth.type = "button"
		previousMonth.value = "<<";
		td.appendChild(previousMonth);
		tr.appendChild(td);



		//
		// Create the month drop down
		//
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


		//
		// Create the year drop down
		//
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
		var nextMonth = document.createElement("input");
		nextMonth.className = "nextMonthButton button";
		nextMonth.type = "button";
		nextMonth.value = ">>";
		td.appendChild(nextMonth);
		tr.appendChild(td);

		// Calendar body
		div = document.createElement("div");
		div.className = "calendarBody";
		this._calDiv.appendChild(div);
		var calendarBody = div;

		// Create the inside of calendar body

		var text;
		table = document.createElement("table");
		table.align="center";
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

		// Date grid
		tbody = document.createElement("tbody");
		table.appendChild(tbody);

		for(var week=0; week<6; ++week) {
			tr = document.createElement("tr");
			tbody.appendChild(tr);

		for(var day=0; day<7; ++day) {
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

				this.observe(td, "mouseover", jQuery.proxy(this.hover,this));
				this.observe(td, "mouseout", jQuery.proxy(this.hover,this));

			}
		}

		// Calendar Footer
		div = document.createElement("div");
		div.className = "calendarFooter";
		this._calDiv.appendChild(div);

		var todayButton = document.createElement("input");
		todayButton.type="button";
		todayButton.className = "todayButton";
		var today = this.newDate();
		var buttonText = today.SimpleFormat(this.Format,this);
		todayButton.value = buttonText;
		div.appendChild(todayButton);

		this.control.parentNode.appendChild(this._calDiv);

		this.update();
		this.updateHeader();

		// hook up events
		this.observe(previousMonth, "click", jQuery.proxy(this.prevMonth,this));
		this.observe(nextMonth, "click", jQuery.proxy(this.nextMonth,this));
		this.observe(todayButton, "click", jQuery.proxy(this.selectToday,this));
		//Event.observe(clearButton, "click", jQuery.proxy(this.clearSelection,this));
		this.observe(this._monthSelect, "change", jQuery.proxy(this.monthSelect,this));
		this.observe(this._yearSelect, "change", jQuery.proxy(this.yearSelect,this));

		// ie, opera
		this.observe(this._calDiv, "mousewheel", jQuery.proxy(this.mouseWheelChange,this));
		// ff
		this.observe(this._calDiv, "DOMMouseScroll", jQuery.proxy(this.mouseWheelChange,this));

		this.observe(calendarBody, "click", jQuery.proxy(this.selectDate,this));

		jQuery(this.control).focus();

	},

	keyPressed : function(ev)
	{
		if(!this.showing) return;
		if (!ev) ev = document.parentWindow.event;
		var kc = ev.keyCode != null ? ev.keyCode : ev.charCode;

		// return, space, tab
		if(kc == 13 || kc == 32 || kc == 9)
		{
			this.setSelectedDate(this.selectedDate);
			ev.preventDefault();
			this.hide();
		}
		// esc
		if(kc == 27)
		{
			ev.preventDefault();
			this.hide();
		}

		var getDaysPerMonth = function (nMonth, nYear)
		{
			nMonth = (nMonth + 12) % 12;
	        var days= [31,28,31,30,31,30,31,31,30,31,30,31];
			var res = days[nMonth];
			if (nMonth == 1) //feburary, leap years has 29
                res += nYear % 4 == 0 && !(nYear % 400 == 0) ? 1 : 0;
	        return res;
		}

		if(kc < 37 || kc > 40) return true;

		var current = this.selectedDate;
		var d = current.valueOf();
		if(kc == 37) // left
		{
			if(ev.ctrlKey || ev.shiftKey) // -1 month
			{
                current.setDate( Math.min(current.getDate(), getDaysPerMonth(current.getMonth() - 1,current.getFullYear())) ); // no need to catch dec -> jan for the year
                d = current.setMonth( current.getMonth() - 1 );
			}
			else
				d -= 86400000; //-1 day
		}
		else if (kc == 39) // right
		{
			if(ev.ctrlKey || ev.shiftKey) // +1 month
			{
				current.setDate( Math.min(current.getDate(), getDaysPerMonth(current.getMonth() + 1,current.getFullYear())) ); // no need to catch dec -> jan for the year
				d = current.setMonth( current.getMonth() + 1 );
			}
			else
				d += 86400000; //+1 day
		}
		else if (kc == 38) // up
		{
			if(ev.ctrlKey || ev.shiftKey) //-1 year
			{
				current.setDate( Math.min(current.getDate(), getDaysPerMonth(current.getMonth(),current.getFullYear() - 1)) ); // no need to catch dec -> jan for the year
				d = current.setFullYear( current.getFullYear() - 1 );
			}
			else
				d -= 604800000; // -7 days
		}
		else if (kc == 40) // down
		{
			if(ev.ctrlKey || ev.shiftKey) // +1 year
			{
				current.setDate( Math.min(current.getDate(), getDaysPerMonth(current.getMonth(),current.getFullYear() + 1)) ); // no need to catch dec -> jan for the year
				d = current.setFullYear( current.getFullYear() + 1 );
			}
			else
				d += 7 * 24 * 61 * 60 * 1000; // +7 days
		}
		this.setSelectedDate(d);
		ev.preventDefault();
	},

	selectDate : function(ev)
	{
		var el = ev.target;
		while (el.nodeType != 1)
			el = el.parentNode;

		while (el != null && el.tagName && el.tagName.toLowerCase() != "td")
			el = el.parentNode;

		// if no td found, return
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
		this.setMonth(ev.target.value);
	},

	yearSelect : function(ev)
	{
		this.setYear(ev.target.value);
	},

	mouseWheelChange : function (event)
	{
		var delta = 0;
		if (!event) event = document.parentWindow.event;
		if (event.wheelDelta) {
			delta = event.wheelDelta/120;
			if (window.opera) delta = -delta;
		} else if (event.detail) { delta = -event.detail/3;     }

		var d = this.newDate(this.selectedDate);
		var m = d.getMonth() + Math.round(delta);
		this.setMonth(m,true);
		return false;
	},

	// Respond to change event on the textbox or dropdown list
	// This method raises OnDateChanged event on client side if it has been defined
	onDateChanged : function ()
	{
		if (this.options.OnDateChanged)
		{
		 	var date;
		 	if (this.options.InputMode == "TextBox")
		 	{
		 		date=this.control.value;
		 	}
		 	else
		 	{
		 		var day = Prado.WebUI.TDatePicker.getDayListControl(this.control).selectedIndex+1;
				var month = Prado.WebUI.TDatePicker.getMonthListControl(this.control).selectedIndex;
				var year = Prado.WebUI.TDatePicker.getYearListControl(this.control).value;
				date=new Date(year, month, day, 0,0,0).SimpleFormat(this.Format, this);
			}
			this.options.OnDateChanged(this, date);
		}
	},

	fireChangeEvent: function(element, capped)
	{
		if (capped)
			{
				var obj = this;

				if (typeof(obj.changeeventtimer)!="undefined")
				{
					clearTimeout(obj.changeeventtimer);
					obj.changeeventtimer = null;
				}
				obj.changeeventtimer = setTimeout(
					function() { obj.changeeventtimer = null; jQuery(element).trigger("change"); },
					1500
				);
			}
		else
			jQuery(element).trigger("change");
	},

	onChange : function(ref, date, capevents)
	{
		if(this.options.InputMode == "TextBox")
		{
			this.control.value = this.formatDate();
			this.fireChangeEvent(this.control, capevents);
		}
		else
		{
			var day = Prado.WebUI.TDatePicker.getDayListControl(this.control);
			var month = Prado.WebUI.TDatePicker.getMonthListControl(this.control);
			var year = Prado.WebUI.TDatePicker.getYearListControl(this.control);
			var date = this.selectedDate;
			if(day)
			{
				day.selectedIndex = date.getDate()-1;
			}
			if(month)
			{
				month.selectedIndex = date.getMonth();
			}
			if(year)
			{
				var years = year.options;
				var currentYear = date.getFullYear();
				for(var i = 0; i < years.length; i++)
					years[i].selected = years[i].value.toInteger() == currentYear;
			}

			day && this.fireChangeEvent(day, capevents);
			month && this.fireChangeEvent(month, capevents);
			year && this.fireChangeEvent(year, capevents);
		}
	},

	formatDate : function()
	{
		return this.selectedDate ? this.selectedDate.SimpleFormat(this.Format,this) : '';
	},

	newDate : function(date)
	{
		if(!date)
			date = new Date();
		if(typeof(date) == "string" || typeof(date) == "number")
			date = new Date(date);
		return new Date(Math.min(Math.max(date.getFullYear(),this.FromYear),this.UpToYear), date.getMonth(), date.getDate(), 0,0,0);
	},

	setSelectedDate : function(date, capevents)
	{
		if (date == null)
			return;
		var old=this.selectedDate;
		this.selectedDate = this.newDate(date);
		var dateChanged=(old - this.selectedDate != 0) || ( this.options.InputMode == "TextBox" && this.control.value != this.formatDate());

		this.updateHeader();
		this.update();
		if (dateChanged && typeof(this.onChange) == "function")
			this.onChange(this, date, capevents);
	},

	getElement : function()
	{
		return this._calDiv;
	},

	getSelectedDate : function ()
	{
		return this.selectedDate == null ? null : this.newDate(this.selectedDate);
	},

	setYear : function(year)
	{
		var d = this.newDate(this.selectedDate);
		d.setFullYear(year);
		this.setSelectedDate(d);
	},

	setMonth : function (month, capevents)
	{
		var d = this.newDate(this.selectedDate);
		d.setDate(Math.min(d.getDate(), this.getDaysPerMonth(month,d.getFullYear())));
		d.setMonth(month);
		this.setSelectedDate(d,capevents);
	},

	nextMonth : function ()
	{
		this.setMonth(this.selectedDate.getMonth()+1);
	},

	prevMonth : function ()
	{
		this.setMonth(this.selectedDate.getMonth()-1);
	},

	getDaysPerMonth : function (month, year)
	{
		month = (Number(month)+12) % 12;
        var days = [31,28,31,30,31,30,31,31,30,31,30,31];
		var res = days[month];
		if (month == 1 && ((!(year % 4) && (year % 100)) || !(year % 400))) //feburary, leap years has 29
            res++;
        return res;
	},

	getDatePickerOffsetHeight : function()
	{
		if(this.options.InputMode == "TextBox")
			return this.control.offsetHeight;

		var control = Prado.WebUI.TDatePicker.getDayListControl(this.control);
		if(control) return control.offsetHeight;

		var control = Prado.WebUI.TDatePicker.getMonthListControl(this.control);
		if(control) return control.offsetHeight;

		var control = Prado.WebUI.TDatePicker.getYearListControl(this.control);
		if(control) return control.offsetHeight;
		return 0;
	},

	show : function()
	{
		this.create();

		if(!this.showing)
		{
			var controlOffset = jQuery(this.control).offset();
			var parentOffset = jQuery(this.control).offsetParent().offset();

			if(this.positionMode=='Top')
			{
				jQuery(this._calDiv).css({
					display: "block"
				});
				// _calDiv must be visible to get its offsetHeight
				jQuery(this._calDiv).css({
					top: controlOffset['top'] - parentOffset['top'] - this._calDiv.offsetHeight,
					left: controlOffset['left'] - parentOffset['left'],
				});
			} else {
				jQuery(this._calDiv).css({
					top: controlOffset['top'] - parentOffset['top'] + this.getDatePickerOffsetHeight() - 1,
					left: controlOffset['left'] - parentOffset['left'],
					display: "block"
				});
			}

			this.documentClickEvent = jQuery.bind(this.hideOnClick, this);
			this.documentKeyDownEvent = jQuery.bind(this.keyPressed, this);
			this.observe(document.body, "click", this.documentClickEvent);
			var date = this.getDateFromInput();
			if(date)
			{
				this.selectedDate = date;
				this.setSelectedDate(date);
			}
			this.observe(document,"keydown", this.documentKeyDownEvent);
			this.showing = true;
		}
	},

	getDateFromInput : function()
	{
		if(this.options.InputMode == "TextBox")
			return Date.SimpleParse(this.control.value, this.Format);
		else
			return Prado.WebUI.TDatePicker.getDropDownDate(this.control);
	},

	//hide the calendar when clicked outside any calendar
	hideOnClick : function(ev)
	{
		if(!this.showing) return;
		var el = ev.target;
		var within = false;
		do
		{
			within = within || (el.className && jQuery(el).hasClass("TDatePicker_"+this.CalendarStyle));
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
			this.stopObserving(document.body, "click", this.documentClickEvent);
			this.stopObserving(document,"keydown", this.documentKeyDownEvent);
		}
	},

	update : function()
	{
		// Calculate the number of days in the month for the selected date
		var date = this.selectedDate;
		var today = (this.newDate()).toISODate();

		var selected = date.toISODate();
		var d1 = new Date(date.getFullYear(), date.getMonth(), 1);
		var d2 = new Date(date.getFullYear(), date.getMonth()+1, 1);
		var monthLength = Math.round((d2 - d1) / (24 * 60 * 60 * 1000));

		// Find out the weekDay index for the first of this month
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

	    for (var i = 1; i <= monthLength; i++, index++) {
			var slot = this.dateSlot[index];
			var slotNode = slot.data.parentNode;
			slot.value = i;
			slot.data.data = i;
			slotNode.className = "date";
			//slotNode.style.color = "";
			if (d1.toISODate() == today) {
				slotNode.className += " today";
			}
			if (d1.toISODate() == selected) {
			//	slotNode.style.color = "blue";
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
		if(ev.target.tagName)
		{
			if(ev.type == "mouseover")
				jQuery(ev.target).addClass("hover");
			else
				jQuery(ev.target).removeClass("hover");
		}
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
});

jQuery.extend(Prado.WebUI.TDatePicker,
{
	/**
	 * @return Date the date from drop down list options.
	 */
	getDropDownDate : function(control)
	{
		var now=new Date();
		var year=now.getFullYear();
		var month=now.getMonth();
		var day=1;

		var month_list = Prado.WebUI.TDatePicker.getMonthListControl(control);
	 	var day_list = Prado.WebUI.TDatePicker.getDayListControl(control);
	 	var year_list = Prado.WebUI.TDatePicker.getYearListControl(control);

		var day = day_list ? day_list.value : 1;
		var month = month_list ? month_list.value : now.getMonth();
		var year = year_list ? year_list.value : now.getFullYear();

		return new Date(year,month,day, 0, 0, 0);
	},

	getYearListControl : function(control)
	{
		return jQuery('#'+control.id+"_year").get(0);
	},

	getMonthListControl : function(control)
	{
		return jQuery('#'+control.id+"_month").get(0);
	},

	getDayListControl : function(control)
	{
		return jQuery('#'+control.id+"_day").get(0);
	}
});