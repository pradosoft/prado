/*! PRADO TTimeAgo javascript file | github.com/pradosoft/prado */

/**
 * TTimeAgo client class.
 *
 */


Prado.WebUI.TTimeAgo = jQuery.klass(Prado.WebUI.Control,
{
	onInit : function(options)
	{
		this.options = options || {};
		this.element = jQuery('#'+this.options.ID).get(0)
		this.startTime = (new Date()).getTime()/1000;
		this.showDT = false;
		this.rerenderTimeout = false;
		
		if(this.options.ClickToChange) {
			jQuery(this.element).css('cursor', 'pointer');
			self = this;
			jQuery(this.element).click(function(){self.eventMouseDown();});
		}
		this.drawLoop();
	},
	
	
	eventMouseDown : function()
	{
		this.showDT = !this.showDT;
		this.render();
	},
	
	
	drawLoop : function() 
	{
		this.render();
		self = this;
		this.rerenderTimeout = setTimeout(function() {self.drawLoop();}, this.getWaitTime()*1000);
	},
	
	
	getWaitTime : function ()
	{
		var current = (new Date()).getTime()/1000;
		if(!this.options.UseRawTime)
			delta = this.options.ServerTime - this.options.OriginTime + current - this.startTime;
		else
			delta = current - this.options.OriginTime;
			
		if(delta < 0) {
			return -delta;
		}
		if(delta < 60) {
			wait = 1;
		} else if(delta < 60*60) {
			tunit = Math.floor(delta/60);
			dosec = (tunit <= 3);
			wait = 60 - (delta % 60);
			if(dosec) {
				wait = 1;
			}
		} else if(delta < 60*60*24) {
			tunit = Math.floor(delta/(60*60)) ;
			wait = (60*60) - (delta % (60*60));
			if(tunit <= 2) {
				wait = (60) - (delta % (60));
			}
		} else if(delta < 60*60*24*7) {
			tunit = Math.floor(delta/(60*60*24));
			wait = (60*60*24) - (delta % (60*60*24));
			if(tunit <= 2) {
				wait = (60*60) - (delta % (60*60));
			}
		} else if(delta < 60*60*24*30) {
			tunit = Math.floor(delta/(60*60*24*7));
			wait = (60*60*24*7) - (delta % (60*60*24*7));
			if(tunit <= 2) {
				wait = (60*60*24) - (delta % (60*60*24));
			}
		} else if(delta < 60*60*24*365) {
			tunit = Math.floor(delta/(60*60*24*30))
			wait = (60*60*24*30) - (delta % (60*60*24*30));
			if(tunit <= 2) {
				wait = (60*60*24*7) - ((delta % (60*60*24*30)) % (60*60*24*7));
			}
		} else {
			wait = 3600;
		}
		return wait;
	},
	
	
	render : function()
	{
		var current = (new Date()).getTime()/1000;
		delta = current - this.options.OriginTime;
		
		if(!this.options.UseRawTime)
			delta += this.options.ServerTime - this.startTime;
		
		if(delta < 0) {
			preText = 'in ';
			postText = '';
			delta *= -1;
			isFuture = true;
		} else {
			preText = '';
			postText = ' ago';
			isFuture = false;
		}
		/*
		{0} second ago
		{0} seconds ago
		{0} minute ago
		{0} minutes ago
		{0} minute {1} second ago
		{0} minute {1} seconds ago
		{0} minutes {1} second ago
		{0} minutes {1} seconds ago
		{0} hour ago
		{0} hour {1} min ago
		{0} hour {1} mins ago
		{0} hours {1} min ago
		{0} hours {1} mins ago
		{0} hours ago
		{0} day ago
		{0} day {1} hour ago
		{0} day {1} hours ago
		{0} days {1} hour ago
		{0} days {1} hours ago
		{0} days ago
		{0} week ago
		{0} week {1} day ago
		{0} week {1} days ago
		{0} weeks {1} day ago
		{0} weeks {1} days ago
		{0} weeks ago
		{0} month ago
		{0} month {1} week ago
		{0} month {1} weeks ago
		{0} months {1} week ago
		{0} months {1} weeks ago
		{0} months ago
		*/
		if(delta < 60) {
			seconds = Math.floor(delta);
			str = this.options. Localize['second' + (seconds != 1 ? 's' : '')];
			str = str.replace('{0}', seconds);
		} else if(delta < 60*60) {
			minutes = Math.floor(delta/60);
			seconds = Math.floor(delta - minutes * 60);
			str = this.options. Localize['minute' + (minutes != 1 ? 's' : '') + (minutes <= 3 && seconds != 0 ? ('second' + (seconds != 1 ? 's' : '')) : '')];
			str = str.replace('{0}', minutes);
			if (minutes <= 3) {
				str = str.replace('{1}', seconds);
			}
		} else if(delta < 60*60*24) {
			hours = Math.floor(delta/(60*60));
			minutes = Math.floor(delta/60 - hours * 60);
			str = this.options. Localize['hour' + (hours != 1 ? 's' : '') + (hours <= 2 && minutes != 0 ? ('minute' + (minutes != 1 ? 's' : '')) : '')];
			str = str.replace('{0}', hours);
			if (hours <= 2) {
				str = str.replace('{1}', minutes);
			}
		} else if(delta < 60*60*24*7) {
			days = Math.floor(delta/(60*60*24));
			hours = Math.floor(delta/(60*60) - days * 24);
			str = this.options. Localize['day' + (days != 1 ? 's' : '') + (days <= 2 && hours != 0 ? ('hour' + (hours != 1 ? 's' : '')) : '')];
			str = str.replace('{0}', days);
			if (days <= 2) {
				str = str.replace('{1}', hours);
			}
		} else if(delta < 60*60*24*30) {
			weeks = Math.floor(delta/(60*60*24*7));
			days = Math.floor(delta/(60*60*24) - weeks * 7);
			str = this.options. Localize['week' + (weeks != 1 ? 's' : '') + (weeks <= 2 && days != 0 ? ('day' + (days != 1 ? 's' : '')) : '')];
			str = str.replace('{0}', weeks);
			if (weeks <= 2) {
				str = str.replace('{1}', days);
			}
		} else if(delta < 60*60*24*365) {
			months = Math.floor(delta/(60*60*24*30));
			weeks = Math.floor((delta/(60*60*24) - months * 30) / 7);
			str = this.options. Localize['month' + (months != 1 ? 's' : '') + (months <= 2 && weeks != 0 ? ('week' + (weeks != 1 ? 's' : '')) : '')];
			str = str.replace('{0}', months);
			if (months <= 2) {
				str = str.replace('{1}', weeks);
			}
		} else {
			date = new Date();
			date.setTime(this.options.OriginTime * 1000);
			options = {year: 'numeric', month: 'short'};
			str = new Intl.DateTimeFormat('default', options).format(date);
		}
		if(isFuture) {
			date = new Date();
			date.setTime(this.options.OriginTime * 1000);
			
			options = {year: 'numeric', month: 'short', day: 'numeric'};
			theDate = new Intl.DateTimeFormat('default', options).format(date);
			options = {hour: 'numeric', minute: 'numeric', second: 'numeric'};
			theTime = new Intl.DateTimeFormat('default', options).format(date);
			str = 'on ' + theDate + ' at ' + theTime + ' in the future';
		}
		if(this.showDT) {
			date = new Date();
			date.setTime(this.options.OriginTime * 1000);
			options = {
				  year: 'numeric', month: 'long', day: 'numeric',
				  hour: 'numeric', minute: 'numeric', second: 'numeric'
				};
			str = new Intl.DateTimeFormat('default', options).format(date);
		}
		this.element.innerHTML = str;
	}
});
