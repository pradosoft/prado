/*! PRADO TDatePicker javascript file | github.com/pradosoft/prado */

Prado.WebUI.TDatePicker = Prado.Class(Prado.WebUI.Control,
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

	onInit(options) {
		this.options = options || [];
		this.control = document.getElementById(options.ID);
		this.dateSlot = new Array(42);
		this.weekSlot = new Array(6);
		this.minimalDaysInFirstWeek	= 4;
		this.positionMode = 'Bottom';

		Prado.Registry[options.ID] = this;

		//which element to trigger to show the calendar
		let triggerEvent;
		if(this.options.Trigger)
		{
			this.trigger = document.getElementById(this.options.Trigger);
			triggerEvent = this.options.TriggerEvent || "click";
		}
		else
		{
			this.trigger  = this.control;
			triggerEvent = this.options.TriggerEvent || "focus";
		}

		// Popup position
		if(this.options.PositionMode == 'Top')
		{
			this.positionMode = this.options.PositionMode;
		}

		Object.assign(this,options);
		// generate default date _after_ extending options
		this.selectedDate = this.newDate();

		this.observe(this.trigger, triggerEvent, this.show.bind(this));

		// Listen to change event if needed
		if (typeof(this.options.OnDateChanged) == "function")
		{
			if(this.options.InputMode == "TextBox")
			{
				this.observe(this.control, "change", this.onDateChanged.bind(this));
			}
			else
			{
				const day = Prado.WebUI.TDatePicker.getDayListControl(this.control);
				const month = Prado.WebUI.TDatePicker.getMonthListControl(this.control);
				const year = Prado.WebUI.TDatePicker.getYearListControl(this.control);
				this.observe (day, "change", this.onDateChanged.bind(this));
				this.observe (month, "change", this.onDateChanged.bind(this));
				this.observe (year, "change", this.onDateChanged.bind(this));

			}


		}

	},

	create() {
		if(typeof(this._calDiv) != "undefined")
			return;

		let div;
		let table;
		let tbody;
		let tr;
		let td;

		// Create the top-level div element
		this._calDiv = document.createElement("div");
		this._calDiv.className = `TDatePicker_${this.CalendarStyle} ${this.ClassName}`;
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
		const previousMonth = document.createElement("input");
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
	    for (let i = 0 ; i < this.MonthNames.length ; i++) {
	        const opt = document.createElement("option");
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
		for(let i = this.FromYear; i <= this.UpToYear; ++i) {
			const opt = document.createElement("option");
			opt.innerHTML = i;
			opt.value = i;
			if (i == this.selectedDate.getFullYear()) {
				opt.selected = false;
			}
			this._yearSelect.appendChild(opt);
		}
		td.appendChild(this._yearSelect);


		td = document.createElement("td");
		const nextMonth = document.createElement("input");
		nextMonth.className = "nextMonthButton button";
		nextMonth.type = "button";
		nextMonth.value = ">>";
		td.appendChild(nextMonth);
		tr.appendChild(td);

		// Calendar body
		div = document.createElement("div");
		div.className = "calendarBody";
		this._calDiv.appendChild(div);
		const calendarBody = div;

		// Create the inside of calendar body

		let text;
		table = document.createElement("table");
		table.align="center";
		table.className = "grid";

	    div.appendChild(table);
		const thead = document.createElement("thead");
		table.appendChild(thead);
		tr = document.createElement("tr");
		thead.appendChild(tr);

		for(let i = 0; i < 7; ++i) {
			td = document.createElement("th");
			text = document.createTextNode(this.ShortWeekDayNames[(i+this.FirstDayOfWeek)%7]);
			td.appendChild(text);
			td.className = "weekDayHead";
			tr.appendChild(td);
		}

		// Date grid
		tbody = document.createElement("tbody");
		table.appendChild(tbody);

		for(let week=0; week<6; ++week) {
			tr = document.createElement("tr");
			tbody.appendChild(tr);

		for(let day=0; day<7; ++day) {
				td = document.createElement("td");
				td.className = "calendarDate";
				text = document.createTextNode(String.fromCharCode(160));
				td.appendChild(text);

				tr.appendChild(td);
				const tmp = new Object();
				tmp.tag = "DATE";
				tmp.value = -1;
				tmp.data = text;
				this.dateSlot[(week*7)+day] = tmp;

				this.observe(td, "mouseover", this.hover.bind(this));
				this.observe(td, "mouseout", this.hover.bind(this));

			}
		}

		// Calendar Footer
		div = document.createElement("div");
		div.className = "calendarFooter";
		this._calDiv.appendChild(div);

		const todayButton = document.createElement("input");
		todayButton.type="button";
		todayButton.className = "todayButton";
		const today = this.newDate();
		const buttonText = today.SimpleFormat(this.Format,this);
		todayButton.value = buttonText;
		div.appendChild(todayButton);

		this.control.parentNode.appendChild(this._calDiv);

		this.update();
		this.updateHeader();

		// hook up events
		this.observe(previousMonth, "click", this.prevMonth.bind(this));
		this.observe(nextMonth, "click", this.nextMonth.bind(this));
		this.observe(todayButton, "click", this.selectToday.bind(this));
		//Event.observe(clearButton, "click", this.clearSelection.bind(this));
		this.observe(this._monthSelect, "change", this.monthSelect.bind(this));
		this.observe(this._yearSelect, "change", this.yearSelect.bind(this));

		// ie, opera
		this.observe(this._calDiv, "mousewheel", this.mouseWheelChange.bind(this));
		// ff
		this.observe(this._calDiv, "DOMMouseScroll", this.mouseWheelChange.bind(this));

		this.observe(calendarBody, "click", this.selectDate.bind(this));

		this.control.focus();

	},

	keyPressed(ev) {
		if(!this.showing) return;
		if (!ev) ev = document.parentWindow.event;
		const kc = ev.keyCode != null ? ev.keyCode : ev.charCode;

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

		const getDaysPerMonth = (nMonth, nYear) => {
			nMonth = (nMonth + 12) % 12;
	        const days= [31,28,31,30,31,30,31,31,30,31,30,31];
			let res = days[nMonth];
			if (nMonth == 1) //feburary, leap years has 29
                res += nYear % 4 == 0 && !(nYear % 400 == 0) ? 1 : 0;
	        return res;
		};

		if(kc < 37 || kc > 40) return true;

		const current = this.selectedDate;
		let d = current.valueOf();
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

	selectDate(ev) {
		let el = ev.target;
		while (el.nodeType != 1)
			el = el.parentNode;

		while (el != null && el.tagName && el.tagName.toLowerCase() != "td")
			el = el.parentNode;

		// if no td found, return
		if (el == null || el.tagName == null || el.tagName.toLowerCase() != "td")
			return;

		const d = this.newDate(this.selectedDate);
		const n = Number(el.firstChild.data);
		if (isNaN(n) || n <= 0 || n == null)
			return;

		d.setDate(n);
		this.setSelectedDate(d);
		this.hide();
	},

	selectToday() {
		if(this.selectedDate.toISODate() == this.newDate().toISODate())
			this.hide();

		this.setSelectedDate(this.newDate());
	},

	clearSelection() {
		this.setSelectedDate(this.newDate());
		this.hide();
	},

	monthSelect(ev) {
		this.setMonth(ev.target.value);
	},

	yearSelect(ev) {
		this.setYear(ev.target.value);
	},

	mouseWheelChange(event) {
		let delta = 0;
		if (!event) event = document.parentWindow.event;
		if (event.wheelDelta) {
			delta = event.wheelDelta/120;
			if (window.opera) delta = -delta;
		} else if (event.detail) { delta = -event.detail/3;     }

		const d = this.newDate(this.selectedDate);
		const m = d.getMonth() + Math.round(delta);
		this.setMonth(m,true);
		return false;
	},

	// Respond to change event on the textbox or dropdown list
	// This method raises OnDateChanged event on client side if it has been defined
	onDateChanged() {
		if (this.options.OnDateChanged)
		{
		 	let date;
		 	if (this.options.InputMode == "TextBox")
		 	{
		 		date=this.control.value;
		 	}
		 	else
		 	{
		 		const day = Prado.WebUI.TDatePicker.getDayListControl(this.control).selectedIndex+1;
				const month = Prado.WebUI.TDatePicker.getMonthListControl(this.control).selectedIndex;
				const year = Prado.WebUI.TDatePicker.getYearListControl(this.control).value;
				date=new Date(year, month, day, 0,0,0).SimpleFormat(this.Format, this);
			}
			this.options.OnDateChanged(this, date);
		}
	},

	fireChangeEvent(element, capped) {
		if (capped)
			{
				const obj = this;

				if (typeof(obj.changeeventtimer)!="undefined")
				{
					clearTimeout(obj.changeeventtimer);
					obj.changeeventtimer = null;
				}
				obj.changeeventtimer = setTimeout(
					() => { obj.changeeventtimer = null; element.dispatchEvent(new Event('change', { bubbles: true })); },
					1500
				);
			}
		else
			element.dispatchEvent(new Event('change', { bubbles: true }));
	},

	onChange(ref, date, capevents) {
		if(this.options.InputMode == "TextBox")
		{
			this.control.value = this.formatDate();
			this.fireChangeEvent(this.control, capevents);
		}
		else
		{
			const day = Prado.WebUI.TDatePicker.getDayListControl(this.control);
			const month = Prado.WebUI.TDatePicker.getMonthListControl(this.control);
			const year = Prado.WebUI.TDatePicker.getYearListControl(this.control);
			// Reassign the `date` parameter; in DropDownList mode the picker's
			// currently-selected date is the source of truth.
			date = this.selectedDate;
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
				const years = year.options;
				const currentYear = date.getFullYear();
				for(let i = 0; i < years.length; i++)
					years[i].selected = years[i].value.toInteger() == currentYear;
			}

			day && this.fireChangeEvent(day, capevents);
			month && this.fireChangeEvent(month, capevents);
			year && this.fireChangeEvent(year, capevents);
		}
	},

	formatDate() {
		return this.selectedDate ? this.selectedDate.SimpleFormat(this.Format,this) : '';
	},

	newDate(date) {
		if(!date)
			date = new Date();
		if(typeof(date) == "string" || typeof(date) == "number")
			date = new Date(date);
		return new Date(Math.min(Math.max(date.getFullYear(),this.FromYear),this.UpToYear), date.getMonth(), date.getDate(), 0,0,0);
	},

	setSelectedDate(date, capevents) {
		if (date == null)
			return;
		const old=this.selectedDate;
		this.selectedDate = this.newDate(date);
		const dateChanged=(old - this.selectedDate != 0) || ( this.options.InputMode == "TextBox" && this.control.value != this.formatDate());

		this.updateHeader();
		this.update();
		if (dateChanged && typeof(this.onChange) == "function")
			this.onChange(this, date, capevents);
	},

	getElement() {
		return this._calDiv;
	},

	getSelectedDate() {
		return this.selectedDate == null ? null : this.newDate(this.selectedDate);
	},

	setYear(year) {
		const d = this.newDate(this.selectedDate);
		d.setFullYear(year);
		this.setSelectedDate(d);
	},

	setMonth(month, capevents) {
		const d = this.newDate(this.selectedDate);
		d.setDate(Math.min(d.getDate(), this.getDaysPerMonth(month,d.getFullYear())));
		d.setMonth(month);
		this.setSelectedDate(d,capevents);
	},

	nextMonth() {
		this.setMonth(this.selectedDate.getMonth()+1);
	},

	prevMonth() {
		this.setMonth(this.selectedDate.getMonth()-1);
	},

	getDaysPerMonth(month, year) {
		month = (Number(month)+12) % 12;
        const days = [31,28,31,30,31,30,31,31,30,31,30,31];
		let res = days[month];
		if (month == 1 && ((!(year % 4) && (year % 100)) || !(year % 400))) //feburary, leap years has 29
            res++;
        return res;
	},

	getDatePickerOffsetHeight() {
		if(this.options.InputMode == "TextBox")
			return this.control.offsetHeight;

		let control = Prado.WebUI.TDatePicker.getDayListControl(this.control);
		if(control) return control.offsetHeight;

		control = Prado.WebUI.TDatePicker.getMonthListControl(this.control);
		if(control) return control.offsetHeight;

		control = Prado.WebUI.TDatePicker.getYearListControl(this.control);
		if(control) return control.offsetHeight;
		return 0;
	},

	show() {
		this.create();

		if(!this.showing)
		{
			// Position the calendar relative to the input's offsetParent.
			// `el.offsetTop`/`offsetLeft` is the native equivalent of
			// jQuery's `$(control).offset() - $(control).offsetParent().offset()`
			// and is unaffected by document scroll or body margin (which broke
			// the previous jQuery-based math on pages with a non-zero body
			// margin — the calendar would render slightly *above* the input,
			// and the next test action would hit a calendar cell instead of
			// the following input).
			const top  = this.control.offsetTop;
			const left = this.control.offsetLeft;

			if(this.positionMode=='Top')
			{
				this._calDiv.style.display = 'block';
				// _calDiv must be visible to get its offsetHeight
				this._calDiv.style.top  = `${top - this._calDiv.offsetHeight}px`;
				this._calDiv.style.left = `${left}px`;
			} else {
				this._calDiv.style.top  = `${top + this.getDatePickerOffsetHeight() - 1}px`;
				this._calDiv.style.left = `${left}px`;
				this._calDiv.style.display = 'block';
			}

			// `jQuery.bind(fn, this)` was a low-pro shim helper removed in step 3
			// of the JS modernization. Use the native Function.prototype.bind.
			// Without these, the document-body click handler was never attached
			// and the calendar never closed on outside clicks — leaving the
			// calendar overlay intercepting subsequent test actions.
			this.documentClickEvent   = this.hideOnClick.bind(this);
			this.documentKeyDownEvent = this.keyPressed.bind(this);
			this.observe(document.body, "click", this.documentClickEvent);
			const date = this.getDateFromInput();
			if(date)
			{
				this.selectedDate = date;
				this.setSelectedDate(date);
			}
			this.observe(document,"keydown", this.documentKeyDownEvent);
			this.showing = true;
		}
	},

	getDateFromInput() {
		if(this.options.InputMode == "TextBox")
			return Date.SimpleParse(this.control.value, this.Format);
		else
			return Prado.WebUI.TDatePicker.getDropDownDate(this.control);
	},

	//hide the calendar when clicked outside any calendar
	hideOnClick(ev) {
		if(!this.showing) return;
		let el = ev.target;
		let within = false;
		do
		{
			within = within || (el.className && el.classList.contains(`TDatePicker_${this.CalendarStyle}`));
			within = within || el == this.trigger;
			within = within || el == this.control;
			if(within) break;
			el = el.parentNode;
		}
		while(el);
		if(!within) this.hide();
	},


	hide() {
		if(this.showing)
		{
			this._calDiv.style.display = "none";
			this.showing = false;
			this.stopObserving(document.body, "click", this.documentClickEvent);
			this.stopObserving(document,"keydown", this.documentKeyDownEvent);
		}
	},

	update() {
		// Calculate the number of days in the month for the selected date
		const date = this.selectedDate;
		const today = (this.newDate()).toISODate();

		const selected = date.toISODate();
		let d1 = new Date(date.getFullYear(), date.getMonth(), 1);
		const d2 = new Date(date.getFullYear(), date.getMonth()+1, 1);
		const monthLength = Math.round((d2 - d1) / (24 * 60 * 60 * 1000));

		// Find out the weekDay index for the first of this month
		let firstIndex = (d1.getDay() - this.FirstDayOfWeek) % 7;
	    if (firstIndex < 0)
	    	firstIndex += 7;

		let index = 0;
		while (index < firstIndex) {
			this.dateSlot[index].value = -1;
			this.dateSlot[index].data.data = String.fromCharCode(160);
			this.dateSlot[index].data.parentNode.className = "empty";
			index++;
		}

	    for (let i = 1; i <= monthLength; i++, index++) {
			const slot = this.dateSlot[index];
			const slotNode = slot.data.parentNode;
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

		// Index of the last in-month date slot; kept available for any future
		// "last filled cell" computations but currently unused.
		const _lastDateIndex = index;

	    while(index < 42) {
			this.dateSlot[index].value = -1;
			this.dateSlot[index].data.data = String.fromCharCode(160);
			this.dateSlot[index].data.parentNode.className = "empty";
			++index;
		}

	},

	hover(ev) {
		if(ev.target.tagName)
		{
			if(ev.type == "mouseover")
				ev.target.classList.add("hover");
			else
				ev.target.classList.remove("hover");
		}
	},

	updateHeader() {

		let options = this._monthSelect.options;
		const m = this.selectedDate.getMonth();
		for(let i = 0; i < options.length; ++i) {
			options[i].selected = false;
			if (options[i].value == m) {
				options[i].selected = true;
			}
		}

		options = this._yearSelect.options;
		const year = this.selectedDate.getFullYear();
		for(let i = 0; i < options.length; ++i) {
			options[i].selected = false;
			if (options[i].value == year) {
				options[i].selected = true;
			}
		}

	}
});

Object.assign(Prado.WebUI.TDatePicker,
{
	/**
	 * @return Date the date from drop down list options.
	 */
	getDropDownDate(control) {
		const now = new Date();
		const month_list = Prado.WebUI.TDatePicker.getMonthListControl(control);
	 	const day_list = Prado.WebUI.TDatePicker.getDayListControl(control);
	 	const year_list = Prado.WebUI.TDatePicker.getYearListControl(control);

		const day   = day_list   ? day_list.value   : 1;
		const month = month_list ? month_list.value : now.getMonth();
		const year  = year_list  ? year_list.value  : now.getFullYear();

		return new Date(year, month, day, 0, 0, 0);
	},

	getYearListControl(control) {
		return document.getElementById(`${control.id}_year`);
	},

	getMonthListControl(control) {
		return document.getElementById(`${control.id}_month`);
	},

	getDayListControl(control) {
		return document.getElementById(`${control.id}_day`);
	}
});