Object.extend(String.prototype, {
	pad : function(side, len, chr) {
		if (!chr) chr = ' ';
		var s = this;
		var left = side.toLowerCase()=='left';
		while (s.length<len) s = left? chr + s : s + chr;
		return s;
	},

	padLeft : function(len, chr) {
		return this.pad('left',len,chr);
	},

	padRight : function(len, chr) {
		return this.pad('right',len,chr);
	},

	zerofill : function(len) { 
		return this.padLeft(len,'0');
	},

	trim : function() { 
		return this.replace(/^\s+|\s+$/g,'');
	},

	trimLeft : function() { 
		return this.replace(/^\s+/,''); 
	},

	trimRight : function() { 
		return this.replace(/\s+$/,'');
	},

	/**
	 * Convert period separated function names into a function reference.
	 * e.g. "Prado.AJAX.Callback.Action.setValue".toFunction() will return
	 * the actual function Prado.AJAX.Callback.Action.setValue()
	 * @return Function the corresponding function represented by the string.
	 */
	toFunction : function()
	{
		var commands = this.split(/\./);
		var command = window;
		commands.each(function(action)
		{ 
			if(command[new String(action)]) 
				command=command[new String(action)]; 
		});
		if(isFunction(command))
			return command;
		else
		{
			if(typeof Logger != "undefined")
				Logger.error("Missing function", this);
			return Prototype.emptyFunction;
		}
	},

	/** 
	 * Convert a string into integer, returns null if not integer.
	 * @return {integer|null} null if string does not represent an integer.
	 */
	toInteger : function()
	{
		var exp = /^\s*[-\+]?\d+\s*$/;
		if (this.match(exp) == null)
			return null;
		var num = parseInt(this, 10);
		return (isNaN(num) ? null : num);
	},

	/** 
	 * Convert a string into a double/float value. <b>Internationalization 
	 * is not supported</b>
	 * @param {string} the decimal character
	 * @return {float|null} null if string does not represent a float value
	 */
	toDouble : function(decimalchar)
	{
		decimalchar = decimalchar || ".";
		var exp = new RegExp("^\\s*([-\\+])?(\\d+)?(\\" + decimalchar + "(\\d+))?\\s*$");
		var m = this.match(exp);
		if (m == null)	
			return null;
		var cleanInput = m[1] + (m[2].length>0 ? m[2] : "0") + "." + m[4];
		var num = parseFloat(cleanInput);
		return (isNaN(num) ? null : num);
	},

	/**
	 * Convert strings that represent a currency value (e.g. a float with grouping 
	 * characters) to float. E.g. "10,000.50" will become "10000.50". The number 
	 * of dicimal digits, grouping and decimal characters can be specified.
	 * <i>The currency input format is <b>very</b> strict, null will be returned if
	 * the pattern does not match</i>.
	 * @param {string} the grouping character, default is ","
	 * @param {int} number of decimal digits
	 * @param {string} the decimal character, default is "."
	 * @type {float|null} the currency value as float.
	 */
	toCurrency : function(groupchar, digits, decimalchar)
	{
		groupchar = groupchar || ",";
		decimalchar = decimalchar || ".";
		digits = typeof(digits) == "undefined" ? 2 : digits;

		var exp = new RegExp("^\\s*([-\\+])?(((\\d+)\\" + groupchar + ")*)(\\d+)"
			+ ((digits > 0) ? "(\\" + decimalchar + "(\\d{1," + digits + "}))?" : "")
			+ "\\s*$");
		var m = this.match(exp);
		if (m == null)
			return null;
		var intermed = m[2] + m[5] ;
		var cleanInput = m[1] + intermed.replace(
				new RegExp("(\\" + groupchar + ")", "g"), "") 
								+ ((digits > 0) ? "." + m[7] : "");
		var num = parseFloat(cleanInput);
		return (isNaN(num) ? null : num);
	}
});
