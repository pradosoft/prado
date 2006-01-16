
Object.extend(Date.prototype,
{	
	SimpleFormat: function(format)
	{
		var bits = new Array();
		bits['d'] = this.getDate();
		bits['dd'] = Prado.Util.pad(this.getDate(),2);
		
		bits['M'] = this.getMonth()+1;
		bits['MM'] = Prado.Util.pad(this.getMonth()+1,2);
    
		var yearStr = "" + this.getFullYear();
		yearStr = (yearStr.length == 2) ? '19' + yearStr: yearStr;
		bits['yyyy'] = yearStr;
		bits['yy'] = bits['yyyy'].toString().substr(2,2);

		// do some funky regexs to replace the format string
		// with the real values
		var frm = new String(format);
		for (var sect in bits) 
		{
			var reg = new RegExp("\\b"+sect+"\\b" ,"g");
			frm = frm.replace(reg, bits[sect]);
		}
		return frm;
	},

	toISODate : function()
	{
		var y = this.getFullYear();
		var m = Prado.Util.pad(this.getMonth() + 1);
		var d = Prado.Util.pad(this.getDate());
		return String(y) + String(m) + String(d);
	}
});

Object.extend(Date, 
{
	SimpleParse: function(value, format)
	{	
		val=String(value);
		format=String(format);
		
		if(val.length <= 0) return null;
		
		if(format.length <= 0) return new Date(value);
			
		var isInteger = function (val) 
		{
			var digits="1234567890";
			for (var i=0; i < val.length; i++) 
			{
				if (digits.indexOf(val.charAt(i))==-1) { return false; }
			}
			return true;
		};
		
		var getInt = function(str,i,minlength,maxlength) 
		{
			for (var x=maxlength; x>=minlength; x--) 
			{
				var token=str.substring(i,i+x);
				if (token.length < minlength) { return null; }
				if (isInteger(token)) { return token; }
			}
			return null;
		};
	
		var i_val=0;
		var i_format=0;
		var c="";
		var token="";
		var token2="";
		var x,y;
		var now=new Date();
		var year=now.getFullYear();
		var month=now.getMonth()+1;
		var date=1;
	
		while (i_format < format.length) 
		{
			// Get next token from format string
			c=format.charAt(i_format);
			token="";
			while ((format.charAt(i_format)==c) && (i_format < format.length)) 
			{
				token += format.charAt(i_format++);
			}
		
			// Extract contents of value based on format token
			if (token=="yyyy" || token=="yy" || token=="y") 
			{
				if (token=="yyyy") { x=4;y=4; }
				if (token=="yy")   { x=2;y=2; }
				if (token=="y")    { x=2;y=4; }
				year=getInt(val,i_val,x,y);
				if (year==null) { return null; }
				i_val += year.length;
				if (year.length==2) 
				{
					if (year > 70) { year=1900+(year-0); }
					else { year=2000+(year-0); }
				}
			}

			else if (token=="MM"||token=="M") 
			{
				month=getInt(val,i_val,token.length,2);
				if(month==null||(month<1)||(month>12)){return null;}
				i_val+=month.length;
			}
			else if (token=="dd"||token=="d") 
			{
				date=getInt(val,i_val,token.length,2);
				if(date==null||(date<1)||(date>31)){return null;}
				i_val+=date.length;
			}
			else 
			{
				if (val.substring(i_val,i_val+token.length)!=token) {return null;}
				else {i_val+=token.length;}
			}
		}
	
		// If there are any trailing characters left in the value, it doesn't match
		if (i_val != val.length) { return null; }
		
		// Is date valid for month?
		if (month==2) 
		{
			// Check for leap year
			if ( ( (year%4==0)&&(year%100 != 0) ) || (year%400==0) ) { // leap year
				if (date > 29){ return null; }
			}
			else { if (date > 28) { return null; } }
		}
	
		if ((month==4)||(month==6)||(month==9)||(month==11)) 
		{
			if (date > 30) { return null; }
		}
		
		var newdate=new Date(year,month-1,date, 0, 0, 0);
		return newdate;
	}
});