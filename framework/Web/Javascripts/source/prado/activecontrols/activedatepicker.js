/**
 * TActiveDatePicker control
 */
Prado.WebUI.TActiveDatePicker = Class.create(Prado.WebUI.TDatePicker,
{
	onInit : function(options)
	{
		this.options = options || [];
		this.control = $(options.ID);
		this.dateSlot = new Array(42);
		this.weekSlot = new Array(6);
		this.minimalDaysInFirstWeek	= 4;
		this.selectedDate = this.newDate();
		this.positionMode = 'Bottom';


		//which element to trigger to show the calendar
		if(this.options.Trigger)
		{
			this.trigger = $(this.options.Trigger) ;
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

		Object.extend(this,options);

		if (this.options.ShowCalendar)
			this.observe(this.trigger, triggerEvent, this.show.bindEvent(this));
		
		// Listen to change event 
		if(this.options.InputMode == "TextBox")
		{
			this.observe(this.control, "change", this.onDateChanged.bindEvent(this));
		} 
		else
		{
			var day = Prado.WebUI.TDatePicker.getDayListControl(this.control);
			var month = Prado.WebUI.TDatePicker.getMonthListControl(this.control);
			var year = Prado.WebUI.TDatePicker.getYearListControl(this.control);
			if (day) this.observe (day, "change", this.onDateChanged.bindEvent(this));
			if (month) this.observe (month, "change", this.onDateChanged.bindEvent(this));
			if (year) this.observe (year, "change", this.onDateChanged.bindEvent(this));
				
		}

	},	
	
	// Respond to change event on the textbox or dropdown list
	// This method raises OnDateChanged event on client side if it has been defined,
	// and raise the callback request
	onDateChanged : function ()
	{
		var date;
		if (this.options.InputMode == "TextBox")
		{
			date=this.control.value;
		 } 
		 else
		 {
		 	var day = Prado.WebUI.TDatePicker.getDayListControl(this.control);
			if (day) day=day.selectedIndex+1;
			var month = Prado.WebUI.TDatePicker.getMonthListControl(this.control);
			if (month) month=month.selectedIndex;
			var year = Prado.WebUI.TDatePicker.getYearListControl(this.control);
			if (year) year=year.value;
			date=new Date(year, month, day, 0,0,0).SimpleFormat(this.Format, this);
		}
		if (typeof(this.options.OnDateChanged) == "function") this.options.OnDateChanged(this, date);
		
		if(this.options['AutoPostBack']==true)
		{
			// Make callback request
			var request = new Prado.CallbackRequest(this.options.EventTarget,this.options);
			request.dispatch();
		}
	}
}); 
