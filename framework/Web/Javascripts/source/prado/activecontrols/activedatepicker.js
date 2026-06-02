/*! PRADO TActiveDatePicker javascript file | github.com/pradosoft/prado */

Prado.WebUI.TActiveDatePicker = Prado.Class(Prado.WebUI.TDatePicker,
{
	onInit(options) {
		this.options = options || [];
		this.control = document.getElementById(options.ID);
		this.dateSlot = new Array(42);
		this.weekSlot = new Array(6);
		this.minimalDaysInFirstWeek	= 4;
		this.selectedDate = this.newDate();
		this.positionMode = 'Bottom';


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

		Object.assign(this, options);

		if (this.options.ShowCalendar)
			this.observe(this.trigger, triggerEvent, this.show.bind(this));

		// Listen to change event
		if(this.options.InputMode == "TextBox")
		{
			this.observe(this.control, "change", this.onDateChanged.bind(this));
		}
		else
		{
			const day = Prado.WebUI.TDatePicker.getDayListControl(this.control);
			const month = Prado.WebUI.TDatePicker.getMonthListControl(this.control);
			const year = Prado.WebUI.TDatePicker.getYearListControl(this.control);
			if (day) this.observe (day, "change", this.onDateChanged.bind(this));
			if (month) this.observe (month, "change", this.onDateChanged.bind(this));
			if (year) this.observe (year, "change", this.onDateChanged.bind(this));

		}

	},

	// Respond to change event on the textbox or dropdown list
	// This method raises OnDateChanged event on client side if it has been defined,
	// and raise the callback request
	onDateChanged() {
		let date;
		if (this.options.InputMode == "TextBox")
		{
			date=this.control.value;
		 }
		 else
		 {
		 	let day = Prado.WebUI.TDatePicker.getDayListControl(this.control);
			if (day) day=day.selectedIndex+1;
			let month = Prado.WebUI.TDatePicker.getMonthListControl(this.control);
			if (month) month=month.selectedIndex;
			let year = Prado.WebUI.TDatePicker.getYearListControl(this.control);
			if (year) year=year.value;
			date=new Date(year, month, day, 0,0,0).SimpleFormat(this.Format, this);
		}
		if (typeof(this.options.OnDateChanged) == "function") this.options.OnDateChanged(this, date);

		if(this.options['AutoPostBack']==true)
		{
			// Make callback request
			const request = new Prado.CallbackRequest(this.options.EventTarget,this.options);
			request.dispatch();
		}
	}
});
