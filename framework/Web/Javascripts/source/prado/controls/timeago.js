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
				wait = (60*60*24*7) - (delta % (60*60*24*7));
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
		{0} min {1} sec ago
		{0} min {1} secs ago
		{0} mins {1} sec ago
		{0} mins {1} secs ago
		{0} minutes ago
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
		date format- short month year: {mon} {year}
		date format- long year, month, day, hour, minute, secod
		*/
		if(delta < 60) {
			tunit = Math.floor(delta) ;
			str = preText + tunit + " second" + (tunit != 1?'s':'') + postText;
		} else if(delta < 60*60) {
			tunit = Math.floor(delta/60);
			if(tunit <= 3) {
				minute = "min";
				dosec = true;
			} else {
				minute = "minute";
				dosec = false;
			}
			str = tunit + " "+minute + (tunit != 1?'s':'');
			if(dosec) {
				tunit = Math.floor(delta - tunit * 60) ;
				if(tunit)
					str += ' ' + tunit + " sec" + (tunit != 1?'s':'') ;
			}
			str = preText + str + postText;
		} else if(delta < 60*60*24) {
			tunit = Math.floor(delta/(60*60)) ;
			str = tunit + " hour" + (tunit != 1?'s':'');
			if(tunit <= 2) {
				tunit = Math.floor(delta/60 - tunit * 60) ;
				if(tunit)
					str += ' ' + tunit + " min" + (tunit != 1?'s':'') ;
			}
			str = preText + str + postText;
		} else if(delta < 60*60*24*7) {
			tunit = Math.floor(delta/(60*60*24));
			str = tunit + " day" + (tunit != 1?'s':'');
			if(tunit <= 2) {
				tunit = Math.floor(delta/(60*60) - tunit * 24) ;
				if(tunit)
					str += ' ' + tunit + " hour" + (tunit != 1?'s':'') ;
			}
			str = preText + str + postText;
		} else if(delta < 60*60*24*30) {
			tunit = Math.floor(delta/(60*60*24*7));
			str = tunit + " week" + (tunit != 1?'s':'');
			if(tunit <= 2) {
				tunit = Math.floor(delta/(60*60*24) - tunit * 7) ;
				if(tunit)
					str += ' ' + tunit + " day" + (tunit != 1?'s':'') ;
			}
			str = preText + str + postText;
		} else if(delta < 60*60*24*365) {
			tunit = Math.floor(delta/(60*60*24*30))
			str = tunit + " month" + (tunit != 1?'s':'');
			if(tunit <= 2) {
				tunit = Math.floor((delta/(60*60*24) - tunit * 30) / 7) ;
				if(tunit)
					str += ' ' + tunit + " week" + (tunit != 1?'s':'') ;
			}
			str = preText + str + postText;
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



/**
 *
 * @param server_now   time() of generation of control
 * @param server_origin   origin time
 * @param client_start   time of first run on the client side
 * @param id   which control to run the time on
 * @param canClickToChange   option to click the time to change to the actual date/time rather than the time ago
 */
function CountDown(server_now, server_origin, client_start, id, canClickToChange, rawtime) {
	if(!$(id)) return;
	if(typeof canClickToChange == 'undefined')
		canClickToChange = true;
	
	if(typeof rawtime == 'undefined')
		rawtime = false;
	
	var current = (new Date()).getTime()/1000;
	if(!client_start) {
		timeAgo[id] = server_origin;
		client_start = current;
		timeAgoType[id] = false;
		
		if(canClickToChange) {
			$(id).observe('click', function() {
					timeAgoType[id] = !timeAgoType[id];
					CountDown(server_now, server_origin, client_start, id, canClickToChange, rawtime);
				});
			$(id).setStyle({cursor: 'pointer'});
		}
	}
	showDT = timeAgoType[id];
	
	//This is in case a new timer is set on the same object (actively changing html)
	//	stop the prior one because the time ago for that control is expired
	if(timeAgo[id] != server_origin)
		return;
	
	// too much delay in page starting for this to be accurate within seconds
	if(!rawtime)
		delta = server_now - server_origin + current - client_start;
	else
		delta = current - server_origin;
	//			alert(server_now+ ' ' + server_origin + "     " + current + " " +client_start);
	
	if(delta < 60) {
		tunit = Math.floor(delta) ;
		str = tunit + " second" + (tunit != 1?'s':'') + " " + postText;
		wait = 1;
	} else if(delta < 60*60) {
		tunit = Math.floor(delta/60);
		if(tunit <= 3) {
			minute = "min";
			dosec = true;
		} else {
			minute = "minute";
			dosec = false;
		}
		str = tunit + " "+minute + (tunit != 1?'s':'');
		wait = 60 - (delta % 60);
		if(dosec) {
			tunit = Math.floor(delta - tunit * 60) ;
			if(tunit)
				str += ' ' + tunit + " sec" + (tunit != 1?'s':'') ;
			wait = 1;
		}
		str += " " + postText;
	} else if(delta < 60*60*24) {
		tunit = Math.floor(delta/(60*60)) ;
		str = tunit + " hour" + (tunit != 1?'s':'');
		wait = (60*60) - (delta % (60*60));
		if(tunit <= 2) {
			tunit = Math.floor(delta/60 - tunit * 60) ;
			if(tunit)
				str += ' ' + tunit + " min" + (tunit != 1?'s':'') ;
			wait = (60) - (delta % (60));
		}
		str += " " + postText;
	} else if(delta < 60*60*24*7) {
		tunit = Math.floor(delta/(60*60*24));
		str = tunit + " day" + (tunit != 1?'s':'');
		wait = (60*60*24) - (delta % (60*60*24));
		if(tunit <= 2) {
			tunit = Math.floor(delta/(60*60) - tunit * 24) ;
			if(tunit)
				str += ' ' + tunit + " hour" + (tunit != 1?'s':'') ;
			wait = (60*60) - (delta % (60*60));
		}
		str += " " + postText;
	} else if(delta < 60*60*24*30) {
		tunit = Math.floor(delta/(60*60*24*7));
		str = tunit + " week" + (tunit != 1?'s':'');
		wait = (60*60*24*7) - (delta % (60*60*24*7));
		if(tunit <= 2) {
			tunit = Math.floor(delta/(60*60*24) - tunit * 7) ;
			if(tunit)
				str += ' ' + tunit + " day" + (tunit != 1?'s':'') ;
			wait = (60*60*24) - (delta % (60*60*24));
		}
		str += " " + postText;
	} else if(delta < 60*60*24*365) {
		tunit = Math.floor(delta/(60*60*24*30))
		str = tunit + " month" + (tunit != 1?'s':'');
		wait = (60*60*24*30) - (delta % (60*60*24*30));
		if(tunit <= 2) {
			tunit = Math.floor((delta/(60*60*24) - tunit * 30) / 7) ;
			if(tunit)
				str += ' ' + tunit + " week" + (tunit != 1?'s':'') ;
			wait = (60*60*24*7) - (delta % (60*60*24*7));
		}
		str += " " + postText;
	} else {
		date = new Date();
		date.setTime(server_origin * 1000);
		mo = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Ocr", "Nov", "Dec"];
		str = mo[date.getMonth()] + " " + date.getFullYear();
		wait = 1;
	}
	if(isFuture) {
		var estr = '';
		date = new Date();
		date.setTime((server_origin) * 1000);
		
		var h = date.getHours();
		if(h < 12)
			ampm = 'a.m.';
		else {
			ampm = 'p.m.';
			h -= 12;
		}
		if(h == 0)
			h += 12;
		
		
		day = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
		mo = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Ocr", "Nov", "Dec"];
		str += " on " + day[date.getDay()] + ' ' + mo[date.getMonth()] + ' ' + 
			date.getDate() + ' ' + date.getFullYear() + ' at '  +
			h + ':' + twoDigit(date.getMinutes()) + ' ' + ampm;
	}
	if(showDT) {
		date = new Date();
		date.setTime((server_origin) * 1000);
		str = date.getFullYear() + '/' + (date.getMonth()+1) + '/' + date.getDate() + ' ' + 
				date.getHours() + ':' + twoDigit(date.getMinutes()) + ':' + twoDigit(date.getSeconds());
	}
	if($(id)) {
		$(id).innerHTML = str;
		setTimeout(function() {
				CountDown(server_now, server_origin, client_start, id, canClickToChange, rawtime);
			}, wait*1000);
	}
}
